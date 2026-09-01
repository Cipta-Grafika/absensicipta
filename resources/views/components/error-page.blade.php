@props(['code', 'title', 'message'])

@php
    $homeUrl = url('/');
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->isSuperadmin || $user->isAdmin) {
            $homeUrl = route('hr.dashboard');
        } elseif ($user->isPayroll || $user->isOwner) {
            $homeUrl = route('payroll.dashboard');
        } elseif ($user->isSyirkah) {
            $homeUrl = route('payroll.saving-transactions');
        } else {
            $homeUrl = route('home');
        }
    }
@endphp

<x-guest-layout>
  <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex w-full max-w-2xl flex-col items-center text-center">
      
      @if (isset($illustration))
        <div class="mb-6 text-red-500 dark:text-red-400">
          {{ $illustration }}
        </div>
      @endif
      
      <!-- HTTP Error Code: Dominan, Besar, dan Merah (Menggunakan clamp agar responsif sempurna) -->
      <h1 class="font-black tracking-tighter text-red-600 drop-shadow-md dark:text-red-500" 
          style="font-size: clamp(6rem, 20vw, 12rem); line-height: 0.8; margin-bottom: 0.5rem;">
        {{ $code }}
      </h1>
      
      <!-- Error Title -->
      <h2 class="mb-4 mt-2 text-2xl font-bold tracking-tight text-gray-800 dark:text-gray-100 sm:text-3xl lg:text-4xl">
        {{ $title }}
      </h2>
      
      <!-- Error Message -->
      <p class="mb-8 max-w-lg text-sm font-medium text-gray-500 dark:text-gray-400 sm:text-base leading-relaxed">
        {{ $message }}
      </p>
      
      <!-- Action Buttons -->
      <div class="flex w-full flex-col items-center justify-center gap-4 sm:flex-row sm:gap-4 mt-2">
        <!-- Button Kembali -->
        <a href="javascript:history.back()" 
           class="group inline-flex w-full sm:w-40 items-center justify-center rounded-xl border-2 border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-all duration-200 ease-in-out hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-500 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
          <svg class="mr-2 h-4 w-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          Kembali
        </a>
        
        <!-- Button Ke Beranda (Blue Sky Accent) -->
        <a href="{{ $homeUrl }}" 
           class="group inline-flex w-full sm:w-40 items-center justify-center rounded-xl bg-sky-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-sky-500/30 transition-all duration-200 ease-in-out hover:bg-sky-600 hover:shadow-lg hover:shadow-sky-600/40 focus:outline-none focus:ring-4 focus:ring-sky-300 dark:bg-sky-600 dark:shadow-sky-900/30 dark:hover:bg-sky-500 dark:focus:ring-sky-800">
          Ke Beranda
          <svg class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        </a>
      </div>
    </div>
  </div>
</x-guest-layout>
