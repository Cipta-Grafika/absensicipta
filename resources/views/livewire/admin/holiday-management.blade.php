<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Hari Libur
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

<div class="py-0 sm:py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="w-full sm:px-6 lg:px-8">
    <div class="overflow-hidden bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 sm:rounded-2xl p-6">

      <!-- Interactive Monthly Holiday Calendar Box -->
      <div class="mb-6 rounded-2xl bg-gray-50/80 dark:bg-gray-900/60 p-4 sm:p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center justify-between border-b border-gray-200/80 pb-4 dark:border-gray-700">
          <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <x-heroicon-o-calendar-days class="h-5 w-5 text-sky-600 dark:text-sky-400" />
              Kalender Hari Libur {{ \Carbon\Carbon::parse($calendar_month)->isoFormat('MMMM YYYY') }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              Klik pada tanggal di bawah untuk menginput hari libur (Nasional, Divisi, atau Custom Multi-User)
            </p>
          </div>
          <div class="flex items-center gap-2">
            <x-label for="calendar_month_filter" value="Pilih Bulan" class="whitespace-nowrap text-xs font-semibold"></x-label>
            <x-input type="month" name="calendar_month_filter" id="calendar_month_filter" wire:model.live="calendar_month" class="text-xs py-1.5" />
          </div>
        </div>

        <div class="mt-4 overflow-x-auto">
          <div class="min-w-[650px]">
            <!-- Days Header -->
            <div class="mb-2 grid grid-cols-7 text-center text-xs font-bold text-gray-600 dark:text-gray-300">
              <div class="py-1">Sen</div>
              <div class="py-1">Sel</div>
              <div class="py-1">Rab</div>
              <div class="py-1">Kam</div>
              <div class="py-1">Jum</div>
              <div class="py-1">Sab</div>
              <div class="py-1 text-red-500">Min</div>
            </div>

            <!-- Dates Grid -->
            <div class="grid grid-cols-7 gap-1.5">
              <!-- Leading Empty Cells -->
              @if ($calStart->dayOfWeekIso > 1)
                @foreach (range(1, $calStart->dayOfWeekIso - 1) as $i)
                  <div class="h-16 rounded-lg bg-gray-100/50 dark:bg-gray-800/40 border border-transparent"></div>
                @endforeach
              @endif

              <!-- Date Cells -->
              @foreach ($calDates as $dateObj)
                @php
                  $dateStr = $dateObj->format('Y-m-d');
                  $isSunday = $dateObj->isSunday();
                  $isToday = $dateObj->isToday();
                  $dateHolidays = $monthHolidays->get($dateStr) ?? collect();
                  $hasHolidays = $dateHolidays->count() > 0;
                @endphp

                <button type="button"
                  wire:click="handleCalendarDateClick('{{ $dateStr }}')"
                  x-data x-on:click="$el.blur()"
                  class="group relative flex h-16 flex-col justify-between rounded-xl border p-2 text-left transition-all duration-150
                         {{ $hasHolidays 
                            ? 'border-sky-200 bg-sky-50/60 dark:border-sky-800/80 dark:bg-sky-950/40 hover:border-sky-400 hover:shadow-md' 
                            : 'border-gray-200 bg-white dark:border-gray-700/80 dark:bg-gray-800/80 hover:border-sky-300 hover:bg-gray-50 dark:hover:bg-gray-700/60' }}">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold {{ $isSunday ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }}">
                      {{ $dateObj->format('d') }}
                    </span>
                    @if ($isToday)
                      <span class="rounded bg-sky-500 px-1 py-0.2 text-[9px] font-bold text-white">Hari ini</span>
                    @elseif ($hasHolidays)
                      <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    @endif
                  </div>

                  <div class="mt-1 flex flex-wrap gap-1">
                    @forelse($dateHolidays as $dh)
                      @if($dh->type === 'general')
                        <span class="inline-flex items-center rounded-full bg-purple-100 px-1.5 py-0.5 text-[10px] font-bold text-purple-800 dark:bg-purple-900/80 dark:text-purple-200 truncate max-w-[70px]" title="{{ $dh->name }}">
                          Nasional
                        </span>
                      @elseif($dh->type === 'division')
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-800 dark:bg-blue-900/80 dark:text-blue-200 truncate max-w-[70px]" title="{{ $dh->name }}">
                          Divisi
                        </span>
                      @else
                        <span class="inline-flex items-center rounded-full bg-teal-100 px-1.5 py-0.5 text-[10px] font-bold text-teal-800 dark:bg-teal-900/80 dark:text-teal-200 truncate max-w-[70px]" title="{{ $dh->name }}">
                          {{ $dh->users->count() }} User
                        </span>
                      @endif
                    @empty
                      <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 group-hover:text-sky-600 dark:group-hover:text-sky-400">
                        + Libur
                      </span>
                    @endforelse
                  </div>
                </button>
              @endforeach

              <!-- Trailing Empty Cells -->
              @if ($calEnd->dayOfWeekIso < 7)
                @foreach (range($calEnd->dayOfWeekIso + 1, 7) as $i)
                  <div class="h-16 rounded-lg bg-gray-100/50 dark:bg-gray-800/40 border border-transparent"></div>
                @endforeach
              @endif
            </div>
          </div>
        </div>
      </div>

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

      <!-- Table Header Bar: Title & Per Page Selector -->
      <div class="mb-4 flex flex-col items-center justify-between gap-4 sm:flex-row border-t border-gray-200/80 pt-5 dark:border-gray-700">
        <div>
          <h4 class="text-base font-bold text-gray-900 dark:text-white">
            Riwayat Hari Libur {{ \Carbon\Carbon::parse($calendar_month)->isoFormat('MMMM YYYY') }}
          </h4>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Menampilkan data hari libur untuk bulan terpilih.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <label for="perPage_hol" class="text-xs font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">Tampilkan:</label>
          <select wire:model.live="perPage" id="perPage_hol" class="w-24 truncate rounded-md border border-gray-300 bg-gray-50 py-1 pl-2 pr-7 text-xs text-gray-900 focus:border-sky-500 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all">Semua</option>
          </select>
        </div>
      </div>

      <!-- Holiday Table -->
      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
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
                    <span class="inline-flex rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold leading-5 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                      Nasional / Umum
                    </span>
                  @elseif ($h->type === 'division')
                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      Divisi
                    </span>
                  @else
                    <span class="inline-flex rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold leading-5 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
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
                  <div class="flex justify-end gap-2">
                    <button type="button" wire:click="edit({{ $h->id }})" title="Edit Hari Libur"
                      class="inline-flex items-center justify-center rounded-md border border-transparent bg-sky-500 px-2 py-1.5 text-white shadow-sm hover:bg-sky-600 focus:outline-none transition-colors duration-150">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button type="button" wire:click="confirmDeletion({{ $h->id }})" title="Hapus Hari Libur"
                      class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-2 py-1.5 text-white shadow-sm hover:bg-red-700 focus:outline-none transition-colors duration-150">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
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

  <!-- Calendar Date Holiday Modal -->
  <x-dialog-modal wire:model="calendarDateModalOpen" maxWidth="2xl">
    <x-slot name="title">
      Tambah Hari Libur Per Tanggal
    </x-slot>

    <form wire:submit.prevent="submitCalendarDateHoliday">
      <x-slot name="content">
        @if ($selected_calendar_date)
          <div class="mb-4 rounded-xl bg-sky-50 p-4 border border-sky-200 dark:bg-sky-950/50 dark:border-sky-800 flex items-center justify-between">
            <div>
              <span class="text-xs font-bold text-sky-700 dark:text-sky-300 uppercase tracking-wider block">Tanggal Hari Libur</span>
              <span class="text-base font-extrabold text-gray-900 dark:text-white mt-0.5 block">
                {{ $selectedDateDisplay }}
              </span>
            </div>
            <span class="inline-flex items-center rounded-md bg-sky-100 dark:bg-sky-900/80 px-2.5 py-1 text-xs font-bold text-sky-700 dark:text-sky-300">
              Input Dari Kalender
            </span>
          </div>
        @endif

        <div>
          <x-label for="cal_name" value="Nama Hari Libur" />
          <x-input id="cal_name" type="text" class="mt-1 block w-full" wire:model="name" placeholder="Misal: Tahun Baru Islam / Cuti Bersama PT" />
          <x-input-error for="name" class="mt-1" />
        </div>

        <div class="mt-4">
          <x-label for="cal_type" value="Pilih Jenis / Tipe Libur" />
          <select id="cal_type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-sky-500 focus:ring-sky-500">
            <option value="general">Nasional / Umum (Seluruh Karyawan)</option>
            <option value="division">Khusus Divisi tertentu</option>
            <option value="custom">Custom Multi-User (Pilih Karyawan Terpilih)</option>
          </select>
          <x-input-error for="type" class="mt-1" />
        </div>

        @if ($type === 'division')
          <div class="mt-4">
            <x-label for="cal_division_id" value="Pilih Divisi" />
            <select id="cal_division_id" wire:model="division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-sky-500 focus:ring-sky-500">
              <option value="">-- Pilih Divisi --</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </select>
            <x-input-error for="division_id" class="mt-1" />
          </div>
        @endif

        @if ($type === 'custom')
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
              <x-label value="Pilih Karyawan Terpilih (Multi-Select)" />
              <span class="text-xs font-semibold text-sky-600 dark:text-sky-400">
                <span x-text="($wire.user_ids || []).length"></span> Karyawan Dipilih
              </span>
            </div>

            <!-- Search Input Field -->
            <div class="relative mb-2">
              <input type="text" x-model="userSearch" placeholder="Cari nama karyawan / NIP..."
                class="w-full rounded-md border border-gray-300 bg-gray-50 py-1.5 pl-8 pr-3 text-xs text-gray-900 focus:border-sky-500 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
              <svg class="pointer-events-none absolute inset-y-0 left-2.5 my-auto h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Select All Option Bar -->
            <div class="mb-2 flex items-center justify-between rounded-md bg-gray-100 dark:bg-gray-800 px-3 py-1.5 border border-gray-200 dark:border-gray-700">
              <label class="inline-flex items-center text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                <input type="checkbox"
                  @change="toggleAll($event, {{ json_encode($users->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'nip' => $u->nip ?? ''])) }})"
                  class="rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-900">
                <span class="ml-2">Pilih Semua Karyawan</span>
              </label>
            </div>

            <!-- Employee Checkbox List -->
            <div class="max-h-48 overflow-y-auto rounded-md border border-gray-300 p-3 dark:border-gray-700 dark:bg-gray-900">
              @foreach ($users as $u)
                <label class="mb-1.5 flex items-center cursor-pointer"
                  x-show="!userSearch || '{{ strtolower(addslashes($u->name . ' ' . ($u->nip ?? ''))) }}'.includes(userSearch.toLowerCase())">
                  <input type="checkbox" wire:model="user_ids" value="{{ $u->id }}" class="rounded text-sky-600 focus:ring-sky-500">
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    {{ $u->name }} <span class="text-xs text-gray-400">({{ $u->division->name ?? 'Tanpa Divisi' }})</span>
                  </span>
                </label>
              @endforeach
            </div>
            <x-input-error for="user_ids" class="mt-1" />
          </div>
        @endif

        <div class="mt-4">
          <x-label for="cal_description" value="Keterangan (Opsional)" />
          <x-input id="cal_description" type="text" class="mt-1 block w-full" wire:model="description" placeholder="Misal: Keputusan Manajemen" />
          <x-input-error for="description" class="mt-1" />
        </div>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('calendarDateModalOpen')" wire:loading.attr="disabled">
          Batal
        </x-secondary-button>

        <x-button class="ml-2 !bg-sky-600 hover:!bg-sky-700" wire:click="submitCalendarDateHoliday" wire:loading.attr="disabled">
          Simpan Hari Libur
        </x-button>
      </x-slot>
    </form>
  </x-dialog-modal>

  <!-- Create Modal -->
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
            <select id="type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-sky-500 focus:ring-sky-500">
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
            <select id="division_id" wire:model="division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-sky-500 focus:ring-sky-500">
              <option value="">-- Pilih Divisi --</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </select>
            <x-input-error for="division_id" class="mt-1" />
          </div>
        @endif

        @if ($type === 'custom')
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
              <x-label value="Pilih Karyawan Terpilih (Multi-Select)" />
              <span class="text-xs font-semibold text-sky-600 dark:text-sky-400">
                <span x-text="($wire.user_ids || []).length"></span> Karyawan Dipilih
              </span>
            </div>

            <!-- Search Input Field -->
            <div class="relative mb-2">
              <input type="text" x-model="userSearch" placeholder="Cari nama karyawan / NIP..."
                class="w-full rounded-md border border-gray-300 bg-gray-50 py-1.5 pl-8 pr-3 text-xs text-gray-900 focus:border-sky-500 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
              <svg class="pointer-events-none absolute inset-y-0 left-2.5 my-auto h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Select All Option Bar -->
            <div class="mb-2 flex items-center justify-between rounded-md bg-gray-100 dark:bg-gray-800 px-3 py-1.5 border border-gray-200 dark:border-gray-700">
              <label class="inline-flex items-center text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                <input type="checkbox"
                  @change="toggleAll($event, {{ json_encode($users->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'nip' => $u->nip ?? ''])) }})"
                  class="rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-900">
                <span class="ml-2">Pilih Semua Karyawan</span>
              </label>
            </div>

            <!-- Employee Checkbox List -->
            <div class="max-h-48 overflow-y-auto rounded-md border border-gray-300 p-3 dark:border-gray-700 dark:bg-gray-900">
              @foreach ($users as $u)
                <label class="mb-1.5 flex items-center cursor-pointer"
                  x-show="!userSearch || '{{ strtolower(addslashes($u->name . ' ' . ($u->nip ?? ''))) }}'.includes(userSearch.toLowerCase())">
                  <input type="checkbox" wire:model="user_ids" value="{{ $u->id }}" class="rounded text-sky-600 focus:ring-sky-500">
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    {{ $u->name }} <span class="text-xs text-gray-400">({{ $u->division->name ?? 'Tanpa Divisi' }})</span>
                  </span>
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

        <x-button class="ml-2 !bg-sky-600 hover:!bg-sky-700" wire:click="create" wire:loading.attr="disabled">
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
            <select id="edit_type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-sky-500 focus:ring-sky-500">
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
            <select id="edit_division_id" wire:model="division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-sky-500 focus:ring-sky-500">
              <option value="">-- Pilih Divisi --</option>
              @foreach ($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </select>
            <x-input-error for="division_id" class="mt-1" />
          </div>
        @endif

        @if ($type === 'custom')
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
              <x-label value="Pilih Karyawan Terpilih (Multi-Select)" />
              <span class="text-xs font-semibold text-sky-600 dark:text-sky-400">
                <span x-text="($wire.user_ids || []).length"></span> Karyawan Dipilih
              </span>
            </div>

            <!-- Search Input Field -->
            <div class="relative mb-2">
              <input type="text" x-model="userSearch" placeholder="Cari nama karyawan / NIP..."
                class="w-full rounded-md border border-gray-300 bg-gray-50 py-1.5 pl-8 pr-3 text-xs text-gray-900 focus:border-sky-500 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
              <svg class="pointer-events-none absolute inset-y-0 left-2.5 my-auto h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Select All Option Bar -->
            <div class="mb-2 flex items-center justify-between rounded-md bg-gray-100 dark:bg-gray-800 px-3 py-1.5 border border-gray-200 dark:border-gray-700">
              <label class="inline-flex items-center text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                <input type="checkbox"
                  @change="toggleAll($event, {{ json_encode($users->map(fn($u) => ['id' => (string)$u->id, 'name' => $u->name, 'nip' => $u->nip ?? ''])) }})"
                  class="rounded border-gray-300 text-sky-600 focus:ring-sky-500 dark:border-gray-600 dark:bg-gray-900">
                <span class="ml-2">Pilih Semua Karyawan</span>
              </label>
            </div>

            <!-- Employee Checkbox List -->
            <div class="max-h-48 overflow-y-auto rounded-md border border-gray-300 p-3 dark:border-gray-700 dark:bg-gray-900">
              @foreach ($users as $u)
                <label class="mb-1.5 flex items-center cursor-pointer"
                  x-show="!userSearch || '{{ strtolower(addslashes($u->name . ' ' . ($u->nip ?? ''))) }}'.includes(userSearch.toLowerCase())">
                  <input type="checkbox" wire:model="user_ids" value="{{ $u->id }}" class="rounded text-sky-600 focus:ring-sky-500">
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    {{ $u->name }} <span class="text-xs text-gray-400">({{ $u->division->name ?? 'Tanpa Divisi' }})</span>
                  </span>
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

        <x-button class="ml-2 !bg-sky-600 hover:!bg-sky-700" wire:click="update" wire:loading.attr="disabled">
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
