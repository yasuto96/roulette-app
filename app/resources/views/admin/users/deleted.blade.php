<x-app-layout>
  <div class="max-w-6xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold">管理者：削除済みユーザー</h1>
      <a href="{{ route('admin.users.index') }}" class="px-3 py-2 border rounded">← 有効ユーザー一覧へ</a>
    </div>

    @if (session('success'))
      <div class="mb-3 text-green-700">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto bg-white shadow rounded">
      <table class="w-full table-fixed text-sm">
        <colgroup>
          <col style="width:18%">
          <col style="width:22%">
          <col style="width:34%">  {{-- メール --}}
          <col style="width:18%">
          <col style="width:8%">   {{-- 操作 --}}
        </colgroup>

        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left">登録日</th>
            <th class="px-4 py-2 text-left">ユーザー名</th>
            <th class="px-4 py-2 text-left">メールアドレス</th> {{-- 追加 --}}
            <th class="px-4 py-2 text-left">最終ログイン</th>
            <th class="px-2 py-2 text-right">操作</th>
          </tr>
        </thead>

        <tbody>
          @forelse ($users as $u)
            <tr class="border-t">
              <td class="px-4 py-2">{{ optional($u->created_at)->format('Y-m-d H:i') }}</td>
              <td class="px-4 py-2">{{ $u->name }}</td>
              <td class="px-4 py-2">
                <span class="block truncate" title="{{ $u->email }}">{{ $u->email }}</span>
              </td>
              <td class="px-4 py-2">
                {{ $u->last_login_at ? \Carbon\Carbon::parse($u->last_login_at)->format('Y-m-d H:i') : '—' }}
              </td>
              <td class="pr-3 py-2 text-right">
                <form method="POST" action="{{ route('admin.users.restore', $u->id) }}" class="inline">
                  @csrf @method('PATCH')
                  <button class="px-2 py-1 border rounded">復元</button>
                </form>
                {{-- 物理削除ボタンを使う場合はコメント解除
                <form method="POST" action="{{ route('admin.users.forceDelete', $u->id) }}" class="inline"
                      onsubmit="return confirm('完全に削除します。元に戻せません。よろしいですか？');">
                  @csrf @method('DELETE')
                  <button class="px-2 py-1 border rounded ml-2">完全削除</button>
                </form>
                --}}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-4 py-8 text-center text-gray-500">削除済みユーザーはいません</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
  </div>
</x-app-layout>
