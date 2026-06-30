@php
  $date = Carbon\Carbon::now();
@endphp
<div x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  @pushOnce('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  @endpushOnce

  <x-filter-sidebar maxWidth="sm">
    <x-slot name="title">Absensi Filters</x-slot>
    <x-slot name="actions">
      <button type="button" wire:click="$set('month', ''); $set('week', ''); $set('date', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
      </button>
    </x-slot>
    
    <x-slot name="content">
      <div class="flex flex-col gap-6">
        <div>
          <x-label for="month_filter" value="Per Bulan" class="mb-1"></x-label>
          <x-input type="month" name="month_filter" id="month_filter" class="w-full" wire:model.live="month" />
        </div>
        <div>
          <x-label for="week_filter" value="Per Minggu" class="mb-1"></x-label>
          <x-input type="week" name="week_filter" id="week_filter" class="w-full" wire:model.live="week" />
        </div>
        <div>
          <x-label for="day_filter" value="Per Hari" class="mb-1"></x-label>
          <x-input type="date" name="day_filter" id="day_filter" class="w-full" wire:model.live="date" />
        </div>
      </div>
    </x-slot>
  </x-filter-sidebar>

  <div class="flex flex-col justify-between sm:flex-row items-center mb-4">
    <h3 class="text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Absensi {{ $titlePrefix }}
    </h3>
    <h3 class="text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200 mt-4 sm:mt-0">
      Jumlah Karyawan: {{ $employeesCount }}
    </h3>
  </div>
  <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Kehadiran Container -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50/50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
        <h3 class="font-medium text-gray-700 dark:text-gray-300">Kehadiran</h3>
      </div>
      <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
        <!-- Hadir -->
        <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
              Hadir <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-green-500 dark:text-green-400">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $presentCount }}</p>
            @if ($stats['present']['is_up'])
              <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['present']['trend'] }}
              </span>
            @elseif ($stats['present']['is_down'])
              <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['present']['trend'] }}
              </span>
            @else
              <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
              </span>
            @endif
          </div>
          <div class="mt-3">
            <svg class="h-6 w-full text-green-100 dark:text-green-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor"><path d="M0 20 L0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10 L100 20 Z" opacity="0.5"></path><path d="M0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10" fill="none" stroke="currentColor" stroke-width="1.5" class="text-green-400 dark:text-green-500"></path></svg>
          </div>
        </div>
        <!-- WFH -->
        <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
              WFH <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-purple-500 dark:text-purple-400">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $wfhCount }}</p>
            @if ($stats['wfh']['is_up'])
              <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['wfh']['trend'] }}
              </span>
            @elseif ($stats['wfh']['is_down'])
              <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['wfh']['trend'] }}
              </span>
            @else
              <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
              </span>
            @endif
          </div>
          <div class="mt-3">
            <svg class="h-6 w-full text-purple-100 dark:text-purple-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor"><path d="M0 20 L0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10 L100 20 Z" opacity="0.5"></path><path d="M0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10" fill="none" stroke="currentColor" stroke-width="1.5" class="text-purple-400 dark:text-purple-500"></path></svg>
          </div>
        </div>
        <!-- Izin -->
        <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
              Izin <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-blue-500 dark:text-blue-400">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $excusedCount }}</p>
            @if ($stats['excused']['is_up'])
              <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['excused']['trend'] }}
              </span>
            @elseif ($stats['excused']['is_down'])
              <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['excused']['trend'] }}
              </span>
            @else
              <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
              </span>
            @endif
          </div>
          <div class="mt-3">
            <svg class="h-6 w-full text-blue-100 dark:text-blue-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor"><path d="M0 20 L0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10 L100 20 Z" opacity="0.5"></path><path d="M0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10" fill="none" stroke="currentColor" stroke-width="1.5" class="text-blue-400 dark:text-blue-500"></path></svg>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Ketidakhadiran Container -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50/50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <h3 class="font-medium text-gray-700 dark:text-gray-300">Ketidakhadiran</h3>
      </div>
      <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
        <!-- Sakit -->
        <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
              Sakit <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-yellow-500 dark:text-yellow-400">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $sickCount }}</p>
            @if ($stats['sick']['is_up'])
              <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['sick']['trend'] }}
              </span>
            @elseif ($stats['sick']['is_down'])
              <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['sick']['trend'] }}
              </span>
            @else
              <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
              </span>
            @endif
          </div>
          <div class="mt-3">
            <svg class="h-6 w-full text-yellow-100 dark:text-yellow-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor"><path d="M0 20 L0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10 L100 20 Z" opacity="0.5"></path><path d="M0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10" fill="none" stroke="currentColor" stroke-width="1.5" class="text-yellow-400 dark:text-yellow-500"></path></svg>
          </div>
        </div>
        <!-- Cuti -->
        <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
              Cuti <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-teal-500 dark:text-teal-400">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $leaveCount }}</p>
            @if ($stats['leave']['is_up'])
              <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['leave']['trend'] }}
              </span>
            @elseif ($stats['leave']['is_down'])
              <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['leave']['trend'] }}
              </span>
            @else
              <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
              </span>
            @endif
          </div>
          <div class="mt-3">
            <svg class="h-6 w-full text-teal-100 dark:text-teal-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor"><path d="M0 20 L0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10 L100 20 Z" opacity="0.5"></path><path d="M0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10" fill="none" stroke="currentColor" stroke-width="1.5" class="text-teal-400 dark:text-teal-500"></path></svg>
          </div>
        </div>
        <!-- Absen -->
        <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
              Absen <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-red-500 dark:text-red-400">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $absentCount }}</p>
            @if ($stats['absent']['is_up'])
              <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['absent']['trend'] }}
              </span>
            @elseif ($stats['absent']['is_down'])
              <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['absent']['trend'] }}
              </span>
            @else
              <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
              </span>
            @endif
          </div>
          <div class="mt-3">
            <svg class="h-6 w-full text-red-100 dark:text-red-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor"><path d="M0 20 L0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10 L100 20 Z" opacity="0.5"></path><path d="M0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10" fill="none" stroke="currentColor" stroke-width="1.5" class="text-red-400 dark:text-red-500"></path></svg>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="mb-4 overflow-x-scroll">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Name') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('NIP') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Division') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Job Title') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Shift') }}
          </th>
          <th scope="col"
            class="text-nowrap border border-gray-300 px-1 py-3 text-center text-xs font-medium text-gray-500 dark:border-gray-600 dark:text-gray-300">
            Status
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Time In') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Time Out') }}
          </th>
          <th scope="col" class="relative">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @php
          $class = 'px-4 py-3 text-sm font-medium text-gray-900 dark:text-white';
        @endphp
        @foreach ($employees as $employee)
          @php
            $attendance = $employee->attendance;
            $timeIn = $attendance ? $attendance?->time_in?->format('H:i:s') : null;
            $timeOut = $attendance ? $attendance?->time_out?->format('H:i:s') : null;
            $isSunday = $date->isSunday();
            $status = ($attendance ?? [
                'status' => $isSunday || !$date->isPast() ? '-' : 'absent',
            ])['status'];
            switch ($status) {
                case 'present':
                    $shortStatus = 'H';
                    $bgColor =
                        'bg-green-200 dark:bg-green-800 hover:bg-green-300 dark:hover:bg-green-700 border border-green-300 dark:border-green-600';
                    break;
                case 'late':
                    $shortStatus = 'T';
                    $bgColor =
                        'bg-orange-200 dark:bg-orange-800 hover:bg-orange-300 dark:hover:bg-orange-700 border border-orange-300 dark:border-orange-600';
                    break;
                case 'excused':
                    $shortStatus = 'I';
                    $bgColor =
                        'bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 dark:hover:bg-blue-700 border border-blue-300 dark:border-blue-600';
                    break;
                case 'sick':
                    $shortStatus = 'S';
                    $bgColor =
                        'bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700 border border-yellow-300 dark:border-yellow-600';
                    break;
                case 'absent':
                    $shortStatus = 'A';
                    $bgColor =
                        'bg-red-200 dark:bg-red-800 hover:bg-red-300 dark:hover:bg-red-700 border border-red-300 dark:border-red-600';
                    break;
                default:
                    $shortStatus = '-';
                    $bgColor = 'hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600';
                    break;
            }
          @endphp
          <tr wire:key="{{ $employee->id }}" class="group">
            {{-- Detail karyawan --}}
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->name }}
            </td>
            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->nip }}
            </td>
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->division?->name ?? '-' }}
            </td>
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->jobTitle?->name ?? '-' }}
            </td>
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $attendance->shift?->name ?? '-' }}
            </td>

            {{-- Absensi --}}
            <td
              class="{{ $bgColor }} text-nowrap px-1 py-3 text-center text-sm font-medium text-gray-900 dark:text-white">
              {{ __($status) }}
            </td>

            {{-- Waktu masuk/keluar --}}
            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $timeIn ?? '-' }}
            </td>
            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $timeOut ?? '-' }}
            </td>

            {{-- Action --}}
            <td
              class="cursor-pointer text-center text-sm font-medium text-gray-900 group-hover:bg-gray-100 dark:text-white dark:group-hover:bg-gray-700">
              <div class="flex items-center justify-center gap-3">
                @if ($attendance && ($attendance->attachment || $attendance->note || $attendance->lat_lng))
                  <x-button type="button" wire:click="show({{ $attendance->id }})"
                    onclick="setLocation({{ $attendance->latitude ?? 0 }}, {{ $attendance->longitude ?? 0 }})">
                    {{ __('Detail') }}
                  </x-button>
                @else
                  -
                @endif
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {{ $employees->links() }}

  <x-attendance-detail-modal :current-attendance="$currentAttendance" />
  @stack('attendance-detail-scripts')
</div>
