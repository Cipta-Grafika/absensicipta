<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Approval Lembur') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="Livewire.dispatch('print-report')">
        <x-heroicon-o-printer class="mr-1.5 h-4 w-4 text-sky-500" />
        Cetak
      </x-secondary-button>
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="mr-1.5 h-4 w-4 text-sky-500" />
        Filter
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
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
        <x-input type="text" class="block w-full pl-10 pr-10" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search"
          placeholder="{{ __('Cari nama atau NIP') }}" />
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

  <x-filter-sidebar maxWidth="sm">
    <x-slot name="title">Filter Lemburan</x-slot>
    <x-slot name="actions">
      <button type="button" wire:click="$set('month', ''); $set('week', ''); $set('date', ''); $set('division', ''); $set('jobTitle', ''); $set('statusFilter', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
      </button>
    </x-slot>
    
    <x-slot name="content">
      <div class="flex flex-col gap-6">
        <div>
          <x-label for="statusFilter" value="Status" class="mb-1"></x-label>
          <x-select id="statusFilter" class="w-full" wire:model.live="statusFilter">
            <option value="">Semua</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </x-select>
        </div>
        <hr class="dark:border-gray-700">
        <div>
          <x-label for="month_filter" value="Per Bulan" class="mb-1"></x-label>
          <x-input type="month" name="month_filter" id="month_filter" class="w-full" wire:model.live="month" />
        </div>
        <div>
          <x-label for="week_filter" value="Per Minggu" class="mb-1"></x-label>
          <x-input type="week" name="week_filter" id="week_filter" class="w-full" wire:model.live="week" />
        </div>
        <div>
          <x-label for="day_filter" value="Per Hari" class="mb-1"></x-label>
          <x-input type="date" name="day_filter" id="day_filter" class="w-full" wire:model.live="date" />
        </div>
        <hr class="dark:border-gray-700">
        @if (Auth::user()->isSuperadmin)
        <div>
          <x-label for="division" value="Pilih Divisi" class="mb-1"></x-label>
          <x-select id="division" class="w-full" wire:model.live="division">
            <option value="">{{ __('Select Division') }}</option>
            @foreach (App\Models\Division::all() as $_division)
              <option value="{{ $_division->id }}" {{ $_division->id == $division ? 'selected' : '' }}>
                {{ $_division->name }}
              </option>
            @endforeach
          </x-select>
        </div>
        @endif
        <div>
          <x-label for="jobTitle" value="Pilih Jabatan" class="mb-1"></x-label>
          <x-select id="jobTitle" class="w-full" wire:model.live="jobTitle">
            <option value="">{{ __('Select Job Title') }}</option>
            @foreach (App\Models\JobTitle::all() as $_jobTitle)
              <option value="{{ $_jobTitle->id }}" {{ $_jobTitle->id == $jobTitle ? 'selected' : '' }}>
                {{ $_jobTitle->name }}
              </option>
            @endforeach
          </x-select>
        </div>
      </div>
    </x-slot>
  </x-filter-sidebar>

  <div class="overflow-x-scroll">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            Karyawan
          </th>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[200px]">
            Waktu Lembur
          </th>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[200px]">
            Alasan
          </th>
          <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[120px]">
            Status
          </th>
          <th scope="col" class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 min-w-[160px]">
            Aksi
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @forelse ($approvals as $approval)
          <tr>
            <td class="whitespace-nowrap px-2 py-4">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <img class="h-10 w-10 rounded-full" src="{{ $approval->employee->profile_photo_url }}" alt="">
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $approval->employee->name ?? 'Unknown' }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $approval->employee->nip ?? '-' }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-2 py-4 text-sm text-gray-900 dark:text-gray-300 min-w-[200px]">
              <div class="mb-1 text-xs text-gray-500">Tanggal: <span class="font-medium text-gray-900 dark:text-gray-300">{{ \Carbon\Carbon::parse($approval->overtime_date)->format('d M Y') }}</span></div>
              <div>{{ \Carbon\Carbon::parse($approval->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($approval->end_time)->format('H:i') }}</div>
              <div class="font-bold text-gray-700 dark:text-gray-200">Durasi: {{ $approval->duration_hours }} Jam</div>
            </td>
            <td class="px-2 py-4 text-sm text-gray-900 dark:text-gray-300 min-w-[200px]">
              {{ Str::limit($approval->reason, 100) }}
            </td>
            <td class="whitespace-nowrap px-2 py-4 text-sm">
              @if($approval->status == 'pending')
                  <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                      Pending
                  </span>
              @elseif($approval->status == 'approved')
                  <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                      Approved
                  </span>
                  <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">by {{ $approval->approver->name ?? '-' }}</div>
              @else
                  <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">
                      Rejected
                  </span>
                  <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">by {{ $approval->approver->name ?? '-' }}</div>
              @endif
            </td>
            <td class="whitespace-nowrap px-2 py-4 text-center text-sm font-medium">
              <div class="flex items-center justify-center space-x-2">
                @if($approval->status == 'pending')
                    <button wire:click="approve({{ $approval->id }})" class="rounded-md border border-transparent bg-green-600 px-2 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none" title="Setujui">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                    <button wire:click="reject({{ $approval->id }})" class="rounded-md border border-transparent bg-red-600 px-2 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none" title="Tolak">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
                
                @if(Auth::user()->isSuperadmin)
                    <button wire:click="confirmDelete({{ $approval->id }})" class="rounded-md border border-transparent bg-red-600 px-2 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none" title="Hapus Permanen">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-2 py-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
              Belum ada data pengajuan lembur.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  
  <div class="mt-4">
      {{ $approvals->links() }}
  </div>

  <!-- Delete Modal Confirmation -->
  @if($isDeleteModalOpen)
      <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black bg-opacity-75">
          <div class="relative w-full max-w-md p-4">
              <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
                  <div class="p-4 text-center md:p-5">
                      <svg class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                      </svg>
                      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">Apakah Anda yakin ingin menghapus data lembur ini secara permanen?</h3>
                      <button wire:click="deleteOvertime" type="button" class="inline-flex items-center rounded-lg bg-red-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800">
                          Ya, Hapus
                      </button>
                      <button wire:click="cancelDelete" type="button" class="ms-3 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">
                          Batal
                      </button>
                  </div>
              </div>
          </div>
      </div>
  @endif

      </div>
    </div>
  </div>
</div>
