@php
  $date = Carbon\Carbon::now();
@endphp
<div>
  @pushOnce('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  @endpushOnce
  <div class="flex flex-col justify-between sm:flex-row">
    <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Absensi Hari Ini
    </h3>
    <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Jumlah Karyawan: {{ $employeesCount }}
    </h3>
  </div>
  <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Card Hadir -->
    <div class="group relative overflow-hidden rounded-xl bg-green-200 p-5 text-gray-800 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:bg-green-900 dark:text-white dark:shadow-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium opacity-80">Hadir Hari Ini</p>
          <p class="mt-1 text-3xl font-bold">{{ $presentCount }}</p>
        </div>
        <div class="rounded-full bg-green-300 p-3 opacity-70 transition-transform duration-300 group-hover:scale-110 dark:bg-green-800">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
      </div>
      <div class="mt-4 flex items-center text-sm">
        @if ($stats['present']['is_up'])
          <span class="flex items-center font-semibold text-green-700 dark:text-green-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            {{ $stats['present']['trend'] }}
          </span>
        @elseif ($stats['present']['is_down'])
          <span class="flex items-center font-semibold text-red-600 dark:text-red-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>
            {{ $stats['present']['trend'] }}
          </span>
        @else
          <span class="flex items-center font-semibold text-gray-600 dark:text-gray-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>
            0
          </span>
        @endif
        <span class="ml-2 opacity-80">vs bln lalu</span>
      </div>
      <div class="mt-1 text-xs opacity-75">Trmsk telat: {{ $lateCount }}</div>
    </div>

    <!-- Card Izin -->
    <div class="group relative overflow-hidden rounded-xl bg-blue-200 p-5 text-gray-800 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:bg-blue-900 dark:text-white dark:shadow-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium opacity-80">Izin Hari Ini</p>
          <p class="mt-1 text-3xl font-bold">{{ $excusedCount }}</p>
        </div>
        <div class="rounded-full bg-blue-300 p-3 opacity-70 transition-transform duration-300 group-hover:scale-110 dark:bg-blue-800">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
      </div>
      <div class="mt-4 flex items-center text-sm">
        @if ($stats['excused']['is_up'])
          <span class="flex items-center font-semibold text-red-600 dark:text-red-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            {{ $stats['excused']['trend'] }}
          </span>
        @elseif ($stats['excused']['is_down'])
          <span class="flex items-center font-semibold text-green-700 dark:text-green-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>
            {{ $stats['excused']['trend'] }}
          </span>
        @else
          <span class="flex items-center font-semibold text-gray-600 dark:text-gray-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>
            0
          </span>
        @endif
        <span class="ml-2 opacity-80">vs bln lalu</span>
      </div>
      <div class="mt-1 text-xs opacity-75">Izin/Cuti</div>
    </div>

    <!-- Card Sakit -->
    <div class="group relative overflow-hidden rounded-xl bg-yellow-200 p-5 text-gray-800 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:bg-yellow-900 dark:text-white dark:shadow-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium opacity-80">Sakit Hari Ini</p>
          <p class="mt-1 text-3xl font-bold">{{ $sickCount }}</p>
        </div>
        <div class="rounded-full bg-yellow-300 p-3 opacity-70 transition-transform duration-300 group-hover:scale-110 dark:bg-yellow-800">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
      </div>
      <div class="mt-4 flex items-center text-sm">
        @if ($stats['sick']['is_up'])
          <span class="flex items-center font-semibold text-red-600 dark:text-red-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            {{ $stats['sick']['trend'] }}
          </span>
        @elseif ($stats['sick']['is_down'])
          <span class="flex items-center font-semibold text-green-700 dark:text-green-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>
            {{ $stats['sick']['trend'] }}
          </span>
        @else
          <span class="flex items-center font-semibold text-gray-600 dark:text-gray-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>
            0
          </span>
        @endif
        <span class="ml-2 opacity-80">vs bln lalu</span>
      </div>
      <div class="mt-1 text-xs opacity-75">Masa penyembuhan</div>
    </div>

    <!-- Card Tidak Hadir -->
    <div class="group relative overflow-hidden rounded-xl bg-red-200 p-5 text-gray-800 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:bg-red-900 dark:text-white dark:shadow-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium opacity-80">Absen Hari Ini</p>
          <p class="mt-1 text-3xl font-bold">{{ $absentCount }}</p>
        </div>
        <div class="rounded-full bg-red-300 p-3 opacity-70 transition-transform duration-300 group-hover:scale-110 dark:bg-red-800">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
      </div>
      <div class="mt-4 flex items-center text-sm">
        @if ($stats['absent']['is_up'])
          <span class="flex items-center font-semibold text-red-600 dark:text-red-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            {{ $stats['absent']['trend'] }}
          </span>
        @elseif ($stats['absent']['is_down'])
          <span class="flex items-center font-semibold text-green-700 dark:text-green-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>
            {{ $stats['absent']['trend'] }}
          </span>
        @else
          <span class="flex items-center font-semibold text-gray-600 dark:text-gray-300">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>
            0
          </span>
        @endif
        <span class="ml-2 opacity-80">vs bln lalu</span>
      </div>
      <div class="mt-1 text-xs opacity-75">Tidak/Belum Hadir</div>
    </div>
  </div>

  <div class="mb-4 overflow-x-scroll">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Name') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('NIP') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Division') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Job Title') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Shift') }}
          </th>
          <th scope="col"
            class="text-nowrap border border-gray-300 px-1 py-3 text-center text-xs font-medium text-gray-500 dark:border-gray-600 dark:text-gray-300">
            Status
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Time In') }}
          </th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Time Out') }}
          </th>
          <th scope="col" class="relative">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @php
          $class = 'px-4 py-3 text-sm font-medium text-gray-900 dark:text-white';
        @endphp
        @foreach ($employees as $employee)
          @php
            $attendance = $employee->attendance;
            $timeIn = $attendance ? $attendance?->time_in?->format('H:i:s') : null;
            $timeOut = $attendance ? $attendance?->time_out?->format('H:i:s') : null;
            $isSunday = $date->isSunday();
            $status = ($attendance ?? [
                'status' => $isSunday || !$date->isPast() ? '-' : 'absent',
            ])['status'];
            switch ($status) {
                case 'present':
                    $shortStatus = 'H';
                    $bgColor =
                        'bg-green-200 dark:bg-green-800 hover:bg-green-300 dark:hover:bg-green-700 border border-green-300 dark:border-green-600';
                    break;
                case 'late':
                    $shortStatus = 'T';
                    $bgColor =
                        'bg-orange-200 dark:bg-orange-800 hover:bg-orange-300 dark:hover:bg-orange-700 border border-orange-300 dark:border-orange-600';
                    break;
                case 'excused':
                    $shortStatus = 'I';
                    $bgColor =
                        'bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 dark:hover:bg-blue-700 border border-blue-300 dark:border-blue-600';
                    break;
                case 'sick':
                    $shortStatus = 'S';
                    $bgColor =
                        'bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700 border border-yellow-300 dark:border-yellow-600';
                    break;
                case 'absent':
                    $shortStatus = 'A';
                    $bgColor =
                        'bg-red-200 dark:bg-red-800 hover:bg-red-300 dark:hover:bg-red-700 border border-red-300 dark:border-red-600';
                    break;
                default:
                    $shortStatus = '-';
                    $bgColor = 'hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600';
                    break;
            }
          @endphp
          <tr wire:key="{{ $employee->id }}" class="group">
            {{-- Detail karyawan --}}
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->name }}
            </td>
            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->nip }}
            </td>
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->division?->name ?? '-' }}
            </td>
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $employee->jobTitle?->name ?? '-' }}
            </td>
            <td class="{{ $class }} text-nowrap group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $attendance->shift?->name ?? '-' }}
            </td>

            {{-- Absensi --}}
            <td
              class="{{ $bgColor }} text-nowrap px-1 py-3 text-center text-sm font-medium text-gray-900 dark:text-white">
              {{ __($status) }}
            </td>

            {{-- Waktu masuk/keluar --}}
            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $timeIn ?? '-' }}
            </td>
            <td class="{{ $class }} group-hover:bg-gray-100 dark:group-hover:bg-gray-700">
              {{ $timeOut ?? '-' }}
            </td>

            {{-- Action --}}
            <td
              class="cursor-pointer text-center text-sm font-medium text-gray-900 group-hover:bg-gray-100 dark:text-white dark:group-hover:bg-gray-700">
              <div class="flex items-center justify-center gap-3">
                @if ($attendance && ($attendance->attachment || $attendance->note || $attendance->lat_lng))
                  <x-button type="button" wire:click="show({{ $attendance->id }})"
                    onclick="setLocation({{ $attendance->latitude ?? 0 }}, {{ $attendance->longitude ?? 0 }})">
                    {{ __('Detail') }}
                  </x-button>
                @else
                  -
                @endif
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {{ $employees->links() }}

  <x-attendance-detail-modal :current-attendance="$currentAttendance" />
  @stack('attendance-detail-scripts')
</div>
