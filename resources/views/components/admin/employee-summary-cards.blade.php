@props([
    'activeSuspendCount' => 0,
    'suspendCount' => 0,
    'resignCount' => 0,
    'firedCount' => 0,
])

<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-1">
  <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50/50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
      <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
      <h3 class="font-medium text-gray-700 dark:text-gray-300">Statistik Karyawan</h3>
    </div>
    <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:grid-cols-4 sm:divide-x sm:divide-y-0">
      
      <!-- Total Karyawan (Active + Suspend) -->
      <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
            Total Aktif & Suspend <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <div class="text-green-500 dark:text-green-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $activeSuspendCount }}</p>
          <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
            <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
          </span>
        </div>
        <div class="mt-3">
          <svg class="h-6 w-full text-green-100 dark:text-green-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="M0 20 L0 15 L100 15 L100 20 Z" opacity="0.5"></path>
            <path d="M0 15 L100 15" fill="none" stroke="currentColor" stroke-width="1.5" class="text-green-400 dark:text-green-500"></path>
          </svg>
        </div>
      </div>

      <!-- Suspend -->
      <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
            Diskors (Suspend) <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <div class="text-yellow-500 dark:text-yellow-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $suspendCount }}</p>
          <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
            <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
          </span>
        </div>
        <div class="mt-3">
          <svg class="h-6 w-full text-yellow-100 dark:text-yellow-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="M0 20 L0 15 L100 15 L100 20 Z" opacity="0.5"></path>
            <path d="M0 15 L100 15" fill="none" stroke="currentColor" stroke-width="1.5" class="text-yellow-400 dark:text-yellow-500"></path>
          </svg>
        </div>
      </div>

      <!-- Resign -->
      <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
            Resign <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <div class="text-blue-500 dark:text-blue-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $resignCount }}</p>
          <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
            <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
          </span>
        </div>
        <div class="mt-3">
          <svg class="h-6 w-full text-blue-100 dark:text-blue-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="M0 20 L0 15 L100 15 L100 20 Z" opacity="0.5"></path>
            <path d="M0 15 L100 15" fill="none" stroke="currentColor" stroke-width="1.5" class="text-blue-400 dark:text-blue-500"></path>
          </svg>
        </div>
      </div>

      <!-- Fired -->
      <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
            Dipecat (Fired) <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <div class="text-red-500 dark:text-red-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $firedCount }}</p>
          <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
            <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
          </span>
        </div>
        <div class="mt-3">
          <svg class="h-6 w-full text-red-100 dark:text-red-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
            <path d="M0 20 L0 15 L100 15 L100 20 Z" opacity="0.5"></path>
            <path d="M0 15 L100 15" fill="none" stroke="currentColor" stroke-width="1.5" class="text-red-400 dark:text-red-500"></path>
          </svg>
        </div>
      </div>

    </div>
  </div>
</div>
