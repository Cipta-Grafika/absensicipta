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
  <!-- Scripts & Initial State -->
  <script>
    if (localStorage.getItem('isDark') === 'true' || (!('isDark' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    }
    if (localStorage.getItem('hr_sidebar_collapsed') === 'true') {
      document.documentElement.classList.add('hr-sidebar-collapsed');
    }
  </script>
  <style>
    html.hr-sidebar-collapsed .hr-main-content {
      padding-left: 0 !important;
    }
    html.hr-sidebar-collapsed .hr-sidebar-container {
      transform: translateX(-100%) !important;
      opacity: 0 !important;
      pointer-events: none !important;
    }
  </style>
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Styles -->
  @livewireStyles

  @stack('styles')
</head>

<body class="font-sans antialiased">
  <div class="flex min-h-screen flex-col justify-between bg-gray-100 dark:bg-gray-900 pt-16">
    <x-banner />
    
    @livewire('navigation-menu')

    @if (request()->routeIs('hr.*'))
      <!-- HR PORTAL FIXED LAYOUT: FULL-WIDTH NAVBAR -> FULL-WIDTH HEADER BANNER -> FIXED SIDEBAR BELOW -->
      <div class="relative flex-grow flex w-full"
           x-data="{
             sidebarCollapsed: localStorage.getItem('hr_sidebar_collapsed') === 'true',
             toggleSidebar() {
               this.sidebarCollapsed = !this.sidebarCollapsed;
               localStorage.setItem('hr_sidebar_collapsed', this.sidebarCollapsed);
               if (this.sidebarCollapsed) {
                 document.documentElement.classList.add('hr-sidebar-collapsed');
               } else {
                 document.documentElement.classList.remove('hr-sidebar-collapsed');
               }
             }
           }">

        @if (isset($header))
          <!-- Full-Width Fixed Header Banner (Directly Below Fixed Top Navbar) -->
          <header class="fixed top-16 left-0 right-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200/80 dark:border-gray-700">
            <div class="w-full px-4 py-3.5 sm:px-6 lg:px-8 flex items-center gap-3">
              <!-- Sidebar Toggle Button (Sky Blue Theme Icon) -->
              <button type="button"
                      @click="toggleSidebar()"
                      title="Toggle Sidebar"
                      class="hidden lg:flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gray-200/80 bg-white text-sky-600 shadow-sm transition-all duration-200 hover:bg-sky-50 hover:border-sky-200 dark:border-gray-700 dark:bg-gray-800 dark:text-sky-400 dark:hover:bg-gray-700 cursor-pointer">
                <x-heroicon-o-view-columns class="h-4 w-4 text-sky-600 dark:text-sky-400" />
              </button>

              <div class="flex-1 min-w-0">
                {{ $header }}
              </div>
            </div>
          </header>
        @endif

        <!-- Reusable Fixed Left Sidebar Component (Positioned Below Banner Component) -->
        <x-hr-sidebar />

        <!-- Right Main Content Area (Slot & Footer) -->
        <div class="hr-main-content flex-1 min-w-0 flex flex-col min-h-[calc(100vh-4rem)] lg:pl-64 transition-[padding] duration-300 ease-in-out"
             :class="sidebarCollapsed ? '!pl-0' : 'lg:pl-64'">

          <main class="flex-grow flex flex-col {{ isset($header) ? 'pt-14' : '' }} pb-16 md:pb-0">
            {{ $slot }}
          </main>

          <!-- Footer -->
          <footer class="mt-auto border-t border-gray-200/80 bg-white/80 py-4 dark:border-gray-800 dark:bg-gray-800/80 backdrop-blur-sm mb-16 md:mb-0">
            <div class="w-full flex items-center justify-center gap-1.5 px-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 sm:px-6 lg:px-8">
              <x-heroicon-s-fire class="h-4 w-4 text-amber-500 shrink-0" />
              <span>Crafted by</span>
              <a href="https://zaenalalfian.cloud" target="_blank" rel="noopener noreferrer" class="font-semibold text-sky-500 underline transition-colors hover:text-sky-600 dark:text-sky-400 dark:hover:text-sky-300">
                Zaenal Alfian
              </a>
            </div>
          </footer>
        </div>
      </div>
    @else
      <!-- STANDARD LAYOUT FOR NON-HR PAGES -->
      @if (isset($header))
        <header class="sticky top-16 z-40 bg-white shadow dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
          <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            {{ $header }}
          </div>
        </header>
      @endif

      <!-- Page Content -->
      <main class="flex-grow flex flex-col pb-16 md:pb-0">
        {{ $slot }}
      </main>
    @endif

    <!-- Mobile Bottom Navigation Bar -->
    <x-bottom-navigation-bar />

    <!-- Footer for Non-HR pages -->
    @unless (request()->routeIs('hr.*'))
      <footer class="mt-auto border-t border-gray-200/80 bg-white/80 py-4 dark:border-gray-800 dark:bg-gray-800/80 backdrop-blur-sm mb-16 md:mb-0">
        <div class="mx-auto flex max-w-7xl items-center justify-center gap-1.5 px-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 sm:px-6 lg:px-8">
          <x-heroicon-s-fire class="h-4 w-4 text-amber-500 shrink-0" />
          <span>Crafted by</span>
          <a href="https://zaenalalfian.cloud" target="_blank" rel="noopener noreferrer" class="font-semibold text-sky-500 underline transition-colors hover:text-sky-600 dark:text-sky-400 dark:hover:text-sky-300">
            Zaenal Alfian
          </a>
        </div>
      </footer>
    @endunless
  </div>

  @stack('modals')

  @livewireScripts

  @stack('scripts')
</body>

</html>
