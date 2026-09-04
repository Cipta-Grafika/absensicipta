<!-- REUSABLE HR PORTAL LEFT SIDEBAR COMPONENT (FIXED FULL-HEIGHT WITH AUTO VERTICAL SCROLL) -->
@php
  $isJadwalActive = request()->routeIs('hr.work-schedules') || request()->routeIs('hr.holidays');
  $isMasterActive = request()->routeIs('hr.masters.*') || request()->routeIs('hr.masterdata.*');
  $isImportActive = request()->routeIs('hr.import-export.*');
@endphp

<aside class="hr-sidebar-container fixed top-[7.25rem] left-0 bottom-0 z-30 w-64 hidden lg:flex flex-col bg-white/85 dark:bg-gray-900/85 backdrop-blur-xl border-r border-sky-200/80 dark:border-gray-800/80 select-none transition-all duration-300 ease-in-out"
       :class="sidebarCollapsed ? '-translate-x-64 opacity-0 pointer-events-none' : 'translate-x-0 opacity-100 pointer-events-auto'"
       x-data="{
         openJadwal: {{ $isJadwalActive ? 'true' : 'false' }},
         openMaster: {{ $isMasterActive ? 'true' : 'false' }},
         openImport: {{ $isImportActive ? 'true' : 'false' }}
       }">

  <!-- Auto Vertical Scroll Wrapper for All Sidebar Items -->
  <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-1.5 custom-scrollbar-y">

    <!-- 1. DASBOR -->
    @php $active = request()->routeIs('hr.dashboard'); @endphp
    <a href="{{ route('hr.dashboard') }}"
       class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
      <x-heroicon-o-home class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
      <span class="truncate">Dasbor</span>
    </a>

    <!-- 2. BARCODE (SUPERADMIN ONLY) -->
    @if (Auth::user()?->isSuperadmin)
      @php $active = request()->routeIs('hr.barcodes') || request()->routeIs('hr.barcodes.*'); @endphp
      <a href="{{ route('hr.barcodes') }}"
         class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
        <x-heroicon-o-qr-code class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
        <span class="truncate">Barcode</span>
      </a>
    @endif

    <!-- 3. ABSENSI -->
    @php $active = request()->routeIs('hr.attendances'); @endphp
    <a href="{{ route('hr.attendances') }}"
       class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
      <x-heroicon-o-calendar-days class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
      <span class="truncate">Absensi</span>
    </a>

    <!-- 4. GANTI JAM -->
    @php $active = request()->routeIs('hr.replacement-approvals'); @endphp
    <a href="{{ route('hr.replacement-approvals') }}"
       class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
      <x-heroicon-o-arrow-path class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
      <span class="truncate">Ganti Jam</span>
    </a>

    <!-- 5. LEMBUR -->
    @php $active = request()->routeIs('hr.overtime-approvals'); @endphp
    <a href="{{ route('hr.overtime-approvals') }}"
       class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
      <x-heroicon-o-fire class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
      <span class="truncate">Lembur</span>
    </a>

    <!-- 6. KARYAWAN -->
    @php $active = request()->routeIs('hr.employees'); @endphp
    <a href="{{ route('hr.employees') }}"
       class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
      <x-heroicon-o-user-group class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
      <span class="truncate">Karyawan</span>
    </a>

    <!-- 7. SYIRKAH (APPROVAL & MUTASI) -->
    @php 
      $active = request()->routeIs('payroll.saving-transactions') || request()->routeIs('payroll.savings'); 
      $pendingWithdrawalsCount = \App\Models\SavingWithdrawal::where('status', 'pending')
          ->when(Auth::user()?->isAdmin && !Auth::user()?->isSuperadmin && Auth::user()?->division_id, function($q) {
              $q->whereHas('user', fn($sq) => $sq->where('division_id', Auth::user()->division_id));
          })
          ->count();
    @endphp
    <a href="{{ route('payroll.saving-transactions') }}"
       class="group flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
      <div class="flex items-center gap-3 truncate">
        <x-heroicon-o-banknotes class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
        <span class="truncate">Syirkah</span>
      </div>
      @if ($pendingWithdrawalsCount > 0)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white shadow-xs animate-pulse">
          {{ $pendingWithdrawalsCount }}
        </span>
      @endif
    </a>

    <!-- 7. JADWAL & LIBUR (DROPDOWN) -->
    <div>
      <button type="button" @click="openJadwal = !openJadwal"
              class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isJadwalActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
        <div class="flex items-center gap-3 truncate">
          <x-heroicon-o-clock class="h-5 w-5 shrink-0 {{ $isJadwalActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
          <span class="truncate">Jadwal & Libur</span>
        </div>
        <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openJadwal ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
      </button>

      <div x-show="openJadwal" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
        @php $active = request()->routeIs('hr.work-schedules'); @endphp
        <a href="{{ route('hr.work-schedules') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Jadwal Rolling</span>
        </a>

        @if (Auth::user()?->isSuperadmin)
          @php $active = request()->routeIs('hr.holidays'); @endphp
          <a href="{{ route('hr.holidays') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Manajemen Hari Libur</span>
          </a>
        @endif
      </div>
    </div>

    <!-- 8. MASTER DATA (DROPDOWN) -->
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
        @if (Auth::user()?->isSuperadmin)
          @php $active = request()->routeIs('hr.masters.division'); @endphp
          <a href="{{ route('hr.masters.division') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Divisi</span>
          </a>

          @php $active = request()->routeIs('hr.masters.job-title'); @endphp
          <a href="{{ route('hr.masters.job-title') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Jabatan</span>
          </a>

          @php $active = request()->routeIs('hr.masters.education'); @endphp
          <a href="{{ route('hr.masters.education') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Pendidikan</span>
          </a>

          @php $active = request()->routeIs('hr.masters.leaderboard'); @endphp
          <a href="{{ route('hr.masters.leaderboard') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Leaderboard</span>
          </a>

          @php $active = request()->routeIs('hr.masters.scan-feedback'); @endphp
          <a href="{{ route('hr.masters.scan-feedback') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Feedback Ucapan</span>
          </a>
        @endif

        @if (Auth::user()?->isAdmin)
          @php $active = request()->routeIs('hr.masters.shift'); @endphp
          <a href="{{ route('hr.masters.shift') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Shift</span>
          </a>

          @php $active = request()->routeIs('hr.masters.overtime-rate'); @endphp
          <a href="{{ route('hr.masters.overtime-rate') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Tarif Lembur</span>
          </a>
        @endif

        @if (Auth::user()?->isSuperadmin)
          @php $active = request()->routeIs('hr.masters.admin'); @endphp
          <a href="{{ route('hr.masters.admin') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Admin</span>
          </a>
        @endif
      </div>
    </div>

    <!-- 9. IMPORT & EXPORT (DROPDOWN) -->
    <div>
      <button type="button" @click="openImport = !openImport"
              class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isImportActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
        <div class="flex items-center gap-3 truncate">
          <x-heroicon-o-arrow-down-tray class="h-5 w-5 shrink-0 {{ $isImportActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
          <span class="truncate">Import & Export</span>
        </div>
        <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openImport ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
      </button>

      <div x-show="openImport" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
        @php $active = request()->routeIs('hr.import-export.users'); @endphp
        <a href="{{ route('hr.import-export.users') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Karyawan / Admin</span>
        </a>

        @php $active = request()->routeIs('hr.import-export.attendances'); @endphp
        <a href="{{ route('hr.import-export.attendances') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Absensi</span>
        </a>

        @php $active = request()->routeIs('hr.import-export.overtimes'); @endphp
        <a href="{{ route('hr.import-export.overtimes') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Lembur</span>
        </a>

        @php $active = request()->routeIs('hr.import-export.work-schedules'); @endphp
        <a href="{{ route('hr.import-export.work-schedules') }}"
           class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
          <span>Jadwal Rolling</span>
        </a>

        @if (Auth::user()?->isSuperadmin)
          @php $active = request()->routeIs('hr.import-export.holidays'); @endphp
          <a href="{{ route('hr.import-export.holidays') }}"
             class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
            <span>Hari Libur</span>
          </a>
        @endif
      </div>
    </div>

  </div>
