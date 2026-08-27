<x-slot name="header">
  <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Pinjaman Karyawan (Kasbon)
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola pengajuan, persetujuan, dan pemotongan cicilan pinjaman karyawan</p>
    </div>
    <div class="flex items-center gap-2">
      <x-button type="button" x-data @click.prevent="$dispatch('open-create-modal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="hidden sm:inline">Buat Pinjaman Baru</span>
        <span class="sm:hidden">Buat Pinjaman</span>
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

    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Pinjaman</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('status', ''); $set('division', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="status_filter" value="Status Pinjaman" class="mb-1"></x-label>
            <x-select id="status_filter" class="w-full" wire:model.live="status">
              <option value="">Semua Status</option>
              <option value="pending">Menunggu Persetujuan (Pending)</option>
              <option value="active">Aktif (Sedang Berjalan)</option>
              <option value="paid_off">Lunas (Paid Off)</option>
              <option value="rejected">Ditolak (Rejected)</option>
            </x-select>
          </div>

          <div>
            <x-label for="division_filter" value="Divisi" class="mb-1"></x-label>
            <x-select id="division_filter" class="w-full" wire:model.live="division">
              <option value="">Semua Divisi</option>
              @foreach (\App\Models\Division::all() as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </x-select>
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden p-4 sm:p-6 lg:p-8">
      
      <!-- SUMMARY STATS -->
      <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/50 p-4 sm:p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <dt class="truncate text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Pinjaman Aktif</dt>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900/60 dark:text-blue-200">
              {{ $activeCount }} Akun
            </span>
          </div>
          <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-blue-950 dark:text-blue-100">
            Rp {{ number_format($activeBalance, 0, ',', '.') }}
          </dd>
          <p class="text-[11px] text-blue-500 dark:text-blue-400 mt-1">Sisa saldo belum lunas</p>
        </div>

        <div class="overflow-hidden rounded-xl {{ $pendingCount > 0 ? 'bg-amber-50/90 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700/60' : 'bg-gray-50/80 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700' }} border p-4 sm:p-5 shadow-xs transition-colors">
          <div class="flex items-center justify-between">
            <dt class="truncate text-xs font-semibold uppercase tracking-wider {{ $pendingCount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-600 dark:text-gray-400' }}">Menunggu Approval</dt>
            @if($pendingCount > 0)
              <span class="inline-flex items-center rounded-full bg-amber-200/80 px-2 py-0.5 text-xs font-bold text-amber-900 dark:bg-amber-900/60 dark:text-amber-200 animate-pulse">
                {{ $pendingCount }} Pengajuan
              </span>
            @endif
          </div>
          <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight {{ $pendingCount > 0 ? 'text-amber-950 dark:text-amber-100' : 'text-gray-700 dark:text-gray-300' }}">
            Rp {{ number_format($pendingNominal, 0, ',', '.') }}
          </dd>
          <p class="text-[11px] text-amber-600/80 dark:text-amber-400/80 mt-1">Butuh konfirmasi syirkah/payroll</p>
        </div>

        <div class="overflow-hidden rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 p-4 sm:p-5 shadow-xs">
          <dt class="truncate text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Pinjaman Lunas</dt>
          <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-emerald-950 dark:text-emerald-100">
            {{ $paidOffCount }} Pinjaman
          </dd>
          <p class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-1">Telah selesai dilunasi</p>
        </div>

        <div class="overflow-hidden rounded-xl bg-sky-50/80 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/50 p-4 sm:p-5 shadow-xs">
          <dt class="truncate text-xs font-semibold uppercase tracking-wider text-sky-600 dark:text-sky-400">Total Transaksi Pinjaman</dt>
          <dd class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-sky-950 dark:text-sky-100">
            {{ $loans->total() }} Record
          </dd>
          <p class="text-[11px] text-sky-600/80 dark:text-sky-400/80 mt-1">Keseluruhan riwayat kasbon</p>
        </div>
      </div>

      <!-- STATUS TABS & SEARCH -->
      <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <!-- Status Tabs -->
        <div class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1 text-xs font-medium">
          <button type="button" 
                  wire:click="$set('status', '')" 
                  class="rounded-md px-3 py-1.5 transition-colors {{ $status === '' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
            Semua
          </button>
          <button type="button" 
                  wire:click="$set('status', 'pending')" 
                  class="relative rounded-md px-3 py-1.5 transition-colors {{ $status === 'pending' ? 'bg-amber-500 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400' }}">
            Menunggu
            @if($pendingCount > 0)
              <span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.2 text-[10px] font-bold text-amber-800 {{ $status === 'pending' ? 'bg-white text-amber-900' : '' }}">
                {{ $pendingCount }}
              </span>
            @endif
          </button>
          <button type="button" 
                  wire:click="$set('status', 'active')" 
                  class="rounded-md px-3 py-1.5 transition-colors {{ $status === 'active' ? 'bg-blue-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">
            Aktif
          </button>
          <button type="button" 
                  wire:click="$set('status', 'paid_off')" 
                  class="rounded-md px-3 py-1.5 transition-colors {{ $status === 'paid_off' ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400' }}">
            Lunas
          </button>
          <button type="button" 
                  wire:click="$set('status', 'rejected')" 
                  class="rounded-md px-3 py-1.5 transition-colors {{ $status === 'rejected' ? 'bg-rose-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-400' }}">
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
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          @endif
        </div>
      </div>

      <!-- BULK ACTION BAR -->
      @if(!empty($selectedLoans))
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-sky-50 dark:bg-sky-950/50 border border-sky-200 dark:border-sky-800 p-3 text-xs sm:text-sm">
          <div class="flex items-center gap-2 font-medium text-sky-900 dark:text-sky-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><strong>{{ count($selectedLoans) }}</strong> pinjaman dipilih</span>
          </div>
          @if(Auth::user()?->isSyirkah || Auth::user()?->isPayroll || Auth::user()?->isSuperadmin)
            <div class="flex flex-wrap items-center gap-2">
              <button type="button" 
                      wire:click="bulkApprove" 
                      class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500 active:bg-emerald-700 transition-colors cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Setujui Terpilih
              </button>
              <button type="button" 
                      wire:click="openBulkRejectModal" 
                      class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-rose-500 active:bg-rose-700 transition-colors cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Tolak Terpilih
              </button>
              <button type="button" 
                      wire:click="openBulkDeleteModal" 
                      class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-rose-500 active:bg-rose-700 transition-colors cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Hapus Permanen Terpilih
              </button>
            </div>
          @endif
        </div>
      @endif

      <!-- TABLE -->
      <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[1100px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
          <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <tr>
              @if(Auth::user()?->isSyirkah || Auth::user()?->isPayroll || Auth::user()?->isSuperadmin)
                <th scope="col" class="w-10 px-3 py-3 text-center">
                  <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-sky-600 shadow-xs focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-offset-gray-800">
                </th>
              @endif
              <th scope="col" class="px-4 py-3 min-w-[130px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tgl Pengajuan</th>
              <th scope="col" class="px-4 py-3 min-w-[190px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
              <th scope="col" class="px-4 py-3 min-w-[150px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Pinjaman</th>
              <th scope="col" class="px-4 py-3 min-w-[90px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tenor</th>
              <th scope="col" class="px-4 py-3 min-w-[140px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cicilan/Bulan</th>
              <th scope="col" class="px-4 py-3 min-w-[140px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sisa Saldo</th>
              <th scope="col" class="px-4 py-3 min-w-[140px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
              <th scope="col" class="px-4 py-3 min-w-[160px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Keterangan</th>
              <th scope="col" class="px-4 py-3 min-w-[130px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse($loans as $loan)
              <tr class="{{ in_array($loan->id, $selectedLoans) ? 'bg-sky-50/60 dark:bg-sky-950/30' : '' }} hover:bg-gray-50/80 dark:hover:bg-gray-750 transition-colors">
                @if(Auth::user()?->isSyirkah || Auth::user()?->isPayroll || Auth::user()?->isSuperadmin)
                  <td class="px-3 py-4 text-center">
                    <input type="checkbox" value="{{ $loan->id }}" wire:model.live="selectedLoans" class="rounded border-gray-300 text-sky-600 shadow-xs focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-offset-gray-800">
                  </td>
                @endif
                <td class="whitespace-nowrap px-4 py-4 text-xs font-medium text-gray-900 dark:text-gray-100">
                  {{ $loan->created_at->format('d M Y') }}
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-gray-300">
                  <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $loan->user->name ?? '-' }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ $loan->user->nip ?? '-' }} &bull; {{ $loan->user->division->name ?? 'No Div' }}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-right text-xs font-semibold text-gray-900 dark:text-gray-100">
                  Rp {{ number_format($loan->loan_amount, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-center text-xs text-gray-800 dark:text-gray-200 font-medium">
                  {{ $loan->tenor_months }} Bln
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-right text-xs text-gray-700 dark:text-gray-300 font-medium">
                  Rp {{ number_format($loan->installment_amount, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-right text-xs font-bold {{ $loan->remaining_balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                  Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-center text-xs">
                  @if($loan->status == 'pending')
                    <span class="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-800 dark:bg-amber-900/80 dark:text-amber-200 animate-pulse">
                      Menunggu
                    </span>
                  @elseif(in_array($loan->status, ['approved', 'active']))
                    <div class="flex flex-col items-center">
                      <span class="inline-flex items-center rounded-md bg-blue-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-800 dark:bg-blue-900/80 dark:text-blue-200">
                        Aktif
                      </span>
                      @if($loan->approver)
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">by {{ $loan->approver->name }}</span>
                      @endif
                    </div>
                  @elseif($loan->status == 'paid_off')
                    <span class="inline-flex items-center rounded-md bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-200">
                      Lunas
                    </span>
                  @else
                    <div class="flex flex-col items-center">
                      <span class="inline-flex items-center rounded-md bg-rose-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-rose-800 dark:bg-rose-900/80 dark:text-rose-200">
                        Ditolak
                      </span>
                      @if($loan->rejection_reason)
                        <span class="text-[10px] text-rose-500 max-w-[120px] truncate" title="{{ $loan->rejection_reason }}">{{ $loan->rejection_reason }}</span>
                      @endif
                    </div>
                  @endif
                </td>
                <td class="px-4 py-4 text-xs text-gray-700 dark:text-gray-300 max-w-[200px] truncate" title="{{ $loan->description }}">
                  {{ $loan->description ?: '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-center text-xs font-medium">
                  <div class="inline-flex items-center justify-center gap-1.5">
                    @if(Auth::user()?->isSyirkah || Auth::user()?->isPayroll || Auth::user()?->isSuperadmin)
                      @if($loan->status == 'pending')
                        <button type="button" 
                                wire:click="approveLoan('{{ $loan->id }}')" 
                                title="Setujui Pinjaman Ini"
                                class="inline-flex items-center justify-center p-1.5 rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:hover:bg-emerald-900 transition-colors cursor-pointer">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                          </svg>
                        </button>
                        <button type="button" 
                                wire:click="openRejectModal('{{ $loan->id }}')" 
                                title="Tolak Pinjaman Ini"
                                class="inline-flex items-center justify-center p-1.5 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-950 dark:text-rose-300 dark:hover:bg-rose-900 transition-colors cursor-pointer">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      @elseif(in_array($loan->status, ['approved', 'active']))
                        <button type="button" 
                                wire:click="markAsPaidOff('{{ $loan->id }}')" 
                                onclick="confirm('Yakin ingin menandai pinjaman ini sebagai LUNAS?') || event.stopImmediatePropagation()" 
                                class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 border border-emerald-300 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 hover:bg-emerald-100 transition-colors cursor-pointer" 
                                title="Tandai Lunas">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                          </svg>
                          Lunas
                        </button>
                      @endif

                      <!-- Tombol Hapus Permanen -->
                      <button type="button" 
                              wire:click="openDeleteModal('{{ $loan->id }}')" 
                              title="Hapus Permanen Pinjaman Ini"
                              class="inline-flex items-center justify-center p-1.5 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-950 dark:text-rose-300 dark:hover:bg-rose-900 transition-colors cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                      </button>
                    @else
                      <span class="text-xs text-gray-400">-</span>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="{{ (Auth::user()?->isSyirkah || Auth::user()?->isPayroll || Auth::user()?->isSuperadmin) ? 10 : 9 }}" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                  <div class="flex flex-col items-center justify-center gap-1">
                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <span>Belum ada data pengajuan pinjaman karyawan.</span>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($loans->hasPages())
        <div class="mt-4">
          {{ $loans->links() }}
        </div>
      @endif
    </div>
  </div>

  <!-- Modal Tolak Pinjaman -->
  <x-dialog-modal wire:model.live="rejectModalOpen" maxWidth="md">
    <x-slot name="title">
      {{ $isBulkReject ? 'Tolak Pinjaman Terpilih' : 'Tolak Pengajuan Pinjaman' }}
    </x-slot>

    <x-slot name="content">
      <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {{ $isBulkReject ? 'Apakah Anda yakin ingin menolak ' . count($selectedLoans) . ' pinjaman yang dipilih?' : 'Apakah Anda yakin ingin menolak pengajuan pinjaman ini?' }}
      </div>

      <div>
        <x-label for="rejection_reason_loan" value="Alasan Penolakan (Opsional)" />
        <x-input id="rejection_reason_loan" type="text" class="mt-1 block w-full text-sm" wire:model="rejection_reason" placeholder="Contoh: Plafon pinjaman melebihi batas / masa kerja belum memenuhi syarat" />
        <x-input-error for="rejection_reason" class="mt-2" />
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeRejectModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-rose-600 hover:bg-rose-700 text-white" wire:click="submitRejectLoan" wire:loading.attr="disabled">
        Tolak Pinjaman
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Hapus Permanen Pinjaman -->
  <x-dialog-modal wire:model.live="isDeleteModalOpen" maxWidth="md">
    <x-slot name="title">
      {{ $isBulkDelete ? 'Hapus Permanen Pinjaman Terpilih' : 'Hapus Permanen Data Pinjaman' }}
    </x-slot>

    <x-slot name="content">
      <div class="flex items-start gap-3">
        <div class="flex-shrink-0 rounded-full bg-rose-100 dark:bg-rose-950 p-2 text-rose-600 dark:text-rose-400">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
          <p class="font-semibold text-gray-900 dark:text-gray-100">Tindakan ini tidak dapat dibatalkan!</p>
          <p class="mt-1">
            {{ $isBulkDelete ? 'Apakah Anda yakin ingin menghapus permanen ' . count($selectedLoans) . ' pinjaman yang dipilih? Seluruh data pinjaman dan riwayat cicilannya akan dihapus dari database.' : 'Apakah Anda yakin ingin menghapus permanen data pinjaman ini? Seluruh data pinjaman dan riwayat cicilannya akan dihapus dari database.' }}
          </p>
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeDeleteModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-rose-600 hover:bg-rose-700 text-white" wire:click="confirmDeleteLoan" wire:loading.attr="disabled">
        Hapus Permanen
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Buat Pinjaman -->
  <x-dialog-modal wire:model.live="createModalOpen" maxWidth="lg">
    <x-slot name="title">
      Form Pengajuan Pinjaman Karyawan (Kasbon)
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-6">
        <div>
          <x-label for="user_id" value="Pilih Karyawan" />
          <x-select id="user_id" class="mt-1 block w-full" wire:model="user_id">
            <option value="">-- Pilih Karyawan --</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->nip }})</option>
            @endforeach
          </x-select>
          <x-input-error for="user_id" class="mt-2" />
        </div>

        <div>
          <x-label for="loan_amount" value="Total Nominal Pinjaman (Rp)" />
          <x-input id="loan_amount" type="number" class="mt-1 block w-full" wire:model.live.debounce.500ms="loan_amount" placeholder="Contoh: 1000000" />
          <x-input-error for="loan_amount" class="mt-2" />
        </div>

        <div>
          <x-label for="tenor_months" value="Tenor (Bulan)" />
          <x-input id="tenor_months" type="number" class="mt-1 block w-full" wire:model.live.debounce.500ms="tenor_months" placeholder="Berapa bulan dicicil" />
          <x-input-error for="tenor_months" class="mt-2" />
        </div>

        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-750 dark:border-gray-700">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Estimasi Cicilan per Bulan (Otomatis dipotong saat Payroll):</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">Rp {{ number_format($installment_amount, 0, ',', '.') }}</p>
        </div>

        <div>
          <x-label for="description" value="Keterangan / Alasan" />
          <x-input id="description" type="text" class="mt-1 block w-full" wire:model="description" placeholder="Contoh: Biaya pendidikan anak" />
          <x-input-error for="description" class="mt-2" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeCreateModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-sky-600 hover:bg-sky-700 text-white" wire:click="storeLoan" wire:loading.attr="disabled">
        Simpan & Ajukan Pinjaman
      </x-button>
    </x-slot>
  </x-dialog-modal>
</div>

<script>
  window.addEventListener('open-create-modal', event => {
    @this.openCreateModal();
  });
</script>
