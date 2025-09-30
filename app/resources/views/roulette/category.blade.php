<x-app-layout>
  <div class="py-6 max-w-3xl mx-auto">
    <h1 class="text-xl font-semibold">カテゴリルーレット</h1>

    <form method="POST" action="{{ route('roulette.category.spin') }}" class="mt-4 space-y-4">
      @csrf

      <p class="text-sm text-gray-600">複数チェックして回してください。何も選ばないと全カテゴリから抽選します。</p>

      <div class="grid sm:grid-cols-2 gap-2">
        @foreach($cuisines as $c)
          <label class="flex items-center gap-2">
            <input type="checkbox" name="cuisine_ids[]" value="{{ $c->id }}"
                   {{ collect(old('cuisine_ids', []))->contains($c->id) ? 'checked' : '' }}>
            <span>{{ $c->name }}</span>
          </label>
        @endforeach
      </div>

      @auth
      <div>
        <label class="block text-sm font-medium">新規カテゴリ（任意・1件）</label>
        <input name="new_cuisine" class="border rounded px-3 py-2 w-full" placeholder="例: 洋食">
        <p class="text-xs text-gray-500 mt-1">入力すると同時にカテゴリを作成して抽選対象に含めます。</p>
      </div>
      @endauth

      <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white">回す！</button>
    </form>
  </div>
</x-app-layout>
