@props([
    'stats',
    'presentCount',
    'wfhCount',
    'excusedCount',
    'sickCount',
    'leaveCount',
    'absentCount',
    'sparklines'
])

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
          <svg class="h-6 w-full text-green-100 dark:text-green-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="{{ $sparklines['present']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
            <path d="{{ $sparklines['present']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-green-400 dark:text-green-500"></path>
          </svg>
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
          <svg class="h-6 w-full text-purple-100 dark:text-purple-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="{{ $sparklines['wfh']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
            <path d="{{ $sparklines['wfh']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-purple-400 dark:text-purple-500"></path>
          </svg>
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
          <svg class="h-6 w-full text-blue-100 dark:text-blue-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="{{ $sparklines['excused']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
            <path d="{{ $sparklines['excused']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-blue-400 dark:text-blue-500"></path>
          </svg>
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
          <svg class="h-6 w-full text-yellow-100 dark:text-yellow-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="{{ $sparklines['sick']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
            <path d="{{ $sparklines['sick']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-yellow-400 dark:text-yellow-500"></path>
          </svg>
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
          <svg class="h-6 w-full text-teal-100 dark:text-teal-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="{{ $sparklines['leave']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
            <path d="{{ $sparklines['leave']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-teal-400 dark:text-teal-500"></path>
          </svg>
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
          <svg class="h-6 w-full text-red-100 dark:text-red-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="{{ $sparklines['absent']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
            <path d="{{ $sparklines['absent']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-red-400 dark:text-red-500"></path>
          </svg>
        </div>
      </div>
    </div>
  </div>
</div>
