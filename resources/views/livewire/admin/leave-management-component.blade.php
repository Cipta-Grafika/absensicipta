<div>
  <x-slot name="header">
    <div class="relative flex items-center justify-between">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        {{ __('Manajemen Cuti Karyawan') }}
      </h2>

      <div class="flex items-center gap-2">
        <x-button type="button" x-data @click.prevent="Livewire.dispatch('open-add-leave-modal')">
          <x-heroicon-o-plus class="mr-1.5 h-4 w-4" />
          Tambah
        </x-button>

        <x-secondary-button type="button" x-data @click.prevent="Livewire.dispatch('open-manage-leave-types-modal')">
          <x-heroicon-o-tag class="mr-1.5 h-4 w-4 text-sky-500" />
          Master Tipe Cuti
        </x-secondary-button>

        <x-secondary-button type="button" x-data @click.prevent="Livewire.dispatch('bulk-sync-all')" title="Hitung ulang & sinkronkan kuota semua karyawan">
          <x-heroicon-o-arrow-path class="mr-1.5 h-4 w-4 text-emerald-500" />
          <span>Sinkronkan</span>
        </x-secondary-button>

        <x-secondary-button type="button" x-data @click.prevent="Livewire.dispatch('export-pdf')" title="Unduh laporan rekap cuti PDF">
          <x-heroicon-o-arrow-down-tray class="mr-1.5 h-4 w-4 text-rose-500" />
          Export PDF
        </x-secondary-button>
      </div>
    </div>
  </x-slot>

  <div class="py-4 sm:py-6 max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 space-y-6">

    <!-- 1. KPI SUMMARY CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
      
      <!-- Total Karyawan -->
      <div class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold tracking-wider uppercase text-gray-500 dark:text-gray-400">Total Karyawan</span>
            <div class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($totalEmployees) }}</div>
          </div>
          <div class="h-11 w-11 rounded-xl bg-sky-50 dark:bg-sky-950/80 border border-sky-200/60 dark:border-sky-800/60 flex items-center justify-center text-sky-600 dark:text-sky-400 group-hover:scale-110 transition-transform">
            <x-heroicon-o-users class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-2 text-[10px] text-gray-400 dark:text-gray-500">Tahun Periode {{ $year }}</div>
      </div>

      <!-- Total Kuota Dialokasikan -->
      <div class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold tracking-wider uppercase text-gray-500 dark:text-gray-400">Alokasi Kuota</span>
            <div class="mt-1 text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($totalQuotaAllocated) }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Hari</span></div>
          </div>
          <div class="h-11 w-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200/60 dark:border-indigo-800/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
            <x-heroicon-o-archive-box class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-2 text-[10px] text-gray-400 dark:text-gray-500">Awal + Carry Over + Penyesuaian</div>
      </div>

      <!-- Total Cuti Terpakai -->
      <div class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold tracking-wider uppercase text-gray-500 dark:text-gray-400">Cuti Terpakai</span>
            <div class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($totalUsedDays) }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Hari</span></div>
          </div>
          <div class="h-11 w-11 rounded-xl bg-amber-50 dark:bg-amber-950/80 border border-amber-200/60 dark:border-amber-800/60 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
            <x-heroicon-o-calendar-days class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-2 text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
          {{ $totalQuotaAllocated > 0 ? round(($totalUsedDays / $totalQuotaAllocated) * 100, 1) : 0 }}% kuota digunakan
        </div>
      </div>

      <!-- Sisa Kuota Aktif -->
      <div class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold tracking-wider uppercase text-gray-500 dark:text-gray-400">Sisa Kuota Total</span>
            <div class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($totalRemainingDays) }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Hari</span></div>
          </div>
          <div class="h-11 w-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200/60 dark:border-emerald-800/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
            <x-heroicon-o-check-badge class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-2 text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Saldo cuti aktif tersedia</div>
      </div>

      <!-- Karyawan Cuti Hari Ini & Peringatan -->
      <div class="col-span-2 lg:col-span-1 p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold tracking-wider uppercase text-gray-500 dark:text-gray-400">Cuti Hari Ini</span>
            <div class="mt-1 text-2xl font-black text-sky-600 dark:text-sky-400">{{ $onLeaveToday->count() }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Orang</span></div>
          </div>
          <div class="h-11 w-11 rounded-xl bg-sky-50 dark:bg-sky-950/80 border border-sky-200/60 dark:border-sky-800/60 flex items-center justify-center text-sky-600 dark:text-sky-400 group-hover:scale-110 transition-transform">
            <x-heroicon-o-sun class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-2 flex items-center gap-1.5">
          @if ($emptyCount > 0 || $criticalCount > 0)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white shadow-xs animate-pulse">
              {{ $emptyCount + $criticalCount }} Kritis / Habis
            </span>
          @else
            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Semua kuota aman
            </span>
          @endif
        </div>
      </div>

    </div>

    <!-- 2. TODAY'S ON LEAVE EMPLOYEES BANNER (If any) -->
    @if ($onLeaveToday->isNotEmpty())
      <div class="p-4 rounded-2xl bg-sky-500/10 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/80 shadow-xs">
        <div class="flex items-center justify-between mb-2.5">
          <div class="flex items-center gap-2">
            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-ping"></span>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Karyawan Sedang Cuti Hari Ini ({{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }})</h3>
          </div>
          <span class="text-[11px] font-bold text-sky-600 dark:text-sky-400">{{ $onLeaveToday->count() }} Karyawan</span>
        </div>
        <div class="flex flex-wrap gap-2">
          @foreach ($onLeaveToday as $att)
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xs">
              <div class="h-6 w-6 rounded-full bg-sky-100 dark:bg-sky-900/60 text-sky-700 dark:text-sky-300 font-bold text-[10px] flex items-center justify-center">
                {{ strtoupper(substr($att->user?->name ?? 'K', 0, 1)) }}
              </div>
              <div class="text-left">
                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block leading-tight">{{ $att->user?->name }}</span>
                <span class="text-[10px] text-gray-500 dark:text-gray-400 block">{{ $att->user?->division?->name ?? '-' }} &bull; {{ $att->status === 'special-leaves' ? 'Cuti Khusus' : 'Cuti Tahunan' }}</span>
              </div>
              @if ($att->note)
                <span class="text-[10px] italic text-gray-400 dark:text-gray-500 max-w-[120px] truncate" title="{{ $att->note }}">({{ $att->note }})</span>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <!-- 3. MAIN FILTER & ACTION TOOLBAR -->
    <div class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xs space-y-3">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
        
        <!-- Search Input -->
        <div class="lg:col-span-4">
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
            </div>
            <x-input type="text"
                     wire:model.live.debounce.300ms="search"
                     placeholder="Cari nama, NIP, atau email..."
                     class="w-full pl-9 text-xs py-2 rounded-xl dark:bg-gray-900 dark:text-white dark:border-gray-700" />
            @if ($search)
              <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-heroicon-o-x-mark class="h-4 w-4" />
              </button>
            @endif
          </div>
        </div>

        <!-- Year Selector -->
        <div class="lg:col-span-2">
          <x-select wire:model.live="year" class="w-full text-xs py-2 px-3 rounded-xl font-bold dark:bg-gray-900 dark:text-white dark:border-gray-700">
            @for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 3; $y--)
              <option value="{{ $y }}">{{ $y }}</option>
            @endfor
          </x-select>
        </div>

        <!-- Division Filter -->
        <div class="lg:col-span-3">
          <x-select wire:model.live="division_id" class="w-full text-xs py-2 rounded-xl dark:bg-gray-900 dark:text-white dark:border-gray-700">
            <option value="">Semua Divisi</option>
            @foreach ($divisions as $div)
              <option value="{{ $div->id }}">{{ $div->name }}</option>
            @endforeach
          </x-select>
        </div>

        <!-- Status Saldo Kuota Filter -->
        <div class="lg:col-span-3">
          <x-select wire:model.live="quota_status" class="w-full text-xs py-2 rounded-xl dark:bg-gray-900 dark:text-white dark:border-gray-700">
            <option value="all">Semua Status Kuota</option>
            <option value="safe">Kuota Aman (&gt; 3 Hari)</option>
            <option value="critical">Kuota Kritis (1 - 3 Hari)</option>
            <option value="empty">Kuota Habis (0 Hari)</option>
            <option value="minus">Overdrawn (&lt; 0 Hari)</option>
          </x-select>
        </div>

      </div>
    </div>

    <!-- 4. EMPLOYEES LEAVE MANAGEMENT TABLE -->
    <div class="overflow-hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-md rounded-2xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
          <thead class="bg-gray-50/90 dark:bg-gray-900/90 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th scope="col" class="py-3.5 px-4">Karyawan</th>
              <th scope="col" class="py-3.5 px-3 text-center">Divisi & Jabatan</th>
              <th scope="col" class="py-3.5 px-3 text-center" title="Kuota awal yang ditetapkan">Kuota Awal</th>
              <th scope="col" class="py-3.5 px-3 text-center" title="Sisa kuota dari tahun sebelumnya">Sisa Lalu</th>
              <th scope="col" class="py-3.5 px-3 text-center" title="Penyesuaian manual oleh Superadmin">Penyesuaian</th>
              <th scope="col" class="py-3.5 px-3 text-center font-black text-gray-700 dark:text-gray-200">Total Kuota</th>
              <th scope="col" class="py-3.5 px-3 text-center font-bold text-amber-600 dark:text-amber-400">Terpakai</th>
              <th scope="col" class="py-3.5 px-4 text-center font-black">Sisa Kuota Cuti</th>
              <th scope="col" class="py-3.5 px-4 text-center">Aksi Cepat</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($employees as $emp)
              @php
                $balance = $emp->leaveBalanceForYear($year);
                $totalQ = $balance->total_quota;
                $usedQ = $balance->used_quota;
                $remQ = $balance->remaining_quota;
                $usagePct = $balance->usage_percentage;
              @endphp
              <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors">
                
                <!-- Karyawan Info -->
                <td class="py-3 px-4">
                  <div class="flex items-center gap-3">
                    <img src="{{ $emp->profile_photo_url }}" alt="{{ $emp->name }}" class="h-9 w-9 rounded-full object-cover border border-gray-200 dark:border-gray-700 shrink-0">
                    <div class="min-w-0">
                      <div class="font-bold text-gray-900 dark:text-white truncate flex items-center gap-1.5">
                        <span>{{ $emp->name }}</span>
                        @if ($emp->status !== 'active')
                          <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ ucfirst($emp->status) }}
                          </span>
                        @endif
                      </div>
                      <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">{{ $emp->nip ?? '-' }}</div>
                    </div>
                  </div>
                </td>

                <!-- Divisi & Jabatan -->
                <td class="py-3 px-3 text-center">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-gray-100 text-gray-800 border border-gray-200/60 dark:bg-gray-700/80 dark:text-gray-200 dark:border-gray-600/60">
                    {{ $emp->division?->name ?? '-' }}
                  </span>
                  <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ $emp->jobTitle?->name ?? '-' }}</div>
                </td>

                <!-- Kuota Awal -->
                <td class="py-3 px-3 text-center font-semibold text-gray-700 dark:text-gray-300">
                  {{ $balance->initial_quota }}
                </td>

                <!-- Carry Over / Sisa Lalu -->
                <td class="py-3 px-3 text-center text-gray-600 dark:text-gray-400">
                  {{ $balance->carry_forward > 0 ? '+' . $balance->carry_forward : $balance->carry_forward }}
                </td>

                <!-- Penyesuaian / Adjustment -->
                <td class="py-3 px-3 text-center">
                  @if ($balance->adjustment > 0)
                    <span class="inline-flex items-center font-bold text-emerald-600 dark:text-emerald-400">+{{ $balance->adjustment }}</span>
                  @elseif ($balance->adjustment < 0)
                    <span class="inline-flex items-center font-bold text-rose-600 dark:text-rose-400">{{ $balance->adjustment }}</span>
                  @else
                    <span class="text-gray-400 dark:text-gray-500">0</span>
                  @endif
                </td>

                <!-- Total Kuota Efektif -->
                <td class="py-3 px-3 text-center font-extrabold text-sm text-indigo-600 dark:text-indigo-400">
                  {{ $totalQ }}
                </td>

                <!-- Cuti Terpakai -->
                <td class="py-3 px-3 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $usedQ > 0 ? 'bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-800' : 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700' }}">
                    {{ $usedQ }} Hari
                  </span>
                </td>

                <!-- Sisa Kuota dengan Visual Progress -->
                <td class="py-3 px-4 text-center min-w-[150px]">
                  <div class="flex flex-col items-center">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold shadow-2xs {{
                      $remQ > 3
                        ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/90 dark:text-emerald-300 dark:border-emerald-700'
                        : ($remQ > 0
                          ? 'bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/90 dark:text-amber-300 dark:border-amber-700 animate-pulse'
                          : ($remQ === 0
                            ? 'bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-950/90 dark:text-rose-300 dark:border-rose-700'
                            : 'bg-purple-100 text-purple-800 border border-purple-200 dark:bg-purple-950/90 dark:text-purple-300 dark:border-purple-700 font-black'))
                    }}">
                      {{ $remQ }} Hari Sisa
                    </span>
                    
                    <!-- Micro Progress Bar -->
                    <div class="w-full bg-gray-200 dark:bg-gray-700/80 h-1.5 rounded-full mt-1.5 overflow-hidden">
                      <div class="h-full rounded-full transition-all duration-300 {{ $remQ <= 0 ? 'bg-rose-500 dark:bg-rose-400' : ($remQ <= 3 ? 'bg-amber-500 dark:bg-amber-400' : 'bg-emerald-500 dark:bg-emerald-400') }}" style="width: {{ $usagePct }}%"></div>
                    </div>
                    <span class="text-[9px] text-gray-400 dark:text-gray-400 mt-0.5 font-medium">{{ $usagePct }}% terpakai</span>
                  </div>
                </td>

                <!-- Quick Actions -->
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    
                    <!-- Riwayat & Breakdown -->
                    <button type="button"
                            wire:click="openHistoryModal('{{ $emp->id }}')"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200/80 dark:bg-sky-950/70 dark:text-sky-300 dark:border-sky-800 dark:hover:bg-sky-900/80 transition-colors shadow-2xs"
                            title="Lihat riwayat dan tanggal cuti yang diambil">
                      <x-heroicon-o-document-text class="h-3.5 w-3.5" />
                      <span>Riwayat</span>
                    </button>

                    <!-- Adjust Kuota -->
                    <button type="button"
                            wire:click="openAdjustModal('{{ $emp->id }}')"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200/80 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800 dark:hover:bg-amber-900/80 transition-colors shadow-2xs"
                            title="Sesuaikan kuota cuti karyawan">
                      <x-heroicon-o-adjustments-horizontal class="h-3.5 w-3.5" />
                      <span>Adjust</span>
                    </button>

                    <!-- Input Cuti -->
                    <button type="button"
                            wire:click="openAddLeaveModal('{{ $emp->id }}')"
                            class="inline-flex items-center justify-center h-7 w-7 rounded-lg text-emerald-700 hover:bg-emerald-50 border border-emerald-200/80 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800 dark:hover:bg-emerald-900/80 transition-colors shadow-2xs"
                            title="Input cuti langsung untuk karyawan ini">
                      <x-heroicon-o-plus-circle class="h-4 w-4" />
                    </button>

                  </div>
                </td>

              </tr>
            @empty
              <tr>
                <td colspan="9" class="py-12 text-center text-gray-500 dark:text-gray-400">
                  <x-heroicon-o-calendar-days class="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                  <p class="font-semibold text-sm">Tidak ada data karyawan yang sesuai dengan filter.</p>
                  <p class="text-xs mt-1">Coba ubah filter pencarian atau pilih tahun/divisi yang lain.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      @if ($employees->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/60">
          {{ $employees->links() }}
        </div>
      @endif
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- MODAL 1: RIWAYAT & DETAIL CUTI KARYAWAN                                   -->
  <!-- ========================================================================= -->
  <x-dialog-modal wire:model.live="isHistoryModalOpen" maxWidth="3xl">
    <x-slot name="title">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="h-8 w-8 rounded-lg bg-sky-500 flex items-center justify-center text-white shadow-xs">
            <x-heroicon-o-document-text class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Cuti Karyawan</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $selectedUser?->name }} (NIP: {{ $selectedUser?->nip ?? '-' }}) &bull; Periode Tahun {{ $year }}</p>
          </div>
        </div>
      </div>
    </x-slot>

    <x-slot name="content">
      @if ($selectedUserBalance)
        <!-- Mini Balance Overview in Modal -->
        <div class="mb-4 grid grid-cols-4 gap-2.5 p-3.5 rounded-xl bg-sky-50/70 dark:bg-gray-900 border border-sky-200/80 dark:border-gray-700 text-center">
          <div>
            <span class="text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">Kuota Awal</span>
            <div class="text-base font-black text-gray-800 dark:text-gray-100">{{ $selectedUserBalance->initial_quota }}</div>
          </div>
          <div>
            <span class="text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">Total Kuota</span>
            <div class="text-base font-black text-indigo-600 dark:text-indigo-400">{{ $selectedUserBalance->total_quota }}</div>
          </div>
          <div>
            <span class="text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">Cuti Terpakai</span>
            <div class="text-base font-black text-amber-600 dark:text-amber-400">{{ $selectedUserBalance->used_quota }}</div>
          </div>
          <div>
            <span class="text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">Sisa Kuota</span>
            <div class="text-base font-black {{ $selectedUserBalance->remaining_quota > 2 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
              {{ $selectedUserBalance->remaining_quota }} Hari
            </div>
          </div>
        </div>
      @endif

      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Daftar Tanggal Cuti Tercatat</h4>
          <x-button type="button" wire:click="openAddLeaveModal('{{ $selectedUserId }}')" class="!py-1.5 !px-3 text-xs bg-emerald-600 hover:bg-emerald-700">
            <x-heroicon-o-plus class="h-3.5 w-3.5 mr-1" />
            Tambah Tanggal Cuti
          </x-button>
        </div>

        <div class="overflow-x-auto max-h-80 overflow-y-auto custom-scrollbar-y rounded-xl border border-gray-200 dark:border-gray-700">
          <table class="w-full text-left text-xs">
            <thead class="bg-gray-100/90 dark:bg-gray-800 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 sticky top-0 z-10 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="py-2.5 px-3">Tanggal</th>
                <th class="py-2.5 px-3">Jenis Cuti</th>
                <th class="py-2.5 px-3">Keterangan / Alasan</th>
                <th class="py-2.5 px-3 text-center">Lampiran</th>
                <th class="py-2.5 px-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
              @forelse ($userLeaveHistory as $hist)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                  <td class="py-2.5 px-3 font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                    {{ \Carbon\Carbon::parse($hist->date)->translatedFormat('l, d F Y') }}
                  </td>
                  <td class="py-2.5 px-3">
                    @if ($hist->status === 'special-leaves')
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200 dark:bg-purple-950/80 dark:text-purple-300 dark:border-purple-800">
                        Cuti Khusus
                      </span>
                    @elseif ($hist->status === 'leave')
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200 dark:bg-sky-950/80 dark:text-sky-300 dark:border-sky-800">
                        Cuti Tahunan (Potong Kuota)
                      </span>
                    @else
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-800">
                          {{ ucfirst($hist->status) }} (Izin Cuti)
                        </span>
                        <button type="button"
                                wire:click="updateLeaveStatus({{ $hist->id }}, 'leave')"
                                class="text-[9px] font-semibold text-sky-600 hover:text-sky-800 dark:text-sky-400 underline cursor-pointer"
                                title="Ubah status menjadi Cuti Tahunan resmi yang memotong kuota">
                          Jadikan Cuti Tahunan
                        </button>
                      </div>
                    @endif
                  </td>
                  <td class="py-2.5 px-3 text-gray-600 dark:text-gray-300">
                    {{ $hist->note ?: '-' }}
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    @if ($hist->attachment)
                      <a href="{{ $hist->attachment_url }}" target="_blank" class="inline-flex items-center gap-1 text-sky-600 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-300 font-semibold underline">
                        <x-heroicon-o-paper-clip class="h-3.5 w-3.5" />
                        <span>Unduh</span>
                      </a>
                    @else
                      <span class="text-gray-400 text-[10px]">-</span>
                    @endif
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                      @if ($hist->status === 'special-leaves')
                        <button type="button"
                                wire:click="updateLeaveStatus({{ $hist->id }}, 'leave')"
                                class="inline-flex items-center text-sky-600 hover:text-sky-800 dark:text-sky-400 p-1 hover:bg-sky-50 dark:hover:bg-sky-950/60 rounded text-[10px] font-semibold"
                                title="Ubah menjadi Cuti Tahunan (Potong Kuota)">
                          &rarr; Tahunan
                        </button>
                      @elseif ($hist->status === 'leave')
                        <button type="button"
                                wire:click="updateLeaveStatus({{ $hist->id }}, 'special-leaves')"
                                class="inline-flex items-center text-purple-600 hover:text-purple-800 dark:text-purple-400 p-1 hover:bg-purple-50 dark:hover:bg-purple-950/60 rounded text-[10px] font-semibold"
                                title="Ubah menjadi Cuti Khusus (Tidak Potong Kuota)">
                          &rarr; Khusus
                        </button>
                      @endif

                      <button type="button"
                              wire:click="deleteLeaveRecord({{ $hist->id }})"
                              wire:confirm="Yakin ingin membatalkan/menghapus cuti tanggal {{ \Carbon\Carbon::parse($hist->date)->format('d/m/Y') }}? Kuota cuti akan otomatis dikembalikan."
                              class="inline-flex items-center text-rose-600 hover:text-rose-800 dark:text-rose-400 p-1 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded"
                              title="Hapus cuti ini dan kembalikan kuota">
                        <x-heroicon-o-trash class="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="py-8 text-center text-gray-400 dark:text-gray-500">
                    Belum ada riwayat cuti yang tercatat pada tahun {{ $year }}.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeHistoryModal" class="dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
        Tutup
      </x-secondary-button>
    </x-slot>
  </x-dialog-modal>

  <!-- ========================================================================= -->
  <!-- MODAL 2: ADJUST KUOTA CUTI                                               -->
  <!-- ========================================================================= -->
  <x-dialog-modal wire:model.live="isAdjustModalOpen" maxWidth="lg">
    <x-slot name="title">
      <div class="flex items-center gap-2">
        <div class="h-8 w-8 rounded-lg bg-amber-500 flex items-center justify-center text-white shadow-xs">
          <x-heroicon-o-adjustments-horizontal class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-gray-900 dark:text-white">Penyesuaian Kuota Cuti Dinamis</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ $adjustUserName }} &bull; Tahun {{ $year }}</p>
        </div>
      </div>
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="saveAdjustment" id="adjustQuotaForm" class="space-y-4">
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Kuota Awal -->
          <div>
            <x-label for="formInitialQuota" value="Kuota Awal (Hari)" class="text-xs font-bold" />
            <x-input type="number" id="formInitialQuota" wire:model.live="formInitialQuota" min="0" max="100" class="mt-1 block w-full text-xs font-semibold dark:bg-gray-900 dark:text-white dark:border-gray-700" required />
            <x-input-error for="formInitialQuota" class="mt-1" />
          </div>

          <!-- Carry Over -->
          <div>
            <x-label for="formCarryForward" value="Sisa Tahun Lalu" class="text-xs font-bold" />
            <x-input type="number" id="formCarryForward" wire:model.live="formCarryForward" min="0" max="100" class="mt-1 block w-full text-xs font-semibold dark:bg-gray-900 dark:text-white dark:border-gray-700" required />
            <x-input-error for="formCarryForward" class="mt-1" />
          </div>

          <!-- Adjustment -->
          <div>
            <x-label for="formAdjustment" value="Penyesuaian (+/-)" class="text-xs font-bold" />
            <x-input type="number" id="formAdjustment" wire:model.live="formAdjustment" min="-100" max="100" class="mt-1 block w-full text-xs font-semibold dark:bg-gray-900 dark:text-white dark:border-gray-700" required />
            <x-input-error for="formAdjustment" class="mt-1" />
          </div>
        </div>

        <!-- Live Preview Total & Remaining -->
        <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between text-xs">
            <span class="text-gray-600 dark:text-gray-400">Total Kuota Baru:</span>
            <span class="font-extrabold text-indigo-600 dark:text-indigo-400 text-sm">
              {{ (int)$formInitialQuota + (int)$formCarryForward + (int)$formAdjustment }} Hari
            </span>
          </div>
        </div>

        <!-- Expired Date (Optional) -->
        <div>
          <x-label for="formExpiredAt" value="Tanggal Batas Kadaluarsa Kuota (Opsional)" class="text-xs font-bold" />
          <x-input type="date" id="formExpiredAt" wire:model="formExpiredAt" class="mt-1 block w-full text-xs dark:bg-gray-900 dark:text-white dark:border-gray-700" />
          <x-input-error for="formExpiredAt" class="mt-1" />
        </div>

        <!-- Catatan Alasan -->
        <div>
          <x-label for="formNote" value="Catatan / Alasan Penyesuaian" class="text-xs font-bold" />
          <x-textarea id="formNote" wire:model="formNote" placeholder="Contoh: Reward prestasi kerja, rollover sisa cuti, dsb..." class="mt-1 block w-full text-xs dark:bg-gray-900 dark:text-white dark:border-gray-700" rows="2"></x-textarea>
          <x-input-error for="formNote" class="mt-1" />
        </div>

      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeAdjustModal" wire:loading.attr="disabled" class="dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
        Batal
      </x-secondary-button>

      <x-button class="ml-2 bg-amber-600 hover:bg-amber-700 text-white" wire:click="saveAdjustment" wire:loading.attr="disabled">
        Simpan Penyesuaian
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- ========================================================================= -->
  <!-- MODAL 3: INPUT CUTI LANGSUNG OLEH SUPERADMIN                              -->
  <!-- ========================================================================= -->
  <x-dialog-modal wire:model.live="isAddLeaveModalOpen" maxWidth="lg">
    <x-slot name="title">
      <div class="flex items-center gap-2">
        <div class="h-8 w-8 rounded-lg bg-emerald-500 flex items-center justify-center text-white shadow-xs">
          <x-heroicon-o-plus-circle class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-gray-900 dark:text-white">Input Cuti Karyawan</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">Pencatatan langsung cuti yang otomatis memotong master kuota</p>
        </div>
      </div>
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="saveAddLeave" id="addLeaveSuperadminForm" class="space-y-4">
        
        <!-- Pilih Karyawan -->
        <div>
          <x-label for="addLeaveUserId" value="Pilih Karyawan" class="text-xs font-bold" />
          <x-select id="addLeaveUserId" wire:model="addLeaveUserId" class="mt-1 block w-full text-xs font-semibold dark:bg-gray-900 dark:text-white dark:border-gray-700" required>
            <option value="">-- Pilih Karyawan --</option>
            @foreach ($allEmployeesList as $emp)
              <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->nip ?? '-' }})</option>
            @endforeach
          </x-select>
          <x-input-error for="addLeaveUserId" class="mt-1" />
        </div>

        <!-- Jenis Cuti -->
        <div>
          <x-label for="addLeaveStatus" value="Jenis Cuti" class="text-xs font-bold" />
          <x-select id="addLeaveStatus" wire:model="addLeaveStatus" class="mt-1 block w-full text-xs dark:bg-gray-900 dark:text-white dark:border-gray-700" required>
            <option value="leave">Cuti Tahunan (Memotong Saldo Kuota Cuti Tahunan)</option>
            <option value="special-leaves">Cuti Khusus (Tidak Memotong Kuota Tahunan)</option>
          </x-select>
          <x-input-error for="addLeaveStatus" class="mt-1" />
        </div>

        <!-- Tanggal Mulai & Tanggal Selesai -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <x-label for="addLeaveFrom" value="Tanggal Mulai" class="text-xs font-bold" />
            <x-input type="date" id="addLeaveFrom" wire:model="addLeaveFrom" class="mt-1 block w-full text-xs font-semibold dark:bg-gray-900 dark:text-white dark:border-gray-700" required />
            <x-input-error for="addLeaveFrom" class="mt-1" />
          </div>

          <div>
            <x-label for="addLeaveTo" value="Tanggal Berakhir" class="text-xs font-bold" />
            <x-input type="date" id="addLeaveTo" wire:model="addLeaveTo" class="mt-1 block w-full text-xs font-semibold dark:bg-gray-900 dark:text-white dark:border-gray-700" required />
            <x-input-error for="addLeaveTo" class="mt-1" />
          </div>
        </div>

        <!-- Keterangan / Alasan -->
        <div>
          <x-label for="addLeaveNote" value="Keterangan / Alasan Cuti" class="text-xs font-bold" />
          <x-textarea id="addLeaveNote" wire:model="addLeaveNote" placeholder="Tuliskan keterangan permohonan cuti..." class="mt-1 block w-full text-xs dark:bg-gray-900 dark:text-white dark:border-gray-700" required></x-textarea>
          <x-input-error for="addLeaveNote" class="mt-1" />
        </div>

        <!-- Upload Lampiran -->
        <div>
          <x-label for="addLeaveAttachment" value="Lampiran Dokumen / Surat Izin (Opsional, Max 3MB)" class="text-xs font-bold" />
          <input type="file" id="addLeaveAttachment" wire:model="addLeaveAttachment" class="mt-1 block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 dark:file:bg-gray-700 dark:file:text-gray-200 hover:file:bg-sky-100" />
          <div wire:loading wire:target="addLeaveAttachment" class="text-[11px] text-sky-500 mt-1">Mengunggah lampiran...</div>
          <x-input-error for="addLeaveAttachment" class="mt-1" />
        </div>

      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeAddLeaveModal" wire:loading.attr="disabled" class="dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
        Batal
      </x-secondary-button>

      <x-button class="ml-2 bg-emerald-600 hover:bg-emerald-700 text-white" wire:click="saveAddLeave" wire:loading.attr="disabled">
        Simpan & Potong Kuota
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <!-- ========================================================================= -->
  <!-- MODAL 4: MASTER DATA TIPE CUTI                                            -->
  <!-- ========================================================================= -->
  <x-dialog-modal wire:model.live="isLeaveTypesModalOpen" maxWidth="3xl">
    <x-slot name="title">
      <div class="flex items-center gap-2">
        <div class="h-8 w-8 rounded-lg bg-indigo-500 flex items-center justify-center text-white shadow-xs">
          <x-heroicon-o-tag class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-gray-900 dark:text-white">Master Data Tipe Cuti</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">Konfigurasi jenis-jenis cuti, alokasi hari default, dan aturan pemotongan kuota</p>
        </div>
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="space-y-4">
        
        <!-- Form Add/Edit Leave Type -->
        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
            {{ $isEditingLeaveType ? 'Edit Tipe Cuti' : 'Tambah Tipe Cuti Baru' }}
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <x-label for="typeCode" value="Kode Cuti" class="text-xs font-bold" />
              <x-input type="text" id="typeCode" wire:model="typeCode" placeholder="Contoh: MARRIAGE" class="mt-1 block w-full text-xs font-mono uppercase dark:bg-gray-800 dark:text-white dark:border-gray-700" required />
              <x-input-error for="typeCode" class="mt-1" />
            </div>

            <div>
              <x-label for="typeName" value="Nama Tipe Cuti" class="text-xs font-bold" />
              <x-input type="text" id="typeName" wire:model="typeName" placeholder="Contoh: Cuti Menikah" class="mt-1 block w-full text-xs dark:bg-gray-800 dark:text-white dark:border-gray-700" required />
              <x-input-error for="typeName" class="mt-1" />
            </div>

            <div>
              <x-label for="typeDefaultDays" value="Default Kuota (Hari)" class="text-xs font-bold" />
              <x-input type="number" id="typeDefaultDays" wire:model="typeDefaultDays" min="1" max="365" class="mt-1 block w-full text-xs font-semibold dark:bg-gray-800 dark:text-white dark:border-gray-700" required />
              <x-input-error for="typeDefaultDays" class="mt-1" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" wire:model="typeDeductsQuota" class="rounded text-sky-600 focus:ring-sky-500 dark:bg-gray-800 dark:border-gray-700">
              <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Potong Saldo Kuota Cuti Tahunan</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" wire:model="typeRequiresAttachment" class="rounded text-sky-600 focus:ring-sky-500 dark:bg-gray-800 dark:border-gray-700">
              <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Wajib Lampirkan Dokumen Bukti</span>
            </label>
          </div>

          <div>
            <x-label for="typeDescription" value="Deskripsi / Aturan Cuti" class="text-xs font-bold" />
            <x-textarea id="typeDescription" wire:model="typeDescription" placeholder="Penjelasan mengenai hak dan ketentuan cuti ini..." class="mt-1 block w-full text-xs dark:bg-gray-800 dark:text-white dark:border-gray-700" rows="2"></x-textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            @if ($isEditingLeaveType)
              <x-secondary-button type="button" wire:click="resetLeaveTypeForm" class="!py-1.5 !px-3 text-xs dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                Batal Edit
              </x-secondary-button>
            @endif

            <x-button type="button" wire:click="saveLeaveType" class="!py-1.5 !px-3 text-xs bg-indigo-600 hover:bg-indigo-700 text-white">
              {{ $isEditingLeaveType ? 'Simpan Perubahan' : 'Tambah Tipe Cuti' }}
            </x-button>
          </div>
        </div>

        <!-- Table Master Tipe Cuti -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 max-h-64 overflow-y-auto custom-scrollbar-y">
          <table class="w-full text-left text-xs">
            <thead class="bg-gray-100 dark:bg-gray-800 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 sticky top-0 z-10 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="py-2.5 px-3">Kode</th>
                <th class="py-2.5 px-3">Nama Cuti</th>
                <th class="py-2.5 px-3 text-center">Jatah Hari</th>
                <th class="py-2.5 px-3 text-center">Potong Kuota</th>
                <th class="py-2.5 px-3 text-center">Status</th>
                <th class="py-2.5 px-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
              @foreach ($masterLeaveTypes as $lt)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                  <td class="py-2 px-3 font-mono font-bold text-gray-800 dark:text-gray-200">{{ $lt->code }}</td>
                  <td class="py-2 px-3 font-semibold text-gray-900 dark:text-white">{{ $lt->name }}</td>
                  <td class="py-2 px-3 text-center font-bold">{{ $lt->default_days }} Hari</td>
                  <td class="py-2 px-3 text-center">
                    @if ($lt->deducts_annual_quota)
                      <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-800">Ya</span>
                    @else
                      <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">Tidak</span>
                    @endif
                  </td>
                  <td class="py-2 px-3 text-center">
                    <button type="button" wire:click="toggleLeaveTypeStatus({{ $lt->id }})" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold cursor-pointer {{ $lt->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/80 dark:text-emerald-300 dark:border-emerald-800' : 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700' }}">
                      {{ $lt->is_active ? 'Aktif' : 'Nonaktif' }}
                    </button>
                  </td>
                  <td class="py-2 px-3 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button type="button" wire:click="editLeaveType({{ $lt->id }})" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400" title="Edit Tipe Cuti">
                        <x-heroicon-o-pencil-square class="h-4 w-4" />
                      </button>
                      @if ($lt->code !== 'ANNUAL')
                        <button type="button" wire:click="deleteLeaveType({{ $lt->id }})" wire:confirm="Yakin ingin menghapus tipe cuti ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400" title="Hapus Tipe Cuti">
                          <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="closeLeaveTypesModal" class="dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
        Tutup
      </x-secondary-button>
    </x-slot>
  </x-dialog-modal>

</div>
