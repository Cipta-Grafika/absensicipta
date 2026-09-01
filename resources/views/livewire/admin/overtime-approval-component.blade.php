<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Approval Lembur') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="Livewire.dispatch('print-report')">
        <x-heroicon-o-printer class="mr-1.5 h-4 w-4 text-sky-500" />
        Cetak
      </x-secondary-button>
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="py-0 sm:py-6">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 sm:rounded-2xl p-6 lg:p-8">

        <!-- Monthly Calendar Section -->
        <div class="mb-6 rounded-2xl bg-gray-50/80 p-4 sm:p-5 border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 shadow-xs">
          <div class="flex flex-col gap-3 lg:flex-row lg:items-center justify-between border-b border-gray-200/80 pb-4 dark:border-gray-700 mb-4">
            <div>
              <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-calendar-days class="h-5 w-5 text-sky-600 dark:text-sky-400" />
                Kalender Approval Lembur {{ \Carbon\Carbon::parse($calendar_month)->isoFormat('MMMM YYYY') }}
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Klik pada tanggal di bawah untuk melihat dan memproses persetujuan (Approve/Reject) pengajuan lembur karyawan secara bulk.
              </p>
            </div>

            <div class="flex items-center gap-2">
              <x-label for="calendar_month_overtime" value="Pilih Bulan" class="whitespace-nowrap text-xs font-semibold" />
              <x-input id="calendar_month_overtime" type="month" wire:model.live="calendar_month" class="text-xs py-1.5" />
            </div>
          </div>

          <!-- Calendar Grid -->
          <div class="overflow-x-auto">
            <div class="min-w-[650px]">
              <!-- Days Header -->
              <div class="mb-2 grid grid-cols-7 text-center text-xs font-bold text-gray-600 dark:text-gray-300">
                <div class="py-1">Sen</div>
                <div class="py-1">Sel</div>
                <div class="py-1">Rab</div>
                <div class="py-1">Kam</div>
                <div class="py-1">Jum</div>
                <div class="py-1">Sab</div>
                <div class="py-1 text-red-500">Min</div>
              </div>

              <!-- Dates Grid -->
              <div class="grid grid-cols-7 gap-1.5">
                <!-- Leading Empty Cells -->
                @if ($calStart->dayOfWeekIso > 1)
                  @foreach (range(1, $calStart->dayOfWeekIso - 1) as $i)
                    <div class="h-16 rounded-lg bg-gray-100/50 dark:bg-gray-800/40 border border-transparent"></div>
                  @endforeach
                @endif

                <!-- Date Cells -->
                @foreach ($calDates as $dateObj)
                  @php
                    $dateStr = $dateObj->format('Y-m-d');
                    $dayNum = $dateObj->day;
                    $isSunday = $dateObj->isSunday();
                    $isToday = $dateObj->isToday();

                    $dateOvertimes = $monthOvertimes->get($dateStr) ?? collect();
                    $pendingCount = $dateOvertimes->where('status', 'pending')->count();
                    $approvedCount = $dateOvertimes->where('status', 'approved')->count();
                    $paidCount = $dateOvertimes->where('status', 'paid')->count();
                    $rejectedCount = $dateOvertimes->where('status', 'rejected')->count();
                    $hasOvertimes = $dateOvertimes->isNotEmpty();
                  @endphp

                  <button type="button" wire:click="handleCalendarDateClick('{{ $dateStr }}')"
                    x-data x-on:click="$el.blur()"
                    class="group relative flex h-16 flex-col justify-between rounded-xl border p-2 text-left transition-all duration-150
                           {{ $hasOvertimes 
                              ? 'border-sky-200 bg-sky-50/60 dark:border-sky-800/80 dark:bg-sky-950/40 hover:border-sky-400 hover:shadow-md' 
                              : 'border-gray-200 bg-white dark:border-gray-700/80 dark:bg-gray-800/80 hover:border-sky-300 hover:bg-gray-50 dark:hover:bg-gray-700/60' }}">
                    
                    <div class="flex items-center justify-between w-full">
                      <span class="text-xs font-extrabold {{ $isSunday ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }}">
                        {{ $dayNum }}
                      </span>
                      @if ($isToday)
                        <span class="rounded bg-sky-500 px-1 py-0.2 text-[9px] font-bold text-white">Hari ini</span>
                      @elseif ($hasOvertimes)
                        <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                      @endif
                    </div>

                    <div class="flex flex-col gap-0.5 mt-1 overflow-hidden">
                      @if ($hasOvertimes)
                        <div class="flex flex-wrap gap-1">
                          @if ($pendingCount > 0)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-800 dark:bg-amber-900/80 dark:text-amber-200" title="{{ $pendingCount }} Pending">
                              <span class="sm:hidden">{{ $pendingCount }}</span>
                              <span class="hidden sm:inline">{{ $pendingCount }} Pending</span>
                            </span>
                          @endif
                          @if ($approvedCount > 0)
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-200" title="{{ $approvedCount }} Acc">
                              <span class="sm:hidden">{{ $approvedCount }}</span>
                              <span class="hidden sm:inline">{{ $approvedCount }} Acc</span>
                            </span>
                          @endif
                          @if ($paidCount > 0)
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-[9px] font-bold text-blue-800 dark:bg-blue-900/80 dark:text-blue-200" title="{{ $paidCount }} Paid">
                              <span class="sm:hidden">{{ $paidCount }}</span>
                              <span class="hidden sm:inline">{{ $paidCount }} Paid</span>
                            </span>
                          @endif
                          @if ($rejectedCount > 0)
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-1.5 py-0.5 text-[9px] font-bold text-rose-800 dark:bg-rose-900/80 dark:text-rose-200" title="{{ $rejectedCount }} Ditolak">
                              <span class="sm:hidden">{{ $rejectedCount }}</span>
                              <span class="hidden sm:inline">{{ $rejectedCount }} Ditolak</span>
                            </span>
                          @endif
                        </div>
                      @else
                        <span class="text-[9px] font-medium text-gray-400 dark:text-gray-500 group-hover:text-sky-600 dark:group-hover:text-sky-400">
                          -
                        </span>
                      @endif
                    </div>
                  </button>
                @endforeach

                <!-- Trailing Empty Cells -->
                @if ($calEnd->dayOfWeekIso < 7)
                  @foreach (range($calEnd->dayOfWeekIso + 1, 7) as $i)
                    <div class="h-16 rounded-lg bg-gray-100/50 dark:bg-gray-800/40 border border-transparent"></div>
                  @endforeach
                @endif
              </div>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <div class="flex w-full flex-1 items-center gap-2">
            <div class="relative w-full">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <x-input type="text" class="block w-full pl-10 pr-10" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Cari nama atau NIP') }}" />
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

  <x-filter-sidebar maxWidth="sm">
    <x-slot name="title">Filter Lemburan</x-slot>
    <x-slot name="actions">
      <button type="button" wire:click="$set('month', ''); $set('week', ''); $set('date', ''); $set('division', ''); $set('jobTitle', ''); $set('statusFilter', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
      </button>
    </x-slot>
    
    <x-slot name="content">
      <div class="flex flex-col gap-6">
        <div>
          <x-label for="statusFilter" value="Status" class="mb-1"></x-label>
          <x-select id="statusFilter" class="w-full" wire:model.live="statusFilter">
            <option value="">Semua</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="paid">Paid (Dibayar)</option>
            <option value="rejected">Rejected</option>
          </x-select>
        </div>
        <hr class="dark:border-gray-700">
        <div>
          <x-label for="month_filter" value="Per Bulan" class="mb-1"></x-label>
          <x-input type="month" name="month_filter" id="month_filter" class="w-full" wire:model.live="month" />
        </div>
        <div>
          <x-label for="week_filter" value="Per Minggu" class="mb-1"></x-label>
          <x-input type="week" name="week_filter" id="week_filter" class="w-full" wire:model.live="week" />
        </div>
        <div>
          <x-label for="day_filter" value="Per Hari" class="mb-1"></x-label>
          <x-input type="date" name="day_filter" id="day_filter" class="w-full" wire:model.live="date" />
        </div>
        <hr class="dark:border-gray-700">
        @if (Auth::user()->isSuperadmin)
        <div>
          <x-label for="division" value="Pilih Divisi" class="mb-1"></x-label>
          <x-select id="division" class="w-full" wire:model.live="division">
            <option value="">{{ __('Select Division') }}</option>
            @foreach (App\Models\Division::all() as $_division)
              <option value="{{ $_division->id }}" {{ $_division->id == $division ? 'selected' : '' }}>
                {{ $_division->name }}
              </option>
            @endforeach
          </x-select>
        </div>
        @endif
        <div>
          <x-label for="jobTitle" value="Pilih Jabatan" class="mb-1"></x-label>
          <x-select id="jobTitle" class="w-full" wire:model.live="jobTitle">
            <option value="">{{ __('Select Job Title') }}</option>
            @foreach (App\Models\JobTitle::all() as $_jobTitle)
              <option value="{{ $_jobTitle->id }}" {{ $_jobTitle->id == $jobTitle ? 'selected' : '' }}>
                {{ $_jobTitle->name }}
              </option>
            @endforeach
          </x-select>
        </div>
      </div>
    </x-slot>
  </x-filter-sidebar>

  <!-- Table Header Bar: Title, Subtitle & Show Entries Dropdown -->
  <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-200/80 pt-5 dark:border-gray-700">
    <div>
      <h4 class="text-base font-bold text-gray-900 dark:text-white">
        Pengajuan Lembur {{ \Carbon\Carbon::parse($calendar_month)->isoFormat('MMMM YYYY') }}
      </h4>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
        Menampilkan {{ $approvals->total() }} data pengajuan lembur karyawan untuk bulan terpilih.
      </p>
    </div>
    <div class="flex items-center gap-2">
      <label for="perPage_overtime" class="text-xs font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">Tampilkan:</label>
      <select wire:model.live="perPage" id="perPage_overtime" class="w-24 truncate rounded-md border border-gray-300 bg-gray-50 py-1 pl-2 pr-7 text-xs text-gray-900 focus:border-sky-500 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
        <option value="all">Semua</option>
      </select>
    </div>
  </div>

  <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            Karyawan
          </th>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[200px]">
            Waktu Lembur
          </th>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[200px]">
            Alasan
          </th>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[140px]">
            Bayaran Lembur
          </th>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[120px]">
            Status
          </th>
          <th scope="col" class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[160px]">
            Aksi
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @forelse ($approvals as $approval)
          <tr wire:key="approval-{{ $approval->id }}"
              wire:click="showDetail({{ $approval->id }})"
              class="cursor-pointer hover:bg-sky-50/70 dark:hover:bg-sky-950/40 transition-colors duration-150 group">
            <td class="whitespace-nowrap px-2 py-4">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <img class="h-10 w-10 rounded-full object-cover ring-1 ring-black/5 dark:ring-white/10" src="{{ $approval->employee->profile_photo_url }}" alt="">
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                    {{ $approval->employee->name ?? 'Unknown' }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $approval->employee->nip ?? '-' }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-2 py-4 text-sm text-gray-900 dark:text-gray-300 min-w-[220px]">
              <div class="mb-1 text-xs text-gray-500">Tanggal: <span class="font-medium text-gray-900 dark:text-gray-300">{{ \Carbon\Carbon::parse($approval->overtime_date)->format('d M Y') }}</span></div>
              <div>{{ \Carbon\Carbon::parse($approval->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($approval->end_time)->format('H:i') }}</div>
              @php
                $breakMins = \App\Models\Overtime::convertBreakToMinutes($approval->break);
                $breakLabel = '';
                if ($breakMins > 0) {
                    $bH = floor($breakMins / 60);
                    $bM = $breakMins % 60;
                    if ($bH > 0 && $bM > 0) {
                        $breakLabel = "({$bH} jam {$bM} menit istirahat)";
                    } elseif ($bH > 0) {
                        $breakLabel = "({$bH} jam istirahat)";
                    } else {
                        $breakLabel = "({$bM} menit istirahat)";
                    }
                }
              @endphp
              <div class="font-bold text-gray-700 dark:text-gray-200 whitespace-nowrap flex items-center gap-1">
                <span>Durasi: {{ $approval->duration_hours }} Jam</span>
                @if ($breakLabel)
                  <span class="font-normal text-gray-500 dark:text-gray-400 text-xs">{{ $breakLabel }}</span>
                @endif
              </div>
            </td>
            <td class="px-2 py-4 text-sm text-gray-900 dark:text-gray-300 min-w-[200px]">
              {{ Str::limit($approval->reason, 100) }}
            </td>
            <td class="px-2 py-4 text-sm font-bold text-emerald-600 dark:text-emerald-400 min-w-[140px]">
              @php
                $payData = \App\Models\OvertimeRate::calculatePayForDuration((float) $approval->duration_hours, $approval->employee, $approval->start_time, $approval->end_time, $approval->overtime_date ? $approval->overtime_date->format('Y-m-d') : null);
                $finalPay = $payData['total_pay'] > 0 ? $payData['total_pay'] : ($approval->total_pay ?? 0);
              @endphp
              <div>Rp {{ number_format($finalPay, 0, ',', '.') }}</div>
              @if(($payData['meal_allowance'] ?? 0) > 0)
                <div class="text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                  (+ Uang Makan Rp {{ number_format($payData['meal_allowance'], 0, ',', '.') }})
                </div>
              @endif
            </td>
            <td class="whitespace-nowrap px-2 py-4 text-sm">
              @if($approval->status == 'pending')
                  <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                      Pending
                  </span>
              @elseif($approval->status == 'approved')
                  <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                      Approved
                  </span>
                  <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">by {{ $approval->approver->name ?? '-' }}</div>
              @elseif($approval->status == 'paid')
                  <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                      <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                      Paid
                  </span>
                  @if($approval->paid_at)
                    <div class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-semibold">{{ \Carbon\Carbon::parse($approval->paid_at)->format('d M Y H:i') }}</div>
                  @endif
                  <div class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">by {{ $approval->approver->name ?? '-' }}</div>
              @else
                  <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">
                      Rejected
                  </span>
                  <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">by {{ $approval->approver->name ?? '-' }}</div>
              @endif
            </td>
            <td class="whitespace-nowrap px-2 py-4 text-center text-sm font-medium" wire:click.stop="">
              <div class="flex items-center justify-center space-x-2">
                <!-- Eye View Detail Button -->
                <button type="button" 
                        wire:click.stop="showDetail({{ $approval->id }})" 
                        class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 shadow-xs hover:bg-gray-50 dark:hover:bg-gray-600 hover:text-sky-600 dark:hover:text-sky-400 focus:outline-none transition cursor-pointer" 
                        title="Lihat Detail Lembur">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>

                @if($approval->status == 'pending')
                    <button wire:click.stop="approve({{ $approval->id }})" class="rounded-md border border-transparent bg-green-600 px-2 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none transition" title="Setujui (Approve)">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                    <button wire:click.stop="reject({{ $approval->id }})" class="rounded-md border border-transparent bg-red-600 px-2 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none transition" title="Tolak (Reject)">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @elseif($approval->status == 'approved')
                    <button wire:click.stop="markAsPaid({{ $approval->id }})" 
                            class="inline-flex items-center gap-1 rounded-md border border-transparent bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none transition" 
                            title="Tandai Sudah Dibayar (Paid)">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Bayar</span>
                    </button>
                @elseif($approval->status == 'paid')
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2 py-1 rounded-md border border-blue-200 dark:border-blue-800/80">
                        <svg class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Lunas</span>
                    </span>
                @endif
                
                @if(Auth::user()->isAdmin)
                    <button wire:click.stop="confirmDelete({{ $approval->id }})" class="rounded-md border border-transparent bg-red-600 px-2 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none transition" title="Hapus Permanen">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-2 py-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
              Belum ada data pengajuan lembur.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  
  <div class="mt-4">
      {{ $approvals->links() }}
  </div>

  <!-- Bulk Overtime Approval Modal -->
  <x-dialog-modal wire:model="bulkOvertimeModalOpen" maxWidth="3xl">
    <x-slot name="title">
      Approval Lembur Bulk Per Tanggal
    </x-slot>

    <x-slot name="content">
      <div class="space-y-4">
        <!-- Selected Date Banner -->
        <div class="rounded-lg bg-sky-50 p-3 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 flex items-center justify-between">
          <div>
            <h4 class="text-sm font-bold text-sky-900 dark:text-sky-200">
              {{ $selectedDateDisplay }}
            </h4>
            <p class="text-xs text-sky-700 dark:text-sky-300">
              Pilih opsi aksi persetujuan untuk setiap karyawan. Tekan "Simpan Approval Bulk" untuk menyimpan semua perubahan.
            </p>
          </div>
        </div>

        <!-- Search & Quick Action Buttons -->
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" wire:model.live.debounce.150ms="bulk_search" class="w-full pl-9 pr-4 text-xs" placeholder="Cari nama karyawan atau NIP..." />
          </div>

          <!-- 2x2 Grid on Mobile, Flex on Tablet/Desktop -->
          <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center sm:gap-2">
            <button type="button" wire:click="bulkSetAllStatus('approved')" class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 sm:py-1.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 active:scale-95 focus:outline-none transition">
              <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <span>Approve All</span>
            </button>
            <button type="button" wire:click="bulkSetAllStatus('paid')" class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 sm:py-1.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 active:scale-95 focus:outline-none transition">
              <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Paid All</span>
            </button>
            <button type="button" wire:click="bulkSetAllStatus('rejected')" class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 rounded-lg bg-rose-600 px-3 py-2 sm:py-1.5 text-xs font-bold text-white shadow-xs hover:bg-rose-700 active:scale-95 focus:outline-none transition">
              <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span>Reject All</span>
            </button>
            <button type="button" wire:click="bulkSetAllStatus('pending')" class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 sm:py-1.5 text-xs font-bold text-white shadow-xs hover:bg-amber-600 active:scale-95 focus:outline-none transition" title="Reset ke Pending">
              <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>Reset Pending</span>
            </button>
          </div>
        </div>

        <!-- Overtime Employee Requests List -->
        <div class="max-h-[50vh] overflow-x-auto overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700">
          <table class="w-full min-w-[650px] text-left text-xs text-gray-700 dark:text-gray-200">
            <thead class="bg-gray-50 text-gray-700 uppercase dark:bg-gray-700 dark:text-gray-300 sticky top-0">
              <tr>
                <th scope="col" class="px-4 py-3 min-w-[200px]">Karyawan</th>
                <th scope="col" class="px-4 py-3 min-w-[220px]">Detail Lembur</th>
                <th scope="col" class="px-4 py-3 text-center min-w-[260px]">Aksi Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              @forelse ($modalOvertimeItems as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                  <td class="px-4 py-3 min-w-[200px]">
                    <div class="flex items-center gap-3">
                      <img class="h-8 w-8 flex-shrink-0 rounded-full object-cover" src="{{ $item->employee->profile_photo_url }}" alt="">
                      <div>
                        <div class="font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ $item->employee->name ?? 'Unknown' }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 whitespace-nowrap">NIP: {{ $item->employee->nip ?? '-' }}</div>
                        @if($item->employee->division)
                          <span class="inline-block mt-0.5 rounded bg-gray-100 px-1.5 py-0.2 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $item->employee->division->name }}
                          </span>
                        @endif
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3 min-w-[220px]">
                    @php
                      $itemBreakMins = \App\Models\Overtime::convertBreakToMinutes($item->break);
                      $itemBreakLabel = '';
                      if ($itemBreakMins > 0) {
                          $bH = floor($itemBreakMins / 60);
                          $bM = $itemBreakMins % 60;
                          if ($bH > 0 && $bM > 0) {
                              $itemBreakLabel = "({$bH}j {$bM}m istirahat)";
                          } elseif ($bH > 0) {
                              $itemBreakLabel = "({$bH}j istirahat)";
                          } else {
                              $itemBreakLabel = "({$bM}m istirahat)";
                          }
                      }
                    @endphp
                    <div class="font-semibold text-gray-800 dark:text-gray-200 whitespace-nowrap">
                      {{ \Carbon\Carbon::parse($item->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($item->end_time)->format('H:i') }}
                      <span class="text-xs font-bold text-sky-600 dark:text-sky-400">({{ $item->duration_hours }} Jam)</span>
                      @if($itemBreakLabel)
                        <span class="text-[11px] font-normal text-gray-500 dark:text-gray-400">{{ $itemBreakLabel }}</span>
                      @endif
                    </div>
                    @php
                      $estPay = \App\Models\OvertimeRate::calculatePayForDuration((float) $item->duration_hours, $item->employee, $item->start_time, $item->end_time, $item->overtime_date ? $item->overtime_date->format('Y-m-d') : null);
                      $dispPay = !is_null($item->total_pay) ? $item->total_pay : $estPay['total_pay'];
                    @endphp
                    <div class="flex flex-col">
                      @if(!is_null($item->total_pay))
                        <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">Rp {{ number_format($dispPay, 0, ',', '.') }}</div>
                      @else
                        <div class="text-[11px] font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">(Est: Rp {{ number_format($dispPay, 0, ',', '.') }})</div>
                      @endif
                      @if(($estPay['meal_allowance'] ?? 0) > 0)
                        <div class="text-[9px] font-semibold text-amber-600 dark:text-amber-400">
                          (+ Uang Makan Rp {{ number_format($estPay['meal_allowance'], 0, ',', '.') }})
                        </div>
                      @endif
                    </div>
                    <div class="mt-1 text-[11px] text-gray-600 dark:text-gray-300 italic">"{{ Str::limit($item->reason, 80) }}"</div>
                  </td>
                  <td class="px-4 py-3 text-center min-w-[260px]">
                    <div class="inline-flex rounded-md shadow-sm whitespace-nowrap" role="group">
                      <button type="button" 
                        wire:click="$set('bulk_overtime_data.{{ $item->id }}', 'approved')"
                        class="px-2.5 py-1.5 text-xs font-bold rounded-l-lg border transition {{ ($bulk_overtime_data[$item->id] ?? 'pending') === 'approved' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-emerald-700 border-gray-300 hover:bg-emerald-50 dark:bg-gray-800 dark:text-emerald-400 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                        Approve
                      </button>
                      <button type="button" 
                        wire:click="$set('bulk_overtime_data.{{ $item->id }}', 'paid')"
                        class="px-2.5 py-1.5 text-xs font-bold border-t border-b border-r transition {{ ($bulk_overtime_data[$item->id] ?? 'pending') === 'paid' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-700 border-gray-300 hover:bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                        Paid
                      </button>
                      <button type="button" 
                        wire:click="$set('bulk_overtime_data.{{ $item->id }}', 'pending')"
                        class="px-2.5 py-1.5 text-xs font-bold border-t border-b transition {{ ($bulk_overtime_data[$item->id] ?? 'pending') === 'pending' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-amber-700 border-gray-300 hover:bg-amber-50 dark:bg-gray-800 dark:text-amber-400 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                        Pending
                      </button>
                      <button type="button" 
                        wire:click="$set('bulk_overtime_data.{{ $item->id }}', 'rejected')"
                        class="px-2.5 py-1.5 text-xs font-bold rounded-r-lg border transition {{ ($bulk_overtime_data[$item->id] ?? 'pending') === 'rejected' ? 'bg-rose-600 text-white border-rose-600' : 'bg-white text-rose-700 border-gray-300 hover:bg-rose-50 dark:bg-gray-800 dark:text-rose-400 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                        Reject
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="px-4 py-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                    Tidak ada pengajuan lembur pada tanggal ini.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('bulkOvertimeModalOpen')" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 !bg-sky-600 hover:!bg-sky-700" wire:click="submitBulkOvertimeApproval" wire:loading.attr="disabled">
        Simpan Approval Bulk
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Detail Lembur Modal -->
  <x-dialog-modal wire:model="isDetailModalOpen" maxWidth="2xl">
    <x-slot name="title">
      @if($selectedOvertime)
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
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
        </div>
      @else
        <div class="text-base font-bold text-gray-900 dark:text-white">Detail Lembur</div>
      @endif
    </x-slot>

    <x-slot name="content">
      @if($selectedOvertime)
        @php
          $emp = $selectedOvertime->employee;
          $payData = \App\Models\OvertimeRate::calculatePayForDuration(
            (float) $selectedOvertime->duration_hours,
            $emp,
            $selectedOvertime->start_time,
            $selectedOvertime->end_time,
            $selectedOvertime->overtime_date ? $selectedOvertime->overtime_date->format('Y-m-d') : null
          );
          $mealAllowance = (float) ($payData['meal_allowance'] ?? 0);
          $finalTotalPay = (float) (!is_null($selectedOvertime->total_pay) && $selectedOvertime->total_pay > 0 ? $selectedOvertime->total_pay : ($payData['total_pay'] ?? 0));
          $appliedRate = (float) ($selectedOvertime->applied_rate_amount ?: ($payData['applied_rate_amount'] ?? 0));
          $baseHourlyPay = max(0, $finalTotalPay - $mealAllowance);
          $breakMinutes = \App\Models\Overtime::convertBreakToMinutes($selectedOvertime->break);
        @endphp

        <div class="space-y-4">
          <!-- 1. Employee Info Card -->
          <div class="p-4 rounded-xl border border-gray-200/80 dark:border-gray-700/80 bg-gray-50/70 dark:bg-gray-800/60 backdrop-blur-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3.5">
              <img class="h-12 w-12 rounded-full object-cover ring-2 ring-sky-500/30 dark:ring-sky-400/30 shrink-0" 
                   src="{{ $emp->profile_photo_url }}" 
                   alt="{{ $emp->name ?? 'User' }}">
              <div>
                <div class="text-base font-bold text-gray-900 dark:text-white">{{ $emp->name ?? 'Unknown' }}</div>
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
              <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700/80 dark:text-gray-300">
                {{ ucfirst($emp->type ?? 'Full-time') }}
              </span>
            </div>
          </div>

          <!-- 2. Waktu Lembur, Durasi & Jam Istirahat (4 Grid Cards) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- Tanggal Lembur -->
            <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 shadow-2xs">
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
            <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 shadow-2xs">
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
            <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 shadow-2xs">
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
            <div class="p-3.5 rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50/60 dark:bg-sky-950/40 shadow-2xs">
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

          <!-- 3. Rincian & Akumulasi Nominal Bayaran Lembur -->
          <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-2xs">
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Rincian Nominal Bayaran Lembur</span>
              </div>
              <span class="text-[11px] text-gray-500 dark:text-gray-400">
                Tarif: <strong>Rp {{ number_format($appliedRate, 0, ',', '.') }}</strong>/jam
              </span>
            </div>

            <div class="p-4 space-y-2.5 text-xs">
              <!-- Baris 1: Upah Pokok Lembur -->
              <div class="flex items-center justify-between py-1 border-b border-gray-100 dark:border-gray-700/60">
                <div class="text-gray-600 dark:text-gray-300">
                  <span>Upah Lembur Pokok</span>
                  <span class="text-gray-400">({{ $selectedOvertime->duration_hours }} Jam &times; Rp {{ number_format($appliedRate, 0, ',', '.') }})</span>
                </div>
                <div class="font-semibold text-gray-900 dark:text-white text-sm">
                  Rp {{ number_format($baseHourlyPay, 0, ',', '.') }}
                </div>
              </div>

              <!-- Baris 2: Uang Makan Lembur -->
              <div class="flex items-center justify-between py-1 border-b border-gray-100 dark:border-gray-700/60">
                <div class="text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                  <span>Uang Makan Lembur</span>
                  @if($mealAllowance > 0)
                    <span class="inline-flex items-center rounded bg-amber-100 dark:bg-amber-950/80 px-1.5 py-0.2 text-[10px] font-bold text-amber-800 dark:text-amber-300">
                      Memenuhi Syarat
                    </span>
                  @else
                    <span class="text-gray-400 text-[11px]">(Tidak memenuhi syarat)</span>
                  @endif
                </div>
                <div class="font-semibold {{ $mealAllowance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }} text-sm">
                  Rp {{ number_format($mealAllowance, 0, ',', '.') }}
                </div>
              </div>

              <!-- Baris 3: Total Akumulasi Bayaran (Highlighted) -->
              <div class="mt-3 p-4 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 flex items-center justify-between">
                <div>
                  <div class="text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">
                    Total Akumulasi Bayaran Lembur
                  </div>
                  <div class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                    Akumulasi upah pokok + uang makan lembur
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
                <div class="text-[10px] text-gray-400 mt-0.5">Oleh Karyawan</div>
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
                    Belum diproses
                  </div>
                  <div class="text-[10px] text-gray-400 mt-0.5">Menunggu review Admin/HR</div>
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
      @endif
    </x-slot>

    <x-slot name="footer">
      <div class="flex flex-wrap items-center justify-between w-full gap-2">
        <div>
          @if($selectedOvertime && Auth::user()->isAdmin)
            <button type="button" 
                    wire:click="confirmDelete({{ $selectedOvertime->id }})" 
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 dark:border-red-800/80 bg-red-50 dark:bg-red-950/50 px-3 py-2 text-xs font-semibold text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/60 transition">
              <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <span>Hapus</span>
            </button>
          @endif
        </div>

        <div class="flex items-center gap-2">
          <x-secondary-button wire:click="closeDetailModal" wire:loading.attr="disabled">
            Tutup
          </x-secondary-button>

          @if($selectedOvertime)
            @if($selectedOvertime->status == 'pending')
              <button type="button" 
                      wire:click="approve({{ $selectedOvertime->id }})" 
                      class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-green-700 transition" 
                      title="Setujui Lembur">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                <span>Setujui</span>
              </button>
              <button type="button" 
                      wire:click="reject({{ $selectedOvertime->id }})" 
                      class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-red-700 transition" 
                      title="Tolak Lembur">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                <span>Tolak</span>
              </button>
            @elseif($selectedOvertime->status == 'approved')
              <button type="button" 
                      wire:click="markAsPaid({{ $selectedOvertime->id }})" 
                      class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition" 
                      title="Tandai Sudah Dibayar (Paid)">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Tandai Sudah Dibayar</span>
              </button>
            @endif
          @endif
        </div>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- Delete Modal Confirmation -->
  <x-confirmation-modal wire:model="isDeleteModalOpen">
    <x-slot name="title">
      {{ __('Hapus Data Lembur') }}
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus data lembur ini secara permanen?
    </x-slot>

    <x-slot name="footer">
      <x-danger-button wire:click="deleteOvertime" wire:loading.attr="disabled">
        {{ __('Ya, Hapus') }}
      </x-danger-button>
      <x-secondary-button class="ms-2" wire:click="cancelDelete" wire:loading.attr="disabled">
        {{ __('Batal') }}
      </x-secondary-button>
    </x-slot>
  </x-confirmation-modal>

      </div>
    </div>
  </div>
</div>
