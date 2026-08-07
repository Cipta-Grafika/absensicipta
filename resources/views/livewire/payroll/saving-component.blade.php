<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Syirkah') }}
    </h2>
    <x-button x-data @click="$dispatch('open-saving-modal')" class="bg-sky-500 hover:bg-sky-600 focus:bg-sky-600 active:bg-sky-700">
      <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
      </svg>
      Tambah Baru
    </x-button>
  </div>
</x-slot>

<div class="py-0 sm:py-6" x-data @open-saving-modal.window="$wire.openModal()">
  <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
    <div class="bg-white p-6 shadow-none sm:shadow-xl dark:bg-gray-800 sm:rounded-lg lg:p-8">
      
      <div class="mb-4">
        <div class="flex w-full flex-1 items-center gap-2">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-10 pr-10" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Data Syirkah..." />
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
        <table class="w-full min-w-[700px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
          <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <tr>
              <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Syirkah</th>
              <th scope="col" class="px-4 py-3 min-w-[220px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nominal Syirkah Wajib</th>
              <th scope="col" class="px-4 py-3 min-w-[220px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nominal Syirkah Sukarela</th>
              <th scope="col" class="px-4 py-3 min-w-[120px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($savings as $saving)
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ $saving->savings_name ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  Rp {{ number_format($saving->mandatory_savings, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  Rp {{ number_format($saving->secondary_savings, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-center text-sm font-medium">
                  <button wire:click="edit('{{ $saving->id }}')" title="Edit" class="inline-flex items-center justify-center rounded-md bg-sky-500 p-2 text-white shadow-sm hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    </svg>
                  </button>
                  <button wire:click="confirmDelete('{{ $saving->id }}')" title="Hapus" class="ml-2 inline-flex items-center justify-center rounded-md bg-red-600 p-2 text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada data syirkah ditemukan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $savings->links() }}
      </div>

    </div>
  </div>

  <!-- Form Modal -->
  <x-dialog-modal wire:model.live="isModalOpen">
    <x-slot name="title">
      {{ $saving_id ? 'Edit Data Syirkah' : 'Tambah Data Syirkah' }}
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="save" id="savingForm">
        <div class="grid grid-cols-1 gap-4">
          <div>
            <x-label for="savings_name" value="Nama Syirkah" />
            <x-input id="savings_name" type="text" class="mt-1 block w-full" wire:model="savings_name" required placeholder="Contoh: Syirkah Reguler 2026" />
            <x-input-error for="savings_name" class="mt-2" />
          </div>

          <div>
            <x-label for="mandatory_savings" value="Nominal Syirkah Wajib" />
            <div class="relative mt-1 rounded-md shadow-sm">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="text-gray-500 sm:text-sm">Rp</span>
              </div>
              <x-input id="mandatory_savings" type="number" class="block w-full pl-10" wire:model="mandatory_savings" required placeholder="0" min="0" />
            </div>
            <x-input-error for="mandatory_savings" class="mt-2" />
          </div>

          <div>
            <x-label for="secondary_savings" value="Nominal Syirkah Sukarela" />
            <div class="relative mt-1 rounded-md shadow-sm">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="text-gray-500 sm:text-sm">Rp</span>
              </div>
              <x-input id="secondary_savings" type="number" class="block w-full pl-10" wire:model="secondary_savings" required placeholder="0" min="0" />
            </div>
            <x-input-error for="secondary_savings" class="mt-2" />
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
      title="Yakin ingin menghapus data syirkah ini secara permanen?" 
      deleteAction="delete" 
      cancelAction="cancelDelete" 
  />
</div>
