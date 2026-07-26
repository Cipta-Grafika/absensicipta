<div>
  @pushOnce('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  @endpushOnce

  <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
    <x-label for="month_filter" value="Bulan"></x-label>
    <x-input type="month" name="month_filter" id="month_filter" wire:model.live="month" />
  </div>
  <h5 class="mt-3 text-sm dark:text-gray-200">Klik pada tanggal untuk melihat detail</h5>
  <div class="mt-4 flex w-full flex-col gap-3 lg:flex-row">
    <div class="grid w-full grid-cols-7 dark:text-white lg:w-[36rem]">
      @foreach (['M', 'S', 'S', 'R', 'K', 'J', 'S'] as $day)
        <div
          class="{{ $day === 'M' ? 'text-red-500' : '' }} {{ $day === 'J' ? 'text-green-600 dark:text-green-500' : '' }} flex h-10 items-center justify-center border border-gray-300 text-center dark:border-gray-600">
          {{ $day }}
        </div>
      @endforeach
      @if ($start->dayOfWeek !== 0)
        @foreach (range(1, $start->dayOfWeek) as $i)
          <div class="h-14 border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700">
          </div>
        @endforeach
      @endif

      @foreach ($dates as $date)
        @php
          $isWorkingDay = \App\Services\AttendanceScheduleService::isWorkingDay(auth()->user(), $date);
          $attendance = $attendances->first(fn($v, $k) => $v['date'] === $date->format('Y-m-d'));
          $status = ($attendance ?? [
              'status' => !$isWorkingDay || !$date->isPast() ? '-' : 'absent',
          ])['status'];

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
        @if ($attendance && isset($attendance['id']))
          <button class="{{ $bgColor }} h-14 w-full py-1 text-center" wire:click="show({{ $attendance['id'] }})"
            onclick="setLocation({{ $attendance['lat'] ?? 0 }}, {{ $attendance['lng'] ?? 0 }})">
            <span
              class="{{ $date->isSunday() ? 'text-red-500' : '' }} {{ $date->isFriday() ? 'text-green-600 dark:text-green-500' : '' }}">
              {{ $date->format('d') }}
            </span>
            <br>
            {{ $shortStatus }}
          </button>
        @else
          <div class="{{ $bgColor }} h-14 py-1 text-center">
            <span
              class="{{ $date->isSunday() ? 'text-red-500' : '' }} {{ $date->isFriday() ? 'text-green-600 dark:text-green-500' : '' }}">
              {{ $date->format('d') }}
            </span>
            <br>
            {{ $shortStatus }}
          </div>
        @endif
      @endforeach
      @if ($end->dayOfWeek !== 6)
        @foreach (range(5, $end->dayOfWeek) as $i)
          <div class="h-14 border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700"></div>
        @endforeach
      @endif
    </div>
    <div class="w-full">
      @if (isset($stats) && !empty($stats))
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
      @endif
    </div>
  </div>

  <x-attendance-detail-modal :current-attendance="$currentAttendance" />
  @stack('attendance-detail-scripts')
</div>