</aside>

<!-- 2. MOBILE & TABLET SLIDE-OVER SIDEBAR DRAWER (< lg) -->
<template x-teleport="body">
  <div x-show="mobileSidebarOpen"
       x-trap.inert.noscroll="mobileSidebarOpen"
       class="fixed inset-0 z-[200] lg:hidden"
       aria-labelledby="mobile-hr-sidebar-title"
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
             openJadwal: {{ $isJadwalActive ? 'true' : 'false' }},
             openMaster: {{ $isMasterActive ? 'true' : 'false' }},
             openImport: {{ $isImportActive ? 'true' : 'false' }}
           }"
           class="w-72 max-w-[85vw] h-full flex flex-col bg-white dark:bg-gray-900 border-r border-sky-200/80 dark:border-gray-800/80 shadow-xl relative select-none transform-gpu will-change-transform">

        <!-- Drawer Header -->
        <div class="flex items-center justify-between px-4 py-3.5 border-b border-sky-200/80 dark:border-gray-800/80 bg-sky-50/50 dark:bg-gray-800/50">
          <div class="flex items-center gap-2.5">
            <div class="h-8 w-8 rounded-lg bg-sky-500 flex items-center justify-center text-white shadow-xs">
              <x-heroicon-s-building-office-2 class="h-5 w-5" />
            </div>
            <div>
              <span class="font-bold text-sm text-gray-900 dark:text-white block leading-tight">HR Portal</span>
              <span class="text-[10px] text-sky-600 dark:text-sky-400 block font-medium">Navigasi Utama</span>
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

          <!-- 1. DASBOR -->
          @php $active = request()->routeIs('hr.dashboard'); @endphp
          <a href="{{ route('hr.dashboard') }}"
             class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
            <x-heroicon-o-home class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
            <span class="truncate">Dasbor</span>
          </a>

          <!-- 2. BARCODE (SUPERADMIN ONLY) -->
          @if (Auth::user()?->isSuperadmin)
            @php $active = request()->routeIs('hr.barcodes') || request()->routeIs('hr.barcodes.*'); @endphp
            <a href="{{ route('hr.barcodes') }}"
               class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
              <x-heroicon-o-qr-code class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
              <span class="truncate">Barcode</span>
            </a>
          @endif

          <!-- 3. ABSENSI -->
          @php $active = request()->routeIs('hr.attendances'); @endphp
          <a href="{{ route('hr.attendances') }}"
             class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
            <x-heroicon-o-calendar-days class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
            <span class="truncate">Absensi</span>
          </a>

          <!-- 4. GANTI JAM -->
          @php $active = request()->routeIs('hr.replacement-approvals'); @endphp
          <a href="{{ route('hr.replacement-approvals') }}"
             class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
            <x-heroicon-o-arrow-path class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
            <span class="truncate">Ganti Jam</span>
          </a>

          <!-- 5. LEMBUR -->
          @php $active = request()->routeIs('hr.overtime-approvals'); @endphp
          <a href="{{ route('hr.overtime-approvals') }}"
             class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
            <x-heroicon-o-fire class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
            <span class="truncate">Lembur</span>
          </a>

          <!-- 6. KARYAWAN -->
          @php $active = request()->routeIs('hr.employees'); @endphp
          <a href="{{ route('hr.employees') }}"
             class="group flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
            <x-heroicon-o-user-group class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
            <span class="truncate">Karyawan</span>
          </a>

          <!-- 7. SYIRKAH (APPROVAL & MUTASI) -->
          @php 
            $active = request()->routeIs('payroll.saving-transactions') || request()->routeIs('payroll.savings'); 
            $pendingWithdrawalsCount = \App\Models\SavingWithdrawal::where('status', 'pending')
                ->when(Auth::user()?->isAdmin && !Auth::user()?->isSuperadmin && Auth::user()?->division_id, function($q) {
                    $q->whereHas('user', fn($sq) => $sq->where('division_id', Auth::user()->division_id));
                })
                ->count();
          @endphp
          <a href="{{ route('payroll.saving-transactions') }}"
             class="group flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
            <div class="flex items-center gap-3 truncate">
              <x-heroicon-o-banknotes class="h-5 w-5 shrink-0 {{ $active ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
              <span class="truncate">Syirkah</span>
            </div>
            @if ($pendingWithdrawalsCount > 0)
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white shadow-xs animate-pulse">
                {{ $pendingWithdrawalsCount }}
              </span>
            @endif
          </a>

          <!-- 7. JADWAL & LIBUR (DROPDOWN) -->
          <div>
            <button type="button" @click="openJadwal = !openJadwal"
                    class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isJadwalActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
              <div class="flex items-center gap-3 truncate">
                <x-heroicon-o-clock class="h-5 w-5 shrink-0 {{ $isJadwalActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
                <span class="truncate">Jadwal & Libur</span>
              </div>
              <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openJadwal ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
            </button>

            <div x-show="openJadwal" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
              @php $active = request()->routeIs('hr.work-schedules'); @endphp
              <a href="{{ route('hr.work-schedules') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Jadwal Rolling</span>
              </a>

              @if (Auth::user()?->isSuperadmin)
                @php $active = request()->routeIs('hr.holidays'); @endphp
                <a href="{{ route('hr.holidays') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Manajemen Hari Libur</span>
                </a>
              @endif
            </div>
          </div>

          <!-- 8. MASTER DATA (DROPDOWN) -->
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
              @if (Auth::user()?->isSuperadmin)
                @php $active = request()->routeIs('hr.masters.division'); @endphp
                <a href="{{ route('hr.masters.division') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Divisi</span>
                </a>

                @php $active = request()->routeIs('hr.masters.job-title'); @endphp
                <a href="{{ route('hr.masters.job-title') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Jabatan</span>
                </a>

                @php $active = request()->routeIs('hr.masters.education'); @endphp
                <a href="{{ route('hr.masters.education') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Pendidikan</span>
                </a>

                @php $active = request()->routeIs('hr.masters.leaderboard'); @endphp
                <a href="{{ route('hr.masters.leaderboard') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Leaderboard</span>
                </a>

                @php $active = request()->routeIs('hr.masters.scan-feedback'); @endphp
                <a href="{{ route('hr.masters.scan-feedback') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Feedback Ucapan</span>
                </a>
              @endif

              @if (Auth::user()?->isAdmin)
                @php $active = request()->routeIs('hr.masters.shift'); @endphp
                <a href="{{ route('hr.masters.shift') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Shift</span>
                </a>

                @php $active = request()->routeIs('hr.masters.overtime-rate'); @endphp
                <a href="{{ route('hr.masters.overtime-rate') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Tarif Lembur</span>
                </a>
              @endif

              @if (Auth::user()?->isSuperadmin)
                @php $active = request()->routeIs('hr.masters.admin'); @endphp
                <a href="{{ route('hr.masters.admin') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Admin</span>
                </a>
              @endif
            </div>
          </div>

          <!-- 9. IMPORT & EXPORT (DROPDOWN) -->
          <div>
            <button type="button" @click="openImport = !openImport"
                    class="group flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition-all duration-150 {{ $isImportActive ? 'bg-gray-100/80 dark:bg-gray-700/80 text-gray-900 dark:text-white' : 'text-gray-600 hover:bg-gray-100/80 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200' }}">
              <div class="flex items-center gap-3 truncate">
                <x-heroicon-o-arrow-down-tray class="h-5 w-5 shrink-0 {{ $isImportActive ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
                <span class="truncate">Import & Export</span>
              </div>
              <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400" x-bind:class="openImport ? 'rotate-180 text-gray-600 dark:text-gray-300' : ''" />
            </button>

            <div x-show="openImport" x-collapse x-cloak class="mt-1 ml-4 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
              @php $active = request()->routeIs('hr.import-export.users'); @endphp
              <a href="{{ route('hr.import-export.users') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Karyawan / Admin</span>
              </a>

              @php $active = request()->routeIs('hr.import-export.attendances'); @endphp
              <a href="{{ route('hr.import-export.attendances') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Absensi</span>
              </a>

              @php $active = request()->routeIs('hr.import-export.overtimes'); @endphp
              <a href="{{ route('hr.import-export.overtimes') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Lembur</span>
              </a>

              @php $active = request()->routeIs('hr.import-export.work-schedules'); @endphp
              <a href="{{ route('hr.import-export.work-schedules') }}"
                 class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                <span>Jadwal Rolling</span>
              </a>

              @if (Auth::user()?->isSuperadmin)
                @php $active = request()->routeIs('hr.import-export.holidays'); @endphp
                <a href="{{ route('hr.import-export.holidays') }}"
                   class="group flex items-center justify-between px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 {{ $active ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 font-bold' : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-gray-200' }}">
                  <span>Hari Libur</span>
                </a>
              @endif
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</template>
