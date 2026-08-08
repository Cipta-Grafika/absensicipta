<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
          Import & Export Master Gaji
        </h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          Kelola & Sinkronkan Data Gaji Pokok serta Tunjangan Karyawan via Excel/CSV
        </p>
      </div>
      <a href="{{ asset('excel/employee_salaries.xlsx') }}" download class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl font-semibold text-xs text-white transition-all duration-150 bg-sky-500 hover:bg-sky-600 focus:bg-sky-600 active:bg-sky-700 shadow-md shadow-sky-500/20">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sm:mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        <span class="hidden sm:inline">Unduh Sampel</span>
      </a>
    </div>
  </x-slot>

  <div class="py-6">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
      <div class="bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-sky-200/80 dark:border-gray-800/80 shadow-2xl shadow-black/5 rounded-none sm:rounded-2xl overflow-hidden">
        <div class="p-6 lg:p-8">
          @livewire('payroll.import-export.employee-salary')
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
