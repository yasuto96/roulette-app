<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      @php
        // $favorites が Paginator / Collection / 配列のどれでも件数が出るように
        $count =
            ($favorites instanceof \Illuminate\Pagination\LengthAwarePaginator) ? $favorites->total() :
            ($favorites instanceof \Illuminate\Contracts\Pagination\Paginator)  ? count($favorites->items()) :
            (is_countable($favorites) ? count($favorites) : 0);
      @endphp

      <h2 class="font-semibold text-xl">お気に入り <span class="text-gray-500 text-base">（<span id="favCount">{{ $count }}</span> 件）</span></h2>

      <a href="{{ url()->previous() }}"
         onclick="if (history.length>1){history.back();return false;}"
         class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5
                text-sm font-medium bg-white hover:bg-gray-50 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        戻る
      </a>
    </div>
  </x-slot>

  @forelse($favorites as $fav)
    @php
      $r   = $fav->restaurant;
      $lh  = optional($r->histories->first()); // 最新履歴（この会員の）
      $date = $lh? ($lh->visited_at ? \Carbon\Carbon::parse($lh->visited_at)->format('Y-m-d') : \Carbon\Carbon::parse($lh->created_at)->format('Y-m-d'))
                 : ($r->created_at? $r->created_at->format('Y-m-d') : '');
      $rating = (int) ($lh->my_rating ?? 0);
      $memo   = trim($lh->memo ?? '');
    @endphp

    <div id="fav-{{ $fav->id }}"
         class="rounded-2xl border bg-gray-50 hover:bg-white transition p-5 mb-4 history-row"
         data-restaurant-id="{{ $r->id }}">
      {{-- 1行目：日付 / 店名 / 自分の評価 / 右端に⭐ --}}
      <div class="grid items-center gap-x-4" style="grid-template-columns: 7.5rem 1fr 14rem auto;">
        <div class="text-base text-gray-500">{{ $date }}</div>
        <div class="min-w-0 font-semibold text-xl truncate">{{ $r->name }}</div>
        <div class="justify-self-center text-lg text-gray-700" data-slot="my-rating">
          自分の評価：
          @for($i=1;$i<=5;$i++)
            <span class="mx-0.5 {{ $rating >= $i ? 'text-amber-500 font-bold' : 'text-gray-400' }}">{{ $i }}</span>
          @endfor
        </div>
        <div class="justify-self-end flex items-center gap-4">
          <button type="button"
                  class="fav-btn leading-none text-amber-400"
                  style="font-size:34px"
                  title="お気に入りを解除">★</button>
        </div>
      </div>

      {{-- 2行目：コメントだけ --}}
      <div class="mt-2 pl-32 text-base text-gray-600" data-slot="memo">
        {{ $memo !== '' ? $memo : '（コメントはまだありません）' }}
      </div>
    </div>
  @empty
    <p class="p-6 text-gray-500">お気に入りはまだありません。</p>
  @endforelse

  <div class="mt-4">{{ $favorites->links() }}</div>

  <script>
  (() => {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const countEl = document.getElementById('favCount');

    // ★クリックでトグル（OFF → 行を消す）
    document.querySelectorAll('.fav-btn').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const box = btn.closest('[data-restaurant-id]');
        const rid = box?.dataset.restaurantId;
        if (!rid) { alert('店舗IDが不明です'); return; }

        const res = await fetch(`{{ route('favorites.toggle') }}`, {
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN': token,'Accept':'application/json'},
          body: JSON.stringify({ restaurant_id: rid })
        });
        if (!res.ok) { alert('お気に入り更新に失敗しました'); return; }
        const j = await res.json();

        if (!j.favorited) {
          // 解除されたので行を削除して件数デクリメント
          const row = btn.closest('[id^="fav-"]');
          if (row) row.remove();
          if (countEl) countEl.textContent = Math.max(0, (+countEl.textContent || 0) - 1);
        } else {
          // （理論上ここには来ない＝一覧では常に解除動作になる想定）
        }
      });
    });
  })();
  </script>
</x-app-layout>
