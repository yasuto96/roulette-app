<x-app-layout>
  <div class="py-6 max-w-3xl mx-auto">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div id="__debug__" style="display:none">roulette.search v1</div>


    {{-- ルーレット（検索ルーレット） --}}
    <div id="wheel-area" class="max-w-xl mx-auto mb-6">
      <h2 class="text-2xl font-semibold text-center mb-2">検索ルーレット（会員限定）</h2>

      <div class="relative flex flex-col items-center">
        <canvas id="search-wheel" width="420" height="420"
                class="block rounded-full bg-transparent drop-shadow"></canvas>

        {{-- 右側の赤いポインタ（大きめ＆円に近づけ） --}}
        <div
          aria-hidden="true"
          style="
            position:absolute;
            top:50%;
            transform:translateY(-50%);
            right: 20px;                /* ルーレットの縁に寄せる。必要なら 0～8pxで微調整 */
            width:0; height:0;
            border-top:20px solid transparent;
            border-bottom:20px solid transparent;
            border-right:55px solid #ef4444; /* Tailwindのred-500相当 */
            z-index: 0;              /* キャンバスより前面へ */
            pointer-events:none;
          ">
        </div>
      </div>

      <div class="mt-3 flex gap-2 justify-center">
        <button type="button" id="spin-btn"  class="px-4 py-2 rounded bg-blue-600 text-white">回す！</button>
        <button type="button" id="wheel-reset" class="px-4 py-2 rounded border border-gray-300">リセット</button>
        <button type="button" id="open-modal" class="px-4 py-2 rounded border border-gray-300">条件を設定</button>

        <label class="ml-2 inline-flex items-center gap-2 text-sm select-none">
          <input id="only-favs" type="checkbox" class="w-4 h-4">
          <span>お気に入りのみ</span>
        </label>
      </div>

      <p id="wheel-result" class="mt-3 text-center text-lg font-semibold"></p>
      <p id="wheel-link"   class="mt-1 text-center text-sm"></p>
    </div>

    {{-- 画面下部ナビ（履歴／カテゴリ・ルーレット／お気に入り） --}}
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-xl mx-auto">

      {{-- 履歴（会員専用） --}}
      <a href="{{ route('histories.index') }}"
        class="block text-center rounded-2xl px-5 py-3 font-semibold
                bg-white shadow active:translate-y-[1px] border border-gray-200
                hover:shadow-md transition">
        履歴
      </a>

      {{-- カテゴリ・ルーレット（一般公開） --}}
      <a href="{{ route('roulette.category.form') }}"
        class="block text-center rounded-2xl px-5 py-3 font-semibold
                bg-white shadow active:translate-y-[1px] border border-gray-200
                hover:shadow-md transition leading-tight">
        <span class="block">カテゴリ</span>
        <span class="block text-sm opacity-80 -mt-1">ルーレット</span>
      </a>

      {{-- お気に入り（会員専用） --}}
      <a href="{{ route('favorites.index') }}"
        class="block text-center rounded-2xl px-5 py-3 font-semibold
                bg-white shadow active:translate-y-[1px] border border-gray-200
                hover:shadow-md transition">
        お気に入り
      </a>
    </div>


    {{-- 検索条件モーダル（非同期） --}}
    <div id="search-modal" class="fixed inset-0 z-40 hidden">
    <!-- 背景 -->
    <div class="absolute inset-0 bg-black/40" data-close="1"></div>

    <!-- 位置＆外枠（中央寄せ＋左右余白） -->
     <div class="relative z-50 flex min-h-dvh items-center justify-center p-4">
      <!-- モーダル本体（高さ制限） -->
      <div id="searchCard"
        class="w-[min(92vw,680px)] bg-white rounded-xl shadow-xl
                max-h-[90dvh] flex flex-col overflow-hidden">
        <!-- header -->
        <div data-part="header" class="flex items-center justify-between p-4 border-b">
          <h3 class="text-xl font-semibold">検索条件</h3>
          <button type="button" class="text-gray-500" data-close="1">✕</button>
        </div>
        <div data-part="body" id="searchBody"
          class="p-4 overflow-y-auto min-h-0 flex-1">
          <form id="cond-form" class="space-y-4">
            {{-- 1) フリーワード --}}
            <div>
              <label class="block text-sm font-medium mb-1">フリーワード</label>
              <input type="text" id="q" class="w-full border rounded px-3 py-2" placeholder="例：渋谷 ラーメン">
            </div>

            {{-- 2) 距離 --}}
            <div>
              <label class="block text-sm font-medium mb-1">距離</label>
              <select id="radius" class="w-full border rounded px-3 py-2">
                <option value="500">500m</option>
                <option value="1000">1km</option>
                <option value="2000">2km</option>
                <option value="3000">3km</option>
                <option value="5000">5km</option>
              </select>
            </div>

            {{-- 3) ジャンル（最大3つ・既存のcuisinesを使う） --}}
            <div>
              <label class="block text-sm font-medium mb-1">ジャンル（最大3つ）</label>
              <div class="grid grid-cols-2 gap-2 max-h-44 overflow-auto border rounded p-2" id="genre-box">
                @foreach($cuisines as $c)
                  <label class="inline-flex items-center gap-2">
                    <input type="checkbox" value="{{ $c->name }}" class="genre">
                    <span>{{ $c->name }}</span>
                  </label>
                @endforeach
              </div>
              <p class="text-xs text-gray-500 mt-1">※3つを超えると自動で外します</p>
            </div>

            {{-- 4) 金額（Googleのprice_level 0〜4に合わせる） --}}
            <div>
              <label class="block text-sm font-medium mb-1">価格帯</label>
              <select id="price" class="w-full border rounded px-3 py-2">
                <option value="">指定なし</option>
                <option value="1">低価格（$）</option>
                <option value="2">普通（$$）</option>
                <option value="3">やや高め（$$$）</option>
                <option value="4">高級（$$$$）</option>
              </select>
            </div>

            {{-- 5) 営業時間（今営業中） --}}
            <div class="flex items-center gap-2">
              <input type="checkbox" id="open_now">
              <label for="open_now">今営業中のみ</label>
            </div>

            {{-- 6) 高評価（星4以上） --}}
            <div class="flex items-center gap-2">
              <input type="checkbox" id="min4">
              <label for="min4">評価4.0以上</label>
            </div>

            <!-- どこでもOK（検索フォーム内） -->
            <input type="hidden" id="js-lat" name="lat" value="">
            <input type="hidden" id="js-lng" name="lng" value="">

            <!-- 取得状況を目で見えるように -->
            <p id="js-loc-status" class="text-sm text-gray-500">現在地: 未取得</p>
            <div class="mt-1 flex flex-wrap gap-2">
              <button type="button" id="btn-sync-loc"
                class="inline-flex items-center rounded border px-3 py-1 text-sm">
                現在地を反映
              </button>
              <button type="button" id="btn-clear-loc"
                class="inline-flex items-center rounded border px-3 py-1 text-sm">
                位置情報をクリア
              </button>
            </div>
            {{-- 7) 基準地点（地図で選ぶのみ） --}}
            <div class="pt-3 border-t">
              <label class="block text-sm font-medium mb-1">基準地点</label>

              <div class="flex flex-wrap items-center gap-2">
                <!-- 選択済みの地点名（クリックで再選択） -->
                <button type="button" id="place-label"
                        class="text-blue-600 underline"
                        title="地図で選び直す">
                  未設定（地図で選ぶ）
                </button>

                <!-- 地図モーダルを開く明示ボタン（任意／残してOK） -->
                <button type="button" id="open-map"
                        class="px-2 py-1 text-sm border rounded">
                  地図で選ぶ
                </button>

                <!-- 適用済みバッジ -->
                <span id="place-applied-badge"
                      class="hidden text-xs px-2 py-0.5 rounded bg-blue-600 text-white">
                  適用済み
                </span>
              </div>

              <p class="mt-1 text-xs text-gray-500">
                地図をクリックした地点を基準に、上の「距離」で指定した半径内を検索します。
              </p>
            </div>

            {{-- 位置情報（JSで埋める） --}}
            <input type="hidden" id="lat">
            <input type="hidden" id="lng">

          </form>
        </div>
        <!-- フッター（常に見える） -->
        <div data-part="footer" class="p-3 border-t bg-white">
        <div class="flex gap-3 justify-end">
          <button type="button" id="apply-cond"
                  class="px-5 py-2 rounded bg-blue-600 text-white">設定</button>
          <button type="button" data-close="1"
                  class="px-5 py-2 rounded border">戻る</button>
        </div>
      </div>
    </div>

  </div>
  {{-- 結果モーダル --}}
  <div id="resultModal"
      class="fixed inset-0 z-[9999] hidden items-center justify-center">
      {{-- 背景 --}}
       <div class="absolute inset-0 bg-black/50"></div>

      {{-- 本体 --}}
      <div class="relative z-10 w-[min(92vw,720px)] rounded-2xl bg-white p-6 shadow-xl">
        <button id="btnClose"
          class="absolute right-3 top-3 rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600 hover:bg-gray-200">
          ×
        </button>
        <div class="mb-4 text-center text-2xl font-bold">ルーレット結果</div>

        {{-- 結果カード --}}
        <div class="rounded-xl border p-4">
          <div id="resName" class="text-xl font-semibold"></div>
          <div id="resCuisines" class="mt-1 text-sm text-gray-600"></div>
          <div id="resAddress" class="mt-1 text-sm text-gray-600"></div>
          <div id="resRating" class="mt-1 text-sm text-gray-600"></div>
        </div>

        {{-- ボタン群 --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
          <button id="btnGo"
            class="rounded-xl border-2 border-emerald-500 bg-emerald-500/10 px-4 py-3 font-bold text-emerald-700 hover:bg-emerald-500/20">
            行ってみる！
          </button>
          <button id="btnMap"
            class="rounded-xl border-2 border-sky-500 bg-sky-500/10 px-4 py-3 font-bold text-sky-700 hover:bg-sky-500/20">
            MAPを表示
          </button>
          <button id="btnSpinAgain"
            class="rounded-xl border-2 border-gray-500 bg-gray-500/10 px-4 py-3 font-bold text-gray-700 hover:bg-gray-500/20">
            もう一度回す
          </button>
        </div>
      </div>
  </div>
  <!-- Leaflet（地図ライブラリ：CSS/JS） -->
  <link rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <!-- 地図ピッカー・モーダル（必須：id="mapPickerModal"） -->
  <div id="mapPickerModal" class="fixed inset-0 hidden z-[9998]">      
      <!-- 背景（クリックで閉じる） -->
    <div class="absolute inset-0 bg-black/40" data-close="1"></div>

    <!-- 本体 -->
    <div class="relative z-[9999] mx-auto mt-10 bg-white rounded-xl shadow-xl p-4"
      style="width:min(94vw,800px); max-width:800px;">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">地図から地点を選択</h3>
        <button type="button" class="text-gray-500" data-close="1">✕</button>
      </div>
      <!-- 検索バー -->
      <div class="mb-2 flex gap-2">
        <input id="map-search-q" class="flex-1 border rounded px-3 py-2"
              placeholder="例: 仙台駅 / 東京タワー / 郵便番号 100-0001">
        <button id="map-search-btn" class="px-3 py-2 rounded bg-blue-600 text-white">
          検索
        </button>
      </div>


      <!-- 地図を表示する箱（必須：id="pickMap"） -->
      <div id="pickMap" class="w-full rounded-lg overflow-hidden border" style="height:420px;"></div>

      <!-- 選択中の情報 -->
      <div class="mt-3 text-sm text-gray-600">
        <div>選択中：<span id="map-addr">(未選択)</span></div>
        <div class="mt-1">座標：<span id="map-latlng">-</span></div>
        <div class="mt-1">半径：<span id="map-radius-view">-</span></div>
        <p class="mt-1 text-xs text-gray-500">地図をクリックすると地点が移動します。</p>
      </div>

      <div class="mt-4 flex justify-end gap-2">
        <button type="button" class="px-4 py-2 rounded border" data-close="1">キャンセル</button>
        <button type="button" id="map-apply" class="px-4 py-2 rounded bg-blue-600 text-white">この地点を適用</button>
      </div>
    </div>
  </div>

  {{-- ページ専用スクリプト --}}
<script>

(function(){
  'use strict';
 // --- まず最低限の初期化が走っているか確認 ---
  try { console.log('[roulette] script start'); } catch(e) {}

  // ===== モーダルを <body> 直下に移して最前面に固定（親の stacking context 影響を断つ） =====
  function portalizeToBody(el, z = 100000) {
    if (!el) return;
    if (el.parentElement !== document.body) {
      document.body.appendChild(el);  // ★ DOM末尾＝最上位へ
    }
    el.style.zIndex = String(z);      // ★ 念のため z-index も最終上書き
    el.style.position = 'fixed';      // ★ fixed を明示（環境依存の崩れ防止）
    el.style.inset = '0';             // ★ フルスクリーンを保証
  }

  function closeAllModals() {
    try { closeMapPicker?.(); } catch {}
    try { closeSearchModal?.(); } catch {}
    try {
      const rm = document.getElementById('resultModal');
      if (rm) { rm.classList.add('hidden'); rm.classList.remove('flex'); rm.style.display='none'; }
    } catch {}
  }

  // 要素の存在を厳密にチェック（nullで落ちない）
  const canvas   = document.getElementById('search-wheel');
  const spinBtn  = document.getElementById('spin-btn');
  const resetBtn = document.getElementById('wheel-reset');
  const openBtn  = document.getElementById('open-modal');
  const onlyFavsEl = document.getElementById('only-favs');
  const resultEl = document.getElementById('wheel-result');
  const linkEl   = document.getElementById('wheel-link');

  // 初期は必ずOFF（前回の保存は使わない）
  if (onlyFavsEl) {
    onlyFavsEl.checked = false;
    try { localStorage.removeItem('roulette.only_favorites'); } catch {}
  }


  let lastCriteria = null;        // 直近に使った条件（お気に入り/通常どちらでも）
  let lastNonFavCriteria = null;  // 直近の「通常検索」の条件だけを記憶



  if (!canvas || !spinBtn || !resetBtn || !openBtn) {
    console.error('[roulette] DOM not ready or IDs changed');
    return; // ここで中断（以降で落ちないように）
  }

  const ctx = canvas.getContext('2d');

  // ルーレットの配列（初期は空）とプレースホルダを必ず描く
  let items = [];
  function drawPlaceholder(){
    const r = canvas.width/2;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.save(); ctx.translate(r,r);
    ctx.beginPath(); ctx.arc(0,0,r-2,0,Math.PI*2);
    ctx.strokeStyle='#e5e7eb'; ctx.lineWidth=4; ctx.stroke();
    ctx.fillStyle='#6b7280'; ctx.font='16px system-ui,sans-serif'; ctx.textAlign='center';
    ctx.fillText('条件を設定して候補を取得',0,6);
    ctx.restore();
    spinBtn.disabled = true;
  }
  drawPlaceholder(); // ← 最初に必ず描いておく

  function build(){
    if(items.length===0){ drawPlaceholder(); return; }
    spinBtn.disabled = false;
    const r = canvas.width/2;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.save(); ctx.translate(r,r);
    for(let i=0;i<items.length;i++){
      const start = 2*Math.PI*i/items.length;
      const end   = 2*Math.PI*(i+1)/items.length;
      ctx.beginPath(); ctx.moveTo(0,0); ctx.arc(0,0,r-3,start,end); ctx.closePath();
      ctx.fillStyle = `hsl(${i*360/items.length},70%,70%)`; ctx.fill();
      ctx.strokeStyle='#fff'; ctx.lineWidth=2; ctx.stroke();
      const mid = (start+end)/2;
      ctx.save(); ctx.rotate(mid); ctx.textAlign='right'; ctx.fillStyle='#111'; ctx.font='16px system-ui,sans-serif';
      const label = (items[i].name || '').slice(0,12);
      ctx.fillText(label, r-18, 6);
      ctx.restore();
    }
    ctx.restore();
  }
  const ease = t => 1 - Math.pow(1 - t, 3);
  let rotation = 0;
  let isSpinning = false;
  function redrawRot(){
    if(items.length===0){ drawPlaceholder(); return; }
    const r = canvas.width/2;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.save(); ctx.translate(r,r); ctx.rotate(rotation);
    for(let i=0;i<items.length;i++){
      const start = 2*Math.PI*i/items.length, end = 2*Math.PI*(i+1)/items.length;
      ctx.beginPath(); ctx.moveTo(0,0); ctx.arc(0,0,r-3,start,end); ctx.closePath();
      ctx.fillStyle = `hsl(${i*360/items.length},70%,70%)`; ctx.fill();
      ctx.strokeStyle='#fff'; ctx.lineWidth=2; ctx.stroke();
      const mid = (start+end)/2;
      ctx.save(); ctx.rotate(mid); ctx.textAlign='right'; ctx.fillStyle='#111'; ctx.font='16px system-ui,sans-serif';
      const label = (items[i].name || '').slice(0,12);
      ctx.fillText(label, r-18, 6);
      ctx.restore();
    }
    ctx.restore();
  }
  function spin(){
    if(items.length===0) return;
    if(isSpinning) return;
    isSpinning = true;
    spinBtn.disabled = true;
    const n = items.length;
    const pick = Math.floor(Math.random()*n);
    const center = (2*Math.PI*pick/n) + Math.PI/n;
    const start = rotation;
    const target = rotation + (4+Math.random()*2)*2*Math.PI + (2*Math.PI - center);
    const delta = target - start;
    const dur = 3200;
    const t0 = performance.now();
    resultEl.textContent = ''; linkEl.innerHTML = '';

    function frame(now){
      const t = Math.min(1,(now-t0)/dur);
      rotation = start + delta * ease(t);
      redrawRot();
      if(t<1){
        requestAnimationFrame(frame);
      }else{
        const angle = (2*Math.PI - (rotation % (2*Math.PI))) % (2*Math.PI);
        const idx = Math.floor(angle / (2*Math.PI/n));
        const win = items[idx];
        isSpinning = false;
        spinBtn.disabled = false;

        if (!win || !win.name) {
          console.warn('[roulette] invalid win:', win);
          alert('選択データに店名がありません。コンソールを確認してください。');
          return;
        }
        // 既存のテキスト表示
        resultEl.textContent = `結果：${win.name}`;
        if(win.place_id){
          const url = `https://www.google.com/maps/place/?q=place_id:${win.place_id}`;
          linkEl.innerHTML = `<a class="underline text-blue-600" href="${url}" target="_blank" rel="noopener">Googleマップで開く</a>`;
        }else if(win.address){
          const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(win.name+' '+(win.address||''))}`;
          linkEl.innerHTML = `<a class="underline text-blue-600" href="${url}" target="_blank" rel="noopener">Googleマップで開く</a>`;
        }

        try { typeof closeMapPicker === 'function'   && closeMapPicker(); } catch(e){}
        try { typeof closeSearchModal === 'function' && closeSearchModal(); } catch(e){}

        setTimeout(() => {
          try {
            console.log('[roulette] openResultModal', win);
            openResultModal({
              id:        win.id ?? win.restaurant_id ?? null,
              name:      win.name,
              place_id:  win.place_id ?? null,
              lat:       win.lat ?? null,
              lng:       win.lng ?? null,
              address:   win.address ?? null,
              rating:    win.rating ?? null,
              cuisines:  Array.isArray(win.cuisines) ? win.cuisines.map(c => c.name ?? c) : []
            }, {
              criteria: { q: document.getElementById('q')?.value || '' },
              seed: undefined
            });
          } catch (err) {
            console.error('[openResultModal failed]', err);
            alert('結果モーダルの表示でエラーが発生しました。コンソールを確認してください。');
          }
        }, 0);
      }
    }
    requestAnimationFrame(frame);
  }

  resetBtn.addEventListener('click', ()=>{ rotation=0; resultEl.textContent=''; linkEl.innerHTML=''; build(); });
  spinBtn.addEventListener('click', spin);
  drawPlaceholder();

  // ====== 位置情報（localStorage に保存して復元） ======
  const latEl   = document.getElementById('js-lat');
  const lngEl   = document.getElementById('js-lng');
  const locText = document.getElementById('js-loc-status');
  const syncBtn = document.getElementById('btn-sync-loc');
  const LOC_KEY = 'roulette.loc';
  const ONLY_FAVS_KEY = 'roulette.only_favorites';

  function readLocCache(){
    try {
      const c = JSON.parse(localStorage.getItem(LOC_KEY));
      if (c && (Date.now() - c.ts) < 24*60*60*1000) return c; // 24時間有効
    } catch {}
    return null;
  }
  function saveLocCache(lat, lng, area){
    localStorage.setItem(LOC_KEY, JSON.stringify({ lat, lng, area: area || null, ts: Date.now() }));
  }
  function getCurrentPositionOnce(){
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) return reject(new Error('Geolocation非対応'));
      navigator.geolocation.getCurrentPosition(
        p => resolve({ lat: p.coords.latitude, lng: p.coords.longitude }),
        e => reject(e),
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
      );
    });
  }
  async function fillLocation(force=false){
    try{
      if (force || !latEl.value || !lngEl.value){
        locText.textContent = '現在地を取得中…';
        const {lat,lng} = await getCurrentPositionOnce();
        latEl.value = String(lat);
        lngEl.value = String(lng);
        const area = await reverseAreaName(lat, lng);
        if (area) {
          locText.textContent = `現在地: ${area}`;
          saveLocCache(lat, lng, area);
        } else {
          locText.textContent = `現在地: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
          saveLocCache(lat, lng, null);
        }
        console.log('[geo] set', latEl.value, lngEl.value);
      }
    }catch(e){
      console.warn('[geo] failed', e);
      const cached = readLocCache();
      if (cached) {
        latEl.value = cached.lat;
        lngEl.value = cached.lng;
        locText.textContent = `現在地(前回): ${(+cached.lat).toFixed(5)}, ${(+cached.lng).toFixed(5)}`;
      } else {
        // ★ 東京駅をデフォルトに採用
        const tokyo = { lat: 35.681236, lng: 139.767125 };
        latEl.value = String(tokyo.lat);
        lngEl.value = String(tokyo.lng);
        locText.textContent = `現在地: 東京駅（デフォルト）`;
        // 逆ジオで名称が取れたら置き換え＆キャッシュ
        try {
          const area = await reverseAreaName(tokyo.lat, tokyo.lng);
          if (area) locText.textContent = `現在地: ${area}`;
          saveLocCache(tokyo.lat, tokyo.lng, area || '東京駅');
        } catch {}
      }
    }
  }

  // ====== 手入力地点 → 現在地として反映 ======
  // UI要素
  const placeLabelEl = document.getElementById('place-label'); // ← 表示兼「地図で選ぶ」
  const openMapBtn   = document.getElementById('open-map');    // ← 予備の開くボタン
  const appliedBadge = document.getElementById('place-applied-badge');

  // 直近で解決できた地点を保持（「現在地として設定」時に使用）
  let tmpOrigin = null;

  function showAppliedBadge(on){
    if(!appliedBadge) return;
    appliedBadge.classList.toggle('hidden', !on);
  }

  if (placeLabelEl) placeLabelEl.addEventListener('click', openMapPicker);
  if (openMapBtn)   openMapBtn.addEventListener('click', openMapPicker);


  // 権限状態を表示（対応ブラウザのみ）
  async function showGeoPermission(){
    try{
      if (!navigator.permissions) return;
      const s = await navigator.permissions.query({ name: 'geolocation' });
      locText.textContent += `（権限: ${s.state}）`;
      s.onchange = () => { locText.textContent = locText.textContent.replace(/（権限:.*?）/,'') + `（権限: ${s.state}）`; };
    }catch{}
  }
  showGeoPermission();

  // 位置キャッシュを消去
  const btnClear = document.getElementById('btn-clear-loc');
  if (btnClear) {
    btnClear.addEventListener('click', () => {
      localStorage.removeItem(LOC_KEY);
      latEl.value = ''; lngEl.value = '';
      locText.textContent = '現在地: 未取得（キャッシュ消去済み）';
    });
  }

  // 仙台駅に設定（テスト/手動用）
  const btnSendai = document.getElementById('btn-sendai');
  if (btnSendai) {
    btnSendai.addEventListener('click', () => {
      const lat = 38.268215, lng = 140.869356; // 仙台駅付近
      latEl.value = String(lat);
      lngEl.value = String(lng);
      try { localStorage.setItem(LOC_KEY, JSON.stringify({ lat, lng, ts: Date.now() })); } catch {}
      locText.textContent = `現在地(手動): ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    });
  }


  // ページ読み込み時：キャッシュがあれば即反映
  {
    const cached = readLocCache();
    if (cached) {
      latEl.value = cached.lat;
      lngEl.value = cached.lng;
      locText.textContent = `現在地(前回): ${(+cached.lat).toFixed(5)}, ${(+cached.lng).toFixed(5)}`;
    }
    // 初期は必ずOFF（前回の保存は使わない）
    if (onlyFavsEl) {
      onlyFavsEl.checked = false;
      try { localStorage.removeItem('roulette.only_favorites'); } catch {}
    }


  }
  // 「お気に入りのみ」を切り替えた瞬間に即フェッチ
  if (onlyFavsEl) {
    onlyFavsEl.addEventListener('change', async () => {
      try { localStorage.setItem(ONLY_FAVS_KEY, onlyFavsEl.checked ? '1' : '0'); } catch {}

      if (onlyFavsEl.checked) {
        // ★ お気に入りモード：モーダル無関係で即取得（半径は広めでOK）
        const body = {
          only_favorites: 1,
          radius : 5000,                    // バリデーション回避用のデフォルト
          q      : '',                      // ここでは絞らない
          genres : [],
          price  : null,
          open_now: 0,
          min_rating: null,
          lat : latEl.value || null,
          lng : lngEl.value || null,
        };
        lastCriteria = body;
        await fetchCandidates(body);
      } else {
        // ★ 通常モード復帰：直前の通常条件があればそれで、なければプレースホルダ
        if (lastNonFavCriteria) {
          lastCriteria = lastNonFavCriteria;
          await fetchCandidates(lastNonFavCriteria);
        } else {
          items = [];
          rotation = 0; resultEl.textContent=''; linkEl.innerHTML='';
          build(); // プレースホルダ表示
        }
      }
    });
  }



  // 『現在地を反映』ボタン
  if (syncBtn) {
    syncBtn.addEventListener('click', async () => {
      syncBtn.disabled = true;
      await fillLocation(true);    // 強制取得
      syncBtn.disabled = false;
    });
  }

  // 「条件を設定」モーダルを開いたときにも、未取得なら試す
  document.getElementById('open-modal').addEventListener('click', async ()=>{
    openSearchModal();
    if (!latEl.value || !lngEl.value) fillLocation(true).catch(()=>{});
  });

  if (!latEl.value || !lngEl.value) fillLocation(true).catch(()=>{});


  // ====== モーダル ======
  const searchModalEl = document.getElementById('search-modal');
  searchModalEl.addEventListener('click', (e) => {
    const closer = e.target.closest('[data-close="1"]');
    if (!closer) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    closeSearchModal();
  });

  function sizeSearchModal() {
    const card  = document.getElementById('searchCard');
    const body  = document.querySelector('#searchCard [data-part="body"]');
    const head  = document.querySelector('#searchCard [data-part="header"]');
    const foot  = document.querySelector('#searchCard [data-part="footer"]');
    if (!card || !body || !head || !foot) return;

    const max = Math.min(window.innerHeight * 0.9, window.innerHeight - 16);
    card.style.maxHeight = `${max}px`;
    const rest = max - head.offsetHeight - foot.offsetHeight;
    body.style.maxHeight = `${Math.max(200, rest)}px`;
  }

  function openSearchModal() {
    const modal = document.getElementById('search-modal');
    modal.classList.remove('hidden');
    document.documentElement.style.overflow = 'hidden';
    sizeSearchModal();
    setTimeout(sizeSearchModal, 0);
  }

  function closeSearchModal() {
    const modal = document.getElementById('search-modal');
    modal.classList.add('hidden');
    document.documentElement.style.overflow = '';
  }


  // ジャンル：最大3件まで
  const genreBox = document.getElementById('genre-box');
  genreBox.addEventListener('change', ()=>{
    const checked = [...genreBox.querySelectorAll('input.genre:checked')];
    if(checked.length>3){ checked[0].checked = false; }
  });

  // 共通：候補取得 → ルーレット再描画（正規化つき）
  async function fetchCandidates(criteria) {
    console.log('[roulette] request body =', criteria); // ★ 追加（確認用）
    const res = await fetch('{{ route('roulette.search.spin') }}', {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept':'application/json'
      },
      body: JSON.stringify(criteria)
    });
    if (!res.ok) { alert('候補の取得に失敗しました'); return; }

    const json = await res.json(); // {items:[...], src: "..."}
    // 受け取った配列を “name, address, lat, lng, place_id, rating, cuisines” に揃える
    const normalized = (json.items || []).map((raw) => {
      const r = raw.restaurant ?? raw;
      const placeId = 
        r.place_id ??
        r.source_id ??
        (r.source === 'google' ? r.source_id : null);
      return {
        id       : r.id ?? r.restaurant_id ?? null,
        name     : String(r.name ?? r.title ?? '').trim(),
        address  : String(r.address ?? r.vicinity ?? ''),
        lat      : r.lat ?? null,
        lng      : r.lng ?? null,
         place_id : placeId ?? null,
        rating   : r.rating ?? null,
        // cuisines は ["中華", ...] / [{name:"中華"}, ...] の両対応
        cuisines : Array.isArray(r.cuisines)
          ? r.cuisines.map(c => (typeof c === 'string') ? c : (c?.name ?? '')).filter(Boolean)
          : []
      };
    }).filter(it => it.name); // ← name が空のものは除外

    // ここから先は今まで通り
    items = normalized.slice(0, 24);
    rotation = 0; resultEl.textContent = ''; linkEl.innerHTML = '';
    build();

    if (items.length === 0) {
      alert('候補が見つかりませんでした。条件を変えてお試しください。');
    }

    // デバッグ用（必要なら開発中だけ）
    try { console.log('[roulette] src=', json.src, 'items[0]=', items[0]); } catch(e){}
  }


  // 条件を送信→候補取得→ルーレット反映
  document.getElementById('apply-cond').addEventListener('click', async ()=>{
    if (!latEl.value || !lngEl.value) fillLocation(true).catch(()=>{});
    const genres = [...genreBox.querySelectorAll('input.genre:checked')].map(x=>x.value);

    const body = {
      q      : document.getElementById('q').value || '',
      radius : parseInt(document.getElementById('radius').value,10),
      genres : genres,
      price  : document.getElementById('price').value || null,
      open_now: document.getElementById('open_now').checked ? 1 : 0,
      min_rating: document.getElementById('min4').checked ? 4.0 : null,
      // 外側トグル（id="only-favs"）
      only_favorites: 0,

      lat : latEl.value || null,
      lng : lngEl.value || null,
    };

    lastNonFavCriteria = body;  // ★ 通常条件を記憶
    lastCriteria = body;           // 直近の条件を記憶
    await fetchCandidates(body);   // 取得＆描画
    closeSearchModal();
  });

  // ルーレット結果を受け取ってモーダルを開く
  function openResultModal(restaurant, options = {}) {
    closeAllModals(); 

    console.log('[roulette] openResultModal(): showing', restaurant);
    const modal      = document.getElementById('resultModal');
    portalizeToBody(modal, 100020);
    const nameEl     = document.getElementById('resName');
    const addressEl  = document.getElementById('resAddress');
    const ratingEl   = document.getElementById('resRating');

    nameEl.textContent    = restaurant?.name ?? '(名称未取得)';
    addressEl.textContent = restaurant?.address ? '住所: ' + restaurant.address : '';
    addressEl.style.display = addressEl.textContent ? '' : 'none';

    ratingEl.textContent  = (restaurant?.rating != null) ? `評価: ${restaurant.rating}` : '';
    ratingEl.style.display = ratingEl.textContent ? '' : 'none';

    // 後続ボタン用のデータ
    modal.dataset.restaurantId = restaurant?.id ?? '';
    modal.dataset.lat          = restaurant?.lat ?? '';
    modal.dataset.lng          = restaurant?.lng ?? '';
    modal.dataset.address      = restaurant?.address ?? '';
    modal.dataset.placeId      = restaurant?.place_id ?? '';
    modal.dataset.rating       = restaurant?.rating ?? ''; 
    modal.dataset.cuisines     = JSON.stringify(
      Array.isArray(restaurant?.cuisines) ? restaurant.cuisines : []
    );
    modal.dataset.criteria     = JSON.stringify(options.criteria ?? null);
    modal.dataset.seed         = options.seed ?? '';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.removeProperty('display');
    // 競合対策：display を直接指定（最優先）
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');

    const areaCached = readLocCache();
    const resUserArea = document.getElementById('resUserArea');
    if (resUserArea) {
      resUserArea.textContent = areaCached?.area ? `現在地: ${areaCached.area}` : '';
    }
  }

  // 取得（シンプルキャッシュ付き）
  const placeCache = new Map();
  async function loadHours(placeId){
    if (placeCache.has(placeId)) return placeCache.get(placeId);
    try {
      const res = await fetch(`{{ route('roulette.place.detail', ['placeId' => 'PLACE_ID']) }}`.replace('PLACE_ID', encodeURIComponent(placeId)));
      if (!res.ok) throw new Error('place detail failed');
      const json = await res.json();
      placeCache.set(placeId, json);
      return json;
    } catch(e) {
      console.warn(e);
      return null;
    }
  }
  // 閉じる
  function closeResultModal() {
    const modal = document.getElementById('resultModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }

// クリックハンドラ登録（DOMContentLoadedに依存せず即時に紐付け）
const resultModalEl = document.getElementById('resultModal');
const btnClose      = document.getElementById('btnClose');
const btnSpinAgain  = document.getElementById('btnSpinAgain');
const btnMap        = document.getElementById('btnMap');
const btnGo         = document.getElementById('btnGo');

// 背景クリックで閉じる
resultModalEl.addEventListener('click', (e) => {
  if (e.target === resultModalEl) closeResultModal();
});
btnClose.addEventListener('click', closeResultModal);

// 3) もう一度回す = モーダルを閉じて再スピン
btnSpinAgain.addEventListener('click', () => {
  closeResultModal();
  if (typeof spin === 'function') {
    // このファイルでは関数名が「spin」なのでそのまま再実行
    spin();
  }
});

// 2) MAPを表示 = Googleマップへ
btnMap.addEventListener('click', () => {
  const lat = resultModalEl.dataset.lat;
  const lng = resultModalEl.dataset.lng;
  const address = resultModalEl.dataset.address;
  let url = '';
  if (lat && lng) {
    url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
  } else if (address) {
    url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
  } else {
    alert('位置情報がありません');
    return;
  }
  window.open(url, '_blank', 'noopener');
});

  // 1) 行ってみる！ = 履歴へ保存して /histories に遷移
  btnGo.addEventListener('click', () => {
    // 表示中の店名を履歴名として使う（50文字に抑制）
    const name = (document.getElementById('resName').textContent || '履歴').trim().slice(0, 50);
    
    const placeId = resultModalEl.dataset.placeId || null;
    const rating  = resultModalEl.dataset.rating ? Number(resultModalEl.dataset.rating) : null;
    const cuisines = (() => {
      try { return JSON.parse(resultModalEl.dataset.cuisines || '[]'); }
      catch { return []; }
    })();
    // criteria には「当時の検索条件＋店情報」をまとめて保存
    const criteriaObj = {
      decided_at: new Date().toISOString(),
      source: 'roulette/search',
      restaurant: {
        name: document.getElementById('resName').textContent || null,
        address: resultModalEl.dataset.address || null,
        lat: resultModalEl.dataset.lat ? Number(resultModalEl.dataset.lat) : null,
        lng: resultModalEl.dataset.lng ? Number(resultModalEl.dataset.lng) : null,
        place_id: placeId,
        rating: rating,
        cuisines: cuisines
      },
      filters: (() => {
        // openResultModal で set した criteria（JSON文字列 or "null"）
        try { return JSON.parse(resultModalEl.dataset.criteria || 'null'); }
        catch { return null; }
      })()
    };

    // hidden フォームに詰めて submit（→ histories.store）
    document.getElementById('history-name').value     = name;
    document.getElementById('history-criteria').value = JSON.stringify(criteriaObj);
    document.getElementById('go-history-form').submit();
  });
  async function reverseAreaName(lat, lng){
    try{
      const res = await fetch(`{{ route('roulette.reverse') }}?lat=${lat}&lng=${lng}`);
      if(!res.ok) throw 0;
      const j = await res.json();
      return j.area || null;
    }catch{ return null; }
  }
  // ===== 地図ピッカー =====
  const mapModal   = document.getElementById('mapPickerModal');
  const mapAddrEl  = document.getElementById('map-addr');
  const mapLLel    = document.getElementById('map-latlng');
  const mapRadiusV = document.getElementById('map-radius-view');
  const mapApply   = document.getElementById('map-apply');


  let _leaflet, _marker, _circle;

  // ====== 住所検索（OSM Nominatim） ======
  const mapSearchQ   = document.getElementById('map-search-q');
  const mapSearchBtn = document.getElementById('map-search-btn');

  async function geocodeOSM(q){
    const url = `{{ route('roulette.geocode') }}?q=${encodeURIComponent(q)}`;
    const res = await fetch(url, { headers: { 'Accept':'application/json' } });
    if(!res.ok) return null;
    const j = await res.json();
    if(!j || !j.hit) return null;
    return { lat: j.hit.lat, lng: j.hit.lng, label: j.hit.label };
  }

  async function performMapSearch(){
    const q = (mapSearchQ.value || '').trim();
    if(!q) return;
    mapSearchBtn.disabled = true;
    const prev = mapSearchBtn.textContent;
    mapSearchBtn.textContent = '検索中…';
    try{
      const r = await geocodeOSM(q);
      if(!r){ alert('見つかりませんでした'); return; }

      // 地図・マーカー・円を移動
      _leaflet.setView([r.lat, r.lng], 16);
      _marker.setLatLng([r.lat, r.lng]);
      _circle.setLatLng([r.lat, r.lng]);

      // 表示の更新
      mapLLel.textContent  = `${r.lat.toFixed(6)}, ${r.lng.toFixed(6)}`;
      mapAddrEl.textContent = r.label;

    }catch(e){
      console.warn(e);
      alert('検索に失敗しました');
    }finally{
      mapSearchBtn.disabled = false;
      mapSearchBtn.textContent = prev;
    }
  }

  // クリック＆Enterで検索
  if(mapSearchBtn) mapSearchBtn.addEventListener('click', performMapSearch);
  if(mapSearchQ)   mapSearchQ.addEventListener('keydown', e=>{
    if(e.key === 'Enter'){ e.preventDefault(); performMapSearch(); }
  });


// 現在の“距離”セレクト値（m）を取得
function currentRadiusMeters(){
  const rSel = document.getElementById('radius');
  const v = parseInt(rSel?.value || '500', 10);
  return isNaN(v) ? 500 : v;
}

  // モーダル開閉
  function openMapPicker(){
    const searchModal = document.getElementById('search-modal');
    if (searchModal) {
      searchModal.dataset.wasOpen = '1'; 
      searchModal.classList.add('hidden');
    }

    portalizeToBody(mapModal, 100010);
    mapModal.classList.remove('hidden');
    mapModal.classList.add('flex');
    document.documentElement.style.overflow = 'hidden';

      // 初期中心：hidden に値があればそこ。無ければキャッシュ→無ければ東京駅あたり
      const cached = (function(){
        try { return JSON.parse(localStorage.getItem('roulette.loc')) } catch { return null; }
    })();

    const center = {
      lat: latEl.value ? +latEl.value :
          cached?.lat ?? 35.681236,
      lng: lngEl.value ? +lngEl.value :
          cached?.lng ?? 139.767125
    };

    // 1回だけ初期化
    if(!_leaflet){
      _leaflet = L.map('pickMap').setView([center.lat, center.lng], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
      }).addTo(_leaflet);

      _marker = L.marker([center.lat, center.lng], {draggable:false}).addTo(_leaflet);
      _circle = L.circle([center.lat, center.lng], {radius: currentRadiusMeters()}).addTo(_leaflet);

      _leaflet.on('click', async (e) => {
        const {lat,lng} = e.latlng;
        _marker.setLatLng([lat,lng]);
        _circle.setLatLng([lat,lng]);
        mapLLel.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

        // 住所（またはエリア名）を取得して表示
        const area = await reverseAreaName(lat,lng);
        mapAddrEl.textContent = area || '住所取得できませんでした';
      });
    }else{
      _leaflet.setView([center.lat, center.lng], 15);
      _marker.setLatLng([center.lat, center.lng]);
      _circle.setLatLng([center.lat, center.lng]);
    }

    // 画面の距離セレクトに連動して円半径も更新
    const rMeters = currentRadiusMeters();
    _circle.setRadius(rMeters);
    mapRadiusV.textContent = rMeters + ' m';
    mapLLel.textContent = `${center.lat.toFixed(6)}, ${center.lng.toFixed(6)}`;
    reverseAreaName(center.lat, center.lng).then(a=> mapAddrEl.textContent = a || '(住所取得中)');

    // レイアウト崩れ防止（表示直後にサイズ調整）
    setTimeout(()=> _leaflet.invalidateSize(true), 0);
  }
  function closeMapPicker(){
    mapModal.classList.add('hidden');
    mapModal.classList.remove('flex');
    const searchModal = document.getElementById('search-modal');
    if (searchModal && searchModal.dataset.wasOpen === '1') {
      searchModal.classList.remove('hidden');
      document.documentElement.style.overflow = 'hidden';
      delete searchModal.dataset.wasOpen;
    }else{
      document.documentElement.style.overflow = ''
    }
  }

  // 背景/✕で閉じる
  document.querySelectorAll('#mapPickerModal [data-close="1"]').forEach(el => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeMapPicker();
    });
  });

  // 距離セレクト変更時、円も更新（モーダルが開いている場合のみ）
  document.getElementById('radius').addEventListener('change', ()=>{
    if(!_circle || mapModal.classList.contains('hidden')) return;
    const rm = currentRadiusMeters();
    _circle.setRadius(rm);
    mapRadiusV.textContent = rm + ' m';
  });

  // “この地点を適用” → placeName と hidden(lat/lng) を更新、色は適用前（灰）・適用後（青）
  mapApply.addEventListener('click', async ()=>{
    if(!_marker) return;
    const {lat, lng} = _marker.getLatLng();

    // 住所 or エリア名（サーバの reverse API を流用）
    const label = await reverseAreaName(lat, lng) || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;

    // 1) hidden を上書き（これが検索の基準地点）
    latEl.value = String(lat);
    lngEl.value = String(lng);

    // 2) ステータス表示とローカル保存（既存関数で統一）
    document.getElementById('js-loc-status').textContent =
        label ? `現在地: ${label}` : `現在地: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    saveLocCache(lat, lng, label);

    // 3) UIを更新（名称・適用済みバッジ）
    if (placeLabelEl) placeLabelEl.textContent = label || '（名称未取得）';
    showAppliedBadge(true);

    closeMapPicker();
  });

  // リサイズ時に高さを再計算
  window.addEventListener('resize', sizeSearchModal);


  // 例：ルーレット終了時に呼ぶ（あなたの既存コードから）
  // openResultModal(foundRestaurant, { criteria: currentCriteria, seed: currentSeed });
})();
</script>

<form id="go-history-form" method="POST" action="{{ route('histories.store') }}" class="hidden">
  @csrf
  <input type="hidden" name="name" id="history-name">
  <input type="hidden" name="criteria" id="history-criteria">
</form>

</x-app-layout>
