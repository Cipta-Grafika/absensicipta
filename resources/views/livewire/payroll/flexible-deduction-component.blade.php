<x-slot name="header">
  <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Potongan Gaji Fleksibel
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola pemotongan gaji kustom bulanan (Galang Dana, Infaq, Iuran, dll.) dengan master karyawan</p>
    </div>
    <div class="flex items-center gap-2">
      <x-button type="button" wire:click="openProgramModal" class="bg-sky-600 hover:bg-sky-700">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span>+ Buat Keperluan Baru</span>
      </x-button>
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="pt-3.5 pb-6 sm:py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="w-full sm:px-6 lg:px-8">

    <!-- Filter Sidebar -->
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Karyawan</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('division', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="status_filter_fd" value="Status Karyawan" class="mb-1"></x-label>
            <x-select id="status_filter_fd" class="w-full" wire:model.live="status">
              <option value="">Aktif & Bekerja (Default)</option>
              <option value="all">Semua Status (Termasuk Resign/Keluar)</option>
              <option value="active">Aktif</option>
              <option value="suspend">Suspend</option>
              <option value="resign">Resign</option>
              <option value="fired">Dikeluarkan</option>
            </x-select>
          </div>

          <div>
            <x-label for="division_filter" value="Divisi" class="mb-1"></x-label>
            <x-select id="division_filter" class="w-full" wire:model.live="division">
              <option value="">Semua Divisi</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </x-select>
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <!-- Main Container -->
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden p-4 sm:p-6 lg:p-8">

      <!-- Navigation Tabs -->
      <div class="mb-6 flex border-b border-gray-200 dark:border-gray-700">
        <button type="button" wire:click="$set('activeTab', 'master')" class="pb-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition-colors {{ $activeTab === 'master' ? 'border-sky-500 text-sky-600 dark:text-sky-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
          Master Potongan Bulanan
        </button>
        <button type="button" wire:click="$set('activeTab', 'history')" class="pb-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition-colors {{ $activeTab === 'history' ? 'border-sky-500 text-sky-600 dark:text-sky-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
          Riwayat & Rekapitulasi Program
        </button>
      </div>

      @if($activeTab === 'master')
        <!-- Period & Program Selector Controls -->
        <div class="mb-6 rounded-2xl bg-gradient-to-r from-sky-50/70 via-indigo-50/50 to-blue-50/70 dark:from-sky-950/40 dark:via-indigo-950/30 dark:to-blue-950/40 p-4 sm:p-5 border border-sky-100 dark:border-sky-900/50 shadow-xs">
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 items-end">
            
            <div class="lg:col-span-3">
              <x-label for="selected_period_month" value="Pilih Bulan Periode" class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-1" />
              <x-input id="selected_period_month" type="month" class="w-full text-sm font-semibold" wire:model.live="selected_period_month" />
            </div>

            <div class="lg:col-span-5">
              <x-label for="selected_program_id" value="Keperluan / Program Potongan" class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-1" />
              @if(count($programs) > 0)
                <x-select id="selected_program_id" class="w-full text-sm font-semibold" wire:model.live="selected_program_id">
                  @foreach($programs as $prog)
                    <option value="{{ $prog->id }}">{{ $prog->name }} ({{ $prog->period_month }})</option>
                  @endforeach
                </x-select>
              @else
                <div class="flex items-center justify-between rounded-lg border border-dashed border-amber-300 bg-amber-50/70 p-2.5 dark:border-amber-700/60 dark:bg-amber-950/40">
                  <span class="text-xs text-amber-800 dark:text-amber-300">Belum ada program untuk bulan ini.</span>
                  <button type="button" wire:click="openProgramModal" class="text-xs font-bold text-sky-600 hover:underline dark:text-sky-400">
                    + Buat Sekarang
                  </button>
                </div>
              @endif
            </div>

            <!-- Quick Batch Buttons -->
            <div class="lg:col-span-4 flex flex-wrap items-center justify-start lg:justify-end gap-2">
              @if($currentProgram)
                <x-secondary-button type="button" wire:click="openBatchModal" class="text-xs font-bold">
                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-1.5 h-3.5 w-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  Set Semua Rp
                </x-secondary-button>

                <x-danger-button type="button" wire:click="resetMonthDeductions" wire:confirm="Yakin ingin mereset seluruh nominal potongan untuk program bulan ini menjadi Rp 0?" class="text-xs">
                  Reset Rp 0
                </x-danger-button>
              @endif
            </div>
          </div>

          @if($currentProgram)
            <!-- Program Stats Strip -->
            <div class="mt-4 pt-4 border-t border-sky-200/60 dark:border-sky-800/40 flex flex-wrap items-center justify-between gap-4 text-xs">
              <div class="flex items-center gap-2">
                <span class="font-bold text-gray-900 dark:text-white">{{ $currentProgram->name }}</span>
                @if($currentProgram->description)
                  <span class="text-gray-500 dark:text-gray-400">&bull; {{ $currentProgram->description }}</span>
                @endif
              </div>

              <div class="flex items-center gap-4">
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Karyawan Berpartisipasi:</span>
                  <span class="font-bold text-sky-600 dark:text-sky-400 ml-1">{{ $totalParticipatingEmployees }} Karyawan</span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Total Terkumpul:</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm ml-1">Rp {{ number_format($totalCollected, 0, ',', '.') }}</span>
                </div>
              </div>
            </div>
          @endif
        </div>

        <!-- Search Bar -->
        <div class="mb-4">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-10 pr-10 text-sm" name="search_flex" id="search_flex" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Karyawan, NIP..." />
            @if ($search)
              <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            @endif
          </div>
        </div>

        <!-- Master Table -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
          <table class="w-full min-w-[950px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
            <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <tr>
                <th scope="col" class="px-4 py-3 min-w-[220px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
                <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Keperluan Potongan</th>
                <th scope="col" class="px-4 py-3 min-w-[140px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sumber Potongan</th>
                <th scope="col" class="px-4 py-3 min-w-[150px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nominal Potongan</th>
                <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Catatan</th>
                <th scope="col" class="px-4 py-3 min-w-[120px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
              @forelse ($employees as $emp)
                @php
                  $flexDeduction = $emp->flexibleDeductions->first();
                  $amount = $flexDeduction ? $flexDeduction->amount : 0;
                  $source = $flexDeduction ? $flexDeduction->deduction_source : 'payroll';
                @endphp
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-750 transition-colors">
                  <td class="whitespace-nowrap px-4 py-3.5">
                    <div class="flex items-center">
                      <div class="h-10 w-10 flex-shrink-0">
                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $emp->profile_photo_url }}" alt="{{ $emp->name }}">
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $emp->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $emp->nip }}</div>
                        <div class="text-xs font-medium text-sky-600 dark:text-sky-400">{{ $emp->division->name ?? '-' }} | {{ $emp->jobTitle->name ?? '-' }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-xs">
                    @if($currentProgram)
                      <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900/70 dark:text-blue-200">
                        {{ $currentProgram->name }}
                      </span>
                    @else
                      <span class="text-gray-400 italic">Pilih Program</span>
                    @endif
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-center text-xs">
                    @if($amount > 0)
                      @if($source === 'syirkah_mandatory')
                        <span class="inline-flex rounded-full bg-purple-100 dark:bg-purple-900/60 px-2 py-0.5 text-[11px] font-semibold text-purple-800 dark:text-purple-200">
                          Syirkah Wajib
                        </span>
                      @elseif($source === 'syirkah_secondary')
                        <span class="inline-flex rounded-full bg-indigo-100 dark:bg-indigo-900/60 px-2 py-0.5 text-[11px] font-semibold text-indigo-800 dark:text-indigo-200">
                          Syirkah SSR
                        </span>
                      @elseif($source === 'syirkah_all')
                        <span class="inline-flex rounded-full bg-violet-100 dark:bg-violet-900/60 px-2 py-0.5 text-[11px] font-semibold text-violet-800 dark:text-violet-200">
                          Wajib + SSR
                        </span>
                      @else
                        <span class="inline-flex rounded-full bg-sky-100 dark:bg-sky-900/60 px-2 py-0.5 text-[11px] font-semibold text-sky-800 dark:text-sky-200">
                          Potong Gaji
                        </span>
                      @endif
                    @else
                      <span class="text-gray-400">-</span>
                    @endif
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-right text-xs font-bold {{ $amount > 0 ? 'text-rose-600 dark:text-rose-400 text-sm' : 'text-gray-400' }}">
                    Rp {{ number_format($amount, 0, ',', '.') }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $flexDeduction?->notes ?: '-' }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-center text-xs font-medium">
                    <button type="button" wire:click="openDeductionModal('{{ $emp->id }}')" class="rounded-lg bg-sky-500 px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-sky-600 focus:outline-none transition">
                      {{ $amount > 0 ? 'Edit' : 'Set Potongan' }}
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada data karyawan yang ditemukan.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          {{ $employees->links() }}
        </div>

      @else
        <!-- History Tab -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
          <table class="w-full min-w-[850px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
            <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <tr>
                <th scope="col" class="px-4 py-3 min-w-[120px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Periode Bulan</th>
                <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Keperluan / Program</th>
                <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Keterangan</th>
                <th scope="col" class="px-4 py-3 min-w-[130px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Karyawan</th>
                <th scope="col" class="px-4 py-3 min-w-[150px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Terkumpul</th>
                <th scope="col" class="px-4 py-3 min-w-[100px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
              @forelse ($allProgramsHistory as $prog)
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-750 transition-colors">
                  <td class="whitespace-nowrap px-4 py-3.5 text-xs font-bold text-gray-900 dark:text-gray-100">
                    {{ $prog->period_month }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-xs font-bold text-sky-600 dark:text-sky-400">
                    {{ $prog->name }}
                  </td>
                  <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 max-w-[220px] truncate">
                    {{ $prog->description ?: '-' }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-center text-xs font-semibold text-gray-800 dark:text-gray-200">
                    {{ $prog->total_participants }} Orang
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-right text-xs font-bold text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($prog->total_nominal ?: 0, 0, ',', '.') }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-3.5 text-center text-xs">
                    <button type="button" wire:click="deleteProgram('{{ $prog->id }}')" wire:confirm="Hapus program keperluan ini beserta seluruh rincian potongannya?" class="p-1.5 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-950 dark:text-rose-300 transition-colors">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum ada riwayat program potongan fleksibel.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          {{ $allProgramsHistory->links() }}
        </div>
      @endif

    </div>
  </div>

  <!-- Modal Buat Program Baru -->
  <x-dialog-modal wire:model.live="isProgramModalOpen">
    <x-slot name="title">
      Buat Keperluan / Program Potongan Baru
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-4">
        <div>
          <x-label for="program_name" value="Nama Keperluan / Program *" />
          <x-input id="program_name" type="text" class="mt-1 block w-full text-sm" wire:model="program_name" placeholder="Misal: Galang Dana Peduli Bencana, Infaq Ramadhan, Iuran Koperasi" />
          <x-input-error for="program_name" class="mt-1" />
        </div>

        <div>
          <x-label for="program_period_month" value="Bulan Periode (YYYY-MM) *" />
          <x-input id="program_period_month" type="month" class="mt-1 block w-full text-sm" wire:model="program_period_month" />
          <x-input-error for="program_period_month" class="mt-1" />
        </div>

        <div>
          <x-label for="program_description" value="Keterangan / Tujuan (Opsional)" />
          <x-input id="program_description" type="text" class="mt-1 block w-full text-sm" wire:model="program_description" placeholder="Keterangan atau rujukan penggalangan dana" />
          <x-input-error for="program_description" class="mt-1" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeProgramModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-sky-600 hover:bg-sky-700" wire:click="storeProgram" wire:loading.attr="disabled">
        Simpan Program
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Set Potongan Karyawan -->
  <x-dialog-modal wire:model.live="isDeductionModalOpen">
    <x-slot name="title">
      Atur Potongan Karyawan ({{ $currentProgram?->name }})
    </x-slot>

    <x-slot name="content">
      @if ($selectedEmployee)
        <div class="mb-4 flex items-center gap-3 rounded-xl bg-sky-50 p-3 border border-sky-200 dark:bg-sky-950/50 dark:border-sky-800">
          <img class="h-10 w-10 rounded-full object-cover" src="{{ $selectedEmployee->profile_photo_url }}" alt="{{ $selectedEmployee->name }}">
          <div>
            <div class="text-xs font-bold text-gray-900 dark:text-white">{{ $selectedEmployee->name }}</div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400">
              NIP: {{ $selectedEmployee->nip }} &bull; {{ $selectedEmployee->division->name ?? '-' }}
            </div>
          </div>
        </div>

        <!-- Syirkah Balance Preview -->
        <div class="mb-4 grid grid-cols-2 gap-3 p-3 bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 rounded-xl text-xs">
          <div>
            <span class="text-gray-500 dark:text-gray-400 block">Saldo Syirkah Wajib:</span>
            <span class="font-bold text-indigo-950 dark:text-indigo-200 text-sm">Rp {{ number_format($employee_syirkah_mandatory, 0, ',', '.') }}</span>
          </div>
          <div>
            <span class="text-gray-500 dark:text-gray-400 block">Saldo Syirkah SSR:</span>
            <span class="font-bold text-indigo-950 dark:text-indigo-200 text-sm">Rp {{ number_format($employee_syirkah_secondary, 0, ',', '.') }}</span>
          </div>
        </div>
      @endif

      <div class="grid grid-cols-1 gap-4">
        <div>
          <x-label for="deduction_source" value="Sumber Pemotongan *" />
          <x-select id="deduction_source" class="mt-1 block w-full text-sm" wire:model.live="deduction_source">
            <option value="payroll">Potong Gaji di Payroll Bulanan</option>
            <option value="syirkah_mandatory">Potong Saldo Syirkah Wajib</option>
            <option value="syirkah_secondary">Potong Saldo Syirkah Sukarela (SSR)</option>
            <option value="syirkah_all">Potong Saldo Syirkah (Wajib + SSR)</option>
          </x-select>
          <x-input-error for="deduction_source" class="mt-1" />
        </div>

        <div>
          <x-label for="deduction_amount" value="Nominal Potongan Bulan Ini (Rp) *" />
          <x-input id="deduction_amount" type="number" class="mt-1 block w-full text-sm" wire:model="deduction_amount" placeholder="Contoh: 25000" />
          <p class="text-[11px] text-gray-500 mt-1">Nominal dapat disetel bebas dan berbeda-beda untuk tiap karyawan.</p>
          <x-input-error for="deduction_amount" class="mt-1" />
        </div>

        <div>
          <x-label for="deduction_notes" value="Catatan Tambahan (Opsional)" />
          <x-input id="deduction_notes" type="text" class="mt-1 block w-full text-sm" wire:model="deduction_notes" placeholder="Contoh: Donasi sukarela" />
          <x-input-error for="deduction_notes" class="mt-1" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeDeductionModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-sky-600 hover:bg-sky-700" wire:click="saveDeduction" wire:loading.attr="disabled">
        Simpan Potongan
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Set Semua Karyawan (Batch) -->
  <x-dialog-modal wire:model.live="isBatchModalOpen">
    <x-slot name="title">
      Set Potongan Massal untuk Semua Karyawan
    </x-slot>

    <x-slot name="content">
      <div class="text-xs text-gray-600 dark:text-gray-400 mb-4">
        Tindakan ini akan menyetel nominal potongan pada program <strong class="text-gray-900 dark:text-white">{{ $currentProgram?->name }}</strong> untuk seluruh karyawan yang aktif.
      </div>

      <div class="grid grid-cols-1 gap-4">
        <div>
          <x-label for="batch_source" value="Sumber Pemotongan Massal *" />
          <x-select id="batch_source" class="mt-1 block w-full text-sm" wire:model="batch_source">
            <option value="payroll">Potong Gaji di Payroll Bulanan</option>
            <option value="syirkah_mandatory">Potong Saldo Syirkah Wajib</option>
            <option value="syirkah_secondary">Potong Saldo Syirkah Sukarela (SSR)</option>
            <option value="syirkah_all">Potong Saldo Syirkah (Wajib + SSR)</option>
          </x-select>
          <x-input-error for="batch_source" class="mt-1" />
        </div>

        <div>
          <x-label for="batch_amount" value="Nominal Potongan per Karyawan (Rp) *" />
          <x-input id="batch_amount" type="number" class="mt-1 block w-full text-sm" wire:model="batch_amount" placeholder="Contoh: 20000" />
          <x-input-error for="batch_amount" class="mt-1" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeBatchModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-sky-600 hover:bg-sky-700" wire:click="applyBatchAmount" wire:loading.attr="disabled">
        Terapkan ke Semua Karyawan
      </x-button>
    </x-slot>
  </x-dialog-modal>

</div>
