<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <div>
      <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
        Pinjaman Karyawan
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
        Kelola Pengajuan Pinjaman & Angsuran Kasbon Karyawan
      </p>
    </div>
    <div class="flex items-center gap-2">
      <x-button type="button" x-data @click.prevent="$dispatch('open-create-modal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sm:mr-1.5 h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="hidden sm:inline">Buat Pinjaman Baru</span>
      </x-button>
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
      <x-slot name="title">Filter Pinjaman</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('status', ''); $set('division', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="status_filter" value="Status Pinjaman" class="mb-1"></x-label>
            <x-select id="status_filter" class="w-full" wire:model.live="status">
              <option value="">Semua Status</option>
              <option value="pending">Menunggu (Pending)</option>
              <option value="approved">Disetujui</option>
              <option value="active">Aktif (Sedang Berjalan)</option>
              <option value="paid_off">Lunas</option>
            </x-select>
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
            <x-input type="text" class="block w-full pl-10 pr-10" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Nama Karyawan, NIP..." />
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
        <table class="w-full min-w-[850px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
          <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <tr>
              <th scope="col" class="px-4 py-3 min-w-[150px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tgl Pengajuan</th>
              <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
              <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Pinjaman</th>
              <th scope="col" class="px-4 py-3 min-w-[100px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tenor</th>
              <th scope="col" class="px-4 py-3 min-w-[160px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sisa Saldo</th>
              <th scope="col" class="px-4 py-3 min-w-[120px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
              <th scope="col" class="px-4 py-3 min-w-[100px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            @forelse($loans as $loan)
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ $loan->created_at->format('d M Y') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-300">
                  <div class="font-medium">{{ $loan->user->name ?? '-' }}</div>
                  <div class="text-gray-500 dark:text-gray-400">{{ $loan->user->nip ?? '-' }}</div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-900 dark:text-gray-300">
                  Rp {{ number_format($loan->loan_amount, 0, ',', '.') }}
                  <div class="text-xs text-gray-400 mt-1">Cicilan: Rp {{ number_format($loan->installment_amount, 0, ',', '.') }}/bln</div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-900 dark:text-gray-300">
                  {{ $loan->tenor_months }} Bln
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-semibold {{ $loan->remaining_balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                  Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  @if($loan->status == 'pending')
                    <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Pending</span>
                  @elseif($loan->status == 'paid_off')
                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Lunas</span>
                  @else
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">Aktif</span>
                  @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-center text-sm font-medium">
                  @if(in_array($loan->status, ['approved', 'active']))
                  <button type="button" wire:click="markAsPaidOff('{{ $loan->id }}')" onclick="confirm('Yakin ingin menandai pinjaman ini sebagai LUNAS?') || event.stopImmediatePropagation()" class="text-green-600 hover:text-green-900 ml-2" title="Tandai Lunas">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 inline">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                  </button>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  Belum ada data pinjaman karyawan.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($loans->hasPages())
        <div class="mt-4">
          {{ $loans->links() }}
        </div>
      @endif
    </div>
  </div>

  <!-- Modal Buat Pinjaman -->
  <x-dialog-modal wire:model.live="createModalOpen" maxWidth="lg">
    <x-slot name="title">
      Form Pengajuan Pinjaman Karyawan (Kasbon)
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-6">
        <div>
          <x-label for="user_id" value="Pilih Karyawan" />
          <x-select id="user_id" class="mt-1 block w-full" wire:model="user_id">
            <option value="">-- Pilih Karyawan --</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->nip }})</option>
            @endforeach
          </x-select>
          <x-input-error for="user_id" class="mt-2" />
        </div>

        <div>
          <x-label for="loan_amount" value="Total Nominal Pinjaman (Rp)" />
          <x-input id="loan_amount" type="number" class="mt-1 block w-full" wire:model.live.debounce.500ms="loan_amount" placeholder="Contoh: 1000000" />
          <x-input-error for="loan_amount" class="mt-2" />
        </div>

        <div>
          <x-label for="tenor_months" value="Tenor (Bulan)" />
          <x-input id="tenor_months" type="number" class="mt-1 block w-full" wire:model.live.debounce.500ms="tenor_months" placeholder="Berapa bulan dicicil" />
          <x-input-error for="tenor_months" class="mt-2" />
        </div>

        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600">
          <p class="text-sm text-gray-500 dark:text-gray-300">Estimasi Cicilan per Bulan (Otomatis potong gaji):</p>
          <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">Rp {{ number_format($installment_amount, 0, ',', '.') }}</p>
        </div>

        <div>
          <x-label for="description" value="Keterangan / Alasan" />
          <x-input id="description" type="text" class="mt-1 block w-full" wire:model="description" placeholder="Contoh: Biaya pendidikan" />
          <x-input-error for="description" class="mt-2" />
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeCreateModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-green-600 hover:bg-green-700" wire:click="storeLoan" wire:loading.attr="disabled">
        Simpan & Setujui Pinjaman
      </x-button>
    </x-slot>
  </x-dialog-modal>
</div>

<script>
  window.addEventListener('open-create-modal', event => {
    @this.openCreateModal();
  });
</script>
