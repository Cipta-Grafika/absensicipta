<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {{ __('Ekspor Lembur (Overtime)') }}
        </h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          Ekspor data lembur ke format Peachtree 2011 Accounting CSV dan Microsoft Excel (XLSX)
        </p>
      </div>
    </div>
  </x-slot>

  <div class="py-4 sm:py-6">
    <div class="w-full px-4 sm:px-6 lg:px-8">
      @livewire('admin.import-export.overtime')
    </div>
  </div>
</x-app-layout>
