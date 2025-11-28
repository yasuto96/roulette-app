<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16">
      {{-- Left: Logo --}}
      <div class="flex">
        <div class="shrink-0 flex items-center">
          <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('images/title-small.png') }}?v={{ filemtime(public_path('images/title-small.png')) }}"
                 alt="今日のご飯は？" height="100" style="height:150px;width:auto" class="select-none" draggable="false">
          </a>
        </div>
      </div>

      {{-- Right: Auth / Links --}}
      <div class="hidden sm:flex items-center gap-6">
        {{-- ▼ 管理者ログイン中 --}}
        @if (auth('admin')->check())
          <x-dropdown align="right" width="48">
            <x-slot name="trigger">
              <button type="button" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <div>{{ auth('admin')->user()->name }}（管理者）</div>
                <div class="ms-1">
                  <svg class="fill-current h-4 w-4" viewBox="0 0 20 20"><path fill-rule="evenodd"
                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    clip-rule="evenodd"/></svg>
                </div>
              </button>
            </x-slot>
            <x-slot name="content">
              {{-- 管理者ログアウト --}}
              <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <x-dropdown-link :href="route('admin.logout')"
                  onclick="event.preventDefault(); this.closest('form').submit();">
                  管理者ログアウト
                </x-dropdown-link>
              </form>
            </x-slot>
          </x-dropdown>

        {{-- ▼ 一般ユーザーでログイン中（webガード） --}}
        @elseif (auth()->check())
          <x-dropdown align="right" width="48">
            <x-slot name="trigger">
              <button type="button" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <div>{{ auth()->user()->name }}</div>
                <div class="ms-1">
                  <svg class="fill-current h-4 w-4" viewBox="0 0 20 20"><path fill-rule="evenodd"
                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    clip-rule="evenodd"/></svg>
                </div>
              </button>
            </x-slot>
            <x-slot name="content">
              {{-- 一般ログアウト --}}
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                  onclick="event.preventDefault(); this.closest('form').submit();">
                  ログアウト
                </x-dropdown-link>
              </form>
            </x-slot>
          </x-dropdown>

        {{-- ▼ 未ログイン（ゲスト） --}}
        @else
          <a href="{{ route('login') }}" class="text-sm text-black hover:text-gray-800">ログイン</a>
          <a href="{{ route('register') }}" class="text-sm text-black hover:text-gray-800" style="margin-left:20px;">
              新規登録
          </a>
        @endif
      </div>

      {{-- Hamburger (mobile) --}}
      <div class="-mr-2 flex items-center sm:hidden">
        <button @click="open = ! open"
                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
          <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"/>
            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>
  </div>

  {{-- Responsive (mobile) --}}
  <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
    {{-- 管理者 --}}
    @if (auth('admin')->check())
      <div class="pt-2 pb-3 px-4 text-gray-600">{{ auth('admin')->user()->name }}（管理者）</div>
      <div class="pt-4 pb-1 border-t border-gray-200 px-4">
        <form method="POST" action="{{ route('admin.logout') }}">
          @csrf
          <x-responsive-nav-link :href="route('admin.logout')"
             onclick="event.preventDefault(); this.closest('form').submit();">
            管理者ログアウト
          </x-responsive-nav-link>
        </form>
      </div>

    {{-- 一般ユーザー --}}
    @elseif (auth()->check())
      <div class="pt-2 pb-3 px-4 text-gray-600">{{ auth()->user()->name }}</div>
      <div class="pt-4 pb-1 border-t border-gray-200 px-4">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <x-responsive-nav-link :href="route('logout')"
             onclick="event.preventDefault(); this.closest('form').submit();">
            ログアウト
          </x-responsive-nav-link>
        </form>
      </div>

    {{-- ゲスト --}}
    @else
      <div class="space-y-1 px-4 py-3">
        <a href="{{ route('login') }}" class="block text-base text-gray-600 hover:text-gray-900">ログイン</a>
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="block text-base text-gray-600 hover:text-gray-900">新規登録</a>
        @endif
        <a href="{{ route('admin.login') }}" class="block text-base text-gray-600 hover:text-gray-900">管理者用</a>
      </div>
    @endif
  </div>
</nav>
