<x-guest-layout>
  <x-authentication-card>
    <x-slot name="logo">
    </x-slot>

    <div class="mb-6 flex flex-col items-center text-center">
      <x-authentication-card-logo />
      <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Join Board!</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create your new account</p>
    </div>

    <x-validation-errors class="mb-4" />

    <form method="POST" action="{{ route('register') }}" x-data="{ show: false, showConfirm: false }">
      @csrf

      <!-- Baris 1: Nama -->
      <div class="mt-4">
        <x-input id="name" class="block w-full bg-white dark:bg-gray-700" type="text" name="name" :value="old('name')" required autofocus
          autocomplete="name" placeholder="{{ __('Name') }}" />
      </div>

      <!-- Baris 2: Email -->
      <div class="mt-4">
        <x-input id="email" class="block w-full bg-white dark:bg-gray-700" type="email" name="email" :value="old('email')" required
          autocomplete="username" placeholder="{{ __('Email') }}" />
      </div>

      <!-- Baris 3: Phone dan Gender -->
      <div class="mt-4 flex flex-col gap-4 md:flex-row">
        <div class="w-full">
          <x-input id="phone" class="block w-full bg-white dark:bg-gray-700" type="number" name="phone" :value="old('phone')" required
            autocomplete="username" placeholder="{{ __('Phone Number') }}" />
        </div>
        <div class="w-full">
          <x-select id="gender" class="block w-full bg-white dark:bg-gray-700" name="gender" required>
            <option disabled selected>{{ __('Select Gender') }}</option>
            <option value="male">
              {{ __('Male') }}
            </option>
            <option value="female">
              {{ __('Female') }}
            </option>
          </x-select>
        </div>
      </div>

      <!-- Baris 4: Password -->
      <div class="mt-4">
        <div class="relative">
          <x-input id="password" class="block w-full bg-white dark:bg-gray-700 pr-10" x-bind:type="show ? 'text' : 'password'" name="password" required
            autocomplete="new-password" placeholder="{{ __('Password') }}" />
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

      <div class="mt-4">
        <div class="relative">
          <x-input id="password_confirmation" class="block w-full bg-white dark:bg-gray-700 pr-10" x-bind:type="showConfirm ? 'text' : 'password'" name="password_confirmation" required
            autocomplete="new-password" placeholder="{{ __('Confirm Password') }}" />
          <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none">
            <svg x-show="!showConfirm" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg x-show="showConfirm" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 015.058-5.058m1.276-1.276A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21m-2.175-2.175l-15.65-15.65" />
            </svg>
          </button>
        </div>
      </div>

      @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
        <div class="mt-4">
          <x-label for="terms">
            <div class="flex items-center">
              <x-checkbox name="terms" id="terms" required />

              <div class="ms-2">
                {!! __('I agree to the :terms_of_service and :privacy_policy', [
                    'terms_of_service' =>
                        '<a target="_blank" href="' .
                        route('terms.show') .
                        '" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">' .
                        __('Terms of Service') .
                        '</a>',
                    'privacy_policy' =>
                        '<a target="_blank" href="' .
                        route('policy.show') .
                        '" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">' .
                        __('Privacy Policy') .
                        '</a>',
                ]) !!}
              </div>
            </div>
          </x-label>
        </div>
      @endif

      <div class="mt-6 flex flex-col items-center">
        <x-button class="w-full flex justify-center py-3">
          {{ __('Register') }}
        </x-button>

        <a class="mt-4 rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
          href="{{ route('login') }}">
          {{ __('Already registered?') }}
        </a>
      </div>
    </form>
  </x-authentication-card>
</x-guest-layout>
