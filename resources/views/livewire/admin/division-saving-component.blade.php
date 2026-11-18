<div>
  <x-slot name="header">
    <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-100 flex items-center gap-2">
          <x-heroicon-o-wallet class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
          Tabungan Divisi (Division Savings)
        </h2>
        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Kelola alokasi dompet kas divisi, pengajuan mutasi kredit & debit, serta verifikasi approval oleh Owner.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        @if ($isOwnerOrSuperadmin)
          <div class="flex items-center gap-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-1.5 shadow-xs">
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Divisi:</span>
            <select wire:model.live="selectedDivisionId" class="text-xs font-bold border-0 bg-transparent text-indigo-600 dark:text-indigo-400 focus:ring-0 p-0 cursor-pointer">
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </select>
          </div>
        @elseif (Auth::user()->hasMultipleDivisions())
          <div class="flex items-center gap-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-1.5 shadow-xs">
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Pilih Divisi:</span>
            <select wire:model.live="selectedDivisionId" class="text-xs font-bold border-0 bg-transparent text-indigo-600 dark:text-indigo-400 focus:ring-0 p-0 cursor-pointer">
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </select>
          </div>
        @else
          <div class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:text-indigo-300">
            <x-heroicon-o-building-office class="h-4 w-4" />
            Divisi: {{ $activeDivision?->name ?? 'Divisi' }}
          </div>
        @endif

        <x-button type="button" wire:click="openTransactionModal" class="bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 shadow-xs text-xs font-semibold">
          <x-heroicon-o-plus-circle class="mr-1.5 h-4 w-4" />
          {{ $isOwnerOrSuperadmin ? 'Catat Transaksi' : 'Ajukan Transaksi' }}
        </x-button>
      </div>
    </div>
  </x-slot>

  <div class="py-4 sm:py-6 space-y-6">
    <!-- FLASH / ERROR BANNER -->
    @if ($errorMessage)
      <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/50 p-4 text-sm text-red-700 dark:text-red-300 flex items-start gap-3 shadow-xs">
        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
        <div class="flex-1 font-medium">{{ $errorMessage }}</div>
        <button type="button" wire:click="$set('errorMessage', '')" class="text-red-400 hover:text-red-600">
          <x-heroicon-o-x-mark class="h-5 w-5" />
        </button>
      </div>
    @endif

    <!-- PENDING APPROVAL ALERT BANNER (JIKA ADA PENGAJUAN PENDING) -->
    @if ($summary['pending_count'] > 0)
      <div class="rounded-2xl border border-amber-300 dark:border-amber-700/70 bg-gradient-to-r from-amber-50 via-amber-100/40 to-orange-50 dark:from-amber-950/40 dark:via-amber-900/20 dark:to-orange-950/30 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
          <div class="h-10 w-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs animate-pulse">
            <x-heroicon-o-clock class="h-6 w-6" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                Terdapat {{ $summary['pending_count'] }} Pengajuan Transaksi Menunggu Verifikasi
              </h3>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white shadow-xs">
                Pending
              </span>
            </div>
            <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
              Dana tidak akan bertambah atau berkurang sampai diverifikasi dan disetujui langsung oleh Owner.
              <span class="font-semibold ml-1">
                Estimasi Kredit: <span class="font-mono text-emerald-600 dark:text-emerald-400">+Rp {{ number_format($summary['pending_credit'], 0, ',', '.') }}</span> |
                Estimasi Debit: <span class="font-mono text-rose-600 dark:text-rose-400">-Rp {{ number_format($summary['pending_debit'], 0, ',', '.') }}</span>
              </span>
            </p>
          </div>
        </div>

        <button type="button" wire:click="$set('selectedStatus', 'pending')" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition-all whitespace-nowrap">
          <span>Tinjau Pengajuan Pending</span>
          <x-heroicon-o-arrow-right class="h-4 w-4" />
        </button>
      </div>
    @endif

    <!-- 1. SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Saldo Divisi (Approved Real Balance) -->
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-blue-700 p-5 text-white shadow-md">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold tracking-wider uppercase text-indigo-100">Saldo Riil Divisi</span>
          <div class="rounded-xl bg-white/20 p-2 backdrop-blur-xs">
            <x-heroicon-o-banknotes class="h-5 w-5 text-white" />
          </div>
        </div>
        <div class="mt-3">
          <div class="text-2xl sm:text-3xl font-extrabold tracking-tight font-mono">
            Rp {{ number_format($summary['total_balance'], 0, ',', '.') }}
          </div>
          <div class="mt-2 text-xs text-indigo-100 flex items-center gap-1.5">
            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-white/25 text-[11px] font-bold">
              {{ $summary['total_wallets_count'] }} Dompet Aktif
            </span>
            <span>di {{ $activeDivision?->name ?? 'Divisi' }}</span>
          </div>
        </div>
      </div>

      <!-- Pemasukan Bulan Ini (Approved Credit) -->
      <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/70 dark:bg-emerald-950/30 p-5 shadow-xs">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold tracking-wider uppercase text-emerald-800 dark:text-emerald-300">Pemasukan Disetujui</span>
          <div class="rounded-xl bg-emerald-500/15 dark:bg-emerald-500/20 p-2 text-emerald-600 dark:text-emerald-400">
            <x-heroicon-o-arrow-down-left class="h-5 w-5" />
          </div>
        </div>
        <div class="mt-3">
          <div class="text-2xl font-bold font-mono text-emerald-700 dark:text-emerald-300">
            + Rp {{ number_format($summary['total_credit_month'], 0, ',', '.') }}
          </div>
          <div class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
            Periode: {{ $summary['period_label'] }}
          </div>
        </div>
      </div>

      <!-- Pengeluaran Bulan Ini (Approved Debit) -->
      <div class="rounded-2xl border border-rose-200 dark:border-rose-800/60 bg-rose-50/70 dark:bg-rose-950/30 p-5 shadow-xs">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold tracking-wider uppercase text-rose-800 dark:text-rose-300">Pengeluaran Disetujui</span>
          <div class="rounded-xl bg-rose-500/15 dark:bg-rose-500/20 p-2 text-rose-600 dark:text-rose-400">
            <x-heroicon-o-arrow-up-right class="h-5 w-5" />
          </div>
        </div>
        <div class="mt-3">
          <div class="text-2xl font-bold font-mono text-rose-700 dark:text-rose-300">
            - Rp {{ number_format($summary['total_debit_month'], 0, ',', '.') }}
          </div>
          <div class="mt-1 text-xs text-rose-600 dark:text-rose-400">
            Periode: {{ $summary['period_label'] }}
          </div>
        </div>
      </div>

      <!-- Status Antrean Pending & Periode -->
      <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-xs flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold tracking-wider uppercase text-gray-500 dark:text-gray-400">Filter Bulan</span>
            <x-heroicon-o-calendar class="h-5 w-5 text-gray-400" />
          </div>
          <div class="mt-2">
            <input type="month" wire:model.live="selectedMonth" class="w-full text-xs font-semibold rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
          </div>
        </div>
        <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
          <span>Antrean Pending:</span>
          <span class="font-bold {{ $summary['pending_count'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
            {{ $summary['pending_count'] }} Transaksi
          </span>
        </div>
      </div>
    </div>

    <!-- 2. WALLET CARDS GRID (DOMPET ALOKASI DANA DIVISI) -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-xs space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
        <div>
          <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <x-heroicon-o-folder class="h-5 w-5 text-indigo-500" />
            Daftar Dompet Alokasi Dana ({{ $wallets->count() }})
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Dompet tabungan terisolasi untuk <strong>{{ $activeDivision?->name ?? 'Divisi' }}</strong>. Saldo di bawah adalah saldo riil FIX (hanya dari transaksi yang disetujui Owner).
          </p>
        </div>
        <button type="button" wire:click="openTransactionModal(null, 'credit')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 hover:underline">
          <x-heroicon-o-plus class="h-4 w-4" />
          Buat Dompet Baru
        </button>
      </div>

      @if ($wallets->isEmpty())
        <div class="text-center py-10">
          <div class="mx-auto h-12 w-12 rounded-full bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-500">
            <x-heroicon-o-wallet class="h-6 w-6" />
          </div>
          <h4 class="mt-3 text-sm font-bold text-gray-900 dark:text-gray-100">Belum Ada Dompet Tabungan</h4>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
            Divisi ini belum memiliki dompet kas alokasi. Klik tombol di bawah untuk membuat dompet dan melakukan transaksi pertama.
          </p>
          <div class="mt-4">
            <x-button type="button" wire:click="openTransactionModal" class="bg-indigo-600 hover:bg-indigo-700 text-xs">
              <x-heroicon-o-plus-circle class="mr-1.5 h-4 w-4" />
              Buat Dompet Pertama
            </x-button>
          </div>
        </div>
      @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          @foreach ($wallets as $wallet)
            <div class="group relative rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 hover:bg-gray-50 dark:hover:bg-gray-800/70 p-4 transition-all duration-200 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 flex flex-col justify-between">
              <div>
                <div class="flex items-start justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-indigo-100 dark:bg-indigo-900/50 p-2 text-indigo-600 dark:text-indigo-400 group-hover:scale-105 transition-transform">
                      <x-heroicon-o-wallet class="h-5 w-5" />
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        {{ $wallet->title }}
                      </h4>
                      <div class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                        <span>{{ $wallet->approved_transactions_count }} disetujui</span>
                        @if ($wallet->pending_transactions_count > 0)
                          <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                            {{ $wallet->pending_transactions_count }} pending
                          </span>
                        @endif
                      </div>
                    </div>
                  </div>
                  <button type="button" wire:click="openEditWalletModal('{{ $wallet->id }}')" title="Edit nama dompet" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-md hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                  </button>
                </div>

                @if ($wallet->description)
                  <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                    {{ $wallet->description }}
                  </p>
                @endif

                <div class="mt-3 pt-3 border-t border-gray-200/60 dark:border-gray-700/60">
                  <span class="text-[10px] font-semibold tracking-wider uppercase text-gray-400">Saldo Riil</span>
                  <div class="text-xl font-extrabold font-mono text-gray-900 dark:text-gray-100">
                    {{ $wallet->formatted_balance }}
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-2 flex items-center gap-2">
                <button type="button" wire:click="openTransactionModal('{{ $wallet->id }}', 'credit')" class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 px-2.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 border border-emerald-200 dark:border-emerald-800 transition-colors">
                  <x-heroicon-o-plus class="h-3.5 w-3.5" />
                  Isi Kas
                </button>
                <button type="button" wire:click="openTransactionModal('{{ $wallet->id }}', 'debit')" class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 px-2.5 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900 border border-rose-200 dark:border-rose-800 transition-colors">
                  <x-heroicon-o-minus class="h-3.5 w-3.5" />
                  Keluar
                </button>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <!-- 3. MUTATION LEDGER (BUKU BESAR MUTASI TRANSAKSI) -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xs overflow-hidden">
      <!-- Toolbar Filter & Tabs -->
      <div class="p-5 border-b border-gray-100 dark:border-gray-800 space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <x-heroicon-o-clipboard-document-list class="h-5 w-5 text-indigo-500" />
              Buku Besar & Pengajuan Tabungan Divisi
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Riwayat lengkap uang masuk dan keluar pada divisi <strong>{{ $activeDivision?->name ?? 'Divisi' }}</strong>.
            </p>
          </div>
        </div>

        <!-- STATUS TABS SWITCHER -->
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-3">
          <button type="button" wire:click="$set('selectedStatus', '')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $selectedStatus === '' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            <span>Semua Transaksi</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedStatus === '' ? 'bg-white/25 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
              {{ $statusCounts['all'] }}
            </span>
          </button>

          <button type="button" wire:click="$set('selectedStatus', 'pending')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $selectedStatus === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            <x-heroicon-o-clock class="h-3.5 w-3.5" />
            <span>Menunggu Approval</span>
            @if ($statusCounts['pending'] > 0)
              <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-600 text-white animate-pulse">
                {{ $statusCounts['pending'] }}
              </span>
            @else
              <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-gray-200 dark:bg-gray-700 text-gray-500">
                0
              </span>
            @endif
          </button>

          <button type="button" wire:click="$set('selectedStatus', 'approved')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $selectedStatus === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
            <span>Disetujui (Approved)</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedStatus === 'approved' ? 'bg-white/25 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
              {{ $statusCounts['approved'] }}
            </span>
          </button>

          <button type="button" wire:click="$set('selectedStatus', 'rejected')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $selectedStatus === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            <x-heroicon-o-x-circle class="h-3.5 w-3.5" />
            <span>Ditolak</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedStatus === 'rejected' ? 'bg-white/25 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
              {{ $statusCounts['rejected'] }}
            </span>
          </button>
        </div>

        <!-- Filter Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
          <!-- Search -->
          <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode, keterangan, ref..." class="w-full pl-9 text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
          </div>

          <!-- Filter Wallet -->
          <div>
            <select wire:model.live="selectedWalletId" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">Semua Dompet Alokasi</option>
              @foreach ($wallets as $w)
                <option value="{{ $w->id }}">{{ $w->title }} ({{ $w->formatted_balance }})</option>
              @endforeach
            </select>
          </div>

          <!-- Filter Type -->
          <div>
            <select wire:model.live="selectedType" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">Semua Tipe Transaksi</option>
              <option value="credit">🟢 Uang Masuk (Credit)</option>
              <option value="debit">🔴 Pengeluaran (Debit)</option>
            </select>
          </div>

          <!-- Reset Filter -->
          <div class="flex items-center gap-2">
            <button type="button" wire:click="$set('search', ''); $set('selectedWalletId', ''); $set('selectedType', ''); $set('selectedStatus', '');" class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
              <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
              Reset Filter
            </button>
          </div>
        </div>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-800 text-left text-xs">
          <thead class="bg-gray-50 dark:bg-gray-800/60 uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold">
            <tr>
              <th scope="col" class="py-3 px-4">Tanggal & Kode</th>
              <th scope="col" class="py-3 px-4">Dompet Alokasi</th>
              <th scope="col" class="py-3 px-4 text-center">Tipe</th>
              <th scope="col" class="py-3 px-4 text-center">Status</th>
              <th scope="col" class="py-3 px-4 text-right">Nominal</th>
              <th scope="col" class="py-3 px-4 text-right">Saldo Sesudah</th>
              <th scope="col" class="py-3 px-4">Keterangan & Audit</th>
              <th scope="col" class="py-3 px-4 text-center">Aksi / Bukti</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
            @forelse ($transactions as $tx)
              <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors">
                <!-- Tanggal & Kode -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-bold text-gray-900 dark:text-gray-100">
                    {{ $tx->transaction_date->format('d/m/Y') }}
                  </div>
                  <div class="text-[11px] font-mono text-gray-500 dark:text-gray-400">
                    {{ $tx->transaction_code }}
                  </div>
                </td>

                <!-- Dompet -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    <x-heroicon-o-folder class="h-3.5 w-3.5" />
                    {{ $tx->wallet?->title ?? 'Dompet Dihapus' }}
                  </span>
                </td>

                <!-- Tipe -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  @if ($tx->is_credit)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300">
                      <x-heroicon-o-arrow-down-left class="h-3 w-3" />
                      CREDIT (Masuk)
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/70 text-rose-800 dark:text-rose-300">
                      <x-heroicon-o-arrow-up-right class="h-3 w-3" />
                      DEBIT (Keluar)
                    </span>
                  @endif
                </td>

                <!-- Status Approval -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  @if ($tx->status === 'approved')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                      <x-heroicon-s-check-circle class="h-3.5 w-3.5 text-emerald-500" />
                      Disetujui
                    </span>
                  @elseif ($tx->status === 'rejected')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                      <x-heroicon-s-x-circle class="h-3.5 w-3.5 text-rose-500" />
                      Ditolak
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-700 animate-pulse">
                      <x-heroicon-s-clock class="h-3.5 w-3.5 text-amber-500" />
                      Pending Owner
                    </span>
                  @endif
                </td>

                <!-- Nominal -->
                <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold {{ $tx->is_credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                  {{ $tx->is_credit ? '+' : '-' }}{{ $tx->formatted_amount }}
                </td>

                <!-- Saldo Sesudah (FIX hanya jika approved) -->
                <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-semibold">
                  @if ($tx->status === 'approved')
                    <span class="text-gray-900 dark:text-gray-100">{{ $tx->formatted_balance_after }}</span>
                  @elseif ($tx->status === 'pending')
                    <span class="text-[11px] text-amber-600 dark:text-amber-400 italic font-sans font-medium">Menunggu Approval</span>
                  @else
                    <span class="text-[11px] text-gray-400 italic font-sans">Dibatalkan</span>
                  @endif
                </td>

                <!-- Keterangan & Audit Trail -->
                <td class="py-3 px-4 max-w-xs">
                  <div class="font-medium text-gray-900 dark:text-gray-100 truncate" title="{{ $tx->description }}">
                    {{ $tx->description }}
                  </div>
                  <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                    Oleh: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $tx->creator?->name ?? 'System' }}</span>
                  </div>

                  @if ($tx->status === 'approved' && $tx->approver)
                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">
                      ✓ Disetujui: {{ $tx->approver->name }} ({{ $tx->approved_at?->format('d/m/y H:i') }})
                    </div>
                  @elseif ($tx->status === 'rejected')
                    <div class="text-[10px] text-rose-600 dark:text-rose-400 font-medium">
                      ✗ Ditolak: {{ $tx->rejector?->name ?? 'Owner' }} - "{{ $tx->rejection_note ?? 'Tanpa alasan' }}"
                    </div>
                  @endif

                  @if ($tx->reference_number)
                    <div class="text-[10px] text-gray-400 font-mono">
                      Ref: {{ $tx->reference_number }}
                    </div>
                  @endif
                </td>

                <!-- Aksi & Bukti -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    @if ($tx->status === 'pending')
                      @if ($isOwnerOrSuperadmin)
                        <button type="button" wire:click="openApproveModal('{{ $tx->id }}')" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors" title="Setujui transaksi">
                          <x-heroicon-s-check class="h-3 w-3" />
                          Setujui
                        </button>
                        <button type="button" wire:click="openRejectModal('{{ $tx->id }}')" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition-colors" title="Tolak transaksi">
                          <x-heroicon-s-x-mark class="h-3 w-3" />
                          Tolak
                        </button>
                      @elseif ($tx->created_by === Auth::id())
                        <button type="button" wire:click="cancelTransaction('{{ $tx->id }}')" wire:confirm="Batalkan pengajuan tabungan ini?" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors" title="Batalkan pengajuan saya">
                          <x-heroicon-o-trash class="h-3 w-3 text-red-500" />
                          Batal
                        </button>
                      @endif
                    @endif

                    @if ($tx->proof_attachment_path)
                      <button type="button" wire:click="openProofModal('{{ $tx->id }}')" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-semibold bg-gray-100 hover:bg-indigo-50 dark:bg-gray-800 dark:hover:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-gray-300 dark:border-gray-700 transition-colors" title="Lihat bukti nota">
                        <x-heroicon-o-document-text class="h-3.5 w-3.5" />
                        Bukti
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="py-12 text-center text-gray-400 dark:text-gray-500">
                  <x-heroicon-o-inbox class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600 mb-2" />
                  <p class="text-sm font-medium">Belum ada transaksi tabungan divisi yang sesuai filter.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      @if ($transactions->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-gray-800">
          {{ $transactions->links() }}
        </div>
      @endif
    </div>
  </div>

  <!-- MODAL 1: INPUT / PENGAJUAN TRANSAKSI (CREDIT / DEBIT) -->
  <x-dialog-modal wire:model.live="showTransactionModal" maxWidth="xl">
    <x-slot name="title">
      <div class="flex items-center gap-2">
        <div class="rounded-lg p-2 {{ $form_type === 'credit' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400' }}">
          <x-heroicon-o-wallet class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
            {{ $isOwnerOrSuperadmin ? 'Catat Transaksi Tabungan Divisi' : 'Pengajuan Mutasi Tabungan Divisi' }}
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $form_type === 'credit' ? 'Uang Masuk / Tambah Saldo (Credit)' : 'Pengeluaran / Beban Kas (Debit)' }}
          </p>
        </div>
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="space-y-4">
        @if ($errorMessage)
          <div class="p-3 rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-xs text-red-600 dark:text-red-300 font-medium">
            {{ $errorMessage }}
          </div>
        @endif

        <!-- Notice Banner untuk Admin -->
        @if (!$isOwnerOrSuperadmin)
          <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
            <x-heroicon-o-information-circle class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
            <div>
              <span class="font-bold block">Sistem Verifikasi Approval Owner Aktif</span>
              Input Anda akan masuk sebagai <strong>Pengajuan (Pending)</strong>. Saldo dompet divisi belum bertambah atau berkurang sampai disetujui langsung oleh Owner.
            </div>
          </div>
        @endif

        <!-- Pilihan Divisi (Khusus Owner / Superadmin) -->
        @if ($isOwnerOrSuperadmin)
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
              Divisi Target <span class="text-red-500">*</span>
            </label>
            <select wire:model.live="form_division_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
              @foreach ($divisions as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
              @endforeach
            </select>
            <x-input-error for="form_division_id" class="mt-1 text-xs" />
          </div>
        @else
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
              Divisi
            </label>
            <div class="px-3 py-2 bg-gray-100 dark:bg-gray-800/80 rounded-lg text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5 border border-gray-200 dark:border-gray-700">
              <x-heroicon-o-lock-closed class="h-3.5 w-3.5 text-gray-400" />
              {{ $activeDivision?->name ?? 'Divisi Anda' }} (Terkunci Otomatis Sesuai Akun)
            </div>
          </div>
        @endif

        <!-- Tipe Transaksi (Pills Switcher) -->
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
            Jenis Transaksi <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-2 gap-3">
            <button type="button" wire:click="$set('form_type', 'credit')" class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all {{ $form_type === 'credit' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-gray-100' }}">
              <x-heroicon-o-arrow-down-left class="h-4 w-4" />
              🟢 Uang Masuk (Credit / Setor)
            </button>
            <button type="button" wire:click="$set('form_type', 'debit')" class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all {{ $form_type === 'debit' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-gray-100' }}">
              <x-heroicon-o-arrow-up-right class="h-4 w-4" />
              🔴 Pengeluaran (Debit / Belanja)
            </button>
          </div>
        </div>

        <!-- Pilihan Dompet (Hybrid: Pilih Existing atau Buat Baru) -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-3.5 bg-gray-50/40 dark:bg-gray-800/30 space-y-2.5">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
              Dompet Alokasi (Title) <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-0.5 rounded-lg">
              <button type="button" wire:click="$set('form_wallet_mode', 'select')" class="px-2 py-0.5 rounded text-[11px] font-semibold transition-colors {{ $form_wallet_mode === 'select' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                Pilih yang Ada
              </button>
              <button type="button" wire:click="$set('form_wallet_mode', 'new')" class="px-2 py-0.5 rounded text-[11px] font-semibold transition-colors {{ $form_wallet_mode === 'new' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                + Buat Baru
              </button>
            </div>
          </div>

          @if ($form_wallet_mode === 'select')
            @if ($formWallets->isEmpty())
              <div class="text-xs text-amber-600 dark:text-amber-400 p-2 bg-amber-50 dark:bg-amber-950/40 rounded-lg border border-amber-200 dark:border-amber-800">
                Belum ada dompet di divisi ini. Silakan klik opsi <strong>"+ Buat Baru"</strong> di kanan atas untuk membuat dompet pertama.
              </div>
            @else
              <select wire:model.live="form_wallet_id" class="w-full text-xs font-medium rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">-- Pilih Dompet Alokasi --</option>
                @foreach ($formWallets as $w)
                  <option value="{{ $w->id }}">{{ $w->title }} (Saldo Riil: {{ $w->formatted_balance }})</option>
                @endforeach
              </select>
              <x-input-error for="form_wallet_id" class="mt-1 text-xs" />
            @endif
          @else
            <div>
              <input type="text" wire:model.live="form_new_wallet_title" placeholder="Ketik nama dompet baru (misal: Kas Operasional, Dana Sosial, dll)" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
              <p class="text-[11px] text-gray-400 mt-1">Dompet baru akan otomatis dibuat untuk divisi ini.</p>
              <x-input-error for="form_new_wallet_title" class="mt-1 text-xs" />
            </div>
          @endif
        </div>

        <!-- Nominal Transaksi -->
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
            Nominal Transaksi (Rp) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute left-3 top-2.5 text-xs font-bold text-gray-400">Rp</span>
            <input type="number" step="1000" min="1000" wire:model.live="form_amount" placeholder="0" class="w-full pl-10 text-sm font-bold font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
          </div>
          <!-- Quick Amount Chips -->
          <div class="flex flex-wrap gap-1.5 mt-2">
            <button type="button" wire:click="addAmount(100000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700">+100rb</button>
            <button type="button" wire:click="addAmount(500000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700">+500rb</button>
            <button type="button" wire:click="addAmount(1000000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700">+1 Jt</button>
            <button type="button" wire:click="addAmount(2500000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700">+2.5 Jt</button>
            <button type="button" wire:click="addAmount(5000000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700">+5 Jt</button>
          </div>
          <x-input-error for="form_amount" class="mt-1 text-xs" />
        </div>

        <!-- Tanggal & No Referensi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
              Tanggal Transaksi <span class="text-red-500">*</span>
            </label>
            <input type="date" wire:model.live="form_date" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
            <x-input-error for="form_date" class="mt-1 text-xs" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
              No. Referensi / Nota (Opsional)
            </label>
            <input type="text" wire:model.live="form_reference_number" placeholder="Contoh: NOTA-0812, TRF-BCA" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
            <x-input-error for="form_reference_number" class="mt-1 text-xs" />
          </div>
        </div>

        <!-- Keterangan -->
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
            Keterangan / Keperluan Transaksi <span class="text-red-500">*</span>
          </label>
          <textarea wire:model.live="form_description" rows="2" placeholder="Tuliskan keterangan detail transaksi..." class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
          <x-input-error for="form_description" class="mt-1 text-xs" />
        </div>

        <!-- Upload Bukti -->
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
            Lampirkan Bukti / Struk / Nota (Opsional, Maks 5MB)
          </label>
          <input type="file" wire:model="form_proof" accept="image/*,.pdf" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300" />
          <x-input-error for="form_proof" class="mt-1 text-xs" />
        </div>

        <!-- Auto-Approve Toggle (HANYA MUNCUL UNTUK OWNER / SUPERADMIN) -->
        @if ($isOwnerOrSuperadmin)
          <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-200 dark:border-indigo-800 flex items-center justify-between">
            <div>
              <span class="text-xs font-bold text-indigo-900 dark:text-indigo-200 block">Langsung Setujui (Auto-Approve)</span>
              <span class="text-[11px] text-indigo-700 dark:text-indigo-400">Saldo dompet divisi akan langsung bertambah/berkurang secara riil.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" wire:model.live="form_auto_approve" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
            </label>
          </div>
        @endif
      </div>
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeTransactionModal" wire:loading.attr="disabled">
          Batal
        </x-secondary-button>
        <x-button wire:click="saveTransaction" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-700">
          <span wire:loading.remove wire:target="saveTransaction">
            {{ $isOwnerOrSuperadmin && $form_auto_approve ? 'Simpan & Setujui' : 'Kirim Pengajuan' }}
          </span>
          <span wire:loading wire:target="saveTransaction">Memproses...</span>
        </x-button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- MODAL APPROVAL (KHUSUS OWNER / SUPERADMIN) -->
  <x-dialog-modal wire:model.live="showApproveModal" maxWidth="lg">
    <x-slot name="title">
      <div class="flex items-center gap-2">
        <div class="rounded-lg p-2 bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
          <x-heroicon-o-check-badge class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
            Setujui Pengajuan Transaksi Tabungan
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Verifikasi dan terapkan perubahan saldo kas divisi secara riil.
          </p>
        </div>
      </div>
    </x-slot>

    <x-slot name="content">
      @if ($approvingTx)
        <div class="space-y-4">
          @if ($errorMessage)
            <div class="p-3 rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-xs text-red-600 dark:text-red-300 font-medium">
              {{ $errorMessage }}
            </div>
          @endif

          <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/50 p-4 space-y-2.5 text-xs">
            <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2">
              <span class="text-gray-500">Kode Transaksi:</span>
              <span class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ $approvingTx->transaction_code }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Divisi Target:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $approvingTx->division?->name }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Dompet Alokasi:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $approvingTx->wallet?->title }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Jenis Transaksi:</span>
              @if ($approvingTx->is_credit)
                <span class="font-bold text-emerald-600 dark:text-emerald-400">🟢 Uang Masuk (Credit)</span>
              @else
                <span class="font-bold text-rose-600 dark:text-rose-400">🔴 Pengeluaran (Debit)</span>
              @endif
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Nominal:</span>
              <span class="font-mono text-base font-extrabold {{ $approvingTx->is_credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                {{ $approvingTx->is_credit ? '+' : '-' }}{{ $approvingTx->formatted_amount }}
              </span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-700">
              <span class="text-gray-500">Saldo Dompet Saat Ini:</span>
              <span class="font-mono font-bold text-gray-700 dark:text-gray-300">{{ $approvingTx->wallet?->formatted_balance }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Perkiraan Saldo Sesudah:</span>
              @php
                $currentBal = (float) ($approvingTx->wallet?->current_balance ?? 0);
                $estimatedBal = $approvingTx->is_credit ? ($currentBal + (float) $approvingTx->amount) : ($currentBal - (float) $approvingTx->amount);
              @endphp
              <span class="font-mono font-bold {{ $estimatedBal < 0 ? 'text-rose-600' : 'text-indigo-600 dark:text-indigo-400' }}">
                Rp {{ number_format($estimatedBal, 0, ',', '.') }}
              </span>
            </div>
          </div>

          <div class="text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 space-y-1">
            <div class="font-semibold text-gray-800 dark:text-gray-200">Keterangan Pengajuan:</div>
            <p>{{ $approvingTx->description }}</p>
            <div class="text-[11px] text-gray-400 pt-1">
              Diajukan oleh: <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $approvingTx->creator?->name }}</span>
              pada {{ $approvingTx->created_at->format('d/m/Y H:i') }} WIB
            </div>
          </div>

          @if ($approvingTx->is_debit && ((float)$approvingTx->wallet?->current_balance < (float)$approvingTx->amount))
            <div class="p-3 bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 rounded-xl text-xs text-red-700 dark:text-red-300 flex items-start gap-2">
              <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-500 shrink-0" />
              <div>
                <strong>Peringatan Saldo Kurang:</strong> Saldo dompet saat ini tidak mencukupi untuk melakukan debit sejumlah ini. Menyetujui akan gagal karena perlindungan saldo negatif (overdraft protection).
              </div>
            </div>
          @endif
        </div>
      @endif
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeApproveModal" wire:loading.attr="disabled">
          Batal
        </x-secondary-button>
        <x-button wire:click="executeApprove" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-700">
          <span wire:loading.remove wire:target="executeApprove">Ya, Setujui Transaksi</span>
          <span wire:loading wire:target="executeApprove">Menyetujui...</span>
        </x-button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- MODAL REJECTION (KHUSUS OWNER / SUPERADMIN) -->
  <x-dialog-modal wire:model.live="showRejectModal" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2">
        <div class="rounded-lg p-2 bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
          <x-heroicon-o-x-circle class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
            Tolak Pengajuan Transaksi
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Transaksi ini tidak akan memengaruhi saldo dompet divisi.
          </p>
        </div>
      </div>
    </x-slot>

    <x-slot name="content">
      @if ($rejectingTx)
        <div class="space-y-4">
          @if ($errorMessage)
            <div class="p-3 rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-xs text-red-600 dark:text-red-300 font-medium">
              {{ $errorMessage }}
            </div>
          @endif

          <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs space-y-1 border border-gray-200 dark:border-gray-700">
            <div><strong>Kode:</strong> <span class="font-mono">{{ $rejectingTx->transaction_code }}</span></div>
            <div><strong>Nominal:</strong> <span class="font-mono font-bold">{{ $rejectingTx->formatted_amount }}</span> ({{ $rejectingTx->is_credit ? 'Credit' : 'Debit' }})</div>
            <div><strong>Keterangan:</strong> {{ $rejectingTx->description }}</div>
            <div><strong>Pengaju:</strong> {{ $rejectingTx->creator?->name }}</div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
              Alasan Penolakan <span class="text-red-500">*</span>
            </label>
            <textarea wire:model.live="rejectionReason" rows="3" placeholder="Contoh: Bukti nota tidak valid, alokasi dana tidak sesuai rencana anggaran, dll..." class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-rose-500 focus:border-rose-500"></textarea>
            <x-input-error for="rejectionReason" class="mt-1 text-xs" />
          </div>
        </div>
      @endif
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeRejectModal" wire:loading.attr="disabled">
          Batal
        </x-secondary-button>
        <x-button wire:click="executeReject" wire:loading.attr="disabled" class="bg-rose-600 hover:bg-rose-700">
          <span wire:loading.remove wire:target="executeReject">Tolak Pengajuan</span>
          <span wire:loading wire:target="executeReject">Memproses...</span>
        </x-button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- MODAL 2: EDIT WALLET TITLE -->
  <x-dialog-modal wire:model.live="showEditWalletModal" maxWidth="md">
    <x-slot name="title">
      Edit Data Dompet Alokasi
    </x-slot>
    <x-slot name="content">
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
            Nama Dompet <span class="text-red-500">*</span>
          </label>
          <input type="text" wire:model.live="editingWalletTitle" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          <x-input-error for="editingWalletTitle" class="mt-1 text-xs" />
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
            Deskripsi (Opsional)
          </label>
          <textarea wire:model.live="editingWalletDescription" rows="2" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>
          <x-input-error for="editingWalletDescription" class="mt-1 text-xs" />
        </div>
      </div>
    </x-slot>
    <x-slot name="footer">
      <x-secondary-button wire:click="$set('showEditWalletModal', false)">Batal</x-secondary-button>
      <x-button wire:click="saveWalletChanges" class="ml-2 bg-indigo-600 hover:bg-indigo-700">Simpan Perubahan</x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- MODAL 3: LIGHTBOX PREVIEW BUKTI -->
  <x-dialog-modal wire:model.live="showProofModal" maxWidth="2xl">
    <x-slot name="title">
      {{ $selectedProofTitle ?? 'Bukti Transaksi' }}
    </x-slot>
    <x-slot name="content">
      <div class="text-center">
        @if ($selectedProofUrl)
          @if (str_ends_with(strtolower($selectedProofUrl), '.pdf'))
            <iframe src="{{ $selectedProofUrl }}" class="w-full h-96 rounded-lg border border-gray-300"></iframe>
          @else
            <img src="{{ $selectedProofUrl }}" alt="Bukti Transaksi" class="max-h-[75vh] mx-auto rounded-lg shadow-md object-contain" />
          @endif
        @else
          <p class="text-sm text-gray-500">Berkas bukti tidak ditemukan.</p>
        @endif
      </div>
    </x-slot>
    <x-slot name="footer">
      <x-secondary-button wire:click="closeProofModal">Tutup</x-secondary-button>
    </x-slot>
  </x-dialog-modal>
</div>
