<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Riwayat Syirkah') }}
    </h2>
    <div class="flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="sm:mr-1.5 h-4 w-4 text-sky-500" />
        <span class="hidden sm:inline">Filter</span>
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-0 sm:py-12" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
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
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nominal Bulanan</th>
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
                <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-sky-600 dark:text-sky-400">
                  Rp{{ number_format($history->total_savings, 0, ',', '.') }}
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

  <x-filter-sidebar maxWidth="sm">
    <x-slot name="title">Filter Riwayat Syirkah</x-slot>
    <x-slot name="actions">
      <button type="button" wire:click="$set('month', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filter">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
      </button>
    </x-slot>
    <x-slot name="content">
      <div class="flex flex-col gap-6">
        <div>
          <x-label for="month_filter" value="Pilih Bulan Periode" class="mb-1"></x-label>
          <x-input type="month" id="month_filter" class="block w-full" wire:model.live="month" />
        </div>
      </div>
    </x-slot>
  </x-filter-sidebar>
</div>
