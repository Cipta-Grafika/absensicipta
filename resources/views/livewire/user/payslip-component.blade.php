<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Slip Gaji Saya (Payslips)') }}
    </h2>
  </div>
</x-slot>

<div class="py-0 sm:py-12">
  <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
    <div class="bg-white p-6 shadow-xl dark:bg-gray-800 sm:rounded-lg lg:p-8">

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
              <button disabled class="w-full rounded bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500">
                <div class="flex items-center justify-center">
                  <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  Unduh PDF (Segera)
                </div>
              </button>
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
