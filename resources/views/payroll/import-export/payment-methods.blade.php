<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center justify-between">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Metode Pembayaran
      </h2>
      <a href="{{ asset('excel/payment_methods.xlsx') }}" download class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white transition ease-in-out duration-150 bg-sky-500 hover:bg-sky-600 focus:bg-sky-600 active:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sm:mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        <span class="hidden sm:inline">Unduh Sampel</span>
      </a>
    </div>
  </x-slot>

  <div class="py-0 sm:py-6">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
      <div class="bg-white shadow-xl dark:bg-gray-800 sm:rounded-lg">
        <div class="p-6 lg:p-8">
          @livewire('payroll.import-export.payment-method')
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
