<x-app-layout>
  <style>
    /* かわいい丸ゴ系のWebフォントを読み込み（Google Fonts） */
    @import url('https://fonts.googleapis.com/css2?family=Mochiy+Pop+One&family=M+PLUS+Rounded+1c:wght@700&display=swap');

    .btn-pop {
      /* ← フォント指定（先頭が適用、無ければ順にフォールバック） */
      font-family: 'Mochiy Pop One', 'M PLUS Rounded 1c', 'Hiragino Maru Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
      font-size: 25px;
      letter-spacing: .02em;

      display:inline-block;
      background: linear-gradient(#ffb74d, #ff9800); /* 明るめ→濃いオレンジ */
      color:#111;
      font-weight:700;
      padding:24px 50px;
      border-radius:16px;
      text-decoration:none;
      border:2px solid #c96a00;
      box-shadow: 0 6px 0 #c96a00, 0 10px 20px rgba(0,0,0,.15);
      transition: transform .07s ease, box-shadow .07s ease;
    }
    .btn-pop:hover {
      transform: translateY(1px);
      box-shadow: 0 5px 0 #c96a00, 0 8px 16px rgba(0,0,0,.15);
    }
    .btn-pop:active {
      transform: translateY(4px);
      box-shadow: 0 2px 0 #c96a00, 0 4px 8px rgba(0,0,0,.15);
    }
</style>


  <div class="py-6 max-w-4xl mx-auto">
    <div style="margin-top: -30px; display:flex; justify-content:center;">
      <img src="{{ asset('images/title.png') }}?v={{ filemtime(public_path('images/title.png')) }}"
       alt="今日のご飯は？"
       width="600"
       style="width:600px; height:auto"
       class="select-none"
       draggable="false">
    </div>
    {{-- ルーレット開始ボタン（中央） --}}
    <div class="text-center mt-6">
      <a href="{{ url('/roulette/category?reset=1') }}" class="btn-pop">
        スタート
      </a>
    </div>
  </div>
</x-app-layout>
