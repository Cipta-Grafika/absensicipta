<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Dasbor Payroll &bull; <span class="text-sky-600 dark:text-sky-400">{{ $currentMonth }}</span>
    </h2>
    <div class="flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="pt-3.5 pb-6 sm:py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="w-full sm:px-6 lg:px-8 space-y-6">
    
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Dashboard</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('month', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="month_filter" value="Pilih Bulan Periode" class="mb-1"></x-label>
            <x-input type="month" id="month_filter" class="w-full block" wire:model.live="month" />
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <div class="mb-6 grid grid-cols-1 gap-6">
      <div class="overflow-hidden rounded-none sm:rounded-2xl border-t border-b sm:border border-sky-200/80 bg-white/70 backdrop-blur-xl shadow-2xl shadow-black/5 dark:border-gray-800/80 dark:bg-gray-900/70">
        <div class="flex items-center gap-2 border-b border-sky-200/80 bg-sky-50/50 px-4 py-3 dark:border-gray-800/80 dark:bg-gray-800/50">
          <svg class="h-5 w-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <h3 class="font-bold text-gray-800 dark:text-gray-200">Ringkasan Payroll</h3>
        </div>
        <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
          
          <!-- Total Karyawan -->
          <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
                Total Karyawan <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div class="text-blue-500 dark:text-blue-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalEmployees }}</p>
              @if ($stats['employees']['is_up'])
                <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['employees']['trend'] }}
                </span>
              @elseif ($stats['employees']['is_down'])
                <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['employees']['trend'] }}
                </span>
              @else
                <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0
                </span>
              @endif
            </div>
            <div class="mt-3">
              <svg class="h-6 w-full text-blue-100 dark:text-blue-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
                <path d="{{ $sparklines['employees']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
                <path d="{{ $sparklines['employees']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-blue-400 dark:text-blue-500"></path>
              </svg>
            </div>
          </div>

          <!-- Total Gaji Dibayar -->
          <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
                Total Gaji Dibayar <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div class="text-green-500 dark:text-green-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"></path></svg>
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($totalPaidOut, 0, ',', '.') }}</p>
              @if ($stats['paid']['is_up'])
                <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['paid']['trend'] }}
                </span>
              @elseif ($stats['paid']['is_down'])
                <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['paid']['trend'] }}
                </span>
              @else
                <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
                </span>
              @endif
            </div>
            <div class="mt-1 flex items-center text-xs">
              <span class="font-medium text-green-600 dark:text-green-400">{{ $paidCount }}</span>
              <span class="ml-1 text-gray-500 dark:text-gray-400">Slip Gaji (Paid)</span>
            </div>
            <div class="mt-3">
              <svg class="h-6 w-full text-green-100 dark:text-green-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
                <path d="{{ $sparklines['paid']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
                <path d="{{ $sparklines['paid']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-green-400 dark:text-green-500"></path>
              </svg>
            </div>
          </div>

          <!-- Estimasi Draft Gaji -->
          <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
                Estimasi Draft Gaji <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div class="text-yellow-500 dark:text-yellow-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path></svg>
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($totalDraft, 0, ',', '.') }}</p>
              @if ($stats['draft']['is_up'])
                <span class="flex items-center text-xs font-medium text-red-600 dark:text-red-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>{{ $stats['draft']['trend'] }}
                </span>
              @elseif ($stats['draft']['is_down'])
                <span class="flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>{{ $stats['draft']['trend'] }}
                </span>
              @else
                <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                  <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>0%
                </span>
              @endif
            </div>
            <div class="mt-1 flex items-center text-xs">
              <span class="font-medium text-yellow-600 dark:text-yellow-400">{{ $draftCount }}</span>
              <span class="ml-1 text-gray-500 dark:text-gray-400">Slip Gaji (Draft)</span>
            </div>
            <div class="mt-3">
              <svg class="h-6 w-full text-yellow-100 dark:text-yellow-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
                <path d="{{ $sparklines['draft']['fill'] ?? 'M0 20 L0 15 L100 15 L100 20 Z' }}" opacity="0.5"></path>
                <path d="{{ $sparklines['draft']['stroke'] ?? 'M0 15 L100 15' }}" fill="none" stroke="currentColor" stroke-width="1.5" class="text-yellow-400 dark:text-yellow-500"></path>
              </svg>
            </div>
          </div>
          
        </div>
      </div>
    </div>

    <!-- Info Banner -->
    <div class="mt-6 overflow-hidden rounded-none sm:rounded-2xl border-t border-b sm:border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Alur Kerja Sistem Penggajian (Payroll)</h3>
        <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-3 text-sm text-gray-600 dark:text-gray-400">
          
          <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 font-bold mb-3">1</div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-200">Atur Master Gaji</h4>
            <p class="mt-1">Pilih tipe gaji harian/bulanan dan atur besaran gaji pokok serta tunjangan untuk setiap karyawan di menu Master Gaji.</p>
          </div>

          <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 font-bold mb-3">2</div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-200">Generate Payroll</h4>
            <p class="mt-1">Tentukan rentang tanggal cut-off absensi. Sistem akan menarik data kehadiran, lembur, dan ganti jam secara otomatis.</p>
          </div>

          <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 font-bold mb-3">3</div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-200">Review & Paid</h4>
            <p class="mt-1">Tinjau slip gaji yang berstatus Draft di Riwayat Gaji, ubah status ke Paid agar karyawan bisa melihat dan mengunduh slip mereka.</p>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
