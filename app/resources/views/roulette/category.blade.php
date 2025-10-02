<x-app-layout>
  <div class="py-6 max-w-3xl mx-auto">

    {{-- ルーレット（Canvas） --}}
    <div id="wheel-area" class="max-w-xl mx-auto mb-6">
      <h2 class="text-2xl font-semibold text-center mb-2">カテゴリルーレット</h2>

      <div class="relative flex flex-col items-center">
        <!-- 枠線クラスを外す -->
        <canvas id="roulette-canvas" width="380" height="380"
          class="block rounded-full bg-transparent drop-shadow"
          style="background-color: transparent;"
        ></canvas>
        <!-- ポインタ：左向き・少し大きめ・ルーレットに近づける -->
        <div
          aria-hidden="true"
          style="
            position:absolute;
            top:50%;
            transform:translateY(-50%);
            right:45px;                /* ルーレットの縁に寄せる。必要なら 0～8pxで微調整 */
            width:0; height:0;
            border-top:20px solid transparent;
            border-bottom:20px solid transparent;
            border-right:55px solid #ef4444; /* Tailwindのred-500相当 */
            z-index: 20;              /* キャンバスより前面へ */
            pointer-events:none;
          ">
        </div>

      </div>

      <div class="mt-3 flex gap-2 justify-center">
        <button type="button" id="spin-btn"  class="px-4 py-2 rounded bg-blue-600 text-white">回す！</button>
        <button type="button" id="wheel-reset" class="px-4 py-2 rounded border border-gray-300">リセット</button>
      </div>
      <p id="wheel-result" class="mt-3 text-center text-lg font-semibold"></p>
    </div>

    <form method="POST" action="{{ route('roulette.category.spin') }}" class="mt-4 space-y-4" id="spin-form">
      @csrf
      {{-- 一括選択 --}}
      <label class="flex items-center gap-2 mb-2">
        <input type="checkbox" id="select-all"
          checked
          onclick="window.rouletteToggleAll && window.rouletteToggleAll(this.checked)">
        <span class="font-medium">全件まとめて選択</span>
      </label>

      {{-- 一覧（※id=cuisine-list、name="cuisine_ids[]" を必ず守る） --}}
      <div id="cuisine-list" class="grid sm:grid-cols-2 gap-2">
        @foreach($cuisines as $c)
          <label class="flex items-center gap-2">
            <input type="checkbox" name="cuisine_ids[]" value="{{ $c->id }}">
            <span>{{ $c->name }}</span>
          </label>
        @endforeach
      </div>

      <!-- 操作ボタン行 -->
      <div class="flex flex-wrap gap-3 items-center">
        @auth
        <button type="button" id="delete-selected-btn"
            class="px-4 py-2 rounded border border-gray-400 text-gray-700 bg-white
                 disabled:opacity-100 disabled:text-gray-400 disabled:border-gray-300
                 enabled:bg-red-600 enabled:text-white enabled:border-red-600"
            disabled>
          選択カテゴリを削除
        </button>
        @endauth
      </div>
    </form>

    {{-- 非同期：カテゴリ追加 --}}
    <div class="mt-8 border-t pt-4">
      <h2 class="text-lg font-semibold">カテゴリを追加</h2>

      <!-- 入力とボタンの行 -->
      <div class="flex gap-2 mt-2 items-start">
        <input id="new-cuisine-input" class="flex-1 border rounded px-3 py-2"
             placeholder="例: 洋食（Enter または『追加』をクリック）">
        <button type="button" id="add-cuisine-btn"
              class="shrink-0 px-4 py-2 rounded bg-white text-black border border-black hover:bg-gray-50">
          追加
        </button>
      </div>

      <p id="add-cuisine-msg" class="text-sm mt-2"></p>
    </div>
    <div class="mt-6">
      <a href="{{ route('top') }}"
        class="px-4 py-2 rounded border border-black text-black bg-white hover:bg-gray-50">
        トップへ戻る
      </a>
    </div>

  </div>

  {{-- ページ専用スクリプト（ビルド不要） --}}
 <script>
