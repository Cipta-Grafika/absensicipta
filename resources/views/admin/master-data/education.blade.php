<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        {{ __('Education') }}
      </h2>
      <div class="absolute right-0 flex items-center gap-2">
        <x-button type="button" x-data @click.prevent="Livewire.dispatch('show-creating')" class="!py-1.5 !px-3">
          <x-heroicon-o-plus class="mr-1.5 h-4 w-4" />
          Tambah
        </x-button>
      </div>
    </div>
  </x-slot>

  <div class="py-0 sm:py-6">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="bg-white shadow-none sm:shadow-xl dark:bg-gray-800 sm:rounded-lg">
        <div class="p-6 lg:p-8">
          @livewire('admin.master-data.education-component')
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
