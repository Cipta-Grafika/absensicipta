<div>
  <x-slot name="header">
    <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <a href="{{ route('payroll.history') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
          </a>
          <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
            Export Transfer Bank (BCA MAT)
          </h2>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Export data penggajian ke format resmi Multi Auto Transfer (MAT) / Multi Payroll Bank BCA.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <a href="{{ asset('excel/template_mat_bca.xlsx') }}" download class="inline-flex items-center px-3.5 py-2 border border-gray-300 dark:border-gray-600 rounded-xl font-medium text-xs text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4 text-emerald-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
          </svg>
          Unduh Format Template MAT
        </a>

        <x-button type="button" x-data @click.prevent="Livewire.dispatch('trigger-export-bank')" class="!bg-sky-600 hover:!bg-sky-700 active:!bg-sky-800 focus:!ring-sky-500 shadow-md shadow-sky-500/20">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
          </svg>
          <span>Export Excel XLSX</span>
        </x-button>
      </div>
    </div>
  </x-slot>

  <div class="py-6 space-y-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

      <!-- Summary Metric Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Selected Count -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/60 rounded-2xl p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Karyawan Terpilih</span>
            <div class="p-2 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $selectedCount }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">dari {{ $totalEmployees }} total</span>
          </div>
        </div>

        <!-- Total Transfer Amount -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/60 rounded-2xl p-5 shadow-sm sm:col-span-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Dana Transfer (Net Amount)</span>
            <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalTransferAmount, 2, ',', '.') }}</span>
          </div>
        </div>

        <!-- Missing Account Warning -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/60 rounded-2xl p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanpa No. Rekening</span>
            <div class="p-2 rounded-xl {{ $missingAccountCount > 0 ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400' : 'bg-gray-50 dark:bg-gray-700/40 text-gray-400' }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-bold {{ $missingAccountCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $missingAccountCount }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">karyawan</span>
          </div>
        </div>
      </div>

      <!-- Export Configuration Parameters Card -->
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Pengaturan Parameter Export BCA MAT
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Periode Bulan -->
          <div>
            <x-label for="month" value="Periode Payroll" class="mb-1" />
            <x-input type="month" id="month" class="w-full" wire:model.live="month" />
          </div>

          <!-- Opsi Bank -->
          <div>
            <x-label for="bank_type" value="Opsi Bank Transfer" class="mb-1" />
            <x-select id="bank_type" class="w-full" wire:model.live="bank_type">
              <option value="BCA">BCA (Multi Auto Transfer / MAT)</option>
              <option value="LLG">LLG (Transfer Bank Lain)</option>
              <option value="RTG">RTGS (Transfer Bank Lain)</option>
            </x-select>
          </div>

          <!-- Tanggal Transaksi (Execution Date) -->
          <div>
            <x-label for="transaction_date" value="Tanggal Eksekusi Transfer" class="mb-1" />
            <x-input type="date" id="transaction_date" class="w-full" wire:model.live="transaction_date" />
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Prefix ID: <strong class="text-sky-600 dark:text-sky-400">{{ $formattedDatePrefix }}-001</strong></p>
          </div>

          <!-- Remark / Keterangan Transfer -->
          <div>
            <x-label for="remark" value="Keterangan Mutasi (Max 18 Karakter)" class="mb-1" />
            <x-input type="text" id="remark" maxlength="18" placeholder="Contoh: Gaji Ags 2026" class="w-full" wire:model.live="remark" />
            <div class="flex justify-between items-center mt-1">
              <span class="text-[11px] text-gray-400">Tampil di mutasi rekening</span>
              <span class="text-[11px] text-gray-400">{{ strlen($remark) }}/18</span>
            </div>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
          <div>
            <x-label for="division_id" value="Filter Divisi" class="mb-1" />
            <x-select id="division_id" class="w-full text-sm" wire:model.live="division_id">
              <option value="">Semua Divisi</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </x-select>
          </div>

          <div>
            <x-label for="status_filter" value="Filter Status Payroll" class="mb-1" />
            <x-select id="status_filter" class="w-full text-sm" wire:model.live="status_filter">
              <option value="">Semua Status (Draft & Paid)</option>
              <option value="draft">Draft Saja</option>
              <option value="paid">Paid Saja</option>
            </x-select>
          </div>

          <div class="sm:pt-5 flex items-center">
            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" wire:model.live="only_with_account" class="rounded border-gray-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-sky-600">
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-medium">Hanya karyawan dengan No. Rekening BCA</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Preview Table Card -->
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <span>Preview Data Baris Export (Urutan Karyawan Terlama ke Terbaru)</span>
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              Urutan row data konsisten dari karyawan terlama bergabung sampai terbaru dengan nomor Transaction ID berurutan.
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button type="button" wire:click="$toggle('select_all')" class="text-xs font-semibold text-sky-600 dark:text-sky-400 hover:underline">
              {{ $select_all ? 'Batal Pilih Semua' : 'Pilih Semua' }}
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/60">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
              <tr>
                <th scope="col" class="w-12 px-4 py-3.5 text-center">
                  <input type="checkbox" wire:model.live="select_all" class="rounded border-gray-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-sky-600">
                </th>
                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No</th>
                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transaction ID</th>
                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transfer Type</th>
                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Credited Account</th>
                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Receiver Name</th>
                <th scope="col" class="px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">NIP</th>
                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remark</th>
                <th scope="col" class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Rekening</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-transparent text-sm">
              @php $seq = 1; @endphp
              @forelse ($payrolls as $p)
                @php
                  $hasAccount = !empty($p->employee?->paymentMethod?->bank_account);
                  $txId = sprintf('%s-%03d', $formattedDatePrefix, $seq);
                  $receiverName = strtoupper($p->employee?->paymentMethod?->account_name ?: ($p->employee?->name ?? ''));
                @endphp
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors {{ !$hasAccount ? 'bg-amber-50/30 dark:bg-amber-950/10' : '' }}">
                  <td class="px-4 py-3 text-center">
                    <input type="checkbox" value="{{ $p->id }}" wire:model.live="selected_payrolls" class="rounded border-gray-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-sky-600">
                  </td>
                  <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $seq }}</td>
                  <td class="px-3 py-3 text-xs font-mono font-bold text-sky-600 dark:text-sky-400 whitespace-nowrap">{{ $txId }}</td>
                  <td class="px-3 py-3 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $bank_type }}</td>
                  <td class="px-3 py-3 text-xs font-mono font-medium {{ $hasAccount ? 'text-gray-900 dark:text-white' : 'text-amber-500 italic' }}">
                    {{ $hasAccount ? $p->employee->paymentMethod->bank_account : 'Belum Diatur' }}
                  </td>
                  <td class="px-3 py-3 text-xs font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                    {{ $receiverName }}
                    <span class="block text-[11px] font-normal text-gray-400">{{ $p->employee?->division?->name ?? '-' }} &bull; {{ $p->employee?->jobTitle?->name ?? '-' }}</span>
                  </td>
                  <td class="px-3 py-3 text-xs text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                    Rp {{ number_format($p->net_salary, 2, ',', '.') }}
                  </td>
                  <td class="px-3 py-3 text-xs font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $p->employee?->nip ?? '-' }}</td>
                  <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $remark ?: '-' }}</td>
                  <td class="px-3 py-3 text-center whitespace-nowrap">
                    @if ($hasAccount)
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        <svg class="mr-1 h-2 w-2 text-emerald-500 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                        Siap Transfer
                      </span>
                    @else
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                        <svg class="mr-1 h-2 w-2 text-amber-500 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                        Rekening Kosong
                      </span>
                    @endif
                  </td>
                </tr>
                @php $seq++; @endphp
              @empty
                <tr>
                  <td colspan="10" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="max-w-xs mx-auto text-center space-y-2">
                      <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      <p class="font-semibold text-gray-900 dark:text-white">Tidak Ada Data Payroll</p>
                      <p class="text-xs">Tidak ditemukan payroll aktif pada periode <strong>{{ $month }}</strong> dengan filter yang dipilih.</p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if ($payrolls->isNotEmpty())
          <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">
              Menampilkan <strong>{{ $payrolls->count() }}</strong> baris payroll siap ekspor.
            </div>
            <div>
              <x-button type="button" wire:click="export" wire:loading.attr="disabled" class="!bg-sky-600 hover:!bg-sky-700 active:!bg-sky-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                <span>Unduh File Excel BCA MAT ({{ $selectedCount }} Karyawan)</span>
              </x-button>
            </div>
          </div>
        @endif
      </div>

    </div>
  </div>
</div>
