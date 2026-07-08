<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Pengajuan Izin Baru
      </h2>
      <div class="absolute right-0 flex items-center gap-2">
        <x-secondary-button href="{{ route('home') }}">
          <x-heroicon-o-chevron-left class="mr-1.5 h-4 w-4" />
          Kembali
        </x-secondary-button>
      </div>
    </div>
  </x-slot>

  <div class="py-0 sm:py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
      <div class="overflow-hidden bg-white shadow-xl dark:bg-gray-800 sm:rounded-lg">
        <div class="p-6 lg:p-8">
          {{--  --}}
          <form action="{{ route('store-leave-request') }}" method="post" enctype="multipart/form-data" x-data="{ leaveStatus: '{{ old('status', request('status', $attendance?->status ?? 'excused')) }}' }">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <div>
                  <x-label for="status" value="{{ __('Status') }}" />
                  <x-select id="status" class="mt-1 block w-full" name="status" required x-model="leaveStatus">
                    <option value="excused"
                      {{ (old('status', request('status', $attendance?->status))) === 'excused' ? 'selected' : '' }}>
                      Izin
                    </option>
                    <option value="sick" {{ (old('status', request('status', $attendance?->status))) === 'sick' ? 'selected' : '' }}>
                      Sakit
                    </option>
                    <option value="leave" {{ (old('status', request('status', $attendance?->status))) === 'leave' ? 'selected' : '' }}>
                      Cuti
                    </option>
                    <option value="wfh" {{ (old('status', request('status', $attendance?->status))) === 'wfh' ? 'selected' : '' }}>
                      WFH
                    </option>
                    <option value="imp" {{ (old('status', request('status', $attendance?->status))) === 'imp' ? 'selected' : '' }}>
                      IMP (Izin Meninggalkan Pekerjaan)
                    </option>
                    <option value="special-leaves" {{ (old('status', request('status', $attendance?->status))) === 'special-leaves' ? 'selected' : '' }}>
                      Cuti Khusus
                    </option>
                  </x-select>
                  @error('status')
                    <x-input-error for="status" class="mt-2" message="{{ $message }}" />
                  @enderror
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-3">
                  <div>
                    <x-label for="from">
                      <span x-text="leaveStatus === 'imp' ? 'Tanggal IMP' : 'Tanggal mulai'"></span>
                    </x-label>
                    <x-input type="date" x-bind:min="leaveStatus === 'imp' ? '{{ date('Y-m-01') }}' : '{{ date('Y-m-d') }}'" x-bind:max="leaveStatus === 'imp' ? '{{ date('Y-m-t') }}' : ''" value="{{ old('from', date('Y-m-d')) }}" id="from"
                      class="mt-1 block w-full" name="from" required />
                    @error('from')
                      <x-input-error for="from" class="mt-2" message="{{ $message }}" />
                    @enderror
                  </div>
                  <div x-show="leaveStatus !== 'imp'">
                    <x-label for="to" value="Tanggal berakhir (Opsional)" />
                    <x-input type="date" id="to" x-bind:min="leaveStatus === 'imp' ? '{{ date('Y-m-01') }}' : '{{ date('Y-m-d') }}'" class="mt-1 block w-full"
                      name="to" value="{{ old('to') }}" />
                    @error('to')
                      <x-input-error for="to" class="mt-2" message="{{ $message }}" />
                    @enderror
                  </div>
                  <div x-show="leaveStatus === 'imp'" style="display: none;">
                    <x-label for="imp_duration_hours" value="Durasi IMP (Jam)" />
                    <x-input type="number" id="imp_duration_hours" min="1" max="24" class="mt-1 block w-full" name="imp_duration_hours" value="{{ old('imp_duration_hours') }}" placeholder="Contoh: 3" x-bind:required="leaveStatus === 'imp'" />
                    @error('imp_duration_hours')
                      <x-input-error for="imp_duration_hours" class="mt-2" message="{{ $message }}" />
                    @enderror
                  </div>
                </div>



                <div x-show="leaveStatus === 'imp'" class="mt-4" style="display: none;">
                  <x-label for="shift_id" value="Pilih Shift" />
                  <x-select id="shift_id" class="mt-1 block w-full" name="shift_id" x-bind:required="leaveStatus === 'imp'">
                    <option value="">-- Pilih Shift --</option>
                    @foreach($shifts ?? [] as $shift)
                      <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>{{ $shift->name }} (Target: {{ floor(\Carbon\Carbon::parse($shift->start_time)->diffInMinutes(\Carbon\Carbon::parse($shift->end_time)) / 60) }} jam)</option>
                    @endforeach
                  </x-select>
                  @error('shift_id')
                    <x-input-error for="shift_id" class="mt-2" message="{{ $message }}" />
                  @enderror
                </div>

                <div class="mt-4">
                  <x-label for="note" value="Keterangan" />
                  <x-textarea id="note" type="text" class="mt-1 block w-full" name="note" required
                    value="{{ old('note') ?? $attendance?->note }}" />
                  <x-input-error for="note" class="mt-2" />
                </div>
              </div>

              <div x-data="{ filename: null, preview: null }">
                <input type="file" value="{{ old('attachment') ?? $attendance?->attachment }}" class="hidden"
                  id="attachment" name="attachment" x-ref="attachment"
                  x-on:change="
                                filename = $refs.attachment.files[0].name;
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    preview = e.target.result;
                                };
                                reader.readAsDataURL($refs.attachment.files[0]);
                            " />

                <x-label for="attachment" value="{{ __('Attachment') }}" />

                <div class="mb-2 mt-2" x-show="preview" style="display: none;">
                  <span class="block h-48 max-h-72 w-full bg-contain bg-left bg-no-repeat"
                    x-bind:style="'background-image: url(\'' + preview + '\');'">
                  </span>
                </div>

                @if ($attendance?->attachment)
                  <div class="mb-2 mt-2" x-show="!preview">
                    <img class="block h-48 max-h-72 w-full object-contain object-left"
                      src="{{ $attendance?->attachment_url }}" />
                  </div>
                @endif

                <x-secondary-button class="me-2 mt-2" type="button" x-on:click.prevent="$refs.attachment.click()">
                  {{ __('Select Attachment') }}
                </x-secondary-button>

                <x-secondary-button type="button" class="mt-2" x-show="preview"
                  x-on:click="filename = null; preview = null">
                  {{ __('Remove Attachment') }}
                </x-secondary-button>

                <x-input-error for="attachment" class="mt-2" />
              </div>
            </div>

            <input type="hidden" id="lat" name="lat" value="{{ $attendance?->latitude }}">
            <input type="hidden" id="lng" name="lng" value="{{ $attendance?->longitude }}">

            <div class="mb-3 mt-4 flex items-center justify-end">
              <x-button class="ms-4">
                {{ __('Save') }}
              </x-button>
            </div>
          </form>
          {{--  --}}
        </div>
      </div>
    </div>
  </div>
  @pushOnce('scripts')
    <script>
      getLocation();

      async function getLocation() {
        if (navigator.geolocation) {
          navigator.geolocation.watchPosition((position) => {
            console.log(position);
            document.getElementById('lat').value = position.coords.latitude;
            document.getElementById('lng').value = position.coords.longitude;
          }, (err) => {
            console.error(`ERROR(${err.code}): ${err.message}`);
            alert('{{ __('Please enable your location') }}');
          });
        }
      }
    </script>
  @endPushOnce
</x-app-layout>
