<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Riwayat Syirkah') }}
    </h2>
    <div class="flex items-center gap-2">
      <x-secondary-button href="{{ route('home') }}">
        <x-heroicon-o-chevron-left class="mr-1.5 h-4 w-4" />
        Kembali
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="flex-grow flex flex-col pt-3 pb-24 sm:py-8">
  <div class="mx-auto w-full max-w-7xl px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

    <!-- 1. UNIFIED DIGITAL WALLET HERO CARD -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-700 to-cyan-800 p-5 sm:p-6 text-white shadow-xl shadow-emerald-950/15 border border-white/10">
      <div class="absolute -right-8 -bottom-8 h-40 w-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
      <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-teal-300/20 blur-xl pointer-events-none"></div>
      
      <div class="relative z-10">
        <!-- Card Top Bar: Title & Action "Ajukan" Button -->
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 backdrop-blur-md">
              <x-heroicon-o-building-library class="h-4 w-4 text-white" />
            </div>
            <span class="text-xs sm:text-sm font-semibold tracking-wide text-emerald-100">
              Tabungan Syirkah Karyawan
            </span>
          </div>

          <!-- ACTION BUTTON "AJUKAN" PENARIKAN (Replacing previous static badge) -->
          <button 
            type="button"
            wire:click="openWithdrawalModal"
            class="inline-flex items-center gap-1.5 rounded-full bg-white/90 hover:bg-white text-emerald-900 font-bold px-3.5 py-1.5 text-xs shadow-md shadow-emerald-950/30 hover:scale-[1.03] active:scale-[0.98] transition-all duration-150 border border-white/40 cursor-pointer"
            title="Ajukan Penarikan Saldo Syirkah"
          >
            <x-heroicon-s-arrow-up-tray class="h-3.5 w-3.5 text-emerald-700" />
            <span>Ajukan</span>
          </button>
        </div>

        <!-- Card Main Balance -->
        <div class="mt-4 sm:mt-5">
          <p class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-emerald-200/90">
            Total Saldo Syirkah
          </p>
          <h3 class="mt-1 text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white">
            Rp {{ number_format($totalSaldo, 0, ',', '.') }}
          </h3>
        </div>

        <!-- Sub-Balances Grid (Wajib vs Sukarela) -->
        <div class="mt-4 pt-3.5 border-t border-white/15 grid grid-cols-2 sm:grid-cols-3 gap-2.5 sm:gap-4">
          <!-- Syirkah Wajib -->
          <div class="rounded-xl bg-white/10 backdrop-blur-md p-2.5 sm:p-3 border border-white/10">
            <div class="flex items-center gap-1.5 text-[11px] text-emerald-200">
              <x-heroicon-o-lock-closed class="h-3.5 w-3.5 text-emerald-300" />
              <span>Syirkah Wajib</span>
            </div>
            <p class="mt-1 text-sm sm:text-base font-extrabold text-white">
              Rp {{ number_format($saldoWajib, 0, ',', '.') }}
            </p>
          </div>

          <!-- Syirkah Sukarela -->
          <div class="rounded-xl bg-white/10 backdrop-blur-md p-2.5 sm:p-3 border border-white/10">
            <div class="flex items-center gap-1.5 text-[11px] text-emerald-200">
              <x-heroicon-o-sparkles class="h-3.5 w-3.5 text-amber-300" />
              <span>Sukarela (SSR)</span>
            </div>
            <p class="mt-1 text-sm sm:text-base font-extrabold text-white">
              Rp {{ number_format($saldoSukarela, 0, ',', '.') }}
            </p>
          </div>

          <!-- Total Mutasi -->
          <div class="col-span-2 sm:col-span-1 rounded-xl bg-white/10 backdrop-blur-md p-2.5 sm:p-3 border border-white/10 flex sm:flex-col items-center sm:items-start justify-between sm:justify-center">
            <div class="flex items-center gap-1.5 text-[11px] text-emerald-200">
              <x-heroicon-o-receipt-percent class="h-3.5 w-3.5 text-cyan-300" />
              <span>Total Mutasi</span>
            </div>
            <p class="sm:mt-1 text-xs sm:text-sm font-bold text-white">
              {{ $totalTransactionsCount }} Transaksi
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. TOTAL CASH FLOW SUMMARY (KREDIT & DEBIT) -->
    <div class="grid grid-cols-2 gap-2.5 sm:gap-4">
      <div class="flex items-center gap-2.5 sm:gap-3.5 rounded-2xl bg-emerald-50/90 p-3 sm:p-4 border border-emerald-200/70 dark:bg-emerald-950/30 dark:border-emerald-800/40">
        <div class="flex h-9 w-9 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-xs shadow-emerald-500/30">
          <x-heroicon-o-arrow-down-left class="h-4 w-4 sm:h-5 sm:w-5" />
        </div>
        <div class="min-w-0">
          <p class="text-[10px] sm:text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wide truncate">
            Total Setoran (Masuk)
          </p>
          <p class="text-xs sm:text-base font-extrabold text-emerald-800 dark:text-emerald-200 truncate">
            + Rp {{ number_format($totalCreditAll, 0, ',', '.') }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2.5 sm:gap-3.5 rounded-2xl bg-rose-50/90 p-3 sm:p-4 border border-rose-200/70 dark:bg-rose-950/30 dark:border-rose-800/40">
        <div class="flex h-9 w-9 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-white shadow-xs shadow-rose-500/30">
          <x-heroicon-o-arrow-up-right class="h-4 w-4 sm:h-5 sm:w-5" />
        </div>
        <div class="min-w-0">
          <p class="text-[10px] sm:text-xs font-semibold text-rose-700 dark:text-rose-300 uppercase tracking-wide truncate">
            Total Penarikan (Keluar)
          </p>
          <p class="text-xs sm:text-base font-extrabold text-rose-800 dark:text-rose-200 truncate">
            - Rp {{ number_format($totalDebitAll, 0, ',', '.') }}
          </p>
        </div>
      </div>
    </div>

    <!-- 3. MUTASI LEDGER CARD -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-xs border border-gray-100 dark:bg-gray-800 dark:border-gray-700/60">
      
      <!-- Card Header & Filter Bar -->
      <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-700 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
          <div>
            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <x-heroicon-o-queue-list class="h-4 w-4 sm:h-5 sm:w-5 text-emerald-600 dark:text-emerald-400" />
              Mutasi Rekening Syirkah (Debit / Kredit)
            </h3>
            <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400">
              Menampilkan riwayat mutasi yang telah disetujui (Approved)
            </p>
          </div>
        </div>

        <!-- Filter Controls -->
        <div class="pt-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
          <!-- Search Input -->
          <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
            </div>
            <x-input type="text" class="block w-full pl-9 text-xs sm:text-sm !py-2" wire:model.live.debounce.300ms="search" placeholder="Cari keterangan atau referensi..." />
          </div>

          <!-- Filters Row -->
          <div class="flex items-center gap-2">
            <!-- Month Selector -->
            <div class="flex-1 sm:w-44">
              <x-input type="month" class="w-full text-xs sm:text-sm !py-2" wire:model.live="month" title="Filter Bulan" />
            </div>

            <!-- Type Selector -->
            <div class="flex-1 sm:w-40">
              <x-select class="w-full text-xs sm:text-sm !py-2" wire:model.live="type">
                <option value="">Semua Mutasi</option>
                <option value="deposit">Setoran (Kredit +)</option>
                <option value="withdrawal">Penarikan (Debit -)</option>
              </x-select>
            </div>

            @if($search || $month || $type)
              <button 
                type="button" 
                wire:click="resetFilters" 
                class="px-2.5 py-2 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 transition"
                title="Reset Filter"
              >
                Reset
              </button>
            @endif
          </div>
        </div>
      </div>

      <!-- Desktop & Tablet Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
          <thead class="bg-gray-50/80 text-[11px] uppercase tracking-wider text-gray-500 dark:bg-gray-700/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
            <tr>
              <th scope="col" class="py-3.5 px-4 font-bold">Tanggal</th>
              <th scope="col" class="py-3.5 px-4 font-bold">Keterangan</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-right">Syirkah Wajib</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-right">Sukarela (SSR)</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-right">Nominal Mutasi</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-right">Saldo Akhir</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
            @forelse($transactions as $tx)
              @php
                $isDeposit = $tx->transaction_type === 'deposit';
                $totalNominal = (float) ($tx->mandatory_amount + $tx->secondary_amount);
                $totalRunningBalance = (float) ($tx->balance_mandatory + $tx->balance_secondary);
              @endphp
              <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-bold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($tx->created_at)->translatedFormat('d M Y') }}
                  </div>
                  <div class="text-[11px] text-gray-400">
                    {{ \Carbon\Carbon::parse($tx->created_at)->format('H:i') }} WIB
                  </div>
                </td>

                <td class="py-3 px-4">
                  <div class="font-medium text-gray-800 dark:text-gray-200">
                    {{ $tx->description ?: ($isDeposit ? 'Setoran Syirkah' : 'Penarikan Syirkah') }}
                  </div>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold {{ $isDeposit ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' }}">
                      {{ $isDeposit ? 'Setoran (Kredit)' : 'Penarikan (Debit)' }}
                    </span>
                    @if($tx->reference_type)
                      <span class="text-[10px] text-gray-400 uppercase">
                        ref: {{ $tx->reference_type }}
                      </span>
                    @endif
                  </div>
                </td>

                <td class="py-3 px-4 text-right whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                  Rp {{ number_format($tx->mandatory_amount, 0, ',', '.') }}
                </td>

                <td class="py-3 px-4 text-right whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                  Rp {{ number_format($tx->secondary_amount, 0, ',', '.') }}
                </td>

                <td class="py-3 px-4 text-right whitespace-nowrap">
                  <span class="font-extrabold text-sm {{ $isDeposit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $isDeposit ? '+' : '-' }} Rp {{ number_format($totalNominal, 0, ',', '.') }}
                  </span>
                </td>

                <td class="py-3 px-4 text-right whitespace-nowrap font-bold text-gray-900 dark:text-white">
                  Rp {{ number_format($totalRunningBalance, 0, ',', '.') }}
                </td>

                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <button
                    type="button"
                    wire:click="openDetailModal('{{ $tx->id }}')"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-400 transition"
                    title="Lihat Detail Transaksi"
                  >
                    <x-heroicon-o-eye class="h-4 w-4" />
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="py-12 text-center text-gray-400 dark:text-gray-500">
                  <x-heroicon-o-banknotes class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" />
                  <p class="text-sm font-bold text-gray-600 dark:text-gray-400">Belum Ada Riwayat Mutasi Syirkah</p>
                  <p class="text-xs mt-1 text-gray-400 dark:text-gray-500">Data mutasi yang disetujui akan tampil di sini.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Mobile List View -->
      <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700/60">
        @forelse($transactions as $tx)
          @php
            $isDeposit = $tx->transaction_type === 'deposit';
            $totalNominal = (float) ($tx->mandatory_amount + $tx->secondary_amount);
            $totalRunningBalance = (float) ($tx->balance_mandatory + $tx->balance_secondary);
          @endphp
          <div 
            wire:click="openDetailModal('{{ $tx->id }}')"
            class="p-3.5 hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition active:bg-gray-100 dark:active:bg-gray-700 cursor-pointer"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isDeposit ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200/60 dark:border-rose-800/40' }}">
                  @if($isDeposit)
                    <x-heroicon-o-arrow-down-left class="h-4 w-4" />
                  @else
                    <x-heroicon-o-arrow-up-right class="h-4 w-4" />
                  @endif
                </div>
                <div class="min-w-0">
                  <h4 class="text-xs font-bold text-gray-900 dark:text-white line-clamp-1">
                    {{ $tx->description ?: ($isDeposit ? 'Setoran Syirkah' : 'Penarikan Syirkah') }}
                  </h4>
                  <p class="text-[11px] text-gray-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($tx->created_at)->translatedFormat('d M Y, H:i') }} WIB
                  </p>
                </div>
              </div>

              <div class="text-right shrink-0">
                <span class="text-xs sm:text-sm font-extrabold {{ $isDeposit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                  {{ $isDeposit ? '+' : '-' }} Rp {{ number_format($totalNominal, 0, ',', '.') }}
                </span>
                <div class="text-[10px] text-gray-400 font-semibold mt-0.5">
                  Saldo: Rp {{ number_format($totalRunningBalance, 0, ',', '.') }}
                </div>
              </div>
            </div>

            <div class="mt-2.5 flex items-center justify-between text-[11px] pt-2 border-t border-gray-50 dark:border-gray-700/40 text-gray-500 dark:text-gray-400">
              <div class="flex items-center gap-1.5 truncate">
                <span>W: <strong class="text-gray-700 dark:text-gray-300">Rp {{ number_format($tx->mandatory_amount, 0, ',', '.') }}</strong></span>
                <span>•</span>
                <span>S: <strong class="text-gray-700 dark:text-gray-300">Rp {{ number_format($tx->secondary_amount, 0, ',', '.') }}</strong></span>
              </div>
              <span class="inline-flex items-center gap-0.5 font-semibold text-sky-600 dark:text-sky-400 shrink-0">
                Detail
                <x-heroicon-o-chevron-right class="h-3 w-3" />
              </span>
            </div>
          </div>
        @empty
          <div class="p-8 text-center text-gray-400 dark:text-gray-500">
            <x-heroicon-o-banknotes class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" />
            <p class="text-xs font-bold text-gray-600 dark:text-gray-400">Belum Ada Riwayat Mutasi Syirkah</p>
            <p class="text-[11px] mt-1 text-gray-400 dark:text-gray-500">Data mutasi yang disetujui akan tampil di sini.</p>
          </div>
        @endforelse
      </div>

      <!-- Pagination Mutasi -->
      @if($transactions->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
          {{ $transactions->links() }}
        </div>
      @endif
    </div>

    <!-- 4. SECTION RIWAYAT PENGAJUAN PENARIKAN SYIRKAH (Right below Mutasi Rekening Card) -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-xs border border-gray-100 dark:bg-gray-800 dark:border-gray-700/60">
      
      <!-- Card Header -->
      <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <x-heroicon-o-document-text class="h-4 w-4 sm:h-5 sm:w-5 text-teal-600 dark:text-teal-400" />
            Riwayat Pengajuan Penarikan Syirkah
          </h3>
          <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400">
            Pantau status verifikasi dan pembayaran pengajuan penarikan dana syirkah Anda
          </p>
        </div>

        <button 
          type="button" 
          wire:click="openWithdrawalModal"
          class="inline-flex items-center gap-1.5 self-start sm:self-auto px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition"
        >
          <x-heroicon-s-plus class="h-3.5 w-3.5" />
          <span>Buat Pengajuan Baru</span>
        </button>
      </div>

      <!-- Desktop & Tablet Withdrawals Table -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
          <thead class="bg-gray-50/80 text-[11px] uppercase tracking-wider text-gray-500 dark:bg-gray-700/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
            <tr>
              <th scope="col" class="py-3.5 px-4 font-bold">Tanggal Pengajuan</th>
              <th scope="col" class="py-3.5 px-4 font-bold">Tipe Penarikan</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-right">Syirkah Wajib</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-right">Sukarela (SSR)</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-right">Total Penarikan</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-center">Status</th>
              <th scope="col" class="py-3.5 px-4 font-bold text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
            @forelse($withdrawals as $wd)
              @php
                $badge = $wd->status_badge;
              @endphp
              <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-bold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($wd->created_at)->translatedFormat('d M Y') }}
                  </div>
                  <div class="text-[11px] text-gray-400">
                    {{ \Carbon\Carbon::parse($wd->created_at)->format('H:i') }} WIB
                  </div>
                </td>

                <td class="py-3 px-4">
                  <div class="font-semibold text-gray-800 dark:text-gray-200">
                    {{ $wd->withdrawal_type_label }}
                  </div>
                  @if($wd->reason)
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-1 italic mt-0.5">
                      "{{ $wd->reason }}"
                    </div>
                  @endif
                </td>

                <td class="py-3 px-4 text-right whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                  @if($wd->is_amount_adjusted && abs($wd->effective_mandatory_amount - $wd->mandatory_amount) > 0.001)
                    <span class="font-bold text-gray-900 dark:text-white">Rp {{ number_format($wd->effective_mandatory_amount, 0, ',', '.') }}</span>
                    <p class="text-[10px] text-gray-400 font-normal line-through">Rp {{ number_format($wd->mandatory_amount, 0, ',', '.') }}</p>
                  @else
                    Rp {{ number_format($wd->effective_mandatory_amount, 0, ',', '.') }}
                  @endif
                </td>

                <td class="py-3 px-4 text-right whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                  @if($wd->is_amount_adjusted && abs($wd->effective_secondary_amount - $wd->secondary_amount) > 0.001)
                    <span class="font-bold text-gray-900 dark:text-white">Rp {{ number_format($wd->effective_secondary_amount, 0, ',', '.') }}</span>
                    <p class="text-[10px] text-gray-400 font-normal line-through">Rp {{ number_format($wd->secondary_amount, 0, ',', '.') }}</p>
                  @else
                    Rp {{ number_format($wd->effective_secondary_amount, 0, ',', '.') }}
                  @endif
                </td>

                <td class="py-3 px-4 text-right whitespace-nowrap font-extrabold text-rose-600 dark:text-rose-400 text-sm">
                  @if($wd->is_amount_adjusted)
                    <span class="text-rose-600 dark:text-rose-400 block font-black text-sm">- Rp {{ number_format($wd->effective_total_amount, 0, ',', '.') }}</span>
                    <p class="text-[10px] text-gray-400 font-normal line-through mt-0.5">Rp {{ number_format($wd->total_amount, 0, ',', '.') }}</p>
                  @else
                    - Rp {{ number_format($wd->effective_total_amount, 0, ',', '.') }}
                  @endif
                </td>

                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['border'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $wd->status === 'pending' ? 'bg-amber-500 animate-ping' : ($wd->status === 'accepted' ? 'bg-blue-500' : ($wd->status === 'approved' ? 'bg-indigo-500 animate-pulse' : ($wd->status === 'paid' ? 'bg-emerald-500' : 'bg-rose-500'))) }}"></span>
                    {{ $badge['label'] }}
                  </span>
                  <p class="text-[10px] text-gray-400 mt-0.5">
                    {{ $badge['desc'] }}
                  </p>
                </td>

                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      type="button"
                      wire:click="openWithdrawalDetailModal('{{ $wd->id }}')"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-950/40 dark:hover:text-teal-400 transition"
                      title="Lihat Detail Pengajuan"
                    >
                      <x-heroicon-o-eye class="h-4 w-4" />
                    </button>

                    @if($wd->status === 'pending')
                      <button
                        type="button"
                        wire:click="cancelWithdrawal('{{ $wd->id }}')"
                        wire:confirm="Apakah Anda yakin ingin membatalkan pengajuan penarikan ini?"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                        title="Batalkan Pengajuan"
                      >
                        <x-heroicon-o-trash class="h-4 w-4" />
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="py-12 text-center text-gray-400 dark:text-gray-500">
                  <x-heroicon-o-document-plus class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" />
                  <p class="text-sm font-bold text-gray-600 dark:text-gray-400">Belum Ada Pengajuan Penarikan</p>
                  <p class="text-xs mt-1 text-gray-400 dark:text-gray-500">Klik tombol "Ajukan" di atas untuk mengajukan penarikan syirkah.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Mobile Withdrawals Card List -->
      <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700/60">
        @forelse($withdrawals as $wd)
          @php
            $badge = $wd->status_badge;
          @endphp
          <div class="p-3.5 hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition">
            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['border'] }}">
                  {{ $badge['label'] }}
                </span>
                <h4 class="text-xs font-bold text-gray-900 dark:text-white mt-1.5">
                  {{ $wd->withdrawal_type_label }}
                </h4>
                <p class="text-[11px] text-gray-400">
                  {{ \Carbon\Carbon::parse($wd->created_at)->translatedFormat('d M Y, H:i') }} WIB
                </p>
              </div>

              <div class="text-right">
                @if($wd->is_amount_adjusted)
                  <span class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400 block">
                    - Rp {{ number_format($wd->effective_total_amount, 0, ',', '.') }}
                  </span>
                  <span class="text-[10px] text-gray-400 line-through block mt-0.5">
                    Rp {{ number_format($wd->total_amount, 0, ',', '.') }}
                  </span>
                @else
                  <span class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400">
                    - Rp {{ number_format($wd->effective_total_amount, 0, ',', '.') }}
                  </span>
                @endif
                <p class="text-[10px] text-gray-400 mt-0.5">
                  {{ $badge['desc'] }}
                </p>
              </div>
            </div>

            @if($wd->reason)
              <p class="mt-2 text-[11px] text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg italic">
                "{{ $wd->reason }}"
              </p>
            @endif

            <div class="mt-2.5 flex items-center justify-between text-[11px] pt-2 border-t border-gray-50 dark:border-gray-700/40 text-gray-500 dark:text-gray-400">
              <div class="flex items-center gap-1.5 truncate">
                <span>W: <strong class="text-gray-700 dark:text-gray-300">
                  @if($wd->is_amount_adjusted && abs($wd->effective_mandatory_amount - $wd->mandatory_amount) > 0.001)
                    Rp {{ number_format($wd->effective_mandatory_amount, 0, ',', '.') }} <span class="text-[9px] line-through text-gray-400 font-normal">(Rp {{ number_format($wd->mandatory_amount, 0, ',', '.') }})</span>
                  @else
                    Rp {{ number_format($wd->effective_mandatory_amount, 0, ',', '.') }}
                  @endif
                </strong></span>
                <span>•</span>
                <span>S: <strong class="text-gray-700 dark:text-gray-300">
                  @if($wd->is_amount_adjusted && abs($wd->effective_secondary_amount - $wd->secondary_amount) > 0.001)
                    Rp {{ number_format($wd->effective_secondary_amount, 0, ',', '.') }} <span class="text-[9px] line-through text-gray-400 font-normal">(Rp {{ number_format($wd->secondary_amount, 0, ',', '.') }})</span>
                  @else
                    Rp {{ number_format($wd->effective_secondary_amount, 0, ',', '.') }}
                  @endif
                </strong></span>
              </div>
              
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  wire:click="openWithdrawalDetailModal('{{ $wd->id }}')"
                  class="font-semibold text-teal-600 dark:text-teal-400"
                >
                  Detail
                </button>
                @if($wd->status === 'pending')
                  <span>•</span>
                  <button
                    type="button"
                    wire:click="cancelWithdrawal('{{ $wd->id }}')"
                    wire:confirm="Batalkan pengajuan ini?"
                    class="font-semibold text-rose-600 dark:text-rose-400"
                  >
                    Batal
                  </button>
                @endif
              </div>
            </div>
          </div>
        @empty
          <div class="p-8 text-center text-gray-400 dark:text-gray-500">
            <x-heroicon-o-document-plus class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" />
            <p class="text-xs font-bold text-gray-600 dark:text-gray-400">Belum Ada Pengajuan Penarikan</p>
            <p class="text-[11px] mt-1 text-gray-400 dark:text-gray-500">Klik tombol "Ajukan" di atas untuk mengajukan penarikan syirkah.</p>
          </div>
        @endforelse
      </div>

      <!-- Pagination Withdrawals -->
      @if($withdrawals->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
          {{ $withdrawals->links() }}
        </div>
      @endif
    </div>

  </div>

  <!-- 5. PENGAJUAN PENARIKAN MODAL -->
  <x-dialog-modal wire:model.live="isWithdrawalModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-gray-900 dark:text-white">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300">
          <x-heroicon-o-banknotes class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-base font-bold">{{ __('Pengajuan Penarikan Syirkah') }}</h3>
          <p class="text-xs font-normal text-gray-500 dark:text-gray-400">Pilih opsi penarikan & isi nominal dana</p>
        </div>
      </div>
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="submitWithdrawal" class="space-y-4">
        
        <!-- Saldo Tersedia Overview Cards -->
        <div class="rounded-xl bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 p-3 border border-emerald-200/80 dark:from-emerald-950/40 dark:via-teal-950/40 dark:to-cyan-950/40 dark:border-emerald-800/40">
          <p class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wide">
            Sisa Saldo Tersedia untuk Ditarik:
          </p>
          <div class="grid grid-cols-2 gap-2 mt-2">
            <div class="bg-white/80 dark:bg-gray-800/80 p-2 rounded-lg border border-emerald-100 dark:border-gray-700 text-center">
              <span class="text-[10px] text-gray-500 dark:text-gray-400 block">Wajib:</span>
              <strong class="text-xs sm:text-sm font-black text-gray-800 dark:text-gray-100">
                Rp {{ number_format($availMandatory, 0, ',', '.') }}
              </strong>
            </div>
            <div class="bg-white/80 dark:bg-gray-800/80 p-2 rounded-lg border border-emerald-100 dark:border-gray-700 text-center">
              <span class="text-[10px] text-gray-500 dark:text-gray-400 block">Sukarela (SSR):</span>
              <strong class="text-xs sm:text-sm font-black text-gray-800 dark:text-gray-100">
                Rp {{ number_format($availSecondary, 0, ',', '.') }}
              </strong>
            </div>
          </div>
        </div>

        <!-- Withdrawal Type Selector (3 Options) -->
        <div>
          <x-label value="Opsi Penarikan Syirkah" class="text-xs font-semibold mb-1.5 block" />
          <div class="grid grid-cols-3 gap-2">
            <!-- Option 1: Syirkah Full -->
            <label class="relative flex flex-col items-center justify-center p-2.5 rounded-xl border-2 cursor-pointer transition text-center {{ $withdrawalType === 'full' ? 'border-emerald-600 bg-emerald-50/70 text-emerald-900 dark:border-emerald-500 dark:bg-emerald-950/60 dark:text-emerald-200 shadow-xs' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
              <input type="radio" name="withdrawalType" value="full" wire:model.live="withdrawalType" class="sr-only" />
              <x-heroicon-o-scale class="h-4 w-4 mb-1 {{ $withdrawalType === 'full' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}" />
              <span class="text-[11px] font-extrabold leading-tight">Syirkah Full</span>
              <span class="text-[9px] text-gray-400 mt-0.5">Wajib + SSR</span>
            </label>

            <!-- Option 2: Syirkah Wajib -->
            <label class="relative flex flex-col items-center justify-center p-2.5 rounded-xl border-2 cursor-pointer transition text-center {{ $withdrawalType === 'mandatory' ? 'border-emerald-600 bg-emerald-50/70 text-emerald-900 dark:border-emerald-500 dark:bg-emerald-950/60 dark:text-emerald-200 shadow-xs' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
              <input type="radio" name="withdrawalType" value="mandatory" wire:model.live="withdrawalType" class="sr-only" />
              <x-heroicon-o-lock-closed class="h-4 w-4 mb-1 {{ $withdrawalType === 'mandatory' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}" />
              <span class="text-[11px] font-extrabold leading-tight">Syirkah Wajib</span>
              <span class="text-[9px] text-gray-400 mt-0.5">Hanya Wajib</span>
            </label>

            <!-- Option 3: Syirkah SSR -->
            <label class="relative flex flex-col items-center justify-center p-2.5 rounded-xl border-2 cursor-pointer transition text-center {{ $withdrawalType === 'secondary' ? 'border-emerald-600 bg-emerald-50/70 text-emerald-900 dark:border-emerald-500 dark:bg-emerald-950/60 dark:text-emerald-200 shadow-xs' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
              <input type="radio" name="withdrawalType" value="secondary" wire:model.live="withdrawalType" class="sr-only" />
              <x-heroicon-o-sparkles class="h-4 w-4 mb-1 {{ $withdrawalType === 'secondary' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}" />
              <span class="text-[11px] font-extrabold leading-tight">Syirkah SSR</span>
              <span class="text-[9px] text-gray-400 mt-0.5">Sukarela</span>
            </label>
          </div>
          <x-input-error for="withdrawalType" class="mt-1" />
        </div>

        <!-- Dynamic Inputs Based on Selection with Auto IDR / Rupiah Formatting -->
        @if($withdrawalType === 'full' || $withdrawalType === 'mandatory')
          <div x-data="{
            rawVal: @entangle('mandatoryAmount').live,
            displayVal: '',
            formatRupiah(val) {
              if (val === null || val === undefined || val === '' || Number(val) === 0) return '';
              let num = parseInt(String(val).replace(/[^0-9]/g, ''), 10);
              if (isNaN(num) || num === 0) return '';
              return 'Rp. ' + new Intl.NumberFormat('id-ID').format(num);
            },
            init() {
              this.displayVal = this.formatRupiah(this.rawVal);
              this.$watch('rawVal', (newVal) => {
                this.displayVal = this.formatRupiah(newVal);
              });
            },
            handleInput(e) {
              let clean = e.target.value.replace(/[^0-9]/g, '');
              let num = clean ? parseInt(clean, 10) : 0;
              this.rawVal = num;
              this.displayVal = num > 0 ? 'Rp. ' + new Intl.NumberFormat('id-ID').format(num) : '';
            }
          }">
            <div class="flex items-center justify-between">
              <x-label for="mandatoryAmount" value="Nominal Syirkah Wajib (Rp)" class="text-xs font-semibold" />
              <button 
                type="button" 
                wire:click="setMaxMandatory" 
                class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 underline cursor-pointer"
              >
                Tarik Semua (Rp {{ number_format($availMandatory, 0, ',', '.') }})
              </button>
            </div>
            <div class="relative mt-1">
              <x-input 
                type="text" 
                inputmode="numeric"
                id="mandatoryAmount" 
                x-model="displayVal"
                @input="handleInput($event)"
                class="w-full text-xs sm:text-sm font-bold text-gray-900 dark:text-white" 
                placeholder="Rp. 0" 
              />
            </div>
            <x-input-error for="mandatoryAmount" class="mt-1" />
          </div>
        @endif

        @if($withdrawalType === 'full' || $withdrawalType === 'secondary')
          <div x-data="{
            rawVal: @entangle('secondaryAmount').live,
            displayVal: '',
            formatRupiah(val) {
              if (val === null || val === undefined || val === '' || Number(val) === 0) return '';
              let num = parseInt(String(val).replace(/[^0-9]/g, ''), 10);
              if (isNaN(num) || num === 0) return '';
              return 'Rp. ' + new Intl.NumberFormat('id-ID').format(num);
            },
            init() {
              this.displayVal = this.formatRupiah(this.rawVal);
              this.$watch('rawVal', (newVal) => {
                this.displayVal = this.formatRupiah(newVal);
              });
            },
            handleInput(e) {
              let clean = e.target.value.replace(/[^0-9]/g, '');
              let num = clean ? parseInt(clean, 10) : 0;
              this.rawVal = num;
              this.displayVal = num > 0 ? 'Rp. ' + new Intl.NumberFormat('id-ID').format(num) : '';
            }
          }">
            <div class="flex items-center justify-between">
              <x-label for="secondaryAmount" value="Nominal Syirkah SSR / Sukarela (Rp)" class="text-xs font-semibold" />
              <button 
                type="button" 
                wire:click="setMaxSecondary" 
                class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 underline cursor-pointer"
              >
                Tarik Semua (Rp {{ number_format($availSecondary, 0, ',', '.') }})
              </button>
            </div>
            <div class="relative mt-1">
              <x-input 
                type="text" 
                inputmode="numeric"
                id="secondaryAmount" 
                x-model="displayVal"
                @input="handleInput($event)"
                class="w-full text-xs sm:text-sm font-bold text-gray-900 dark:text-white" 
                placeholder="Rp. 0" 
              />
            </div>
            <x-input-error for="secondaryAmount" class="mt-1" />
          </div>
        @endif

        <!-- Reason / Keperluan Input -->
        <div>
          <x-label for="reason" value="Keperluan / Alasan Penarikan (Opsional)" class="text-xs font-semibold" />
          <textarea 
            id="reason" 
            wire:model.live="reason" 
            rows="2" 
            class="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-xs sm:text-sm" 
            placeholder="Contoh: Kebutuhan keluarga mendesak, renovasi, dll."
          ></textarea>
          <x-input-error for="reason" class="mt-1" />
        </div>

        <!-- Live Total Summary Card -->
        @php
          $calcMandatory = ($withdrawalType === 'secondary') ? 0 : (float) $mandatoryAmount;
          $calcSecondary = ($withdrawalType === 'mandatory') ? 0 : (float) $secondaryAmount;
          $calcTotal = $calcMandatory + $calcSecondary;
        @endphp
        <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-3 border border-gray-200 dark:border-gray-600 flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase block">Total Pengajuan:</span>
            <span class="text-[10px] text-gray-400">W: Rp {{ number_format($calcMandatory, 0, ',', '.') }} | S: Rp {{ number_format($calcSecondary, 0, ',', '.') }}</span>
          </div>
          <span class="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400">
            Rp {{ number_format($calcTotal, 0, ',', '.') }}
          </span>
        </div>

        <!-- Lifecycle Notice -->
        <div class="rounded-lg bg-sky-50 dark:bg-sky-950/40 p-2.5 border border-sky-200 dark:border-sky-800 text-[11px] text-sky-800 dark:text-sky-300 flex items-start gap-2">
          <x-heroicon-o-information-circle class="h-4 w-4 shrink-0 mt-0.5 text-sky-600" />
          <p>
            Pengajuan akan diverifikasi oleh <strong>Manajer Divisi</strong> dan disetujui oleh <strong>Owner</strong>. Saldo mutasi syirkah Anda <strong>hanya akan terpotong setelah status berubah menjadi DIBAYARKAN (PAID)</strong> sebesar nominal yang disetujui.
          </p>
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeWithdrawalModal" wire:loading.attr="disabled">
          {{ __('Batal') }}
        </x-secondary-button>

        <x-button wire:click="submitWithdrawal" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800">
          <span wire:loading.remove wire:target="submitWithdrawal">
            {{ __('Kirim Pengajuan') }}
          </span>
          <span wire:loading wire:target="submitWithdrawal" class="inline-flex items-center gap-1.5">
            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            {{ __('Mengirim...') }}
          </span>
        </x-button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- 6. DETAIL MUTASI TRANSACTION DIALOG MODAL -->
  <x-dialog-modal wire:model.live="isDetailModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-gray-900 dark:text-white">
        <x-heroicon-o-receipt-percent class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
        {{ __('Rincian Transaksi Syirkah') }}
      </div>
    </x-slot>

    <x-slot name="content">
      @if($selectedTransaction)
        @php
          $isDep = $selectedTransaction->transaction_type === 'deposit';
          $totNom = (float) ($selectedTransaction->mandatory_amount + $selectedTransaction->secondary_amount);
          $totBal = (float) ($selectedTransaction->balance_mandatory + $selectedTransaction->balance_secondary);
        @endphp

        <!-- Top Amount Card -->
        <div class="rounded-2xl p-4 text-center {{ $isDep ? 'bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-800/40' : 'bg-rose-50 dark:bg-rose-950/40 border border-rose-200/60 dark:border-rose-800/40' }} mb-4">
          <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider {{ $isDep ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300' }}">
            {{ $isDep ? 'KREDIT / SETORAN (MASUK)' : 'DEBIT / PENARIKAN (KELUAR)' }}
          </span>
          <h3 class="mt-2 text-2xl font-black {{ $isDep ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
            {{ $isDep ? '+' : '-' }} Rp {{ number_format($totNom, 0, ',', '.') }}
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Status: <strong class="text-emerald-600 dark:text-emerald-400 font-bold">Disetujui (Approved)</strong>
          </p>
        </div>

        <!-- Detail Information List -->
        <div class="space-y-2.5 text-xs">
          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Tanggal Transaksi</span>
            <span class="font-semibold text-gray-800 dark:text-gray-200">
              {{ \Carbon\Carbon::parse($selectedTransaction->created_at)->translatedFormat('d F Y, H:i') }} WIB
            </span>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Program Syirkah</span>
            <span class="font-semibold text-gray-800 dark:text-gray-200">
              {{ $selectedTransaction->masterSaving?->savings_name ?? 'Syirkah Umum' }}
            </span>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Nominal Wajib</span>
            <span class="font-bold text-gray-800 dark:text-gray-200">
              Rp {{ number_format($selectedTransaction->mandatory_amount, 0, ',', '.') }}
            </span>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Nominal Sukarela (SSR)</span>
            <span class="font-bold text-gray-800 dark:text-gray-200">
              Rp {{ number_format($selectedTransaction->secondary_amount, 0, ',', '.') }}
            </span>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Saldo Akhir Transaksi</span>
            <span class="font-black text-emerald-600 dark:text-emerald-400">
              Rp {{ number_format($totBal, 0, ',', '.') }}
            </span>
          </div>

          @if($selectedTransaction->reference_type)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Tipe Referensi</span>
              <span class="font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                {{ $selectedTransaction->reference_type }}
              </span>
            </div>
          @endif

          @if($selectedTransaction->approver)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Disetujui Oleh</span>
              <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $selectedTransaction->approver->name }}
              </span>
            </div>
          @endif

          @if($selectedTransaction->approval_date)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Waktu Persetujuan</span>
              <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($selectedTransaction->approval_date)->translatedFormat('d M Y, H:i') }} WIB
              </span>
            </div>
          @endif

          @if($selectedTransaction->description)
            <div class="pt-2">
              <span class="text-gray-500 dark:text-gray-400 block mb-1">Keterangan:</span>
              <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-2.5 text-gray-700 dark:text-gray-300 font-medium">
                {{ $selectedTransaction->description }}
              </div>
            </div>
          @endif
        </div>
      @endif
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeDetailModal" wire:loading.attr="disabled">
        {{ __('Tutup') }}
      </x-secondary-button>
    </x-slot>
  </x-dialog-modal>

  <!-- 7. DETAIL WITHDRAWAL REQUEST MODAL -->
  <x-dialog-modal wire:model.live="isWithdrawalDetailModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-gray-900 dark:text-white">
        <x-heroicon-o-document-text class="h-5 w-5 text-teal-600 dark:text-teal-400" />
        {{ __('Detail Pengajuan Penarikan Syirkah') }}
      </div>
    </x-slot>

    <x-slot name="content">
      @if($selectedWithdrawal)
        @php
          $badge = $selectedWithdrawal->status_badge;
        @endphp

        <!-- Top Status Card -->
        <div class="rounded-2xl p-4 text-center {{ $badge['bg'] }} border {{ $badge['border'] }} mb-4">
          <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider {{ $badge['text'] }}">
            {{ $badge['label'] }}
          </span>
          <h3 class="mt-2 text-2xl font-black text-rose-600 dark:text-rose-400">
            - Rp {{ number_format($selectedWithdrawal->total_amount, 0, ',', '.') }}
          </h3>
          <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 font-semibold">
            {{ $badge['desc'] }}
          </p>
        </div>

        <!-- Detail Information List -->
        <div class="space-y-2.5 text-xs">
          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Tanggal Pengajuan</span>
            <span class="font-semibold text-gray-800 dark:text-gray-200">
              {{ \Carbon\Carbon::parse($selectedWithdrawal->created_at)->translatedFormat('d F Y, H:i') }} WIB
            </span>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Opsi Penarikan</span>
            <span class="font-bold text-gray-800 dark:text-gray-200">
              {{ $selectedWithdrawal->withdrawal_type_label }}
            </span>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Nominal Wajib</span>
            <span class="font-bold text-gray-800 dark:text-gray-200">
              Rp {{ number_format($selectedWithdrawal->mandatory_amount, 0, ',', '.') }}
            </span>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Nominal Sukarela (SSR)</span>
            <span class="font-bold text-gray-800 dark:text-gray-200">
              Rp {{ number_format($selectedWithdrawal->secondary_amount, 0, ',', '.') }}
            </span>
          </div>

          @if($selectedWithdrawal->approved_total_amount && $selectedWithdrawal->approved_total_amount != $selectedWithdrawal->total_amount)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700 bg-indigo-50/50 dark:bg-indigo-950/20 px-2 rounded-lg">
              <span class="text-indigo-600 dark:text-indigo-400 font-bold">Nominal Disetujui Owner</span>
              <span class="font-extrabold text-indigo-700 dark:text-indigo-300">
                Rp {{ number_format($selectedWithdrawal->approved_total_amount, 0, ',', '.') }}
              </span>
            </div>
          @endif

          @if($selectedWithdrawal->approver)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Diverifikasi Manajer Divisi</span>
              <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $selectedWithdrawal->approver->name }}
              </span>
            </div>
          @endif

          @if($selectedWithdrawal->approved_at)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Waktu Verifikasi Manajer</span>
              <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($selectedWithdrawal->approved_at)->translatedFormat('d M Y, H:i') }} WIB
              </span>
            </div>
          @endif

          @if($selectedWithdrawal->ownerApprover)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Disetujui Owner</span>
              <span class="font-semibold text-indigo-600 dark:text-indigo-400">
                {{ $selectedWithdrawal->ownerApprover->name }}
              </span>
            </div>
          @endif

          @if($selectedWithdrawal->owner_approved_at)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Waktu Persetujuan Owner</span>
              <span class="font-semibold text-indigo-600 dark:text-indigo-400">
                {{ \Carbon\Carbon::parse($selectedWithdrawal->owner_approved_at)->translatedFormat('d M Y, H:i') }} WIB
              </span>
            </div>
          @endif

          @if($selectedWithdrawal->payer)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Dibayarkan Oleh</span>
              <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $selectedWithdrawal->payer->name }}
              </span>
            </div>
          @endif

          @if($selectedWithdrawal->paid_at)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Waktu Pembayaran</span>
              <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                {{ \Carbon\Carbon::parse($selectedWithdrawal->paid_at)->translatedFormat('d M Y, H:i') }} WIB
              </span>
            </div>
          @endif

          @if($selectedWithdrawal->owner_note)
            <div class="pt-2">
              <span class="text-indigo-600 dark:text-indigo-400 font-bold block mb-1">Catatan Owner:</span>
              <div class="rounded-xl bg-indigo-50 dark:bg-indigo-950/40 p-2.5 text-indigo-800 dark:text-indigo-300 font-medium border border-indigo-200 dark:border-indigo-800">
                {{ $selectedWithdrawal->owner_note }}
              </div>
            </div>
          @endif

          @if($selectedWithdrawal->rejection_reason)
            <div class="pt-2">
              <span class="text-rose-500 font-bold block mb-1">Alasan Penolakan:</span>
              <div class="rounded-xl bg-rose-50 dark:bg-rose-950/40 p-2.5 text-rose-700 dark:text-rose-300 font-medium border border-rose-200 dark:border-rose-800">
                {{ $selectedWithdrawal->rejection_reason }}
              </div>
            </div>
          @endif

          @if($selectedWithdrawal->reason)
            <div class="pt-2">
              <span class="text-gray-500 dark:text-gray-400 block mb-1">Alasan / Catatan Pengajuan:</span>
              <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-2.5 text-gray-700 dark:text-gray-300 font-medium">
                {{ $selectedWithdrawal->reason }}
              </div>
            </div>
          @endif
        </div>
      @endif
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeWithdrawalDetailModal" wire:loading.attr="disabled">
        {{ __('Tutup') }}
      </x-secondary-button>
    </x-slot>
  </x-dialog-modal>

</div>
