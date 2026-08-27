<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Metode Pembayaran
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
  <div class="w-full sm:px-6 lg:px-8">
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Karyawan</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('division', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="status_filter_pm" value="Status Karyawan" class="mb-1"></x-label>
            <x-select id="status_filter_pm" class="w-full" wire:model.live="status">
              <option value="">Aktif & Bekerja (Default)</option>
              <option value="all">Semua Status (Termasuk Resign/Keluar)</option>
              <option value="active">Aktif</option>
              <option value="suspend">Suspend</option>
              <option value="resign">Resign</option>
              <option value="fired">Dikeluarkan</option>
            </x-select>
          </div>

          <div>
            <x-label for="division_filter" value="Divisi" class="mb-1"></x-label>
            <x-select id="division_filter" class="w-full" wire:model.live="division">
              <option value="">Semua Divisi</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </x-select>
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden p-4 sm:p-6 lg:p-8">
      
      <!-- Search Bar -->
      <div class="mb-4">
        <div class="flex w-full flex-1 items-center gap-2">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-10 pr-10" name="search_employee" id="search_employee" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Karyawan, NIP, atau Rekening..." />
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

      <!-- Employee Payment Methods Table -->
      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[800px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
          <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <tr>
              <th scope="col" class="px-4 py-3 min-w-[220px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
              <th scope="col" class="px-4 py-3 min-w-[160px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Metode / Bank</th>
              <th scope="col" class="px-4 py-3 min-w-[160px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">No. Rekening</th>
              <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Atas Nama</th>
              <th scope="col" class="px-4 py-3 min-w-[120px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($employees as $emp)
              <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-750 transition-colors">
                <td class="whitespace-nowrap px-4 py-3.5">
                  <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0">
                      <img class="h-10 w-10 rounded-full object-cover" src="{{ $emp->profile_photo_url }}" alt="{{ $emp->name }}">
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $emp->name }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">{{ $emp->nip }}</div>
                      <div class="text-xs font-medium text-sky-600 dark:text-sky-400">{{ $emp->division->name ?? '-' }} | {{ $emp->jobTitle->name ?? '-' }}</div>
                    </div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-4 py-3.5 text-sm">
                  @if($emp->paymentMethod)
                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold leading-5 text-blue-800 dark:bg-blue-900/70 dark:text-blue-200">
                      {{ $emp->paymentMethod->payment_name }}
                    </span>
                  @else
                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold leading-5 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                      Belum Diset
                    </span>
                  @endif
                </td>
                <td class="whitespace-nowrap px-4 py-3.5 text-sm font-medium text-gray-900 dark:text-gray-200">
                  {{ $emp->paymentMethod->bank_account ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                  {{ $emp->paymentMethod->account_name ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3.5 text-center text-sm font-medium">
                  <button wire:click="edit('{{ $emp->id }}')" class="rounded-lg bg-sky-500 px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-sky-600 focus:outline-none transition">
                    {{ $emp->paymentMethod ? 'Edit' : 'Set Pembayaran' }}
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                  Tidak ada data karyawan yang ditemukan.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $employees->links() }}
      </div>

    </div>
  </div>

  <!-- Modal Set / Edit Metode Pembayaran -->
  <x-dialog-modal wire:model.live="isModalOpen">
    <x-slot name="title">
      {{ $payment_id ? __('Edit Metode Pembayaran Karyawan') : __('Atur Metode Pembayaran Karyawan') }}
    </x-slot>

    <x-slot name="content">
      @if ($selectedUser)
        <div class="mb-4 flex items-center gap-3 rounded-xl bg-sky-50 p-3 border border-sky-200 dark:bg-sky-950/50 dark:border-sky-800">
          <img class="h-10 w-10 rounded-full object-cover" src="{{ $selectedUser->profile_photo_url }}" alt="{{ $selectedUser->name }}">
          <div>
            <div class="text-xs font-bold text-gray-900 dark:text-white">{{ $selectedUser->name }}</div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400">
              NIP: {{ $selectedUser->nip }} &bull; {{ $selectedUser->division->name ?? '-' }}
            </div>
          </div>
        </div>
      @endif

      <form wire:submit.prevent="save" id="paymentMethodForm">
        <div class="grid grid-cols-1 gap-4">
          <div>
            <x-label for="payment_name" value="{{ __('Nama Metode / Bank *') }}" />
            <x-input id="payment_name" type="text" class="mt-1 block w-full text-sm" wire:model="payment_name" placeholder="Misal: BCA, Mandiri, BRI, BNI, BSI, CASH..." />
            <x-input-error for="payment_name" class="mt-1" />
          </div>

          <div>
            <x-label for="bank_account" value="{{ __('Nomor Rekening / No. Akun') }}" />
            <x-input id="bank_account" type="text" class="mt-1 block w-full text-sm" wire:model="bank_account" placeholder="Contoh: 1234567890 (Kosongkan jika Tunai)" />
            <x-input-error for="bank_account" class="mt-1" />
          </div>

          <div>
            <x-label for="account_name" value="{{ __('Atas Nama Pemilik Rekening') }}" />
            <x-input id="account_name" type="text" class="mt-1 block w-full text-sm" wire:model="account_name" placeholder="Nama pemilik sesuai rekening bank" />
            <x-input-error for="account_name" class="mt-1" />
          </div>
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <div class="flex w-full items-center justify-between">
        <div>
          @if ($payment_id)
            <x-danger-button type="button" wire:click="removePaymentMethod" wire:confirm="Yakin ingin menghapus metode pembayaran untuk karyawan ini?">
              {{ __('Hapus Rekening') }}
            </x-danger-button>
          @endif
        </div>

        <div class="flex items-center gap-2">
          <x-secondary-button wire:click="closeModal" wire:loading.attr="disabled">
            {{ __('Batal') }}
          </x-secondary-button>

          <x-button class="!bg-sky-600 hover:!bg-sky-700" wire:click="save" wire:loading.attr="disabled">
            {{ __('Simpan') }}
          </x-button>
        </div>
      </div>
    </x-slot>
  </x-dialog-modal>
</div>
