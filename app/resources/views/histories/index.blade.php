<x-app-layout>
    {{-- ヘッダー（任意） --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl">履歴</h2>

      {{-- 戻るボタン（履歴があれば JS で戻る／無ければ直リンク） --}}
      <a href="{{ url()->previous() }}"
        onclick="if (window.history.length > 1) { history.back(); return false; }"
        class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5
                text-sm font-medium bg-white hover:bg-gray-50 shadow-sm">
        {{-- 左向き矢印アイコン --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        戻る
      </a>
    </div>
  </x-slot>

  {{-- 履歴行 --}}
  @forelse($histories as $h)
    @php
    $crit    = is_array($h->criteria) ? $h->criteria : (json_decode($h->criteria ?? '[]', true) ?? []);
    $r       = $crit['restaurant'] ?? [];
    $visited = $h->visited_at ? \Carbon\Carbon::parse($h->visited_at)->format('Y-m-d')
                              : $h->created_at->format('Y-m-d');
    $rating  = (int)($h->my_rating ?? 0);
    $isFav   = $h->restaurant_id && in_array($h->restaurant_id, $favoriteIds ?? []);
    $cmt     = trim($h->memo ?? '');
  @endphp

  <div id="row-{{ $h->id }}"
     class="history-row cursor-pointer select-none rounded-2xl border bg-gray-50 hover:bg-white transition p-5 mb-4"
     data-id="{{ $h->id }}"
     data-restaurant-id="{{ $h->restaurant_id ?? '' }}"
     data-name="{{ $r['name'] ?? $h->name }}"
     data-address="{{ $r['address'] ?? '' }}"
     data-rating="{{ $r['rating'] ?? '' }}"
     data-cuisines='@json($r['cuisines'] ?? [])'
     data-place-id="{{ $r['place_id'] ?? '' }}"
     data-lat="{{ $r['lat'] ?? '' }}"
     data-lng="{{ $r['lng'] ?? '' }}"
     data-visited="{{ $h->visited_at ?? '' }}"
     data-my-rating="{{ $h->my_rating ?? '' }}"
     data-memo="{{ $h->memo ?? '' }}">

    {{-- 1行目：日付 / 店名 / 自分の評価（中央） / 右端に⭐+編集+削除 --}}
    <div class="grid items-center gap-x-4"
        style="grid-template-columns: 7.5rem 1fr 14rem auto;">
      {{-- col1: 日付 --}}
      <div class="text-base text-gray-500">{{ $visited }}</div>

      {{-- col2: 店名（大きめ） --}}
      <div class="min-w-0 font-semibold text-xl truncate">
        {{ $r['name'] ?? $h->name }}
      </div>

      {{-- col3: 自分の評価（中央寄せ） --}}
      <div class="justify-self-center text-lg text-gray-700" data-slot="my-rating">
        自分の評価：
        @for($i=1; $i<=5; $i++)
          <span class="mx-0.5 {{ $rating >= $i ? 'text-amber-500 font-bold' : 'text-gray-400' }}">
            {{ $i }}
          </span>
        @endfor
      </div>

      {{-- col4: 右端（⭐ + 縦並びの編集/削除） --}}
      <div class="justify-self-end flex items-center gap-4"
          data-id="{{ $h->id }}"
          data-restaurant-id="{{ $h->restaurant_id ?? '' }}"
          data-visited="{{ $h->visited_at ?? '' }}"
          data-my-rating="{{ $h->my_rating ?? '' }}"
          data-memo="{{ $h->memo ?? '' }}">
        {{-- お気に入りスター（大きめ） --}}
        <button type="button"
          class="fav-btn leading-none {{ $isFav ? 'text-amber-400' : 'text-gray-300' }}"
          style="font-size:34px"
          title="{{ $h->restaurant_id ? 'お気に入りに追加/解除' : '店舗IDが無いのでお気に入りできません' }}">
          ★
        </button>


        {{-- 編集/削除（縦2段） --}}
        <div class="flex flex-col gap-2 items-stretch">
          <button type="button"
                  class="edit-btn px-3 py-1.5 rounded-md border text-sm bg-white hover:bg-gray-50">
            編集
          </button>
          <form method="POST" action="{{ route('histories.destroy', $h->id) }}"
                onclick="event.stopPropagation()"
                onsubmit="return confirm('削除しますか？');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="px-3 py-1.5 rounded-md border text-sm text-red-600 bg-red-50 hover:bg-red-100">
              削除
            </button>
          </form>
        </div>
      </div>
    </div>

    {{-- 2行目：コメントだけ（店名の下位置に来るよう、日付幅ぶんだけインデント） --}}
    <div class="mt-2 pl-32 text-base text-gray-600" data-slot="memo">
      @if($cmt !== '')
        {{ $cmt }}
      @else
        （コメントはまだありません）
      @endif
    </div>
  </div>


  @empty
    <p class="p-6 text-gray-500">履歴はまだありません。</p>
  @endforelse

  <div class="mt-4">{{ $histories->links() }}</div>

  {{-- 編集モーダル --}}
  <div id="editModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 w-[min(92vw,640px)] rounded-2xl bg-white p-6 shadow-xl">
      <div class="text-xl font-semibold mb-4">履歴を編集</div>
      <form id="editForm" class="space-y-4">
        <input type="hidden" id="em-id">
        <div>
          <label class="block text-sm font-medium">日付</label>
          <input type="date" id="em-visited" class="mt-1 w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">評価（1〜5）</label>
          <select id="em-rating" class="mt-1 w-full border rounded px-3 py-2">
            <option value="">未評価</option>
            <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium">コメント</label>
          <textarea id="em-memo" rows="5" class="mt-1 w-full border rounded px-3 py-2"></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" id="em-cancel" class="border rounded px-4 py-2">戻る</button>
          <button type="submit" class="bg-blue-600 text-white rounded px-4 py-2">登録</button>
        </div>
      </form>
    </div>
  </div>


  {{-- 詳細モーダル --}}
  <div id="historyModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative z-10 w-[min(92vw,720px)] rounded-2xl bg-white p-6 shadow-xl">
      <button id="hmClose" class="absolute right-3 top-3 rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600 hover:bg-gray-200">
        ×
      </button>
      <div class="mb-4 text-center text-2xl font-bold" id="hmName">店名</div>

      <div class="space-y-2 text-sm">
        <div id="hmCuisines" class="text-gray-700"></div>
        <div id="hmAddress"  class="text-gray-700"></div>
        <div id="hmRating"   class="text-gray-700"></div>
        <div id="hmHours"    class="text-gray-700 whitespace-pre-line"></div>
      </div>

      <div class="mt-5 flex gap-3 justify-end">
        <a id="hmMap" href="#" target="_blank" rel="noopener"
          class="inline-flex items-center rounded-lg border-2 border-sky-500 bg-sky-50 px-4 py-2 font-semibold text-sky-700 hover:bg-sky-100">
          Googleマップで開く
        </a>
        <button id="hmClose2"
          class="inline-flex items-center rounded-lg border px-4 py-2">閉じる</button>
      </div>
    </div>
  </div>
  <script>
  (() => {
    const token = document.querySelector('meta[name="csrf-token"]').content;

    // --- 編集モーダル ---
    const em = document.getElementById('editModal');
    const emId = document.getElementById('em-id');
    const emVisited = document.getElementById('em-visited');
    const emRating = document.getElementById('em-rating');
    const emMemo = document.getElementById('em-memo');
    const openEm = () => { em.classList.remove('hidden'); em.classList.add('flex'); };
    const closeEm = () => { em.classList.add('hidden'); em.classList.remove('flex'); };
    document.getElementById('em-cancel').onclick = closeEm;
    em.addEventListener('click', e => { if (e.target === em) closeEm(); });

    // 編集ボタン
    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();                           // ← 行クリックを止める
        const row = btn.closest('.history-row');
        emId.value       = row.dataset.id;
        emVisited.value  = (row.dataset.visited || '').split('T')[0]; // YYYY-MM-DD だけ
        emRating.value   = row.dataset.myRating || '';
        emMemo.value     = row.dataset.memo || '';
        openEm();
      });
    });
    // 編集送信
    document.getElementById('editForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = emId.value;
      const res = await fetch(`{{ route('histories.update', ['history' => '__ID__']) }}`.replace('__ID__', id), {
        method: 'PATCH',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': token,'Accept':'application/json'},
        body: JSON.stringify({
          visited_at: emVisited.value || null,
          my_rating:  emRating.value || null,
          memo:       emMemo.value || null,
        })
      });
      if (!res.ok) { alert('更新に失敗しました'); return; }
      const j = await res.json();

      // 画面反映
      const row = document.getElementById(`row-${id}`);
      if (row) {
        // 行データも更新（次回編集の既定値に反映）
        row.dataset.visited   = j.row.visited_at || '';
        row.dataset.myRating  = j.row.my_rating || '';
        row.dataset.memo      = j.row.memo || '';

        // 表示の方
        // 日付（左カラム）
        const dateEl = row.querySelector('.text-base.text-gray-500');
        if (dateEl && j.row.visited_at) dateEl.textContent = j.row.visited_at.substring(0,10);

        // 自分の評価（中央の 1 2 3 4 5）
        const ratingNum = Number(j.row.my_rating || 0);
        const ratingBox = row.querySelector('[data-slot="my-rating"]');
        if (ratingBox) {
          ratingBox.innerHTML = '自分の評価：' + [1,2,3,4,5].map(i =>
            `<span class="mx-0.5 ${ratingNum >= i ? 'text-amber-500 font-bold' : 'text-gray-400'}">${i}</span>`
          ).join('');
        }

        // コメント（2行目）
        const memoEl = row.querySelector('[data-slot="memo"]');
        if (memoEl) memoEl.textContent = j.row.memo || '（コメントはまだありません）';
      }
      closeEm();
    });

    // --- 削除 ---
    document.querySelectorAll('.del-btn').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation(); // ← 追加
        if (!confirm('この履歴を削除します。よろしいですか？')) return;
        const box = btn.closest('[data-id]') || btn.closest('.history-row');
        const id = box.dataset.id;
        const res = await fetch(`{{ route('histories.destroy', ['history' => '__ID__']) }}`.replace('__ID__', id), {
          method: 'DELETE',
          headers: {'X-CSRF-TOKEN': token,'Accept':'application/json'}
        });
        if (!res.ok) { alert('削除に失敗しました'); return; }
        document.getElementById(`row-${id}`).remove();
      });
    });

    // --- お気に入りトグル（⭐） ---
    document.querySelectorAll('.fav-btn').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const box = btn.closest('[data-id]') || btn.closest('.history-row'); // 保険
        const row = btn.closest('.history-row'); // ここでも行クリックを止める
        const restaurantId = (box?.dataset.restaurantId) || (row?.dataset.restaurantId);
        if (!restaurantId) { alert('この履歴には店舗IDがありません'); return; }

        const res = await fetch(`{{ route('favorites.toggle') }}`, {
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN': token,'Accept':'application/json'},
          body: JSON.stringify({ restaurant_id: restaurantId })
        });
        if (!res.ok) { alert('お気に入り更新に失敗しました'); return; }
        const j = await res.json();

        // ★ の色をクラスで切替（path操作は不要）
        btn.classList.toggle('text-amber-400', j.favorited);
        btn.classList.toggle('text-gray-300', !j.favorited);
      });
    });
    const modal   = document.getElementById('historyModal');
    const nameEl  = document.getElementById('hmName');
    const addrEl  = document.getElementById('hmAddress');
    const rateEl  = document.getElementById('hmRating');
    const cuisEl  = document.getElementById('hmCuisines');
    const hoursEl = document.getElementById('hmHours');
    const mapA    = document.getElementById('hmMap');

    function openModal() {
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }
    function closeModal() {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }
    document.getElementById('hmClose').onclick = closeModal;
    document.getElementById('hmClose2').onclick = closeModal;
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    
    // 履歴行クリック
    document.querySelectorAll('.history-row').forEach(row => {
      row.addEventListener('click', async () => {
        const name     = row.dataset.name || '(名称不明)';
        const address  = row.dataset.address || '';
        const rating   = row.dataset.rating || '';
        const cuisines = (() => {
          try { return JSON.parse(row.dataset.cuisines || '[]'); }
          catch { return []; }
        })();
        const placeId  = row.dataset.placeId || '';
        const lat      = row.dataset.lat || '';
        const lng      = row.dataset.lng || '';

        console.log('row dataset', row.dataset);

        // まずは保存済み情報を即表示（体感を速く）
        nameEl.textContent = name;
        cuisEl.textContent = cuisines.length ? `カテゴリ: ${cuisines.join(' / ')}` : '';
        addrEl.textContent = address ? `住所: ${address}` : '';
        rateEl.textContent = rating ? `評価: ${rating}` : '';
        hoursEl.textContent = '営業時間: 取得中…';

        // マップリンク（place_id > 緯度経度 > 住所 の優先度）
        let mapUrl = '#';
        if (placeId) {
          mapUrl = `https://www.google.com/maps/place/?q=place_id:${encodeURIComponent(placeId)}`;
        } else if (lat && lng) {
          mapUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
        } else if (address) {
          mapUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
        }
        mapA.href = mapUrl;

        // place_id があれば詳細を取得（営業時間など）
        if (placeId) {
          try {
            const url = `{{ route('roulette.place.detail', ['placeId' => 'PLACE_ID']) }}`.replace('PLACE_ID', encodeURIComponent(placeId));
            const res = await fetch(url);
            if (!res.ok) throw 0;
            const json = await res.json();
            // 期待するキーは backend 実装に合わせて。例: json.opening_hours.weekday_text, json.rating, json.types
            const weekday = json?.opening_hours?.weekday_text || null;
            if (weekday && weekday.length) {
              hoursEl.textContent = `営業時間:\n${weekday.join('\n')}`;
            } else {
              hoursEl.textContent = '営業時間: 情報なし';
            }
            // API側の rating/types があれば上書き
            if (json?.rating && !rating) rateEl.textContent = `評価: ${json.rating}`;
            if (Array.isArray(json?.types) && !cuisines.length) {
              const labels = json.types.map(String).slice(0,6);
              cuisEl.textContent = labels.length ? `カテゴリ: ${labels.join(' / ')}` : '';
            }
          } catch {
            hoursEl.textContent = '営業時間: 取得に失敗しました';
          }
        } else {
          hoursEl.textContent = '営業時間: 情報なし';
        }

        openModal();
      });
    });
  })();
  </script>



</x-app-layout>