(function(){
  'use strict';

  // ---- DOM 取得（listEl に統一） ----
  const listEl   = document.getElementById('cuisine-list');
  const spinForm = document.getElementById('spin-form');
  const selectAll= document.getElementById('select-all');
  const deleteBtn= document.getElementById('delete-selected-btn');
  const addBtn   = document.getElementById('add-cuisine-btn');
  const input    = document.getElementById('new-cuisine-input');
  const msg      = document.getElementById('add-cuisine-msg');
  const csrf     = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  // ★ チェックボックス取得は1定義だけ
  const getBoxes = () => Array.from(listEl.querySelectorAll('input[name="cuisine_ids[]"]'));

  // ---- 全選択 & 削除ボタン活性 ----
  function syncMaster(){
    const boxes = getBoxes();
    const allChecked  = boxes.length && boxes.every(cb => cb.checked);
    const someChecked = boxes.some(cb => cb.checked);
    selectAll.checked = allChecked;
    selectAll.indeterminate = !allChecked && someChecked;
    if (deleteBtn) deleteBtn.disabled = !someChecked;
  }
  selectAll.addEventListener('change', () => {
    getBoxes().forEach(cb => cb.checked = selectAll.checked);
    syncMaster();
    // ルーレットも即反映
    rotation = 0; build();
  });
  listEl.addEventListener('change', e => {
    if (e.target && e.target.name === 'cuisine_ids[]') {
      syncMaster();
      rotation = 0; build();
    }
  });
  syncMaster();

  // ---- 追加（非同期） ----
  async function addCuisine(){
    msg.textContent = ''; msg.className = 'text-sm mt-2';
    const name = (input.value || '').trim();
    if (!name){ msg.textContent = '名前を入力してください。'; msg.classList.add('text-red-600'); return; }

    addBtn.disabled = true;
    try{
      const res = await fetch('{{ route('cuisines.store') }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf||'','Accept':'application/json'},
        body: JSON.stringify({name})
      });
      if(!res.ok){
        let err='追加に失敗しました。';
        try{ const j=await res.json(); err = (j.errors?.name?.[0]) || j.message || err; }catch{}
        throw new Error(err);
      }
      const data = await res.json(); // {id,name}

      const ok = () => {
        msg.textContent = 'カテゴリを追加しました。';
        msg.className = 'text-sm mt-2 text-green-700';
        input.value=''; syncMaster(); rotation=0; build();
      };

      // 既存ならチェックだけ
      const already = listEl.querySelector(`input[name="cuisine_ids[]"][value="${data.id}"]`);
      if (already){ already.checked = true; ok(); return; }

      // 新規項目を追加
      const label = document.createElement('label');
      label.className = 'flex items-center gap-2';
      label.innerHTML = `<input type="checkbox" name="cuisine_ids[]" value="${data.id}" checked>
                         <span>${data.name}</span>`;
      listEl.appendChild(label);

      const hidden = document.createElement('input');
      hidden.type='hidden'; hidden.name='visible_ids[]'; hidden.value=String(data.id);
      spinForm.appendChild(hidden);

      ok();
    }catch(e){
      msg.textContent = e.message || '追加に失敗しました。';
      msg.classList.add('text-red-600');
    }finally{
      addBtn.disabled = false;
    }
  }
  addBtn?.addEventListener('click', addCuisine);
  input?.addEventListener('keydown', e => { if(e.key==='Enter'){ e.preventDefault(); addCuisine(); } });

  // ---- 選択削除（非同期） ----
  async function deleteSelected(){
    const ids = getBoxes().filter(cb=>cb.checked).map(cb=>parseInt(cb.value,10));
    if(!ids.length) return;
    if(!confirm(`${ids.length}件のカテゴリを削除します。よろしいですか？`)) return;

    deleteBtn.disabled = true;
    try{
      const res = await fetch('{{ route('cuisines.destroyMany') }}', {
        method:'DELETE',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf||'','Accept':'application/json'},
        body: JSON.stringify({ids})
      });
      if(!res.ok){
        let err='削除に失敗しました。';
        try{ const j=await res.json(); err=j.message||err; }catch{}
        throw new Error(err);
      }
      const json = await res.json();
      const deleted = new Set(json.deleted_ids || []);
      getBoxes().forEach(cb => { if(deleted.has(parseInt(cb.value,10))) cb.closest('label')?.remove(); });

      // hidden も除去
      deleted.forEach(id=>{
        spinForm.querySelectorAll(`input[name="visible_ids[]"][value="${id}"]`).forEach(el=>el.remove());
      });

      selectAll.checked=false; syncMaster(); rotation=0; build();
      alert('削除しました。');
    }catch(e){ alert(e.message || '削除に失敗しました。'); }
    finally{ deleteBtn.disabled=false; }
  }
  deleteBtn?.addEventListener('click', deleteSelected);

  // ==== ルーレット（Canvas） ====
  const canvas   = document.getElementById('roulette-canvas');
  if(!canvas) return;
  const ctx      = canvas.getContext('2d');
  const spinBtn  = document.getElementById('spin-btn');
  const resetBtn = document.getElementById('wheel-reset');
  const resultEl = document.getElementById('wheel-result');

  function items(){
    const b = getBoxes();
    const checked = b.filter(x=>x.checked);
    const src = checked.length ? checked : b;
    return src.map(x => (x.closest('label')?.querySelector('span')?.textContent || x.value).trim());
  }

  function drawPlaceholder(){
    const r=canvas.width/2;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.save(); ctx.translate(r,r);
    ctx.beginPath(); ctx.arc(0,0,r-2,0,Math.PI*2); ctx.strokeStyle='#d1d5db'; ctx.lineWidth=4; ctx.stroke();
    ctx.fillStyle='#6b7280'; ctx.font='16px system-ui,sans-serif'; ctx.textAlign='center';
    ctx.fillText('カテゴリを選択してください',0,6);
    ctx.restore();
  }

  function build(){
    const arr = items();
    if(arr.length===0){ drawPlaceholder(); return; }
    const r=canvas.width/2;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.save(); ctx.translate(r,r);
    for(let i=0;i<arr.length;i++){
      const start=2*Math.PI*i/arr.length, end=2*Math.PI*(i+1)/arr.length;
      ctx.beginPath(); ctx.moveTo(0,0); ctx.arc(0,0,r-2,start,end); ctx.closePath();
      ctx.fillStyle=`hsl(${i*360/arr.length},70%,70%)`; ctx.fill();
      ctx.strokeStyle='#fff'; ctx.lineWidth=2; ctx.stroke();
      const mid=(start+end)/2;
      ctx.save(); ctx.rotate(mid); ctx.textAlign='right'; ctx.fillStyle='#111'; ctx.font='16px system-ui,sans-serif';
      const t = arr[i].length>12 ? arr[i].slice(0,12) : arr[i];
      ctx.fillText(t, r-16, 6);
      ctx.restore();
    }
    ctx.restore();
  }

  let rotation=0;
  function redrawRot(){
    const arr = items();
    if(arr.length===0){ drawPlaceholder(); return; }
    const r=canvas.width/2;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.save(); ctx.translate(r,r); ctx.rotate(rotation);
    for(let i=0;i<arr.length;i++){
      const start=2*Math.PI*i/arr.length, end=2*Math.PI*(i+1)/arr.length;
      ctx.beginPath(); ctx.moveTo(0,0); ctx.arc(0,0,r-2,start,end); ctx.closePath();
      ctx.fillStyle=`hsl(${i*360/arr.length},70%,70%)`; ctx.fill();
      ctx.strokeStyle='#fff'; ctx.lineWidth=2; ctx.stroke();
      const mid=(start+end)/2;
      ctx.save(); ctx.rotate(mid); ctx.textAlign='right'; ctx.fillStyle='#111'; ctx.font='16px system-ui,sans-serif';
      const t = arr[i].length>12 ? arr[i].slice(0,12) : arr[i];
      ctx.fillText(t, r-16, 6);
      ctx.restore();
    }
    ctx.restore();
  }

  function spin(){
    const arr = items(); if(arr.length===0) return;
    const n=arr.length, pick=Math.floor(Math.random()*n);
    const center=(2*Math.PI*pick/n)+Math.PI/n;

    const start=rotation;
    const target=rotation+(4+Math.random()*2)*2*Math.PI + (2*Math.PI - center);
    const delta=target-start, dur=3000, t0=performance.now();
    resultEl.textContent='';

    function frame(now){
      const t=Math.min(1,(now-t0)/dur);
      rotation=start + delta*(1-Math.pow(1-t,3));
      redrawRot();
      if(t<1){ requestAnimationFrame(frame); }
      else{
        const angle=(2*Math.PI - (rotation % (2*Math.PI))) % (2*Math.PI);
        const idx=Math.floor(angle/(2*Math.PI/n));
        resultEl.textContent=`結果：${arr[idx]}`;
      }
    }
    requestAnimationFrame(frame);
  }

  spinBtn?.addEventListener('click', spin);
  resetBtn?.addEventListener('click', ()=>{ rotation=0; resultEl.textContent=''; build(); });

  // インライン onClick 用：全選択
  window.rouletteToggleAll = function(on){
    selectAll.checked = !!on;
    getBoxes().forEach(cb => cb.checked = !!on);
    syncMaster(); rotation=0; build();
  };

  if (selectAll) {
    selectAll.checked = true;
    window.rouletteToggleAll?.(true); // 全項目にチェック
    syncMaster();                      // indeterminate/削除ボタン状態を同期
  }

  build();
})();
</script>

</x-app-layout>
