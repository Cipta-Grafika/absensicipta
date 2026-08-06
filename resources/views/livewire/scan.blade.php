<div class="w-full" x-data="{ hasLocation: @js(!is_null($currentLiveCoords)) }" @location-acquired.window="hasLocation = true">
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
        <div class="relative flex justify-center outline outline-gray-100 dark:outline-slate-700 overflow-hidden rounded-md">
          <!-- Floating Date Badge (Top Center) -->
          <div class="absolute top-3 left-1/2 -translate-x-1/2 z-20 pointer-events-auto">
            <div class="inline-flex h-8 items-center gap-2 rounded-full bg-blue-50/90 dark:bg-blue-950/90 px-3.5 text-xs font-semibold text-blue-700 dark:text-blue-300 border border-blue-200/90 dark:border-blue-800/90 shadow-md backdrop-blur-sm">
              <x-heroicon-o-calendar class="h-4 w-4 text-blue-500 dark:text-blue-400 shrink-0" />
              <span class="whitespace-nowrap">{{ now()->isoFormat('D MMMM YYYY') }}</span>
            </div>
          </div>

          <!-- Camera Scanner Box -->
          <div id="scanner" class="min-h-72 sm:min-h-96 w-72 rounded-sm outline-dashed outline-slate-500 sm:w-96" wire:ignore>
          </div>

          <!-- Floating Location Badge (Bottom Center) -->
          <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 pointer-events-auto flex items-center justify-center">
            <button type="button" x-cloak x-show="hasLocation" wire:click="$set('showLocationMapModal', true)"
              class="inline-flex h-8 items-center gap-2 rounded-full bg-emerald-50/90 dark:bg-emerald-950/90 px-3.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 border border-emerald-200/90 dark:border-emerald-800/90 shadow-md backdrop-blur-sm transition hover:bg-emerald-100 dark:hover:bg-emerald-900/90">
              <x-heroicon-s-check-circle class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
              <span class="whitespace-nowrap">Lokasi Terdeteksi</span>
              <x-heroicon-o-map-pin class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0 ml-0.5" />
            </button>
            <div x-cloak x-show="!hasLocation"
              class="inline-flex h-8 items-center gap-2 rounded-full bg-red-50/90 dark:bg-red-950/90 px-3.5 text-xs font-semibold text-red-700 dark:text-red-300 border border-red-200/90 dark:border-red-800/90 shadow-md backdrop-blur-sm">
              <x-heroicon-s-x-circle class="h-4 w-4 text-red-600 dark:text-red-400 shrink-0" />
              <span class="whitespace-nowrap">Lokasi Belum Terdeteksi</span>
            </div>
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

      @if ($isAbsence)
        <div id="latlng" class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60 pb-3">
          <div class="inline-flex h-8 items-center gap-2 rounded-full bg-blue-50 dark:bg-blue-900/50 px-3.5 text-xs font-semibold text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60 shadow-sm">
            <x-heroicon-o-calendar class="h-4 w-4 text-blue-500 dark:text-blue-400 shrink-0" />
            <span>{{ now()->isoFormat('D MMMM YYYY') }}</span>
          </div>

          <div class="flex items-center justify-center">
            <button type="button" x-cloak x-show="hasLocation" wire:click="$set('showLocationMapModal', true)"
              class="inline-flex h-8 items-center gap-2 rounded-full bg-emerald-50 dark:bg-emerald-900/50 px-3.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 shadow-sm transition hover:bg-emerald-100 dark:hover:bg-emerald-900/80">
              <x-heroicon-s-check-circle class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
              <span>Lokasi Terdeteksi</span>
              <x-heroicon-o-map-pin class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0 ml-0.5" />
            </button>
            <div x-cloak x-show="!hasLocation"
              class="inline-flex h-8 items-center gap-2 rounded-full bg-red-50 dark:bg-red-900/50 px-3.5 text-xs font-semibold text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/60 shadow-sm">
              <x-heroicon-s-x-circle class="h-4 w-4 text-red-600 dark:text-red-400 shrink-0" />
              <span>Lokasi Belum Terdeteksi</span>
            </div>
          </div>
        </div>
      @endif

      <!-- 1. Card Potongan (Top Banner) -->
      <div class="mb-3 flex items-center gap-3 rounded-md bg-red-200 px-4 py-2.5 text-gray-800 dark:bg-red-900 dark:text-white dark:shadow-gray-700">
        <x-heroicon-o-exclamation-triangle class="h-6 w-6 shrink-0 text-red-600 dark:text-red-300 opacity-90" />
        <div>
          <h4 class="text-base font-semibold sm:text-lg md:text-xl">Potongan</h4>
          <div class="font-bold text-red-700 dark:text-red-200 text-xs sm:text-sm md:text-base">
            Rp {{ number_format($this->getRealtimeDeduction(), 0, ',', '.') }}
          </div>
        </div>
      </div>

      <!-- 2. Cards Jam Masuk & Jam Keluar -->
      <div class="grid grid-cols-2 gap-3 mb-3">
        <div
          class="col-span-1 {{ $attendance?->status == 'late' ? 'bg-orange-200 dark:bg-orange-900' : 'bg-green-200 dark:bg-green-900' }} flex items-center gap-3 rounded-md px-3 py-2 text-gray-800 dark:text-white dark:shadow-gray-700">
          <x-heroicon-o-arrows-pointing-in class="h-6 w-6 shrink-0 opacity-75" />
          <div>
            <h4 class="text-base font-semibold sm:text-lg md:text-xl">Jam Masuk</h4>
            <div class="flex flex-col text-xs sm:text-sm sm:flex-row">
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
        </div>
        <div
          class="col-span-1 flex items-center gap-3 rounded-md bg-orange-200 px-3 py-2 text-gray-800 dark:bg-orange-900 dark:text-white dark:shadow-gray-700">
          <x-heroicon-o-arrows-pointing-out class="h-6 w-6 shrink-0 opacity-75" />
          <div>
            <h4 class="text-base font-semibold sm:text-lg md:text-xl">Jam Keluar</h4>
            <div class="text-xs sm:text-sm">
              @if ($isAbsence)
                {{ __($attendance?->status) ?? '-' }}
              @else
                {{ $attendance?->time_out ? Carbon::parse($attendance?->time_out)->format('H:i:s') : 'Belum Absen' }}
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Navigation Buttons: Absensi & Slip Gaji -->
      <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('attendance-history') }}" class="col-span-1 cursor-pointer">
          <div
            class="flex flex-row items-center justify-center gap-2 rounded-md bg-blue-600 dark:bg-blue-600 px-3 py-2.5 text-center font-medium text-white transition duration-100 hover:bg-blue-700 dark:hover:bg-blue-500 md:gap-2">
            <x-heroicon-o-clock class="h-5 w-5 text-white shrink-0" />
            <span class="whitespace-nowrap">Absensi</span>
          </div>
        </a>
        <a href="{{ route('user.payslips') }}" class="col-span-1 cursor-pointer">
          <div
            class="flex flex-row items-center justify-center gap-2 rounded-md bg-green-600 dark:bg-green-600 px-3 py-2.5 text-center font-medium text-white transition duration-100 hover:bg-green-700 dark:hover:bg-green-500 md:gap-2">
            <x-heroicon-o-document-text class="h-5 w-5 text-white shrink-0" />
            <span class="whitespace-nowrap">Slip Gaji</span>
          </div>
        </a>
      </div>
    </div>
  </div>

  <hr class="my-6">

  <!-- 4 Submission Action Buttons -->
  <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-4" wire:ignore>
    <a href="#" x-data @click.prevent="$dispatch('open-apply-leave-modal')" class="col-span-1 cursor-pointer">
      <div
        class="flex flex-row items-center justify-center gap-2 rounded-md bg-sky-500 dark:bg-sky-500 px-3 py-2.5 text-center font-medium text-white transition duration-100 hover:bg-sky-600 dark:hover:bg-sky-400 md:gap-2">
        <x-heroicon-o-envelope-open class="h-5 w-5 text-white shrink-0" />
        <span class="whitespace-nowrap">Ajukan Izin</span>
      </div>
    </a>
    <a href="#" x-data @click.prevent="$dispatch('open-apply-imp-modal')" class="col-span-1 cursor-pointer">
      <div
        class="flex flex-row items-center justify-center gap-2 rounded-md bg-orange-500 dark:bg-orange-500 px-3 py-2.5 text-center font-medium text-white transition duration-100 hover:bg-orange-600 dark:hover:bg-orange-400 md:gap-2">
        <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5 text-white shrink-0" />
        <span class="whitespace-nowrap">Ajukan IMP</span>
      </div>
    </a>
    <a href="#" x-data @click.prevent="$dispatch('open-apply-sick-modal')" class="col-span-1 cursor-pointer">
      <div
        class="flex flex-row items-center justify-center gap-2 rounded-md bg-rose-500 dark:bg-rose-500 px-3 py-2.5 text-center font-medium text-white transition duration-100 hover:bg-rose-600 dark:hover:bg-rose-400 md:gap-2">
        <x-heroicon-o-heart class="h-5 w-5 text-white shrink-0" />
        <span class="whitespace-nowrap">Ajukan Sakit</span>
      </div>
    </a>
    <a href="#" x-data @click.prevent="$dispatch('open-apply-cuti-modal')" class="col-span-1 cursor-pointer">
      <div
        class="flex flex-row items-center justify-center gap-2 rounded-md bg-indigo-600 dark:bg-indigo-600 px-3 py-2.5 text-center font-medium text-white transition duration-100 hover:bg-indigo-700 dark:hover:bg-indigo-500 md:gap-2">
        <x-heroicon-o-calendar class="h-5 w-5 text-white shrink-0" />
        <span class="whitespace-nowrap">Ajukan Cuti</span>
      </div>
    </a>
  </div>

  <!-- Separator & Integrated Dual Leaderboard Widget -->
  <hr class="my-8 border-gray-200 dark:border-gray-700">

  @livewire('leaderboard-widget')

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
        <button type="button" x-on:click="show = false" class="px-10 py-3 bg-red-400 hover:bg-red-500 text-white font-bold text-lg rounded-full transition shadow-lg transform hover:scale-105 focus:outline-none">
          Okay
        </button>
      </div>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Detail Lokasi Anda -->
  <x-dialog-modal wire:model.live="showLocationMapModal" maxWidth="lg">
    <x-slot name="title">
      <div class="flex items-center gap-2 text-gray-900 dark:text-gray-100">
        <x-heroicon-o-map-pin class="h-6 w-6 text-emerald-500 shrink-0" />
        <span>Detail Lokasi Anda</span>
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="flex flex-col gap-4" x-data x-effect="if($wire.showLocationMapModal) { setTimeout(() => window.initModalLiveMap && window.initModalLiveMap(), 250); }">
        
        <!-- Leaflet Map Container inside Modal -->
        <div class="relative overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
          <div id="currentMapModal" class="h-64 sm:h-80 w-full rounded-xl bg-gray-100 dark:bg-gray-900" wire:ignore></div>
        </div>

        <!-- User Friendly Location Info Box -->
        @if (!is_null($currentLiveCoords))
          <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700/60">
            <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-200 dark:border-gray-600">
              <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status GPS</span>
              <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                <x-heroicon-s-check-circle class="h-4 w-4" /> Lokasi Akurat
              </span>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs text-gray-700 dark:text-gray-300">
              <div>
                <span class="text-gray-400 dark:text-gray-400 block text-[10px] uppercase font-semibold">Latitude</span>
                <span class="font-mono font-medium text-sm">{{ number_format($currentLiveCoords[0], 6) }}</span>
              </div>
              <div>
                <span class="text-gray-400 dark:text-gray-400 block text-[10px] uppercase font-semibold">Longitude</span>
                <span class="font-mono font-medium text-sm">{{ number_format($currentLiveCoords[1], 6) }}</span>
              </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600 flex justify-end">
              <a href="{{ \App\Helpers::getGoogleMapsUrl($currentLiveCoords[0], $currentLiveCoords[1]) }}" target="_blank"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                <span>Buka di Google Maps</span>
                <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
              </a>
            </div>
          </div>
        @else
          <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-center">
            <x-heroicon-o-exclamation-triangle class="mx-auto h-8 w-8 text-red-500 mb-1" />
            <p class="text-xs font-semibold text-red-700 dark:text-red-300">Lokasi GPS belum terdeteksi.</p>
            <p class="text-[11px] text-red-600 dark:text-red-400 mt-0.5">Pastikan izin lokasi (GPS) pada browser sudah diaktifkan.</p>
          </div>
        @endif
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$set('showLocationMapModal', false)">
        Tutup
      </x-secondary-button>
    </x-slot>
  </x-dialog-modal>

  @livewire('user.apply-leave-modal-component')

