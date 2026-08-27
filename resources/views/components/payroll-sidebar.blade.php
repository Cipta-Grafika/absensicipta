<!-- REUSABLE PAYROLL PORTAL LEFT SIDEBAR COMPONENT (FIXED FULL-HEIGHT WITH AUTO VERTICAL SCROLL) -->
@php
  $isSyirkahActive = request()->routeIs('payroll.saving-transactions') || request()->routeIs('payroll.loans') || request()->routeIs('payroll.flexible-deductions');
  $isMasterActive = request()->routeIs('payroll.employee-salaries') || request()->routeIs('payroll.payment-methods') || request()->routeIs('payroll.savings');
  $isImportActive = request()->routeIs('payroll.import-export.*');
@endphp

<aside class="hr-sidebar-container fixed top-[7.25rem] left-0 bottom-0 z-30 w-64 hidden lg:flex flex-col bg-white/85 dark:bg-gray-900/85 backdrop-blur-xl border-r border-sky-200/80 dark:border-gray-800/80 select-none transition-all duration-300 ease-in-out"
       :class="sidebarCollapsed ? '-translate-x-64 opacity-0 pointer-events-none' : 'translate-x-0 opacity-100 pointer-events-auto'"
       x-data="{
         openSyirkah: {{ $isSyirkahActive ? 'true' : 'false' }},
         openMaster: {{ $isMasterActive ? 'true' : 'false' }},
         openImport: {{ $isImportActive ? 'true' : 'false' }}
       }">

  <!-- Auto Vertical Scroll Wrapper for All Sidebar Items -->
  <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-1.5 custom-scrollbar-y">

    @if (!Auth::user()?->isSyirkah)
    <!-- 1. DASBOR -->
    @php $active = request()->routeIs('payroll.dashboard'); @endphp
    <a href="{{ route('payroll.dashboard') }}"
       class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
      <x-heroicon-o-home class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
      <span class="truncate">Dasbor</span>
    </a>

    <!-- 2. RIWAYAT GAJI -->
    @php $active = request()->routeIs('payroll.history'); @endphp
    <a href="{{ route('payroll.history') }}"
       class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
      <x-heroicon-o-document-text class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
      <span class="truncate">Riwayat Gaji</span>
    </a>
    @endif

    <!-- 3. SYIRKAH (DROPDOWN) -->
    <div>
      <button type="button" @click="openSyirkah = !openSyirkah"
              class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isSyirkahActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
        <div class="flex items-center gap-3 truncate">
          <x-heroicon-o-banknotes class="h-5 w-5 shrink-0 {{ $isSyirkahActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
          <span class="truncate">Syirkah</span>
        </div>
        <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openSyirkah ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
      </button>

      <div x-show="openSyirkah" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
        @php $active = request()->routeIs('payroll.saving-transactions'); @endphp
        <a href="{{ route('payroll.saving-transactions') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Mutasi Syirkah</span>
        </a>

        @php $active = request()->routeIs('payroll.loans'); @endphp
        <a href="{{ route('payroll.loans') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Pinjaman Karyawan</span>
        </a>

        @php $active = request()->routeIs('payroll.flexible-deductions'); @endphp
        <a href="{{ route('payroll.flexible-deductions') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Potongan Fleksibel</span>
        </a>
      </div>
    </div>

    <!-- 4. MASTER DATA (DROPDOWN) -->
    <div>
      <button type="button" @click="openMaster = !openMaster"
              class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isMasterActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
        <div class="flex items-center gap-3 truncate">
          <x-heroicon-o-circle-stack class="h-5 w-5 shrink-0 {{ $isMasterActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
          <span class="truncate">Master Data</span>
        </div>
        <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openMaster ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
      </button>

      <div x-show="openMaster" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
        @if (!Auth::user()?->isSyirkah)
        @php $active = request()->routeIs('payroll.employee-salaries'); @endphp
        <a href="{{ route('payroll.employee-salaries') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Master Gaji</span>
        </a>

        @php $active = request()->routeIs('payroll.payment-methods'); @endphp
        <a href="{{ route('payroll.payment-methods') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Metode Pembayaran</span>
        </a>
        @endif

        @php $active = request()->routeIs('payroll.savings'); @endphp
        <a href="{{ route('payroll.savings') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Syirkah</span>
        </a>
      </div>
    </div>

    <!-- 5. IMPORT & EXPORT (DROPDOWN) -->
    <div>
      <button type="button" @click="openImport = !openImport"
              class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isImportActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
        <div class="flex items-center gap-3 truncate">
          <x-heroicon-o-arrow-up-tray class="h-5 w-5 shrink-0 {{ $isImportActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
          <span class="truncate">Import & Export</span>
        </div>
        <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openImport ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
      </button>

      <div x-show="openImport" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
        @if (!Auth::user()?->isSyirkah)
        @php $active = request()->routeIs('payroll.import-export.employee-salaries'); @endphp
        <a href="{{ route('payroll.import-export.employee-salaries') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Master Gaji</span>
        </a>

        @php $active = request()->routeIs('payroll.import-export.payment-methods'); @endphp
        <a href="{{ route('payroll.import-export.payment-methods') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Metode Pembayaran</span>
        </a>
        @endif

        @php $active = request()->routeIs('payroll.import-export.savings'); @endphp
        <a href="{{ route('payroll.import-export.savings') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Syirkah</span>
        </a>

        @php $active = request()->routeIs('payroll.import-export.saving-transactions'); @endphp
        <a href="{{ route('payroll.import-export.saving-transactions') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Mutasi Syirkah</span>
        </a>
      </div>
    </div>

  </div>

  <!-- Bottom Brand Footer inside Sidebar -->
  <div class="p-3 border-t border-sky-200/80 dark:border-gray-800/80 bg-sky-50/50 dark:bg-gray-800/40 backdrop-blur-md">
    <div class="flex items-center gap-2.5 px-2 py-1.5">
      <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-sky-500 to-emerald-500 text-white shadow-md shadow-sky-500/20">
        <x-heroicon-o-banknotes class="h-4 w-4" />
      </div>
      <div class="flex flex-col min-w-0">
        <span class="text-xs font-bold text-gray-900 dark:text-white truncate">Payroll Portal</span>
        <span class="text-[10px] text-gray-500 dark:text-gray-400 truncate">Cipta Grafika</span>
      </div>
    </div>
  </div>

