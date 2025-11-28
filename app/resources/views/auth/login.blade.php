<x-guest-layout>
    <x-auth-card>
        {{-- ロゴ（トップへリンク） --}}
        <x-slot name="logo">
            <a href="{{ route('top') }}" class="inline-flex items-center justify-center">
                {{-- ここでサイズを直接指定（px） --}}
                <x-application-logo :size="250" class="mx-auto" />
            </a>
        </x-slot>

        {{-- セッション系メッセージ --}}
        <x-auth-session-status class="mb-4" :status="session('status')" />
        {{-- バリデーションエラー --}}
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- メールアドレス --}}
            <div>
                <x-label for="email" value="メールアドレス" />
                <x-input id="email" class="block mt-1 w-full"
                         type="email" name="email" :value="old('email')" required autofocus />
            </div>

            {{-- パスワード --}}
            <div class="mt-4">
                <x-label for="password" value="パスワード" />
                <x-input id="password" class="block mt-1 w-full"
                         type="password" name="password" required autocomplete="current-password" />
            </div>

            {{-- ログイン状態を保持 --}}
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                           name="remember">
                    <span class="ml-2 text-sm text-gray-600">ログイン状態を保持する</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900"
                       href="{{ route('password.request') }}">
                        パスワードをお忘れの方はこちら
                    </a>
                @endif

                <x-button class="ml-3">
                    ログイン
                </x-button>
            </div>
        </form>
    </x-auth-card>
    {{-- ユーザーログイン画面の下部などに --}}
    <a href="{{ route('admin.login') }}" class="underline">管理者用</a>
</x-guest-layout>
