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
                                <th class="px-4 py-3 whitespace-nowrap">Jam Lembur</th>
                                <th class="px-4 py-3 whitespace-nowrap">Durasi</th>
                                <th class="px-4 py-3 whitespace-nowrap">Estimasi Bayaran</th>
                                <th class="px-4 py-3 whitespace-nowrap min-w-[200px]">Alasan</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @forelse ($overtimes as $overtime)
                                <tr wire:key="overtime-row-{{ $overtime->id }}" 
                                    wire:click="showDetail({{ $overtime->id }})" 
                                    class="text-gray-700 hover:bg-sky-50/50 dark:hover:bg-gray-750 dark:text-gray-400 transition-colors cursor-pointer">
                                    <td class="px-4 py-3 text-sm">
                                        @if($overtime->status == 'pending')
                                            <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold leading-tight text-yellow-800 dark:bg-yellow-900/80 dark:text-yellow-300">
                                                Pending
                                            </span>
                                        @elseif($overtime->status == 'approved')
                                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold leading-tight text-green-800 dark:bg-green-900/80 dark:text-green-300">
                                                Approved
                                            </span>
                                        @elseif($overtime->status == 'paid')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Paid (Lunas)
                                            </span>
                                            @if($overtime->paid_at)
                                                <div class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-medium">
                                                    {{ \Carbon\Carbon::parse($overtime->paid_at)->format('d M Y H:i') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold leading-tight text-red-800 dark:bg-red-900/80 dark:text-red-300">
                                                Rejected
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">
                                        {{ \Carbon\Carbon::parse($overtime->overtime_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                        @php
                                            $bMins = \App\Models\Overtime::convertBreakToMinutes($overtime->break);
                                            $bLabel = '';
                                            if ($bMins > 0) {
                                                $bH = floor($bMins / 60);
                                                $bM = $bMins % 60;
                                                if ($bH > 0 && $bM > 0) {
                                                    $bLabel = "({$bH} jam {$bM} menit istirahat)";
                                                } elseif ($bH > 0) {
                                                    $bLabel = "({$bH} jam istirahat)";
                                                } else {
                                                    $bLabel = "({$bM} menit istirahat)";
                                                }
                                            }
                                        @endphp
                                        <span>{{ $overtime->duration_hours }} Jam</span>
                                        @if($bLabel)
                                            <span class="font-normal text-gray-500 dark:text-gray-400 text-xs ml-1">{{ $bLabel }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                        @php
                                            $payCalc = \App\Models\OvertimeRate::calculatePayForDuration((float) $overtime->duration_hours, $overtime->employee ?? auth()->user(), $overtime->start_time, $overtime->end_time, $overtime->overtime_date ? $overtime->overtime_date->format('Y-m-d') : null);
                                            $displayPay = (!is_null($overtime->total_pay) && $overtime->total_pay > 0) ? $overtime->total_pay : $payCalc['total_pay'];
                                        @endphp
                                        <div>
                                            @if(in_array($overtime->status, ['approved', 'paid']))
                                                Rp {{ number_format($displayPay, 0, ',', '.') }}
                                            @elseif($overtime->duration_hours > 0)
                                                <span class="text-gray-500 dark:text-gray-400 font-normal">
                                                    ~ Rp {{ number_format($displayPay, 0, ',', '.') }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </div>
                                        @if(($payCalc['meal_allowance'] ?? 0) > 0)
                                            <div class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 mt-0.5">
                                                (+ Uang Makan Rp {{ number_format($payCalc['meal_allowance'], 0, ',', '.') }})
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm truncate max-w-[200px]" title="{{ $overtime->reason }}">
                                        {{ $overtime->reason }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm font-medium whitespace-nowrap" wire:click.stop="">
                                        <button type="button" 
                                                wire:click.stop="showDetail({{ $overtime->id }})" 
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 shadow-2xs hover:bg-gray-50 dark:hover:bg-gray-600 hover:text-sky-600 dark:hover:text-sky-400 focus:outline-none transition cursor-pointer" 
                                                title="Lihat Detail Lembur">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Detail</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
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

    <!-- DETAIL OVERTIME MODAL -->
    @if(($isDetailModalOpen ?? false) && $selectedOvertime)
        <div x-data>
            <template x-teleport="body">
                <div class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 dark:bg-gray-950/75 backdrop-blur-xs overflow-y-auto">
                    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-800 flex flex-col max-h-[85vh] sm:max-h-[90vh] my-auto overflow-hidden transform-gpu border border-gray-200/80 dark:border-gray-700">
                        @php
                            $emp = $selectedOvertime->employee ?? auth()->user();
                            $detailPay = \App\Models\OvertimeRate::calculatePayForDuration(
                                (float) $selectedOvertime->duration_hours,
                                $emp,
                                $selectedOvertime->start_time,
                                $selectedOvertime->end_time,
                                $selectedOvertime->overtime_date ? \Carbon\Carbon::parse($selectedOvertime->overtime_date)->format('Y-m-d') : null
                            );
                            $mealAllowance = (float) ($detailPay['meal_allowance'] ?? 0);
                            $finalTotalPay = (float) (!is_null($selectedOvertime->total_pay) && $selectedOvertime->total_pay > 0 ? $selectedOvertime->total_pay : ($detailPay['total_pay'] ?? 0));
                            $breakdownTiers = $detailPay['breakdown'] ?? [];
                            $appliedRate = (float) ($selectedOvertime->applied_rate_amount ?: ($detailPay['applied_rate_amount'] ?? 0));
                            $baseHourlyPay = max(0, $finalTotalPay - $mealAllowance);
                            $breakMinutes = \App\Models\Overtime::convertBreakToMinutes($selectedOvertime->break);
                        @endphp

                        <!-- Header Modal -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 p-4 md:p-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-850 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-800/60 shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">Detail Pengajuan Lembur</div>
                                    <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                        ID: #{{ $selectedOvertime->id }} &bull; Diajukan pada {{ $selectedOvertime->created_at ? \Carbon\Carbon::parse($selectedOvertime->created_at)->isoFormat('DD MMMM YYYY, HH:mm') : '-' }} WIB
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end gap-2">
                                <div>
                                    @if($selectedOvertime->status == 'pending')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-bold text-yellow-800 dark:bg-yellow-950/80 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800">
                                            <span class="h-2 w-2 rounded-full bg-yellow-500 animate-pulse"></span>
                                            Pending
                                        </span>
                                    @elseif($selectedOvertime->status == 'approved')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-800 dark:bg-green-950/80 dark:text-green-300 border border-green-200 dark:border-green-800">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            Approved
                                        </span>
                                    @elseif($selectedOvertime->status == 'paid')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            Paid (Lunas)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            Rejected
                                        </span>
                                    @endif
                                </div>
                                <button wire:click="closeDetailModal" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white transition" type="button">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Content Modal -->
                        <div class="p-4 md:p-5 space-y-4 overflow-y-auto min-h-0 flex-1">
                            <!-- 1. Employee Info Card -->
                            <div class="p-3.5 rounded-xl border border-gray-200/80 dark:border-gray-700/80 bg-gray-50/70 dark:bg-gray-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <img class="h-10 w-10 rounded-full object-cover ring-2 ring-sky-500/30 dark:ring-sky-400/30 shrink-0" 
                                         src="{{ $emp->profile_photo_url }}" 
                                         alt="{{ $emp->name ?? 'User' }}">
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $emp->name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">NIP: {{ $emp->nip ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 sm:justify-end">
                                    @if($emp?->division)
                                        <span class="inline-flex items-center rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60">
                                            {{ $emp->division->name }}
                                        </span>
                                    @endif
                                    @if($emp?->jobTitle)
                                        <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                            {{ $emp->jobTitle->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- 2. Waktu Lembur, Durasi & Jam Istirahat (4 Grid Cards) -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <!-- Tanggal Lembur -->
                                <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 shadow-2xs">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="font-medium">Tanggal Lembur</span>
                                    </div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($selectedOvertime->overtime_date)->format('d M Y') }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ \Carbon\Carbon::parse($selectedOvertime->overtime_date)->isoFormat('dddd') }}
                                    </div>
                                </div>

                                <!-- Jam Mulai - Selesai -->
                                <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 shadow-2xs">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-medium">Jam Lembur</span>
                                    </div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($selectedOvertime->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($selectedOvertime->end_time)->format('H:i') }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        WIB
                                    </div>
                                </div>

                                <!-- Waktu Istirahat (Break) -->
                                <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 shadow-2xs">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-12.728 0m0 0l2.829-2.829m-2.829 2.829L3 21m5.636-15.536a5 5 0 017.072 0m0 0l-2.829 2.829" />
                                        </svg>
                                        <span class="font-medium">Jam Istirahat</span>
                                    </div>
                                    <div class="text-sm font-bold {{ $breakMinutes > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-700 dark:text-gray-300' }}">
                                        @if($breakMinutes > 0)
                                            {{ floor($breakMinutes / 60) > 0 ? floor($breakMinutes / 60) . ' Jam ' : '' }}{{ ($breakMinutes % 60) > 0 ? ($breakMinutes % 60) . ' Mnt' : '' }}
                                        @else
                                            0 Menit
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $breakMinutes > 0 ? 'Dipotong dr durasi' : 'Tanpa istirahat' }}
                                    </div>
                                </div>

                                <!-- Durasi Efektif -->
                                <div class="p-3 rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50/60 dark:bg-sky-950/40 shadow-2xs">
                                    <div class="flex items-center gap-1.5 text-xs text-sky-700 dark:text-sky-400 mb-1">
                                        <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="font-semibold">Durasi Efektif</span>
                                    </div>
                                    <div class="text-base font-extrabold text-sky-700 dark:text-sky-300">
                                        {{ $selectedOvertime->duration_hours }} Jam
                                    </div>
                                    <div class="text-[11px] text-sky-600/80 dark:text-sky-400/80 mt-0.5 truncate" title="{{ $selectedOvertime->formatted_duration }}">
                                        {{ $selectedOvertime->formatted_duration }}
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Rincian & Akumulasi Nominal Bayaran Lembur Berjenjang -->
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-2xs">
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Rincian Kalkulasi & Akumulasi Tarif Lembur</span>
                                    </div>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                                        {{ count($breakdownTiers) > 1 ? count($breakdownTiers) . ' Tingkatan Tarif' : '1 Tingkatan Tarif' }}
                                    </span>
                                </div>

                                <div class="p-4 space-y-3 text-xs">
                                    <!-- Tier Breakdown List -->
                                    <div class="space-y-2">
                                        <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Kalkulasi Upah Pokok Berjenjang (Tier Rate):
                                        </div>

                                        @if(!empty($breakdownTiers))
                                            <div class="space-y-1.5">
                                                @foreach($breakdownTiers as $idx => $tier)
                                                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/60 border border-gray-200/70 dark:border-gray-700/60">
                                                        <div class="flex items-center gap-2.5 min-w-0">
                                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-sky-100 dark:bg-sky-950/80 text-[10px] font-bold text-sky-700 dark:text-sky-300 shrink-0">
                                                                {{ $idx + 1 }}
                                                            </span>
                                                            <div class="min-w-0">
                                                                <div class="font-semibold text-gray-800 dark:text-gray-200 truncate">
                                                                    {{ $tier['name'] }}
                                                                    <span class="font-normal text-[11px] text-gray-500 dark:text-gray-400">({{ $tier['tier_range_name'] }})</span>
                                                                </div>
                                                                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                                                    {{ $tier['hours'] }} Jam &times; Rp {{ number_format($tier['rate_amount'], 0, ',', '.') }}@if(($tier['rate_type'] ?? '') === 'flat_package') <span class="text-indigo-600 dark:text-indigo-400 font-medium">(Paket Flat)</span>@else/jam @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm shrink-0 ml-3">
                                                            Rp {{ number_format($tier['subtotal'], 0, ',', '.') }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="flex items-center justify-between py-1.5 px-3 rounded-lg bg-gray-50 dark:bg-gray-900/60 border border-gray-200/70 dark:border-gray-700/60">
                                                <div class="text-gray-700 dark:text-gray-300">
                                                    <span>Upah Lembur Pokok</span>
                                                    <span class="text-gray-400 text-[11px]">({{ $selectedOvertime->duration_hours }} Jam &times; Rp {{ number_format($appliedRate, 0, ',', '.') }})</span>
                                                </div>
                                                <div class="font-semibold text-gray-900 dark:text-white">
                                                    Rp {{ number_format($baseHourlyPay, 0, ',', '.') }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Subtotal Upah Lembur Pokok -->
                                    <div class="flex items-center justify-between py-2 border-t border-gray-200/80 dark:border-gray-700/80 text-xs">
                                        <div class="font-semibold text-gray-700 dark:text-gray-300">
                                            Subtotal Upah Pokok Lembur <span class="font-normal text-gray-500">({{ $selectedOvertime->duration_hours }} Jam)</span>
                                        </div>
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">
                                            Rp {{ number_format($baseHourlyPay, 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <!-- Baris Uang Makan Lembur -->
                                    <div class="flex items-center justify-between py-2 border-t border-gray-100 dark:border-gray-700/60 text-xs">
                                        <div class="text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                            <span class="font-semibold">Uang Makan Lembur</span>
                                            @if($mealAllowance > 0)
                                                <span class="inline-flex items-center rounded bg-amber-100 dark:bg-amber-950/80 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:text-amber-300">
                                                    Memenuhi Syarat
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-[11px]">(Tidak memenuhi syarat)</span>
                                            @endif
                                        </div>
                                        <div class="font-bold {{ $mealAllowance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }} text-sm">
                                            Rp {{ number_format($mealAllowance, 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <!-- Total Akumulasi Bayaran (Highlighted) -->
                                    <div class="mt-3 p-4 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">
                                                Total Akumulasi Bayaran Lembur
                                            </div>
                                            <div class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                                                Akumulasi upah pokok lembur + uang makan
                                            </div>
                                        </div>
                                        <div class="text-xl sm:text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                                            Rp {{ number_format($finalTotalPay, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Alasan / Rincian Pekerjaan -->
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-2xs">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                                    <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Alasan / Keterangan Pekerjaan Lembur</span>
                                </div>
                                <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900/60 border border-gray-200/60 dark:border-gray-700/60 text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-line">
                                    {{ $selectedOvertime->reason ?: 'Tidak ada keterangan alasan lembur.' }}
                                </div>
                            </div>

                            <!-- 5. Jejak Waktu & History (Audit Trail) -->
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                                <div class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Riwayat Jejak Waktu (Audit Trail)</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                    <!-- Pengajuan Dibuat -->
                                    <div class="p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700">
                                        <div class="text-gray-500 dark:text-gray-400 font-medium">Diajukan Pada</div>
                                        <div class="font-semibold text-gray-900 dark:text-white mt-1">
                                            {{ \Carbon\Carbon::parse($selectedOvertime->created_at)->isoFormat('DD MMM YYYY, HH:mm') }} WIB
                                        </div>
                                    </div>

                                    <!-- Persetujuan -->
                                    <div class="p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700">
                                        <div class="text-gray-500 dark:text-gray-400 font-medium">Status Persetujuan</div>
                                        @if($selectedOvertime->approval_date)
                                            <div class="font-semibold text-gray-900 dark:text-white mt-1">
                                                {{ \Carbon\Carbon::parse($selectedOvertime->approval_date)->isoFormat('DD MMM YYYY, HH:mm') }} WIB
                                            </div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 truncate" title="{{ $selectedOvertime->approver->name ?? '-' }}">
                                                oleh {{ $selectedOvertime->approver->name ?? '-' }}
                                            </div>
                                        @else
                                            <div class="font-semibold text-amber-600 dark:text-amber-400 mt-1">
                                                Menunggu Review
                                            </div>
                                            <div class="text-[10px] text-gray-400 mt-0.5">Admin / HR</div>
                                        @endif
                                    </div>

                                    <!-- Pembayaran -->
                                    <div class="p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700">
                                        <div class="text-gray-500 dark:text-gray-400 font-medium">Waktu Pembayaran</div>
                                        @if($selectedOvertime->paid_at)
                                            <div class="font-semibold text-blue-600 dark:text-blue-400 mt-1">
                                                {{ \Carbon\Carbon::parse($selectedOvertime->paid_at)->isoFormat('DD MMM YYYY, HH:mm') }} WIB
                                            </div>
                                            <div class="text-[10px] text-blue-600 dark:text-blue-400 font-medium mt-0.5">Sudah Lunas (Paid)</div>
                                        @elseif($selectedOvertime->status == 'paid')
                                            <div class="font-semibold text-blue-600 dark:text-blue-400 mt-1">
                                                Sudah Dibayar
                                            </div>
                                            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">Lunas</div>
                                        @else
                                            <div class="font-semibold text-gray-500 dark:text-gray-400 mt-1">
                                                Belum dibayar
                                            </div>
                                            <div class="text-[10px] text-gray-400 mt-0.5">Diproses saat Payroll</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Modal -->
                        <div class="flex items-center justify-end rounded-b border-t border-gray-200 p-4 shrink-0 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/90">
                            <button wire:click="closeDetailModal" type="button" class="rounded-lg bg-gray-200 px-5 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition">
                                Tutup
                            </button>
                        </div>
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
