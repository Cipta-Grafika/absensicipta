<nav x-data="{ open: false }" class="fixed top-0 left-0 right-0 z-50 h-16 border-b border-sky-200/80 dark:border-gray-800/80 bg-white/85 dark:bg-gray-900/85 backdrop-blur-xl shadow-xs transition-colors">
  <!-- Primary Navigation Menu -->
  <div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="flex h-16 justify-between">
      <div class="flex">
        <!-- Logo -->
        <div class="flex shrink-0 items-center">
          <a href="{{ Auth::user()->isAdmin ? route('hr.dashboard') : ((Auth::user()->isPayroll || Auth::user()->isOwner) ? route('payroll.dashboard') : (Auth::user()->isSyirkah ? route('payroll.saving-transactions') : route('home'))) }}" class="block h-14 w-14">
            <x-application-mark class="block h-full w-full object-contain" />
          </a>
        </div>

        <!-- Navigation Links -->
        <div class="hidden space-x-4 sm:-my-px sm:ms-6 sm:flex sm:space-x-8">
          @if (Auth::user()->isAdmin && !request()->routeIs('hr.*'))
            <x-nav-link href="{{ route('hr.dashboard') }}" :active="request()->routeIs('hr.dashboard')">
              {{ __('Dashboard') }}
            </x-nav-link>
            @if (Auth::user()?->isSuperadmin)
              <x-nav-link href="{{ route('hr.barcodes') }}" :active="request()->routeIs('hr.barcodes')">
                {{ __('Barcode') }}
              </x-nav-link>
            @endif
            <x-nav-link class="hidden md:inline-flex" href="{{ route('hr.attendances') }}" :active="request()->routeIs('hr.attendances')">
              {{ __('Attendance') }}
            </x-nav-link>
            <x-nav-link class="hidden md:inline-flex" href="{{ route('hr.replacement-approvals') }}" :active="request()->routeIs('hr.replacement-approvals')">
              Ganti Jam
            </x-nav-link>
            <x-nav-link class="hidden md:inline-flex" href="{{ route('hr.overtime-approvals') }}" :active="request()->routeIs('hr.overtime-approvals')">
              Lembur
            </x-nav-link>
            <x-nav-link class="hidden md:inline-flex" href="{{ route('hr.employees') }}" :active="request()->routeIs('hr.employees')">
              {{ __('Employee') }}
            </x-nav-link>
            @if (!Auth::user()?->isSuperadmin)
              <x-nav-link class="hidden md:inline-flex" href="{{ route('payroll.saving-transactions') }}" :active="request()->routeIs('payroll.saving-transactions')">
                Syirkah
              </x-nav-link>
            @endif
            <x-nav-dropdown :active="request()->routeIs('hr.work-schedules') || request()->routeIs('hr.holidays')" triggerClasses="text-nowrap">
              <x-slot name="trigger">
                Jadwal & Libur
                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
              </x-slot>
              <x-slot name="content">
                <x-dropdown-link href="{{ route('hr.work-schedules') }}" :active="request()->routeIs('hr.work-schedules')">
                  Jadwal Rolling (Roster)
                </x-dropdown-link>
                @if (Auth::user()?->isSuperadmin)
                  <x-dropdown-link href="{{ route('hr.holidays') }}" :active="request()->routeIs('hr.holidays')">
                    Manajemen Hari Libur
                  </x-dropdown-link>
                @endif
              </x-slot>
            </x-nav-dropdown>
            <x-nav-dropdown :active="request()->routeIs('hr.masters.*')" triggerClasses="text-nowrap">
              <x-slot name="trigger">
                {{ __('Master Data') }}
                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
              </x-slot>
              <x-slot name="content">
                <x-dropdown-link class="md:hidden" href="{{ route('hr.attendances') }}" :active="request()->routeIs('hr.attendances')">
                  {{ __('Attendance') }}
                </x-dropdown-link>
                <x-dropdown-link class="md:hidden" href="{{ route('hr.employees') }}" :active="request()->routeIs('hr.employees')">
                  {{ __('Employee') }}
                </x-dropdown-link>
                @if (Auth::user()?->isSuperadmin)
                  <x-dropdown-link href="{{ route('hr.masters.division') }}" :active="request()->routeIs('hr.masters.division')">
                    {{ __('Division') }}
                  </x-dropdown-link>
                  <x-dropdown-link href="{{ route('hr.masters.job-title') }}" :active="request()->routeIs('hr.masters.job-title')">
                    {{ __('Job Title') }}
                  </x-dropdown-link>
                  <x-dropdown-link href="{{ route('hr.masters.education') }}" :active="request()->routeIs('hr.masters.education')">
                    {{ __('Education') }}
                  </x-dropdown-link>
                  <x-dropdown-link href="{{ route('hr.masters.leaderboard') }}" :active="request()->routeIs('hr.masters.leaderboard')">
                    Leaderboard Kerajinan
                  </x-dropdown-link>
                  <x-dropdown-link href="{{ route('hr.masters.scan-feedback') }}" :active="request()->routeIs('hr.masters.scan-feedback')">
                    Scan Feedback Ucapan
                  </x-dropdown-link>
                @endif
                @if (Auth::user()?->isAdmin)
                  <x-dropdown-link href="{{ route('hr.masters.shift') }}" :active="request()->routeIs('hr.masters.shift')">
                    {{ __('Shift') }}
                  </x-dropdown-link>
                  <x-dropdown-link href="{{ route('hr.masters.overtime-rate') }}" :active="request()->routeIs('hr.masters.overtime-rate')">
                    Tarif Lembur
                  </x-dropdown-link>
                  <hr>
                @endif
                @if (Auth::user()?->isSuperadmin)
                  <x-dropdown-link href="{{ route('hr.masters.admin') }}" :active="request()->routeIs('hr.masters.admin')">
                    {{ __('Admin') }}
                  </x-dropdown-link>
                @endif
              </x-slot>
            </x-nav-dropdown>
            <x-nav-dropdown :active="request()->routeIs('hr.import-export.*')" triggerClasses="text-nowrap">
              <x-slot name="trigger">
                {{ __('Import & Export') }}
                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
              </x-slot>
              <x-slot name="content">
                <x-dropdown-link href="{{ route('hr.import-export.users') }}" :active="request()->routeIs('hr.import-export.users')">
                  {{ __('Employee') }}/{{ __('Admin') }}
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('hr.import-export.attendances') }}" :active="request()->routeIs('hr.import-export.attendances')">
                  {{ __('Attendance') }}
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('hr.import-export.overtimes') }}" :active="request()->routeIs('hr.import-export.overtimes')">
                  Lembur
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('hr.import-export.work-schedules') }}" :active="request()->routeIs('hr.import-export.work-schedules')">
                  Jadwal Rolling
                </x-dropdown-link>
                @if (Auth::user()?->isSuperadmin)
                  <x-dropdown-link href="{{ route('hr.import-export.holidays') }}" :active="request()->routeIs('hr.import-export.holidays')">
                    Hari Libur
                  </x-dropdown-link>
                @endif
              </x-slot>
            </x-nav-dropdown>
          @endif
          @if ((Auth::user()->isPayroll || Auth::user()->isOwner) && !request()->routeIs('payroll.*'))
            <x-nav-link class="hidden md:inline-flex text-nowrap" href="{{ route('payroll.dashboard') }}" :active="request()->routeIs('payroll.dashboard')">
              Dashboard
            </x-nav-link>
            <x-nav-dropdown :active="request()->routeIs('payroll.employee-salaries') || request()->routeIs('payroll.taxes') || request()->routeIs('payroll.error-deductions') || request()->routeIs('payroll.payment-methods') || request()->routeIs('payroll.savings')" triggerClasses="text-nowrap">
              <x-slot name="trigger">
                Master Data
                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
              </x-slot>
              <x-slot name="content">
                <x-dropdown-link href="{{ route('payroll.employee-salaries') }}" :active="request()->routeIs('payroll.employee-salaries')">
                  Master Gaji
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.taxes') }}" :active="request()->routeIs('payroll.taxes')">
                  Master Pajak PPh 21
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.error-deductions') }}" :active="request()->routeIs('payroll.error-deductions')">
                  Potongan Log Error
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.payment-methods') }}" :active="request()->routeIs('payroll.payment-methods')">
                  Metode Pembayaran
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.savings') }}" :active="request()->routeIs('payroll.savings')">
                  Syirkah
                </x-dropdown-link>
              </x-slot>
            </x-nav-dropdown>
            <x-nav-link class="hidden md:inline-flex text-nowrap" href="{{ route('payroll.history') }}" :active="request()->routeIs('payroll.history')">
              Riwayat Gaji
            </x-nav-link>
            <x-nav-dropdown :active="request()->routeIs('payroll.saving-transactions') || request()->routeIs('payroll.loans') || request()->routeIs('payroll.flexible-deductions')" triggerClasses="text-nowrap">
              <x-slot name="trigger">
                Koperasi & Syirkah
                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
              </x-slot>
              <x-slot name="content">
                <x-dropdown-link href="{{ route('payroll.saving-transactions') }}" :active="request()->routeIs('payroll.saving-transactions')">
                  Mutasi Syirkah
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.loans') }}" :active="request()->routeIs('payroll.loans')">
                  Pinjaman Karyawan
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.flexible-deductions') }}" :active="request()->routeIs('payroll.flexible-deductions')">
                  Potongan Fleksibel
                </x-dropdown-link>
              </x-slot>
            </x-nav-dropdown>
            <x-nav-dropdown :active="request()->routeIs('payroll.import-export.*')" triggerClasses="text-nowrap">
              <x-slot name="trigger">
                Import & Export
                <x-heroicon-o-chevron-down class="ms-2 h-5 w-5 text-gray-400" />
              </x-slot>
              <x-slot name="content">
                <x-dropdown-link href="{{ route('payroll.import-export.employee-salaries') }}" :active="request()->routeIs('payroll.import-export.employee-salaries')">
                  Master Gaji
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.import-export.payment-methods') }}" :active="request()->routeIs('payroll.import-export.payment-methods')">
                  Metode Pembayaran
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.import-export.savings') }}" :active="request()->routeIs('payroll.import-export.savings')">
                  Syirkah
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('payroll.import-export.saving-transactions') }}" :active="request()->routeIs('payroll.import-export.saving-transactions')">
                  Mutasi Syirkah
                </x-dropdown-link>
                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                <x-dropdown-link href="{{ route('payroll.export-bank') }}" :active="request()->routeIs('payroll.export-bank') || request()->routeIs('payroll.import-export.bank-transfers')">
                  <span class="font-semibold text-sky-600 dark:text-sky-400">Export Transfer Bank (BCA)</span>
                </x-dropdown-link>
              </x-slot>
            </x-nav-dropdown>
          @endif
          @if (!Auth::user()->isAdmin && !Auth::user()->isPayroll && !Auth::user()->isOwner && !Auth::user()->isSyirkah)
            <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">
              {{ __('Home') }}
            </x-nav-link>

            <x-nav-link href="{{ route('attendance-history') }}" :active="request()->routeIs('attendance-history')">
              Riwayat Absen
            </x-nav-link>
            <x-nav-link href="{{ route('user.overtimes') }}" :active="request()->routeIs('user.overtimes')">
              Lembur
            </x-nav-link>
            <x-nav-link href="{{ route('user.replacement-hours') }}" :active="request()->routeIs('user.replacement-hours')">
              Ganti Jam
            </x-nav-link>
            <x-nav-link href="{{ route('user.payslips') }}" :active="request()->routeIs('user.payslips')">
              Slip Gaji
            </x-nav-link>
            <x-nav-link href="{{ route('user.syirkah') }}" :active="request()->routeIs('user.syirkah')">
              Syirkah
            </x-nav-link>
          @endif
        </div>
      </div>

      <div class="flex items-center gap-1.5 sm:gap-2">
        <div class="hidden sm:ms-6 sm:flex sm:items-center">
          <x-theme-toggle />

          <!-- Settings Dropdown (Desktop) -->
          <div class="relative ms-3">
            <x-dropdown align="right" width="48">
              <x-slot name="trigger">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                  <button
                    class="flex rounded-full border-2 border-transparent text-sm transition focus:border-gray-300 focus:outline-none">
                    <img class="h-8 w-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}"
                      alt="{{ Auth::user()->name }}" />
                  </button>
                @else
                  <span class="inline-flex rounded-md">
                    <button type="button"
                      class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:bg-gray-50 focus:outline-none active:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300 dark:focus:bg-gray-700 dark:active:bg-gray-700">
                      {{ Auth::user()->name }}

                      <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                      </svg>
                    </button>
                  </span>
                @endif
              </x-slot>

              <x-slot name="content">
                <!-- Account Management -->
                <div class="block px-4 py-2 text-xs text-gray-400">
                  {{ __('Manage Account') }}
                </div>

                <x-dropdown-link href="{{ route('profile.show') }}">
                  {{ __('Profile') }}
                </x-dropdown-link>

                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                  <x-dropdown-link href="{{ route('api-tokens.index') }}">
                    {{ __('API Tokens') }}
                  </x-dropdown-link>
                @endif

                <div class="border-t border-gray-200 dark:border-gray-600"></div>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                  @csrf

                  <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();" class="!text-red-600 hover:!bg-red-50 dark:!text-red-500 dark:hover:!bg-red-900/50">
                    {{ __('Log Out') }}
                  </x-dropdown-link>
                </form>
              </x-slot>
            </x-dropdown>
          </div>
        </div>

        <x-theme-toggle class="sm:hidden" />

        <!-- Hamburger (Mobile & Tablet) -->
        <div class="flex items-center {{ (request()->routeIs('hr.*') || request()->routeIs('payroll.*')) ? 'lg:hidden' : 'sm:hidden' }}">
          @if (request()->routeIs('hr.*') || request()->routeIs('payroll.*'))
            <button type="button"
              @click="$dispatch('toggle-portal-sidebar')"
              title="Buka Navigasi Sidebar"
              class="inline-flex items-center justify-center rounded-lg p-2 text-sky-600 hover:bg-sky-50 focus:bg-sky-50 focus:outline-none dark:text-sky-400 dark:hover:bg-gray-800 dark:focus:bg-gray-800 transition duration-150 cursor-pointer">
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          @else
            <button @click="open = ! open"
              class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400">
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round"
                  stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                  stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          @endif
        </div>

        <!-- Mobile Profile Dropdown (Far Right) -->
        <div class="relative flex items-center sm:hidden">
          <x-dropdown align="right" width="48">
            <x-slot name="trigger">
              @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <button type="button" class="flex rounded-full border-2 border-transparent text-sm transition focus:border-gray-300 focus:outline-none">
                  <img class="h-8 w-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                </button>
              @else
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/60 text-xs font-bold text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700 focus:outline-none">
                  {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </button>
              @endif
            </x-slot>

            <x-slot name="content">
              <!-- Account Info Header -->
              <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</div>
              </div>

              <!-- Account Management -->
              <x-dropdown-link href="{{ route('profile.show') }}">
                {{ __('Profil') }}
              </x-dropdown-link>

              @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                <x-dropdown-link href="{{ route('api-tokens.index') }}">
                  {{ __('API Tokens') }}
                </x-dropdown-link>
              @endif

              <div class="border-t border-gray-100 dark:border-gray-700"></div>

              <!-- Authentication -->
              <form method="POST" action="{{ route('logout') }}" x-data>
                @csrf
                <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();" class="!text-red-600 hover:!bg-red-50 dark:!text-red-500 dark:hover:!bg-red-900/50">
                  {{ __('Keluar') }}
                </x-dropdown-link>
              </form>
            </x-slot>
          </x-dropdown>
        </div>
      </div>
    </div>
  </div>

  @unless (request()->routeIs('hr.*') || request()->routeIs('payroll.*'))
  <!-- Responsive Navigation Menu (Non-Portal Pages) -->
  <div :class="{ 'block': open, 'hidden': !open }" class="absolute left-0 top-16 z-50 hidden w-full bg-white shadow-lg dark:bg-gray-800 sm:hidden">
    <div class="space-y-1 pb-3 pt-2">
      @if (Auth::user()->isAdmin)
        <x-responsive-nav-link href="{{ route('hr.dashboard') }}" :active="request()->routeIs('hr.dashboard')">
          {{ __('Dashboard') }}
        </x-responsive-nav-link>
        @if (Auth::user()?->isSuperadmin)
          <x-responsive-nav-link href="{{ route('hr.barcodes') }}" :active="request()->routeIs('hr.barcodes')">
            {{ __('Barcode') }}
          </x-responsive-nav-link>
        @endif
        <x-responsive-nav-link href="{{ route('hr.attendances') }}" :active="request()->routeIs('hr.attendances')">
          {{ __('Attendance') }}
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('hr.replacement-approvals') }}" :active="request()->routeIs('hr.replacement-approvals')">
          Ganti Jam
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('hr.overtime-approvals') }}" :active="request()->routeIs('hr.overtime-approvals')">
          Lembur
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('hr.employees') }}" :active="request()->routeIs('hr.employees')">
          {{ __('Employee') }}
        </x-responsive-nav-link>
        @if (!Auth::user()?->isSuperadmin)
          <x-responsive-nav-link href="{{ route('payroll.saving-transactions') }}" :active="request()->routeIs('payroll.saving-transactions')">
            Syirkah
          </x-responsive-nav-link>
        @endif
        @if (Auth::user()?->isSuperadmin)
          <x-responsive-nav-link href="{{ route('hr.masters.division') }}" :active="request()->routeIs('hr.masters.division')">
            {{ __('Division') }}
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('hr.masters.job-title') }}" :active="request()->routeIs('hr.masters.job-title')">
            {{ __('Job Title') }}
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('hr.masters.education') }}" :active="request()->routeIs('hr.masters.education')">
            {{ __('Education') }}
          </x-responsive-nav-link>
        @endif
        @if (Auth::user()?->isAdmin)
          <x-responsive-nav-link href="{{ route('hr.masters.shift') }}" :active="request()->routeIs('hr.masters.shift')">
            {{ __('Shift') }}
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('hr.masters.overtime-rate') }}" :active="request()->routeIs('hr.masters.overtime-rate')">
            Tarif Lembur
          </x-responsive-nav-link>
        @endif
        @if (Auth::user()?->isSuperadmin)
          <x-responsive-nav-link href="{{ route('hr.masters.admin') }}" :active="request()->routeIs('hr.masters.admin')">
            {{ __('Admin Management') }}
          </x-responsive-nav-link>
        @endif
        <x-responsive-nav-link href="{{ route('hr.import-export.users') }}" :active="request()->routeIs('hr.import-export.users')">
          Import & Export Karyawan/Admin
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('hr.import-export.attendances') }}" :active="request()->routeIs('hr.import-export.attendances')">
          Import & Export Absensi
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('hr.import-export.overtimes') }}" :active="request()->routeIs('hr.import-export.overtimes')">
          Import & Export Lembur
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('hr.import-export.work-schedules') }}" :active="request()->routeIs('hr.import-export.work-schedules')">
          Import & Export Jadwal Rolling
        </x-responsive-nav-link>
        @if (Auth::user()?->isSuperadmin)
          <x-responsive-nav-link href="{{ route('hr.import-export.holidays') }}" :active="request()->routeIs('hr.import-export.holidays')">
            Import & Export Hari Libur
          </x-responsive-nav-link>
        @endif
      @endif
      @if ((Auth::user()->isPayroll || Auth::user()->isOwner) && !request()->routeIs('payroll.*'))
        <div class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
          <div class="px-4 text-xs text-gray-400">Payroll</div>
          <x-responsive-nav-link href="{{ route('payroll.dashboard') }}" :active="request()->routeIs('payroll.dashboard')">
            Dashboard
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.employee-salaries') }}" :active="request()->routeIs('payroll.employee-salaries')">
            Master Gaji
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.taxes') }}" :active="request()->routeIs('payroll.taxes')">
            Master Pajak PPh 21
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.error-deductions') }}" :active="request()->routeIs('payroll.error-deductions')">
            Potongan Log Error
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.payment-methods') }}" :active="request()->routeIs('payroll.payment-methods')">
            Metode Pembayaran
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.history') }}" :active="request()->routeIs('payroll.history')">
            Riwayat Gaji
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.savings') }}" :active="request()->routeIs('payroll.savings')">
            Syirkah
          </x-responsive-nav-link>
          <div class="px-4 text-xs text-gray-400 mt-2">Koperasi & Syirkah</div>
          <x-responsive-nav-link href="{{ route('payroll.saving-transactions') }}" :active="request()->routeIs('payroll.saving-transactions')">
            Mutasi Syirkah
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.loans') }}" :active="request()->routeIs('payroll.loans')">
            Pinjaman Karyawan
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.flexible-deductions') }}" :active="request()->routeIs('payroll.flexible-deductions')">
            Potongan Fleksibel
          </x-responsive-nav-link>
          <div class="px-4 text-xs text-gray-400 mt-2">Import & Export</div>
          <x-responsive-nav-link href="{{ route('payroll.import-export.employee-salaries') }}" :active="request()->routeIs('payroll.import-export.employee-salaries')">
            Master Gaji
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.import-export.payment-methods') }}" :active="request()->routeIs('payroll.import-export.payment-methods')">
            Metode Pembayaran
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.import-export.savings') }}" :active="request()->routeIs('payroll.import-export.savings')">
            Syirkah
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.import-export.saving-transactions') }}" :active="request()->routeIs('payroll.import-export.saving-transactions')">
            Mutasi Syirkah
          </x-responsive-nav-link>
          <x-responsive-nav-link href="{{ route('payroll.export-bank') }}" :active="request()->routeIs('payroll.export-bank') || request()->routeIs('payroll.import-export.bank-transfers')">
            <span class="font-semibold text-sky-600 dark:text-sky-400">Export Transfer Bank (BCA)</span>
          </x-responsive-nav-link>
        </div>
      @endif
      @if (!Auth::user()->isAdmin && !Auth::user()->isPayroll && !Auth::user()->isOwner)
        <x-responsive-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">
          {{ __('Home') }}
        </x-responsive-nav-link>

        <x-responsive-nav-link href="{{ route('attendance-history') }}" :active="request()->routeIs('attendance-history')">
          Riwayat Absen
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('user.overtimes') }}" :active="request()->routeIs('user.overtimes')">
          Lembur
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('user.replacement-hours') }}" :active="request()->routeIs('user.replacement-hours')">
          Ganti Jam
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('user.payslips') }}" :active="request()->routeIs('user.payslips')">
          Slip Gaji
        </x-responsive-nav-link>
        <x-responsive-nav-link href="{{ route('user.syirkah') }}" :active="request()->routeIs('user.syirkah')">
          Syirkah
        </x-responsive-nav-link>
      @endif
    </div>
  </div>
  @endunless
</nav>
