<x-slot name="header">
  <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Program Syirkah & Nominal Sukarela
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola paket syirkah dan kustomisasi nominal syirkah sukarela (override) per karyawan</p>
    </div>
    @if($activeTab === 'master')
      <x-button x-data @click="$dispatch('open-saving-modal')" class="bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white cursor-pointer">
        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Tambah Paket Baru
      </x-button>
    @endif
  </div>
</x-slot>

<div class="pt-3.5 pb-6 sm:py-6" x-data @open-saving-modal.window="$wire.openModal()">
  <div class="w-full sm:px-6 lg:px-8">
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-t border-b sm:border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 rounded-none sm:rounded-2xl overflow-hidden p-4 sm:p-6 lg:p-8">
      
      <!-- TAB NAVIGATION -->
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-3">
        <div class="flex items-center gap-2">
          <button type="button" 
                  wire:click="setTab('master')" 
                  class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs sm:text-sm font-semibold transition-all cursor-pointer {{ $activeTab === 'master' ? 'bg-sky-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-750 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>Master Program Syirkah</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $activeTab === 'master' ? 'bg-white/20 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200' }}">
              {{ $savings->total() }}
            </span>
          </button>

          <button type="button" 
                  wire:click="setTab('members')" 
                  class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs sm:text-sm font-semibold transition-all cursor-pointer {{ $activeTab === 'members' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-750 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Kustomisasi Sukarela Karyawan</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $activeTab === 'members' ? 'bg-white/20 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200' }}">
              {{ $totalMembers }} Karyawan
            </span>
          </button>
        </div>

        @if($activeTab === 'members')
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 px-2.5 py-1 text-xs font-medium text-amber-800 dark:text-amber-300">
              <span class="h-2 w-2 rounded-full bg-amber-500"></span>
              <strong>{{ $customMembersCount }}</strong> Karyawan Custom Sukarela
            </span>
          </div>
        @endif
      </div>

      <!-- ================= TAB 1: MASTER PROGRAM SYIRKAH ================= -->
      @if($activeTab === 'master')
        <div class="mb-4">
          <div class="flex w-full flex-1 items-center gap-2">
            <div class="relative w-full sm:max-w-xs">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <x-input type="text" class="block w-full pl-9 pr-8 text-xs sm:text-sm" name="search" id="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari Master Syirkah..." />
              @if ($search)
                <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 focus:outline-none">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              @endif
            </div>
          </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
          <table class="w-full min-w-[700px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
            <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <tr>
                <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Program Syirkah</th>
                <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nominal Wajib (Default)</th>
                <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nominal Sukarela (Default Master)</th>
                <th scope="col" class="px-4 py-3 min-w-[120px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
              @forelse ($savings as $saving)
                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-750 transition-colors">
                  <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $saving->savings_name ?? '-' }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                    Rp {{ number_format($saving->mandatory_savings, 0, ',', '.') }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                    Rp {{ number_format($saving->secondary_savings, 0, ',', '.') }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-center text-sm font-medium">
                    <div class="inline-flex items-center justify-center gap-1.5">
                      <button wire:click="edit('{{ $saving->id }}')" title="Edit Master Syirkah" class="inline-flex items-center justify-center p-1.5 rounded-lg bg-sky-100 text-sky-700 hover:bg-sky-200 dark:bg-sky-950 dark:text-sky-300 dark:hover:bg-sky-900 transition-colors cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                      </button>
                      <button wire:click="confirmDelete('{{ $saving->id }}')" title="Hapus Master Syirkah" class="inline-flex items-center justify-center p-1.5 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-950 dark:text-rose-300 dark:hover:bg-rose-900 transition-colors cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data master syirkah ditemukan.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          {{ $savings->links() }}
        </div>
      @endif

      <!-- ================= TAB 2: KUSTOMISASI SUKARELA KARYAWAN ================= -->
      @if($activeTab === 'members')
        <!-- Filter Bar -->
        <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Status Override -->
            <div class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1 text-xs font-medium">
              <button type="button" 
                      wire:click="$set('overrideFilter', '')" 
                      class="rounded-md px-3 py-1.5 transition-colors {{ $overrideFilter === '' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
                Semua Anggota
              </button>
              <button type="button" 
                      wire:click="$set('overrideFilter', 'custom')" 
                      class="rounded-md px-3 py-1.5 transition-colors {{ $overrideFilter === 'custom' ? 'bg-amber-500 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400' }}">
                Custom Override
                @if($customMembersCount > 0)
                  <span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.2 text-[10px] font-bold text-amber-800 {{ $overrideFilter === 'custom' ? 'bg-white text-amber-900' : '' }}">
                    {{ $customMembersCount }}
                  </span>
                @endif
              </button>
              <button type="button" 
                      wire:click="$set('overrideFilter', 'default')" 
                      class="rounded-md px-3 py-1.5 transition-colors {{ $overrideFilter === 'default' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                Default Master
              </button>
            </div>

            <!-- Divisi Dropdown -->
            <select wire:model.live="memberDivision" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="">Semua Divisi</option>
              @foreach($divisions as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
              @endforeach
            </select>
          </div>

          <!-- Search Bar -->
          <div class="relative flex-1 sm:max-w-xs">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <x-input type="text" class="block w-full pl-9 pr-8 text-xs sm:text-sm" name="memberSearch" id="memberSearch" autocomplete="off" wire:model.live.debounce.300ms="memberSearch" placeholder="Cari Nama Karyawan, NIP..." />
            @if ($memberSearch)
              <button type="button" wire:click="$set('memberSearch', '')" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            @endif
          </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
          <table class="w-full min-w-[1000px] divide-y divide-gray-200 text-left text-xs text-gray-700 dark:divide-gray-700 dark:text-gray-200">
            <thead class="bg-gray-50 uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-300">
              <tr>
                <th scope="col" class="px-4 py-3 min-w-[200px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Karyawan</th>
                <th scope="col" class="px-4 py-3 min-w-[160px] whitespace-nowrap text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Program Syirkah</th>
                <th scope="col" class="px-4 py-3 min-w-[140px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Wajib (Master)</th>
                <th scope="col" class="px-4 py-3 min-w-[150px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sukarela (Default)</th>
                <th scope="col" class="px-4 py-3 min-w-[180px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sukarela Efektif (Override)</th>
                <th scope="col" class="px-4 py-3 min-w-[160px] whitespace-nowrap text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Potongan/Bln</th>
                <th scope="col" class="px-4 py-3 min-w-[130px] whitespace-nowrap text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
              @forelse ($memberSalaries as $salary)
                @php
                  $hasCustom = $salary->custom_secondary_savings !== null;
                  $effectiveSukarela = $salary->effective_secondary_savings;
                  $mandatoryAmount = $salary->savings?->mandatory_savings ?? 0;
                  $totalPotongan = $mandatoryAmount + $effectiveSukarela;
                @endphp
                <tr class="{{ $hasCustom ? 'bg-amber-50/40 dark:bg-amber-950/20' : '' }} hover:bg-gray-50/80 dark:hover:bg-gray-750 transition-colors">
                  <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-gray-300">
                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $salary->employee->name ?? '-' }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $salary->employee->nip ?? '-' }} &bull; {{ $salary->employee->division->name ?? 'No Div' }}</div>
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-xs font-medium text-gray-800 dark:text-gray-200">
                    {{ $salary->savings->savings_name ?? 'Syirkah' }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-right text-xs text-gray-700 dark:text-gray-300 font-medium">
                    Rp {{ number_format($mandatoryAmount, 0, ',', '.') }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-right text-xs text-gray-500 dark:text-gray-400">
                    Rp {{ number_format($salary->savings->secondary_savings ?? 0, 0, ',', '.') }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-right text-xs">
                    <div class="flex flex-col items-end">
                      <span class="font-bold {{ $hasCustom ? 'text-amber-700 dark:text-amber-300 text-sm' : 'text-gray-900 dark:text-gray-100' }}">
                        Rp {{ number_format($effectiveSukarela, 0, ',', '.') }}
                      </span>
                      @if($hasCustom)
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.2 text-[9px] font-bold uppercase tracking-wider text-amber-800 dark:bg-amber-900/80 dark:text-amber-200 mt-0.5">
                          Custom Override
                        </span>
                      @else
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 italic">
                          Mengikuti Default Master
                        </span>
                      @endif
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-right text-xs font-bold text-emerald-700 dark:text-emerald-300">
                    Rp {{ number_format($totalPotongan, 0, ',', '.') }}
                    <div class="text-[10px] font-normal text-gray-400">Potong Payroll</div>
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-center text-xs font-medium">
                    <div class="inline-flex items-center justify-center gap-1.5">
                      <button wire:click="openCustomSukarelaModal('{{ $salary->id }}')" 
                              title="Kustomisasi Nominal Sukarela Karyawan" 
                              class="inline-flex items-center gap-1 rounded-lg bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:hover:bg-indigo-900 px-2.5 py-1.5 font-semibold text-xs transition-colors cursor-pointer">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                        <span>Atur Sukarela</span>
                      </button>

                      @if($hasCustom)
                        <button wire:click="resetCustomSukarelaToDefault('{{ $salary->id }}')" 
                                onclick="confirm('Kembalikan nominal sukarela karyawan ini ke default master program?') || event.stopImmediatePropagation()" 
                                title="Reset ke Default Master Program" 
                                class="inline-flex items-center justify-center p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition-colors cursor-pointer">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                          </svg>
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex flex-col items-center justify-center gap-1">
                      <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      <span>Tidak ada karyawan yang terdaftar dalam program syirkah sesuai filter.</span>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          {{ $memberSalaries->links() }}
        </div>
      @endif

    </div>
  </div>

  <!-- Form Modal (Master Program) -->
  <x-dialog-modal wire:model.live="isModalOpen">
    <x-slot name="title">
      {{ $saving_id ? 'Edit Master Program Syirkah' : 'Tambah Master Program Syirkah' }}
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="save" id="savingForm">
        <div class="grid grid-cols-1 gap-4">
          <div>
            <x-label for="savings_name" value="Nama Program Syirkah" />
            <x-input id="savings_name" type="text" class="mt-1 block w-full" wire:model="savings_name" required placeholder="Contoh: Syirkah Reguler 2026" />
            <x-input-error for="savings_name" class="mt-2" />
          </div>

          <div>
            <x-label for="mandatory_savings" value="Nominal Syirkah Wajib (Default)" />
            <div class="relative mt-1 rounded-md shadow-sm">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="text-gray-500 sm:text-sm">Rp</span>
              </div>
              <x-input id="mandatory_savings" type="number" class="block w-full pl-10" wire:model="mandatory_savings" required placeholder="0" min="0" />
            </div>
            <x-input-error for="mandatory_savings" class="mt-2" />
          </div>

          <div>
            <x-label for="secondary_savings" value="Nominal Syirkah Sukarela (Default Master)" />
            <div class="relative mt-1 rounded-md shadow-sm">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="text-gray-500 sm:text-sm">Rp</span>
              </div>
              <x-input id="secondary_savings" type="number" class="block w-full pl-10" wire:model="secondary_savings" required placeholder="0" min="0" />
            </div>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
              *Nominal ini adalah default untuk seluruh anggota program ini, kecuali jika di-override secara kustom per karyawan.
            </p>
            <x-input-error for="secondary_savings" class="mt-2" />
          </div>
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ml-3 bg-sky-600 hover:bg-sky-500 text-white" wire:click="save" wire:loading.attr="disabled">
        Simpan Paket
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Modal Kustomisasi Sukarela Karyawan -->
  <x-dialog-modal wire:model.live="isCustomSukarelaModalOpen" maxWidth="lg">
    <x-slot name="title">
      Kustomisasi Nominal Syirkah Sukarela (Override)
    </x-slot>

    <x-slot name="content">
      <div class="grid grid-cols-1 gap-5">
        <!-- Employee Info Card -->
        <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/80 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Karyawan</p>
              <p class="text-base font-bold text-slate-900 dark:text-slate-100">{{ $selectedEmployeeName }}</p>
              <p class="text-xs text-slate-500">{{ $selectedEmployeeNip }} &bull; {{ $selectedSavingName }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Default Master</p>
              <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Rp {{ number_format($selectedMasterSukarela, 0, ',', '.') }}</p>
            </div>
          </div>
        </div>

        <!-- Mode Selector -->
        <div>
          <x-label value="Pilihan Aturan Nominal Sukarela" class="mb-2" />
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-xs focus:outline-none transition-all {{ $customSukarelaMode === 'default' ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 ring-2 ring-sky-500 dark:border-sky-500' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750' }}">
              <input type="radio" wire:model.live="customSukarelaMode" value="default" class="sr-only" />
              <span class="flex flex-1 flex-col">
                <span class="block text-xs font-bold text-gray-900 dark:text-gray-100">Gunakan Default Master</span>
                <span class="mt-1 flex items-center text-xs text-gray-500 dark:text-gray-400">
                  Otomatis ikut default (Rp {{ number_format($selectedMasterSukarela, 0, ',', '.') }})
                </span>
              </span>
            </label>

            <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-xs focus:outline-none transition-all {{ $customSukarelaMode === 'custom' ? 'border-amber-500 bg-amber-50/70 dark:bg-amber-950/40 ring-2 ring-amber-500 dark:border-amber-500' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750' }}">
              <input type="radio" wire:model.live="customSukarelaMode" value="custom" class="sr-only" />
              <span class="flex flex-1 flex-col">
                <span class="block text-xs font-bold text-amber-900 dark:text-amber-200">Kustomisasi Khusus (Override)</span>
                <span class="mt-1 flex items-center text-xs text-amber-700 dark:text-amber-400">
                  Tentukan nominal sukarela berbeda untuk karyawan ini
                </span>
              </span>
            </label>
          </div>
        </div>

        <!-- Custom Nominal Input -->
        @if($customSukarelaMode === 'custom')
          <div class="rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 p-4">
            <x-label for="customSukarelaNominal" value="Nominal Syirkah Sukarela Khusus (Rp)" class="font-bold text-amber-900 dark:text-amber-200" />
            <div class="relative mt-1.5 rounded-md shadow-sm">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="text-gray-500 sm:text-sm font-semibold">Rp</span>
              </div>
              <x-input id="customSukarelaNominal" type="number" class="block w-full pl-10 text-base font-bold text-gray-900 dark:text-gray-100" wire:model.live.debounce.300ms="customSukarelaNominal" min="0" placeholder="Contoh: 100000" />
            </div>
            <p class="mt-2 text-xs text-amber-800 dark:text-amber-300">
              *Nominal ini akan secara otomatis mem-bypass default master saat payroll di-generate untuk karyawan <strong>{{ $selectedEmployeeName }}</strong>.
            </p>
          </div>
        @endif
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeCustomSukarelaModal" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-indigo-600 hover:bg-indigo-500 text-white" wire:click="saveCustomSukarela" wire:loading.attr="disabled">
        Simpan Pengaturan Sukarela
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- Delete Confirmation Modal (Master Program) -->
  <x-confirmation-modal wire:model.live="isConfirmingDeletion">
    <x-slot name="title">
      Konfirmasi Hapus Data Syirkah
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus program syirkah ini secara permanen? Tindakan ini tidak dapat dibatalkan.
    </x-slot>

    <x-slot name="footer">
      <x-danger-button wire:click="delete" wire:loading.attr="disabled">
        Ya, Hapus
      </x-danger-button>

      <x-secondary-button wire:click="cancelDelete" wire:loading.attr="disabled" class="ms-3">
        Batal
      </x-secondary-button>
    </x-slot>
  </x-confirmation-modal>
</div>

