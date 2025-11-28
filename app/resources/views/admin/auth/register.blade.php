<x-guest-layout>
    <x-auth-card containerClass="pt-8" gap="mt-2">
        <x-slot name="logo">
            <a href="{{ route('top') }}" class="inline-flex items-center">
                <x-application-logo :size="250" class="mx-auto" />
            </a>
        </x-slot>

        {{-- 見出し：管理者用であることを明示 --}}
        <h3 class="text-2xl font-bold text-center mt-2 mb-4">管理者用 新規登録</h3>

        {{-- バリデーションエラー --}}
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        {{-- ★ 管理者用の登録ルートに送る --}}
        <form method="POST" action="{{ route('admin.register') }}">
            @csrf

            {{-- 名前 --}}
            <div>
                <x-label for="name" value="名前" />
                <x-input id="name" class="block mt-1 w-full"
                         type="text" name="name" :value="old('name')" required autofocus />
            </div>

            {{-- メールアドレス --}}
            <div class="mt-4">
                <x-label for="email" value="メールアドレス" />
                <x-input id="email" class="block mt-1 w-full"
                         type="email" name="email" :value="old('email')" required />
            </div>

            {{-- パスワード --}}
            <div class="mt-4">
                <x-label for="password" value="パスワード" />
                <x-input id="password" class="block mt-1 w-full"
                         type="password" name="password" required autocomplete="new-password" />
            </div>

            {{-- パスワード（確認） --}}
            <div class="mt-4">
                <x-label for="password_confirmation" value="パスワード（確認）" />
                <x-input id="password_confirmation" class="block mt-1 w-full"
                         type="password" name="password_confirmation" required />
            </div>

            {{-- 管理者用パスワード（共通パスワード） --}}
            <div class="mt-4">
                <x-label for="admin_secret" value="管理者用パスワード" />
                <x-input id="admin_secret" class="block mt-1 w-full"
                         type="password" name="admin_secret"
                         placeholder="運用で配布された管理者用パスワードを入力" required />
            </div>

            <div class="flex items-center justify-between mt-6">
                {{-- 管理者ログインへの導線 --}}
                <a class="underline text-sm text-gray-600 hover:text-gray-900"
                   href="{{ route('admin.login') }}">
                    すでに登録済みの方（管理者ログイン）
                </a>

                <x-button class="ml-3">登録</x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
