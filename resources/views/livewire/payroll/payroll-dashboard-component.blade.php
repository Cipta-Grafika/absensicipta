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

    <!-- KARTU RINGKASAN & APPROVAL SYIRKAH (SHORTCUT KHUSUS OWNER / PAYROLL) -->
    <div class="mb-6 overflow-hidden rounded-none sm:rounded-2xl border-t border-b sm:border border-emerald-200/80 bg-white/70 backdrop-blur-xl shadow-2xl shadow-black/5 dark:border-emerald-900/40 dark:bg-gray-900/70">
      <!-- Header Card -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-emerald-200/80 bg-gradient-to-r from-emerald-50/70 via-teal-50/40 to-transparent px-5 py-4 dark:border-emerald-900/40 dark:from-emerald-950/40 dark:via-teal-950/20">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-500/20">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="font-bold text-gray-900 dark:text-gray-100 text-base sm:text-lg">
                Ringkasan & Persetujuan Syirkah
              </h3>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-300/50 dark:border-emerald-800/60">
                Payroll {{ $currentMonth }}
              </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              Shortcut persetujuan mutasi simpanan syirkah karyawan dari generate payroll
            </p>
          </div>
        </div>

        <!-- Action Buttons on Header -->
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto justify-start sm:justify-end">
          <a href="{{ route('payroll.saving-transactions') }}"
             class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-xl transition-colors">
            <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            <span>Buka Mutasi</span>
          </a>

          @if ($syirkahSummary['pending_count'] > 0)
            <button type="button"
                    wire:click="openBulkApproveModal('current_month')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 rounded-xl shadow-lg shadow-emerald-600/25 transition-all duration-150 transform active:scale-95 cursor-pointer">
              <svg class="h-4 w-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Setujui Semua ({{ $syirkahSummary['pending_count'] }})</span>
            </button>
          @elseif ($syirkahSummary['total_count'] > 0 && $syirkahSummary['approved_count'] === $syirkahSummary['total_count'])
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-950/70 border border-emerald-300/50 dark:border-emerald-800/60 rounded-xl">
              <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Semua Mutasi Telah Disetujui
            </span>
          @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-500 bg-gray-100 dark:text-gray-400 dark:bg-gray-800 rounded-xl">
              Belum Ada Mutasi Payroll
            </span>
          @endif
        </div>
      </div>

      <!-- Card Grid Metrics -->
      <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-800 sm:grid-cols-2 lg:grid-cols-4 sm:divide-y-0 sm:divide-x">
        
        <!-- 1. Menunggu Persetujuan (Pending) -->
        <div class="p-5 transition-colors hover:bg-emerald-50/40 dark:hover:bg-gray-800/40 relative group">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold tracking-wider text-amber-700 dark:text-amber-400 uppercase">
              Perlu Persetujuan
            </span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-400">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </span>
          </div>
          <div class="mt-2">
            <div class="flex items-baseline gap-2">
              <span class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">
                {{ $syirkahSummary['pending_count'] }}
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">Mutasi Pending</span>
            </div>
            <p class="mt-1 text-sm font-bold text-amber-600 dark:text-amber-400">
              Rp {{ number_format($syirkahSummary['pending_total'], 0, ',', '.') }}
            </p>
          </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800">
            <span>Wajib: Rp {{ number_format($syirkahSummary['pending_mandatory'], 0, ',', '.') }}</span>
            <span>Sukarela: Rp {{ number_format($syirkahSummary['pending_secondary'], 0, ',', '.') }}</span>
          </div>
        </div>

        <!-- 2. Potongan Simpanan Wajib -->
        <div class="p-5 transition-colors hover:bg-emerald-50/40 dark:hover:bg-gray-800/40">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase">
              Simpanan Wajib (Pokok)
            </span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </span>
          </div>
          <div class="mt-2">
            <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
              Rp {{ number_format($syirkahSummary['total_mandatory'], 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Total potongan pokok periode {{ $currentMonth }}
            </p>
          </div>
          <div class="mt-3 flex items-center gap-1.5 text-[11px] text-emerald-600 dark:text-emerald-400 font-medium pt-2 border-t border-gray-100 dark:border-gray-800">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ $syirkahSummary['total_count'] }} Transaksi terdata</span>
          </div>
        </div>

        <!-- 3. Potongan Simpanan Sukarela -->
        <div class="p-5 transition-colors hover:bg-emerald-50/40 dark:hover:bg-gray-800/40">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase">
              Simpanan Sukarela
            </span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-950/70 dark:text-teal-400">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </span>
          </div>
          <div class="mt-2">
            <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
              Rp {{ number_format($syirkahSummary['total_secondary'], 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Total sukarela tambahan periode {{ $currentMonth }}
            </p>
          </div>
          <div class="mt-3 flex items-center gap-1.5 text-[11px] text-teal-600 dark:text-teal-400 font-medium pt-2 border-t border-gray-100 dark:border-gray-800">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Masuk ke saldo simpanan sukarela</span>
          </div>
        </div>

        <!-- 4. Grand Total Syirkah -->
        <div class="p-5 transition-colors hover:bg-emerald-50/40 dark:hover:bg-gray-800/40 bg-emerald-500/5 dark:bg-emerald-950/20">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold tracking-wider text-emerald-800 dark:text-emerald-300 uppercase">
              Total Potongan Syirkah
            </span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-xs">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </span>
          </div>
          <div class="mt-2">
            <p class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">
              Rp {{ number_format($syirkahSummary['total_amount'], 0, ',', '.') }}
            </p>
            <div class="mt-1 flex items-center gap-2 text-xs">
              <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ $syirkahSummary['approved_count'] }} Disetujui</span>
              <span class="text-gray-400">&bull;</span>
              <span class="font-bold text-amber-600 dark:text-amber-400">{{ $syirkahSummary['pending_count'] }} Pending</span>
            </div>
          </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
            <span>Total: {{ $syirkahSummary['total_count'] }} slip</span>
            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">
              {{ $syirkahSummary['total_count'] > 0 ? round(($syirkahSummary['approved_count'] / $syirkahSummary['total_count']) * 100) : 0 }}% Disetujui
            </span>
          </div>
        </div>

      </div>

      @if ($allPendingSummary['count'] > $syirkahSummary['pending_count'])
      <!-- Notice jika ada transaksi pending di luar bulan ini -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 px-5 py-3 bg-amber-500/10 border-t border-amber-300/40 dark:border-amber-900/40 text-xs text-amber-800 dark:text-amber-300">
        <div class="flex items-center gap-2">
          <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>
            Terdapat <strong>{{ $allPendingSummary['count'] - $syirkahSummary['pending_count'] }} mutasi syirkah pending lainnya</strong> di luar periode ini (Total: Rp {{ number_format($allPendingSummary['total_amount'] - $syirkahSummary['pending_total'], 0, ',', '.') }}).
          </span>
        </div>
        <button type="button"
                wire:click="openBulkApproveModal('all_pending')"
                class="font-bold underline hover:text-amber-900 dark:hover:text-amber-200 cursor-pointer shrink-0">
          Setujui Semua Pending Sistem ({{ $allPendingSummary['count'] }}) &rarr;
        </button>
      </div>
      @endif
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

    <!-- DIALOG MODAL KONFIRMASI APPROVAL BULK SYIRKAH -->
    <x-dialog-modal wire:model.live="bulkApproveModalOpen" maxWidth="md">
      <x-slot name="title">
        <div class="flex items-center gap-2.5">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <span class="font-bold text-gray-900 dark:text-gray-100">Konfirmasi Persetujuan Massal Syirkah</span>
        </div>
      </x-slot>

      <x-slot name="content">
        @php
          $targetCount = $bulkApproveScope === 'all_pending' ? $allPendingSummary['count'] : $syirkahSummary['pending_count'];
          $targetMandatory = $bulkApproveScope === 'all_pending' ? $allPendingSummary['total_mandatory'] : $syirkahSummary['pending_mandatory'];
          $targetSecondary = $bulkApproveScope === 'all_pending' ? $allPendingSummary['total_secondary'] : $syirkahSummary['pending_secondary'];
          $targetTotal = $bulkApproveScope === 'all_pending' ? $allPendingSummary['total_amount'] : $syirkahSummary['pending_total'];
        @endphp

        <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
          Apakah Anda yakin ingin menyetujui mutasi simpanan syirkah berikut secara massal?
        </p>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/60 p-4 space-y-2.5">
          <div class="flex items-center justify-between text-xs">
            <span class="text-gray-500 dark:text-gray-400">Cakupan Persetujuan:</span>
            <span class="font-bold text-gray-800 dark:text-gray-200">
              {{ $bulkApproveScope === 'all_pending' ? 'Seluruh Mutasi Pending di Sistem' : 'Payroll Periode ' . $currentMonth }}
            </span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-gray-500 dark:text-gray-400">Jumlah Transaksi:</span>
            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $targetCount }} Transaksi</span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-gray-500 dark:text-gray-400">Simpanan Wajib:</span>
            <span class="font-semibold text-gray-800 dark:text-gray-200">Rp {{ number_format($targetMandatory, 0, ',', '.') }}</span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-gray-500 dark:text-gray-400">Simpanan Sukarela:</span>
            <span class="font-semibold text-gray-800 dark:text-gray-200">Rp {{ number_format($targetSecondary, 0, ',', '.') }}</span>
          </div>
          <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-sm">
            <span class="font-bold text-gray-900 dark:text-white">Total Nominal Disetujui:</span>
            <span class="font-extrabold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($targetTotal, 0, ',', '.') }}</span>
          </div>
        </div>

        <div class="mt-3 flex items-start gap-2 text-[11px] text-gray-500 dark:text-gray-400">
          <svg class="h-4 w-4 shrink-0 text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>
            Status mutasi akan diperbarui menjadi <strong>Approved</strong> dan saldo simpanan karyawan akan dihitung ulang otomatis.
          </span>
        </div>
      </x-slot>

      <x-slot name="footer">
        <div class="flex items-center justify-end gap-3">
          <x-secondary-button wire:click="closeBulkApproveModal" wire:loading.attr="disabled">
            Batal
          </x-secondary-button>

          <button type="button"
                  wire:click="confirmBulkApprove"
                  wire:loading.attr="disabled"
                  class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 rounded-xl shadow-lg shadow-emerald-600/25 transition-all duration-150 transform active:scale-95 disabled:opacity-50 cursor-pointer">
            <svg wire:loading wire:target="confirmBulkApprove" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span wire:loading.remove wire:target="confirmBulkApprove">Ya, Setujui Sekarang</span>
            <span wire:loading wire:target="confirmBulkApprove">Memproses...</span>
          </button>
        </div>
      </x-slot>
    </x-dialog-modal>
  </div>
</div>
