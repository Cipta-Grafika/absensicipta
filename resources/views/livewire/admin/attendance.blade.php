@php
  use Illuminate\Support\Carbon;
  $m = Carbon::parse($month);
  $showUserDetail = true;
  $isPerDayFilter = isset($date);
@endphp
<div x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  @pushOnce('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  @endpushOnce
  
  <div class="mb-4">
    <div class="flex w-full flex-1 items-center gap-2">
      <div class="relative w-full">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <x-input type="text" class="block w-full pl-10 pr-10" name="attendance_search" id="attendance_search" autocomplete="off" wire:model.live.debounce.300ms="search"
          placeholder="{{ __('Search') }}" />
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
    <x-slot name="title">Absensi Filters</x-slot>
    <x-slot name="actions">
      <button type="button" wire:click="$set('month', ''); $set('week', ''); $set('date', ''); $set('division', ''); $set('jobTitle', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
      </button>
    </x-slot>
    
    <x-slot name="content">
      <div class="flex flex-col gap-6">
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

  @if (isset($stats) && !empty($stats))
    <div class="mb-4">
      <x-admin.attendance-summary-cards
        :stats="$stats"
        :presentCount="$presentCount"
        :wfhCount="$wfhCount"
        :excusedCount="$excusedCount"
        :sickCount="$sickCount"
        :leaveCount="$leaveCount"
        :absentCount="$absentCount"
        :sparklines="$sparklines"
      />
    </div>
  @endif
  @if (empty($dates))
    <div class="my-2 py-10 text-center text-sm font-medium text-gray-900 dark:text-gray-100">
      Tidak ada data (Silakan pilih filter tanggal)
    </div>
  @else
    <div class="overflow-x-scroll">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ $showUserDetail ? __('Name') : __('Name') . '/' . __('Date') }}
          </th>
          @if ($showUserDetail)
            <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
              {{ __('NIP') }}
            </th>
            <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
              {{ __('Division') }}
            </th>
            <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
              {{ __('Job Title') }}
            </th>
            @if ($isPerDayFilter)
              <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                {{ __('Shift') }}
              </th>
            @endif
          @endif
          @if (!$month)
            @foreach ($dates as $date)
              @php
                if (!$isPerDayFilter && $date->isSunday()) {
                    // Minggu merah
                    $textClass = 'text-red-500 dark:text-red-300';
                } elseif (!$isPerDayFilter && $date->isFriday()) {
                    // Jumat hijau
                    $textClass = 'text-green-500 dark:text-green-300';
                } else {
                    $textClass = 'text-gray-500 dark:text-gray-300';
                }
              @endphp
              <th scope="col"
                class="{{ $textClass }} text-nowrap border border-gray-300 px-1 py-3 text-center text-xs font-medium dark:border-gray-600">
                @if ($isPerDayFilter)
                  Status
                @else
                  {{ $date->format('d/m') }}
                @endif
              </th>
            @endforeach
          @endif
          @if ($isPerDayFilter)
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
              {{ __('Time In') }}
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
              {{ __('Time Out') }}
            </th>
          @endif
          @if (!$isPerDayFilter)
            @foreach (['H', 'T', 'I', 'S', 'A', 'W', 'C'] as $_st)
              <th scope="col"
                class="w-12 min-w-[3rem] border border-gray-300 p-0 text-center text-xs font-medium text-gray-500 dark:border-gray-600 dark:text-gray-300">
                <div class="flex h-12 w-12 items-center justify-center">
                  {{ $_st }}
                </div>
              </th>
            @endforeach
          @endif

        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @php
          $class = 'cursor-pointer px-2 py-3 text-sm font-medium text-gray-900 dark:text-white';
        @endphp
        @foreach ($employees as $employee)
          @php
            $attendances = $employee->attendances;
          @endphp
          <tr wire:key="employee-{{ $employee->id }}" class="group {{ $month ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750' : '' }}" @if($month) wire:click.prevent="showMonthlyDetail('{{ $employee->id }}')" @endif>
            {{-- Detail karyawan --}}
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->name }}
            </td>
            @if ($showUserDetail)
              <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                {{ $employee->nip }}
              </td>
              <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                {{ $employee->division?->name ?? '-' }}
              </td>
              <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                {{ $employee->jobTitle?->name ?? '-' }}
              </td>
              @if ($isPerDayFilter)
                @php
                  $attendance = $employee->attendances->isEmpty() ? null : $employee->attendances->first();
                  $timeIn = $attendance ? $attendance['time_in'] : null;
                  $timeOut = $attendance ? $attendance['time_out'] : null;
                @endphp
                <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                  {{ $attendance['shift'] ?? '-' }}
                </td>
              @endif
            @endif

            {{-- Absensi --}}
            @php
              $presentCount = 0;
              $lateCount = 0;
              $excusedCount = 0;
              $sickCount = 0;
              $absentCount = 0;
              $wfhCount = 0;
              $leaveCount = 0;
            @endphp
            @if (!$month)
              @foreach ($dates as $date)
                @php
                  $isWorkingDay = \App\Services\AttendanceScheduleService::isWorkingDay($employee, $date);
                  $attendance = $attendances->firstWhere(fn($v, $k) => $v['date'] === $date->format('Y-m-d'));
                  $status = ($attendance ?? [
                      'status' => !$isWorkingDay || !$date->isPast() ? '-' : 'absent',
                  ])['status'];
                  switch ($status) {
                      case 'present':
                          $shortStatus = 'H';
                          $bgColor =
                              'bg-green-200 dark:bg-green-800 hover:bg-green-300 dark:hover:bg-green-700 border border-green-300 dark:border-green-600';
                          $presentCount++;
                          break;
                      case 'late':
                          $shortStatus = 'T';
                          $bgColor =
                              'bg-orange-200 dark:bg-orange-800 hover:bg-orange-300 dark:hover:bg-orange-700 border border-orange-300 dark:border-orange-600';
                          $lateCount++;
                          break;
                      case 'excused':
                          $shortStatus = 'I';
                          $bgColor =
                              'bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 dark:hover:bg-blue-700 border border-blue-300 dark:border-blue-600';
                          $excusedCount++;
                          break;
                      case 'imp':
                          $shortStatus = 'IMP';
                          $bgColor =
                              'bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 dark:hover:bg-blue-700 border border-blue-300 dark:border-blue-600';
                          $excusedCount++;
                          break;
                      case 'sick':
                          $shortStatus = 'S';
                          $bgColor =
                              'bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700 border border-yellow-300 dark:border-yellow-600';
                          $sickCount++;
                          break;
                      case 'absent':
                          $shortStatus = 'A';
                          $bgColor =
                              'bg-red-200 dark:bg-red-800 hover:bg-red-300 dark:hover:bg-red-700 border border-red-300 dark:border-red-600';
                          $absentCount++;
                          break;
                      case 'wfh':
                          $shortStatus = 'W';
                          $bgColor =
                              'bg-purple-200 dark:bg-purple-800 hover:bg-purple-300 dark:hover:bg-purple-700 border border-purple-300 dark:border-purple-600';
                          $wfhCount++;
                          break;
                      case 'leave':
                          $shortStatus = 'C';
                          $bgColor =
                              'bg-teal-200 dark:bg-teal-800 hover:bg-teal-300 dark:hover:bg-teal-700 border border-teal-300 dark:border-teal-600';
                          $leaveCount++;
                          break;
                      case 'special-leaves':
                          $shortStatus = 'CK';
                          $bgColor =
                              'bg-cyan-200 dark:bg-cyan-800 hover:bg-cyan-300 dark:hover:bg-cyan-700 border border-cyan-300 dark:border-cyan-600';
                          $leaveCount++;
                          break;
                      default:
                          $shortStatus = '-';
                          $bgColor =
                              'hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600';
                          break;
                  }
                @endphp
                @if (Auth::user()->isAdmin)
                  <td
                    class="{{ $bgColor }} cursor-pointer text-center text-sm font-medium text-gray-900 dark:text-white">
                    <button class="w-full px-1 py-3" wire:click="editAttendance('{{ $employee->id }}', '{{ $date->format('Y-m-d') }}')">
                      {{ $isPerDayFilter ? __($status) : $shortStatus }}
                    </button>
                  </td>
                @elseif (!$isPerDayFilter && $attendance && ($attendance['attachment'] || $attendance['note'] || $attendance['coordinates']))
                  <td
                    class="{{ $bgColor }} cursor-pointer text-center text-sm font-medium text-gray-900 dark:text-white">
                    <button class="w-full px-1 py-3" wire:click="show({{ $attendance['id'] }})"
                      onclick="setLocation({{ $attendance['lat'] ?? 0 }}, {{ $attendance['lng'] ?? 0 }})">
                      {{ $isPerDayFilter ? ($status === 'imp' ? 'IMP' : __($status)) : $shortStatus }}
                    </button>
                  </td>
                @else
                  <td
                    class="{{ $bgColor }} text-nowrap cursor-pointer px-1 py-3 text-center text-sm font-medium text-gray-900 dark:text-white">
                    {{ $isPerDayFilter ? ($status === 'imp' ? 'IMP' : __($status)) : $shortStatus }}
                  </td>
                @endif
              @endforeach
            @else
              @php
                // Just count for the month
                foreach ($dates as $date) {
                  $isWorkingDay = \App\Services\AttendanceScheduleService::isWorkingDay($employee, $date);
                  $attendance = $attendances->firstWhere(fn($v, $k) => $v['date'] === $date->format('Y-m-d'));
                  $status = ($attendance ?? ['status' => !$isWorkingDay || !$date->isPast() ? '-' : 'absent'])['status'];
                  if ($status === 'present') $presentCount++;
                  elseif ($status === 'late') $lateCount++;
                  elseif ($status === 'excused') $excusedCount++;
                  elseif ($status === 'sick') $sickCount++;
                  elseif ($status === 'absent') $absentCount++;
                  elseif ($status === 'wfh') $wfhCount++;
                  elseif ($status === 'leave' || $status === 'special-leaves') $leaveCount++;
                }
              @endphp
            @endif

            {{-- Waktu masuk/keluar --}}
            @if ($isPerDayFilter)
              <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                {{ $timeIn ?? '-' }}
              </td>
              <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
                {{ $timeOut ?? '-' }}
              </td>
            @endif

            {{-- Total --}}
            @if (!$isPerDayFilter)
              @foreach ([
                  'bg-green-200 dark:bg-green-800 hover:bg-green-300 dark:hover:bg-green-700' => $presentCount,
                  'bg-orange-200 dark:bg-orange-800 hover:bg-orange-300 dark:hover:bg-orange-700' => $lateCount,
                  'bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 dark:hover:bg-blue-700' => $excusedCount,
                  'bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700' => $sickCount,
                  'bg-red-200 dark:bg-red-800 hover:bg-red-300 dark:hover:bg-red-700' => $absentCount,
                  'bg-purple-200 dark:bg-purple-800 hover:bg-purple-300 dark:hover:bg-purple-700' => $wfhCount,
                  'bg-teal-200 dark:bg-teal-800 hover:bg-teal-300 dark:hover:bg-teal-700' => $leaveCount
              ] as $bgClass => $statusCount)
                <td
                  class="{{ $bgClass }} cursor-pointer border border-gray-300 p-0 text-center text-sm font-medium text-gray-900 dark:border-gray-600 dark:text-white">
                  <div class="flex h-12 w-12 items-center justify-center">
                    {{ $statusCount }}
                  </div>
                </td>
              @endforeach
            @endif


          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @if ($employees->isEmpty())
    <div class="my-2 text-center text-sm font-medium text-gray-900 dark:text-gray-100">
      Tidak ada data
    </div>
  @endif
    <div class="mt-3">
      {{ $employees->links() }}
    </div>
  @endif

  <x-attendance-detail-modal :current-attendance="$currentAttendance" />
  @stack('attendance-detail-scripts')

  @if (Auth::user()->isAdmin)
    <x-dialog-modal wire:model="editingAttendance">
      <x-slot name="title">
        {{ Auth::user()->isSuperadmin ? __('Manipulasi Absensi') : __('Detail Absensi') }}
      </x-slot>

      <x-slot name="content">
        <form id="attendanceForm" wire:submit.prevent="saveAttendance">
          <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
            <div class="w-full">
              <x-label for="edit_date">{{ __('Date') }}</x-label>
              <x-input id="edit_date" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700" type="date" wire:model="formAttendance.date" readonly />
            </div>
            <div class="w-full">
              <x-label for="edit_shift_id" value="{{ __('Shift') }}" />
              <x-select id="edit_shift_id" class="mt-1 block w-full" wire:model.live="formAttendance.shift_id" :disabled="!Auth::user()->isSuperadmin">
                <option value="">{{ __('Select Shift') }}</option>
                @foreach (App\Models\Shift::all() as $shift)
                  <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                @endforeach
              </x-select>
              @error('formAttendance.shift_id')
                <x-input-error for="formAttendance.shift_id" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
          </div>

          <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
            <div class="w-full">
              <x-label for="edit_time_in">{{ __('Time In') }}</x-label>
              <x-input id="edit_time_in" class="mt-1 block w-full" type="time" wire:model="formAttendance.time_in" :disabled="!Auth::user()->isSuperadmin" />
              @error('formAttendance.time_in')
                <x-input-error for="formAttendance.time_in" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
            <div class="w-full">
              <x-label for="edit_time_out">{{ __('Time Out') }}</x-label>
              <x-input id="edit_time_out" class="mt-1 block w-full" type="time" wire:model="formAttendance.time_out" :disabled="!Auth::user()->isSuperadmin" />
              @error('formAttendance.time_out')
                <x-input-error for="formAttendance.time_out" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
          </div>

          <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
            <div class="w-full">
              <x-label for="edit_status" value="{{ __('Status') }}" />
              <x-select id="edit_status" class="mt-1 block w-full" wire:model.live="formAttendance.status" required :disabled="!Auth::user()->isSuperadmin">
                <option value="-">- (Kosong)</option>
                <option value="present">{{ __('present') }}</option>
                <option value="late">{{ __('late') }}</option>
                <option value="excused">{{ __('excused') }}</option>
                <option value="imp">{{ __('IMP') }}</option>
                <option value="sick">{{ __('sick') }}</option>
                <option value="absent">{{ __('absent') }}</option>
                <option value="wfh">{{ __('WFH') }}</option>
                <option value="leave">{{ __('Cuti') }}</option>
                <option value="special-leaves">{{ __('Cuti Khusus') }}</option>
              </x-select>
              @error('formAttendance.status')
                <x-input-error for="formAttendance.status" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
          </div>

          @if(isset($formAttendance['status']) && $formAttendance['status'] === 'imp')
          <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
            <div class="w-full">
              <x-label for="edit_imp_duration_minutes">{{ __('Durasi IMP (HH:MM)') }}</x-label>
              <x-input id="edit_imp_duration_minutes" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700" type="text" wire:model="formAttendance.imp_duration_minutes" readonly />
            </div>
            <div class="w-full">
              <x-label for="edit_replaced_duration_minutes">{{ __('Ganti Jam (HH:MM)') }}</x-label>
              <x-input id="edit_replaced_duration_minutes" class="mt-1 block w-full" type="text" placeholder="Contoh: 1:30" wire:model="formAttendance.replaced_duration_minutes" :disabled="!Auth::user()->isSuperadmin" />
              @error('formAttendance.replaced_duration_minutes')
                <x-input-error for="formAttendance.replaced_duration_minutes" class="mt-2" message="{{ $message }}" />
              @enderror
            </div>
          </div>
          @endif

          <div class="mt-4">
            <x-label for="edit_note">{{ __('Note') }}</x-label>
            <x-input id="edit_note" class="mt-1 block w-full" type="text" wire:model="formAttendance.note" :disabled="!Auth::user()->isSuperadmin" />
            @error('formAttendance.note')
              <x-input-error for="formAttendance.note" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </form>
      </x-slot>

      <x-slot name="footer">
        @if (Auth::user()->isSuperadmin)
          <x-secondary-button wire:click="cancelEditAttendance" wire:loading.attr="disabled">
            {{ __('Cancel') }}
          </x-secondary-button>

          <x-button class="ml-2" type="submit" form="attendanceForm" wire:loading.attr="disabled">
            {{ __('Confirm') }}
          </x-button>
        @else
          <x-secondary-button wire:click="cancelEditAttendance" wire:loading.attr="disabled">
            {{ __('Tutup') }}
          </x-secondary-button>
        @endif
      </x-slot>
    </x-dialog-modal>
  @endif
  
  <x-dialog-modal wire:model="viewingMonthlyDetail">
    <x-slot name="title">
      {{ __('Detail Absensi') }} {{ $month ? \Illuminate\Support\Carbon::parse($month)->translatedFormat('F Y') : __('Bulanan') }}
    </x-slot>

    <x-slot name="content">
      @php
        $detailUser = null;
        $start = null;
        $end = null;
        if ($viewingMonthlyDetail && $monthlyDetailUserId && $month) {
            // First check if user is in current paginator items to save queries
            $detailUser = collect($employees->items())->firstWhere('id', '==', $monthlyDetailUserId);
            
            // If somehow not in current items, query from DB
            if (!$detailUser) {
                $detailUser = App\Models\User::with(['division', 'jobTitle', 'attendances' => function ($q) use ($month) {
                    $s = Illuminate\Support\Carbon::parse($month)->startOfMonth();
                    $e = Illuminate\Support\Carbon::parse($month)->endOfMonth();
                    $q->whereBetween('date', [$s, $e]);
                }])->find($monthlyDetailUserId);
            }
            
            if ($detailUser) {
                $start = Illuminate\Support\Carbon::parse($month)->startOfMonth();
                $end = Illuminate\Support\Carbon::parse($month)->endOfMonth();
            }
        }
      @endphp
      
      @if($detailUser && $start && $end)
        <div class="mb-4">
           <p class="font-bold dark:text-white">{{ $detailUser->name }} - {{ $detailUser->nip }}</p>

        </div>
        <h5 class="mb-3 text-sm dark:text-gray-200">Klik pada tanggal untuk manipulasi absen</h5>
        <div class="grid w-full grid-cols-7 dark:text-white">
          @foreach (['M', 'S', 'S', 'R', 'K', 'J', 'S'] as $day)
            <div class="{{ $day === 'M' ? 'text-red-500' : '' }} {{ $day === 'J' ? 'text-green-600 dark:text-green-500' : '' }} flex h-10 items-center justify-center border border-gray-300 text-center dark:border-gray-600">
              {{ $day }}
            </div>
          @endforeach
          
          @if ($start->dayOfWeek !== 0)
            @foreach (range(1, $start->dayOfWeek) as $i)
              <div class="h-14 border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700"></div>
            @endforeach
          @endif
          
          @foreach ($dates as $date)
            @php
              $isWorkingDay = \App\Services\AttendanceScheduleService::isWorkingDay($detailUser, $date);
              $attendance = $detailUser->attendances->firstWhere('date', $date->format('Y-m-d'));
              
              // Handle array vs object depending on whether it was eager loaded via array or eloquent model
              $status = '-';
              if ($attendance) {
                  $status = is_array($attendance) ? $attendance['status'] : $attendance->status;
              } elseif (!$isWorkingDay || !$date->isPast()) {
                  $status = '-';
              } else {
                  $status = 'absent';
              }
              
              switch ($status) {
                  case 'present':
                      $shortStatus = 'H';
                      $bgColor = 'bg-green-200 dark:bg-green-800 hover:bg-green-300 dark:hover:bg-green-700 border border-green-300 dark:border-green-600';
                      break;
                  case 'late':
                      $shortStatus = 'T';
                      $bgColor = 'bg-orange-200 dark:bg-orange-800 hover:bg-orange-300 dark:hover:bg-orange-700 border border-orange-300 dark:border-orange-600';
                      break;
                  case 'excused':
                      $shortStatus = 'I';
                      $bgColor = 'bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 dark:hover:bg-blue-700 border border-blue-300 dark:border-blue-600';
                      break;
                  case 'imp':
                      $shortStatus = 'IMP';
                      $bgColor = 'bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 dark:hover:bg-blue-700 border border-blue-300 dark:border-blue-600';
                      break;
                  case 'sick':
                      $shortStatus = 'S';
                      $bgColor = 'bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700 border border-yellow-300 dark:border-yellow-600';
                      break;
                  case 'absent':
                      $shortStatus = 'A';
                      $bgColor = 'bg-red-200 dark:bg-red-800 hover:bg-red-300 dark:hover:bg-red-700 border border-red-300 dark:border-red-600';
                      break;
                  case 'wfh':
                      $shortStatus = 'W';
                      $bgColor = 'bg-purple-200 dark:bg-purple-800 hover:bg-purple-300 dark:hover:bg-purple-700 border border-purple-300 dark:border-purple-600';
                      break;
                  case 'leave':
                      $shortStatus = 'C';
                      $bgColor = 'bg-teal-200 dark:bg-teal-800 hover:bg-teal-300 dark:hover:bg-teal-700 border border-teal-300 dark:border-teal-600';
                      break;
                  case 'special-leaves':
                      $shortStatus = 'CK';
                      $bgColor = 'bg-cyan-200 dark:bg-cyan-800 hover:bg-cyan-300 dark:hover:bg-cyan-700 border border-cyan-300 dark:border-cyan-600';
                      break;
                  default:
                      $shortStatus = '-';
                      $bgColor = 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600';
                      break;
              }
            @endphp
            <button type="button" class="{{ $bgColor }} h-14 w-full py-1 text-center" 
                    @if (Auth::user()->isAdmin) wire:click="editAttendance('{{ $detailUser->id }}', '{{ $date->format('Y-m-d') }}')" @endif>
              <span class="{{ !$isWorkingDay ? 'text-red-500' : '' }} {{ $date->isFriday() ? 'text-green-600 dark:text-green-500' : '' }}">
                {{ $date->format('d') }}
              </span>
              <br>
              {{ $shortStatus }}
            </button>
          @endforeach
          
          @if ($end->dayOfWeek !== 6)
            @foreach (range(5, $end->dayOfWeek) as $i)
              <div class="h-14 border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700"></div>
            @endforeach
          @endif
        </div>
      @else
        <div class="py-4 text-center text-gray-500 dark:text-gray-400">
          Memuat data...
        </div>
      @endif
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$set('viewingMonthlyDetail', false)" wire:loading.attr="disabled">
        {{ __('Tutup') }}
      </x-secondary-button>
    </x-slot>
  </x-dialog-modal>
</div>
