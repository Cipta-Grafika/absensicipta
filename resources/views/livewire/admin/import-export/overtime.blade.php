<div class="space-y-6">

  <!-- TOP STATS SUMMARY CARDS -->
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Card 1: Total Pengajuan Lembur -->
    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Pengajuan</p>
          <h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($totalOvertimes) }}</h3>
          <p class="mt-1 text-[11px] text-sky-600 dark:text-sky-400 font-medium">Transaksi lembur sesuai filter</p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-900/40 dark:text-sky-300">
          <x-heroicon-o-document-text class="h-6 w-6" />
        </div>
      </div>
    </div>

    <!-- Card 2: Total Durasi Jam -->
    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Durasi Jam</p>
          <h3 class="mt-1 text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($totalHours, 1) }} <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Jam</span></h3>
          <p class="mt-1 text-[11px] text-indigo-500 dark:text-indigo-400 font-medium">Akumulasi jam lembur</p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
          <x-heroicon-o-clock class="h-6 w-6" />
        </div>
      </div>
    </div>

    <!-- Card 3: Total Pengeluaran / Biaya -->
    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Biaya Lembur</p>
          <h3 class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalPayout, 0, ',', '.') }}</h3>
          <p class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Upah lembur + uang makan</p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
          <x-heroicon-o-banknotes class="h-6 w-6" />
        </div>
      </div>
    </div>

    <!-- Card 4: Total Baris Jurnal Peachtree -->
    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jurnal Peachtree</p>
          <h3 class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($peachtreeRowsCount) }} <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Baris</span></h3>
          <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400 font-medium">9 baris distribusi per cek</p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300">
          <x-heroicon-o-table-cells class="h-6 w-6" />
        </div>
      </div>
    </div>
  </div>

  <!-- MAIN FILTER & EXPORT CONFIGURATION CARD -->
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-800 sm:p-8">
    <div class="mb-6 flex flex-col gap-4 border-b border-gray-200 pb-5 dark:border-gray-700 md:flex-row md:items-center md:justify-between">
      <div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <x-heroicon-s-adjustments-horizontal class="h-5 w-5 text-sky-500" />
          Filter & Konfigurasi Ekspor Lembur
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          Tentukan parameter periode, divisi, status, dan format file yang diinginkan
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button type="button" wire:click="resetFilters" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-xs transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-650 cursor-pointer">
          <x-heroicon-o-arrow-path class="h-4 w-4" />
          Reset Filter
        </button>
        <button type="button" wire:click="toggleSettings" class="inline-flex items-center gap-1.5 rounded-xl border border-sky-300 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 shadow-xs transition hover:bg-sky-100 dark:border-sky-700 dark:bg-sky-900/50 dark:text-sky-300 dark:hover:bg-sky-900/70 cursor-pointer">
          <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
          {{ $show_peachtree_settings ? 'Sembunyikan Akun Peachtree' : 'Pengaturan Akun Peachtree' }}
        </button>
      </div>
    </div>

    <!-- FILTER FORM GRID -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Periode Bulan -->
      <div>
        <x-label for="month" value="Periode Bulan" class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1" />
        <x-input type="month" id="month" wire:model.live="month" class="w-full text-xs rounded-xl" />
      </div>

      <!-- Rentang Tanggal Mulai -->
      <div>
        <x-label for="date_from" value="Dari Tanggal (Opsional)" class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1" />
        <x-input type="date" id="date_from" wire:model.live="date_from" class="w-full text-xs rounded-xl" />
      </div>

      <!-- Rentang Tanggal Selesai -->
      <div>
        <x-label for="date_to" value="Sampai Tanggal (Opsional)" class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1" />
        <x-input type="date" id="date_to" wire:model.live="date_to" class="w-full text-xs rounded-xl" />
      </div>

      <!-- Status Persetujuan -->
      <div>
        <x-label for="status" value="Status Lembur" class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1" />
        <x-select id="status" wire:model.live="status" class="w-full text-xs rounded-xl">
          <option value="approved">Disetujui (Approved)</option>
          <option value="paid">Sudah Dibayar (Paid)</option>
          <option value="pending">Menunggu Persetujuan (Pending)</option>
          <option value="rejected">Ditolak (Rejected)</option>
          <option value="all">Semua Status</option>
        </x-select>
      </div>

      <!-- Divisi (SuperAdmin Only) -->
      @if (Auth::user()->isSuperadmin)
        <div>
          <x-label for="division" value="Divisi" class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1" />
          <x-select id="division" wire:model.live="division" class="w-full text-xs rounded-xl">
            <option value="">Semua Divisi</option>
            @foreach ($divisions as $div)
              <option value="{{ $div->id }}">{{ $div->name }}</option>
            @endforeach
          </x-select>
        </div>
      @else
        <div>
          <x-label value="Divisi Anda" class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1" />
          <div class="flex h-10 w-full items-center rounded-xl border border-gray-300 bg-gray-50 px-3 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            {{ Auth::user()->division?->name ?? 'Divisi Terkunci' }}
          </div>
        </div>
      @endif

      <!-- Jabatan -->
      <div>
        <x-label for="job_title" value="Jabatan (Job Title)" class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1" />
        <x-select id="job_title" wire:model.live="job_title" class="w-full text-xs rounded-xl">
          <option value="">Semua Jabatan</option>
          @foreach ($jobTitles as $jt)
            <option value="{{ $jt->id }}">{{ $jt->name }}</option>
          @endforeach
        </x-select>
      </div>

      <!-- Format File Ekspor -->
      <div class="sm:col-span-2">
        <x-label value="Pilih Format Ekspor" class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1" />
        <div class="grid grid-cols-2 gap-3">
          <label class="relative flex cursor-pointer items-center justify-between rounded-xl border p-3 shadow-xs transition-all {{ $export_format === 'peachtree_csv' ? 'border-sky-500 bg-sky-50 dark:border-sky-500 dark:bg-sky-900/30 ring-2 ring-sky-500/20' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700' }}">
            <div class="flex items-center gap-3">
              <input type="radio" name="export_format" value="peachtree_csv" wire:model.live="export_format" class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
              <div>
                <span class="block text-xs font-bold text-gray-900 dark:text-white">Peachtree 2011 (.CSV)</span>
                <span class="block text-[10px] text-gray-500 dark:text-gray-400">Jurnal Penggajian (51 Kolom & 9 Baris)</span>
              </div>
            </div>
            <span class="inline-flex items-center rounded-md bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-800 dark:bg-sky-900/80 dark:text-sky-200">Accounting</span>
          </label>

          <label class="relative flex cursor-pointer items-center justify-between rounded-xl border p-3 shadow-xs transition-all {{ $export_format === 'excel_xlsx' ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-500 dark:bg-emerald-900/30 ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700' }}">
            <div class="flex items-center gap-3">
              <input type="radio" name="export_format" value="excel_xlsx" wire:model.live="export_format" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
              <div>
                <span class="block text-xs font-bold text-gray-900 dark:text-white">Excel Rekap (.XLSX)</span>
                <span class="block text-[10px] text-gray-500 dark:text-gray-400">Laporan Detail Operasional & HR</span>
              </div>
            </div>
            <span class="inline-flex items-center rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-200">HR Report</span>
          </label>
        </div>
      </div>
    </div>

    <!-- PEACHTREE ADVANCED GL ACCOUNTS CONFIGURATION (COLLAPSIBLE) -->
    @if ($show_peachtree_settings)
      <div class="mt-6 rounded-2xl border border-sky-200 bg-sky-50/50 p-5 dark:border-sky-800 dark:bg-sky-950/40">
        <div class="mb-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <x-heroicon-s-cog-6-tooth class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Pengaturan Akun & Parameter Peachtree 2011</h4>
          </div>
          <span class="text-[11px] text-gray-500 dark:text-gray-400">Format Akun: Prefix-{Kode Divisi}</span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <!-- Akun Kas / Bank -->
          <div>
            <x-label for="cash_account_prefix" value="Prefix Akun Kas (Cash Account)" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
            <x-input type="text" id="cash_account_prefix" wire:model.live="cash_account_prefix" placeholder="10010" class="w-full text-xs rounded-xl" />
          </div>

          <!-- Akun Biaya Lembur (Field 1 & 2) -->
          <div>
            <x-label for="overtime_account_prefix" value="Akun Biaya Lembur (Overtime GL)" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
            <x-input type="text" id="overtime_account_prefix" wire:model.live="overtime_account_prefix" placeholder="70020" class="w-full text-xs rounded-xl" />
          </div>

          <!-- Akun Biaya Makan (Field 3) -->
          <div>
            <x-label for="meal_account_prefix" value="Akun Uang Makan (Meal GL)" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
            <x-input type="text" id="meal_account_prefix" wire:model.live="meal_account_prefix" placeholder="70060" class="w-full text-xs rounded-xl" />
          </div>

          <!-- Akun Kewajiban / Potongan (Field 22-24, 51-53) -->
          <div>
            <x-label for="liability_account_prefix" value="Akun Kewajiban/Potongan (Liability GL)" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
            <x-input type="text" id="liability_account_prefix" wire:model.live="liability_account_prefix" placeholder="21120" class="w-full text-xs rounded-xl" />
          </div>

          <!-- Akun Beban Perusahaan (Field 51-53 Exp) -->
          <div>
            <x-label for="expense_account_prefix" value="Akun Beban Perusahaan (Expense GL)" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
            <x-input type="text" id="expense_account_prefix" wire:model.live="expense_account_prefix" placeholder="78010" class="w-full text-xs rounded-xl" />
          </div>

          <!-- Mode Check Number -->
          <div>
            <x-label for="check_number_mode" value="Format Check Number / Cek" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
            <x-select id="check_number_mode" wire:model.live="check_number_mode" class="w-full text-xs rounded-xl">
              <option value="custom">Teks Kustom / Kosong (Default)</option>
              <option value="prefix_id">Nomor Voucher ID (LEMBUR-{ID})</option>
              <option value="nip">NIP Karyawan</option>
            </x-select>
          </div>

          <!-- Teks Check Number Kustom -->
          @if ($check_number_mode === 'custom')
            <div>
              <x-label for="check_number_custom" value="Nilai Check Number Kustom (Bisa Dikosongkan)" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
              <x-input type="text" id="check_number_custom" wire:model.live="check_number_custom" placeholder="Kosongkan atau ketik jika ada" class="w-full text-xs rounded-xl" />
            </div>
          @else
            <div>
              <x-label value="Contoh Output Check Number" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
              <div class="flex h-10 w-full items-center rounded-xl border border-gray-300 bg-gray-50 px-3 text-xs font-mono text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                {{ $check_number_mode === 'nip' ? 'EMP-001' : 'LEMBUR-101' }}
              </div>
            </div>
          @endif

          <!-- Periode Akuntansi & Frekuensi -->
          <div>
            <x-label for="transaction_period" value="Transaction Period (Kosongkan = Otomatis)" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" />
            <x-input type="number" id="transaction_period" wire:model.live="transaction_period" placeholder="Contoh: 15" class="w-full text-xs rounded-xl" />
          </div>
        </div>
      </div>
    @endif

    <!-- ACTION BUTTONS SECTION -->
    <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
          <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
          Siap diekspor: <b class="text-gray-900 dark:text-white">{{ $totalOvertimes }} data</b> ({{ $peachtreeRowsCount }} baris Peachtree)
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <!-- Quick Export Excel Button -->
        <button type="button" wire:click="exportExcel" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700 shadow-xs transition-colors hover:bg-emerald-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-600 dark:hover:text-white cursor-pointer">
          <x-heroicon-s-table-cells class="h-4 w-4" />
          Ekspor Excel (.xlsx)
        </button>

        <!-- Primary Export Button (Dynamic) -->
        <button type="button" wire:click="export" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-500 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-sky-500/20 transition-colors hover:bg-sky-600 active:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
          <x-heroicon-s-arrow-down-tray class="h-4 w-4 text-white" />
          <span class="text-white font-bold">
            @if ($export_format === 'peachtree_csv')
              Unduh CSV Peachtree 2011
            @else
              Unduh Excel (.xlsx)
            @endif
          </span>
        </button>
      </div>
    </div>
  </div>

  <!-- INTERACTIVE PREVIEW SECTION WITH TABS -->
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-800 sm:p-8">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
      <div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <x-heroicon-o-eye class="h-5 w-5 text-sky-500" />
          Pratinjau Data (Live Preview)
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          Pastikan format data dan nominal akun sesuai sebelum diimpor ke Peachtree 2011
        </p>
      </div>

      <!-- Tab Switcher -->
      <div class="inline-flex rounded-xl bg-gray-100 p-1 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
        <button type="button" wire:click="$set('preview_tab', 'peachtree')" class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs transition cursor-pointer {{ $preview_tab === 'peachtree' ? 'bg-white text-sky-600 shadow-sm dark:bg-gray-800 dark:text-sky-400 font-bold' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white font-medium' }}">
          <x-heroicon-s-table-cells class="h-4 w-4" />
          Jurnal Peachtree (51 Kolom)
        </button>
        <button type="button" wire:click="$set('preview_tab', 'data')" class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs transition cursor-pointer {{ $preview_tab === 'data' ? 'bg-white text-sky-600 shadow-sm dark:bg-gray-800 dark:text-sky-400 font-bold' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white font-medium' }}">
          <x-heroicon-s-document-text class="h-4 w-4" />
          Daftar Lembur (Rekap)
        </button>
      </div>
    </div>

    @if ($preview_tab === 'peachtree')
      <!-- TAB 1: PEACHTREE 2011 CSV FORMAT PREVIEW -->
      <div class="space-y-3">
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
          <span>Menampilkan pratinjau baris jurnal Peachtree (9 baris distribusi per data cek)</span>
          <span class="font-mono font-medium text-sky-600 dark:text-sky-400">Format: Standard Peachtree 2011 CSV</span>
        </div>

        <div class="relative w-full overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm custom-scrollbar-x max-h-[500px]">
          <table class="w-full text-left text-xs whitespace-nowrap divide-y divide-gray-200 dark:divide-gray-700 border-collapse">
            <thead class="sticky top-0 z-20 bg-gray-50 text-gray-700 dark:bg-gray-900 dark:text-gray-200 uppercase tracking-wider font-bold text-[11px] shadow-sm">
              <tr>
                <th class="px-3 py-3 text-center border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">#</th>
                @foreach ($peachtreeHeadings as $h)
                  <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px] whitespace-nowrap">{{ $h }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white font-mono text-[11px] dark:divide-gray-700 dark:bg-gray-800">
              @forelse ($peachtreeRows as $idx => $row)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $idx % 9 === 0 ? 'border-t-2 border-sky-400 dark:border-sky-500 font-semibold bg-sky-50/40 dark:bg-sky-950/40' : '' }}">
                  <td class="px-3 py-2 text-center text-gray-500 dark:text-gray-400 font-sans border-r border-gray-200 dark:border-gray-700">
                    {{ $idx + 1 }}
                  </td>
                  @foreach ($row as $colIdx => $colVal)
                    <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-gray-900 dark:text-gray-100">
                      @if ($colIdx === 8 && is_numeric($colVal))
                        <span class="font-bold text-red-600 dark:text-red-400">{{ $colVal }}</span>
                      @elseif ($colIdx === 39 && is_numeric($colVal) && $colVal > 0)
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $colVal }}</span>
                      @elseif ($colIdx === 34)
                        <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $colVal }}</span>
                      @else
                        {{ $colVal !== '' ? $colVal : '-' }}
                      @endif
                    </td>
                  @endforeach
                </tr>
              @empty
                <tr>
                  <td colspan="{{ count($peachtreeHeadings) + 1 }}" class="py-12 text-center text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-inbox class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500 mb-2" />
                    <p class="font-medium text-gray-600 dark:text-gray-300">Tidak ada data lembur yang sesuai dengan filter yang dipilih.</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    @else
      <!-- TAB 2: OPERATIONAL OVERTIME SUMMARY TABLE PREVIEW -->
      <div class="relative w-full overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm max-h-[500px]">
        <table class="w-full text-left text-xs whitespace-nowrap divide-y divide-gray-200 dark:divide-gray-700 border-collapse">
          <thead class="sticky top-0 z-20 bg-gray-50 text-gray-700 dark:bg-gray-900 dark:text-gray-200 uppercase tracking-wider font-bold text-[11px] shadow-sm">
            <tr>
              <th class="px-3 py-3 text-center border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">No</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">NIP</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Nama Karyawan</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Divisi</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Tanggal</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Jam Kerja</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Durasi</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Tarif / Jam</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Uang Makan</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Total Upah</th>
              <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Status</th>
              <th class="px-3 py-3 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold text-[11px]">Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white text-xs dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($overtimes as $idx => $ot)
              @php
                $emp = $ot->employee;
                $dur = (float)($ot->duration_hours ?? $ot->calculateDuration());
                $payCalc = \App\Models\OvertimeRate::calculatePayForDuration($dur, $emp);
                $meal = (float)($payCalc['meal_allowance'] ?? 0);
                $tot = (float)($ot->total_pay ?? $ot->overtime_pay ?? 0);
              @endphp
              <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-3 py-2.5 text-center text-gray-500 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700">{{ $idx + 1 }}</td>
                <td class="px-3 py-2.5 font-mono font-medium text-gray-900 dark:text-gray-100 border-r border-gray-100 dark:border-gray-700">{{ $emp?->nip ?? '-' }}</td>
                <td class="px-3 py-2.5 font-bold text-gray-900 dark:text-gray-100 border-r border-gray-100 dark:border-gray-700">{{ $emp?->name ?? '-' }}</td>
                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 border-r border-gray-100 dark:border-gray-700">{{ $emp?->division?->name ?? '-' }}</td>
                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 border-r border-gray-100 dark:border-gray-700">{{ $ot->overtime_date ? \Carbon\Carbon::parse($ot->overtime_date)->format('d/m/Y') : '-' }}</td>
                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 font-mono border-r border-gray-100 dark:border-gray-700">
                  {{ $ot->start_time ? \Carbon\Carbon::parse($ot->start_time)->format('H:i') : '' }} - {{ $ot->end_time ? \Carbon\Carbon::parse($ot->end_time)->format('H:i') : '' }}
                </td>
                <td class="px-3 py-2.5 font-semibold text-sky-600 dark:text-sky-400 border-r border-gray-100 dark:border-gray-700">{{ $dur }} Jam</td>
                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 border-r border-gray-100 dark:border-gray-700">Rp {{ number_format($ot->applied_rate_amount ?? $payCalc['applied_rate_amount'] ?? 0, 0, ',', '.') }}</td>
                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 border-r border-gray-100 dark:border-gray-700">Rp {{ number_format($meal, 0, ',', '.') }}</td>
                <td class="px-3 py-2.5 font-bold text-emerald-600 dark:text-emerald-400 border-r border-gray-100 dark:border-gray-700">Rp {{ number_format($tot, 0, ',', '.') }}</td>
                <td class="px-3 py-2.5 border-r border-gray-100 dark:border-gray-700">
                  @if ($ot->status === 'approved')
                    <span class="inline-flex items-center rounded-md bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-800 dark:bg-sky-900/80 dark:text-sky-200">Disetujui</span>
                  @elseif ($ot->status === 'paid')
                    <span class="inline-flex items-center rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-200">Dibayar</span>
                  @elseif ($ot->status === 'pending')
                    <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800 dark:bg-amber-900/80 dark:text-amber-200">Menunggu</span>
                  @else
                    <span class="inline-flex items-center rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-800 dark:bg-rose-900/80 dark:text-rose-200">Ditolak</span>
                  @endif
                </td>
                <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400 truncate max-w-xs">{{ $ot->reason ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="12" class="py-12 text-center text-gray-500 dark:text-gray-400">
                  <x-heroicon-o-inbox class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500 mb-2" />
                  <p class="font-medium text-gray-600 dark:text-gray-300">Tidak ada data lembur yang ditemukan.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif
  </div>

</div>

