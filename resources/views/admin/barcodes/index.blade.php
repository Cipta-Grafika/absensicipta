<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        {{ __('Barcode') }}
      </h2>
      <div class="absolute right-0 flex items-center gap-2">
        <x-secondary-button href="{{ route('hr.barcodes.downloadall') }}" class="!py-1.5 !px-3">
          <x-heroicon-o-arrow-down-tray class="mr-1.5 h-4 w-4 text-sky-500" />
          Download Semua
        </x-secondary-button>
        <x-button href="{{ route('hr.barcodes.create') }}" class="!py-1.5 !px-3">
          <x-heroicon-o-plus class="mr-1.5 h-4 w-4" />
          Buat Baru
        </x-button>
      </div>
    </div>
  </x-slot>

  <div class="py-0 sm:py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
      <div class="overflow-hidden bg-white shadow-xl dark:bg-gray-800 sm:rounded-lg">
        @livewire('admin.barcode-component')
      </div>
    </div>
  </div>
</x-app-layout>
