<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Hari Libur
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-button type="button" x-data @click.prevent="Livewire.dispatch('show-creating')">
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
        <x-slot name="title">Filter Hari Libur</x-slot>
        <x-slot name="actions">
          <button type="button" wire:click="$set('filter_type', ''); $set('filter_year', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
          </button>
        </x-slot>

        <x-slot name="content">
          <div class="flex flex-col gap-6">
            <div>
              <x-label for="filter_type" value="Tipe Libur" class="mb-1"></x-label>
              <x-select id="filter_type" class="w-full" wire:model.live="filter_type">
                <option value="">Semua Tipe</option>
                <option value="general">Nasional / Umum</option>
                <option value="division">Khusus Divisi</option>
                <option value="custom">Custom Multi-User (Rolling)</option>
              </x-select>
            </div>

            <div>
              <x-label for="filter_year" value="Tahun" class="mb-1"></x-label>
              <x-select id="filter_year" class="w-full" wire:model.live="filter_year">
                <option value="">Semua Tahun</option>
                @foreach (range(date('Y') - 1, date('Y') + 2) as $y)
                  <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
              </x-select>
            </div>
          </div>
        </x-slot>
      </x-filter-sidebar>

      <!-- Holiday Table -->
      <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Nama Libur
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Tanggal
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Tipe Libur
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Cakupan / Scope
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse ($holidays as $h)
              <tr>
                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                  {{ $h->name }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                  {{ $h->date->format('d M Y') }} ({{ $h->date->translatedFormat('l') }})
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm">
                  @if ($h->type === 'general')
                    <span class="inline-flex rounded-full bg-purple-100 px-2 text-xs font-semibold leading-5 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                      Nasional / Umum
                    </span>
                  @elseif ($h->type === 'division')
                    <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      Divisi
                    </span>
                  @else
                    <span class="inline-flex rounded-full bg-teal-100 px-2 text-xs font-semibold leading-5 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                      Custom Multi-User
                    </span>
                  @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                  @if ($h->type === 'general')
                    Semua Karyawan
                  @elseif ($h->type === 'division')
                    Divisi: {{ $h->division->name ?? '-' }}
                  @else
                    {{ $h->users->count() }} Karyawan Terpilih
                  @endif
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                  <x-button wire:click="edit({{ $h->id }})" class="mr-2">
                    Edit
                  </x-button>
                  <x-danger-button wire:click="confirmDeletion({{ $h->id }})">
                    Hapus
                  </x-danger-button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  Belum ada data hari libur.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $holidays->links() }}
      </div>
    </div>
  </div>

  <!-- Create / Edit Modal -->
  <x-dialog-modal wire:model="creating">
    <x-slot name="title">
      Tambah Hari Libur Baru
    </x-slot>

    <form wire:submit.prevent="create">
      <x-slot name="content">
        <div>
          <x-label for="name" value="Nama Hari Libur" />
          <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name" placeholder="Misal: Tahun Baru Islam / Rolling Shift A" />
          <x-input-error for="name" class="mt-1" />
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <x-label for="date" value="Tanggal Libur" />
            <x-input id="date" type="date" class="mt-1 block w-full" wire:model="date" />
            <x-input-error for="date" class="mt-1" />
          </div>

          <div>
            <x-label for="type" value="Tipe Libur" />
            <select id="type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <option value="general">Nasional / Umum (Seluruh Karyawan)</option>
              <option value="division">Khusus Divisi tertentu</option>
              <option value="custom">Custom Multi-User (Rolling User)</option>
            </select>
            <x-input-error for="type" class="mt-1" />
          </div>
        </div>

        @if ($type === 'division')
          <div class="mt-4">
            <x-label for="division_id" value="Pilih Divisi" />
            <select id="division_id" wire:model="division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <option value="">-- Pilih Divisi --</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </select>
            <x-input-error for="division_id" class="mt-1" />
          </div>
        @endif

        @if ($type === 'custom')
          <div class="mt-4">
            <x-label value="Pilih Karyawan Terpilih" />
            <div class="mt-2 max-h-48 overflow-y-auto rounded-md border border-gray-300 p-3 dark:border-gray-700 dark:bg-gray-900">
              @foreach ($users as $u)
                <label class="mb-1.5 flex items-center">
                  <input type="checkbox" wire:model="user_ids" value="{{ $u->id }}" class="rounded text-indigo-600 focus:ring-indigo-500">
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $u->name }} ({{ $u->division->name ?? 'Tanpa Divisi' }})</span>
                </label>
              @endforeach
            </div>
            <x-input-error for="user_ids" class="mt-1" />
          </div>
        @endif

        <div class="mt-4">
          <x-label for="description" value="Keterangan (Opsional)" />
          <x-input id="description" type="text" class="mt-1 block w-full" wire:model="description" />
          <x-input-error for="description" class="mt-1" />
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
          Batal
        </x-secondary-button>

        <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled">
          Simpan Hari Libur
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <!-- Edit Modal -->
  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Hari Libur
    </x-slot>

    <form wire:submit.prevent="update">
      <x-slot name="content">
        <div>
          <x-label for="edit_name" value="Nama Hari Libur" />
          <x-input id="edit_name" type="text" class="mt-1 block w-full" wire:model="name" />
          <x-input-error for="name" class="mt-1" />
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <x-label for="edit_date" value="Tanggal Libur" />
            <x-input id="edit_date" type="date" class="mt-1 block w-full" wire:model="date" />
            <x-input-error for="date" class="mt-1" />
          </div>

          <div>
            <x-label for="edit_type" value="Tipe Libur" />
            <select id="edit_type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <option value="general">Nasional / Umum (Seluruh Karyawan)</option>
              <option value="division">Khusus Divisi tertentu</option>
              <option value="custom">Custom Multi-User (Rolling User)</option>
            </select>
            <x-input-error for="type" class="mt-1" />
          </div>
        </div>

        @if ($type === 'division')
          <div class="mt-4">
            <x-label for="edit_division_id" value="Pilih Divisi" />
            <select id="edit_division_id" wire:model="division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <option value="">-- Pilih Divisi --</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </select>
            <x-input-error for="division_id" class="mt-1" />
          </div>
        @endif

        @if ($type === 'custom')
          <div class="mt-4">
            <x-label value="Pilih Karyawan Terpilih" />
            <div class="mt-2 max-h-48 overflow-y-auto rounded-md border border-gray-300 p-3 dark:border-gray-700 dark:bg-gray-900">
              @foreach ($users as $u)
                <label class="mb-1.5 flex items-center">
                  <input type="checkbox" wire:model="user_ids" value="{{ $u->id }}" class="rounded text-indigo-600 focus:ring-indigo-500">
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $u->name }} ({{ $u->division->name ?? 'Tanpa Divisi' }})</span>
                </label>
              @endforeach
            </div>
            <x-input-error for="user_ids" class="mt-1" />
          </div>
        @endif

        <div class="mt-4">
          <x-label for="edit_description" value="Keterangan (Opsional)" />
          <x-input id="edit_description" type="text" class="mt-1 block w-full" wire:model="description" />
          <x-input-error for="description" class="mt-1" />
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
          Batal
        </x-secondary-button>

        <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled">
          Perbarui Hari Libur
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <!-- Delete Confirmation Modal -->
  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Hari Libur
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus data hari libur ini?
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
