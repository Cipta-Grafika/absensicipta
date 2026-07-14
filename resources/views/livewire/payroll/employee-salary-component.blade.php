<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Master Gaji') }}
    </h2>
  </div>
</x-slot>

<div class="py-0 sm:py-12">
  <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
    <div class="bg-white p-6 shadow-xl dark:bg-gray-800 sm:rounded-lg lg:p-8">
      
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
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipe Gaji</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Gaji Pokok</th>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tunjangan</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
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
                      <li>Makan: Rp{{ number_format($emp->salary->meal_allowance, 0, ',', '.') }}</li>
                      <li>Transport: Rp{{ number_format($emp->salary->transport_allowance, 0, ',', '.') }}</li>
                      <li>Kehadiran: Rp{{ number_format($emp->salary->attendance_allowance, 0, ',', '.') }}</li>
                      <li>Lembur/Jam: Rp{{ number_format($emp->salary->overtime_rate, 0, ',', '.') }}</li>
                      <li>Telat/Mnt: Rp{{ number_format($emp->salary->late_deduction_rate, 0, ',', '.') }}</li>
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
        <div class="sm:col-span-2">
          <x-label for="salary_type" value="{{ __('Tipe Gaji') }}" />
          <select id="salary_type" wire:model.live="salary_type" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
            <option value="monthly">Bulanan (Monthly)</option>
            <option value="daily">Harian (Daily)</option>
          </select>
          <x-input-error for="salary_type" class="mt-2" />
        </div>

        @if($salary_type === 'monthly')
        <div class="sm:col-span-2">
          <x-label for="working_days_per_month" value="{{ __('Jumlah Hari Kerja per Bulan (Default: 25)') }}" />
          <x-input id="working_days_per_month" type="number" class="mt-1 block w-full" wire:model="working_days_per_month" min="1" max="31" />
          <x-input-error for="working_days_per_month" class="mt-2" />
          <p class="mt-1 text-xs text-gray-500">Akan digunakan sebagai pembagi potongan absen bulanan.</p>
        </div>
        @endif

        <div class="sm:col-span-2">
          <x-label for="basic_salary" value="{{ __('Gaji Pokok (Rp)') }}" />
          <x-input id="basic_salary" type="number" class="mt-1 block w-full" wire:model="basic_salary" />
          <p class="mt-1 text-xs text-gray-500">Jika tipe Harian, ini adalah Gaji Pokok per hari hadir.</p>
          <x-input-error for="basic_salary" class="mt-2" />
        </div>

        <div>
          <x-label for="overtime_rate" value="{{ __('Rate Lembur / Jam (Rp)') }}" />
          <x-input id="overtime_rate" type="number" class="mt-1 block w-full" wire:model="overtime_rate" />
          <x-input-error for="overtime_rate" class="mt-2" />
        </div>

        <div>
          <x-label for="late_deduction_rate" value="{{ __('Potongan Telat / Menit (Rp)') }}" />
          <x-input id="late_deduction_rate" type="number" class="mt-1 block w-full" wire:model="late_deduction_rate" />
          <x-input-error for="late_deduction_rate" class="mt-2" />
        </div>

        <div>
          <x-label for="meal_allowance" value="{{ __('Tunjangan Makan / Hari (Rp)') }}" />
          <x-input id="meal_allowance" type="number" class="mt-1 block w-full" wire:model="meal_allowance" />
          <x-input-error for="meal_allowance" class="mt-2" />
        </div>

        <div>
          <x-label for="transport_allowance" value="{{ __('Tunjangan Transport / Hari (Rp)') }}" />
          <x-input id="transport_allowance" type="number" class="mt-1 block w-full" wire:model="transport_allowance" />
          <x-input-error for="transport_allowance" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
          <x-label for="attendance_allowance" value="{{ __('Tunjangan Kerajinan / Kehadiran (Rp)') }}" />
          <x-input id="attendance_allowance" type="number" class="mt-1 block w-full" wire:model="attendance_allowance" />
          <p class="mt-1 text-xs text-gray-500">Nominal flat bulanan jika memenuhi syarat kehadiran.</p>
          <x-input-error for="attendance_allowance" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
          <x-label for="annual_leave_quota" value="{{ __('Jatah Cuti Tahunan (Default: 12 Hari)') }}" />
          <x-input id="annual_leave_quota" type="number" class="mt-1 block w-full" wire:model="annual_leave_quota" min="0" />
          <p class="mt-1 text-xs text-gray-500">Jumlah hari cuti yang dapat diambil dalam setahun (opsional, untuk pendataan).</p>
          <x-input-error for="annual_leave_quota" class="mt-2" />
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
