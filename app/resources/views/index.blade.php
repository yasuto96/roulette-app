<x-app-layout>
  <div class="py-6 max-w-4xl mx-auto">
    <h1 class="text-xl font-semibold">トップ</h1>
    <ul class="list-disc pl-6 mt-3 space-y-1">
      <li><a class="underline" href="{{ route('roulette.category.form') }}">カテゴリ・ルーレット</a></li>
      <li><a class="underline" href="{{ route('cuisines.index') }}">カテゴリ一覧</a></li>
      @auth
        <li><a class="underline" href="{{ route('roulette.search.form') }}">検索ルーレット（会員限定）</a></li>
        <li><a class="underline" href="{{ route('favorites.index') }}">お気に入り</a></li>
        <li><a class="underline" href="{{ route('histories.index') }}">履歴</a></li>
      @endauth
      <li><a class="underline" href="{{ route('restaurants.index') }}">レストラン一覧</a></li>
    </ul>
  </div>
</x-app-layout>
