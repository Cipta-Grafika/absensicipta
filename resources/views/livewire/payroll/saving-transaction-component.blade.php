<x-slot name="header">
  <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Mutasi & Pengajuan Syirkah
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola mutasi buku kas syirkah dan persetujuan pengajuan penarikan dana karyawan</p>
    </div>
    <div class="flex items-center gap-2">
      <x-button type="button" x-data @click.prevent="$dispatch('open-withdrawal-modal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Pencairan Langsung
      </x-button>
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="pt-3.5 pb-6 sm:py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="w-full sm:px-6 lg:px-8">

    <!-- SIDEBAR FILTER -->
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Data Syirkah</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('statusFilter', ''); $set('month', ''); $set('type', ''); $set('division', ''); $set('withdrawalStatusFilter', ''); $set('withdrawalMonth', ''); $set('withdrawalDivision', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          @if($activeTab === 'transactions')
            <div>
              <x-label for="status_filter" value="Status Persetujuan Mutasi" class="mb-1"></x-label>
              <x-select id="status_filter" class="w-full" wire:model.live="statusFilter">
                <option value="">Semua Status</option>
                <option value="pending">Menunggu Persetujuan (Pending)</option>
                <option value="approved">Disetujui (Approved)</option>
                <option value="rejected">Ditolak (Rejected)</option>
              </x-select>
            </div>

            <div>
              <x-label for="month_filter" value="Pilih Bulan" class="mb-1"></x-label>
              <x-input type="month" id="month_filter" class="w-full block" wire:model.live="month" />
            </div>

            <div>
              <x-label for="type_filter" value="Jenis Mutasi" class="mb-1"></x-label>
              <x-select id="type_filter" class="w-full" wire:model.live="type">
                <option value="">Semua Transaksi</option>
                <option value="deposit">Penambahan (Deposit)</option>
                <option value="withdrawal">Pencairan (Withdrawal)</option>
              </x-select>
            </div>

            <div>
              <x-label for="division_filter" value="Divisi" class="mb-1"></x-label>
              <x-select id="division_filter" class="w-full" wire:model.live="division">
                <option value="">Semua Divisi</option>
                @foreach ($divisionsList as $div)
                  <option value="{{ $div->id }}">{{ $div->name }}</option>
                @endforeach
              </x-select>
            </div>
          @else
            <div>
              <x-label for="wd_status_filter" value="Status Pengajuan Penarikan" class="mb-1"></x-label>
              <x-select id="wd_status_filter" class="w-full" wire:model.live="withdrawalStatusFilter">
                <option value="">Semua Status</option>
                <option value="pending">PENDING (Menunggu Persetujuan)</option>
                <option value="accepted">ACCEPTED (Disetujui, Belum Bayar)</option>
                <option value="paid">PAID (Selesai Dibayar)</option>
                <option value="rejected">REJECTED (Ditolak)</option>
              </x-select>
            </div>

            <div>
              <x-label for="wd_month_filter" value="Pilih Bulan Pengajuan" class="mb-1"></x-label>
              <x-input type="month" id="wd_month_filter" class="w-full block" wire:model.live="withdrawalMonth" />
            </div>

            <div>
              <x-label for="wd_division_filter" value="Divisi Karyawan" class="mb-1"></x-label>
              <x-select id="wd_division_filter" class="w-full" wire:model.live="withdrawalDivision">
                <option value="">Semua Divisi</option>
                @foreach ($divisionsList as $div)
                  <option value="{{ $div->id }}">{{ $div->name }}</option>
                @endforeach
              </x-select>
            </div>
          @endif
        </div>
      </x-slot>
    </x-filter-sidebar>

    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden p-4 sm:p-6 lg:p-8">
      
      <!-- 1. SUMMARY CARDS -->
      <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Saldo Wajib -->
        <div class="overflow-hidden rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/50 p-4 sm:p-5 shadow-xs">
          <dt class="truncate text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Total Saldo Wajib (Disetujui)</dt>
          <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-indigo-950 dark:text-indigo-100">Rp {{ number_format($totalWajib, 0, ',', '.') }}</dd>
        </div>

        <!-- Saldo Sukarela -->
        <div class="overflow-hidden rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 p-4 sm:p-5 shadow-xs">
          <dt class="truncate text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Saldo Sukarela (Disetujui)</dt>
          <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-emerald-950 dark:text-emerald-100">Rp {{ number_format($totalSukarela, 0, ',', '.') }}</dd>
        </div>

        <!-- Antrean Pengajuan Penarikan -->
        <div 
          wire:click="setActiveTab('withdrawals')"
          class="cursor-pointer overflow-hidden rounded-xl {{ $pendingWithdrawalsCount > 0 ? 'bg-amber-50/90 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700/60 ring-2 ring-amber-400/40' : 'bg-gray-50/80 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700' }} border p-4 sm:p-5 shadow-xs transition hover:scale-[1.01]"
        >
          <div class="flex items-center justify-between">
            <dt class="truncate text-xs font-semibold uppercase tracking-wider {{ $pendingWithdrawalsCount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-600 dark:text-gray-400' }}">
              Antrean Pengajuan Tarik
            </dt>
            @if($pendingWithdrawalsCount > 0)
              <span class="inline-flex items-center rounded-full bg-amber-200/90 px-2 py-0.5 text-xs font-bold text-amber-900 dark:bg-amber-900/80 dark:text-amber-200 animate-pulse">
                {{ $pendingWithdrawalsCount }} Baru
              </span>
            @endif
          </div>
          <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight {{ $pendingWithdrawalsCount > 0 ? 'text-amber-950 dark:text-amber-100' : 'text-gray-700 dark:text-gray-300' }}">
            Rp {{ number_format($pendingWithdrawalsNominal, 0, ',', '.') }}
          </dd>
        </div>

        <!-- Pengajuan Disetujui (Belum Bayar) -->
        <div 
          wire:click="setActiveTab('withdrawals')"
          class="cursor-pointer overflow-hidden rounded-xl {{ $acceptedWithdrawalsCount > 0 ? 'bg-blue-50/90 dark:bg-blue-950/40 border-blue-300 dark:border-blue-700/60' : 'bg-gray-50/80 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700' }} border p-4 sm:p-5 shadow-xs transition hover:scale-[1.01]"
        >
          <div class="flex items-center justify-between">
            <dt class="truncate text-xs font-semibold uppercase tracking-wider {{ $acceptedWithdrawalsCount > 0 ? 'text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400' }}">
              Disetujui (Belum Bayar)
            </dt>
            @if($acceptedWithdrawalsCount > 0)
              <span class="inline-flex items-center rounded-full bg-blue-200 px-2 py-0.5 text-xs font-bold text-blue-900 dark:bg-blue-900/80 dark:text-blue-200">
                {{ $acceptedWithdrawalsCount }} ACC
              </span>
            @endif
          </div>
          <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight {{ $acceptedWithdrawalsCount > 0 ? 'text-blue-950 dark:text-blue-100' : 'text-gray-700 dark:text-gray-300' }}">
            {{ $acceptedWithdrawalsCount }} Pengajuan
          </dd>
        </div>
      </div>

      <!-- 2. PRIMARY NAVIGATION TABS (MUTASI VS PENGAJUAN PENARIKAN) -->
      <div class="mb-5 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-4 sm:space-x-8">
          <!-- Tab 1: Mutasi Rekening Syirkah -->
          <button
            type="button"
            wire:click="setActiveTab('transactions')"
            class="whitespace-nowrap py-3 px-1 border-b-2 font-bold text-xs sm:text-sm flex items-center gap-2 transition {{ $activeTab === 'transactions' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}"
          >
            <x-heroicon-o-queue-list class="h-4 w-4 sm:h-5 sm:w-5" />
            <span>Mutasi Rekening (Buku Kas)</span>
            @if($pendingCount > 0)
              <span class="rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 px-2 py-0.5 text-[11px] font-bold">
                {{ $pendingCount }}
              </span>
            @endif
          </button>

          <!-- Tab 2: Pengajuan Penarikan Karyawan -->
          <button
            type="button"
            wire:click="setActiveTab('withdrawals')"
            class="whitespace-nowrap py-3 px-1 border-b-2 font-bold text-xs sm:text-sm flex items-center gap-2 transition {{ $activeTab === 'withdrawals' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}"
          >
            <x-heroicon-o-arrow-up-tray class="h-4 w-4 sm:h-5 sm:w-5" />
            <span>Pengajuan Penarikan Karyawan</span>
            @if($pendingWithdrawalsCount > 0)
              <span class="rounded-full bg-amber-500 text-white px-2 py-0.5 text-[11px] font-extrabold animate-pulse">
                {{ $pendingWithdrawalsCount }} Baru
              </span>
            @endif
          </button>
        </nav>
      </div>

      <!-- =========================================================================
           TAB 1: MUTASI REKENING (BUKU KAS)
           ========================================================================= -->
      @if($activeTab === 'transactions')
        <!-- Status Filter Tabs & Search Bar -->
        <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <!-- Status Tabs -->
          <div class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1 text-xs font-medium">
            <button type="button" 
                    wire:click="$set('statusFilter', '')" 
                    class="rounded-md px-3 py-1.5 transition-colors {{ $statusFilter === '' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
              Semua
            </button>
            <button type="button" 
                    wire:click="$set('statusFilter', 'pending')" 
                    class="relative rounded-md px-3 py-1.5 transition-colors {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400' }}">
              Menunggu
              @if($pendingCount > 0)
                <span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.2 text-[10px] font-bold text-amber-800 {{ $statusFilter === 'pending' ? 'bg-white text-amber-900' : '' }}">
                  {{ $pendingCount }}
                </span>
              @endif
            </button>
            <button type="button" 
                    wire:click="$set('statusFilter', 'approved')" 
                    class="rounded-md px-3 py-1.5 transition-colors {{ $statusFilter === 'approved' ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400' }}">
              Disetujui
            </button>
            <button type="button" 
                    wire:click="$set('statusFilter', 'rejected')" 
                    class="rounded-md px-3 py-1.5 transition-colors {{ $statusFilter === 'rejected' ? 'bg-rose-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-400' }}">
              Ditolak
            </button>
          </div>

          <!-- Search Bar -->
          <div class="relative flex-1 sm:max-w-xs">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-9 pr-8 text-xs sm:text-sm" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Karyawan, NIP..." />
            @if ($search)
              <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 focus:outline-none">
                <x-heroicon-o-x-mark class="size-4" />
              </button>
            @endif
          </div>
        </div>

        <!-- Bulk Actions Bar -->
        @if(count($selectedTransactions) > 0)
          <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 p-3 text-xs">
            <div class="flex items-center gap-2 font-medium text-amber-900 dark:text-amber-200">
              <span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-white font-bold text-[11px]">
                {{ count($selectedTransactions) }}
              </span>
              <span>Transaksi Dipilih</span>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" wire:click="bulkApprove" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 font-semibold text-white hover:bg-emerald-700">
                <x-heroicon-o-check class="h-4 w-4" /> Setujui Semua
              </button>
              <button type="button" wire:click="openBulkRejectModal" class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-2.5 py-1.5 font-semibold text-white hover:bg-rose-700">
                <x-heroicon-o-x-mark class="h-4 w-4" /> Tolak Semua
              </button>
              <button type="button" wire:click="openBulkDeleteModal" class="inline-flex items-center gap-1 rounded-lg bg-gray-600 px-2.5 py-1.5 font-semibold text-white hover:bg-gray-700">
                <x-heroicon-o-trash class="h-4 w-4" /> Hapus Semua
              </button>
            </div>
          </div>
        @endif

        <!-- Transactions Table (Desktop & Tablet) -->
        <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-700/50 uppercase tracking-wider text-[11px] text-gray-500 dark:text-gray-400">
              <tr>
                <th scope="col" class="py-3 px-3 w-8 text-center">
                  <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                </th>
                <th scope="col" class="py-3 px-3 font-bold text-left">Tanggal</th>
                <th scope="col" class="py-3 px-3 font-bold text-left">Karyawan</th>
                <th scope="col" class="py-3 px-3 font-bold text-left">Keterangan</th>
                <th scope="col" class="py-3 px-3 font-bold text-right">Syirkah Wajib</th>
                <th scope="col" class="py-3 px-3 font-bold text-right">Sukarela (SSR)</th>
                <th scope="col" class="py-3 px-3 font-bold text-right">Nominal</th>
                <th scope="col" class="py-3 px-3 font-bold text-center">Status</th>
                <th scope="col" class="py-3 px-3 font-bold text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
              @forelse ($transactions as $tx)
                @php
                  $isDep = $tx->transaction_type === 'deposit';
                  $totNom = (float) ($tx->mandatory_amount + $tx->secondary_amount);
                @endphp
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                  <td class="py-3 px-3 text-center">
                    <input type="checkbox" value="{{ $tx->id }}" wire:model.live="selectedTransactions" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                  </td>
                  <td class="py-3 px-3 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">
                      {{ \Carbon\Carbon::parse($tx->created_at)->translatedFormat('d M Y') }}
                    </div>
                    <div class="text-[11px] text-gray-400">
                      {{ \Carbon\Carbon::parse($tx->created_at)->format('H:i') }} WIB
                    </div>
                  </td>
                  <td class="py-3 px-3 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $tx->user->name ?? '-' }}</div>
                    <div class="text-[11px] text-gray-400">NIP: {{ $tx->user->nip ?? '-' }} • {{ $tx->user->division->name ?? '-' }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $tx->description ?: ($isDep ? 'Setoran Syirkah' : 'Pencairan Syirkah') }}</div>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold {{ $isDep ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' }}">
                      {{ $isDep ? 'Setoran (Kredit)' : 'Penarikan (Debit)' }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-right whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                    Rp {{ number_format($tx->mandatory_amount, 0, ',', '.') }}
                  </td>
                  <td class="py-3 px-3 text-right whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                    Rp {{ number_format($tx->secondary_amount, 0, ',', '.') }}
                  </td>
                  <td class="py-3 px-3 text-right whitespace-nowrap font-extrabold {{ $isDep ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $isDep ? '+' : '-' }} Rp {{ number_format($totNom, 0, ',', '.') }}
                  </td>
                  <td class="py-3 px-3 text-center whitespace-nowrap">
                    @if($tx->status === 'approved')
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                        Disetujui
                      </span>
                    @elseif($tx->status === 'pending')
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800 animate-pulse">
                        Menunggu
                      </span>
                    @else
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800" title="{{ $tx->rejection_reason }}">
                        Ditolak
                      </span>
                    @endif
                  </td>
                  <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1">
                      @if($tx->status === 'pending')
                        <button type="button" wire:click="approve('{{ $tx->id }}')" class="p-1 rounded text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40" title="Setujui Mutasi">
                          <x-heroicon-o-check class="h-4 w-4" />
                        </button>
                        <button type="button" wire:click="openRejectModal('{{ $tx->id }}')" class="p-1 rounded text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Tolak Mutasi">
                          <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                      @endif

                      @if(Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin || Auth::user()?->isOwner)
                        <button type="button" wire:click="openEditNominalModal('{{ $tx->id }}')" class="p-1 rounded text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40" title="Edit Nominal">
                          <x-heroicon-o-pencil-square class="h-4 w-4" />
                        </button>
                        <button type="button" wire:click="openDeleteModal('{{ $tx->id }}')" class="p-1 rounded text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Hapus Permanen">
                          <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="py-8 text-center text-gray-400 dark:text-gray-500">
                    Tidak ada data mutasi syirkah ditemukan.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- Mobile Transactions List -->
        <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700/60 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
          @forelse ($transactions as $tx)
            @php
              $isDep = $tx->transaction_type === 'deposit';
              $totNom = (float) ($tx->mandatory_amount + $tx->secondary_amount);
            @endphp
            <div class="p-3.5 bg-white dark:bg-gray-800">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $tx->user->name ?? '-' }}</h4>
                  <p class="text-[11px] text-gray-400">{{ $tx->user->division->name ?? '-' }} • {{ \Carbon\Carbon::parse($tx->created_at)->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <div class="text-right">
                  <span class="text-xs font-extrabold {{ $isDep ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $isDep ? '+' : '-' }} Rp {{ number_format($totNom, 0, ',', '.') }}
                  </span>
                  <div class="mt-0.5">
                    @if($tx->status === 'approved')
                      <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">Disetujui</span>
                    @elseif($tx->status === 'pending')
                      <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800">Pending</span>
                    @else
                      <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-800">Ditolak</span>
                    @endif
                  </div>
                </div>
              </div>

              <p class="text-[11px] text-gray-600 dark:text-gray-300 mt-1.5">{{ $tx->description }}</p>

              <div class="mt-2.5 flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700 text-xs">
                <span class="text-[10px] text-gray-400">W: Rp {{ number_format($tx->mandatory_amount, 0, ',', '.') }} | S: Rp {{ number_format($tx->secondary_amount, 0, ',', '.') }}</span>
                <div class="flex items-center gap-2">
                  @if($tx->status === 'pending')
                    <button type="button" wire:click="approve('{{ $tx->id }}')" class="text-emerald-600 font-bold">Setujui</button>
                    <button type="button" wire:click="openRejectModal('{{ $tx->id }}')" class="text-rose-600 font-bold">Tolak</button>
                  @endif
                </div>
              </div>
            </div>
          @empty
            <div class="p-6 text-center text-gray-400 text-xs">Tidak ada mutasi syirkah.</div>
          @endforelse
        </div>

        @if($transactions->hasPages())
          <div class="mt-4">
            {{ $transactions->links() }}
          </div>
        @endif

      <!-- =========================================================================
           TAB 2: PENGAJUAN PENARIKAN KARYAWAN (APPROVAL LIFECYCLE)
           ========================================================================= -->
      @else
        <!-- Withdrawal Filters & Search -->
        <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <!-- Status Filter Tabs -->
          <div class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1 text-xs font-medium overflow-x-auto">
            <button type="button" 
                    wire:click="$set('withdrawalStatusFilter', '')" 
                    class="rounded-md px-3 py-1.5 transition-colors whitespace-nowrap {{ $withdrawalStatusFilter === '' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
              Semua
            </button>
            <button type="button" 
                    wire:click="$set('withdrawalStatusFilter', 'pending')" 
                    class="rounded-md px-3 py-1.5 transition-colors whitespace-nowrap {{ $withdrawalStatusFilter === 'pending' ? 'bg-amber-500 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-amber-600' }}">
              Menunggu ({{ $pendingWithdrawalsCount }})
            </button>
            <button type="button" 
                    wire:click="$set('withdrawalStatusFilter', 'accepted')" 
                    class="rounded-md px-3 py-1.5 transition-colors whitespace-nowrap {{ $withdrawalStatusFilter === 'accepted' ? 'bg-blue-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-blue-600' }}">
              Disetujui / Belum Bayar ({{ $acceptedWithdrawalsCount }})
            </button>
            <button type="button" 
                    wire:click="$set('withdrawalStatusFilter', 'paid')" 
                    class="rounded-md px-3 py-1.5 transition-colors whitespace-nowrap {{ $withdrawalStatusFilter === 'paid' ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-emerald-600' }}">
              Selesai Dibayar ({{ $paidWithdrawalsCount }})
            </button>
            <button type="button" 
                    wire:click="$set('withdrawalStatusFilter', 'rejected')" 
                    class="rounded-md px-3 py-1.5 transition-colors whitespace-nowrap {{ $withdrawalStatusFilter === 'rejected' ? 'bg-rose-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-rose-600' }}">
              Ditolak ({{ $rejectedWithdrawalsCount }})
            </button>
          </div>

          <!-- Search Bar -->
          <div class="relative flex-1 sm:max-w-xs">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-9 pr-8 text-xs sm:text-sm" name="withdrawalSearch" id="withdrawalSearch" autocomplete="off" wire:model.live.debounce.300ms="withdrawalSearch" placeholder="Cari Karyawan, Alasan..." />
            @if ($withdrawalSearch)
              <button type="button" wire:click="$set('withdrawalSearch', '')" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 focus:outline-none">
                <x-heroicon-o-x-mark class="size-4" />
              </button>
            @endif
          </div>
        </div>

        <!-- Withdrawals Table (Desktop & Tablet) -->
        <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-700/50 uppercase tracking-wider text-[11px] text-gray-500 dark:text-gray-400">
              <tr>
                <th scope="col" class="py-3.5 px-4 font-bold text-left">Tanggal Pengajuan</th>
                <th scope="col" class="py-3.5 px-4 font-bold text-left">Karyawan</th>
                <th scope="col" class="py-3.5 px-4 font-bold text-left">Tipe Penarikan</th>
                <th scope="col" class="py-3.5 px-4 font-bold text-right">Syirkah Wajib</th>
                <th scope="col" class="py-3.5 px-4 font-bold text-right">Sukarela (SSR)</th>
                <th scope="col" class="py-3.5 px-4 font-bold text-right">Total Penarikan</th>
                <th scope="col" class="py-3.5 px-4 font-bold text-center">Status</th>
                <th scope="col" class="py-3.5 px-4 font-bold text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
              @forelse ($withdrawals as $wd)
                @php
                  $badge = $wd->status_badge;
                @endphp
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">
                      {{ \Carbon\Carbon::parse($wd->created_at)->translatedFormat('d M Y') }}
                    </div>
                    <div class="text-[11px] text-gray-400">
                      {{ \Carbon\Carbon::parse($wd->created_at)->format('H:i') }} WIB
                    </div>
                  </td>

                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $wd->user->name ?? '-' }}</div>
                    <div class="text-[11px] text-gray-400">NIP: {{ $wd->user->nip ?? '-' }} • {{ $wd->user->division->name ?? '-' }}</div>
                  </td>

                  <td class="py-3 px-4">
                    <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $wd->withdrawal_type_label }}</div>
                    @if($wd->reason)
                      <p class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-1 italic mt-0.5">"{{ $wd->reason }}"</p>
                    @endif
                  </td>

                  <td class="py-3 px-4 text-right whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                    Rp {{ number_format($wd->mandatory_amount, 0, ',', '.') }}
                  </td>

                  <td class="py-3 px-4 text-right whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                    Rp {{ number_format($wd->secondary_amount, 0, ',', '.') }}
                  </td>

                  <td class="py-3 px-4 text-right whitespace-nowrap font-extrabold text-sm text-rose-600 dark:text-rose-400">
                    - Rp {{ number_format($wd->total_amount, 0, ',', '.') }}
                  </td>

                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['border'] }}">
                      <span class="h-1.5 w-1.5 rounded-full {{ $wd->status === 'pending' ? 'bg-amber-500 animate-ping' : ($wd->status === 'accepted' ? 'bg-blue-500' : ($wd->status === 'paid' ? 'bg-emerald-500' : 'bg-rose-500')) }}"></span>
                      {{ $badge['label'] }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $badge['desc'] }}</p>
                  </td>

                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                      <!-- Action for PENDING: Accept / Reject -->
                      @if($wd->status === 'pending')
                        <button 
                          type="button" 
                          wire:click="approveWithdrawal('{{ $wd->id }}')" 
                          wire:confirm="Setujui pengajuan penarikan ini? Saldo syirkah karyawan akan otomatis dipotong di mutasi rekening."
                          class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-xs transition"
                          title="Setujui (Accept)"
                        >
                          <x-heroicon-o-check class="h-3.5 w-3.5" />
                          <span>Setujui</span>
                        </button>

                        <button 
                          type="button" 
                          wire:click="openRejectWithdrawalModal('{{ $wd->id }}')" 
                          class="p-1 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                          title="Tolak Pengajuan"
                        >
                          <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                      @endif

                      <!-- Action for ACCEPTED: Mark as Paid / Reject -->
                      @if($wd->status === 'accepted')
                        <button 
                          type="button" 
                          wire:click="markAsPaidWithdrawal('{{ $wd->id }}')" 
                          wire:confirm="Tandai dana fisik penarikan syirkah ini telah selesai dibayarkan/ditransfer ke karyawan?"
                          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] shadow-xs transition"
                          title="Tandai Telah Dibayarkan (PAID)"
                        >
                          <x-heroicon-o-banknotes class="h-3.5 w-3.5" />
                          <span>Bayar (PAID)</span>
                        </button>

                        <button 
                          type="button" 
                          wire:click="openRejectWithdrawalModal('{{ $wd->id }}')" 
                          class="p-1 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                          title="Batalkan / Tolak"
                        >
                          <x-heroicon-o-arrow-path-rounded-square class="h-4 w-4" />
                        </button>
                      @endif

                      <!-- View Detail -->
                      <button 
                        type="button" 
                        wire:click="openDetailWithdrawalModal('{{ $wd->id }}')" 
                        class="p-1 rounded-lg text-gray-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-950/40 transition"
                        title="Lihat Detail"
                      >
                        <x-heroicon-o-eye class="h-4 w-4" />
                      </button>

                      <!-- Delete for Superadmin / Owner -->
                      @if(Auth::user()?->isSuperadmin || Auth::user()?->isSyirkah || Auth::user()?->isOwner)
                        <button 
                          type="button" 
                          wire:click="deleteWithdrawal('{{ $wd->id }}')" 
                          wire:confirm="Hapus pengajuan penarikan ini?"
                          class="p-1 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                          title="Hapus Pengajuan"
                        >
                          <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="py-8 text-center text-gray-400 dark:text-gray-500">
                    Tidak ada data pengajuan penarikan syirkah ditemukan.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- Mobile Withdrawals List -->
        <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700/60 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
          @forelse ($withdrawals as $wd)
            @php
              $badge = $wd->status_badge;
            @endphp
            <div class="p-3.5 bg-white dark:bg-gray-800">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $wd->user->name ?? '-' }}</h4>
                  <p class="text-[11px] text-gray-400">{{ $wd->user->division->name ?? '-' }} • {{ \Carbon\Carbon::parse($wd->created_at)->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <div class="text-right">
                  <span class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400">
                    - Rp {{ number_format($wd->total_amount, 0, ',', '.') }}
                  </span>
                  <div class="mt-0.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['border'] }}">
                      {{ $badge['label'] }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="mt-2 text-[11px] text-gray-600 dark:text-gray-300">
                <span class="font-bold">{{ $wd->withdrawal_type_label }}</span>
                @if($wd->reason)
                  <p class="italic text-gray-500 dark:text-gray-400 mt-0.5">"{{ $wd->reason }}"</p>
                @endif
              </div>

              <div class="mt-3 flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700 text-xs">
                <span class="text-[10px] text-gray-400">W: Rp {{ number_format($wd->mandatory_amount, 0, ',', '.') }} | S: Rp {{ number_format($wd->secondary_amount, 0, ',', '.') }}</span>
                <div class="flex items-center gap-1.5">
                  @if($wd->status === 'pending')
                    <button type="button" wire:click="approveWithdrawal('{{ $wd->id }}')" class="px-2 py-1 rounded bg-emerald-600 text-white font-bold text-[10px]">Setujui</button>
                    <button type="button" wire:click="openRejectWithdrawalModal('{{ $wd->id }}')" class="px-2 py-1 rounded bg-rose-50 text-rose-600 font-bold text-[10px]">Tolak</button>
                  @elseif($wd->status === 'accepted')
                    <button type="button" wire:click="markAsPaidWithdrawal('{{ $wd->id }}')" class="px-2 py-1 rounded bg-blue-600 text-white font-bold text-[10px]">Bayar (PAID)</button>
                  @endif
                  <button type="button" wire:click="openDetailWithdrawalModal('{{ $wd->id }}')" class="text-teal-600 font-bold text-[11px]">Detail</button>
                </div>
              </div>
            </div>
          @empty
            <div class="p-6 text-center text-gray-400 text-xs">Tidak ada data pengajuan penarikan.</div>
          @endforelse
        </div>

        @if($withdrawals->hasPages())
          <div class="mt-4">
            {{ $withdrawals->links() }}
          </div>
        @endif
      @endif

    </div>
  </div>

  <!-- =========================================================================
       MODALS
       ========================================================================= -->

  <!-- 1. MODAL REJECT MUTASI -->
  <x-dialog-modal wire:model.live="rejectModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-bold">
        <x-heroicon-o-x-circle class="h-5 w-5" />
        {{ $isBulkReject ? 'Tolak Mutasi Massal' : 'Tolak Mutasi Syirkah' }}
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="space-y-3">
        <p class="text-xs text-gray-600 dark:text-gray-300">
          Masukkan alasan penolakan mutasi syirkah ini.
        </p>
        <div>
          <x-label for="rejection_reason" value="Alasan Penolakan" class="text-xs font-semibold" />
          <textarea 
            id="rejection_reason" 
            wire:model.live="rejection_reason" 
            rows="3" 
            class="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-rose-500 focus:ring-rose-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-xs" 
            placeholder="Contoh: Data tidak sesuai, revisi payroll, dll."
          ></textarea>
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeRejectModal">Batal</x-secondary-button>
        <x-danger-button wire:click="submitReject">Tolak Mutasi</x-danger-button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- 2. MODAL REJECT PENGAJUAN PENARIKAN -->
  <x-dialog-modal wire:model.live="rejectWithdrawalModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-bold">
        <x-heroicon-o-x-circle class="h-5 w-5" />
        {{ __('Tolak Pengajuan Penarikan Syirkah') }}
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="space-y-3">
        <p class="text-xs text-gray-600 dark:text-gray-300">
          Berikan alasan penolakan pengajuan penarikan agar karyawan mengetahui penyebabnya.
        </p>
        <div>
          <x-label for="withdrawalRejectionReason" value="Alasan Penolakan (Wajib)" class="text-xs font-semibold" />
          <textarea 
            id="withdrawalRejectionReason" 
            wire:model.live="withdrawalRejectionReason" 
            rows="3" 
            class="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-rose-500 focus:ring-rose-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-xs" 
            placeholder="Contoh: Belum memenuhi masa kerja minimal, saldo tidak mencukupi, dll."
          ></textarea>
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeRejectWithdrawalModal">Batal</x-secondary-button>
        <x-danger-button wire:click="submitRejectWithdrawal">Tolak Pengajuan</x-danger-button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- 3. MODAL DETAIL PENGAJUAN PENARIKAN -->
  <x-dialog-modal wire:model.live="detailWithdrawalModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-gray-900 dark:text-white font-bold">
        <x-heroicon-o-document-text class="h-5 w-5 text-teal-600 dark:text-teal-400" />
        {{ __('Rincian Pengajuan Penarikan Syirkah') }}
      </div>
    </x-slot>

    <x-slot name="content">
      @if($selectedWithdrawal)
        @php
          $badge = $selectedWithdrawal->status_badge;
        @endphp

        <!-- Top Card -->
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
            <span class="text-gray-500 dark:text-gray-400">Karyawan</span>
            <span class="font-bold text-gray-900 dark:text-white">
              {{ $selectedWithdrawal->user->name ?? '-' }} ({{ $selectedWithdrawal->user->nip ?? '-' }})
            </span>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Divisi</span>
            <span class="font-semibold text-gray-800 dark:text-gray-200">
              {{ $selectedWithdrawal->user->division->name ?? '-' }}
            </span>
          </div>

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

          @if($selectedWithdrawal->approver)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Disetujui Oleh</span>
              <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $selectedWithdrawal->approver->name }}
              </span>
            </div>
          @endif

          @if($selectedWithdrawal->approved_at)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Waktu Persetujuan</span>
              <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($selectedWithdrawal->approved_at)->translatedFormat('d M Y, H:i') }} WIB
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
              <span class="text-gray-500 dark:text-gray-400 block mb-1">Alasan Pengajuan Karyawan:</span>
              <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-2.5 text-gray-700 dark:text-gray-300 font-medium">
                {{ $selectedWithdrawal->reason }}
              </div>
            </div>
          @endif
        </div>
      @endif
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeDetailWithdrawalModal">Tutup</x-secondary-button>
    </x-slot>
  </x-dialog-modal>

  <!-- 4. MODAL EDIT NOMINAL MUTASI -->
  <x-dialog-modal wire:model.live="editNominalModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-gray-900 dark:text-white font-bold">
        <x-heroicon-o-pencil-square class="h-5 w-5 text-blue-600" />
        {{ __('Edit Nominal Mutasi Syirkah') }}
      </div>
    </x-slot>

    <x-slot name="content">
      @if($editingTransaction)
        <div class="space-y-4 text-xs">
          <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
            <p class="font-bold text-gray-900 dark:text-white">{{ $editingTransaction->user->name ?? '-' }}</p>
            <p class="text-gray-500 dark:text-gray-400 mt-0.5">{{ $editingTransaction->description }}</p>
          </div>

          <div>
            <x-label for="edit_mandatory_amount" value="Nominal Syirkah Wajib (Rp)" class="text-xs font-semibold" />
            <x-input type="number" id="edit_mandatory_amount" wire:model.live="edit_mandatory_amount" min="0" class="w-full mt-1 text-xs sm:text-sm font-semibold" />
            <x-input-error for="edit_mandatory_amount" class="mt-1" />
          </div>

          <div>
            <x-label for="edit_secondary_amount" value="Nominal Syirkah Sukarela / SSR (Rp)" class="text-xs font-semibold" />
            <x-input type="number" id="edit_secondary_amount" wire:model.live="edit_secondary_amount" min="0" class="w-full mt-1 text-xs sm:text-sm font-semibold" />
            <x-input-error for="edit_secondary_amount" class="mt-1" />
          </div>
        </div>
      @endif
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeEditNominalModal">Batal</x-secondary-button>
        <x-button wire:click="updateNominal">Simpan Perubahan</x-button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- 5. MODAL DELETE PERMANENT -->
  <x-dialog-modal wire:model.live="isDeleteModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-bold">
        <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
        {{ $isBulkDelete ? 'Hapus Mutasi Massal' : 'Hapus Mutasi Syirkah' }}
      </div>
    </x-slot>

    <x-slot name="content">
      <p class="text-xs text-gray-600 dark:text-gray-300">
        Apakah Anda yakin ingin menghapus data mutasi syirkah ini secara permanen? Saldo berjalan seluruh transaksi karyawan terkait akan dihitung ulang secara otomatis.
      </p>
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeDeleteModal">Batal</x-secondary-button>
        <x-danger-button wire:click="confirmDelete">Hapus Permanen</x-danger-button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- 6. MODAL PENCAIRAN LANGSUNG (ADMIN MUTASI) -->
  <x-dialog-modal wire:model.live="withdrawalModalOpen" maxWidth="md">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-gray-900 dark:text-white font-bold">
        <x-heroicon-o-banknotes class="h-5 w-5 text-emerald-600" />
        {{ __('Pencairan Saldo Syirkah Karyawan') }}
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="space-y-4 text-xs">
        <div>
          <x-label for="withdrawal_user_id" value="Pilih Karyawan" class="text-xs font-semibold" />
          <x-select id="withdrawal_user_id" wire:model.live="withdrawal_user_id" class="w-full mt-1 text-xs sm:text-sm">
            <option value="">-- Pilih Karyawan --</option>
            @foreach($users as $u)
              <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->nip }}) - {{ $u->division->name ?? '-' }}</option>
            @endforeach
          </x-select>
          <x-input-error for="withdrawal_user_id" class="mt-1" />
        </div>

        <div>
          <x-label for="withdrawal_savings_id" value="Program Syirkah" class="text-xs font-semibold" />
          <x-select id="withdrawal_savings_id" wire:model.live="withdrawal_savings_id" class="w-full mt-1 text-xs sm:text-sm">
            <option value="">-- Pilih Program Syirkah --</option>
            @foreach($savingsList as $s)
              <option value="{{ $s->id }}">{{ $s->savings_name }}</option>
            @endforeach
          </x-select>
          <x-input-error for="withdrawal_savings_id" class="mt-1" />
        </div>

        <div>
          <x-label for="withdrawal_type" value="Sumber Saldo Pencairan" class="text-xs font-semibold" />
          <x-select id="withdrawal_type" wire:model.live="withdrawal_type" class="w-full mt-1 text-xs sm:text-sm">
            <option value="secondary">Sukarela (SSR) Saja</option>
            <option value="mandatory">Wajib Saja</option>
            <option value="both">Kombinasi (Wajib + Sukarela)</option>
          </x-select>
          <x-input-error for="withdrawal_type" class="mt-1" />
        </div>

        <div>
          <x-label for="withdrawal_amount" value="Nominal Pencairan (Rp)" class="text-xs font-semibold" />
          <x-input type="number" id="withdrawal_amount" wire:model.live="withdrawal_amount" min="1" class="w-full mt-1 text-xs sm:text-sm font-semibold" placeholder="0" />
          <x-input-error for="withdrawal_amount" class="mt-1" />
        </div>

        <div>
          <x-label for="withdrawal_description" value="Keterangan Pencairan" class="text-xs font-semibold" />
          <x-input type="text" id="withdrawal_description" wire:model.live="withdrawal_description" class="w-full mt-1 text-xs sm:text-sm" placeholder="Contoh: Pencairan dana darurat" />
          <x-input-error for="withdrawal_description" class="mt-1" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-end gap-2">
        <x-secondary-button wire:click="closeWithdrawalModal">Batal</x-secondary-button>
        <x-button wire:click="processWithdrawal" class="bg-emerald-600 hover:bg-emerald-700">Proses Pencairan</x-button>
      </div>
    </x-slot>
  </x-dialog-modal>

</div>
