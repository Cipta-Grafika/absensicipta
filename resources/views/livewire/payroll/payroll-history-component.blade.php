<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Payroll') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <button type="button" x-data @click.prevent="if(confirm('Anda yakin ingin mengubah semua status Draft bulan ini menjadi Paid?')) { Livewire.dispatch('mark-all-as-paid') }" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sm:mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
        <span class="hidden sm:inline">Mark All as Paid</span>
      </button>

      <x-button type="button" x-data @click.prevent="Livewire.dispatch('open-generate-modal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sm:mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="hidden sm:inline">Proses</span>
      </x-button>
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="sm:mr-1.5 h-4 w-4 text-sky-500" />
        <span class="hidden sm:inline">Filter</span>
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-0 sm:py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Payroll</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('month', ''); $set('status', ''); $set('division', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
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

    <div class="bg-white p-6 shadow-none sm:shadow-xl dark:bg-gray-800 sm:rounded-lg lg:p-8">
      
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
              <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            @endif
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th scope="col" class="min-w-[15rem] whitespace-nowrap px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Periode & Karyawan</th>
              @foreach (['H', 'A', 'T', 'L', 'IMP', 'S', 'I', 'C', 'W'] as $_st)
                <th scope="col" class="w-12 min-w-[3rem] border border-gray-300 p-0 text-center text-xs font-medium text-gray-500 dark:border-gray-600 dark:text-gray-300" title="{{ $_st }}">
                  <div class="flex h-12 w-12 items-center justify-center">{{ $_st }}</div>
                </th>
              @endforeach
              <th scope="col" class="whitespace-nowrap px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Pemasukan</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Potongan</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Net Salary</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status & Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($payrolls as $pr)
              <tr>
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
                    <div class="mt-2 flex flex-col items-center space-y-1">
                        <button wire:click="markAsPaid('{{ $pr->id }}')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Mark as Paid</button>
                        <button wire:click="confirmDelete('{{ $pr->id }}')" class="text-xs text-red-600 dark:text-red-400 hover:underline">Hapus</button>
                    </div>
                  @else
                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
                    <div class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($pr->payment_date)->format('d/m/Y') }}</div>
                    <div class="mt-2">
                        <button wire:click="confirmDelete('{{ $pr->id }}')" class="text-xs text-red-600 dark:text-red-400 hover:underline">Hapus</button>
                    </div>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="14" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada data penggajian.</td>
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
  <x-dialog-modal wire:model.live="isGenerateModalOpen">
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
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeGenerateModal" wire:loading.attr="disabled">
        {{ __('Batal') }}
      </x-secondary-button>

      <x-button class="ms-3 bg-blue-600 hover:bg-blue-700" wire:click="generatePayroll" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="generatePayroll">Generate Payroll Sekarang</span>
        <span wire:loading wire:target="generatePayroll">Memproses...</span>
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Konfirmasi Hapus -->
  <x-delete-modal 
      :isOpen="$isDeleteModalOpen" 
      title="Yakin ingin menghapus data gaji ini secara permanen?" 
      deleteAction="deletePayroll" 
      cancelAction="cancelDelete" 
  />

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
</div>
