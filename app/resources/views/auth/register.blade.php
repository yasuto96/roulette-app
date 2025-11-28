<x-guest-layout>
    {{-- 上寄せ＆ロゴとの間隔は x-auth-card の props で微調整できます --}}
    <x-auth-card containerClass="pt-8" gap="mt-2">
        <x-slot name="logo">
            <a href="{{ route('top') }}" class="inline-flex items-center">
                {{-- ロゴサイズは数値で調整（px）。例: 140 / 160 / 200 … --}}
                <x-application-logo :size="250" class="mx-auto" />
            </a>
        </x-slot>

        {{-- バリデーションエラー表示 --}}
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('register') }}">
            @csrf

            {{-- 名前 --}}
            <div>
                <x-label for="name" value="名前" />
                <x-input
                    id="name"
                    class="block mt-1 w-full"
                    type="text"
                    name="name"
                    :value="old('name')"
                    required
                    autofocus
                />
            </div>

            {{-- メールアドレス --}}
            <div class="mt-4">
                <x-label for="email" value="メールアドレス" />
                <x-input
                    id="email"
                    class="block mt-1 w-full"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                />
            </div>

            {{-- パスワード --}}
            <div class="mt-4">
                <x-label for="password" value="パスワード" />
                <x-input
                    id="password"
                    class="block mt-1 w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                />
            </div>

            {{-- パスワード（確認） --}}
            <div class="mt-4">
                <x-label for="password_confirmation" value="パスワード（確認）" />
                <x-input
                    id="password_confirmation"
                    class="block mt-1 w-full"
                    type="password"
                    name="password_confirmation"
                    required
                />
            </div>

            <div class="flex items-center justify-end mt-4">
                <a
                    class="underline text-sm text-gray-600 hover:text-gray-900"
                    href="{{ route('login') }}"
                >
                    すでに登録済みの方はこちら
                </a>

                <x-button class="ml-3">
                    登録
                </x-button>
            </div>
        </form>
    </x-auth-card>
    {{-- ユーザー新規登録画面の下部などに --}}
    <a href="{{ route('admin.register') }}" class="underline">管理者登録</a>
</x-guest-layout>
