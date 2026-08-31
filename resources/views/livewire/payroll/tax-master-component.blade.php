<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-sky-600 text-white shadow-md shadow-indigo-500/20">
        <x-heroicon-o-calculator class="h-6 w-6" />
      </div>
      <div>
        <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
          Master Pajak PPh 21
        </h2>
        <p class="text-xs text-gray-500 dark:text-gray-400">Manajemen Tarif Efektif Rata-rata (TER) dan Pajak Penghasilan Karyawan</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
      <x-button type="button" x-data @click.prevent="$dispatch('open-create-modal')" class="bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800">
        <x-heroicon-o-plus class="mr-1.5 h-4 w-4" />
        Tambah Tarif
      </x-button>
    </div>
  </div>
</x-slot>

<div class="pt-3.5 pb-6 sm:py-6" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true" @open-create-modal.window="$wire.openCreateModal()">
  <div class="w-full sm:px-6 lg:px-8">
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Pajak</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('categoryFilter', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-4">
          <div>
            <x-label for="category_filter" value="Kategori TER" class="mb-1"></x-label>
            <x-select id="category_filter" class="w-full" wire:model.live="categoryFilter">
              <option value="">Semua Kategori</option>
              @foreach ($categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
              @endforeach
            </x-select>
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <!-- Top Statistics Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div class="rounded-2xl border border-gray-100 bg-white/80 p-4 backdrop-blur-xl shadow-sm dark:border-gray-800 dark:bg-gray-800/80">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
            <x-heroicon-o-document-text class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Lapisan Tarif</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($totalTaxes, 0, ',', '.') }} Bracket</p>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white/80 p-4 backdrop-blur-xl shadow-sm dark:border-gray-800 dark:bg-gray-800/80">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
            <x-heroicon-o-calculator class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Rentang Persentase Tarif</p>
            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $minRate }}% - {{ $maxRate }}%</p>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white/80 p-4 backdrop-blur-xl shadow-sm dark:border-gray-800 dark:bg-gray-800/80">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400">
            <x-heroicon-o-shield-check class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Dasar Regulasi</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">PP 58/2023 / PMK 168/2023</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Container Table -->
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden p-4 sm:p-6 lg:p-8">
      
      <!-- Search & Filters Toolbar -->
      <div class="mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="relative w-full sm:w-80">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <x-input type="text" class="block w-full pl-10 pr-10" wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, tarif..." />
          @if ($search)
            <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          @endif
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
          @if($categoryFilter)
            <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
              Kategori: {{ $categoryFilter }}
              <button type="button" wire:click="$set('categoryFilter', '')" class="text-indigo-400 hover:text-indigo-600">&times;</button>
            </span>
          @endif
        </div>
      </div>

      <!-- Data Table -->
      <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[760px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
          <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <tr>
              <th scope="col" class="px-4 py-3 text-left font-bold tracking-wider">Kode</th>
              <th scope="col" class="px-4 py-3 text-left font-bold tracking-wider">Kategori</th>
              <th scope="col" class="px-4 py-3 text-left font-bold tracking-wider">Nama Lapisan Tarif</th>
              <th scope="col" class="px-4 py-3 text-left font-bold tracking-wider">Penghasilan Bruto (Min - Max)</th>
              <th scope="col" class="px-4 py-3 text-center font-bold tracking-wider">Tarif Pajak</th>
              <th scope="col" class="px-4 py-3 text-center font-bold tracking-wider">Karyawan</th>
              <th scope="col" class="px-4 py-3 text-center font-bold tracking-wider">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700/60 dark:bg-gray-800">
            @forelse ($taxes as $tax)
              <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors">
                <td class="whitespace-nowrap px-4 py-3 font-mono font-bold text-sky-600 dark:text-sky-400">
                  {{ $tax->code }}
                </td>
                <td class="whitespace-nowrap px-4 py-3">
                  <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                    {{ $tax->category }}
                  </span>
                </td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                  <div>{{ $tax->name }}</div>
                  @if($tax->description)
                    <div class="text-[11px] font-normal text-gray-400">{{ $tax->description }}</div>
                  @endif
                </td>
                <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-600 dark:text-gray-300">
                  {{ $tax->formatted_range }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center">
                  <span class="inline-flex items-center rounded-lg px-2.5 py-1 font-mono text-xs font-bold {{ $tax->rate_percentage == 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : ($tax->rate_percentage < 10 ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300') }}">
                    {{ $tax->formatted_rate }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center font-mono">
                  <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    {{ $tax->employee_salaries_count }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button wire:click="edit('{{ $tax->id }}')" class="rounded-lg bg-sky-50 p-1.5 text-sky-600 hover:bg-sky-100 hover:text-sky-700 dark:bg-sky-950/50 dark:text-sky-400 dark:hover:bg-sky-900/60" title="Edit">
                      <x-heroicon-o-pencil-square class="h-4 w-4" />
                    </button>
                    <button wire:click="confirmDelete('{{ $tax->id }}')" class="rounded-lg bg-rose-50 p-1.5 text-rose-600 hover:bg-rose-100 hover:text-rose-700 dark:bg-rose-950/50 dark:text-rose-400 dark:hover:bg-rose-900/60" title="Hapus">
                      <x-heroicon-o-trash class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-4 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                  Tidak ada data tarif pajak PPh 21 ditemukan.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $taxes->links() }}
      </div>

    </div>
  </div>

  <!-- Modal Create / Edit -->
  <x-dialog-modal wire:model.live="isModalOpen">
    <x-slot name="title">
      {{ $tax_id ? __('Edit Tarif Pajak PPh 21') : __('Tambah Tarif Pajak PPh 21 (TER)') }}
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <x-label for="category" value="{{ __('Kategori TER') }}" />
          <x-input id="category" type="text" class="mt-1 block w-full" wire:model="category" placeholder="Contoh: TER A, TER B, TER C" />
          <x-input-error for="category" class="mt-2" />
        </div>

        <div>
          <x-label for="code" value="{{ __('Kode Tarif') }}" />
          <x-input id="code" type="text" class="mt-1 block w-full font-mono uppercase" wire:model="code" placeholder="Contoh: TER-A-01" />
          <x-input-error for="code" class="mt-2" />
        </div>

        <div>
          <x-label for="min_gross_income" value="{{ __('Penghasilan Bruto Minimal (Rp)') }}" />
          <x-input id="min_gross_income" type="number" class="mt-1 block w-full" wire:model="min_gross_income" min="0" />
          <x-input-error for="min_gross_income" class="mt-2" />
        </div>

        <div>
          <x-label for="max_gross_income" value="{{ __('Penghasilan Bruto Maksimal (Rp)') }}" />
          <x-input id="max_gross_income" type="number" class="mt-1 block w-full" wire:model="max_gross_income" placeholder="Kosongkan jika tak terhingga / di atas min" min="0" />
          <p class="mt-1 text-[11px] text-gray-500">Biarkan kosong jika lapisan tarif ini berlaku untuk nominal di atas batas minimal.</p>
          <x-input-error for="max_gross_income" class="mt-2" />
        </div>

        <div>
          <x-label for="rate_percentage" value="{{ __('Persentase Tarif (%)') }}" />
          <div class="relative mt-1 rounded-md shadow-sm">
            <x-input id="rate_percentage" type="number" step="0.01" class="block w-full pr-8" wire:model="rate_percentage" min="0" max="100" />
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
              <span class="text-gray-500 sm:text-sm font-bold">%</span>
            </div>
          </div>
          <x-input-error for="rate_percentage" class="mt-2" />
        </div>

        <div class="flex items-end">
          <button type="button" wire:click="autoGenerateName" class="inline-flex w-full items-center justify-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
            <x-heroicon-o-sparkles class="mr-1.5 h-4 w-4" />
            Otomatis Susun Nama Lapisan
          </button>
        </div>

        <div class="sm:col-span-2">
          <x-label for="name" value="{{ __('Nama / Label Lapisan Tarif') }}" />
          <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name" placeholder="Contoh: TER A - s.d Rp 5.400.000 (0%)" />
          <x-input-error for="name" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
          <x-label for="description" value="{{ __('Keterangan / Dasar Regulasi (Opsional)') }}" />
          <x-input id="description" type="text" class="mt-1 block w-full" wire:model="description" placeholder="Contoh: Tarif Efektif Bulanan Kategori A Karyawan (PP 58/2023)" />
          <x-input-error for="description" class="mt-2" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeModal" wire:loading.attr="disabled">
        {{ __('Batal') }}
      </x-secondary-button>

      <x-button class="ms-3 bg-indigo-600 hover:bg-indigo-700" wire:click="save" wire:loading.attr="disabled">
        {{ __('Simpan') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Delete Confirmation Modal -->
  <x-confirmation-modal wire:model.live="isDeleteModalOpen">
    <x-slot name="title">
      {{ __('Hapus Tarif Pajak') }}
    </x-slot>

    <x-slot name="content">
      @if($taxToDelete)
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Apakah Anda yakin ingin menghapus lapisan tarif <strong>{{ $taxToDelete->code }} - {{ $taxToDelete->name }}</strong>?
        </p>
        @if($taxToDelete->employee_salaries_count > 0)
          <div class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
            <strong>Peringatan:</strong> Tarif ini saat ini digunakan oleh <strong>{{ $taxToDelete->employee_salaries_count }}</strong> karyawan. Jika dihapus, relasi pajak pada master gaji karyawan tersebut akan menjadi kosong (null).
          </div>
        @endif
      @endif
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeDeleteModal" wire:loading.attr="disabled">
        {{ __('Batal') }}
      </x-secondary-button>

      <x-danger-button class="ms-3" wire:click="delete" wire:loading.attr="disabled">
        {{ __('Hapus') }}
      </x-danger-button>
    </x-slot>
  </x-confirmation-modal>
</div>

<script>
  window.addEventListener('open-create-modal', event => {
    if (window.Livewire) {
      @this.openCreateModal();
    }
  });
</script>