</div>

@script
  <script>
    const errorMsg = document.querySelector('#scanner-error');
    getLocation();

    let modalLiveMap = null;
    let modalMarker = null;

    let lastLat = null;
    let lastLng = null;

    async function getLocation() {
      if (navigator.geolocation) {
        const updateCoords = (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          
          window.dispatchEvent(new CustomEvent('location-acquired', { detail: { lat, lng } }));

          if (lastLat === null || Math.abs(lat - lastLat) > 0.00005 || Math.abs(lng - lastLng) > 0.00005) {
            lastLat = lat;
            lastLng = lng;
            $wire.set('currentLiveCoords', [lat, lng]);
          }

          if (modalLiveMap) {
            modalLiveMap.setView([Number(lat), Number(lng)], 15);
            if (modalMarker) {
              modalMarker.setLatLng([lat, lng]);
            } else {
              modalMarker = L.marker([lat, lng]).addTo(modalLiveMap);
            }
          }
        };

        const handleErr = (err) => {
          console.warn(`GPS HighAccuracy failed code ${err.code}: ${err.message}, trying standard accuracy...`);
          navigator.geolocation.getCurrentPosition(updateCoords, (fallbackErr) => {
            console.error(`Geolocation fallback error code ${fallbackErr.code}: ${fallbackErr.message}`);
          }, {
            enableHighAccuracy: false,
            timeout: 15000,
            maximumAge: 60000
          });
        };

        // 1. Immediate position call
        navigator.geolocation.getCurrentPosition(updateCoords, handleErr, {
          enableHighAccuracy: true,
          timeout: 8000,
          maximumAge: 0
        });

        // 2. Continuous watch
        navigator.geolocation.watchPosition(updateCoords, handleErr, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 5000
        });
      } else {
        if (document.querySelector('#scanner-error')) {
          document.querySelector('#scanner-error').innerHTML = "Gagal mendeteksi lokasi";
        }
      }
    }

    window.initModalLiveMap = function() {
      const container = document.querySelector('#currentMapModal');
      if (!container) return;

      if (!modalLiveMap) {
        modalLiveMap = L.map('currentMapModal');
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 21,
        }).addTo(modalLiveMap);
      }

      if ($wire.currentLiveCoords) {
        const lat = Number($wire.currentLiveCoords[0]);
        const lng = Number($wire.currentLiveCoords[1]);
        modalLiveMap.setView([lat, lng], 15);
        if (modalMarker) {
          modalMarker.setLatLng([lat, lng]);
        } else {
          modalMarker = L.marker([lat, lng]).addTo(modalLiveMap);
        }
      }
      setTimeout(() => {
        modalLiveMap.invalidateSize();
      }, 200);
    };

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

      const cardMapEl = document.querySelector('#map');
      if (cardMapEl && {{ !is_null($attendance?->latitude) ? 'true' : 'false' }}) {
        const map = L.map('map').setView([
          Number({{ $attendance?->latitude ?? 0 }}),
          Number({{ $attendance?->longitude ?? 0 }}),
        ], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 21,
        }).addTo(map);
        L.marker([
          Number({{ $attendance?->latitude ?? 0 }}),
          Number({{ $attendance?->longitude ?? 0 }}),
        ]).addTo(map);
      }
    }
  </script>
@endscript
