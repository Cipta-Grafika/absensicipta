<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
  <link rel="icon" href="{{ asset('favicon.ico') }}?v=1.0.1" type="image/x-icon">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=1.0.1" type="image/x-icon">

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
  <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    
    <!-- Sticky Wrapper for Navbar and Header -->
    <div class="z-40 w-full flex flex-col" style="position: sticky; top: 0;">
      <x-banner />
      
      @livewire('navigation-menu')

      <!-- Page Heading -->
      @if (isset($header))
        <header class="bg-white shadow dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
          <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            {{ $header }}
          </div>
        </header>
      @endif
    </div>

    <!-- Page Content -->
    <main>
      {{ $slot }}
    </main>
  </div>

  @stack('modals')

  @livewireScripts

  @stack('scripts')
</body>

</html>
