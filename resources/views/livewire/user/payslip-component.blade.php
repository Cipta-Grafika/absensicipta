<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Slip Gaji') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="sm:mr-1.5 h-4 w-4 text-sky-500" />
        <span class="hidden sm:inline">Filter</span>
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-0 sm:py-12" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
    
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Data</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('month', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="month_filter" value="Pilih Bulan Periode" class="mb-1"></x-label>
            <x-input type="month" id="month_filter" class="w-full block" wire:model.live="month" />
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <div class="overflow-hidden bg-white shadow-xl sm:rounded-lg dark:bg-gray-800">
      <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100">

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($payrolls as $pr)
          <div class="flex flex-col rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ \Carbon\Carbon::parse($pr->period_month)->format('F Y') }}
              </h3>
              <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Telah Dibayar</span>
            </div>
            <div class="mt-4 flex-1">
              <div class="text-sm text-gray-500 dark:text-gray-400">Total Pemasukan</div>
              <div class="text-lg font-medium text-green-600 dark:text-green-400">Rp {{ number_format($pr->basic_salary_earned + $pr->total_allowance + $pr->total_overtime_pay, 0, ',', '.') }}</div>
              
              <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Total Potongan</div>
              <div class="text-lg font-medium text-red-600 dark:text-red-400">Rp {{ number_format($pr->total_deduction, 0, ',', '.') }}</div>
              
              <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">Gaji Bersih (Take Home Pay)</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($pr->net_salary, 0, ',', '.') }}</div>
              </div>
            </div>
            
            <div class="mt-5 text-center">
              <a href="{{ route('user.payslip.print', $pr->id) }}" class="block w-full rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-green-600 dark:text-white dark:hover:bg-green-500 transition-colors">
                <div class="flex items-center justify-center">
                  <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                  Unduh PDF
                </div>
              </a>
            </div>
          </div>
        @empty
          <div class="col-span-1 text-center text-gray-500 sm:col-span-2 lg:col-span-3">
            Belum ada data slip gaji yang dibayarkan.
          </div>
        @endforelse
      </div>

      <div class="mt-6">
        {{ $payrolls->links() }}
      </div>

      </div>
    </div>
  </div>
</div>
