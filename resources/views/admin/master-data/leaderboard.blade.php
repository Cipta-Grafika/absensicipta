<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center justify-between">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        {{ __('Leaderboard Kerajinan') }}
      </h2>
    </div>
  </x-slot>

  <div class="py-0 sm:py-6">
    <div class="w-full sm:px-6 lg:px-8">
      @livewire('admin.master-data.leaderboard-component')
    </div>
  </div>
</x-app-layout>
