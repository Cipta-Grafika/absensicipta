<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-amber-600 text-white shadow-md shadow-rose-500/20">
        <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
      </div>
      <div>
        <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
          Master Log Potongan Error
        </h2>
        <p class="text-xs text-gray-500 dark:text-gray-400">Manajemen & Riwayat Potongan Kesalahan Produksi / Kerusakan Kerja Karyawan</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
      <x-button type="button" x-data @click.prevent="$dispatch('open-create-modal')" class="bg-rose-600 hover:bg-rose-700 active:bg-rose-800 focus:ring-rose-500">
        <x-heroicon-o-plus class="mr-1.5 h-4 w-4" />
        Tambah Log Error
      </x-button>
    </div>
  </div>
</x-slot>

<div class="pt-3.5 pb-6 sm:py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true" @open-create-modal.window="$wire.openCreateModal()">
  <div class="w-full sm:px-6 lg:px-8">
    <!-- Filter Sidebar Drawer -->
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Log Error</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('status', ''); $set('division', ''); $set('deduction_source', ''); $set('search', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-4">
          <div>
            <x-label for="filter_period_month" value="Bulan Periode" class="mb-1" />
            <x-input id="filter_period_month" type="month" class="w-full text-sm" wire:model.live="selected_period_month" />
          </div>

          <div>
            <x-label for="filter_status" value="Status Pemrosesan" class="mb-1" />
            <x-select id="filter_status" class="w-full text-sm" wire:model.live="status">
              <option value="">Semua Status</option>
              <option value="pending">Pending (Menunggu Persetujuan)</option>
              <option value="approved">Approved (Siap Potong)</option>
              <option value="processed">Processed (Sudah Terpotong)</option>
              <option value="cancelled">Cancelled (Dibatalkan)</option>
            </x-select>
          </div>

          <div>
            <x-label for="filter_deduction_source" value="Sumber Pemotongan" class="mb-1" />
            <x-select id="filter_deduction_source" class="w-full text-sm" wire:model.live="deduction_source">
              <option value="">Semua Sumber</option>
              <option value="payroll">Potong Gaji Bulanan</option>
              <option value="syirkah_mandatory">Syirkah Wajib</option>
              <option value="syirkah_secondary">Syirkah SSR (Sukarela)</option>
              <option value="syirkah_all">Total Saldo Syirkah</option>
            </x-select>
          </div>

          <div>
            <x-label for="filter_division" value="Divisi Karyawan" class="mb-1" />
            <x-select id="filter_division" class="w-full text-sm" wire:model.live="division">
              <option value="">Semua Divisi</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </x-select>
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <!-- Top Summary Metrics Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-xs border border-gray-100 dark:bg-gray-800 dark:border-gray-700/60">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Kasus Error</p>
            <h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($totalCases, 0, ',', '.') }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
            <x-heroicon-o-document-magnifying-glass class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
          Periode {{ \Carbon\Carbon::parse($selected_period_month . '-01')->translatedFormat('F Y') }}
        </div>
      </div>

      <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-xs border border-gray-100 dark:bg-gray-800 dark:border-gray-700/60">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Nilai Kerugian</p>
            <h3 class="mt-1 text-2xl font-black text-rose-600 dark:text-rose-400">Rp {{ number_format($totalErrorCost, 0, ',', '.') }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
            <x-heroicon-o-banknotes class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
          Estimasi total biaya kerusakan/error
        </div>
      </div>

      <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-xs border border-gray-100 dark:bg-gray-800 dark:border-gray-700/60">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Beban Potongan Karyawan</p>
            <h3 class="mt-1 text-2xl font-black text-indigo-600 dark:text-indigo-400">Rp {{ number_format($totalDeductionAmount, 0, ',', '.') }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
            <x-heroicon-o-receipt-percent class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
          Nominal yang dibebankan ke karyawan
        </div>
      </div>

      <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-xs border border-gray-100 dark:bg-gray-800 dark:border-gray-700/60">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sudah Diproses (Deducted)</p>
            <h3 class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalProcessedAmount, 0, ',', '.') }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
            <x-heroicon-o-check-circle class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
          Telah dieksekusi ke Payroll / Syirkah
        </div>
      </div>
    </div>

    <!-- Main Card & Data Table -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-xs border border-gray-100 dark:bg-gray-800 dark:border-gray-700/60">
      <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-700 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">Periode:</span>
            <x-input type="month" class="text-xs sm:text-sm font-semibold !py-1.5" wire:model.live="selected_period_month" />
          </div>
        </div>

        <div class="flex items-center gap-2">
          <div class="relative w-full sm:w-64">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
            </div>
            <x-input type="text" class="block w-full pl-9 text-xs sm:text-sm !py-1.5" wire:model.live.debounce.300ms="search" placeholder="Cari nama, NIP, judul error..." />
          </div>
          <x-button type="button" wire:click="openCreateModal" class="bg-rose-600 hover:bg-rose-700 active:bg-rose-800 focus:ring-rose-500 !py-1.5 text-xs whitespace-nowrap">
            <x-heroicon-o-plus class="mr-1 h-3.5 w-3.5" />
            Tambah Error
          </x-button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50/80 dark:bg-gray-900/40">
            <tr>
              <th scope="col" class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal & Karyawan</th>
              <th scope="col" class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Judul & Keterangan Error</th>
              <th scope="col" class="px-4 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kerugian / Potongan</th>
              <th scope="col" class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sumber Potongan</th>
              <th scope="col" class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
              <th scope="col" class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
            @forelse($errorDeductions as $item)
              <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-750 transition-colors">
                <td class="whitespace-nowrap px-4 py-3.5 text-sm">
                  <div class="font-bold text-gray-900 dark:text-white">{{ $item->user->name ?? '-' }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">NIP: {{ $item->user->nip ?? '-' }} &bull; {{ $item->user->division->name ?? 'Tanpa Divisi' }}</div>
                  <div class="mt-1 inline-flex items-center text-[11px] text-gray-400">
                    <x-heroicon-o-calendar class="mr-1 h-3.5 w-3.5 text-gray-400" />
                    {{ $item->error_date ? $item->error_date->format('d/m/Y') : '-' }}
                  </div>
                </td>

                <td class="px-4 py-3.5 text-sm max-w-xs sm:max-w-md">
                  <div class="font-semibold text-rose-700 dark:text-rose-400">{{ $item->error_title }}</div>
                  @if($item->description)
                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300 line-clamp-2" title="{{ $item->description }}">{{ $item->description }}</p>
                  @else
                    <span class="text-xs italic text-gray-400">Tidak ada rincian keterangan.</span>
                  @endif
                </td>

                <td class="whitespace-nowrap px-4 py-3.5 text-right text-sm">
                  <div class="text-xs text-gray-400 line-through" title="Total Biaya Kerugian">
                    @if($item->total_error_cost > 0)
                      Kerugian: Rp {{ number_format($item->total_error_cost, 0, ',', '.') }}
                    @endif
                  </div>
                  <div class="text-sm font-black text-rose-600 dark:text-rose-400">
                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                  </div>
                </td>

                <td class="whitespace-nowrap px-4 py-3.5 text-center text-xs">
                  @if($item->deduction_source === 'payroll')
                    <span class="inline-flex items-center rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800">
                      <x-heroicon-o-banknotes class="mr-1 h-3.5 w-3.5" />
                      Potong Gaji
                    </span>
                  @elseif($item->deduction_source === 'syirkah_mandatory')
                    <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800">
                      <x-heroicon-o-archive-box class="mr-1 h-3.5 w-3.5" />
                      Syirkah Wajib
                    </span>
                  @elseif($item->deduction_source === 'syirkah_secondary')
                    <span class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                      <x-heroicon-o-sparkles class="mr-1 h-3.5 w-3.5" />
                      Syirkah SSR
                    </span>
                  @else
                    <span class="inline-flex items-center rounded-lg bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800">
                      Total Syirkah
                    </span>
                  @endif
                </td>

                <td class="whitespace-nowrap px-4 py-3.5 text-center text-xs">
                  @if($item->status === 'processed')
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                      <x-heroicon-o-check class="mr-1 h-3 w-3" />
                      Processed
                    </span>
                  @elseif($item->status === 'approved')
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                      Approved
                    </span>
                  @elseif($item->status === 'pending')
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                      Pending
                    </span>
                  @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                      Cancelled
                    </span>
                  @endif
                </td>

                <td class="whitespace-nowrap px-4 py-3.5 text-center text-sm">
                  <div class="flex items-center justify-center gap-2">
                    @if($item->status === 'pending')
                      <button wire:click="updateStatus('{{ $item->id }}', 'approved')" class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:underline">
                        Approve
                      </button>
                    @endif

                    <button wire:click="edit('{{ $item->id }}')" class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400" title="Edit Log Error">
                      <x-heroicon-o-pencil-square class="h-4 w-4" />
                    </button>
                    <button wire:click="confirmDelete('{{ $item->id }}')" class="rounded-lg p-1 text-gray-500 hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-950/40 dark:hover:text-red-400" title="Hapus">
                      <x-heroicon-o-trash class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                  <div class="flex flex-col items-center justify-center">
                    <x-heroicon-o-check-badge class="h-10 w-10 text-emerald-400 mb-2" />
                    <p class="font-medium">Tidak ada data log potongan error untuk periode ini.</p>
                    <p class="text-xs text-gray-400 mt-1">Gunakan tombol "Tambah Log Error" di atas untuk mencatat kesalahan produksi baru.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-4 border-t border-gray-100 dark:border-gray-700">
        {{ $errorDeductions->links() }}
      </div>
    </div>
  </div>

  <!-- Modal Create / Edit Log Error -->
  <x-dialog-modal wire:model.live="isModalOpen" maxWidth="2xl">
    <x-slot name="title">
      {{ $editingId ? __('Edit Log Potongan Error Karyawan') : __('Tambah Log Potongan Error Karyawan') }}
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <!-- 1. Pilih Karyawan -->
        <div class="sm:col-span-2">
          <x-label for="user_id" value="{{ __('Pilih Karyawan') }}" />
          <x-select id="user_id" wire:model.live="user_id" class="mt-1 block w-full">
            <option value="">-- Pilih Karyawan Yang Bertanggung Jawab --</option>
            @foreach($employees as $emp)
              <option value="{{ $emp->id }}">{{ $emp->name }} (NIP: {{ $emp->nip }} &bull; {{ $emp->division->name ?? 'Tanpa Divisi' }})</option>
            @endforeach
          </x-select>
          <x-input-error for="user_id" class="mt-1" />
        </div>

        <!-- 2. Periode Bulan & Tanggal Kejadian -->
        <div>
          <x-label for="period_month" value="{{ __('Periode Bulan Pemotongan') }}" />
          <x-input id="period_month" type="month" class="mt-1 block w-full" wire:model="period_month" required />
          <p class="mt-1 text-[11px] text-gray-500">Bulan saat potongan dieksekusi pada payroll/syirkah.</p>
          <x-input-error for="period_month" class="mt-1" />
        </div>

        <div>
          <x-label for="error_date" value="{{ __('Tanggal Kejadian Error') }}" />
          <x-input id="error_date" type="date" class="mt-1 block w-full" wire:model="error_date" required />
          <x-input-error for="error_date" class="mt-1" />
        </div>

        <!-- 3. Judul / Jenis Kesalahan -->
        <div class="sm:col-span-2">
          <x-label for="error_title" value="{{ __('Judul / Jenis Kesalahan Produksi') }}" />
          <x-input id="error_title" type="text" class="mt-1 block w-full" wire:model="error_title" placeholder="Contoh: Rusak Bahan Flexi 2 Rol, Salah Cetak Kartu Nama, Finishing Robek" required />
          <x-input-error for="error_title" class="mt-1" />
        </div>

        <!-- 4. Keterangan Detail Kronologi / No SPK -->
        <div class="sm:col-span-2">
          <x-label for="description" value="{{ __('Kronologi Kejadian / Keterangan (No. SPK / Faktur)') }}" />
          <textarea id="description" rows="3" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-rose-500 focus:ring-rose-500 text-sm shadow-xs" placeholder="Tuliskan kronologi singkat, penyebab error, nomor SPK terkait, dan alasan tidak tercover..."></textarea>
          <x-input-error for="description" class="mt-1" />
        </div>

        <!-- 5. Total Biaya Kerugian & Nominal Potongan Karyawan -->
        <div>
          <x-label for="total_error_cost" value="{{ __('Estimasi Total Nilai Kerugian (Rp)') }}" />
          <x-input id="total_error_cost" type="number" class="mt-1 block w-full" wire:model="total_error_cost" placeholder="0" min="0" />
          <p class="mt-1 text-[11px] text-gray-500">Estimasi total kerugian riil perusahaan.</p>
          <x-input-error for="total_error_cost" class="mt-1" />
        </div>

        <div>
          <x-label for="amount" value="{{ __('Nominal Potongan Karyawan (Rp)') }}" />
          <x-input id="amount" type="number" class="mt-1 block w-full font-bold text-rose-600 dark:text-rose-400" wire:model="amount" placeholder="0" min="0" required />
          <p class="mt-1 text-[11px] text-gray-500">Nominal yang dipotongkan ke karyawan.</p>
          <x-input-error for="amount" class="mt-1" />
        </div>

        <!-- 6. Sumber Pemotongan -->
        <div class="sm:col-span-2">
          <x-label for="form_deduction_source" value="{{ __('Sumber Pemotongan') }}" />
          <x-select id="form_deduction_source" wire:model.live="form_deduction_source" class="mt-1 block w-full">
            <option value="payroll">Potong Langsung dari Gaji Bulanan (Payroll)</option>
            <option value="syirkah_mandatory">Potong dari Saldo Syirkah Wajib</option>
            <option value="syirkah_secondary">Potong dari Saldo Syirkah SSR (Sukarela)</option>
            <option value="syirkah_all">Potong dari Total Saldo Syirkah (SSR lalu Wajib)</option>
          </x-select>
          <x-input-error for="form_deduction_source" class="mt-1" />
        </div>

        @if(in_array($form_deduction_source, ['syirkah_mandatory', 'syirkah_secondary', 'syirkah_all']))
          <div class="sm:col-span-2 p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-900 dark:text-amber-200 mb-1">
              <x-heroicon-o-information-circle class="h-4 w-4 text-amber-600" />
              <span>Informasi Saldo Syirkah Karyawan Terpilih:</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs mt-2">
              <div class="bg-white dark:bg-gray-900 p-2 rounded-lg border border-amber-100 dark:border-amber-900/60">
                <span class="text-gray-500 dark:text-gray-400">Saldo Syirkah Wajib:</span>
                <div class="font-black text-amber-700 dark:text-amber-300">Rp {{ number_format($employee_syirkah_mandatory, 0, ',', '.') }}</div>
              </div>
              <div class="bg-white dark:bg-gray-900 p-2 rounded-lg border border-amber-100 dark:border-amber-900/60">
                <span class="text-gray-500 dark:text-gray-400">Saldo Syirkah Sukarela (SSR):</span>
                <div class="font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($employee_syirkah_secondary, 0, ',', '.') }}</div>
              </div>
            </div>
          </div>
        @endif

        <!-- 7. Status Awal -->
        <div class="sm:col-span-2">
          <x-label for="form_status" value="{{ __('Status Log Error') }}" />
          <x-select id="form_status" wire:model="form_status" class="mt-1 block w-full">
            <option value="pending">Pending (Menunggu Persetujuan)</option>
            <option value="approved">Approved (Disetujui & Siap Dipotong)</option>
            <option value="processed">Processed (Sudah Terpotong)</option>
            <option value="cancelled">Cancelled (Dibatalkan)</option>
          </x-select>
          <x-input-error for="form_status" class="mt-1" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$set('isModalOpen', false)" wire:loading.attr="disabled">
        {{ __('Batal') }}
      </x-secondary-button>

      <x-button class="ml-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 focus:ring-rose-500" wire:click="save" wire:loading.attr="disabled">
        {{ $editingId ? __('Simpan Perubahan') : __('Catat Log Error') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Konfirmasi Hapus -->
  <x-confirmation-modal wire:model.live="isDeleteModalOpen">
    <x-slot name="title">
      {{ __('Hapus Log Potongan Error') }}
    </x-slot>

    <x-slot name="content">
      {{ __('Apakah Anda yakin ingin menghapus data log potongan error ini? Jika potongan ini terhubung dengan transaksi mutasi syirkah, mutasi terkait juga akan ikut dibersihkan.') }}
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$set('isDeleteModalOpen', false)" wire:loading.attr="disabled">
        {{ __('Batal') }}
      </x-secondary-button>

      <x-danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
        {{ __('Hapus Permanen') }}
      </x-danger-button>
    </x-slot>
  </x-confirmation-modal>
</div>

<script>
  window.addEventListener('open-create-modal', event => {
    if (window.Livewire) {
      @this.openCreateModal();
    }
  });
</script>
