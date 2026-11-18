<x-slot name="header">
  <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 flex items-center gap-2">
        <x-heroicon-o-wallet class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
        <span>Tabungan Divisi (Division Savings)</span>
      </h2>
    </div>
    <div class="flex items-center gap-2">
      @if ($isOwnerOrSuperadmin)
        <div class="flex items-center gap-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-1.5 shadow-xs">
          <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Divisi:</span>
          <select x-data @change="Livewire.dispatch('change-division', { id: $event.target.value })" class="text-xs font-bold border-0 bg-transparent text-indigo-600 dark:text-indigo-400 focus:ring-0 p-0 cursor-pointer">
            @foreach ($divisions as $div)
              <option value="{{ $div->id }}" {{ $selectedDivisionId == $div->id ? 'selected' : '' }}>{{ $div->name }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <x-button type="button" class="bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800" x-data @click.prevent="$dispatch('open-transaction-modal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        {{ $isOwnerOrSuperadmin ? 'Catat Transaksi' : 'Ajukan Transaksi' }}
      </x-button>

      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="pt-3.5 pb-6 sm:py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true" @open-transaction-modal.window="$wire.openTransactionModal()" @change-division.window="$wire.handleDivisionChange($event.detail.id)">

  <div class="w-full sm:px-6 lg:px-8">

    <!-- SIDEBAR FILTER -->
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Data Tabungan Divisi</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="resetFilters" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>

      <x-slot name="content">
        <div class="flex flex-col gap-6">
          @if ($isOwnerOrSuperadmin)
            <div>
              <x-label for="filter_division" value="Pilih Divisi" class="mb-1"></x-label>
              <x-select id="filter_division" class="w-full" wire:model.live="selectedDivisionId">
                @foreach ($divisions as $div)
                  <option value="{{ $div->id }}">{{ $div->name }}</option>
                @endforeach
              </x-select>
            </div>
          @endif

          <div>
            <x-label for="filter_wallet" value="Dompet Alokasi" class="mb-1"></x-label>
            <x-select id="filter_wallet" class="w-full" wire:model.live="selectedWalletId">
              <option value="">Semua Dompet Alokasi</option>
              @foreach ($wallets as $w)
                <option value="{{ $w->id }}">{{ $w->title }} ({{ $w->formatted_balance }})</option>
              @endforeach
            </x-select>
          </div>

          <div>
            <x-label for="filter_status" value="Status Persetujuan" class="mb-1"></x-label>
            <x-select id="filter_status" class="w-full" wire:model.live="selectedStatus">
              <option value="">Semua Status</option>
              <option value="pending">Menunggu Approval (Pending)</option>
              <option value="approved">Disetujui (Approved)</option>
              <option value="rejected">Ditolak (Rejected)</option>
            </x-select>
          </div>

          <div>
            <x-label for="filter_type" value="Jenis Mutasi" class="mb-1"></x-label>
            <x-select id="filter_type" class="w-full" wire:model.live="selectedType">
              <option value="">Semua Transaksi</option>
              <option value="credit">Uang Masuk (Credit)</option>
              <option value="debit">Pengeluaran (Debit)</option>
            </x-select>
          </div>

          <div>
            <x-label for="filter_month" value="Pilih Bulan" class="mb-1"></x-label>
            <x-input type="month" id="filter_month" class="w-full block" wire:model.live="selectedMonth" />
          </div>

          <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
            <x-secondary-button type="button" wire:click="resetFilters" class="w-full justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4 text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
              </svg>
              Reset Semua Filter
            </x-secondary-button>
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <!-- MAIN GLASSMORPHIC CONTAINER -->
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden p-4 sm:p-6 lg:p-8">

      <!-- 1. SCOPE DIVISI BANNER -->
      <div class="mb-5 rounded-xl bg-sky-50 dark:bg-sky-950/50 border border-sky-200 dark:border-sky-800 p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2.5">
          <div class="rounded-lg bg-sky-500 text-white p-2 shrink-0">
            <x-heroicon-s-building-office-2 class="h-5 w-5" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h4 class="text-sm font-bold text-gray-900 dark:text-white">Cakupan Data: Divisi {{ $activeDivision?->name ?? 'Divisi' }}</h4>
              @if ($isOwnerOrSuperadmin)
                <span class="inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-300 px-2 py-0.5 text-[10px] font-bold">Akses Global Owner</span>
              @else
                <span class="inline-flex items-center rounded-full bg-sky-100 dark:bg-sky-900 text-sky-800 dark:text-sky-300 px-2 py-0.5 text-[10px] font-bold">Admin Divisi</span>
              @endif
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Menampilkan saldo riil, dompet alokasi, serta riwayat mutasi kas khusus divisi Anda.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
          @if ($isOwnerOrSuperadmin)
            <div class="flex items-center gap-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-1.5 text-xs shadow-xs">
              <span class="font-semibold text-gray-500 dark:text-gray-400">Ganti:</span>
              <select wire:model.live="selectedDivisionId" class="border-0 bg-transparent text-xs font-bold text-sky-600 dark:text-sky-400 focus:ring-0 p-0 cursor-pointer">
                @foreach ($divisions as $div)
                  <option value="{{ $div->id }}">{{ $div->name }}</option>
                @endforeach
              </select>
            </div>
          @endif
          <x-button type="button" wire:click="openTransactionModal" class="bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 shadow-xs text-xs font-semibold cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ $isOwnerOrSuperadmin ? 'Catat Transaksi' : 'Ajukan Transaksi' }}
          </x-button>
        </div>
      </div>

      <!-- 2. FLASH / ERROR BANNER -->
      @if ($errorMessage)
        <div class="mb-5 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/50 p-3.5 text-sm text-red-700 dark:text-red-300 flex items-start gap-3 shadow-xs">
          <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
          <div class="flex-1 font-medium">{{ $errorMessage }}</div>
          <button type="button" wire:click="$set('errorMessage', '')" class="text-red-400 hover:text-red-600">
            <x-heroicon-o-x-mark class="h-5 w-5" />
          </button>
        </div>
      @endif

      <!-- 3. PENDING APPROVAL ALERT BANNER -->
      @if ($summary['pending_count'] > 0)
        <div class="mb-5 rounded-xl border border-amber-300 dark:border-amber-700/70 bg-gradient-to-r from-amber-50 via-amber-100/40 to-orange-50 dark:from-amber-950/40 dark:via-amber-900/20 dark:to-orange-950/30 p-3.5 sm:p-4 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div class="flex items-start gap-3">
            <div class="h-9 w-9 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs animate-pulse">
              <x-heroicon-o-clock class="h-5 w-5" />
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
              <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                Dana tidak akan bertambah atau berkurang sampai diverifikasi dan disetujui langsung oleh Owner.
                <span class="font-semibold ml-1">
                  Est. Kredit: <span class="font-mono text-emerald-600 dark:text-emerald-400">+Rp {{ number_format($summary['pending_credit'], 0, ',', '.') }}</span> |
                  Est. Debit: <span class="font-mono text-rose-600 dark:text-rose-400">-Rp {{ number_format($summary['pending_debit'], 0, ',', '.') }}</span>
                </span>
              </p>
            </div>
          </div>

          <button type="button" wire:click="$set('selectedStatus', 'pending')" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition-all whitespace-nowrap">
            <span>Tinjau Pending</span>
            <x-heroicon-o-arrow-right class="h-3.5 w-3.5" />
          </button>
        </div>
      @endif

      <!-- 4. SUMMARY METRIC CARDS -->
      <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- 1. Saldo Riil Divisi (Approved Real Balance) -->
        <div class="overflow-hidden rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/50 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between gap-1">
              <dt class="truncate text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                Saldo Riil Divisi
              </dt>
              <span class="inline-flex items-center rounded-md bg-indigo-100 dark:bg-indigo-900/60 px-1.5 py-0.5 text-[10px] font-bold text-indigo-800 dark:text-indigo-300 shrink-0">
                {{ $summary['total_wallets_count'] }} Dompet
              </span>
            </div>
            <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-indigo-950 dark:text-indigo-100">
              Rp {{ number_format($summary['total_balance'], 0, ',', '.') }}
            </dd>
            <div class="mt-2.5 space-y-1 text-xs">
              <div class="flex items-center gap-1.5 font-bold text-emerald-600 dark:text-emerald-400">
                <span class="flex h-3.5 w-3.5 items-center justify-center rounded-full bg-emerald-600 text-white text-[9px] font-black shrink-0">↓</span>
                <span>+ Rp {{ number_format($summary['total_credit_month'], 0, ',', '.') }}</span>
              </div>
              <div class="flex items-center gap-1.5 font-bold text-rose-600 dark:text-rose-400">
                <span class="flex h-3.5 w-3.5 items-center justify-center rounded-full bg-rose-600 text-white text-[9px] font-black shrink-0">↑</span>
                <span>- Rp {{ number_format($summary['total_debit_month'], 0, ',', '.') }}</span>
              </div>
            </div>
          </div>
          <div class="mt-2 flex items-center gap-1.5 text-[11px] text-indigo-700/80 dark:text-indigo-300/80 truncate">
            <span>{{ $summary['period_label'] }}</span>
            <span>• Divisi {{ $activeDivision?->name ?? 'Divisi' }}</span>
          </div>
        </div>

        <!-- 2. Pemasukan Disetujui (Credit Bulan Ini) -->
        <div class="overflow-hidden rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between gap-1">
              <dt class="truncate text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                Pemasukan Disetujui
              </dt>
              @if ($selectedMonth)
                <span class="inline-flex items-center rounded-md bg-emerald-100 dark:bg-emerald-900/60 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 dark:text-emerald-300 shrink-0">
                  {{ \Carbon\Carbon::parse($selectedMonth . '-01')->translatedFormat('M Y') }}
                </span>
              @endif
            </div>
            <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-emerald-950 dark:text-emerald-100">
              + Rp {{ number_format($summary['total_credit_month'], 0, ',', '.') }}
            </dd>
            <div class="mt-2.5 flex items-center gap-2 p-2 rounded-lg bg-emerald-100/60 dark:bg-emerald-950/60 border border-emerald-200/80 dark:border-emerald-800/60 text-xs">
              <span class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-white text-[10px] font-black shrink-0">↓</span>
              <span class="font-semibold text-emerald-800 dark:text-emerald-300">Dana Masuk Terverifikasi</span>
            </div>
          </div>
          <div class="mt-2 text-[11px] text-emerald-700/80 dark:text-emerald-300/80 truncate">
            Periode: {{ $summary['period_label'] }}
          </div>
        </div>

        <!-- 3. Pengeluaran Disetujui (Debit Bulan Ini) -->
        <div class="overflow-hidden rounded-xl bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between gap-1">
              <dt class="truncate text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                Pengeluaran Disetujui
              </dt>
              @if ($selectedMonth)
                <span class="inline-flex items-center rounded-md bg-rose-100 dark:bg-rose-900/60 px-1.5 py-0.5 text-[10px] font-bold text-rose-800 dark:text-rose-300 shrink-0">
                  {{ \Carbon\Carbon::parse($selectedMonth . '-01')->translatedFormat('M Y') }}
                </span>
              @endif
            </div>
            <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-rose-950 dark:text-rose-100">
              - Rp {{ number_format($summary['total_debit_month'], 0, ',', '.') }}
            </dd>
            <div class="mt-2.5 flex items-center gap-2 p-2 rounded-lg bg-rose-100/60 dark:bg-rose-950/60 border border-rose-200/80 dark:border-rose-800/60 text-xs">
              <span class="flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-white text-[10px] font-black shrink-0">↑</span>
              <span class="font-semibold text-rose-800 dark:text-rose-300">Beban Mutasi Terverifikasi</span>
            </div>
          </div>
          <div class="mt-2 text-[11px] text-rose-700/80 dark:text-rose-300/80 truncate">
            Periode: {{ $summary['period_label'] }}
          </div>
        </div>

        <!-- 4. Antrean Pending Approval -->
        <div class="overflow-hidden rounded-xl {{ $summary['pending_count'] > 0 ? 'bg-amber-50/90 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700/60 ring-2 ring-amber-400/40' : 'bg-gray-50/80 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700' }} border p-4 sm:p-5 shadow-xs flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between gap-1">
              <dt class="truncate text-xs font-semibold uppercase tracking-wider {{ $summary['pending_count'] > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-600 dark:text-gray-400' }}">
                Antrean Approval
              </dt>
              @if ($summary['pending_count'] > 0)
                <span class="inline-flex items-center rounded-full bg-amber-200/90 px-2 py-0.5 text-xs font-bold text-amber-900 dark:bg-amber-900/80 dark:text-amber-200 animate-pulse shrink-0">
                  {{ $summary['pending_count'] }} Pending
                </span>
              @else
                <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 text-[10px] font-bold text-gray-500 shrink-0">
                  0 Pending
                </span>
              @endif
            </div>
            <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight {{ $summary['pending_count'] > 0 ? 'text-amber-950 dark:text-amber-100' : 'text-gray-700 dark:text-gray-300' }}">
              Rp {{ number_format($summary['pending_credit'] + $summary['pending_debit'], 0, ',', '.') }}
            </dd>
            <div class="mt-2.5 space-y-1 text-xs">
              <div class="flex items-center justify-between font-semibold text-emerald-600 dark:text-emerald-400">
                <span>Est. Masuk:</span>
                <span>+Rp {{ number_format($summary['pending_credit'], 0, ',', '.') }}</span>
              </div>
              <div class="flex items-center justify-between font-semibold text-rose-600 dark:text-rose-400">
                <span>Est. Keluar:</span>
                <span>-Rp {{ number_format($summary['pending_debit'], 0, ',', '.') }}</span>
              </div>
            </div>
          </div>
          <div class="mt-2 text-[11px] {{ $summary['pending_count'] > 0 ? 'text-amber-800/80 dark:text-amber-300/80' : 'text-gray-500 dark:text-gray-400' }} truncate">
            {{ $summary['pending_count'] }} Transaksi Menunggu Approval
          </div>
        </div>
      </div>

      <!-- 5. DAFTAR DOMPET ALOKASI DANA (WALLETS GRID) -->
      <div class="mb-6 rounded-xl border border-gray-200/90 dark:border-gray-700/80 bg-white/60 dark:bg-gray-800/60 p-4 sm:p-5 shadow-2xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-gray-200 dark:border-gray-700/60 pb-3 mb-4">
          <div class="flex items-center gap-2.5">
            <div class="rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 p-2 shrink-0">
              <x-heroicon-o-folder class="h-5 w-5" />
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span>Daftar Dompet Alokasi Dana ({{ $wallets->count() }})</span>
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                Dompet kas terisolasi untuk <strong>{{ $activeDivision?->name ?? 'Divisi' }}</strong>. Saldo di bawah adalah saldo riil FIX (hanya dari transaksi yang telah disetujui Owner).
              </p>
            </div>
          </div>

          <button type="button" wire:click="openTransactionModal(null, 'credit')" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 px-3 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800/60 transition shadow-2xs">
            <x-heroicon-o-plus class="h-4 w-4" />
            <span>Buat Dompet Baru</span>
          </button>
        </div>

        @if ($wallets->isEmpty())
          <div class="text-center py-8">
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
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            @foreach ($wallets as $wallet)
              <div class="group relative rounded-xl border border-gray-200 dark:border-gray-700/80 bg-white dark:bg-gray-800/90 hover:bg-gray-50/70 dark:hover:bg-gray-800 p-4 transition duration-150 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 flex flex-col justify-between">
                <div>
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <div class="rounded-lg bg-indigo-100/70 dark:bg-indigo-900/50 p-2 text-indigo-600 dark:text-indigo-400 group-hover:scale-105 transition-transform">
                        <x-heroicon-o-wallet class="h-5 w-5" />
                      </div>
                      <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                          {{ $wallet->title }}
                        </h4>
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                          <span>{{ $wallet->approved_transactions_count }} disetujui</span>
                          @if ($wallet->pending_transactions_count > 0)
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                              {{ $wallet->pending_transactions_count }} pending
                            </span>
                          @endif
                        </div>
                      </div>
                    </div>
                    <button type="button" wire:click="openEditWalletModal('{{ $wallet->id }}')" title="Edit nama dompet" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                      <x-heroicon-o-pencil-square class="h-4 w-4" />
                    </button>
                  </div>

                  @if ($wallet->description)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                      {{ $wallet->description }}
                    </p>
                  @endif

                  <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                    <span class="text-[10px] font-semibold tracking-wider uppercase text-gray-400">Saldo Riil (FIX)</span>
                    <div class="text-xl font-black font-mono tracking-tight text-gray-900 dark:text-gray-100">
                      {{ $wallet->formatted_balance }}
                    </div>
                  </div>
                </div>

                <div class="mt-4 pt-2 flex items-center gap-2">
                  <button type="button" wire:click="openTransactionModal('{{ $wallet->id }}', 'credit')" class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 px-2.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 border border-emerald-200 dark:border-emerald-800 transition-colors cursor-pointer">
                    <x-heroicon-o-plus class="h-3.5 w-3.5" />
                    Isi Kas
                  </button>
                  <button type="button" wire:click="openTransactionModal('{{ $wallet->id }}', 'debit')" class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 px-2.5 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900 border border-rose-200 dark:border-rose-800 transition-colors cursor-pointer">
                    <x-heroicon-o-minus class="h-3.5 w-3.5" />
                    Keluar
                  </button>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <!-- 6. MUTASI REKENING / BUKU KAS (MUTATION LEDGER) -->
      <div>
        <!-- Status Filter Tabs & Search Bar (Bersebelahan) -->
        <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <!-- Status Tabs -->
          <div class="inline-flex items-center rounded-xl bg-gray-100 dark:bg-gray-900 p-1 text-xs font-medium overflow-x-auto shrink-0">
            <button type="button" 
                    wire:click="$set('selectedStatus', '')" 
                    class="rounded-lg px-3 py-1.5 transition-colors whitespace-nowrap {{ $selectedStatus === '' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
              Semua
              <span class="ml-1 text-[10px] text-gray-400">({{ $statusCounts['all'] }})</span>
            </button>
            <button type="button" 
                    wire:click="$set('selectedStatus', 'pending')" 
                    class="relative rounded-lg px-3 py-1.5 transition-colors whitespace-nowrap {{ $selectedStatus === 'pending' ? 'bg-amber-500 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400' }}">
              Menunggu
              @if ($statusCounts['pending'] > 0)
                <span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.2 text-[10px] font-bold text-amber-800 {{ $selectedStatus === 'pending' ? 'bg-white text-amber-900' : '' }}">
                  {{ $statusCounts['pending'] }}
                </span>
              @endif
            </button>
            <button type="button" 
                    wire:click="$set('selectedStatus', 'approved')" 
                    class="rounded-lg px-3 py-1.5 transition-colors whitespace-nowrap {{ $selectedStatus === 'approved' ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400' }}">
              Disetujui
              <span class="ml-1 text-[10px] opacity-80">({{ $statusCounts['approved'] }})</span>
            </button>
            <button type="button" 
                    wire:click="$set('selectedStatus', 'rejected')" 
                    class="rounded-lg px-3 py-1.5 transition-colors whitespace-nowrap {{ $selectedStatus === 'rejected' ? 'bg-rose-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-400' }}">
              Ditolak
              <span class="ml-1 text-[10px] opacity-80">({{ $statusCounts['rejected'] }})</span>
            </button>

            <span class="h-4 w-px bg-gray-300 dark:bg-gray-700 mx-1"></span>

            <button type="button" 
                    wire:click="$set('selectedType', '{{ $selectedType === 'debit' ? '' : 'debit' }}')" 
                    class="rounded-lg px-3 py-1.5 transition-colors whitespace-nowrap flex items-center gap-1 {{ $selectedType === 'debit' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-400' }}"
                    title="Filter khusus mutasi Pengeluaran (Debit)">
              <span>Pengeluaran</span>
            </button>
            <button type="button" 
                    wire:click="$set('selectedType', '{{ $selectedType === 'credit' ? '' : 'credit' }}')" 
                    class="rounded-lg px-3 py-1.5 transition-colors whitespace-nowrap flex items-center gap-1 {{ $selectedType === 'credit' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400' }}"
                    title="Filter khusus mutasi Uang Masuk (Credit)">
              <span>Uang Masuk</span>
            </button>
          </div>

          <!-- Search Bar & Filter Sidebar Trigger -->
          <div class="flex items-center gap-2 flex-1 min-w-[220px]">
            <div class="relative flex-1">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <x-input type="text" class="block w-full pl-9 pr-8 text-xs sm:text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Kode, Deskripsi, No Ref..." />
              @if ($search)
                <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none">
                  <x-heroicon-o-x-mark class="size-4" />
                </button>
              @endif
            </div>

            <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')" class="shadow-2xs text-xs font-bold py-2 px-3 whitespace-nowrap">
              <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
              Filter
            </x-secondary-button>
          </div>
        </div>

        <!-- Transactions Table (Desktop & Tablet) -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-700/50 uppercase tracking-wider text-[11px] text-gray-500 dark:text-gray-400">
              <tr>
                <th scope="col" class="py-3 px-3.5 font-bold text-left">Tanggal & Kode</th>
                <th scope="col" class="py-3 px-3.5 font-bold text-left">Dompet Alokasi</th>
                <th scope="col" class="py-3 px-3.5 font-bold text-center">Tipe</th>
                <th scope="col" class="py-3 px-3.5 font-bold text-center">Status</th>
                <th scope="col" class="py-3 px-3.5 font-bold text-right">Nominal</th>
                <th scope="col" class="py-3 px-3.5 font-bold text-right">Saldo Sesudah</th>
                <th scope="col" class="py-3 px-3.5 font-bold text-left">Keterangan & Audit</th>
                <th scope="col" class="py-3 px-3.5 font-bold text-center">Aksi / Bukti</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
              @forelse ($transactions as $tx)
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                  <!-- Tanggal & Kode -->
                  <td class="py-3 px-3.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">
                      {{ $tx->transaction_date->format('d/m/Y') }}
                    </div>
                    <div class="text-[11px] font-mono text-gray-400">
                      {{ $tx->transaction_code }}
                    </div>
                  </td>

                  <!-- Dompet -->
                  <td class="py-3 px-3.5 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                      <x-heroicon-o-folder class="h-3.5 w-3.5" />
                      {{ $tx->wallet?->title ?? 'Dompet Dihapus' }}
                    </span>
                  </td>

                  <!-- Tipe -->
                  <td class="py-3 px-3.5 text-center whitespace-nowrap">
                    @if ($tx->is_credit)
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                        <x-heroicon-o-arrow-down-left class="h-3 w-3" />
                        Credit (Masuk)
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                        <x-heroicon-o-arrow-up-right class="h-3 w-3" />
                        Debit (Keluar)
                      </span>
                    @endif
                  </td>

                  <!-- Status Approval -->
                  <td class="py-3 px-3.5 text-center whitespace-nowrap">
                    @if ($tx->status === 'approved')
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                        Disetujui
                      </span>
                    @elseif ($tx->status === 'rejected')
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                        Ditolak
                      </span>
                    @else
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800 animate-pulse">
                        Menunggu
                      </span>
                    @endif
                  </td>

                  <!-- Nominal -->
                  <td class="py-3 px-3.5 text-right whitespace-nowrap font-mono font-bold {{ $tx->is_credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $tx->is_credit ? '+' : '-' }}{{ $tx->formatted_amount }}
                  </td>

                  <!-- Saldo Sesudah (FIX) -->
                  <td class="py-3 px-3.5 text-right whitespace-nowrap font-mono font-semibold">
                    @if ($tx->status === 'approved')
                      <span class="text-gray-900 dark:text-white">{{ $tx->formatted_balance_after }}</span>
                    @elseif ($tx->status === 'pending')
                      <span class="text-[11px] text-amber-600 dark:text-amber-400 italic font-sans font-medium">Menunggu Approval</span>
                    @else
                      <span class="text-[11px] text-gray-400 italic font-sans">Dibatalkan</span>
                    @endif
                  </td>

                  <!-- Keterangan & Audit Trail -->
                  <td class="py-3 px-3.5 max-w-xs">
                    <div class="font-medium text-gray-800 dark:text-gray-200 truncate" title="{{ $tx->description }}">
                      {{ $tx->description }}
                    </div>
                    <div class="text-[11px] text-gray-400 mt-0.5">
                      Oleh: <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $tx->creator?->name ?? 'System' }}</span>
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
                  <td class="py-3 px-3.5 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                      @if ($tx->status === 'pending')
                        @if ($isOwnerOrSuperadmin)
                          <button type="button" wire:click="openApproveModal('{{ $tx->id }}')" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors cursor-pointer" title="Setujui transaksi">
                            <x-heroicon-s-check class="h-3 w-3" />
                            Setujui
                          </button>
                          <button type="button" wire:click="openRejectModal('{{ $tx->id }}')" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition-colors cursor-pointer" title="Tolak transaksi">
                            <x-heroicon-s-x-mark class="h-3 w-3" />
                            Tolak
                          </button>
                        @elseif ($tx->created_by === Auth::id())
                          <button type="button" wire:click="cancelTransaction('{{ $tx->id }}')" wire:confirm="Batalkan pengajuan tabungan ini?" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors cursor-pointer" title="Batalkan pengajuan saya">
                            <x-heroicon-o-trash class="h-3 w-3 text-red-500" />
                            Batal
                          </button>
                        @endif
                      @endif

                      @if ($tx->proof_attachment_path)
                        <button type="button" wire:click="openProofModal('{{ $tx->id }}')" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-semibold bg-gray-100 hover:bg-indigo-50 dark:bg-gray-800 dark:hover:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-gray-300 dark:border-gray-700 transition-colors cursor-pointer" title="Lihat bukti nota">
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
          <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
            {{ $transactions->links() }}
          </div>
        @endif
      </div>

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
            <button type="button" wire:click="$set('form_type', 'credit')" class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer {{ $form_type === 'credit' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-gray-100' }}">
              <x-heroicon-o-arrow-down-left class="h-4 w-4" />
              🟢 Uang Masuk (Credit / Setor)
            </button>
            <button type="button" wire:click="$set('form_type', 'debit')" class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer {{ $form_type === 'debit' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-gray-100' }}">
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
              <button type="button" wire:click="$set('form_wallet_mode', 'select')" class="px-2 py-0.5 rounded text-[11px] font-semibold transition-colors cursor-pointer {{ $form_wallet_mode === 'select' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                Pilih yang Ada
              </button>
              <button type="button" wire:click="$set('form_wallet_mode', 'new')" class="px-2 py-0.5 rounded text-[11px] font-semibold transition-colors cursor-pointer {{ $form_wallet_mode === 'new' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
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
            <button type="button" wire:click="addAmount(100000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700 cursor-pointer">+100rb</button>
            <button type="button" wire:click="addAmount(500000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700 cursor-pointer">+500rb</button>
            <button type="button" wire:click="addAmount(1000000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700 cursor-pointer">+1 Jt</button>
            <button type="button" wire:click="addAmount(2500000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700 cursor-pointer">+2.5 Jt</button>
            <button type="button" wire:click="addAmount(5000000)" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 border border-gray-200 dark:border-gray-700 cursor-pointer">+500 Jt</button>
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
