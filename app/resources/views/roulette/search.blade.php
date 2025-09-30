<x-app-layout>
  <div class="py-6 max-w-3xl mx-auto">
    <h1 class="text-xl font-semibold">検索ルーレット（会員限定）</h1>
    <form method="POST" action="{{ route('roulette.search.spin') }}" class="mt-4 space-y-3">
      @csrf
      <div>
        <label class="block text-sm font-medium">キーワード（店名/住所）</label>
        <input name="keyword" type="text" class="border rounded px-3 py-2 w-full"
               value="{{ old('keyword') }}" placeholder="例：渋谷 ラーメン">
      </div>
      <div>
        <label class="block text-sm font-medium">カテゴリ（任意）</label>
        <select name="cuisine_id" class="border rounded px-3 py-2 w-full">
          <option value="">指定なし</option>
          @foreach($cuisines as $c)
            <option value="{{ $c->id }}" {{ old('cuisine_id')==$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white">条件から回す！</button>
    </form>
  </div>
</x-app-layout>
