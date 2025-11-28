<x-app-layout>
  <div class="max-w-6xl mx-auto px-4 py-6" x-data="userStatsModal()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold">管理者：ユーザー一覧</h1>
      <div class="flex items-center gap-2">
          <button type="button" class="px-3 py-2 border rounded" @click="open()">詳細</button>
          <a href="{{ route('admin.users.deleted') }}" class="px-3 py-2 border rounded">削除一覧</a>
        </div>
    </div>

    @if(session('success'))
      <div class="mb-3 text-green-700">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto bg-white shadow rounded"> {{-- p-0 でOK（デフォで余白なし） --}}
      <table class="w-full table-fixed text-sm">
        <colgroup>
          <col style="width:18%">
          <col style="width:22%">
          <col style="width:34%">
          <col style="width:18%">
          <col style="width:8%">
        </colgroup>

        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left">登録日</th>
            <th class="px-4 py-2 text-left">ユーザー名</th>
            <th class="px-4 py-2 text-left">メールアドレス</th>
            <th class="px-4 py-2 text-left">最終ログイン</th>
            <th class="px-2 py-2 text-right">操作</th> {{-- 右端は余白小さめ --}}
          </tr>
        </thead>

        <tbody>
          @forelse($users as $u)
            <tr class="border-t">
              <td class="px-4 py-2">{{ optional($u->created_at)->format('Y-m-d H:i') }}</td>
              <td class="px-4 py-2">{{ $u->name }}</td>
              <td class="px-4 py-2">
                <span class="block truncate" title="{{ $u->email }}">{{ $u->email }}</span>
              </td>
              <td class="px-4 py-2">
                {{ $u->last_login_at ? \Carbon\Carbon::parse($u->last_login_at)->format('Y-m-d H:i') : '—' }}
              </td>
              <td class="pr-3 py-2 text-right"> {{-- pr-3 で右端の空きを圧縮 --}}
                <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                      onsubmit="return confirm('このユーザーを削除しますか？')">
                  @csrf @method('DELETE')
                  <button class="px-2 py-1 border rounded">削除</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-4 py-8 text-center text-gray-500">ユーザーがいません</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
  
    {{-- ▼▼▼ 詳細モーダル ▼▼▼ --}}
    <div x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="close()"></div>

      <div class="relative bg-white w-full max-w-2xl rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">ユーザー詳細</h2>
          <button type="button" class="px-3 py-1 border rounded" @click="close()">戻る</button>
        </div>

        {{-- サマリ --}}
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <div class="text-base font-medium text-gray-700">全ユーザー数</div>
                <div class="h-0.5 w-12 bg-gray-300 my-2"></div>
                <div class="text-3xl font-bold leading-none"
                    x-text="(stats.total_users ?? '—')"></div>       
            </div>
        </div>

       <!-- 月選択（単月カウント） -->
        <div class="mb-6">
          <label class="text-sm text-gray-600 mr-2">月</label>
          <input type="month" x-model="month" class="border rounded px-2 py-1">
          <!-- 処理中は無効＆表示切替 -->
          <button
            class="ml-2 px-3 py-1 border rounded"
            :class="isLoading ? 'opacity-60 cursor-not-allowed' : ''"
            :disabled="isLoading"
            @click="loadStats()"
          >
            <span x-show="!isLoading">再計算</span>
            <span x-show="isLoading">計算中...</span>
          </button>

          <div class="mt-2 text-sm">
            選択月の登録者数：
            <span class="font-semibold"
              x-text="(stats.monthly === null ? '—' : stats.monthly)"></span> 人
          </div>
        </div>

        <!-- Excel 出力 -->
        <div class="mb-2 font-semibold">Excel出力</div>
        <div class="flex items-center gap-3 mb-4">
          <div class="flex items-center gap-2">
            <span class="text-sm">月</span>
            <input type="month" x-model="from" class="border rounded px-2 py-1" @input="allSelected=false">
            <span class="mx-1">〜</span>
            <input type="month" x-model="to" class="border rounded px-2 py-1" @input="allSelected=false">
          </div>

          <!-- 押したら見た目が変わるトグルボタン -->
          <button
            class="px-3 py-1 border rounded transition"
            :class="allSelected
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
            @click="toggleAll()"
          >
            全ユーザー
          </button>
        </div>


        <div class="text-right">
          <a :href="exportUrl()" class="inline-block px-4 py-2 border rounded">出力</a>
        </div>
      </div>
    </div>
    {{-- ▲▲▲ 詳細モーダル ▲▲▲ --}}
  </div>
   {{-- Alpine スクリプト（このページだけで完結） --}}
  <script>
  function userStatsModal() {
    return {
      show: false,
      month: '',
      from: '',
      to: '',
      allSelected: false,   // ← 追加：全ユーザー選択状態
      isLoading: false,     // ← 追加：再計算中フラグ
      stats: { total_users: null, total_members: null, monthly: null },

      init() {
        this.month = new Date().toISOString().slice(0, 7); // 'YYYY-MM'
        this.loadStats();
      },

      open()  { this.show = true; this.loadStats(); },
      close() { this.show = false; },

      async loadStats() {
        this.isLoading = true;
        try {
          const params = new URLSearchParams();
          if (this.month) params.set('month', this.month);

          const r = await fetch('{{ route('admin.users.stats') }}' + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
          });
          if (!r.ok) throw new Error('HTTP ' + r.status);
          const ct = r.headers.get('content-type') || '';
          if (!ct.includes('application/json')) throw new Error('Not JSON');

          const data = await r.json();
          this.stats = {
            total_users:   Number.isFinite(data.total_users)   ? data.total_users   : 0,
            total_members: Number.isFinite(data.total_members) ? data.total_members : 0,
            monthly:       (data.monthly === null || Number.isFinite(data.monthly)) ? data.monthly : 0
          };
        } catch (err) {
          console.error('stats load failed:', err);
          this.stats = { total_users: 0, total_members: 0, monthly: 0 };
        } finally {
          this.isLoading = false;
        }
      },

      toggleAll() {
        this.allSelected = !this.allSelected;
        if (this.allSelected) {
          // 全ユーザー出力：期間条件クリア
          this.from = '';
          this.to = '';
        }
      },

      exportUrl() {
        const url = new URL('{{ route('admin.users.export') }}', window.location.origin);
        if (this.allSelected || (!this.from && !this.to)) {
          url.searchParams.set('scope', 'all');   // 全件
        } else {
          url.searchParams.set('from', this.from || '');
          url.searchParams.set('to',   this.to   || '');
        }
        return url.toString();
      }
    }
  }
  </script>


</x-app-layout>
