<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">

  <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
  <link rel="icon" href="{{ asset('favicon.ico') }}?v=1.0.1" type="image/x-icon">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=1.0.1" type="image/x-icon">
  <link rel="preload" href="{{ asset('hris.svg') }}" as="image" type="image/svg+xml">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <!-- Scripts -->
  <script>
    if (localStorage.getItem('isDark') === 'true' || (!('isDark' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    }
  </script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Styles -->
  @livewireStyles

  @stack('styles')
</head>

<body class="font-sans antialiased">
  <div class="flex min-h-screen flex-col justify-between bg-gray-100 dark:bg-gray-900">
    <x-banner />
    
    @livewire('navigation-menu')

    <!-- Page Heading -->
    @if (isset($header))
      <header class="sticky top-16 z-40 bg-white shadow dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          {{ $header }}
        </div>
      </header>
    @endif

    <!-- Page Content -->
    <main class="flex-grow">
      {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-gray-200/80 bg-white/80 py-4 dark:border-gray-800 dark:bg-gray-800/80 backdrop-blur-sm">
      <div class="mx-auto flex max-w-7xl items-center justify-center gap-1.5 px-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 sm:px-6 lg:px-8">
        <x-heroicon-s-fire class="h-4 w-4 text-amber-500 shrink-0" />
        <span>Crafted by</span>
        <a href="https://zaenalalfian.cloud" target="_blank" rel="noopener noreferrer" class="font-semibold text-sky-500 underline transition-colors hover:text-sky-600 dark:text-sky-400 dark:hover:text-sky-300">
          Zaenal Alfian
        </a>
      </div>
    </footer>
  </div>

  @stack('modals')

  @livewireScripts

  @stack('scripts')
</body>

</html>
