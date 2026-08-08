@props(['isOpen' => false, 'title' => 'Yakin ingin menghapus data ini secara permanen?', 'deleteAction' => 'delete', 'cancelAction' => 'cancelDelete'])

@if($isOpen)
  <div x-data="{ show: true }">
    <template x-teleport="body">
      <div x-show="show" class="fixed inset-0 z-[300] flex items-center justify-center overflow-y-auto overflow-x-hidden p-4">
        <!-- Backdrop -->
        <div x-show="show"
          class="fixed inset-0 z-[301] bg-gray-900/60 dark:bg-gray-950/80 backdrop-blur-xs sm:backdrop-blur-sm transition-opacity"
          x-transition:enter="ease-out duration-150"
          x-transition:enter-start="opacity-0"
          x-transition:enter-end="opacity-100"
          x-transition:leave="ease-in duration-150"
          x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0"
          wire:click="{{ $cancelAction }}">
        </div>

        <!-- Dialog Box -->
        <div x-show="show"
          class="relative w-full max-w-sm p-4 z-[305] transform transition-all will-change-transform"
          x-transition:enter="ease-out duration-150"
          x-transition:enter-start="opacity-0 scale-[0.98] translate-y-2"
          x-transition:enter-end="opacity-100 scale-100 translate-y-0"
          x-transition:leave="ease-in duration-150"
          x-transition:leave-start="opacity-100 scale-100 translate-y-0"
          x-transition:leave-end="opacity-0 scale-[0.98] translate-y-2">
          <div class="relative rounded-2xl bg-white/95 shadow-2xl dark:bg-gray-900/95 backdrop-blur-xl border border-white/60 dark:border-gray-700/60">
            <div class="p-6 text-center">
              <svg class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
              </svg>
              <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">{{ $title }}</h3>
              <button wire:click="{{ $deleteAction }}" type="button" class="inline-flex items-center rounded-lg bg-red-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800">
                Ya, Hapus
              </button>
              <button wire:click="{{ $cancelAction }}" type="button" class="ms-3 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">
                Batal
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
@endif
