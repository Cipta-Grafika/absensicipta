<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Ganti Jam') }}
    </h2>
    <div class="flex items-center gap-2">
      <x-secondary-button href="{{ route('home') }}">
        <x-heroicon-o-chevron-left class="mr-1.5 h-4 w-4" />
        Kembali
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-0 sm:py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

        <!-- UNIFIED CARD CONTAINER: CALENDAR & REPLACEMENT HOURS HISTORY TABLE -->
        <div class="overflow-hidden bg-white p-6 shadow-xl sm:rounded-lg dark:bg-gray-800 text-gray-900 dark:text-gray-100 space-y-8">
            
            <!-- SECTION 1: INTERACTIVE MONTHLY REPLACEMENT HOURS CALENDAR -->
            <div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center justify-between border-b border-gray-200/80 pb-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Kalender Ganti Jam {{ \Carbon\Carbon::parse($month)->isoFormat('MMMM YYYY') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Klik pada tanggal kosong untuk mengajukan ganti jam (IMP), atau klik tanggal aktif untuk melihat detail pengajuan
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-label for="month_filter" value="Pilih Bulan" class="whitespace-nowrap"></x-label>
                        <x-input type="month" name="month_filter" id="month_filter" wire:model.live="month" class="text-sm" />
                    </div>
                </div>

                @php
                  /* Calendar is Sunday-first: M=Minggu(Sun) S=Senin(Mon) S=Selasa(Tue) R=Rabu(Wed) K=Kamis(Thu) J=Jumat(Fri) S=Sabtu(Sat) */
                  $calDayLabels = ['M', 'S', 'S', 'R', 'K', 'J', 'S'];
                  $calDayNames  = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                  $calOffDays   = $offDays ?? ['sunday'];
                @endphp

                <div class="mt-4 overflow-x-auto">
                    <div class="grid w-full min-w-[320px] grid-cols-7 dark:text-white">
                        @foreach ($calDayLabels as $idx => $dayAbbr)
                            @php
                                $isOffHeader = in_array($calDayNames[$idx], $calOffDays, true);
                            @endphp
                            <div class="{{ $isOffHeader ? 'text-red-500' : '' }} flex h-10 items-center justify-center border border-gray-300 text-center font-bold text-sm dark:border-gray-600 bg-gray-50 dark:bg-gray-750">
                                {{ $dayAbbr }}
                            </div>
                        @endforeach

                        @if ($start->dayOfWeek !== 0)
                            @foreach (range(1, $start->dayOfWeek) as $i)
                                <div class="h-16 border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700/50"></div>
                            @endforeach
                        @endif

                        @foreach ($dates as $date)
                            @php
                                $dateStr = $date->format('Y-m-d');
                                $existingReplacement = $monthReplacements->first(function ($item) use ($dateStr) {
                                    return \Carbon\Carbon::parse($item->replaced_date)->format('Y-m-d') === $dateStr;
                                });
                                $hasImpAttendance = in_array($dateStr, $monthImpDates ?? [], true);

                                $isWorkingDay = \App\Services\AttendanceScheduleService::isWorkingDay(auth()->user(), $date);
                                $dayIsOff = !$isWorkingDay && in_array(strtolower($date->format('l')), $calOffDays, true);
                                
                                $isModalActive = (($isDateModalOpen ?? false) || ($isDetailModalOpen ?? false));
                                $isActiveSubmittedDate = ($isModalActive && (($activeCalendarDate ?? null) === $dateStr || ($replaced_date ?? null) === $dateStr));

                                if ($isActiveSubmittedDate) {
                                    // Solid Sky Blue background when modal is active for this date
                                    $bgColor = 'bg-sky-600 dark:bg-sky-600 text-white font-bold border-2 border-sky-700 ring-2 ring-sky-300 shadow-md transform scale-105 z-10';
                                    $dateNumClass = 'text-white font-bold';
                                } elseif ($existingReplacement) {
                                    switch ($existingReplacement->status) {
                                        case 'approved':
                                            $bgColor = 'bg-emerald-50 dark:bg-emerald-950/70 border-2 border-emerald-400 dark:border-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/80 shadow-xs';
                                            $dateNumClass = 'font-bold text-emerald-950 dark:text-emerald-100';
                                            break;
                                        case 'pending':
                                            $bgColor = 'bg-amber-50 dark:bg-amber-950/70 border-2 border-amber-400 dark:border-amber-600 hover:bg-amber-100 dark:hover:bg-amber-900/80 shadow-xs';
                                            $dateNumClass = 'font-bold text-amber-950 dark:text-amber-100';
                                            break;
                                        case 'rejected':
                                            $bgColor = 'bg-rose-50 dark:bg-rose-950/70 border-2 border-rose-400 dark:border-rose-600 hover:bg-rose-100 dark:hover:bg-rose-900/80 shadow-xs';
                                            $dateNumClass = 'font-bold text-rose-950 dark:text-rose-100';
                                            break;
                                        default:
                                            $bgColor = 'bg-sky-50 dark:bg-sky-950/70 border-2 border-sky-400 dark:border-sky-600 hover:bg-sky-100 dark:hover:bg-sky-900/80 shadow-xs';
                                            $dateNumClass = 'font-bold text-sky-950 dark:text-sky-100';
                                            break;
                                    }
                                } elseif ($hasImpAttendance) {
                                    // Date HAS IMP attendance: Always Active Soft Pastel Blue Box (Gambar 2)
                                    $bgColor = 'bg-sky-100/90 dark:bg-sky-950/70 border-2 border-sky-300 dark:border-sky-700 hover:bg-sky-200/90 dark:hover:bg-sky-900/80 shadow-xs';
                                    $dateNumClass = 'font-bold text-sky-950 dark:text-sky-100';
                                } else {
                                    // Normal Empty Date Box: Hover shows blue pastel
                                    $dateNumClass = $dayIsOff ? 'text-red-500 font-bold' : 'font-semibold text-gray-800 dark:text-gray-200';
                                    $bgColor = 'bg-white dark:bg-gray-800 hover:bg-sky-100/80 dark:hover:bg-gray-700/80 border border-gray-300 dark:border-gray-600 transition-colors';
                                }
                            @endphp

                            <button type="button"
                                    wire:click="handleDateClick('{{ $dateStr }}')"
                                    class="{{ $bgColor }} flex flex-col items-center justify-between h-16 w-full p-2 text-center cursor-pointer group focus:outline-none focus:ring-0 active:outline-none outline-none select-none">
                                <span class="{{ $dateNumClass }} text-sm font-bold leading-none">
                                    {{ $date->format('d') }}
                                </span>

                                @if ($existingReplacement)
                                    @if ($existingReplacement->status === 'approved')
                                        <div class="bg-emerald-600 text-white font-bold text-[10px] p-1 sm:px-2.5 sm:py-0.5 rounded-full shadow-xs flex items-center justify-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-white shrink-0"></span>
                                            <span class="hidden sm:inline leading-none">Approved</span>
                                        </div>
                                    @elseif ($existingReplacement->status === 'pending')
                                        <div class="bg-amber-500 text-white font-bold text-[10px] p-1 sm:px-2.5 sm:py-0.5 rounded-full shadow-xs flex items-center justify-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-white shrink-0"></span>
                                            <span class="hidden sm:inline leading-none">Pending</span>
                                        </div>
                                    @elseif ($existingReplacement->status === 'rejected')
                                        <div class="bg-rose-600 text-white font-bold text-[10px] p-1 sm:px-2.5 sm:py-0.5 rounded-full shadow-xs flex items-center justify-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-white shrink-0"></span>
                                            <span class="hidden sm:inline leading-none">Rejected</span>
                                        </div>
                                    @else
                                        <div class="bg-sky-600 text-white font-bold text-[10px] p-1 sm:px-2.5 sm:py-0.5 rounded-full shadow-xs flex items-center justify-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-white shrink-0"></span>
                                            <span class="hidden sm:inline leading-none">Terisi</span>
                                        </div>
                                    @endif
                                @elseif ($isActiveSubmittedDate)
                                    <span class="text-[10px] text-white font-bold leading-none">
                                        + Ganti Jam
                                    </span>
                                @elseif ($hasImpAttendance)
                                    <span class="text-[10px] font-semibold text-sky-600 dark:text-sky-400 leading-none">
                                        + Ganti Jam
                                    </span>
                                @else
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity leading-none">
                                        + Ganti Jam
                                    </span>
                                @endif
                            </button>
                        @endforeach

                        @if ($end->dayOfWeek !== 6)
                            @foreach (range($end->dayOfWeek + 1, 6) as $i)
                                <div class="h-16 border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700/50"></div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- DIVIDER WITH ELEGANT SPACING -->
            <div class="border-t border-gray-200/80 pt-6 dark:border-gray-700">
                <!-- SECTION 2: REPLACEMENT HOURS HISTORY TABLE -->
                <div class="mb-4 flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <h4 class="text-base font-bold text-gray-800 dark:text-gray-200">
                        Riwayat Pengajuan Ganti Jam
                    </h4>
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
                                <tr wire:key="replacement-row-{{ $replacement->id }}" class="text-gray-700 hover:bg-gray-50/50 dark:hover:bg-gray-750 dark:text-gray-400 transition-colors">
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
                                    <td colspan="7" class="px-4 py-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
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

    <!-- DETAIL REPLACEMENT HOUR MODAL (For existing active date clicks) -->
    @if(($isDetailModalOpen ?? false) && $selectedReplacement)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black bg-opacity-50">
            <div class="relative w-full max-w-lg p-4">
                <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
                    <div class="flex items-center justify-between rounded-t border-b p-4 md:p-5 dark:border-gray-600">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Detail Pengajuan Ganti Jam
                            </h3>
                            <p class="text-xs text-sky-600 dark:text-sky-400 font-semibold mt-0.5">
                                {{ $selectedDateDisplay }}
                            </p>
                        </div>
                        <button wire:click="closeDetailModal" class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" type="button">
                            <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Tutup modal</span>
                        </button>
                    </div>

                    <div class="p-4 md:p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Status</span>
                                @if($selectedReplacement->status == 'pending')
                                    <span class="inline-flex mt-1 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-bold text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">
                                        Pending
                                    </span>
                                @elseif($selectedReplacement->status == 'approved')
                                    <span class="inline-flex mt-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-700 dark:text-emerald-100">
                                        Approved
                                    </span>
                                @else
                                    <span class="inline-flex mt-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-700 dark:text-rose-100">
                                        Rejected
                                    </span>
                                @endif
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Tgl Absen Diganti</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($selectedReplacement->replaced_date)->format('d M Y') }}
                                </span>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Tgl Penggantian</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($selectedReplacement->replacement_date)->format('d M Y') }}
                                </span>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Shift Target</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedReplacement->shift ? $selectedReplacement->shift->name : '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Waktu</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($selectedReplacement->start_hour)->format('H:i') }} - {{ \Carbon\Carbon::parse($selectedReplacement->end_hour)->format('H:i') }}
                                </span>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Durasi</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $selectedReplacement->formatted_duration }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Alasan Penggantian</label>
                            <div class="w-full min-h-[80px] rounded-lg bg-gray-50 p-3.5 text-left text-sm font-medium leading-relaxed text-gray-800 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ trim($selectedReplacement->reason) }}</div>
                        </div>

                        @if($selectedReplacement->attachment)
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Lampiran Bukti</label>
                                <a href="{{ asset('storage/' . $selectedReplacement->attachment) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-semibold text-sky-600 hover:text-sky-700 dark:text-sky-400 underline">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat Lampiran Bukti
                                </a>
                            </div>
                        @endif

                        <div class="flex items-center justify-end rounded-b border-t border-gray-200 pt-4 dark:border-gray-600">
                            <button wire:click="closeDetailModal" type="button" class="rounded-lg bg-gray-200 px-5 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- DEDICATED SUBMISSION FORM MODAL (Triggered exclusively from date box clicks) -->
    @if($isDateModalOpen ?? false)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black bg-opacity-50">
            <div class="relative w-full max-w-lg p-4">
                <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
                    <div class="flex items-center justify-between rounded-t border-b p-4 md:p-5 dark:border-gray-600">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Ajukan Ganti Jam
                            </h3>
                            <p class="text-xs text-sky-600 dark:text-sky-400 font-semibold mt-0.5">
                                {{ $selectedDateDisplay }}
                            </p>
                        </div>
                        <button wire:click="closeDateModal" class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white" type="button">
                            <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Tutup modal</span>
                        </button>
                    </div>
                    
                    <form wire:submit.prevent="submitDateModal" class="p-4 md:p-5">
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

                        <input type="hidden" wire:model="replaced_date">

                        <div class="mb-4 rounded-lg bg-sky-50 p-3.5 border border-sky-200 dark:bg-sky-950/50 dark:border-sky-800">
                            <span class="text-xs font-semibold text-sky-700 dark:text-sky-300 block uppercase tracking-wider">Tanggal Absen Diganti (IMP)</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white mt-0.5 block">
                                {{ $selectedDateDisplay }}
                            </span>
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Tgl Penggantian</label>
                            <input type="date" wire:model="replacement_date" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" required>
                            @error('replacement_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4 grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Jam Mulai</label>
                                <input type="text"
                                       inputmode="numeric"
                                       placeholder="10:30"
                                       maxlength="5"
                                       x-data
                                       x-on:input="
                                         let v = $el.value.replace(/\D/g, '').slice(0, 4);
                                         if (v.length >= 3) v = v.slice(0, 2) + ':' + v.slice(2);
                                         $el.value = v;
                                         $wire.set('start_hour', v);
                                       "
                                       value="{{ $start_hour }}"
                                       class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm font-semibold tracking-wider text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                                       required>
                                <span class="text-[11px] text-gray-400">Format: 10:30</span>
                                @error('start_hour') <span class="block text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Jam Selesai</label>
                                <input type="text"
                                       inputmode="numeric"
                                       placeholder="17:30"
                                       maxlength="5"
                                       x-data
                                       x-on:input="
                                         let v = $el.value.replace(/\D/g, '').slice(0, 4);
                                         if (v.length >= 3) v = v.slice(0, 2) + ':' + v.slice(2);
                                         $el.value = v;
                                         $wire.set('end_hour', v);
                                       "
                                       value="{{ $end_hour }}"
                                       class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm font-semibold tracking-wider text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                                       required>
                                <span class="text-[11px] text-gray-400">Format: 17:30</span>
                                @error('end_hour') <span class="block text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Pilih Shift Yang Digantikan</label>
                            <select wire:model="shift_id" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm font-semibold text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white" required>
                                <option value="">-- Pilih Shift --</option>
                                @php
                                    $userDivId = auth()->user()->division_id;
                                    $divisionShifts = $shifts->filter(fn($s) => $s->division_id == $userDivId && !is_null($s->division_id));
                                    $globalShifts = $shifts->filter(fn($s) => is_null($s->division_id));
                                @endphp

                                @if($divisionShifts->count() > 0)
                                    <optgroup label="Shift Divisi (Prioritas Utama)">
                                        @foreach($divisionShifts as $shift)
                                            @php
                                                $duration = \Carbon\Carbon::parse($shift->start_time)->diffInMinutes(\Carbon\Carbon::parse($shift->end_time));
                                                $hours = floor($duration / 60);
                                            @endphp
                                            <option value="{{ $shift->id }}">{{ $shift->name }} (Target: {{ $hours }} jam)</option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if($globalShifts->count() > 0)
                                    <optgroup label="Shift Global">
                                        @foreach($globalShifts as $shift)
                                            @php
                                                $duration = \Carbon\Carbon::parse($shift->start_time)->diffInMinutes(\Carbon\Carbon::parse($shift->end_time));
                                                $hours = floor($duration / 60);
                                            @endphp
                                            <option value="{{ $shift->id }}">{{ $shift->name }} (Target: {{ $hours }} jam)</option>
                                        @endforeach
                                    </optgroup>
                                @endif
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
                                <input type="file" id="attachment_date" wire:model="attachment" class="hidden">
                                <label for="attachment_date" class="inline-flex cursor-pointer items-center rounded-md bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-800 transition hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
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
                            <button wire:click="closeDateModal" type="button" class="mr-3 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">Batal</button>
                            <button type="submit" class="rounded-lg bg-sky-500 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-300 dark:bg-sky-500 dark:hover:bg-sky-400 dark:focus:ring-sky-800 transition">Ajukan Ganti Jam</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
