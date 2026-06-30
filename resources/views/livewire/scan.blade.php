<div class="w-full">
  @php
    use Illuminate\Support\Carbon;
  @endphp
  @pushOnce('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  @endpushOnce
  @pushOnce('scripts')
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
      let currentMap = document.getElementById('currentMap');
      let map = document.getElementById('map');

      setTimeout(() => {
        toggleMap();
        toggleCurrentMap();
      }, 1000);

      function toggleCurrentMap() {
        const mapIsVisible = currentMap.style.display === "none";
        currentMap.style.display = mapIsVisible ? "block" : "none";
        document.querySelector('#toggleCurrentMap').innerHTML = mapIsVisible ?
          `<x-heroicon-s-chevron-up class="mr-2 h-5 w-5" />` :
          `<x-heroicon-s-chevron-down class="mr-2 h-5 w-5" />`;
      }

      function toggleMap() {
        const mapIsVisible = map.style.display === "none";
        map.style.display = mapIsVisible ? "block" : "none";
      }
    </script>
  @endpushOnce

  @if (!$isAbsence)
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
  @endif

  <div class="flex flex-col gap-4 md:flex-row">
    @if (!$isAbsence)
      <div class="flex flex-col gap-4">
        <div>
          <x-select id="shift" class="mt-1 block w-full" wire:model="shift_id" disabled="{{ !is_null($attendance) }}">
            <option value="">{{ __('Select Shift') }}</option>
            @foreach ($shifts as $shift)
              <option value="{{ $shift->id }}" {{ $shift->id == $shift_id ? 'selected' : '' }}>
                {{ $shift->name . ' | ' . $shift->start_time . ' - ' . $shift->end_time }}
              </option>
            @endforeach
          </x-select>
          @error('shift_id')
            <x-input-error for="shift" class="mt-2" message={{ $message }} />
          @enderror
        </div>
        <div class="flex justify-center outline outline-gray-100 dark:outline-slate-700" wire:ignore>
          <div id="scanner" class="min-h-72 sm:min-h-96 w-72 rounded-sm outline-dashed outline-slate-500 sm:w-96">
          </div>
        </div>
      </div>
    @endif
    <div class="w-full">
      <h4 id="scanner-error" class="mb-3 text-lg font-semibold text-red-500 dark:text-red-400 sm:text-xl" wire:ignore>
      </h4>
      <h4 id="scanner-result" class="mb-3 hidden text-lg font-semibold text-green-500 dark:text-green-400 sm:text-xl">
        {{ $successMsg }}
      </h4>
      <h4 id="latlng" class="mb-3 text-lg font-semibold text-gray-600 dark:text-gray-100 sm:text-xl">
        {{ __('Date') . ': ' . now()->format('d/m/Y') }}<br>

        @if (!is_null($currentLiveCoords))
          <div class="flex justify-between">
            <a href="{{ \App\Helpers::getGoogleMapsUrl($currentLiveCoords[0], $currentLiveCoords[1]) }}" target="_blank"
              class="underline hover:text-blue-400">
              {{ __('Your location') . ': ' . $currentLiveCoords[0] . ', ' . $currentLiveCoords[1] }}
            </a>
            <button class="text-nowrap h-6" onclick="toggleCurrentMap()" id="toggleCurrentMap">
              <x-heroicon-s-chevron-down class="mr-2 h-5 w-5" />
            </button>
          </div>
        @else
          {{ __('Your location') . ': -, -' }}
        @endif
        <div class="my-6 h-72 w-full md:h-96" id="currentMap" wire:ignore></div>
      </h4>
      <div class="grid grid-cols-2 gap-3 md:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3">
        <div
          class="{{ $attendance?->status == 'late' ? 'bg-orange-200 dark:bg-orange-900' : 'bg-green-200 dark:bg-green-900' }} flex items-center justify-between rounded-md px-4 py-2 text-gray-800 dark:text-white dark:shadow-gray-700">
          <div>
            <h4 class="text-lg font-semibold md:text-xl">Absen Masuk</h4>
            <div class="flex flex-col sm:flex-row">
              <span>
                @if ($isAbsence)
                  {{ __($attendance?->status) ?? '-' }}
                @else
                  {{ $attendance?->time_in ? Carbon::parse($attendance?->time_in)->format('H:i:s') : 'Belum Absen' }}
                @endif
              </span>
              @if ($attendance?->status == 'late')
                <span class="mx-1 hidden sm:inline-block">|</span>
              @endif
              <span>{{ $attendance?->status == 'late' ? 'Terlambat: Ya' : '' }}</span>
            </div>
          </div>
          <x-heroicon-o-arrows-pointing-in class="h-5 w-5" />
        </div>
        <div
          class="flex items-center justify-between rounded-md bg-orange-200 px-4 py-2 text-gray-800 dark:bg-orange-900 dark:text-white dark:shadow-gray-700">
          <div>
            <h4 class="text-lg font-semibold md:text-xl">Absen Keluar</h4>
            @if ($isAbsence)
              {{ __($attendance?->status) ?? '-' }}
            @else
              {{ $attendance?->time_out ? Carbon::parse($attendance?->time_out)->format('H:i:s') : 'Belum Absen' }}
            @endif
          </div>
          <x-heroicon-o-arrows-pointing-out class="h-5 w-5" />
        </div>
        <button
          class="col-span-2 flex items-center justify-between rounded-md bg-blue-200 px-4 py-2 text-gray-800 dark:bg-blue-900 dark:text-white dark:shadow-gray-700 md:col-span-1 lg:col-span-2 xl:col-span-1"
          {{ is_null($attendance?->lat_lng) ? 'disabled' : 'onclick=toggleMap()' }} id="toggleMap">
          <div>
            <h4 class="text-lg font-semibold md:text-xl">Koordinat Absen</h4>
            @if (is_null($attendance?->lat_lng))
              Belum Absen
            @else
              <a href="{{ \App\Helpers::getGoogleMapsUrl($attendance?->latitude, $attendance?->longitude) }}"
                target="_blank" class="underline hover:text-blue-400">
                {{ $attendance?->latitude . ', ' . $attendance?->longitude }}
              </a>
            @endif
          </div>
          <x-heroicon-o-map-pin class="h-6 w-6" />
        </button>
      </div>

      <div class="my-6 h-52 w-full md:h-64" id="map" wire:ignore></div>

      <hr class="my-4">

      <div class="grid grid-cols-2 gap-3 md:grid-cols-2 lg:grid-cols-3" wire:ignore>
        <a href="{{ route('apply-leave') }}">
          <div
            class="flex flex-col-reverse items-center justify-center gap-2 rounded-md bg-sky-500 dark:bg-sky-500 px-4 py-2 text-center font-medium text-white transition duration-100 hover:bg-sky-600 dark:hover:bg-sky-400 md:flex-row md:gap-3">
            Ajukan Izin
            <x-heroicon-o-envelope-open class="h-6 w-6 text-white" />
          </div>
        </a>
        <a href="{{ route('attendance-history') }}">
          <div
            class="flex flex-col-reverse items-center justify-center gap-2 rounded-md bg-blue-500 px-4 py-2 text-center font-medium text-white hover:bg-blue-600 md:flex-row md:gap-3">
            Riwayat Absen
            <x-heroicon-o-clock class="h-6 w-6 text-white" />
          </div>
        </a>
      </div>
      </div>
    </div>
  </div>

  <!-- Motivational Modal -->
  <x-dialog-modal wire:model.live="showMotivationModal" maxWidth="sm">
    <x-slot name="title">
      <div class="hidden" x-data="{ type: @entangle('motivationType'), open: @entangle('showMotivationModal') }" x-effect="if(open && type !== 'late') { confetti({ particleCount: 150, spread: 80, origin: { y: 0.6 }, colors: ['#fbbf24', '#f87171', '#60a5fa', '#34d399', '#c084fc'] }); }"></div>
    </x-slot>
    <x-slot name="content">
      <div class="flex flex-col items-center justify-center pt-6 pb-2 px-2 text-center">
        @if ($motivationType === 'early')
          <div class="relative w-32 h-32 mb-6 animate-bounce">
            <!-- Ribbon / Medal Icon -->
            <svg class="w-full h-full drop-shadow-md" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M25 90L35 60L15 45L40 45L50 20L60 45L85 45L65 60L75 90L50 75L25 90Z" fill="#FBBF24"/>
              <circle cx="50" cy="45" r="20" fill="#FDE68A"/>
              <path d="M50 35L53.5 41.5L60.5 42L55 47L56.5 54L50 50.5L43.5 54L45 47L39.5 42L46.5 41.5L50 35Z" fill="#F59E0B"/>
              <path d="M30 65L30 100L50 85L50 65Z" fill="#9333EA" opacity="0.8"/>
              <path d="M70 65L70 100L50 85L50 65Z" fill="#A855F7" opacity="0.8"/>
            </svg>
          </div>
        @elseif ($motivationType === 'on-time')
          <div class="relative w-32 h-32 mb-6 animate-pulse">
            <!-- On Time Check Icon -->
            <svg class="w-full h-full text-green-500 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2ZM12 20C7.6 20 4 16.4 4 12C4 7.6 7.6 4 12 4C16.4 4 20 7.6 20 12C20 16.4 16.4 20 12 20ZM16.2 14.8L13 11V6H11.5V11.5L15.2 15.8L16.2 14.8Z"/></svg>
          </div>
        @elseif ($motivationType === 'late')
          <div class="relative w-32 h-32 mb-6 animate-bounce">
            <!-- Warning / Late Icon -->
            <svg class="w-full h-full text-red-500 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M1 21H23L12 2L1 21ZM13 18H11V16H13V18ZM13 14H11V10H13V14Z"/></svg>
          </div>
        @else
          <div class="relative w-32 h-32 mb-6 animate-pulse">
            <!-- Heart / Check-out Icon -->
            <svg class="w-full h-full text-blue-500 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35L10.55 20.03C5.4 15.36 2 12.28 2 8.5C2 5.42 4.42 3 7.5 3C9.24 3 10.91 3.81 12 5.09C13.09 3.81 14.76 3 16.5 3C19.58 3 22 5.42 22 8.5C22 12.28 18.6 15.36 13.45 20.04L12 21.35Z"/></svg>
          </div>
        @endif
        
        <h2 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 mb-3">{{ $motivationTitle }}</h2>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-2">{{ $motivationMessage }}</p>
      </div>
    </x-slot>
    <x-slot name="footer">
      <div class="flex w-full justify-center pb-4 pt-2">
        <button wire:click="closeMotivationModal" class="px-10 py-3 bg-red-400 hover:bg-red-500 text-white font-bold text-lg rounded-full transition shadow-lg transform hover:scale-105 focus:outline-none">
          Okay
        </button>
      </div>
    </x-slot>
  </x-dialog-modal>

</div>

@script
  <script>
    const errorMsg = document.querySelector('#scanner-error');
    getLocation();

    async function getLocation() {
      if (navigator.geolocation) {
        const map = L.map('currentMap');
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 21,
        }).addTo(map);
        navigator.geolocation.watchPosition((position) => {
          console.log(position);
          $wire.$set('currentLiveCoords', [position.coords.latitude, position.coords.longitude]);
          map.setView([
            Number(position.coords.latitude),
            Number(position.coords.longitude),
          ], 13);
          L.marker([position.coords.latitude, position.coords.longitude]).addTo(map);
        }, (err) => {
          console.error(`ERROR(${err.code}): ${err.message}`);
          alert('{{ __('Please enable your location') }}');
        });
      } else {
        document.querySelector('#scanner-error').innerHTML = "Gagal mendeteksi lokasi";
      }
    }

    if (!$wire.isAbsence) {
      const scanner = new Html5Qrcode('scanner');

      const config = {
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        fps: 15,
        aspectRatio: 1,
        qrbox: {
          width: 280,
          height: 280
        },
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
      };

      async function startScanning() {
        if (scanner.getState() === Html5QrcodeScannerState.PAUSED) {
          return scanner.resume();
        }
        await scanner.start({
            facingMode: "environment"
          },
          config,
          onScanSuccess,
        );
      }

      async function onScanSuccess(decodedText, decodedResult) {
        console.log(`Code matched = ${decodedText}`, decodedResult);

        if (scanner.getState() === Html5QrcodeScannerState.SCANNING) {
          scanner.pause(true);
        }

        if (!(await checkTime())) {
          await startScanning();
          return;
        }

        const result = await $wire.scan(decodedText);

        if (result === true) {
          return onAttendanceSuccess();
        } else if (typeof result === 'string') {
          errorMsg.innerHTML = result;
        }

        setTimeout(async () => {
          await startScanning();
        }, 500);
      }

      async function checkTime() {
        const attendance = await $wire.getAttendance();

        if (attendance) {
          const timeIn = new Date(attendance.time_in).valueOf();
          const diff = (Date.now() - timeIn) / (1000 * 3600);
          const minAttendanceTime = 1;
          console.log(`Difference = ${diff}`);
          if (diff <= minAttendanceTime) {
            const timeIn = new Date(attendance.time_in).toLocaleTimeString([], {
              hour: 'numeric',
              minute: 'numeric',
              second: 'numeric',
              hour12: false,
            });
            const confirmation = confirm(
              `Anda baru saja absen pada ${timeIn}, apakah ingin melanjutkan untuk absen keluar?`
            );
            return confirmation;
          }
        }
        return true;
      }

      function onAttendanceSuccess() {
        scanner.stop();
        errorMsg.innerHTML = '';
        document.querySelector('#scanner-result').classList.remove('hidden');
      }

      const observer = new MutationObserver((mutationList, observer) => {
        const classes = ['text-white', 'bg-blue-500', 'dark:bg-blue-400', 'rounded-md', 'px-3', 'py-1'];
        for (const mutation of mutationList) {
          if (mutation.type === 'childList') {
            const startBtn = document.querySelector('#html5-qrcode-button-camera-start');
            const stopBtn = document.querySelector('#html5-qrcode-button-camera-stop');
            const fileBtn = document.querySelector('#html5-qrcode-button-file-selection');
            const permissionBtn = document.querySelector('#html5-qrcode-button-camera-permission');

            if (startBtn) {
              startBtn.classList.add(...classes);
              stopBtn.classList.add(...classes, 'bg-red-500');
              fileBtn.classList.add(...classes);
            }

            if (permissionBtn)
              permissionBtn.classList.add(...classes);
          }
        }
      });

      observer.observe(document.querySelector('#scanner'), {
        childList: true,
        subtree: true,
      });

      const shift = document.querySelector('#shift');
      const msg = 'Pilih shift terlebih dahulu';
      let isRendered = false;
      setTimeout(() => {
        if (!shift.value) {
          errorMsg.innerHTML = msg;
        } else {
          startScanning();
          isRendered = true;
        }
      }, 1000);
      shift.addEventListener('change', () => {
        if (!isRendered) {
          startScanning();
          isRendered = true;
          errorMsg.innerHTML = '';
        }
        if (!shift.value) {
          scanner.pause(true);
          errorMsg.innerHTML = msg;
        } else if (scanner.getState() === Html5QrcodeScannerState.PAUSED) {
          scanner.resume();
          errorMsg.innerHTML = '';
        }
      });

      const map = L.map('map').setView([
        Number({{ $attendance?->latitude }}),
        Number({{ $attendance?->longitude }}),
      ], 13);
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 21,
      }).addTo(map);
      L.marker([
        Number({{ $attendance?->latitude }}),
        Number({{ $attendance?->longitude }}),
      ]).addTo(map);
    }
  </script>
@endscript
