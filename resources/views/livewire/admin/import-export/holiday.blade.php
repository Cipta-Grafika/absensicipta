<div>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:gap-6">
    @if ($mode != 'import')
      <div>
        <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Ekspor Data Hari Libur
        </h3>
        <form wire:submit.prevent="export">
          <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <x-label for="year" value="Per Tahun" class="mb-1"></x-label>
              <x-input type="number" min="1970" max="2099" name="year" id="year" class="mt-1 block w-full" wire:model.live="year" />
            </div>
            <div>
              <x-label for="month" value="Per Bulan" class="mb-1"></x-label>
              <x-input type="month" name="month" id="month" class="mt-1 block w-full" wire:model.live="month" />
            </div>
          </div>

          <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <x-label for="start_date" value="Dari Tanggal" class="mb-1" />
              <x-input type="date" id="start_date" class="block w-full" wire:model.live="start_date" />
            </div>
            <div>
              <x-label for="end_date" value="Sampai Tanggal" class="mb-1" />
              <x-input type="date" id="end_date" class="block w-full" wire:model.live="end_date" />
            </div>
          </div>

          <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <x-label for="type" value="Tipe Libur" class="mb-1" />
              <x-select id="type" name="type" class="w-full" wire:model.live="type">
                <option value="">Semua Tipe</option>
                <option value="general">Nasional / Umum</option>
                <option value="division">Divisi</option>
                <option value="custom">Kustom Karyawan</option>
              </x-select>
            </div>
            <div>
              <x-label for="division" value="Pilih Divisi" class="mb-1" />
              <x-select id="division" name="division" class="w-full" wire:model.live="division">
                <option value="">Semua Divisi</option>
                @foreach ($divisions as $div)
                  <option value="{{ $div->id }}">{{ $div->name }}</option>
                @endforeach
              </x-select>
            </div>
          </div>

          <div class="flex flex-col items-center justify-stretch gap-4">
            <x-secondary-button type="button" wire:click="preview" class="w-full justify-center">
              @if ($mode == 'export')
                {{ __('Cancel') }}
              @else
                {{ __('Preview') }}
              @endif
            </x-secondary-button>
            <x-button class="w-full justify-center" wire:loading.attr="disabled">
              {{ __('Export Excel') }}
            </x-button>
          </div>
        </form>
      </div>
    @endif

    @if ($mode != 'export')
      <div>
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Impor Data Hari Libur
          </h3>
          <x-secondary-button type="button" wire:click="downloadTemplate" class="!py-1 !px-2.5 text-xs">
            <x-heroicon-o-arrow-down-tray class="mr-1.5 h-3.5 w-3.5 text-indigo-500" />
            Download Template
          </x-secondary-button>
        </div>

        <form x-data="{ file: null }" wire:submit.prevent="import" method="post" enctype="multipart/form-data">
          @csrf
          <div class="mb-4 flex items-center gap-3">
            <x-secondary-button class="me-2" type="button" x-on:click.prevent="$refs.file.click()"
              x-text="file ? 'Ganti File' : 'Pilih File Excel'">
              Pilih File
            </x-secondary-button>
            <x-secondary-button class="me-2" type="button" x-show="file"
              x-on:click.prevent="$refs.file.files[0] = null; file = null; $wire.$set('file', null)">
              Hapus File
            </x-secondary-button>
            <h5 class="text-sm dark:text-gray-200" x-text="file ? file.name : 'File Belum Dipilih'"></h5>
            <x-input type="file" class="hidden" name="file" x-ref="file"
              x-on:change="file = $refs.file.files[0]" wire:model.live="file" />
          </div>

          <div class="mb-4 space-y-1.5 rounded-md bg-amber-50 p-3.5 text-xs text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/50">
            <p class="font-bold text-amber-900 dark:text-amber-200 flex items-center gap-1">
              <x-heroicon-s-exclamation-triangle class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
              Catatan & Ketentuan Format Excel Hari Libur:
            </p>
            <p>1. Header kolom wajib: <b>nama_hari_libur</b>, <b>tanggal</b>, <b>tipe_libur</b>, <b>nama_divisi</b>, <b>keterangan</b>.</p>
            <p>2. Format tanggal diisi dengan <b>YYYY-MM-DD</b> (Contoh: 2026-08-17).</p>
            <p>3. Tipe Libur diisi: <b>Nasional / Umum</b> (general), <b>Divisi</b> (division), atau <b>Kustom</b> (custom).</p>
            <p>4. Jika <b>tipe_libur</b> diisi Divisi, pastikan kolom <b>nama_divisi</b> diisi sesuai dengan nama divisi yang ada.</p>
          </div>

          <div class="flex items-center justify-stretch">
            <x-success-button class="w-full" wire:loading.attr="disabled">
              <span x-text="file ? 'Konfirmasi & Impor ' + file.name : 'Impor Data Excel'">
                Impor Data Excel
              </span>
            </x-success-button>
          </div>
        </form>
      </div>
    @endif
  </div>

  @if ($mode && $previewing)
    <h3 class="mt-6 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Preview') . ' ' . ucfirst($mode) }}
    </h3>
    <div class="mt-4 w-full overflow-x-auto text-sm">
      @php
        $trClass = 'divide-x divide-gray-200 dark:divide-gray-700';
        $thClass = 'px-4 py-3 text-left font-semibold dark:text-white';
        $tdClass = 'px-4 py-4 text-sm font-medium text-gray-900 dark:text-white';
      @endphp
      <table class="w-full divide-y divide-gray-200 border dark:divide-gray-700 dark:border-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr class="{{ $trClass }}">
            <th scope="col" class="px-2 py-3 text-left font-semibold dark:text-white">No</th>
            <th class="{{ $thClass }}">Nama Hari Libur</th>
            <th class="{{ $thClass }}">Tanggal</th>
            <th class="{{ $thClass }}">Tipe Libur</th>
            <th class="{{ $thClass }}">Divisi</th>
            <th class="{{ $thClass }}">Keterangan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
          @forelse ($holidays ?? [] as $h)
            <tr class="{{ $trClass }}">
              <td class="px-2 py-4 text-center text-sm font-medium text-gray-900 dark:text-white">
                {{ $loop->iteration }}
              </td>
              <td class="{{ $tdClass }} font-semibold">{{ $h->name }}</td>
              <td class="{{ $tdClass }} whitespace-nowrap">{{ $h->date?->format('Y-m-d') }}</td>
              <td class="{{ $tdClass }} whitespace-nowrap">
                @if ($h->type === 'general')
                  <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    Nasional / Umum
                  </span>
                @elseif ($h->type === 'division')
                  <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                    Divisi
                  </span>
                @else
                  <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                    Kustom
                  </span>
                @endif
              </td>
              <td class="{{ $tdClass }}">{{ $h->division?->name ?? 'Semua Divisi' }}</td>
              <td class="{{ $tdClass }}">{{ $h->description ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                Tidak ada data pratinjau.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif
</div>
