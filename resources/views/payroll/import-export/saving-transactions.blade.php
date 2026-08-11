<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center justify-between">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Import & Export Mutasi Syirkah
      </h2>
      <a href="{{ asset('excel/saving_transactions.xlsx') }}" download class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl font-semibold text-xs text-white transition-all duration-150 bg-sky-500 hover:bg-sky-600 focus:bg-sky-600 active:bg-sky-700 shadow-md shadow-sky-500/20">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Unduh Sampel
      </a>
    </div>
  </x-slot>

  <div class="pt-3.5 pb-6 sm:py-6">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden">
        <div class="p-4 sm:p-6 lg:p-8">
          @livewire('payroll.import-export.saving-transaction-component')
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
