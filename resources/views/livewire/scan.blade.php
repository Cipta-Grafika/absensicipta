<div>

  @if($showLocationMapModal)
    <div x-data x-init="$nextTick(() => { window.initModalMap && window.initModalMap(); })">
      <template x-teleport="body">
        <div class="fixed inset-0 z-[250] flex items-center justify-center p-4 overflow-y-auto">
          <div class="fixed inset-0 z-[251] bg-gray-900/60 dark:bg-gray-950/75 backdrop-blur-xs transform-gpu transition-opacity" wire:click="$set('showLocationMapModal', false)"></div>
          <div class="relative w-full max-w-lg rounded-2xl bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border border-white/60 dark:border-gray-700/60 p-5 shadow-2xl text-left flex flex-col max-h-[88vh] my-auto z-[255] transform-gpu">
            <div class="flex items-center justify-between border-b border-gray-200/80 pb-3 dark:border-gray-700 shrink-0">
              <div class="flex items-center gap-2">
                <x-heroicon-o-map-pin class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Lokasi GPS Terdeteksi</h3>
              </div>
              <button wire:click="$set('showLocationMapModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <x-heroicon-o-x-mark class="h-5 w-5" />
              </button>
            </div>

            <div class="mt-4 overflow-y-auto flex-1 min-h-0">
              <div id="modal-map" class="h-56 sm:h-64 w-full rounded-xl border border-gray-300 shadow-inner dark:border-gray-600 z-10"></div>
              <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Koordinat: <strong class="text-gray-800 dark:text-gray-200" id="modal-coords-text">{{ $currentLiveCoords ? implode(', ', $currentLiveCoords) : '-' }}</strong></span>
                <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold">
                  <x-heroicon-s-check-circle class="h-3.5 w-3.5" /> Akurat
                </span>
              </div>
            </div>

            <div class="mt-4 flex justify-end shrink-0 border-t border-gray-200 dark:border-gray-700 pt-3">
              <button wire:click="$set('showLocationMapModal', false)" type="button" class="rounded-lg bg-gray-200/80 px-4 py-2 text-xs font-semibold text-gray-800 hover:bg-gray-300 dark:bg-gray-700/80 dark:text-gray-200 dark:hover:bg-gray-600">
                Tutup
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
  @endif

  <div class="space-y-4">
    <!-- LOCKED STATUS ALERT BANNER FOR IZIN/WFH/SAKIT/CUTI/CUTI KHUSUS/IMP -->
    @if ($isAbsence)
      <div class="flex items-center gap-3 rounded-xl bg-amber-50 dark:bg-amber-950/50 p-4 border border-amber-200/90 dark:border-amber-800/90 shadow-xs">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/60 text-amber-600 dark:text-amber-400">
          <x-heroicon-o-lock-closed class="h-5 w-5" />
        </div>
        <div>
          <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">
            Presensi Hari Ini Terkunci (Status: {{ strtoupper($attendance?->status ?? 'IZIN/CUTI/SAKIT/WFH') }})
          </h4>
          <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5 font-medium">
            Input shift kerja serta operasi Check-In & Check-Out diproteksi dan dinonaktifkan untuk mencegah tumpang tindih status di hari ini.
          </p>
        </div>
      </div>
    @endif

    <!-- 1. COMBINED SHIFT & COMPACT GPS LIVE DETECTOR CARD -->
    @php
      $hasTimeIn = !empty($attendance?->time_in);
      $hasCoords = !empty($currentLiveCoords) && is_array($currentLiveCoords) && count($currentLiveCoords) >= 2;
    @endphp

    <div class="rounded-xl bg-gray-50 dark:bg-gray-800/90 p-3.5 border border-gray-200 dark:border-gray-700 space-y-3 shadow-xs"
         x-data="{ hasLoc: @json($hasCoords) }"
         x-init="
           $watch('$wire.currentLiveCoords', val => {
             if (Array.isArray(val) && val.length >= 2) {
               hasLoc = true;
             }
           });
           window.addEventListener('geo-updated', (e) => {
             if (e.detail && Array.isArray(e.detail) && e.detail.length >= 2) {
               hasLoc = true;
             } else if (Array.isArray($wire.currentLiveCoords) && $wire.currentLiveCoords.length >= 2) {
               hasLoc = true;
             }
           });
           if (Array.isArray($wire.currentLiveCoords) && $wire.currentLiveCoords.length >= 2) {
             hasLoc = true;
           }
         ">
      <!-- DROPDOWN SHIFT -->
      <div>
        <x-label for="shift" value="{{ __('Pilih Shift Kerja') }}" class="font-bold text-gray-700 dark:text-gray-200 text-xs uppercase tracking-wider" />
        <x-select name="shift" id="shift" 
          class="mt-1.5 block w-full font-semibold text-sm {{ ($hasTimeIn || $isAbsence) ? 'bg-gray-200 text-gray-500 border-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 cursor-not-allowed opacity-75' : '' }}" 
          wire:model.live="shift_id"
          :disabled="$hasTimeIn || $isAbsence">
          <option value="">-- {{ __('Pilih Shift') }} --</option>
            @php
              $userDivId = auth()->user()?->division_id;
              $divisionShifts = $shifts->filter(fn($s) => $s->division_id == $userDivId && !is_null($s->division_id));
              $globalShifts = $shifts->filter(fn($s) => is_null($s->division_id));
            @endphp

            @if($divisionShifts->count() > 0)
              <optgroup label="Shift Divisi (Prioritas Utama)">
                @foreach ($divisionShifts as $shift)
                  <option value="{{ $shift->id }}">
                    {{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})
                  </option>
                @endforeach
              </optgroup>
            @endif

            @if($globalShifts->count() > 0)
              <optgroup label="Shift Global">
                @foreach ($globalShifts as $shift)
                  <option value="{{ $shift->id }}">
                    {{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})
                  </option>
                @endforeach
              </optgroup>
            @endif
          </x-select>
        </div>

        <!-- ROW BADGES LOKASI GPS (KIRI) & REFRESH GPS (KANAN) -->
        <div class="flex items-center justify-between gap-3 pt-0.5">
          <div class="flex items-center gap-2">
            <x-heroicon-o-map-pin class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
            <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 shrink-0">GPS:</span>

            <div class="inline-flex items-center shrink-0">
              <template x-if="hasLoc">
                <button type="button" 
                  wire:click="$set('showLocationMapModal', true)"
                  class="inline-flex h-7 items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/50 px-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 shadow-xs transition hover:bg-emerald-100 dark:hover:bg-emerald-900/80">
                  <x-heroicon-s-check-circle class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                  <span>Lokasi Terdeteksi</span>
                  <x-heroicon-o-map-pin class="h-3 w-3 text-emerald-600 dark:text-emerald-400 shrink-0" />
                </button>
              </template>

              <template x-if="!hasLoc">
                <div class="inline-flex h-7 items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-900/50 px-3 text-xs font-semibold text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/60 shadow-xs">
                  <x-heroicon-s-x-circle class="h-3.5 w-3.5 text-red-600 dark:text-red-400 shrink-0" />
                  <span>Lokasi Belum Terdeteksi</span>
                </div>
              </template>
            </div>
          </div>

          <button type="button" id="btn-refresh-location" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 shrink-0">
            <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
            <span class="hidden sm:inline">Refresh GPS</span>
          </button>
        </div>
      </div>

    <!-- 3. CARD POTONGAN (TOP BANNER) -->
    <div class="flex items-center gap-3 rounded-lg bg-red-200 px-4 py-3 text-gray-800 dark:bg-red-900 dark:text-white border border-red-300 dark:border-red-800 shadow-xs">
      <x-heroicon-o-exclamation-triangle class="h-6 w-6 shrink-0 text-red-600 dark:text-red-300 opacity-90" />
      <div>
        <h4 class="text-base font-semibold sm:text-lg">Potongan</h4>
        <div class="font-bold text-red-700 dark:text-red-200 text-xs sm:text-sm md:text-base">
          Rp {{ number_format($realtimeDeduction ?? 0, 0, ',', '.') }}
        </div>
      </div>
    </div>

    <!-- 4. CARDS JAM MASUK & JAM KELUAR (BERSEBELAHAN DI MOBILE & DESKTOP) -->
    @php
      $hasTimeIn = !empty($attendance?->time_in);
      $hasTimeOut = !empty($attendance?->time_out);
      $canManualCheckIn = empty($attendance?->time_in) && !$isAbsence;

      $windowInfo = $this->checkOutWindowInfo;
      $hasShift = $windowInfo['hasShift'] ?? true;
      $isCheckOutWindowOpen = $windowInfo['isOpen'];
      $checkOutUnlockTime = $windowInfo['unlockTime'];

      $canManualCheckOut = $hasTimeIn && !$hasTimeOut && !$isAbsence && $hasShift && $isCheckOutWindowOpen;
      $isCheckOutLockedUntilWindow = $hasTimeIn && !$hasTimeOut && !$isAbsence && (!$hasShift || !$isCheckOutWindowOpen);
    @endphp

    <div class="grid grid-cols-2 gap-2.5 sm:gap-4">
      <!-- CARD JAM MASUK -->
      <div
        @if($canManualCheckIn)
          wire:click="manualCheckIn"
          title="Klik untuk Absen Masuk (Verifikasi GPS Radius Barcode Kantor)"
        @endif
        class="col-span-1 relative flex flex-col justify-between rounded-xl p-2.5 sm:p-4 transition-all duration-200
               {{ $canManualCheckIn 
                  ? 'cursor-pointer bg-gradient-to-br from-emerald-50 via-teal-50 to-emerald-100 dark:from-emerald-950 dark:via-teal-950 dark:to-emerald-900 border-2 border-emerald-500 dark:border-emerald-500 shadow-md shadow-emerald-500/15 hover:shadow-lg hover:scale-[1.01] active:scale-95' 
                  : ($attendance?->time_in 
                      ? ($attendance?->status == 'late' 
                          ? 'bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800' 
                          : 'bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800') 
                      : 'bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 opacity-80') }}">
        
        <div class="space-y-2">
          <!-- HEADER: ICON & TITLE (JAM MASUK) -->
          <div class="flex items-center gap-1.5 sm:gap-2">
            <div class="rounded-lg p-1.5 sm:p-2 {{ $canManualCheckIn ? 'bg-emerald-600 text-white' : ($attendance?->time_in ? 'bg-emerald-600/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400') }} shrink-0">
              <x-heroicon-o-arrows-pointing-in class="h-4 w-4 sm:h-5 sm:w-5" />
            </div>
            <h4 class="text-xs sm:text-base font-bold text-gray-900 dark:text-white leading-tight truncate">Jam Masuk</h4>
          </div>

          <!-- VALUE & BADGES (DI BAWAH JUDUL) -->
          <div class="space-y-1 pt-0.5">
            <div>
              @if($canManualCheckIn)
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] sm:text-xs font-bold text-white shadow-xs animate-pulse">
                  <span>Klik Absen</span>
                  <x-heroicon-s-hand-raised class="h-3 w-3" />
                </span>
              @elseif($attendance?->time_in)
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] sm:text-xs font-bold text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-200">
                  <x-heroicon-s-check-circle class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                  Sudah Masuk
                </span>
              @endif
            </div>

            <div class="text-sm sm:text-xl font-extrabold font-mono tracking-tight {{ $attendance?->time_in ? 'text-emerald-700 dark:text-emerald-300' : ($canManualCheckIn ? 'text-emerald-900 dark:text-emerald-100' : 'text-gray-500 dark:text-gray-400') }}">
              @if ($isAbsence)
                {{ __($attendance?->status) ?? '-' }}
              @else
                {{ $attendance?->time_in ? \Carbon\Carbon::parse($attendance?->time_in)->format('H:i:s') : 'Belum Absen' }}
              @endif
            </div>

            @if ($attendance?->status == 'late')
              <div class="text-[10px] sm:text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-0.5">
                <x-heroicon-s-exclamation-triangle class="h-3 w-3" />
                <span>Terlambat</span>
              </div>
            @endif
          </div>
        </div>

        <p class="mt-2 text-[10px] sm:text-xs font-medium {{ $canManualCheckIn ? 'text-emerald-800 dark:text-emerald-200 font-semibold' : 'text-gray-500 dark:text-gray-400' }} line-clamp-2">
          @if($canManualCheckIn)
            Tap di sini untuk Absen Masuk (GPS)
          @else
            Waktu presensi masuk hari ini
          @endif
        </p>
      </div>

      <!-- CARD JAM KELUAR -->
      <div
        @if($canManualCheckOut)
          wire:click="manualCheckOut"
          title="Klik untuk Absen Keluar (Verifikasi GPS Radius Barcode Kantor)"
        @elseif($isCheckOutLockedUntilWindow)
          title="{{ !$hasShift ? 'Pilih shift terlebih dahulu sebelum melakukan presensi.' : 'Absen Keluar belum dibuka. Dapat diakses mulai pukul ' . $checkOutUnlockTime . ' (1 jam sebelum shift berakhir).' }}"
        @endif
        class="col-span-1 relative flex flex-col justify-between rounded-xl p-2.5 sm:p-4 transition-all duration-200
               {{ $canManualCheckOut 
                  ? 'cursor-pointer bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100 dark:from-amber-950 dark:via-orange-950 dark:to-amber-900 border-2 border-amber-500 dark:border-amber-500 shadow-md shadow-amber-500/15 hover:shadow-lg hover:scale-[1.01] active:scale-95' 
                  : ($attendance?->time_out 
                      ? 'bg-amber-50/80 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800' 
                      : ($isCheckOutLockedUntilWindow 
                          ? 'bg-gray-100/90 dark:bg-gray-800/60 border border-gray-300/80 dark:border-gray-700/80 cursor-not-allowed opacity-90' 
                          : 'bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 opacity-80')) }}">
        
        <div class="space-y-2">
          <!-- HEADER: ICON & TITLE (JAM KELUAR) -->
          <div class="flex items-center gap-1.5 sm:gap-2">
            <div class="rounded-lg p-1.5 sm:p-2 {{ $canManualCheckOut ? 'bg-amber-600 text-white' : ($attendance?->time_out ? 'bg-amber-600/10 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400') }} shrink-0">
              @if($isCheckOutLockedUntilWindow)
                <x-heroicon-o-lock-closed class="h-4 w-4 sm:h-5 sm:w-5 text-amber-600 dark:text-amber-400" />
              @else
                <x-heroicon-o-arrows-pointing-out class="h-4 w-4 sm:h-5 sm:w-5" />
              @endif
            </div>
            <h4 class="text-xs sm:text-base font-bold text-gray-900 dark:text-white leading-tight truncate">Jam Keluar</h4>
          </div>

          <!-- VALUE & BADGES (DI BAWAH JUDUL) -->
          <div class="space-y-1 pt-0.5">
            <div>
              @if($canManualCheckOut)
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-600 px-2 py-0.5 text-[10px] sm:text-xs font-bold text-white shadow-xs animate-pulse">
                  <span>Klik Absen</span>
                  <x-heroicon-s-hand-raised class="h-3 w-3" />
                </span>
              @elseif($isCheckOutLockedUntilWindow)
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-900/60 px-2 py-0.5 text-[10px] sm:text-xs font-bold text-amber-800 dark:text-amber-300">
                  <x-heroicon-o-lock-closed class="h-3 w-3 text-amber-600 dark:text-amber-400" />
                  {{ !$hasShift ? 'Pilih Shift Dulu' : 'Buka Pukul ' . $checkOutUnlockTime }}
                </span>
              @elseif($attendance?->time_out)
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] sm:text-xs font-bold text-amber-800 dark:bg-amber-900/80 dark:text-amber-200">
                  <x-heroicon-s-check-circle class="h-3 w-3 text-amber-600 dark:text-amber-400" />
                  Sudah Keluar
                </span>
              @endif
            </div>

            <div class="text-sm sm:text-xl font-extrabold font-mono tracking-tight {{ $attendance?->time_out ? 'text-amber-700 dark:text-amber-300' : ($canManualCheckOut ? 'text-amber-900 dark:text-amber-100' : 'text-gray-500 dark:text-gray-400') }}">
              @if ($isAbsence)
                {{ __($attendance?->status) ?? '-' }}
              @else
                {{ $attendance?->time_out ? \Carbon\Carbon::parse($attendance?->time_out)->format('H:i:s') : 'Belum Absen' }}
              @endif
            </div>
          </div>
        </div>

        <p class="mt-2 text-[10px] sm:text-xs font-medium {{ $canManualCheckOut ? 'text-amber-800 dark:text-amber-200 font-semibold' : 'text-gray-500 dark:text-gray-400' }} line-clamp-2">
          @if($canManualCheckOut)
            Tap di sini untuk Absen Keluar (GPS)
          @elseif($isCheckOutLockedUntilWindow)
            {{ !$hasShift ? 'Pilih shift kerja terlebih dahulu' : 'Buka pukul ' . $checkOutUnlockTime . ' (1 jam sblm shift usai)' }}
          @else
            Waktu presensi keluar hari ini
          @endif
        </p>
      </div>
    </div>

    <!-- 5. NAVIGATION BUTTONS: ABSENSI, SLIP GAJI, LEMBUR & GANTI JAM (Hidden on mobile - available in bottom nav bar) -->
    <div class="hidden md:grid grid-cols-2 gap-3 pt-2">
      <a href="{{ route('attendance-history') }}" class="col-span-1 cursor-pointer">
        <div
          class="flex flex-row items-center justify-center gap-2 rounded-xl bg-blue-600 dark:bg-blue-600 px-3 py-3 text-center font-bold text-white shadow-sm transition-all duration-100 hover:bg-blue-700 dark:hover:bg-blue-500 md:gap-2 active:scale-95">
          <x-heroicon-o-clock class="h-5 w-5 text-white shrink-0" />
          <span class="whitespace-nowrap">Absensi</span>
        </div>
      </a>
      <a href="{{ route('user.payslips') }}" class="col-span-1 cursor-pointer">
        <div
          class="flex flex-row items-center justify-center gap-2 rounded-xl bg-emerald-600 dark:bg-emerald-600 px-3 py-3 text-center font-bold text-white shadow-sm transition-all duration-100 hover:bg-emerald-700 dark:hover:bg-emerald-500 md:gap-2 active:scale-95">
          <x-heroicon-o-document-text class="h-5 w-5 text-white shrink-0" />
          <span class="whitespace-nowrap">Slip Gaji</span>
        </div>
      </a>
      <a href="{{ route('user.overtimes') }}" class="col-span-1 cursor-pointer">
        <div
          class="flex flex-row items-center justify-center gap-2 rounded-xl bg-purple-600 dark:bg-purple-600 px-3 py-3 text-center font-bold text-white shadow-sm transition-all duration-100 hover:bg-purple-700 dark:hover:bg-purple-500 md:gap-2 active:scale-95">
          <x-heroicon-o-fire class="h-5 w-5 text-white shrink-0" />
          <span class="whitespace-nowrap">Lembur</span>
        </div>
      </a>
      <a href="{{ route('user.replacement-hours') }}" class="col-span-1 cursor-pointer">
        <div
          class="flex flex-row items-center justify-center gap-2 rounded-xl bg-amber-600 dark:bg-amber-600 px-3 py-3 text-center font-bold text-white shadow-sm transition-all duration-100 hover:bg-amber-700 dark:hover:bg-amber-500 md:gap-2 active:scale-95">
          <x-heroicon-o-arrow-path class="h-5 w-5 text-white shrink-0" />
          <span class="whitespace-nowrap">Ganti Jam</span>
        </div>
      </a>
    </div>

    <hr class="my-4 border-gray-200 dark:border-gray-700">

    <!-- 6. FORM MODALS BUTTONS: AJUKAN IZIN, SAKIT, CUTI, IMP -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <button type="button" x-data @click.prevent="$dispatch('open-apply-leave-modal')" class="col-span-1 cursor-pointer w-full text-left">
        <div
          class="flex flex-row items-center justify-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 px-3 py-2.5 text-center font-bold text-gray-700 dark:text-gray-200 transition duration-150 hover:bg-gray-200 dark:hover:bg-gray-700 dark:hover:border-gray-600 md:gap-2 active:scale-95 shadow-xs">
          <x-heroicon-o-document-text class="h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" />
          <span class="whitespace-nowrap">Ajukan Izin</span>
        </div>
      </button>
      <button type="button" x-data @click.prevent="$dispatch('open-apply-sick-modal')" class="col-span-1 cursor-pointer w-full text-left">
        <div
          class="flex flex-row items-center justify-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 px-3 py-2.5 text-center font-bold text-gray-700 dark:text-gray-200 transition duration-150 hover:bg-gray-200 dark:hover:bg-gray-700 dark:hover:border-gray-600 md:gap-2 active:scale-95 shadow-xs">
          <x-heroicon-o-heart class="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400" />
          <span class="whitespace-nowrap">Ajukan Sakit</span>
        </div>
      </button>
      <button type="button" x-data @click.prevent="$dispatch('open-apply-cuti-modal')" class="col-span-1 cursor-pointer w-full text-left">
        <div
          class="flex flex-row items-center justify-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 px-3 py-2.5 text-center font-bold text-gray-700 dark:text-gray-200 transition duration-150 hover:bg-gray-200 dark:hover:bg-gray-700 dark:hover:border-gray-600 md:gap-2 active:scale-95 shadow-xs">
          <x-heroicon-o-calendar-days class="h-5 w-5 shrink-0 text-purple-600 dark:text-purple-400" />
          <span class="whitespace-nowrap">Ajukan Cuti</span>
        </div>
      </button>
      <button type="button" x-data @click.prevent="$dispatch('open-apply-imp-modal')" class="col-span-1 cursor-pointer w-full text-left">
        <div
          class="flex flex-row items-center justify-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 px-3 py-2.5 text-center font-bold text-gray-700 dark:text-gray-200 transition duration-150 hover:bg-gray-200 dark:hover:bg-gray-700 dark:hover:border-gray-600 md:gap-2 active:scale-95 shadow-xs">
          <x-heroicon-o-user-minus class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
          <span class="whitespace-nowrap">Ajukan IMP</span>
        </div>
      </button>
    </div>

    @livewire('user.apply-leave-modal-component')

    <hr class="my-6 border-gray-200 dark:border-gray-700">

    <!-- 7. REAL-TIME LEADERBOARD KERAJINAN WIDGET (UNIFIED IN SINGLE CARD) -->
    <div class="pt-2">
      @livewire('leaderboard-widget')
    </div>
  </div>
