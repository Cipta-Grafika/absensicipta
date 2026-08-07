<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        {{ __('Absensi Hari Ini') }}
      </h2>
      <div class="absolute right-0 flex items-center gap-2">
        <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
          <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
          Filter
        </x-secondary-button>
      </div>
    </div>
  </x-slot>

  <div class="py-0 sm:py-6">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 sm:rounded-2xl">
        <div class="p-6 lg:p-8">
          @livewire('admin.dashboard-component')
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
