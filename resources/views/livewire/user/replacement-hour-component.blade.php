<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Ganti Jam') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-button type="button" x-data @click.prevent="$dispatch('open-replacement-modal')">
        <x-heroicon-o-document-text class="mr-1.5 h-4 w-4" />
        Ajukan
      </x-button>
      <x-secondary-button href="{{ route('home') }}">
        <x-heroicon-o-chevron-left class="mr-1.5 h-4 w-4" />
        Kembali
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-0 sm:py-12" x-data @open-replacement-modal.window="$wire.openModal()">
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
                                <th class="px-4 py-3 whitespace-nowrap">Tgl Diganti</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tgl Ganti</th>
                                <th class="px-4 py-3 whitespace-nowrap">Shift (Target)</th>
                                <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                                <th class="px-4 py-3 whitespace-nowrap">Durasi</th>
                                <th class="px-4 py-3 whitespace-nowrap min-w-[200px]">Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @forelse ($replacements as $replacement)
                                <tr class="text-gray-700 dark:text-gray-400">
                                    <td class="px-4 py-3 text-sm">
                                        @if($replacement->status == 'pending')
                                            <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold leading-tight text-yellow-700 dark:bg-yellow-700 dark:text-yellow-100">
                                                Pending
                                            </span>
                                        @elseif($replacement->status == 'approved')
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
                                        {{ \Carbon\Carbon::parse($replacement->replaced_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ \Carbon\Carbon::parse($replacement->replacement_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ $replacement->shift ? $replacement->shift->name : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ \Carbon\Carbon::parse($replacement->start_hour)->format('H:i') }} - {{ \Carbon\Carbon::parse($replacement->end_hour)->format('H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold">
                                        {{ $replacement->formatted_duration }}
                                    </td>
                                    <td class="px-4 py-3 text-sm truncate max-w-[200px]" title="{{ $replacement->reason }}">
                                        {{ $replacement->reason }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-3 text-center text-sm text-gray-500">
                                        Belum ada riwayat pengajuan ganti jam.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $replacements->links() }}
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
                            Ajukan Ganti Jam
                        </h3>
                        <button wire:click="closeModal" class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" type="button">
                            <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Tutup modal</span>
                        </button>
                    </div>
                    
                    <form wire:submit.prevent="submit" class="p-4 md:p-5">
                        <div class="mb-4 grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Tgl Absen Diganti</label>
                                <input type="date" wire:model="replaced_date" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                                @error('replaced_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Tgl Penggantian</label>
                                <input type="date" wire:model="replacement_date" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                                @error('replacement_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4 grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Jam Mulai</label>
                                <input type="time" wire:model="start_hour" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                                @error('start_hour') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Jam Selesai</label>
                                <input type="time" wire:model="end_hour" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                                @error('end_hour') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Pilih Shift Yang Digantikan</label>
                            <select wire:model="shift_id" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                                <option value="">-- Pilih Shift --</option>
                                @foreach($shifts as $shift)
                                    @php
                                        $duration = \Carbon\Carbon::parse($shift->start_time)->diffInMinutes(\Carbon\Carbon::parse($shift->end_time));
                                        $hours = floor($duration / 60);
                                    @endphp
                                    <option value="{{ $shift->id }}">{{ $shift->name }} (Target: {{ $hours }} jam)</option>
                                @endforeach
                            </select>
                            @error('shift_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Alasan Penggantian</label>
                            <textarea wire:model="reason" rows="3" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" placeholder="Jelaskan alasan pengajuan ganti jam..." required></textarea>
                            @error('reason') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Lampiran (Wajib, Max 2MB, JPG/PNG)</label>
                            <div class="flex items-center gap-3 mt-1">
                                <input type="file" id="attachment" wire:model="attachment" class="hidden">
                                <label for="attachment" class="inline-flex cursor-pointer items-center rounded-md bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-800 transition hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                                    Pilih Lampiran
                                </label>
                                <div wire:loading wire:target="attachment" class="text-xs text-blue-500">Mengunggah...</div>
                                <span wire:loading.remove wire:target="attachment" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                    @if($attachment)
                                        {{ method_exists($attachment, 'getClientOriginalName') ? $attachment->getClientOriginalName() : 'File terpilih' }}
                                    @else
                                        Tidak ada file dipilih
                                    @endif
                                </span>
                            </div>
                            @error('attachment') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
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
