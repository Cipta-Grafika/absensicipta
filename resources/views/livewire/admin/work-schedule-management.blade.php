<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Jadwal Kerja
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-button type="button" x-data @click.prevent="Livewire.dispatch('show-creating')" class="!py-1.5 !px-3">
        <x-heroicon-o-plus class="mr-1.5 h-4 w-4" />
        Tambah
      </x-button>
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="py-0 sm:py-12" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
    <div class="overflow-hidden bg-white p-6 shadow-xl dark:bg-gray-800 sm:rounded-lg">

      <!-- Search Field Bar -->
      <div class="mb-4">
        <div class="flex w-full flex-1 items-center gap-2">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-10 pr-10" autocomplete="off" wire:model.live.debounce.300ms="search"
              placeholder="{{ __('Search') }}" />
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

      <!-- Filter Sidebar -->
      <x-filter-sidebar maxWidth="sm">
        <x-slot name="title">Filter Jadwal Kerja</x-slot>
        <x-slot name="actions">
          <button type="button" wire:click="$set('filter_division_id', ''); $set('filter_user_id', ''); $set('filter_start_date', ''); $set('filter_end_date', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
          </button>
        </x-slot>

        <x-slot name="content">
          <div class="flex flex-col gap-6">
            @if(auth()->user()->isSuperadmin)
              <div>
                <x-label for="filter_division_id" value="Pilih Divisi" class="mb-1"></x-label>
                <x-select id="filter_division_id" class="w-full" wire:model.live="filter_division_id">
                  <option value="">Semua Divisi</option>
                  @foreach ($divisions as $div)
                    <option value="{{ $div->id }}">{{ $div->name }}</option>
                  @endforeach
                </x-select>
              </div>
            @endif

            <div>
              <x-label for="filter_user_id" value="Pilih Karyawan" class="mb-1"></x-label>
              <x-select id="filter_user_id" class="w-full" wire:model.live="filter_user_id">
                <option value="">Semua Karyawan</option>
                @foreach ($users as $u)
                  <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
              </x-select>
            </div>

            <div>
              <x-label for="filter_start_date" value="Dari Tanggal" class="mb-1"></x-label>
              <x-input id="filter_start_date" type="date" class="w-full" wire:model.live="filter_start_date" />
            </div>

            <div>
              <x-label for="filter_end_date" value="Sampai Tanggal" class="mb-1"></x-label>
              <x-input id="filter_end_date" type="date" class="w-full" wire:model.live="filter_end_date" />
            </div>
          </div>
        </x-slot>
      </x-filter-sidebar>

      <!-- Schedule Table -->
      <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Tanggal
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Karyawan
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Divisi
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Status Roster
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Catatan
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($schedules as $sched)
              <tr>
                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                  {{ $sched->date->format('d M Y') }} ({{ $sched->date->translatedFormat('l') }})
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                  {{ $sched->user->name ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ $sched->user->division->name ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm">
                  @if ($sched->is_working_day)
                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-200">
                      Hari Kerja (Wajib Masuk)
                    </span>
                  @else
                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold leading-5 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                      Hari Libur (Day Off)
                    </span>
                  @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ $sched->note ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                  <div class="flex justify-end gap-2">
                    <x-button wire:click="edit({{ $sched->id }})">
                      Edit
                    </x-button>
                    <x-danger-button wire:click="confirmDeletion({{ $sched->id }})">
                      Hapus
                    </x-danger-button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  Belum ada data jadwal kerja rolling.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $schedules->links() }}
      </div>
    </div>
  </div>

  <!-- Create Modal -->
  <x-dialog-modal wire:model="creating">
    <x-slot name="title">
      Tambah Jadwal Rolling Karyawan
    </x-slot>

    <form wire:submit.prevent="create">
      <x-slot name="content">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <x-label for="start_date" value="Dari Tanggal" />
            <x-input id="start_date" type="date" class="mt-1 block w-full" wire:model="start_date" />
            <x-input-error for="start_date" class="mt-1" />
          </div>

          <div>
            <x-label for="end_date" value="Sampai Tanggal (Opsional)" />
            <x-input id="end_date" type="date" class="mt-1 block w-full" wire:model="end_date" />
            <x-input-error for="end_date" class="mt-1" />
          </div>
        </div>

        <div class="mt-4">
          <x-label for="is_working_day" value="Status Roster" />
          <select id="is_working_day" wire:model="is_working_day" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <option value="1">Hari Kerja (Masuk)</option>
            <option value="0">Hari Libur (Day Off)</option>
          </select>
          <x-input-error for="is_working_day" class="mt-1" />
        </div>

        <div class="mt-4" x-data="{
          userSearch: '',
          toggleAll(event, userList) {
            if (event.target.checked) {
              let allFilteredIds = userList.map(u => String(u.id));
              let current = ($wire.user_ids || []).map(id => String(id));
              $wire.user_ids = Array.from(new Set([...current, ...allFilteredIds]));
            } else {
              let filteredSet = new Set(userList.map(u => String(u.id)));
              $wire.user_ids = ($wire.user_ids || []).filter(id => !filteredSet.has(String(id)));
            }
          }
        }">
          <div class="flex items-center justify-between mb-1.5">
            <x-label value="Pilih Karyawan (Multi-Select)" />
            <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
              <span x-text="($wire.user_ids || []).length"></span> Karyawan Dipilih
            </span>
          </div>

          <!-- Search Input Field -->
          <div class="relative mb-2">
            <input type="text" x-model="userSearch" placeholder="Cari nama karyawan / NIP..."
              class="w-full rounded-md border border-gray-300 bg-gray-50 py-1.5 pl-8 pr-3 text-xs text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <svg class="pointer-events-none absolute inset-y-0 left-2.5 my-auto h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Select All Option Bar -->
          <div class="mb-2 flex items-center justify-between rounded-md bg-gray-100 dark:bg-gray-800 px-3 py-1.5 border border-gray-200 dark:border-gray-700">
            <label class="inline-flex items-center text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
              <input type="checkbox"
                @change="toggleAll($event, {{ json_encode($users->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'nip' => $u->nip ?? ''])) }})"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900">
              <span class="ml-2">Pilih Semua Karyawan</span>
            </label>
          </div>

          <!-- Employee Checkbox List -->
          <div class="max-h-48 overflow-y-auto rounded-md border border-gray-300 p-3 dark:border-gray-700 dark:bg-gray-900">
            @foreach ($users as $u)
              <label class="mb-1.5 flex items-center cursor-pointer"
                x-show="!userSearch || '{{ strtolower(addslashes($u->name . ' ' . ($u->nip ?? ''))) }}'.includes(userSearch.toLowerCase())">
                <input type="checkbox" wire:model="user_ids" value="{{ $u->id }}" class="rounded text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  {{ $u->name }} <span class="text-xs text-gray-400">({{ $u->division->name ?? 'Tanpa Divisi' }})</span>
                </span>
              </label>
            @endforeach
            <div x-show="userSearch && {{ json_encode($users->map(fn($u) => strtolower($u->name . ' ' . ($u->nip ?? '')))) }}.filter(str => str.includes(userSearch.toLowerCase())).length === 0"
              class="py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400">
              Karyawan tidak ditemukan
            </div>
          </div>
          <x-input-error for="user_ids" class="mt-1" />
        </div>

        <div class="mt-4">
          <x-label for="note" value="Catatan / Keperluan" />
          <x-input id="note" type="text" class="mt-1 block w-full" wire:model="note" placeholder="Misal: Rolling piket Minggu" />
          <x-input-error for="note" class="mt-1" />
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
          Batal
        </x-secondary-button>

        <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled">
          Simpan Jadwal
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <!-- Edit Modal -->
  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Jadwal Roster Karyawan
    </x-slot>

    <form wire:submit.prevent="update" id="edit-work-schedule-form">
      <x-slot name="content">
        <div>
          <x-label for="edit_user_id" value="Karyawan" />
          <select id="edit_user_id" wire:model="edit_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <option value="">Pilih Karyawan</option>
            @foreach ($users as $u)
              <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->division->name ?? 'Tanpa Divisi' }})</option>
            @endforeach
          </select>
          <x-input-error for="edit_user_id" class="mt-1" />
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <x-label for="edit_date" value="Tanggal Roster" />
            <x-input id="edit_date" type="date" class="mt-1 block w-full" wire:model="edit_date" required />
            <x-input-error for="edit_date" class="mt-1" />
          </div>

          <div>
            <x-label for="edit_is_working_day" value="Status Roster" />
            <select id="edit_is_working_day" wire:model="edit_is_working_day" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <option value="1">Hari Kerja (Masuk)</option>
              <option value="0">Hari Libur (Day Off)</option>
            </select>
            <x-input-error for="edit_is_working_day" class="mt-1" />
          </div>
        </div>

        <div class="mt-4">
          <x-label for="edit_note" value="Catatan / Keperluan" />
          <x-input id="edit_note" type="text" class="mt-1 block w-full" wire:model="edit_note" placeholder="Misal: Rolling piket Minggu" />
          <x-input-error for="edit_note" class="mt-1" />
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
          Batal
        </x-secondary-button>

        <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled">
          Perbarui Jadwal
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <!-- Delete Confirmation Modal -->
  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Jadwal Roster
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus data jadwal kerja ini?
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
        Hapus
      </x-danger-button>
    </x-slot>
  </x-confirmation-modal>
</div>
