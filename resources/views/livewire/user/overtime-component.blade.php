<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Lembur') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-button type="button" x-data @click.prevent="$dispatch('open-overtime-modal')">
        <x-heroicon-o-clock class="mr-1.5 h-4 w-4" />
        Ajukan
      </x-button>
      <x-secondary-button href="{{ route('home') }}">
        <x-heroicon-o-chevron-left class="mr-1.5 h-4 w-4" />
        Kembali
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-0 sm:py-12" x-data @open-overtime-modal.window="$wire.openModal()">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xl sm:rounded-lg dark:bg-gray-800">
            <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100">

                <div class="mb-4 flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-2">
                        <label for="perPage" class="text-sm font-medium text-gray-700 dark:text-gray-300">Tampilkan:</label>
                        <select wire:model.live="perPage" id="perPage" class="w-20 truncate rounded-md border border-gray-300 bg-gray-50 py-1 pl-2 pr-7 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="all">Semua</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap table-auto text-left">
                        <thead>
                            <tr class="border-b bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                <th class="px-4 py-3 whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tanggal Lembur</th>
                                <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                                <th class="px-4 py-3 whitespace-nowrap">Durasi</th>
                                <th class="px-4 py-3 whitespace-nowrap">Bayaran</th>
                                <th class="px-4 py-3 whitespace-nowrap min-w-[200px]">Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @forelse ($overtimes as $overtime)
                                <tr class="text-gray-700 dark:text-gray-400">
                                    <td class="px-4 py-3 text-sm">
                                        @if($overtime->status == 'pending')
                                            <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold leading-tight text-yellow-700 dark:bg-yellow-700 dark:text-yellow-100">
                                                Pending
                                            </span>
                                        @elseif($overtime->status == 'approved')
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold leading-tight text-green-700 dark:bg-green-700 dark:text-green-100">
                                                Approved
                                            </span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold leading-tight text-red-700 dark:bg-red-700 dark:text-red-100">
                                                Rejected
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ \Carbon\Carbon::parse($overtime->overtime_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold">
                                        {{ $overtime->duration_hours }} Jam
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                        @if(!is_null($overtime->total_pay))
                                            Rp {{ number_format($overtime->total_pay, 0, ',', '.') }}
                                        @else
                                            @php
                                                $est = \App\Models\OvertimeRate::calculatePayForDuration((float) $overtime->duration_hours, Auth::user());
                                            @endphp
                                            <span class="text-gray-400 dark:text-gray-500 font-medium text-xs">(Est: Rp {{ number_format($est['total_pay'], 0, ',', '.') }})</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm truncate max-w-[200px]" title="{{ $overtime->reason }}">
                                        {{ $overtime->reason }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Belum ada riwayat pengajuan lembur.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $overtimes->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black bg-opacity-50">
            <div class="relative w-full max-w-2xl p-4">
                <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
                    <div class="flex items-center justify-between rounded-t border-b p-4 md:p-5 dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Ajukan Lembur
                        </h3>
                        <button wire:click="closeModal" class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" type="button">
                            <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Tutup modal</span>
                        </button>
                    </div>
                    
                    <form wire:submit.prevent="submit" class="p-4 md:p-5">
                        @if($modalError)
                            <div class="mb-4 flex items-center rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-gray-800 dark:text-red-400" role="alert">
                                <svg class="me-3 inline h-4 w-4 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                                </svg>
                                <div>
                                    {{ $modalError }}
                                </div>
                            </div>
                        @endif

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Tanggal Lembur</label>
                            <input type="date" wire:model="overtime_date" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                            @error('overtime_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4 grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Jam Mulai</label>
                                <input type="time" wire:model="start_time" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                                @error('start_time') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Jam Selesai</label>
                                <input type="time" wire:model="end_time" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                                @error('end_time') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Alasan / Kegiatan Lembur</label>
                            <textarea wire:model="reason" rows="3" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" placeholder="Jelaskan detail kegiatan atau alasan lembur..." required></textarea>
                            @error('reason') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-end rounded-b border-t border-gray-200 pt-4 dark:border-gray-600">
                            <button wire:click="closeModal" type="button" class="mr-3 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">Batal</button>
                            <button type="submit" class="rounded-lg bg-sky-500 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-300 dark:bg-sky-500 dark:hover:bg-sky-400 dark:focus:ring-sky-800 transition">Ajukan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
