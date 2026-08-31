<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Riwayat Gaji
    </h2>
    <div class="flex items-center gap-2">
      <x-button type="button" class="!bg-emerald-600 hover:!bg-emerald-700 active:!bg-emerald-800 focus:!bg-emerald-700 focus:!ring-emerald-500" x-data @click.prevent="if(confirm('Anda yakin ingin mengubah semua status Draft bulan ini menjadi Paid?')) { Livewire.dispatch('mark-all-as-paid') }">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
        <span class="hidden sm:inline">Mark All as Paid</span>
      </x-button>

      <x-button type="button" x-data @click.prevent="Livewire.dispatch('open-generate-modal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="hidden sm:inline">Proses</span>
      </x-button>
      <x-button type="button" class="!bg-sky-600 hover:!bg-sky-700 active:!bg-sky-800 focus:!ring-sky-500 shadow-md shadow-sky-500/20" x-data @click.prevent="Livewire.dispatch('open-export-bank-modal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        <span class="hidden sm:inline">Export Transfer Bank</span>
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

    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Payroll</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="resetFilters" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="month_filter" value="Pilih Bulan Periode" class="mb-1"></x-label>
            <x-input type="month" id="month_filter" class="w-full block" wire:model.live="month" />
          </div>

          <div>
            <x-label for="division_filter" value="Divisi" class="mb-1"></x-label>
            <x-select id="division_filter" class="w-full" wire:model.live="division">
              <option value="">Semua Divisi</option>
              @foreach (\App\Models\Division::all() as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </x-select>
          </div>

          <div>
            <x-label for="status_filter" value="Status" class="mb-1"></x-label>
            <x-select id="status_filter" class="w-full" wire:model.live="status">
              <option value="">Semua Status</option>
              <option value="draft">Draft</option>
              <option value="paid">Paid</option>
            </x-select>
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden p-4 sm:p-6 lg:p-8">
      
      <div class="mb-4">
        <div class="flex w-full flex-1 items-center gap-2">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-10 pr-10" name="search_employee" id="search_employee" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Karyawan..." />
            @if ($search)
              <button type="button" wire:click="clearSearch" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            @endif
          </div>
        </div>
      </div>

      <!-- BULK ACTION BAR -->
      @if(!empty($selectedPayrolls))
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800 p-3.5 shadow-sm text-xs sm:text-sm">
          <div class="flex items-center gap-2.5 font-semibold text-sky-900 dark:text-sky-200">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-500 text-white shadow-2xs">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span><strong>{{ count($selectedPayrolls) }}</strong> data gaji dipilih</span>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button type="button" 
                    wire:click="bulkMarkAsPaid" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition-all cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Mark as Paid ({{ count($selectedPayrolls) }})
            </button>
            <button type="button" 
                    wire:click="openBulkDeleteModal" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition-all cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
              </svg>
              Hapus Bulk ({{ count($selectedPayrolls) }})
            </button>
            <button type="button" 
                    wire:click="resetSelection" 
                    class="inline-flex items-center gap-1 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 transition-all cursor-pointer">
              Batal
            </button>
          </div>
        </div>
      @endif

      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[1000px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
          <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <tr>
              <th scope="col" class="w-10 px-3 py-3 text-center">
                <input type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-800 cursor-pointer" title="Pilih Semua di Halaman Ini">
              </th>
              <th scope="col" class="min-w-[15rem] whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Periode & Karyawan</th>
              @foreach (['H', 'A', 'T', 'L', 'IMP', 'S', 'I', 'C', 'W'] as $_st)
                <th scope="col" class="w-12 min-w-[3rem] border border-gray-300 p-0 text-center text-xs font-bold text-gray-500 dark:border-gray-600 dark:text-gray-300" title="{{ $_st }}">
                  <div class="flex h-12 w-12 items-center justify-center">{{ $_st }}</div>
                </th>
              @endforeach
              <th scope="col" class="whitespace-nowrap px-4 py-3 min-w-[150px] text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pemasukan</th>
              <th scope="col" class="whitespace-nowrap px-4 py-3 min-w-[150px] text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Potongan</th>
              <th scope="col" class="whitespace-nowrap px-4 py-3 min-w-[150px] text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Net Salary</th>
              <th scope="col" class="whitespace-nowrap px-4 py-3 min-w-[140px] text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status & Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($payrolls as $pr)
              <tr class="{{ in_array($pr->id, $selectedPayrolls) ? 'bg-sky-50/70 dark:bg-sky-950/40' : '' }} hover:bg-gray-50/80 dark:hover:bg-gray-750 transition-colors">
                <td class="w-10 px-3 py-4 text-center">
                  <input type="checkbox" value="{{ $pr->id }}" wire:model.live="selectedPayrolls" class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-800 cursor-pointer">
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($pr->period_month)->format('F Y') }}</div>
                  <div class="mt-1 flex items-center">
                    <img class="hidden h-8 w-8 rounded-full object-cover mr-3 lg:block" src="{{ $pr->employee->profile_photo_url }}" alt="">
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-gray-200 whitespace-nowrap" title="{{ $pr->employee->name }}">
                        <span class="lg:hidden">{{ \Illuminate\Support\Str::limit($pr->employee->name, 15, '...') }}</span>
                        <span class="hidden lg:block">{{ $pr->employee->name }}</span>
                      </div>
                      <div class="text-xs text-gray-500">{{ $pr->employee->nip }}</div>
                    </div>
                  </div>
                </td>
                
                {{-- H (Hadir) --}}
                <td class="w-12 min-w-[3rem] border border-green-300 bg-green-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-green-600 dark:bg-green-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1" title="Hadir">
                    <span>{{ $pr->total_present }}</span>
                  </div>
                </td>
                
                {{-- A (Absen) --}}
                <td class="w-12 min-w-[3rem] border border-red-300 bg-red-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-red-600 dark:bg-red-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1" title="Absen">
                    <span>{{ $pr->total_absent }}</span>
                  </div>
                </td>

                {{-- T (Telat) --}}
                <td class="w-12 min-w-[3rem] border border-orange-300 bg-orange-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-orange-600 dark:bg-orange-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1" title="Telat">
                    <span>{{ $pr->total_late_minutes }}m</span>
                  </div>
                </td>

                {{-- L (Lembur) --}}
                <td class="w-12 min-w-[3rem] border border-sky-300 bg-sky-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-sky-600 dark:bg-sky-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1" title="Lembur">
                    <span>{{ $pr->total_overtime_hours }}j</span>
                  </div>
                </td>

                {{-- IMP --}}
                <td class="w-12 min-w-[3rem] border border-amber-300 bg-amber-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-amber-600 dark:bg-amber-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1 leading-tight" title="IMP (Tidak diganti)">
                    <span>{{ $pr->total_unreplaced_imp_hours * 60 }}m</span>
                  </div>
                </td>

                {{-- S (Sakit) --}}
                <td class="w-12 min-w-[3rem] border border-yellow-300 bg-yellow-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-yellow-600 dark:bg-yellow-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1" title="Sakit">
                    <span>{{ $pr->total_sick }}</span>
                  </div>
                </td>

                {{-- I (Izin) --}}
                <td class="w-12 min-w-[3rem] border border-blue-300 bg-blue-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-blue-600 dark:bg-blue-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1" title="Izin">
                    <span>{{ $pr->total_excused }}</span>
                  </div>
                </td>

                {{-- C (Cuti) --}}
                <td class="w-12 min-w-[3rem] border border-teal-300 bg-teal-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-teal-600 dark:bg-teal-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1" title="Cuti (Penalti)">
                    <span>{{ $pr->penalized_cuti_days }}</span>
                  </div>
                </td>

                {{-- W (WFH) --}}
                <td class="w-12 min-w-[3rem] border border-purple-300 bg-purple-200 p-0 text-center text-xs font-medium text-gray-900 dark:border-purple-600 dark:bg-purple-800 dark:text-white">
                  <div class="flex h-full w-full min-h-[3rem] flex-col items-center justify-center p-1" title="WFH">
                    <span>{{ $pr->total_wfh }}</span>
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-green-600 dark:text-green-400">
                  <button wire:click="showIncomes('{{ $pr->id }}')" class="hover:underline focus:outline-none" title="Klik untuk melihat detail pemasukan">
                    Rp {{ number_format($pr->basic_salary_earned + $pr->total_allowance + $pr->total_overtime_pay, 0, ',', '.') }}
                  </button>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-red-600 dark:text-red-400">
                  <button wire:click="showDeductions('{{ $pr->id }}')" class="hover:underline focus:outline-none" title="Klik untuk melihat detail potongan">
                    Rp {{ number_format($pr->total_deduction, 0, ',', '.') }}
                  </button>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-gray-900 dark:text-gray-100">
                  Rp {{ number_format($pr->net_salary, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                  @if($pr->status == 'draft')
                    <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Draft</span>
                    <div class="mt-2 flex flex-col items-center space-y-1.5">
                        <a href="{{ route('payroll.payslip.print', $pr->id) }}" target="_blank" class="inline-flex items-center text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:underline" title="Download Slip Gaji (PDF Terenkripsi)">
                          <svg class="w-3.5 h-3.5 mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                          </svg>
                          Cetak Slip
                        </a>
                        <button wire:click="markAsPaid('{{ $pr->id }}')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Mark as Paid</button>
                        <button wire:click="confirmDelete('{{ $pr->id }}')" class="text-xs text-red-600 dark:text-red-400 hover:underline">Hapus</button>
                    </div>
                  @else
                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
                    <div class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($pr->payment_date)->format('d/m/Y') }}</div>
                    <div class="mt-1.5 flex flex-col items-center space-y-1.5">
                        <a href="{{ route('payroll.payslip.print', $pr->id) }}" target="_blank" class="inline-flex items-center text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:underline" title="Download Slip Gaji (PDF Terenkripsi)">
                          <svg class="w-3.5 h-3.5 mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                          </svg>
                          Cetak Slip
                        </a>
                        <button wire:click="confirmDelete('{{ $pr->id }}')" class="text-xs text-red-600 dark:text-red-400 hover:underline">Hapus</button>
                    </div>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="15" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada data penggajian.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $payrolls->links() }}
      </div>

    </div>
  </div>

  <!-- Modal Buat Gaji Baru -->
  <x-dialog-modal wire:model.live="isGenerateModalOpen" maxWidth="3xl">
    <x-slot name="title">
      {{ __('Buat Gaji Baru') }}
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="generatePayroll" class="space-y-4">
        <div>
          <x-label for="generate_period_month" value="Bulan Periode (YYYY-MM)" />
          <x-input id="generate_period_month" type="month" class="mt-1 block w-full" wire:model.live="generate_period_month" required />
          <x-input-error for="generate_period_month" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <x-label for="generate_start_date" value="Tanggal Mulai" />
            <x-input id="generate_start_date" type="date" class="mt-1 block w-full" wire:model="generate_start_date" required />
            <x-input-error for="generate_start_date" class="mt-2" />
          </div>

          <div>
            <x-label for="generate_end_date" value="Tanggal Selesai" />
            <x-input id="generate_end_date" type="date" class="mt-1 block w-full" wire:model="generate_end_date" required />
            <x-input-error for="generate_end_date" class="mt-2" />
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <x-label value="Target Karyawan Penggajian" class="font-semibold text-sm" />
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="relative flex items-start p-3.5 rounded-2xl border cursor-pointer transition-all {{ $generate_target === 'all' ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 ring-2 ring-sky-500/80 shadow-sm' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750' }}">
              <input type="radio" value="all" wire:model.live="generate_target" class="mt-0.5 h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 dark:border-gray-600">
              <div class="ml-3">
                <span class="block text-xs font-bold text-gray-900 dark:text-gray-100">Semua Karyawan</span>
                <span class="block text-2xs text-gray-500 dark:text-gray-400 mt-0.5">Generate untuk seluruh karyawan aktif yang memiliki master gaji</span>
              </div>
            </label>

            <label class="relative flex items-start p-3.5 rounded-2xl border cursor-pointer transition-all {{ $generate_target === 'specific' ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 ring-2 ring-sky-500/80 shadow-sm' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750' }}">
              <input type="radio" value="specific" wire:model.live="generate_target" class="mt-0.5 h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 dark:border-gray-600">
              <div class="ml-3">
                <span class="block text-xs font-bold text-gray-900 dark:text-gray-100">Pilih Karyawan Spesifik</span>
                <span class="block text-2xs text-gray-500 dark:text-gray-400 mt-0.5">Hanya proses karyawan tertentu tanpa mengubah gaji karyawan lain</span>
              </div>
            </label>
          </div>

          @if($generate_target === 'specific')
            <div x-data="{ empSearch: '' }" class="space-y-3 pt-1">
              
              <!-- Search & Quick Actions Stacked Layout -->
              <div class="space-y-2.5">
                <!-- Full-width Search Field with centered icon -->
                <div class="relative">
                  <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </div>
                  <input type="text" x-model="empSearch" placeholder="Cari nama karyawan / NIP..."
                    class="block w-full rounded-xl border border-gray-300 bg-gray-50/70 py-2.5 pl-9 pr-3 text-xs font-medium text-gray-900 focus:border-sky-500 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 transition">
                </div>

                <!-- Action Bar: Select All on Left, Summary on Right -->
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-gray-100/90 px-3.5 py-2 border border-gray-200 dark:bg-gray-800/80 dark:border-gray-700 shadow-2xs">
                  <label class="inline-flex items-center text-xs font-semibold text-gray-700 dark:text-gray-200 cursor-pointer select-none">
                    <input type="checkbox"
                      wire:click="toggleAllEmployees"
                      @checked(count($selected_employee_ids) === $availableEmployees->count() && $availableEmployees->count() > 0)
                      class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-800 cursor-pointer">
                    <span class="ml-2.5 font-bold">Pilih Semua Karyawan</span>
                  </label>

                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-sky-600 dark:text-sky-400">
                      {{ count($selected_employee_ids) }} Karyawan Dipilih
                    </span>
                  </div>
                </div>
              </div>

              <!-- Scrollable Employee Table Container -->
              <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-inner">
                <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-gray-800/90 text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                      <th class="p-3 w-10 text-center">#</th>
                      <th class="p-3">Nama Karyawan</th>
                      <th class="p-3 w-40">Divisi & Jabatan</th>
                      <th class="p-3 w-44 text-right">Master Gaji Pokok</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($availableEmployees as $u)
                      <tr x-show="!empSearch || '{{ strtolower(addslashes($u->name . ' ' . ($u->nip ?? ''))) }}'.includes(empSearch.toLowerCase())"
                          class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors {{ in_array($u->id, $selected_employee_ids) ? 'bg-sky-50/60 dark:bg-sky-950/30' : '' }}">
                        <td class="p-3 text-center">
                          <input type="checkbox"
                            value="{{ $u->id }}"
                            wire:model.live="selected_employee_ids"
                            class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-800 cursor-pointer">
                        </td>
                        <td class="p-3">
                          <div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <img class="h-6 w-6 rounded-full object-cover shrink-0" src="{{ $u->profile_photo_url }}" alt="">
                            <span>{{ $u->name }}</span>
                          </div>
                          <div class="text-[11px] text-gray-400 ml-8">{{ $u->nip ?? '-' }}</div>
                        </td>
                        <td class="p-3">
                          <div class="font-bold text-gray-900 dark:text-gray-100">{{ $u->division->name ?? 'Tanpa Divisi' }}</div>
                          <div class="text-[11px] text-gray-400">{{ $u->jobTitle->name ?? '-' }}</div>
                        </td>
                        <td class="p-3 text-right">
                          @if($u->salary)
                            <div class="font-bold text-emerald-600 dark:text-emerald-400">
                              Rp {{ number_format($u->salary->basic_salary, 0, ',', '.') }}
                            </div>
                            <div class="text-[10px] text-gray-400 capitalize">{{ $u->type }}</div>
                          @else
                            <span class="inline-flex rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 px-2 py-0.5 text-[10px] font-semibold">Belum Diatur</span>
                          @endif
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="4" class="p-6 text-center text-xs text-gray-500">
                          Tidak ada data karyawan aktif dengan master gaji.
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              <x-input-error for="selected_employee_ids" class="mt-1" />
            </div>
          @endif
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeGenerateModal" wire:loading.attr="disabled">
        {{ __('Batal') }}
      </x-secondary-button>

      <x-button class="ms-3 bg-blue-600 hover:bg-blue-700" wire:click="generatePayroll" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="generatePayroll">
          @if($generate_target === 'specific')
            Generate ({{ count($selected_employee_ids) }} Karyawan)
          @else
            Generate Semua Payroll
          @endif
        </span>
        <span wire:loading wire:target="generatePayroll">Memproses...</span>
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Konfirmasi Hapus Data Gaji -->
  <x-confirmation-modal wire:model.live="isDeleteModalOpen">
    <x-slot name="title">
      Konfirmasi Hapus Data Gaji
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus data gaji ini secara permanen? Tindakan ini tidak dapat dibatalkan.
    </x-slot>

    <x-slot name="footer">
      <x-danger-button wire:click="deletePayroll" wire:loading.attr="disabled">
        Ya, Hapus
      </x-danger-button>

      <x-secondary-button wire:click="cancelDelete" wire:loading.attr="disabled" class="ms-3">
        Batal
      </x-secondary-button>
    </x-slot>
  </x-confirmation-modal>

  <!-- Modal Konfirmasi Hapus Bulk Gaji -->
  <x-confirmation-modal wire:model.live="isBulkDeleteModalOpen">
    <x-slot name="title">
      Konfirmasi Hapus Bulk Data Gaji
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus permanen <strong>{{ count($selectedPayrolls) }}</strong> data gaji yang dipilih? Seluruh rincian pemasukan, potongan, dan riwayat mutasi terkait akan dihapus/direset. Tindakan ini tidak dapat dibatalkan.
    </x-slot>

    <x-slot name="footer">
      <x-danger-button wire:click="bulkDeletePayrolls" wire:loading.attr="disabled">
        Ya, Hapus ({{ count($selectedPayrolls) }}) Terpilih
      </x-danger-button>

      <x-secondary-button wire:click="cancelBulkDelete" wire:loading.attr="disabled" class="ms-3">
        Batal
      </x-secondary-button>
    </x-slot>
  </x-confirmation-modal>

  <!-- Modal Detail Potongan -->
  <x-dialog-modal wire:model.live="showDeductionModal" maxWidth="lg">
    <x-slot name="title">
      Detail Potongan - {{ $selectedPayrollEmployeeName }}
    </x-slot>

    <x-slot name="content">
      @if(count($selectedDeductions) > 0)
        <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
          <table class="w-full divide-y divide-gray-300 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="col" class="w-3/5 py-3 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-300 sm:pl-6">Jenis Potongan</th>
                <th scope="col" class="w-2/5 whitespace-nowrap px-3 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-300">Nominal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
              @php $totalDeduction = 0; @endphp
              @foreach($selectedDeductions as $deduction)
                @php $totalDeduction += $deduction['amount']; @endphp
                <tr>
                  <td class="w-3/5 whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6">
                    {{ $deduction['name'] }}
                  </td>
                  <td class="w-2/5 whitespace-nowrap px-3 py-4 text-sm text-right text-red-600 dark:text-red-400">
                    Rp {{ number_format($deduction['amount'], 0, ',', '.') }}
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="row" class="w-3/5 py-3 pl-4 pr-3 text-left text-sm font-bold text-gray-900 dark:text-white sm:pl-6">Total Potongan</th>
                <td class="w-2/5 whitespace-nowrap px-3 py-3 text-right text-sm font-bold text-red-600 dark:text-red-400">Rp {{ number_format($totalDeduction, 0, ',', '.') }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada detail potongan untuk payroll ini.</p>
      @endif
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeDeductionModal" wire:loading.attr="disabled">
        {{ __('Tutup') }}
      </x-secondary-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Detail Pemasukan -->
  <x-dialog-modal wire:model.live="showIncomeModal" maxWidth="lg">
    <x-slot name="title">
      Detail Pemasukan - {{ $selectedPayrollEmployeeName }}
    </x-slot>

    <x-slot name="content">
      @if(count($selectedIncomes) > 0)
        <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
          <table class="w-full divide-y divide-gray-300 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="col" class="w-3/5 py-3 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-300 sm:pl-6">Jenis Pemasukan</th>
                <th scope="col" class="w-2/5 whitespace-nowrap px-3 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-300">Nominal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
              @php $totalIncome = 0; @endphp
              @foreach($selectedIncomes as $income)
                @php $totalIncome += $income['amount']; @endphp
                <tr>
                  <td class="w-3/5 whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6">
                    {{ $income['name'] }}
                  </td>
                  <td class="w-2/5 whitespace-nowrap px-3 py-4 text-sm text-right text-green-600 dark:text-green-400">
                    Rp {{ number_format($income['amount'], 0, ',', '.') }}
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="row" class="w-3/5 py-3 pl-4 pr-3 text-left text-sm font-bold text-gray-900 dark:text-white sm:pl-6">Total Pemasukan</th>
                <td class="w-2/5 whitespace-nowrap px-3 py-3 text-right text-sm font-bold text-green-600 dark:text-green-400">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada detail pemasukan untuk payroll ini.</p>
      @endif
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeIncomeModal" wire:loading.attr="disabled">
        {{ __('Tutup') }}
      </x-secondary-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Export Transfer Bank (BCA MAT) -->
  <x-dialog-modal wire:model.live="isExportBankModalOpen" maxWidth="4xl">
    <x-slot name="title">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">Export Transfer Bank (BCA MAT)</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Format resmi Multi Auto Transfer (MAT) Bank BCA sesuai standar payroll.</p>
        </div>
        <a href="{{ asset('excel/template_mat_bca.xlsx') }}" download class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1 h-3.5 w-3.5 text-emerald-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
          </svg>
          Unduh Template
        </a>
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="space-y-4">
        <!-- Configuration Form -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 bg-gray-50 dark:bg-gray-900/60 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
          <div>
            <x-label for="export_bank_month" value="Periode Payroll" class="mb-1 text-xs font-semibold" />
            <x-input type="month" id="export_bank_month" class="w-full text-xs" wire:model.live="export_bank_month" />
          </div>

          <div>
            <x-label for="export_bank_type" value="Opsi Bank" class="mb-1 text-xs font-semibold" />
            <x-select id="export_bank_type" class="w-full text-xs" wire:model.live="export_bank_type">
              <option value="BCA">BCA (Multi Auto Transfer)</option>
              <option value="LLG">LLG (Antar Bank)</option>
              <option value="RTG">RTGS (Antar Bank)</option>
            </x-select>
          </div>

          <div>
            <x-label for="export_transaction_date" value="Tanggal Eksekusi Transfer" class="mb-1 text-xs font-semibold" />
            <x-input type="date" id="export_transaction_date" class="w-full text-xs" wire:model.live="export_transaction_date" />
          </div>

          <div>
            <x-label for="export_bank_remark" value="Keterangan (Max 18 Karakter)" class="mb-1 text-xs font-semibold" />
            <x-input type="text" id="export_bank_remark" maxlength="18" placeholder="Misal: Gaji Ags 2026" class="w-full text-xs" wire:model.live="export_bank_remark" />
          </div>

          <div>
            <x-label for="export_cust_type" value="Receiver Cust. Type" class="mb-1 text-xs font-semibold" />
            <x-select id="export_cust_type" class="w-full text-xs" wire:model.live="export_cust_type">
              <option value="1">1 - Perorangan (Default)</option>
              <option value="2">2 - Perusahaan</option>
              <option value="3">3 - Pemerintah</option>
            </x-select>
          </div>

          <div>
            <x-label for="export_cust_residence" value="Receiver Cust. Residence" class="mb-1 text-xs font-semibold" />
            <x-select id="export_cust_residence" class="w-full text-xs" wire:model.live="export_cust_residence">
              <option value="1">1 - Residence / Penduduk (Default)</option>
              <option value="2">2 - Non Residence / Bukan Penduduk</option>
            </x-select>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1">
          <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" wire:model.live="export_only_with_account" class="rounded border-gray-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-sky-600">
            <span class="ml-2 text-xs text-gray-700 dark:text-gray-300 font-medium">Hanya sertakan karyawan yang memiliki No. Rekening</span>
          </label>

          <div class="text-xs text-gray-500 dark:text-gray-400">
            Terpilih: <strong class="text-sky-600 dark:text-sky-400">{{ count($export_selected_payrolls) }}</strong> dari <strong>{{ $exportPayrolls->count() }}</strong> karyawan | Total Transfer: <strong class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($exportPayrolls->whereIn('id', $export_selected_payrolls)->sum('net_salary'), 0, ',', '.') }}</strong>
          </div>
        </div>

        <!-- Preview Table -->
        <div class="overflow-x-auto max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/80 sticky top-0 z-10">
              <tr>
                <th scope="col" class="w-10 px-3 py-2 text-center">
                  <input type="checkbox" wire:model.live="export_select_all" class="rounded border-gray-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-gray-700 dark:bg-gray-900">
                </th>
                <th scope="col" class="px-2 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Transaction ID</th>
                <th scope="col" class="px-2 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">No. Rekening (Credited Acc)</th>
                <th scope="col" class="px-2 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Nama Penerima</th>
                <th scope="col" class="px-2 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">NIP</th>
                <th scope="col" class="px-2 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Nominal (Amount)</th>
                <th scope="col" class="px-2 py-2 text-center font-semibold text-gray-600 dark:text-gray-300">Rekening</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
              @php $pSeq = 1; @endphp
              @forelse ($exportPayrolls as $ep)
                @php
                  $epHasAcc = !empty($ep->employee?->paymentMethod?->bank_account);
                  $epTxId = sprintf('%s-%03d', $exportFormattedDatePrefix, $pSeq);
                  $epReceiver = strtoupper($ep->employee?->paymentMethod?->account_name ?: ($ep->employee?->name ?? ''));
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !$epHasAcc ? 'bg-amber-50/30 dark:bg-amber-950/10' : '' }}">
                  <td class="px-3 py-2 text-center">
                    <input type="checkbox" value="{{ $ep->id }}" wire:model.live="export_selected_payrolls" class="rounded border-gray-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-gray-700 dark:bg-gray-900">
                  </td>
                  <td class="px-2 py-2 font-mono font-bold text-sky-600 dark:text-sky-400 whitespace-nowrap">{{ $epTxId }}</td>
                  <td class="px-2 py-2 font-mono {{ $epHasAcc ? 'text-gray-900 dark:text-white' : 'text-amber-500 italic' }} whitespace-nowrap">
                    {{ $epHasAcc ? $ep->employee->paymentMethod->bank_account : 'Kosong' }}
                  </td>
                  <td class="px-2 py-2 text-left whitespace-nowrap">
                    <span class="font-semibold text-gray-900 dark:text-white block">{{ $epReceiver }}</span>
                    <span class="text-[10px] font-normal text-gray-500 dark:text-gray-400 block">{{ $ep->employee?->division?->name ?? 'Tanpa Divisi' }}</span>
                  </td>
                  <td class="px-2 py-2 font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $ep->employee?->nip ?? '-' }}</td>
                  <td class="px-2 py-2 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                    Rp {{ number_format($ep->net_salary, 2, ',', '.') }}
                  </td>
                  <td class="px-2 py-2 text-center whitespace-nowrap">
                    @if ($epHasAcc)
                      <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">Siap</span>
                    @else
                      <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">Belum Ada</span>
                    @endif
                  </td>
                </tr>
                @php $pSeq++; @endphp
              @empty
                <tr>
                  <td colspan="6" class="px-4 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                    Tidak ada data payroll pada periode <strong>{{ $export_bank_month }}</strong>.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <div class="flex items-center justify-between w-full">
        <a href="{{ route('payroll.export-bank') }}?month={{ $export_bank_month }}" class="text-xs font-semibold text-sky-600 dark:text-sky-400 hover:underline">
          Buka Halaman Penuh Export Bank &rarr;
        </a>
        <div class="flex items-center gap-2">
          <x-secondary-button wire:click="closeExportBankModal" wire:loading.attr="disabled">
            {{ __('Batal') }}
          </x-secondary-button>
          <x-button type="button" wire:click="exportBankTransfer" wire:loading.attr="disabled" class="!bg-sky-600 hover:!bg-sky-700 active:!bg-sky-800">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            {{ __('Download Excel BCA MAT') }}
          </x-button>
        </div>
      </div>
    </x-slot>
  </x-dialog-modal>
</div>
