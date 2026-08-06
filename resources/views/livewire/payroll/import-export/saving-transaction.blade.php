<div>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:gap-6">
    @if ($mode != 'import')
      <div>
        <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Ekspor Data Mutasi Syirkah
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
              <x-label for="type" value="Tipe Transaksi" class="mb-1" />
              <x-select id="type" name="type" class="w-full" wire:model.live="type">
                <option value="">Semua Tipe</option>
                <option value="deposit">Setor (Deposit)</option>
                <option value="withdrawal">Penarikan (Withdrawal)</option>
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
            Impor Data Mutasi Syirkah
          </h3>
          <x-secondary-button type="button" wire:click="downloadTemplate" class="!py-1 !px-2.5 text-xs">
            <x-heroicon-o-arrow-down-tray class="mr-1.5 h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
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

          <div class="mb-4 space-y-1.5 rounded-md bg-emerald-50 p-3.5 text-xs text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200 border border-emerald-200/80 dark:border-emerald-800/50">
            <p class="font-bold text-emerald-950 dark:text-emerald-100 flex items-center gap-1">
              <x-heroicon-s-information-circle class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
              Catatan & Ketentuan Format Excel Mutasi Syirkah:
            </p>
            <p>1. Header kolom wajib: <b>nip</b>, <b>nama_karyawan</b>, <b>nama_syirkah</b>, <b>tanggal</b>, <b>tipe_transaksi</b>, <b>mutasi_wajib</b>, <b>mutasi_sukarela</b>, <b>keterangan</b>.</p>
            <p>2. Kolom <b>nip</b> / <b>nama_karyawan</b> dapat diisi salah satu (sistem mencocokkan NIP/Nama Karyawan).</p>
            <p>3. Format tanggal diisi dengan <b>YYYY-MM-DD</b> (Contoh: 2026-06-30).</p>
            <p>4. Tipe transaksi diisi: <b>deposit</b> (Setor/Saldo Awal) atau <b>withdrawal</b> (Penarikan/Pencairan).</p>
            <p>5. <b>Saldo Awal (Salinan Data Lama)</b>: Masukkan baris <b>deposit</b> dengan tanggal sebelum periode payroll pertama (contoh: 2026-06-30) dan keterangan <i>"Saldo Awal Syirkah"</i>.</p>
            <p>6. Sistem secara otomatis menghitung ulang seluruh <b>Saldo Wajib & Saldo Sukarela Berjalan</b> secara kronologis setelah impor selesai.</p>
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
            <th class="{{ $thClass }}">NIP / Karyawan</th>
            <th class="{{ $thClass }}">Program Syirkah</th>
            <th class="{{ $thClass }}">Tanggal</th>
            <th class="{{ $thClass }}">Tipe</th>
            <th class="{{ $thClass }}">Mutasi Wajib</th>
            <th class="{{ $thClass }}">Mutasi Sukarela</th>
            <th class="{{ $thClass }}">Saldo Wajib</th>
            <th class="{{ $thClass }}">Saldo Sukarela</th>
            <th class="{{ $thClass }}">Keterangan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
          @forelse ($transactions ?? [] as $tx)
            <tr class="{{ $trClass }}">
              <td class="px-2 py-4 text-center text-sm font-medium text-gray-900 dark:text-white">
                {{ $loop->iteration }}
              </td>
              <td class="{{ $tdClass }}">
                <div class="font-semibold text-gray-900 dark:text-white">{{ $tx->user?->name ?? '-' }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tx->user?->nip ?? '-' }}</div>
              </td>
              <td class="{{ $tdClass }}">{{ $tx->masterSaving?->savings_name ?? 'Syirkah Full' }}</td>
              <td class="{{ $tdClass }} whitespace-nowrap">{{ $tx->created_at?->format('d M Y H:i') }}</td>
              <td class="{{ $tdClass }} whitespace-nowrap">
                @if ($tx->transaction_type === 'withdrawal')
                  <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900 dark:text-red-200">
                    Penarikan
                  </span>
                @else
                  <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                    Setor / Deposit
                  </span>
                @endif
              </td>
              <td class="{{ $tdClass }} whitespace-nowrap text-emerald-600 dark:text-emerald-400">
                + Rp {{ number_format($tx->mandatory_amount, 0, ',', '.') }}
              </td>
              <td class="{{ $tdClass }} whitespace-nowrap text-emerald-600 dark:text-emerald-400">
                + Rp {{ number_format($tx->secondary_amount, 0, ',', '.') }}
              </td>
              <td class="{{ $tdClass }} whitespace-nowrap font-bold text-gray-900 dark:text-white">
                Rp {{ number_format($tx->balance_mandatory, 0, ',', '.') }}
              </td>
              <td class="{{ $tdClass }} whitespace-nowrap font-bold text-gray-900 dark:text-white">
                Rp {{ number_format($tx->balance_secondary, 0, ',', '.') }}
              </td>
              <td class="{{ $tdClass }}">{{ $tx->description ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                Tidak ada data pratinjau.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif
</div>