</aside>

<!-- 2. MOBILE & TABLET SLIDE-OVER SIDEBAR DRAWER (< lg) -->
<template x-teleport="body">
  <div x-show="mobileSidebarOpen"
       x-trap.inert.noscroll="mobileSidebarOpen"
       class="fixed inset-0 z-[200] lg:hidden"
       aria-labelledby="mobile-payroll-sidebar-title"
       role="dialog"
       aria-modal="true"
       style="display: none;">

    <!-- Dark Backdrop Overlay -->
    <div x-show="mobileSidebarOpen"
         x-transition:enter="ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-on:click="mobileSidebarOpen = false"
         class="fixed inset-0 bg-gray-900/50 dark:bg-black/70 transition-opacity duration-150 ease-out">
    </div>

    <!-- Slide-Over Left Panel -->
    <div class="fixed inset-y-0 left-0 max-w-full flex">
      <div x-show="mobileSidebarOpen"
           x-transition:enter="transform transition-transform ease-out duration-200"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transform transition-transform ease-in duration-150"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           x-data="{
             openSyirkah: {{ $isSyirkahActive ? 'true' : 'false' }},
             openMaster: {{ $isMasterActive ? 'true' : 'false' }},
             openImport: {{ $isImportActive ? 'true' : 'false' }}
           }"
           class="w-72 max-w-[85vw] h-full flex flex-col bg-white dark:bg-gray-900 border-r border-sky-200/80 dark:border-gray-800/80 shadow-xl relative select-none transform-gpu will-change-transform">

        <!-- Drawer Header -->
        <div class="flex items-center justify-between px-4 py-3.5 border-b border-sky-200/80 dark:border-gray-800/80 bg-sky-50/50 dark:bg-gray-800/50">
          <div class="flex items-center gap-2.5">
            <div class="h-8 w-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-xs">
              <x-heroicon-s-banknotes class="h-5 w-5" />
            </div>
            <div>
              <span class="font-bold text-sm text-gray-900 dark:text-white block leading-tight">Payroll Portal</span>
              <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block font-medium">Navigasi Utama</span>
            </div>
          </div>
          <button type="button"
                  @click="mobileSidebarOpen = false"
                  class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300 transition-colors cursor-pointer">
            <x-heroicon-o-x-mark class="h-5 w-5" />
          </button>
        </div>

        <!-- Sidebar Navigation Content -->
        <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-1.5 custom-scrollbar-y" @click="if ($event.target.closest('a')) mobileSidebarOpen = false">

          @if (!Auth::user()?->isSyirkah)
          <!-- 1. DASBOR -->
          @php $active = request()->routeIs('payroll.dashboard'); @endphp
          <a href="{{ route('payroll.dashboard') }}"
             class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
            <x-heroicon-o-home class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
            <span class="truncate">Dasbor</span>
          </a>

          <!-- 2. RIWAYAT GAJI -->
          @php $active = request()->routeIs('payroll.history'); @endphp
          <a href="{{ route('payroll.history') }}"
             class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
            <x-heroicon-o-document-text class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
            <span class="truncate">Riwayat Gaji</span>
          </a>
          @endif

          <!-- 3. SYIRKAH (DROPDOWN) -->
          <div>
            <button type="button" @click="openSyirkah = !openSyirkah"
                    class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isSyirkahActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
              <div class="flex items-center gap-3 truncate">
                <x-heroicon-o-banknotes class="h-5 w-5 shrink-0 {{ $isSyirkahActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
                <span class="truncate">Syirkah</span>
              </div>
              <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openSyirkah ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
            </button>

            <div x-show="openSyirkah" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
              @php $active = request()->routeIs('payroll.saving-transactions'); @endphp
              <a href="{{ route('payroll.saving-transactions') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Mutasi Syirkah</span>
              </a>

              @php $active = request()->routeIs('payroll.loans'); @endphp
              <a href="{{ route('payroll.loans') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Pinjaman Karyawan</span>
              </a>
            </div>
          </div>

          <!-- 4. MASTER DATA (DROPDOWN) -->
          <div>
            <button type="button" @click="openMaster = !openMaster"
                    class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isMasterActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
              <div class="flex items-center gap-3 truncate">
                <x-heroicon-o-circle-stack class="h-5 w-5 shrink-0 {{ $isMasterActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
                <span class="truncate">Master Data</span>
              </div>
              <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openMaster ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
            </button>

            <div x-show="openMaster" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
              @if (!Auth::user()?->isSyirkah)
              @php $active = request()->routeIs('payroll.employee-salaries'); @endphp
              <a href="{{ route('payroll.employee-salaries') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Master Gaji</span>
              </a>

              @php $active = request()->routeIs('payroll.payment-methods'); @endphp
              <a href="{{ route('payroll.payment-methods') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Metode Pembayaran</span>
              </a>
              @endif

              @php $active = request()->routeIs('payroll.savings'); @endphp
              <a href="{{ route('payroll.savings') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Syirkah</span>
              </a>
            </div>
          </div>

          <!-- 5. IMPORT & EXPORT (DROPDOWN) -->
          <div>
            <button type="button" @click="openImport = !openImport"
                    class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isImportActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
              <div class="flex items-center gap-3 truncate">
                <x-heroicon-o-arrow-up-tray class="h-5 w-5 shrink-0 {{ $isImportActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
                <span class="truncate">Import & Export</span>
              </div>
              <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openImport ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
            </button>

            <div x-show="openImport" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
              @php $active = request()->routeIs('payroll.import-export.employee-salaries'); @endphp
              <a href="{{ route('payroll.import-export.employee-salaries') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Master Gaji</span>
              </a>

              @php $active = request()->routeIs('payroll.import-export.payment-methods'); @endphp
              <a href="{{ route('payroll.import-export.payment-methods') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Metode Pembayaran</span>
              </a>

              @php $active = request()->routeIs('payroll.import-export.savings'); @endphp
              <a href="{{ route('payroll.import-export.savings') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Syirkah</span>
              </a>

              @php $active = request()->routeIs('payroll.import-export.saving-transactions'); @endphp
              <a href="{{ route('payroll.import-export.saving-transactions') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Mutasi Syirkah</span>
              </a>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</template>