</div>

@push('scripts')
  {{-- Leaflet is lazy-loaded only when the map modal opens - see initModalMap() --}}
@endpush

@script
  <script>
    // Anti-Fake GPS & DOM Integrity Hardening Engine
    window.hasLocation = false;
    let liveLat = null;
    let liveLng = null;
    let modalLiveMap = null;
    let modalMarker = null;

    let lastPositionTimestamp = null;
    let lastPositionLat = null;
    let lastPositionLng = null;

    function isGeolocationTampered() {
      try {
        if (!navigator.geolocation || !navigator.geolocation.getCurrentPosition) {
          return true;
        }
        const fnStr = Function.prototype.toString.call(navigator.geolocation.getCurrentPosition);
        if (!fnStr.includes('[native code]')) {
          console.warn('[Security Hardening] Geolocation API proxy override detected.');
          return true;
        }
      } catch (e) {
        return true;
      }
      return false;
    }

    function validateLocationIntegrity(position) {
      if (isGeolocationTampered()) {
        alert('Presensi Ditolak: Terdeteksi penggunaan ekstensi/plugin Fake GPS pada browser.');
        return false;
      }

      const coords = position ? position.coords : null;
      if (!coords) return false;

      // 1. Accuracy Check: Fake GPS tools often report zero/unnatural accuracy
      if (coords.accuracy !== undefined && coords.accuracy === 0) {
        console.warn('[Security Hardening] Suspicious 0m GPS accuracy detected.');
        alert('Presensi Ditolak: Akurasi sinyal GPS terdeteksi tidak wajar (Indikasi Fake GPS).');
        return false;
      }

      // 2. High Speed / Instantaneous Position Teleportation Check
      const now = Date.now();
      if (lastPositionTimestamp && lastPositionLat !== null && lastPositionLng !== null) {
        const timeDiffSec = (now - lastPositionTimestamp) / 1000;
        if (timeDiffSec > 0 && timeDiffSec < 3) {
          const dLat = (coords.latitude - lastPositionLat) * 111000;
          const dLng = (coords.longitude - lastPositionLng) * 111000 * Math.cos(coords.latitude * Math.PI / 180);
          const distMeters = Math.sqrt(dLat * dLat + dLng * dLng);

          if (distMeters > 300) {
            console.warn('[Security Hardening] Instantaneous position teleportation detected.');
            alert('Presensi Ditolak: Terdeteksi loncatan posisi lokasi mendadak (Potensi Fake GPS).');
            return false;
          }
        }
      }

      lastPositionTimestamp = now;
      lastPositionLat = coords.latitude;
      lastPositionLng = coords.longitude;

      return true;
    }

    function requestLiveLocation(isManualRefresh = false) {
      if (!navigator.geolocation) {
        window.hasLocation = false;
        return;
      }

      const options = {
        enableHighAccuracy: true,
        timeout: isManualRefresh ? 6000 : 8000,
        maximumAge: isManualRefresh ? 0 : 15000
      };

      navigator.geolocation.getCurrentPosition(
        async (position) => {
          if (!validateLocationIntegrity(position)) {
            window.hasLocation = false;
            return;
          }

          liveLat = position.coords.latitude;
          liveLng = position.coords.longitude;
          const accuracy = position.coords.accuracy || 10;
          const timestamp = Math.floor(position.timestamp / 1000) || Math.floor(Date.now() / 1000);

          window.hasLocation = true;

          // INSTANT OPTIMISTIC UI UPDATE
          window.dispatchEvent(new CustomEvent('geo-updated', { detail: [liveLat, liveLng] }));

          const coordsText = document.querySelector('#modal-coords-text');
          if (coordsText) {
            coordsText.innerHTML = `${liveLat}, ${liveLng}`;
          }

          if (modalLiveMap && modalMarker) {
            modalMarker.setLatLng([liveLat, liveLng]);
            modalLiveMap.setView([liveLat, liveLng], 16);
          }

          // DIRECT SERVER-SIDE GPS VERIFICATION (No client-side token forging)
          try {
            $wire.updateLiveLocation(liveLat, liveLng, accuracy, timestamp).then(err => {
              if (err && typeof err === 'string') {
                console.warn('[GPS Security Verification Warning]', err);
              }
            });
          } catch (e) {
            console.error('[GPS Verification Error]', e);
          }
        },
        (error) => {
          // Quietly handle location errors without clogging developer console
          window.hasLocation = false;
        },
        options
      );
    }

    async function initGeolocationEngine() {
      if (!navigator.geolocation) {
        window.hasLocation = false;
        return;
      }

      if (navigator.permissions && navigator.permissions.query) {
        try {
          const perm = await navigator.permissions.query({ name: 'geolocation' });
          if (perm.state === 'denied') {
            window.hasLocation = false;
            return;
          }
          perm.onchange = () => {
            if (perm.state === 'granted') {
              requestLiveLocation(true);
            }
          };
        } catch (e) {}
      }

      requestLiveLocation(false);
    }

    initGeolocationEngine();

    const btnRefreshGeo = document.querySelector('#btn-refresh-location');
    if (btnRefreshGeo) {
      btnRefreshGeo.addEventListener('click', () => {
        requestLiveLocation(true);
      });
    }

    let leafletLoaded = false;
    function loadLeaflet() {
      return new Promise((resolve) => {
        if (leafletLoaded && window.L) { resolve(); return; }

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
        js.onload = () => { leafletLoaded = true; resolve(); };
        document.head.appendChild(js);
      });
    }

    window.initModalMap = async function () {
      const modalMapEl = document.querySelector('#modal-map');
      if (!modalMapEl) return;

      await loadLeaflet();

      const lat = liveLat || Number({{ $attendance?->latitude ?? -6.200000 }});
      const lng = liveLng || Number({{ $attendance?->longitude ?? 106.816666 }});

      if (!modalLiveMap) {
        modalLiveMap = L.map('modal-map').setView([lat, lng], 16);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap'
        }).addTo(modalLiveMap);

        modalMarker = L.marker([lat, lng]).addTo(modalLiveMap);
      } else {
        modalLiveMap.setView([lat, lng], 16);
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
  </script>
@endscript
