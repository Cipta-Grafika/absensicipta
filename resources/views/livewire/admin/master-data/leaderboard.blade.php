<div class="py-0 sm:py-6"
     x-data="{
       eventSource: null,
       init() {
         if (!window.EventSource) return;
         const period = '{{ $period }}';
         this.eventSource = new EventSource('/api/leaderboard/stream?period=' + period);
         this.eventSource.addEventListener('leaderboard_updated', (e) => {
           @this.call('$refresh');
         });
       },
       destroy() {
         if (this.eventSource) {
           this.eventSource.close();
         }
       }
     }">
  <div class="w-full sm:px-6 lg:px-8">
    <div class="overflow-hidden bg-white p-6 shadow-xl dark:bg-gray-800 sm:rounded-lg">

      <!-- Header Section inside Livewire DOM Boundary -->
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-700">
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            Manajemen Leaderboard Kerajinan Karyawan
          </h2>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Hitung ulang dan perbarui statistik kerajinan serta kehadiran karyawan per periode bulan.
          </p>
        </div>
        <div>
          <x-button type="button" wire:click="syncLeaderboard" wire:loading.attr="disabled" class="!py-2.5 !px-4 bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-2 h-4 w-4" wire:loading.class="animate-spin" wire:target="syncLeaderboard">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            <span wire:loading.remove wire:target="syncLeaderboard">Generate & Hitung Ulang</span>
            <span wire:loading wire:target="syncLeaderboard">Proses Menghitung...</span>
          </x-button>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <x-label for="period" value="Pilih Periode Bulan" class="mb-1" />
          <x-input id="period" type="month" class="w-full" wire:model.live="period" />
        </div>

        <div>
          <x-label for="division_id" value="Filter Divisi" class="mb-1" />
          <x-select id="division_id" class="w-full" wire:model.live="division_id">
            <option value="">Semua Divisi</option>
            @foreach ($divisions as $div)
              <option value="{{ $div->id }}">{{ $div->name }}</option>
            @endforeach
          </x-select>
        </div>

        <div>
          <x-label for="search" value="Cari Karyawan" class="mb-1" />
          <x-input id="search" type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Cari nama / NIP..." />
        </div>
      </div>

      <!-- Leaderboard Data Table -->
      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Peringkat
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Karyawan
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Divisi
              </th>
              <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Total Hadir
              </th>
              <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Terlambat
              </th>
              <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Total Menit Awal
              </th>
              <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Rata-rata Menit Awal
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Skor Performa
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($stats as $st)
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="whitespace-nowrap px-4 py-4 text-center text-sm font-bold text-gray-900 dark:text-white">
                  @if ($st->rank === 1)
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-400 text-xs text-slate-950 font-extrabold shadow-sm">1</span>
                  @elseif ($st->rank === 2)
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-300 text-xs text-slate-900 font-extrabold shadow-sm">2</span>
                  @elseif ($st->rank === 3)
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-700 text-xs text-white font-extrabold shadow-sm">3</span>
                  @else
                    <span class="text-gray-500 dark:text-gray-400">#{{ $st->rank }}</span>
                  @endif
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                  {{ $st->user->name ?? '-' }}
                  <span class="block text-xs font-normal text-gray-400">{{ $st->user->nip ?? '' }}</span>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                  {{ $st->division->name ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-center text-sm font-medium text-emerald-600 dark:text-emerald-400">
                  {{ $st->total_present }} Hari
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-center text-sm font-medium text-red-500 dark:text-red-400">
                  {{ $st->total_late }} Kali
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                  {{ $st->total_early_minutes }} Menit
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                  {{ $st->avg_early_minutes }} Menit / Hari
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-right text-base font-extrabold text-indigo-600 dark:text-indigo-400">
                  {{ number_format($st->score, 1) }} pts
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                  Belum ada data statistik leaderboard untuk periode {{ $period }}. Silakan klik tombol <b>Generate & Hitung Ulang</b>.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $stats->links() }}
      </div>

    </div>
  </div>
</div>
