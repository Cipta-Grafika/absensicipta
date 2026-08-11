<x-modal wire:model="showDetail">
  @if ($currentAttendance)
    @php
      $isExcused = in_array($currentAttendance['status'], ['excused', 'sick', 'wfh', 'leave', 'special-leaves']);
      $showMap = !empty($currentAttendance['latitude']) && !empty($currentAttendance['longitude']) && !$isExcused;
    @endphp

    <div class="flex flex-col min-h-0 max-h-[82vh] sm:max-h-[88vh] overflow-hidden">
      <!-- Fixed Header -->
      <div class="px-6 pt-5 pb-3.5 shrink-0 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-white/90 dark:bg-gray-900/90 backdrop-blur-md">
        <div>
          <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
            Detail Absensi: {{ $currentAttendance['name'] }}
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            NIP: {{ $currentAttendance['nip'] }} &bull; {{ $currentAttendance['date'] }}
          </p>
        </div>
        <button type="button" wire:click="$set('showDetail', false)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300 transition-colors">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Scrollable Vertical Body Container -->
      <div class="px-6 py-4 overflow-y-auto min-h-0 flex-1 space-y-4 custom-scrollbar-y">
        <div class="flex w-full gap-3">
          <div class="w-full">
            <x-label for="date" value="{{ __('Date') }}"></x-label>
            <x-input type="text" class="w-full" id="date" disabled
              value="{{ $currentAttendance['date'] }}"></x-input>
          </div>
          <div class="w-full">
            <x-label for="status" value="{{ __('Status') }}"></x-label>
            <x-input type="text" class="w-full" id="status" disabled
              value="{{ __($currentAttendance['status']) }}"></x-input>
          </div>
        </div>
        @if ($currentAttendance['status'] === 'imp')
          <div class="flex w-full gap-3">
            <div class="w-full">
              <x-label for="imp_duration" value="{{ __('Durasi IMP (Jam)') }}"></x-label>
              <x-input type="text" class="w-full bg-gray-100 dark:bg-gray-700" id="imp_duration" disabled
                value="{{ isset($currentAttendance['imp_duration_minutes']) ? floor($currentAttendance['imp_duration_minutes']/60).' Jam '.($currentAttendance['imp_duration_minutes']%60).' Menit' : '-' }}"></x-input>
            </div>
            <div class="w-full">
              <x-label for="replaced_duration" value="{{ __('Ganti Jam (Jam)') }}"></x-label>
              <x-input type="text" class="w-full bg-gray-100 dark:bg-gray-700" id="replaced_duration" disabled
                value="{{ isset($currentAttendance['replaced_duration_minutes']) ? floor($currentAttendance['replaced_duration_minutes']/60).' Jam '.($currentAttendance['replaced_duration_minutes']%60).' Menit' : '-' }}"></x-input>
            </div>
          </div>
        @endif
        @if ($isExcused)
          <div class="w-full">
            <x-label for="address" value="{{ __('Address') }}" />
            <x-input type="text" class="w-full" id="address" disabled value="{{ $currentAttendance['address'] }}" />
          </div>
        @endif
        <div class="flex flex-col gap-3">
          @if ($currentAttendance['attachment'])
            <x-label for="attachment" value="{{ __('Attachment') }}"></x-label>
            <img src="{{ $currentAttendance['attachment'] }}" alt="Attachment"
              class="max-h-48 object-contain sm:max-h-64 md:max-h-72 rounded-xl border border-gray-200 dark:border-gray-700">
          @endif
          @if ($currentAttendance['note'])
            <x-label for="note" value="Keterangan" />
            <x-textarea type="text" id="note" disabled value="{{ $currentAttendance['note'] }}" />
          @endif
          @if ($showMap)
            <x-label for="detail-modal-map" value="Koordinat Lokasi Absen"></x-label>
            <p class="dark:text-gray-300 text-xs font-mono">
              {{ $currentAttendance['latitude'] }}, {{ $currentAttendance['longitude'] }}
            </p>
            <div class="my-2 h-56 w-full md:h-64 rounded-xl border border-gray-300 dark:border-gray-600 shadow-inner z-10"
                 id="detail-modal-map"
                 x-data
                 x-init="$nextTick(() => {
                   function initDetailLeafletMap() {
                     const lat = Number({{ $currentAttendance['latitude'] ?? 0 }});
                     const lng = Number({{ $currentAttendance['longitude'] ?? 0 }});
                     if (!lat || !lng) return;

                     if (window.attendanceDetailMap) {
                       try { window.attendanceDetailMap.remove(); } catch(e) {}
                       window.attendanceDetailMap = null;
                     }

                     setTimeout(() => {
                       const mapEl = document.getElementById('detail-modal-map');
                       if (!mapEl) return;
                       try {
                         window.attendanceDetailMap = L.map('detail-modal-map').setView([lat, lng], 17);
                         L.marker([lat, lng]).addTo(window.attendanceDetailMap);
                         L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                           maxZoom: 19,
                           attribution: '&copy; OpenStreetMap'
                         }).addTo(window.attendanceDetailMap);
                         setTimeout(() => { if (window.attendanceDetailMap) window.attendanceDetailMap.invalidateSize(); }, 300);
                       } catch(e) {
                         console.warn('[Map Warning] Could not init map:', e);
                       }
                     }, 200);
                   }

                   if (!window.L) {
                      const css = document.createElement('link');
                      css.rel = 'stylesheet';
                      css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                      css.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                      css.crossOrigin = 'anonymous';
                      document.head.appendChild(css);

                      const js = document.createElement('script');
                      js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                      js.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                      js.crossOrigin = 'anonymous';
                      js.onload = () => initDetailLeafletMap();
                      document.head.appendChild(js);
                   } else {
                     initDetailLeafletMap();
                   }
                 })">
            </div>
          @endif
          @if ($currentAttendance['time_in'] || $currentAttendance['time_out'])
            <div class="grid grid-cols-2 gap-3">
              <div>
                <x-label for="time_in" value="Waktu Masuk"></x-label>
                <x-input type="text" class="w-full" id="time_in" disabled
                  value="{{ $currentAttendance['time_in'] ?? '-' }}"></x-input>
              </div>
              <div>
                <x-label for="time_out" value="Waktu Keluar"></x-label>
                <x-input type="text" class="w-full" id="time_out" disabled
                  value="{{ $currentAttendance['time_out'] ?? '-' }}"></x-input>
              </div>
            </div>
          @endif

          <div class="flex gap-3">
            @if ($currentAttendance['shift'] ?? false)
              <div class="w-full">
                <x-label for="shift" value="Shift"></x-label>
                <x-input class="w-full" type="text" id="shift" disabled
                  value="{{ $currentAttendance['shift']['name'] }}"></x-input>
              </div>
            @endif
            @if ($currentAttendance['barcode'] ?? false)
              <div class="w-full">
                <x-label for="barcode" value="Barcode"></x-label>
                <x-input class="w-full" type="text" id="barcode" disabled
                  value="{{ $currentAttendance['barcode']['name'] }}"></x-input>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Fixed Footer -->
      <div class="flex flex-row justify-end bg-gray-50 px-6 py-3.5 text-end dark:bg-gray-800/80 shrink-0 border-t border-gray-200 dark:border-gray-700">
        <x-secondary-button wire:click="$set('showDetail', false)">
          Tutup
        </x-secondary-button>
      </div>
    </div>
  @endif
</x-modal>
