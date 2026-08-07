<x-app-layout>
  <x-slot name="header">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Import & Export') }} {{ __('Hari Libur') }}
    </h2>
  </x-slot>

  <div class="py-0 sm:py-6">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
      <div class="overflow-hidden bg-white shadow-none sm:shadow-xl dark:bg-gray-800 sm:rounded-lg">
        <div class="p-6 lg:p-8">
          @livewire('admin.import-export.holiday-component')
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
