<x-modal wire:model="showDetail">
  <div class="px-6 py-4 overflow-y-auto max-h-[88vh]">
    @if ($currentAttendance)
      @php
        $isExcused = in_array($currentAttendance['status'], ['excused', 'sick', 'wfh', 'leave', 'special-leaves']);
        $showMap = !empty($currentAttendance['latitude']) && !empty($currentAttendance['longitude']) && !$isExcused;
      @endphp
      <h3 class="mb-3 text-xl font-semibold dark:text-white">{{ $currentAttendance['name'] }}</h3>
      <div class="mb-3 w-full">
        <x-label for="nip" value="{{ __('NIP') }}"></x-label>
        <x-input type="text" class="w-full" id="nip" disabled value="{{ $currentAttendance['nip'] }}"></x-input>
      </div>
      <div class="mb-3 flex w-full gap-3">
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
        <div class="mb-3 flex w-full gap-3">
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
        <div class="mb-3 w-full">
          <x-label for="address" value="{{ __('Address') }}" />
          <x-input type="text" class="w-full" id="address" disabled value="{{ $currentAttendance['address'] }}" />
        </div>
      @endif
      <div class="flex flex-col gap-3">
        @if ($currentAttendance['attachment'])
          <x-label for="attachment" value="{{ __('Attachment') }}"></x-label>
          <img src="{{ $currentAttendance['attachment'] }}" alt="Attachment"
            class="max-h-48 object-contain sm:max-h-64 md:max-h-72">
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
                   document.head.appendChild(css);

                   const js = document.createElement('script');
                   js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
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
            <x-label for="time_in" value="Waktu Masuk"></x-label>
            <x-label for="time_out" value="Waktu Keluar"></x-label>
            <x-input type="text" id="time_in" disabled
              value="{{ $currentAttendance['time_in'] ?? '-' }}"></x-input>
            <x-input type="text" id="time_out" disabled
              value="{{ $currentAttendance['time_out'] ?? '-' }}"></x-input>
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
    @endif
  </div>
</x-modal>
