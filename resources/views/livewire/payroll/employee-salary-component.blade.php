<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <div>
      <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
        Master Gaji
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
        Konfigurasi Gaji Pokok, Tunjangan & Potongan Gaji Karyawan
      </p>
    </div>
    <div class="flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="sm:mr-1.5 h-4 w-4 text-sky-500" />
        <span class="hidden sm:inline">Filter</span>
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
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
            <x-label for="division_filter" value="Divisi" class="mb-1"></x-label>
            <x-select id="division_filter" class="w-full" wire:model.live="division">
              <option value="">Semua Divisi</option>
              @foreach (\App\Models\Division::all() as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
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

      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[800px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
          <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <tr>
              <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
              <th scope="col" class="px-4 py-3 min-w-[140px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipe Gaji</th>
              <th scope="col" class="px-4 py-3 min-w-[160px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Gaji Pokok</th>
              <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tunjangan</th>
              <th scope="col" class="px-4 py-3 min-w-[100px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($employees as $emp)
              <tr>
                <td class="whitespace-nowrap px-3 py-4">
                  <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0">
                      <img class="h-10 w-10 rounded-full object-cover" src="{{ $emp->profile_photo_url }}" alt="">
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $emp->name }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">{{ $emp->nip }}</div>
                      <div class="text-xs text-blue-500 dark:text-blue-400">{{ $emp->division->name ?? '-' }} | {{ $emp->jobTitle->name ?? '-' }}</div>
                    </div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  @if($emp->salary)
                    <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      {{ ucfirst($emp->salary->salary_type) }}
                    </span>
                  @else
                    <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Belum Diset</span>
                  @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  @if($emp->salary)
                    Rp {{ number_format($emp->salary->basic_salary, 0, ',', '.') }}
                  @else
                    -
                  @endif
                </td>
                <td class="px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  @if($emp->salary)
                    <ul class="list-disc pl-4 text-xs">
                      <li>Transport: Rp{{ number_format($emp->salary->transport_allowance, 0, ',', '.') }}</li>
                      <li>Kehadiran: Rp{{ number_format($emp->salary->attendance_allowance, 0, ',', '.') }}</li>
                      <li>Lembur/Jam: Rp{{ number_format($emp->salary->overtime_rate, 0, ',', '.') }}</li>
                      <li>Syirkah: {{ $emp->salary->savings ? $emp->salary->savings->savings_name : 'Tidak Ada' }}</li>
                    </ul>
                  @else
                    -
                  @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-center text-sm font-medium">
                  <button wire:click="edit('{{ $emp->id }}')" class="rounded bg-sky-500 px-3 py-1 text-white hover:bg-sky-600 focus:outline-none">
                    {{ $emp->salary ? 'Edit' : 'Set Gaji' }}
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada data karyawan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $employees->links() }}
      </div>

    </div>
  </div>

  <!-- Modal Edit -->
  <x-dialog-modal wire:model.live="isModalOpen">
    <x-slot name="title">
      {{ __('Atur Gaji Karyawan') }}
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div>
            <x-label for="salary_type" value="{{ __('Tipe Gaji') }}" />
            <select id="salary_type" wire:model.live="salary_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
              <option value="monthly">Bulanan (Monthly)</option>
              <option value="daily">Harian (Daily)</option>
            </select>
            <x-input-error for="salary_type" class="mt-2" />
          </div>

          @if($salary_type === 'monthly')
          <div>
            <x-label for="working_days_per_month" value="{{ __('Jumlah Hari Kerja') }}" />
            <x-input id="working_days_per_month" type="number" class="mt-1 block w-full" wire:model="working_days_per_month" min="1" max="31" />
            <x-input-error for="working_days_per_month" class="mt-2" />
          </div>
          @endif

          <div>
            <x-label for="annual_leave_quota" value="{{ __('Jatah Cuti') }}" />
            <x-input id="annual_leave_quota" type="number" class="mt-1 block w-full" wire:model="annual_leave_quota" min="0" />
            <x-input-error for="annual_leave_quota" class="mt-2" />
          </div>
        </div>

        <div>
          <x-label for="basic_salary" value="{{ __('Gaji Pokok (Rp)') }}" />
          <x-input id="basic_salary" type="number" class="mt-1 block w-full" wire:model="basic_salary" />
          <p class="mt-1 text-xs text-gray-500">Gaji bulanan, atau gaji per hari hadir jika tipe Harian.</p>
          <x-input-error for="basic_salary" class="mt-2" />
        </div>

        <div>
          <x-label for="attendance_allowance" value="{{ __('Tunjangan Kerajinan / Kehadiran (Rp)') }}" />
          <x-input id="attendance_allowance" type="number" class="mt-1 block w-full" wire:model="attendance_allowance" />
          <p class="mt-1 text-xs text-gray-500">Nominal flat bulanan jika memenuhi syarat kehadiran.</p>
          <x-input-error for="attendance_allowance" class="mt-2" />
        </div>

        <div>
          <x-label for="transport_allowance" value="{{ __('Tunjangan Transport (Rp)') }}" />
          <x-input id="transport_allowance" type="number" class="mt-1 block w-full" wire:model="transport_allowance" />
          <x-input-error for="transport_allowance" class="mt-2" />
        </div>

        <div>
          <x-label for="overtime_rate" value="{{ __('Rate Lembur / Jam (Rp)') }}" />
          <x-input id="overtime_rate" type="number" class="mt-1 block w-full" wire:model="overtime_rate" />
          <x-input-error for="overtime_rate" class="mt-2" />
        </div>


        <div class="sm:col-span-2">
          <x-label for="savings_id" value="{{ __('Syirkah / Tabungan (Opsional)') }}" />
          <select id="savings_id" wire:model="savings_id" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
            <option value="">-- Tidak Ada --</option>
            @foreach($savingsList as $s)
              <option value="{{ $s->id }}">{{ $s->savings_name }} (Wajib: Rp{{ number_format($s->mandatory_savings, 0, ',', '.') }})</option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-gray-500">Pilih program syirkah yang diikuti oleh karyawan ini.</p>
          <x-input-error for="savings_id" class="mt-2" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeModal" wire:loading.attr="disabled">
        {{ __('Batal') }}
      </x-secondary-button>

      <x-button class="ms-3" wire:click="save" wire:loading.attr="disabled">
        {{ __('Simpan') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>
</div>
