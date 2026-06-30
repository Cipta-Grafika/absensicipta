<x-guest-layout>
  <x-authentication-card>
    <x-slot name="logo">
    </x-slot>

    <div class="mb-6 flex flex-col items-center sm:items-start">
      <x-authentication-card-logo />
      <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Welcome to Board!</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Log in into your account</p>
    </div>

    <x-validation-errors class="mb-4" />

    @session('status')
      <div class="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
        {{ $value }}
      </div>
    @endsession

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <div>
        <x-input id="email" class="mt-1 block w-full bg-white dark:bg-gray-700" type="email" name="email" :value="old('email')" required
          autofocus autocomplete="username" placeholder="Email" />
      </div>

      <div class="mt-4" x-data="{ show: false }">
        <div class="relative">
          <x-input id="password" class="mt-1 block w-full bg-white dark:bg-gray-700 pr-10" x-bind:type="show ? 'text' : 'password'" name="password" required
            autocomplete="current-password" placeholder="Password" />
          <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none">
            <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 015.058-5.058m1.276-1.276A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21m-2.175-2.175l-15.65-15.65" />
            </svg>
          </button>
        </div>
      </div>

      <div class="mt-4 flex items-center justify-between">
        <label for="remember_me" class="flex items-center">
          <x-checkbox id="remember_me" name="remember" checked />
          <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
        </label>
        
        @if (Route::has('password.request'))
          <a class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            href="{{ route('password.request') }}">
            {{ __('Forgot password?') }}
          </a>
        @endif
      </div>

      <div class="mt-6">
        <x-button class="w-full flex justify-center py-3">
          {{ __('Login') }}
        </x-button>
      </div>
    </form>
  </x-authentication-card>
</x-guest-layout>
