<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Lembur') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-secondary-button href="{{ route('home') }}">
        <x-heroicon-o-chevron-left class="mr-1.5 h-4 w-4" />
        Kembali
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="flex-grow flex flex-col py-0 sm:py-10">
    <div class="mx-auto w-full max-w-7xl px-0 sm:px-6 lg:px-8 flex-grow flex flex-col">

        <!-- UNIFIED CARD CONTAINER: CALENDAR & OVERTIME HISTORY TABLE -->
        <div class="overflow-hidden bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/80 dark:border-gray-800/80 shadow-2xl shadow-black/5 rounded-none sm:rounded-2xl p-4 sm:p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-8 flex-grow flex flex-col transition-all duration-300">
            
            <!-- SECTION 1: INTERACTIVE MONTHLY OVERTIME CALENDAR -->
            <div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center justify-between border-b border-gray-200/80 pb-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Kalender Lembur {{ \Carbon\Carbon::parse($month)->isoFormat('MMMM YYYY') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Klik pada tanggal kosong untuk mengajukan lembur, atau klik tanggal aktif untuk melihat detail pengajuan
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
                            <div class="{{ $isOffHeader ? 'text-red-500' : '' }} flex h-10 items-center justify-center border border-gray-300 text-center font-bold text-sm dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
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
                                $existingOvertime = $monthOvertimes->first(function ($item) use ($dateStr) {
                                    return \Carbon\Carbon::parse($item->overtime_date)->format('Y-m-d') === $dateStr;
                                });
                                $isWorkingDay = \App\Services\AttendanceScheduleService::isWorkingDay(auth()->user(), $date);
                                $dayIsOff = !$isWorkingDay;
                                
                                $isModalActive = (($isDateModalOpen ?? false) || ($isDetailModalOpen ?? false));
                                $isActiveSubmittedDate = ($isModalActive && (($activeCalendarDate ?? null) === $dateStr || ($overtime_date ?? null) === $dateStr));

                                if ($isActiveSubmittedDate) {
                                    // Solid Sky Blue background when modal is active for this date
                                    $bgColor = 'bg-sky-600 dark:bg-sky-600 text-white font-bold border-2 border-sky-700 ring-2 ring-sky-300 shadow-md transform scale-105 z-10';
                                    $dateNumClass = 'text-white font-bold';
                                } elseif ($existingOvertime) {
                                    switch ($existingOvertime->status) {
                                        case 'paid':
                                            $bgColor = 'bg-blue-50 dark:bg-blue-950/70 border-2 border-blue-400 dark:border-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/80 shadow-xs';
                                            $dateNumClass = 'font-bold text-blue-950 dark:text-blue-100';
                                            break;
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
                                } else {
                                    // Normal Empty Date Box
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

                                @if ($existingOvertime)
                                    @if ($existingOvertime->status === 'paid')
                                        <div class="bg-blue-600 text-white font-bold text-[10px] p-1 sm:px-2.5 sm:py-0.5 rounded-full shadow-xs flex items-center justify-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-white shrink-0"></span>
                                            <span class="hidden sm:inline leading-none">Paid</span>
                                        </div>
                                    @elseif ($existingOvertime->status === 'approved')
                                        <div class="bg-emerald-600 text-white font-bold text-[10px] p-1 sm:px-2.5 sm:py-0.5 rounded-full shadow-xs flex items-center justify-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-white shrink-0"></span>
                                            <span class="hidden sm:inline leading-none">Approved</span>
                                        </div>
                                    @elseif ($existingOvertime->status === 'pending')
                                        <div class="bg-amber-500 text-white font-bold text-[10px] p-1 sm:px-2.5 sm:py-0.5 rounded-full shadow-xs flex items-center justify-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-white shrink-0"></span>
                                            <span class="hidden sm:inline leading-none">Pending</span>
                                        </div>
                                    @elseif ($existingOvertime->status === 'rejected')
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
                                        + Form Lembur
                                    </span>
                                @elseif ($dayIsOff)
                                    <span class="text-[10px] font-extrabold text-red-500 dark:text-red-400 group-hover:hidden leading-none">
                                        OFF
                                    </span>
                                    <span class="text-[10px] text-sky-600 dark:text-sky-400 font-semibold hidden group-hover:inline leading-none">
                                        + Ajukan
                                    </span>
                                @else
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity leading-none">
                                        + Ajukan
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
                <!-- SECTION 2: OVERTIME REQUESTS HISTORY TABLE -->
                <div class="mb-4 flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <h4 class="text-base font-bold text-gray-800 dark:text-gray-200">
                        Riwayat Pengajuan Lembur
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
                                <th class="px-4 py-3 whitespace-nowrap">Tanggal Lembur</th>
                                <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                                <th class="px-4 py-3 whitespace-nowrap">Durasi</th>
                                <th class="px-4 py-3 whitespace-nowrap">Estimasi Bayaran</th>
                                <th class="px-4 py-3 whitespace-nowrap min-w-[200px]">Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @forelse ($overtimes as $overtime)
                                <tr wire:key="overtime-row-{{ $overtime->id }}" class="text-gray-700 hover:bg-gray-50/50 dark:hover:bg-gray-750 dark:text-gray-400 transition-colors">
                                    <td class="px-4 py-3 text-sm">
                                        @if($overtime->status == 'pending')
                                            <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold leading-tight text-yellow-700 dark:bg-yellow-700 dark:text-yellow-100">
                                                Pending
                                            </span>
                                        @elseif($overtime->status == 'approved')
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold leading-tight text-green-700 dark:bg-green-700 dark:text-green-100">
                                                Approved
                                            </span>
                                        @elseif($overtime->status == 'paid')
                                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold leading-tight text-blue-700 dark:bg-blue-900/60 dark:text-blue-200">
                                                Paid (Dibayar)
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
                                        {{ $overtime->formatted_duration }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                        @if(in_array($overtime->status, ['approved', 'paid']) && $overtime->overtime_pay > 0)
                                            Rp {{ number_format($overtime->overtime_pay, 0, ',', '.') }}
                                        @elseif($overtime->duration_hours > 0)
                                            <span class="text-gray-500 dark:text-gray-400 font-normal">
                                                ~ Rp {{ number_format($overtime->calculateEstimatedPay(), 0, ',', '.') }}
                                            </span>
                                        @else
                                            -
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

    <!-- DETAIL OVERTIME MODAL (For existing active date clicks) -->
    @if(($isDetailModalOpen ?? false) && $selectedOvertime)
        <div x-data>
            <template x-teleport="body">
                <div class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 dark:bg-gray-950/75 backdrop-blur-xs overflow-y-auto">
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-gray-800 flex flex-col max-h-[82vh] sm:max-h-[88vh] my-auto overflow-hidden transform-gpu">
                <div class="flex items-center justify-between rounded-t border-b p-4 md:p-5 dark:border-gray-700 shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Detail Pengajuan Lembur
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

                <div class="p-4 md:p-5 space-y-4 overflow-y-auto min-h-0 flex-1">
                    <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Status</span>
                            @if($selectedOvertime->status == 'pending')
                                <span class="inline-flex mt-1 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-bold text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">
                                    Pending
                                </span>
                            @elseif($selectedOvertime->status == 'approved')
                                <span class="inline-flex mt-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-700 dark:text-emerald-100">
                                    Approved
                                </span>
                            @elseif($selectedOvertime->status == 'paid')
                                <span class="inline-flex mt-1 items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900 dark:text-blue-100">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Paid (Dibayar)
                                </span>
                            @else
                                <span class="inline-flex mt-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-700 dark:text-rose-100">
                                    Rejected
                                </span>
                            @endif
                        </div>

                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Tanggal Lembur</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($selectedOvertime->overtime_date)->format('d M Y') }}
                            </span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Waktu</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($selectedOvertime->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($selectedOvertime->end_time)->format('H:i') }}
                            </span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Durasi</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $selectedOvertime->formatted_duration }}
                            </span>
                        </div>

                        @if($selectedOvertime->paid_at)
                        <div class="col-span-2 rounded-md bg-blue-50/80 p-2.5 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800">
                            <span class="text-xs text-blue-700 dark:text-blue-300 block font-semibold">Waktu Pembayaran</span>
                            <span class="text-xs font-bold text-blue-950 dark:text-blue-100">
                                {{ \Carbon\Carbon::parse($selectedOvertime->paid_at)->format('d F Y, H:i') }} WIB
                            </span>
                        </div>
                        @endif

                        <div class="col-span-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Bayaran Lembur</span>
                            <span class="text-base font-bold text-emerald-600 dark:text-emerald-400">
                                @if(in_array($selectedOvertime->status, ['approved', 'paid']) && $selectedOvertime->overtime_pay > 0)
                                    Rp {{ number_format($selectedOvertime->overtime_pay, 0, ',', '.') }}
                                @else
                                    ~ Rp {{ number_format($selectedOvertime->calculateEstimatedPay(), 0, ',', '.') }} (Estimasi)
                                @endif
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Alasan / Kegiatan Lembur</label>
                        <div class="w-full min-h-[80px] rounded-lg bg-gray-50 p-3.5 text-left text-sm font-medium leading-relaxed text-gray-800 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ trim($selectedOvertime->reason) }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-end rounded-b border-t border-gray-200 p-4 shrink-0 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/90">
                    <button wire:click="closeDetailModal" type="button" class="rounded-lg bg-gray-200 px-5 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </template>
    </div>
    @endif

    <!-- DEDICATED SUBMISSION FORM MODAL (Triggered exclusively from date box clicks) -->
    @if($isDateModalOpen ?? false)
        <div x-data>
            <template x-teleport="body">
                <div class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 dark:bg-gray-950/75 backdrop-blur-xs overflow-y-auto">
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-gray-800 flex flex-col max-h-[82vh] sm:max-h-[88vh] my-auto overflow-hidden transform-gpu">
                <div class="flex items-center justify-between rounded-t border-b p-4 md:p-5 dark:border-gray-700 shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Ajukan Lembur
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
                
                <form wire:submit.prevent="submitDateModal" class="flex flex-col min-h-0 flex-1 overflow-hidden">
                    <div class="p-4 md:p-5 overflow-y-auto min-h-0 flex-1 space-y-4">
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

                        <input type="hidden" wire:model="overtime_date">

                        <div class="rounded-lg bg-sky-50 p-3.5 border border-sky-200 dark:bg-sky-950/50 dark:border-sky-800">
                            <span class="text-xs font-semibold text-sky-700 dark:text-sky-300 block uppercase tracking-wider">Tanggal Lembur</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white mt-0.5 block">
                                {{ $selectedDateDisplay }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs sm:text-sm font-medium text-gray-900 dark:text-white">Jam Mulai</label>
                                <input type="text"
                                       inputmode="numeric"
                                       placeholder="17:00"
                                       maxlength="5"
                                       x-data
                                       x-on:input="
                                         let v = $el.value.replace(/\D/g, '').slice(0, 4);
                                         if (v.length >= 3) v = v.slice(0, 2) + ':' + v.slice(2);
                                         $el.value = v;
                                         $wire.set('start_time', v);
                                       "
                                       value="{{ $start_time }}"
                                       class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm font-semibold tracking-wider text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                                       required>
                                <span class="text-[11px] text-gray-400">Format: 17:00</span>
                                @error('start_time') <span class="block text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs sm:text-sm font-medium text-gray-900 dark:text-white">Jam Selesai</label>
                                <input type="text"
                                       inputmode="numeric"
                                       placeholder="20:00"
                                       maxlength="5"
                                       x-data
                                       x-on:input="
                                         let v = $el.value.replace(/\D/g, '').slice(0, 4);
                                         if (v.length >= 3) v = v.slice(0, 2) + ':' + v.slice(2);
                                         $el.value = v;
                                         $wire.set('end_time', v);
                                       "
                                       value="{{ $end_time }}"
                                       class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm font-semibold tracking-wider text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                                       required>
                                <span class="text-[11px] text-gray-400">Format: 20:00</span>
                                @error('end_time') <span class="block text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs sm:text-sm font-medium text-gray-900 dark:text-white truncate">
                                    Istirahat (durasi)
                                </label>
                                <input type="text"
                                       inputmode="numeric"
                                       placeholder="0:30"
                                       maxlength="5"
                                       x-data
                                       x-on:input="
                                         let v = $el.value.replace(/\D/g, '').slice(0, 4);
                                         if (v.length >= 3) {
                                           v = v.slice(0, v.length - 2) + ':' + v.slice(-2);
                                         } else if (v.length === 2 && parseInt(v) > 59) {
                                           v = '0:' + v;
                                         }
                                         $el.value = v;
                                         $wire.set('break', v);
                                       "
                                       value="{{ $break }}"
                                       class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm font-semibold tracking-wider text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white">
                                <span class="text-[11px] text-gray-400">Format: 0:30 (opsional)</span>
                                @error('break') <span class="block text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Alasan / Kegiatan Lembur</label>
                            <textarea wire:model="reason" rows="3" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400" placeholder="Jelaskan detail kegiatan atau alasan lembur..." required></textarea>
                            @error('reason') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end rounded-b border-t border-gray-200 p-4 shrink-0 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/90">
                        <button wire:click="closeDateModal" type="button" class="mr-3 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">Batal</button>
                        <button type="submit" class="rounded-lg bg-sky-500 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-300 dark:bg-sky-500 dark:hover:bg-sky-400 dark:focus:ring-sky-800 transition">Ajukan Lembur</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endif
</div>
