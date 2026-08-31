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
        <!-- Card Top Bar -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 backdrop-blur-md">
              <x-heroicon-o-building-library class="h-4 w-4 text-white" />
            </div>
            <span class="text-xs sm:text-sm font-semibold tracking-wide text-emerald-100">
              Tabungan Syirkah Karyawan
            </span>
          </div>

          <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-950/40 backdrop-blur-md px-2.5 py-1 text-[11px] font-medium text-emerald-200 border border-emerald-400/30">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            Aktif & Disetujui
          </span>
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

        <!-- Filter Controls (Responsive 2-Row / Flex layout) -->
        <div class="pt-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
          <!-- Search Input -->
          <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
            </div>
            <x-input type="text" class="block w-full pl-9 text-xs sm:text-sm !py-2" wire:model.live.debounce.300ms="search" placeholder="Cari keterangan atau referensi..." />
          </div>

          <!-- Secondary Filters Row on Mobile -->
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

            <!-- Reset Button -->
            @if($search || $month || $type)
              <x-secondary-button wire:click="resetFilters" class="text-xs !py-2 px-2.5 text-rose-600 hover:text-rose-700 dark:text-rose-400 shrink-0" title="Reset Filter">
                <x-heroicon-o-x-mark class="h-4 w-4" />
              </x-secondary-button>
            @endif
          </div>
        </div>
      </div>

      <!-- DESKTOP PASSBOOK TABLE (md and up) -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
          <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-gray-700 dark:bg-gray-700/50 dark:text-gray-300 border-b border-gray-100 dark:border-gray-700">
            <tr>
              <th scope="col" class="px-4 py-3.5 font-semibold">Tanggal & Waktu</th>
              <th scope="col" class="px-4 py-3.5 font-semibold">Keterangan / Program</th>
              <th scope="col" class="px-4 py-3.5 font-semibold text-center">Jenis</th>
              <th scope="col" class="px-4 py-3.5 font-semibold text-right">Debit (Keluar)</th>
              <th scope="col" class="px-4 py-3.5 font-semibold text-right">Kredit (Masuk)</th>
              <th scope="col" class="px-4 py-3.5 font-semibold text-right">Sisa Saldo</th>
              <th scope="col" class="px-4 py-3.5 font-semibold text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
            @forelse($transactions as $tx)
              @php
                $isDeposit = $tx->transaction_type === 'deposit';
                $totalNominal = (float) ($tx->mandatory_amount + $tx->secondary_amount);
                $totalRunningBalance = (float) ($tx->balance_mandatory + $tx->balance_secondary);
              @endphp
              <tr class="hover:bg-gray-50/75 dark:hover:bg-gray-700/30 transition-colors">
                <!-- Tanggal -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <div class="text-gray-900 dark:text-gray-100 font-bold text-xs sm:text-sm">
                    {{ \Carbon\Carbon::parse($tx->created_at)->translatedFormat('d M Y') }}
                  </div>
                  <div class="text-[11px] text-gray-400">
                    {{ \Carbon\Carbon::parse($tx->created_at)->format('H:i') }} WIB
                  </div>
                </td>

                <!-- Keterangan & Breakdown -->
                <td class="px-4 py-3.5">
                  <div class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $tx->description ?: ($isDeposit ? 'Setoran Syirkah' : 'Penarikan Syirkah') }}
                  </div>
                  <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    @if($tx->masterSaving)
                      <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 font-medium text-gray-600 dark:text-gray-300">
                        {{ $tx->masterSaving->savings_name }}
                      </span>
                    @endif
                    <span>Wajib: <strong class="text-gray-700 dark:text-gray-300">Rp {{ number_format($tx->mandatory_amount, 0, ',', '.') }}</strong></span>
                    <span>•</span>
                    <span>SSR: <strong class="text-gray-700 dark:text-gray-300">Rp {{ number_format($tx->secondary_amount, 0, ',', '.') }}</strong></span>
                  </div>
                </td>

                <!-- Jenis Badge -->
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                  @if($isDeposit)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                      <x-heroicon-o-arrow-down-left class="h-3.5 w-3.5" />
                      KREDIT (SETOR)
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 dark:bg-rose-950/50 px-2.5 py-0.5 text-xs font-bold text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60">
                      <x-heroicon-o-arrow-up-right class="h-3.5 w-3.5" />
                      DEBIT (TARIK)
                    </span>
                  @endif
                </td>

                <!-- Debit (Keluar) -->
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                  @if(!$isDeposit)
                    <span class="text-sm font-bold text-rose-600 dark:text-rose-400">
                      - Rp {{ number_format($totalNominal, 0, ',', '.') }}
                    </span>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>

                <!-- Kredit (Masuk) -->
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                  @if($isDeposit)
                    <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                      + Rp {{ number_format($totalNominal, 0, ',', '.') }}
                    </span>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>

                <!-- Sisa Saldo Berjalan -->
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                  <span class="text-xs sm:text-sm font-black text-gray-900 dark:text-white">
                    Rp {{ number_format($totalRunningBalance, 0, ',', '.') }}
                  </span>
                  <div class="text-[10px] text-gray-400">
                    W: {{ number_format($tx->balance_mandatory, 0, ',', '.') }} | S: {{ number_format($tx->balance_secondary, 0, ',', '.') }}
                  </div>
                </td>

                <!-- Aksi Detail -->
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                  <button type="button" wire:click="openDetailModal('{{ $tx->id }}')" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors" title="Lihat Rincian">
                    <x-heroicon-o-eye class="h-4 w-4" />
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                  <x-heroicon-o-banknotes class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-2" />
                  <p class="text-sm font-bold text-gray-600 dark:text-gray-400">Belum Ada Riwayat Mutasi Syirkah</p>
                  <p class="text-xs mt-1 text-gray-400 dark:text-gray-500">Data mutasi yang disetujui akan tampil di sini secara otomatis.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- MOBILE PASSBOOK LEDGER CARDS (Below md) -->
      <div class="block md:hidden divide-y divide-gray-100 dark:divide-gray-700/60">
        @forelse($transactions as $tx)
          @php
            $isDeposit = $tx->transaction_type === 'deposit';
            $totalNominal = (float) ($tx->mandatory_amount + $tx->secondary_amount);
            $totalRunningBalance = (float) ($tx->balance_mandatory + $tx->balance_secondary);
          @endphp
          <div class="p-3.5 sm:p-4 hover:bg-gray-50/60 dark:hover:bg-gray-700/20 transition-colors cursor-pointer active:bg-gray-100 dark:active:bg-gray-700/40" wire:click="openDetailModal('{{ $tx->id }}')">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-start gap-2.5 min-w-0">
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

      <!-- Pagination -->
      @if($transactions->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
          {{ $transactions->links() }}
        </div>
      @endif
    </div>
  </div>

  <!-- DETAIL TRANSACTION DIALOG MODAL -->
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
</div>
