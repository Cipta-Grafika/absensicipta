<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Mutasi Syirkah') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-button type="button" x-data @click.prevent="$dispatch('open-withdrawal-modal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sm:mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="hidden sm:inline">Pencairan</span>
      </x-button>
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="sm:mr-1.5 h-4 w-4 text-sky-500" />
        <span class="hidden sm:inline">Filter</span>
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-0 sm:py-12" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Mutasi</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('month', ''); $set('type', ''); $set('division', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="month_filter" value="Pilih Bulan" class="mb-1"></x-label>
            <x-input type="month" id="month_filter" class="w-full block" wire:model.live="month" />
          </div>

          <div>
            <x-label for="type_filter" value="Jenis Transaksi" class="mb-1"></x-label>
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
              @foreach (\App\Models\Division::all() as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </x-select>
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <div class="bg-white p-6 shadow-xl dark:bg-gray-800 sm:rounded-lg lg:p-8">
      
      <!-- SUMMARY CARDS -->
      <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="overflow-hidden rounded-lg bg-indigo-50 px-4 py-5 shadow sm:p-6 dark:bg-indigo-900/30">
          <dt class="truncate text-sm font-medium text-indigo-500 dark:text-indigo-300">Total Saldo Wajib</dt>
          <dd class="mt-1 text-2xl font-semibold tracking-tight text-indigo-900 dark:text-indigo-100">Rp {{ number_format($totalWajib, 0, ',', '.') }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-green-50 px-4 py-5 shadow sm:p-6 dark:bg-green-900/30">
          <dt class="truncate text-sm font-medium text-green-500 dark:text-green-300">Total Saldo Sukarela</dt>
          <dd class="mt-1 text-2xl font-semibold tracking-tight text-green-900 dark:text-green-100">Rp {{ number_format($totalSukarela, 0, ',', '.') }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-sky-50 px-4 py-5 shadow sm:p-6 dark:bg-sky-900/30">
          <dt class="truncate text-sm font-medium text-sky-500 dark:text-sky-300">Total Keseluruhan</dt>
          <dd class="mt-1 text-2xl font-semibold tracking-tight text-sky-900 dark:text-sky-100">Rp {{ number_format($totalWajib + $totalSukarela, 0, ',', '.') }}</dd>
        </div>
      </div>

      <div class="mb-4">
        <div class="flex w-full flex-1 items-center gap-2">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-10 pr-10" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Nama Karyawan, NIP, Tabungan..." />
            @if ($search)
              <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            @endif
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tgl Transaksi</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jenis Syirkah</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipe</th>
              <th scope="col" class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Mutasi Wajib</th>
              <th scope="col" class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Mutasi Sukarela</th>
              <th scope="col" class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo Wajib</th>
              <th scope="col" class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo Sukarela</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse($transactions as $trx)
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ $trx->created_at->format('d M Y H:i') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  <div class="font-medium">{{ $trx->user->name ?? '-' }}</div>
                  <div class="text-gray-500 dark:text-gray-400">{{ $trx->user->nip ?? '-' }}</div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  {{ $trx->masterSaving->savings_name ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  @if($trx->transaction_type == 'deposit')
                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Deposit</span>
                  @else
                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Withdrawal</span>
                  @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-right text-sm {{ $trx->transaction_type == 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                  {{ $trx->transaction_type == 'deposit' ? '+' : '-' }} Rp {{ number_format($trx->mandatory_amount, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-right text-sm {{ $trx->transaction_type == 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                  {{ $trx->transaction_type == 'deposit' ? '+' : '-' }} Rp {{ number_format($trx->secondary_amount, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-semibold text-gray-900 dark:text-gray-200">
                  Rp {{ number_format($trx->balance_mandatory, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-semibold text-gray-900 dark:text-gray-200">
                  Rp {{ number_format($trx->balance_secondary, 0, ',', '.') }}
                </td>
                <td class="px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  {{ $trx->description }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  Belum ada data mutasi.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($transactions->hasPages())
        <div class="mt-4">
          {{ $transactions->links() }}
        </div>
      @endif
    </div>
  </div>

  <!-- Modal Pencairan Syirkah -->
  <x-dialog-modal wire:model.live="withdrawalModalOpen" maxWidth="lg">
    <x-slot name="title">
      Proses Pencairan Syirkah
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-6">
        <div>
          <x-label for="withdrawal_user_id" value="Pilih Karyawan" />
          <x-select id="withdrawal_user_id" class="mt-1 block w-full" wire:model="withdrawal_user_id">
            <option value="">-- Pilih Karyawan --</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->nip }})</option>
            @endforeach
          </x-select>
          <x-input-error for="withdrawal_user_id" class="mt-2" />
        </div>

        <div>
          <x-label for="withdrawal_savings_id" value="Pilih Jenis Syirkah" />
          <x-select id="withdrawal_savings_id" class="mt-1 block w-full" wire:model="withdrawal_savings_id">
            <option value="">-- Pilih Syirkah --</option>
            @foreach($savingsList as $saving)
              <option value="{{ $saving->id }}">{{ $saving->savings_name }}</option>
            @endforeach
          </x-select>
          <x-input-error for="withdrawal_savings_id" class="mt-2" />
        </div>

        <div>
          <x-label for="withdrawal_type" value="Sumber Dana Pencairan" />
          <x-select id="withdrawal_type" class="mt-1 block w-full" wire:model="withdrawal_type">
            <option value="secondary">Syirkah Sukarela</option>
            <option value="mandatory">Syirkah Wajib</option>
            <option value="both">Keduanya (Syirkah Wajib + Sukarela)</option>
          </x-select>
          <x-input-error for="withdrawal_type" class="mt-2" />
        </div>

        <div>
          <x-label for="withdrawal_amount" value="Nominal Pencairan (Rp)" />
          <x-input id="withdrawal_amount" type="number" class="mt-1 block w-full" wire:model="withdrawal_amount" placeholder="Contoh: 500000" />
          <x-input-error for="withdrawal_amount" class="mt-2" />
        </div>

        <div>
          <x-label for="withdrawal_description" value="Keterangan / Alasan" />
          <x-input id="withdrawal_description" type="text" class="mt-1 block w-full" wire:model="withdrawal_description" placeholder="Contoh: Pencairan sebagian" />
          <x-input-error for="withdrawal_description" class="mt-2" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeWithdrawalModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-red-600 hover:bg-red-700" wire:click="processWithdrawal" wire:loading.attr="disabled">
        Cairkan Dana
      </x-button>
    </x-slot>
  </x-dialog-modal>
</div>

<script>
  window.addEventListener('open-withdrawal-modal', event => {
    @this.openWithdrawalModal();
  });
</script>
