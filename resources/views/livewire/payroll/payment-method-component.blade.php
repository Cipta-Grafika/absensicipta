<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <div>
      <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
        Metode Pembayaran
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
        Kelola Rekening Bank & Metode Pembayaran Gaji Karyawan
      </p>
    </div>
    <div class="flex items-center gap-2">
      <x-button x-data @click="$dispatch('open-payment-modal')" class="bg-sky-500 hover:bg-sky-600 focus:bg-sky-600 active:bg-sky-700">
        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span class="hidden sm:inline">Tambah Baru</span>
      </x-button>
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="sm:mr-1.5 h-4 w-4 text-sky-500" />
        <span class="hidden sm:inline">Filter</span>
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true" @open-payment-modal.window="$wire.openModal()">
  <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
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

    <div class="bg-white p-6 shadow-none sm:shadow-xl dark:bg-gray-800 sm:rounded-lg lg:p-8">
      
      <div class="mb-4">
        <div class="flex w-full flex-1 items-center gap-2">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-10 pr-10" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Metode Pembayaran..." />
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

      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[800px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
          <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <tr>
              <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
              <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Metode/Bank</th>
              <th scope="col" class="px-4 py-3 min-w-[160px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">No. Rekening</th>
              <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Atas Nama</th>
              <th scope="col" class="px-4 py-3 min-w-[100px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($methods as $method)
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  @if($method->user)
                    <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      {{ $method->user->name }}
                    </span>
                  @else
                    <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                      Umum (Global)
                    </span>
                  @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ $method->payment_name }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  {{ $method->bank_account ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  {{ $method->account_name ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-center text-sm font-medium">
                  <button wire:click="edit('{{ $method->id }}')" title="Edit" class="inline-flex items-center justify-center rounded-md bg-sky-500 p-2 text-white shadow-sm hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    </svg>
                  </button>
                  <button wire:click="confirmDelete('{{ $method->id }}')" title="Hapus" class="ml-2 inline-flex items-center justify-center rounded-md bg-red-600 p-2 text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada metode pembayaran ditemukan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $methods->links() }}
      </div>

    </div>
  </div>

  <!-- Form Modal -->
  <x-dialog-modal wire:model.live="isModalOpen">
    <x-slot name="title">
      {{ $payment_id ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran' }}
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="save" id="paymentForm">
        <div class="grid grid-cols-1 gap-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <x-label for="user_id" value="Karyawan" class="mb-1" />
              <x-tom-select 
                  id="user_id"
                  wire:model="user_id"
                  endpoint="/api/employees/search"
                  placeholder="Cari Karyawan..."
                  searchField="['name', 'nip']"
                  renderOption="function(item, escape) {
                      return `<div class='py-2 px-3'>
                          <span class='block font-semibold text-gray-800 dark:text-gray-200'>${escape(item.name)}</span>
                          <span class='block text-xs text-gray-500'>NIP: ${escape(item.nip)}</span>
                      </div>`;
                  }"
                  renderItem="function(item, escape) {
                      return `<div class='font-medium text-gray-900 dark:text-gray-100'>${escape(item.name)} (${escape(item.nip)})</div>`;
                  }"
              />
              <x-input-error for="user_id" class="mt-2" />
            </div>
  
            <div>
              <x-label for="payment_name" value="Nama Metode/Bank" class="mb-1" />
              <x-input id="payment_name" type="text" class="mt-1 block w-full" wire:model="payment_name" required placeholder="Contoh: BCA, Mandiri, Cash" />
              <x-input-error for="payment_name" class="mt-2" />
            </div>
          </div>

          <div>
            <x-label for="bank_account" value="Nomor Rekening (Opsional)" />
            <x-input id="bank_account" type="text" class="mt-1 block w-full" wire:model="bank_account" placeholder="Contoh: 1234567890" />
            <x-input-error for="bank_account" class="mt-2" />
          </div>

          <div>
            <x-label for="account_name" value="Atas Nama (Opsional)" />
            <x-input id="account_name" type="text" class="mt-1 block w-full" wire:model="account_name" placeholder="Contoh: Zaenal Alfian" />
            <x-input-error for="account_name" class="mt-2" />
          </div>
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ml-3" wire:click="save" wire:loading.attr="disabled">
        Simpan
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Delete Confirmation Modal -->
  <x-delete-modal 
      :isOpen="$isConfirmingDeletion" 
      title="Yakin ingin menghapus metode pembayaran ini secara permanen?" 
      deleteAction="delete" 
      cancelAction="cancelDelete" 
  />
</div>
