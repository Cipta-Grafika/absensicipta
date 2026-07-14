<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Riwayat Syirkah Karyawan') }}
    </h2>
  </div>
</x-slot>

<div class="py-0 sm:py-12">
  <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
    <div class="bg-white p-6 shadow-xl dark:bg-gray-800 sm:rounded-lg lg:p-8">
      
      <div class="mb-4">
        <div class="flex w-full flex-1 items-center gap-2">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-10 pr-10" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Karyawan atau Syirkah..." />
            @if ($search)
              <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            @endif
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Waktu</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Syirkah</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Potongan Bulan Ini</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Akumulasi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($histories as $history)
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  {{ $history->created_at->format('d M Y, H:i') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $history->user->name ?? '-' }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ $history->user->nip ?? '-' }}</div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  {{ $history->savings->savings_name ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  <ul class="list-disc pl-4 text-xs">
                    <li>Wajib: Rp{{ number_format($history->mandatory_savings, 0, ',', '.') }}</li>
                    <li>Sukarela: Rp{{ number_format($history->secondary_savings, 0, ',', '.') }}</li>
                  </ul>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300 font-semibold text-sky-600">
                  <ul class="list-disc pl-4 text-xs text-gray-900 dark:text-gray-300 font-normal mb-1">
                    <li>Wajib: Rp{{ number_format($history->total_mandatory, 0, ',', '.') }}</li>
                    <li>Sukarela: Rp{{ number_format($history->total_secondary, 0, ',', '.') }}</li>
                  </ul>
                  Total: Rp{{ number_format($history->total_savings, 0, ',', '.') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada riwayat syirkah ditemukan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $histories->links() }}
      </div>

    </div>
  </div>
</div>
