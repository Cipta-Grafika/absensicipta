<!-- REUSABLE PAYROLL PORTAL LEFT SIDEBAR COMPONENT (FIXED FULL-HEIGHT WITH AUTO VERTICAL SCROLL) -->
@php
  $isSyirkahActive = request()->routeIs('payroll.saving-transactions') || request()->routeIs('payroll.loans');
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
