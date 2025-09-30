<x-app-layout>
  <div class="py-6 max-w-3xl mx-auto">
    <h1 class="text-xl font-semibold">ルーレット結果</h1>

    {{-- 1) カテゴリルーレットの結果（カテゴリ名を主役で表示） --}}
    @if(!empty($categoryLabel))
      <div class="mt-4 border rounded p-4">
        <div class="text-sm text-gray-600">選ばれたカテゴリ</div>
        <h2 class="text-lg font-semibold mt-1">{{ $categoryLabel }}</h2>
        <div class="mt-3 flex gap-4">
          <a href="{{ route('roulette.category.form') }}" class="underline">カテゴリでもう一度</a>
          @auth
            <a href="{{ route('roulette.search.form', ['cuisine_id' => $pickedCuisineId]) }}" class="underline">
              このカテゴリで店を探す（会員）
            </a>
          @endauth
        </div>
      </div>
    @endif

    {{-- 2) 検索ルーレット（ローカルDB）で店が当たったとき --}}
    @if($restaurant)
      <div class="mt-4 border rounded p-4">
        <h2 class="text-lg font-semibold">{{ $restaurant->name }}</h2>

        @if($restaurant->cuisines && $restaurant->cuisines->count())
          <div class="text-sm text-gray-600 mt-1">
            カテゴリ: {{ $restaurant->cuisines->pluck('name')->join(' / ') }}
          </div>
        @endif

        @if(!empty($restaurant->address))
          <div class="mt-1">住所: {{ $restaurant->address }}</div>
        @endif

        <div class="mt-3 flex gap-3">
          <a href="{{ route('roulette.category.form') }}" class="underline">カテゴリでもう一度</a>
          @auth
            <a href="{{ route('roulette.search.form') }}" class="underline">検索でもう一度</a>
          @endauth
        </div>
      </div>
    @endif

    {{-- 3) 検索ルーレット（外部プロバイダ＝Google等）で店名だけあるとき --}}
    @isset($pickedName)
      <div class="mt-4 border rounded p-4">
        <h2 class="text-lg font-semibold">{{ $pickedName }}</h2>
        @if(!empty($pickedAddress))
          <div class="mt-1">住所: {{ $pickedAddress }}</div>
        @endif
        <div class="mt-3 flex gap-3">
          <a href="{{ route('roulette.search.form') }}" class="underline">検索でもう一度</a>
        </div>
      </div>
    @endisset

    {{-- 4) 何も当たらなかったとき（カテゴリ名も店も無し） --}}
    @if(empty($categoryLabel) && !$restaurant && !isset($pickedName))
      <p class="mt-4">候補が見つかりませんでした。</p>
      <div class="mt-3 flex gap-3">
        <a href="{{ route('roulette.category.form') }}" class="underline">カテゴリに戻る</a>
        @auth
          <a href="{{ route('roulette.search.form') }}" class="underline">検索に戻る</a>
        @endauth
      </div>
    @endif
  </div>
</x-app-layout>
