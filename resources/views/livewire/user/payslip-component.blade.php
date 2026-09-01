<x-slot name="header">
  <div class="relative flex items-center">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Slip Gaji') }}
    </h2>
    <div class="absolute right-0 flex items-center gap-2">
      <x-secondary-button href="#" x-data @click.prevent="$dispatch('open-filter')">
        <x-heroicon-o-funnel class="sm:mr-1.5 h-4 w-4 text-sky-500" />
        <span class="hidden sm:inline">Filter</span>
      </x-secondary-button>
    </div>
  </div>
</x-slot>

<div class="flex-grow flex flex-col py-0 sm:py-10" x-data="{ filterOpen: false }" @open-filter.window="filterOpen = true">
  <div class="mx-auto w-full max-w-7xl px-0 sm:px-6 lg:px-8 flex-grow flex flex-col">
    
    <x-filter-sidebar maxWidth="sm">
      <x-slot name="title">Filter Data</x-slot>
      <x-slot name="actions">
        <button type="button" wire:click="$set('month', '')" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700" title="Reset Filters">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
        </button>
      </x-slot>
      
      <x-slot name="content">
        <div class="flex flex-col gap-6">
          <div>
            <x-label for="month_filter" value="Pilih Bulan Periode" class="mb-1"></x-label>
            <x-input type="month" id="month_filter" class="w-full block" wire:model.live="month" />
          </div>
        </div>
      </x-slot>
    </x-filter-sidebar>

    <div class="overflow-hidden bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/80 dark:border-gray-800/80 shadow-2xl shadow-black/5 rounded-none sm:rounded-2xl flex-grow flex flex-col transition-all duration-300">
      <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 flex-grow flex flex-col justify-between">

        @if ($payrolls->isNotEmpty())
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($payrolls as $pr)
              <div x-data="{ revealed: false }"
                   class="relative group flex flex-col rounded-2xl border border-white/80 dark:border-gray-800/80 bg-white/60 dark:bg-gray-800/60 backdrop-blur-md p-5 shadow-sm transition-all hover:shadow-md overflow-hidden">
                
                <!-- Card Header -->
                <div class="flex items-center justify-between border-b border-gray-100/80 pb-3 dark:border-gray-700/80 relative z-30">
                  <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                      {{ \Carbon\Carbon::parse($pr->period_month)->format('F Y') }}
                    </h3>
                    <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-2.5 py-0.5 text-xs font-semibold leading-5 text-emerald-800 dark:text-emerald-400">Telah Dibayar</span>
                  </div>

                  <!-- Quick Eye Toggle Button -->
                  <button type="button" 
                          @click.stop="revealed = !revealed"
                          :title="revealed ? 'Sembunyikan Rincian Gaji' : 'Tampilkan Rincian Gaji'"
                          class="p-1.5 rounded-xl border border-white/80 dark:border-gray-700/80 bg-white/80 dark:bg-gray-800/80 text-gray-400 hover:text-sky-500 dark:hover:text-sky-400 backdrop-blur-md transition-all cursor-pointer">
                    <span x-show="revealed" style="display: none;">
                      <x-heroicon-o-eye class="h-4 w-4 shrink-0" />
                    </span>
                    <span x-show="!revealed">
                      <x-heroicon-o-eye-slash class="h-4 w-4 shrink-0" />
                    </span>
                  </button>
                </div>
                
                <!-- Payslip Details Body (Statically blurred by default, unblurred when revealed) -->
                <div class="mt-4 flex-1 relative transition-all duration-300 filter blur-md select-none pointer-events-none"
                     :class="revealed ? 'filter-none select-auto pointer-events-auto' : 'filter blur-md select-none pointer-events-none'">
                  <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Pemasukan</div>
                  <div class="text-lg font-semibold text-emerald-600 dark:text-emerald-400"
                       x-text="revealed ? 'Rp {{ number_format($pr->basic_salary_earned + $pr->total_allowance + $pr->total_overtime_pay, 0, ',', '.') }}' : 'Rp ••••••••'">
                    Rp ••••••••
                  </div>
                  
                  <div class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400">Total Potongan</div>
                  <div class="text-lg font-semibold text-rose-600 dark:text-rose-400"
                       x-text="revealed ? 'Rp {{ number_format($pr->total_deduction, 0, ',', '.') }}' : 'Rp ••••••••'">
                    Rp ••••••••
                  </div>
                  
                  <div class="mt-4 border-t border-gray-100/80 pt-3 dark:border-gray-700/80">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Gaji Bersih (Take Home Pay)</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white"
                         x-text="revealed ? 'Rp {{ number_format($pr->net_salary, 0, ',', '.') }}' : 'Rp ••••••••'">
                      Rp ••••••••
                    </div>
                  </div>
                  
                  <div class="mt-5 text-center">
                    <button type="button" 
                            wire:click="downloadPdf('{{ $pr->id }}')" 
                            wire:loading.attr="disabled"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-500 transition-all cursor-pointer">
                      <x-heroicon-o-arrow-down-tray class="mr-2 h-4 w-4 shrink-0" />
                      <span>Unduh PDF</span>
                    </button>
                    <div class="mt-2 text-center text-[11px] text-gray-400 dark:text-gray-500 flex items-center justify-center gap-1">
                      <x-heroicon-o-lock-closed class="w-3 h-3 shrink-0" />
                      <span>Password PDF: Password Login Akun</span>
                    </div>
                  </div>
                </div>

                <!-- SENSITIVE CONTENT GLASS OVERLAY (Visible by default, hidden when revealed) -->
                <div x-show="!revealed"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click="revealed = true"
                     class="absolute inset-x-0 bottom-0 top-14 z-20 flex flex-col items-center justify-center bg-white/40 dark:bg-gray-900/40 backdrop-blur-md cursor-pointer p-4 text-center select-none group-hover:bg-white/30 dark:group-hover:bg-gray-900/30 transition-all">
                  <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/90 dark:bg-gray-800/90 text-sky-500 dark:text-sky-400 shadow-md backdrop-blur-md mb-2.5 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-eye-slash class="h-6 w-6 shrink-0" />
                  </div>
                  <span class="text-sm font-bold text-gray-900 dark:text-white tracking-wide">Konten Sensitif</span>
                  <span class="mt-1 text-xs font-medium text-gray-600 dark:text-gray-300">Klik kartu untuk memperlihatkan rincian gaji</span>
                </div>
              </div>
            @endforeach
          </div>

          <div class="mt-6">
            {{ $payrolls->links() }}
          </div>
        @else
          <div class="flex flex-1 flex-col items-center justify-center text-center py-12 px-4">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-500 dark:text-blue-400">
              <x-heroicon-o-banknotes class="h-8 w-8 stroke-1.5" />
            </div>
            <h3 class="text-base sm:text-lg font-bold text-gray-800 dark:text-gray-200">
              Belum ada data slip gaji yang dibayarkan.
            </h3>
            <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-sm">
              Slip gaji Anda akan otomatis tampil di sini setelah diproses dan disetujui oleh tim HR/Payroll.
            </p>
          </div>
        @endif

      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Password Login untuk Enkripsi PDF -->
  <x-dialog-modal wire:model.live="showPasswordModal">
    <x-slot name="title">
      <div class="flex items-center gap-2">
        <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
          <x-heroicon-o-lock-closed class="w-5 h-5 shrink-0" />
        </div>
        <span class="font-semibold text-gray-900 dark:text-gray-100">Konfirmasi Password Login</span>
      </div>
    </x-slot>

    <x-slot name="content">
      <div class="text-sm text-gray-600 dark:text-gray-400">
        File PDF Slip Gaji diamankan menggunakan <strong>password login akun Anda</strong>. Masukkan password login Anda untuk melanjutkan proses pengunduhan.
      </div>

      <div class="mt-4" x-data="{}" x-on:shown.window="setTimeout(() => $refs.confirmPasswordInput.focus(), 250)">
        <x-label for="confirm_login_password" value="Password Login Akun Anda" class="mb-1" />
        <x-input type="password" 
                 id="confirm_login_password" 
                 class="mt-1 block w-full" 
                 placeholder="Masukkan password login Anda"
                 x-ref="confirmPasswordInput"
                 wire:model="password"
                 wire:keydown.enter="confirmPasswordAndDownload" />
        <x-input-error for="password" class="mt-2" />
      </div>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$set('showPasswordModal', false)" wire:loading.attr="disabled">
        Batal
      </x-secondary-button>

      <x-button class="ms-3 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 focus:ring-emerald-500" wire:click="confirmPasswordAndDownload" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="confirmPasswordAndDownload">Verifikasi & Unduh PDF</span>
        <span wire:loading wire:target="confirmPasswordAndDownload">Memproses...</span>
      </x-button>
    </x-slot>
  </x-dialog-modal>
</div>
