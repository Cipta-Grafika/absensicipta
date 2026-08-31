<div>

  <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            Nama / Label
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            Range Durasi (Jam)
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            Tipe Bayaran
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            Tipe Karyawan
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            Nominal Rate & Uang Makan
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 whitespace-nowrap">
            Divisi Scope
          </th>
          <th scope="col" class="relative px-6 py-3">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @forelse ($rates as $rate)
          <tr>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              {{ $rate->name ?? 'Tarif Lembur' }}
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              <span class="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/40 px-2 py-1 text-xs font-semibold text-blue-700 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10">
                {{ $rate->min_hours }} - {{ $rate->max_hours }} Jam
              </span>
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              @if($rate->rate_type === 'per_hour')
                <span class="inline-flex items-center rounded-md bg-emerald-50 dark:bg-emerald-900/40 px-2 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                  Per Jam (Hourly)
                </span>
              @else
                <span class="inline-flex items-center rounded-md bg-purple-50 dark:bg-purple-900/40 px-2 py-1 text-xs font-medium text-purple-700 dark:text-purple-300">
                  Paket Flat (Flat Rate)
                </span>
              @endif
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              <span class="inline-flex items-center rounded-md bg-sky-50 dark:bg-sky-900/40 px-2 py-1 text-xs font-medium text-sky-700 dark:text-sky-300 ring-1 ring-inset ring-sky-700/10 uppercase">
                {{ $rate->employee_type === 'all' || empty($rate->employee_type) ? 'Semua Tipe' : strtoupper($rate->employee_type) }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
              <div>Rp {{ number_format($rate->rate_amount, 0, ',', '.') }} {{ $rate->rate_type === 'per_hour' ? '/ jam' : '' }}</div>
              @if(($rate->meal_allowance ?? 0) > 0)
                <div class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 mt-0.5 flex flex-col">
                  <span>+ Uang Makan Rp {{ number_format($rate->meal_allowance, 0, ',', '.') }}</span>
                  @if($rate->meal_min_start_time || $rate->meal_max_start_time || $rate->meal_min_duration)
                    <span class="text-[10px] font-medium text-amber-700/80 dark:text-amber-300/80">
                      Syarat: 
                      @if($rate->meal_min_start_time || $rate->meal_max_start_time)
                        Mulai: {{ substr($rate->meal_min_start_time ?? '17:00', 0, 5) }} - {{ substr($rate->meal_max_start_time ?? '18:00', 0, 5) }}
                      @endif
                      @if($rate->meal_min_duration)
                        {{ ($rate->meal_min_start_time || $rate->meal_max_start_time) ? ', ' : '' }}Min {{ $rate->meal_min_duration }} Jam
                      @endif
                    </span>
                  @else
                    <span class="text-[10px] font-normal text-gray-400 dark:text-gray-500">(Tanpa syarat jam)</span>
                  @endif
                </div>
              @endif
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
              @if($rate->division)
                <span class="inline-flex items-center rounded-md bg-indigo-50 dark:bg-indigo-900/40 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300 ring-1 ring-inset ring-indigo-700/10">
                  {{ $rate->division->name }}
                </span>
              @else
                <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                  Global (Semua Divisi)
                </span>
              @endif
            </td>
            <td class="relative flex justify-end gap-2 px-4 py-4 whitespace-nowrap">
              <button type="button" wire:click="edit({{ $rate->id }})" title="Edit Tarif Lembur"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-sky-500 px-2 py-1.5 text-white shadow-sm hover:bg-sky-600 focus:outline-none transition-colors duration-150">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button type="button" wire:click="confirmDeletion({{ $rate->id }}, '{{ $rate->name }}')" title="Hapus Tarif Lembur"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-2 py-1.5 text-white shadow-sm hover:bg-red-700 focus:outline-none transition-colors duration-150">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-6 py-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
              Belum ada data tarif lembur.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Delete Confirmation Modal -->
  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Tarif Lembur
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus tarif lembur <b>{{ $deleteName }}</b>?
    </x-slot>

    <x-slot name="footer">
      <x-danger-button wire:click="delete" wire:loading.attr="disabled">
        Ya, Hapus
      </x-danger-button>
      <x-secondary-button class="ms-2" wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>
    </x-slot>
  </x-confirmation-modal>

  <!-- Create Modal -->
  <x-dialog-modal wire:model="creating">
    <x-slot name="title">
      Tambah Tarif Lembur Baru
    </x-slot>

    <form wire:submit.prevent="create">
      <x-slot name="content">
        <div>
          <x-label for="name">Nama / Label Rate</x-label>
          <x-input id="name" class="mt-1 block w-full" type="text" wire:model="form.name" placeholder="Misal: Lembur 1 - 3 Jam (PKL)" />
          @error('form.name')
            <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="employee_type">Tipe Karyawan</x-label>
            <x-select id="employee_type" wire:model="form.employee_type" class="mt-1 block w-full">
              <option value="all">Semua Tipe Karyawan</option>
              <option value="full-time">Full-time</option>
              <option value="contract">Kontrak (Contract)</option>
              <option value="part-time">Part-time (PT)</option>
              <option value="freelance">Freelance (FR)</option>
              <option value="probation">Probation (PRB)</option>
              <option value="intern">Internship (INT)</option>
              <option value="pkl">PKL (Praktik Kerja Lapangan)</option>
              <option value="outsourcing">Outsourcing</option>
              <option value="volunteer">Volunteer</option>
            </x-select>
            @error('form.employee_type')
              <x-input-error for="form.employee_type" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>

          <div class="w-full">
            <x-label for="meal_allowance">Uang Makan Lembur (Opsional - Rp)</x-label>
            <x-input id="meal_allowance" class="mt-1 block w-full" type="number" wire:model.live="form.meal_allowance" placeholder="0" />
            @error('form.meal_allowance')
              <x-input-error for="form.meal_allowance" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <!-- Conditional Meal Settings Subpanel -->
        <div class="mt-3 p-3 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/50">
          <div class="text-xs font-bold text-amber-900 dark:text-amber-200 flex items-center gap-1.5 mb-2">
            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Syarat Pemberian Uang Makan (Otomatis & Konfigurable)</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <x-label for="meal_min_start_time" class="text-xs text-gray-700 dark:text-gray-300 font-medium">Jam Mulai Dari</x-label>
              <x-input id="meal_min_start_time" class="mt-1 block w-full text-xs" type="time" wire:model="form.meal_min_start_time" placeholder="17:00" />
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Mulai dari jam (misal <strong>17:00</strong>).</p>
            </div>
            <div>
              <x-label for="meal_max_start_time" class="text-xs text-gray-700 dark:text-gray-300 font-medium">Jam Mulai Sampai</x-label>
              <x-input id="meal_max_start_time" class="mt-1 block w-full text-xs" type="time" wire:model="form.meal_max_start_time" placeholder="18:00" />
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Batas akhir mulai (misal <strong>18:00</strong>).</p>
            </div>
            <div>
              <x-label for="meal_min_duration" class="text-xs text-gray-700 dark:text-gray-300 font-medium">Min. Durasi (Jam)</x-label>
              <x-input id="meal_min_duration" class="mt-1 block w-full text-xs" type="number" step="0.5" min="0" wire:model="form.meal_min_duration" placeholder="Contoh: 2" />
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Opsional (mengikuti tier min. jam).</p>
            </div>
          </div>
        </div>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="min_hours">Min. Jam</x-label>
            <x-input id="min_hours" class="mt-1 block w-full" type="number" step="0.5" wire:model="form.min_hours" required />
            @error('form.min_hours')
              <x-input-error for="form.min_hours" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="max_hours">Max. Jam</x-label>
            <x-input id="max_hours" class="mt-1 block w-full" type="number" step="0.5" wire:model="form.max_hours" required />
            @error('form.max_hours')
              <x-input-error for="form.max_hours" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <p class="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">
          <span class="font-bold text-sky-600 dark:text-sky-400">💡 Best-Practice Tier Range:</span>
          Untuk tier berjenjang (misal Tier 1: 1 - 3 Jam & Tier 2: > 3 Jam s/d 24 Jam), sistem secara presisi memprioritaskan batas eksak. Uang makan lembur (jika syarat terpenuhi) ditambahkan <strong>flat (1x)</strong> per pengajuan lembur.
        </p>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="rate_type">Tipe Bayaran</x-label>
            <x-select id="rate_type" wire:model="form.rate_type" class="mt-1 block w-full">
              <option value="per_hour">Per Jam (Jumlah Jam x Nominal)</option>
              <option value="flat_package">Paket Flat (Nominal Tetap)</option>
            </x-select>
            @error('form.rate_type')
              <x-input-error for="form.rate_type" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>

          <div class="w-full">
            <x-label for="rate_amount">Nominal Bayaran (Rp)</x-label>
            <x-input id="rate_amount" class="mt-1 block w-full" type="number" wire:model="form.rate_amount" required />
            @error('form.rate_amount')
              <x-input-error for="form.rate_amount" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <div class="mt-4">
          @if(auth()->user()->isSuperadmin)
            <x-label for="division_id">Divisi Scope</x-label>
            <x-select id="division_id" wire:model="form.division_id" class="mt-1 block w-full">
              <option value="">Global (Semua Divisi)</option>
              @foreach ($divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
              @endforeach
            </x-select>
            @error('form.division_id')
              <x-input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
            @enderror
          @else
            <x-label>Divisi Scope</x-label>
            <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-900 rounded-md text-sm text-gray-700 dark:text-gray-300 font-medium">
              {{ auth()->user()->division?->name ?? 'Divisi Anda' }} (Otomatis)
            </div>
          @endif
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled">
          {{ __('Confirm') }}
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <!-- Edit Modal -->
  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Tarif Lembur
    </x-slot>

    <form wire:submit.prevent="update" id="overtime-rate-edit">
      <x-slot name="content">
        <div>
          <x-label for="edit_name">Nama / Label Rate</x-label>
          <x-input id="edit_name" class="mt-1 block w-full" type="text" wire:model="form.name" />
          @error('form.name')
            <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_employee_type">Tipe Karyawan</x-label>
            <x-select id="edit_employee_type" wire:model="form.employee_type" class="mt-1 block w-full">
              <option value="all">Semua Tipe Karyawan</option>
              <option value="full-time">Full-time</option>
              <option value="contract">Kontrak (Contract)</option>
              <option value="part-time">Part-time (PT)</option>
              <option value="freelance">Freelance (FR)</option>
              <option value="probation">Probation (PRB)</option>
              <option value="intern">Internship (INT)</option>
              <option value="pkl">PKL (Praktik Kerja Lapangan)</option>
              <option value="outsourcing">Outsourcing</option>
              <option value="volunteer">Volunteer</option>
            </x-select>
            @error('form.employee_type')
              <x-input-error for="form.employee_type" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>

          <div class="w-full">
            <x-label for="edit_meal_allowance">Uang Makan Lembur (Opsional - Rp)</x-label>
            <x-input id="edit_meal_allowance" class="mt-1 block w-full" type="number" wire:model.live="form.meal_allowance" placeholder="0" />
            @error('form.meal_allowance')
              <x-input-error for="form.meal_allowance" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <!-- Conditional Meal Settings Subpanel (Edit) -->
        <div class="mt-3 p-3 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/50">
          <div class="text-xs font-bold text-amber-900 dark:text-amber-200 flex items-center gap-1.5 mb-2">
            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Syarat Pemberian Uang Makan (Otomatis & Konfigurable)</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <x-label for="edit_meal_min_start_time" class="text-xs text-gray-700 dark:text-gray-300 font-medium">Jam Mulai Dari</x-label>
              <x-input id="edit_meal_min_start_time" class="mt-1 block w-full text-xs" type="time" wire:model="form.meal_min_start_time" placeholder="17:00" />
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Mulai dari jam (misal <strong>17:00</strong>).</p>
            </div>
            <div>
              <x-label for="edit_meal_max_start_time" class="text-xs text-gray-700 dark:text-gray-300 font-medium">Jam Mulai Sampai</x-label>
              <x-input id="edit_meal_max_start_time" class="mt-1 block w-full text-xs" type="time" wire:model="form.meal_max_start_time" placeholder="18:00" />
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Batas akhir mulai (misal <strong>18:00</strong>).</p>
            </div>
            <div>
              <x-label for="edit_meal_min_duration" class="text-xs text-gray-700 dark:text-gray-300 font-medium">Min. Durasi (Jam)</x-label>
              <x-input id="edit_meal_min_duration" class="mt-1 block w-full text-xs" type="number" step="0.5" min="0" wire:model="form.meal_min_duration" placeholder="Contoh: 2" />
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Opsional (mengikuti tier min. jam).</p>
            </div>
          </div>
        </div>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_min_hours">Min. Jam</x-label>
            <x-input id="edit_min_hours" class="mt-1 block w-full" type="number" step="0.5" wire:model="form.min_hours" required />
            @error('form.min_hours')
              <x-input-error for="form.min_hours" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="edit_max_hours">Max. Jam</x-label>
            <x-input id="edit_max_hours" class="mt-1 block w-full" type="number" step="0.5" wire:model="form.max_hours" required />
            @error('form.max_hours')
              <x-input-error for="form.max_hours" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_rate_type">Tipe Bayaran</x-label>
            <x-select id="edit_rate_type" wire:model="form.rate_type" class="mt-1 block w-full">
              <option value="per_hour">Per Jam (Jumlah Jam x Nominal)</option>
              <option value="flat_package">Paket Flat (Nominal Tetap)</option>
            </x-select>
            @error('form.rate_type')
              <x-input-error for="form.rate_type" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>

          <div class="w-full">
            <x-label for="edit_rate_amount">Nominal Bayaran (Rp)</x-label>
            <x-input id="edit_rate_amount" class="mt-1 block w-full" type="number" wire:model="form.rate_amount" required />
            @error('form.rate_amount')
              <x-input-error for="form.rate_amount" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>

        <div class="mt-4">
          @if(auth()->user()->isSuperadmin)
            <x-label for="edit_division_id">Divisi Scope</x-label>
            <x-select id="edit_division_id" wire:model="form.division_id" class="mt-1 block w-full">
              <option value="">Global (Semua Divisi)</option>
              @foreach ($divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
              @endforeach
            </x-select>
            @error('form.division_id')
              <x-input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
            @enderror
          @else
            <x-label>Divisi Scope</x-label>
            <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-900 rounded-md text-sm text-gray-700 dark:text-gray-300 font-medium">
              {{ auth()->user()->division?->name ?? 'Divisi Anda' }} (Otomatis)
            </div>
          @endif
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled">
          {{ __('Confirm') }}
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>
</div>

