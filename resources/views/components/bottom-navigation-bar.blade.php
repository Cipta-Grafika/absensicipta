<!-- FIXED MOBILE BOTTOM NAVIGATION BAR (FLOATING GLASS DOCK) -->
@auth
  @if(request()->routeIs('payroll.*') || (auth()->user()->isPayroll && !request()->routeIs('hr.*') && !request()->routeIs('home') && !request()->routeIs('attendance-history') && !request()->routeIs('user.*')))
    <!-- PAYROLL MOBILE BOTTOM BAR -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 block md:hidden border-t border-white/80 dark:border-gray-800/80 bg-white/80 dark:bg-gray-900/80 backdrop-blur-2xl shadow-2xl shadow-black/15 select-none transition-colors" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
      <div class="flex items-center h-16 max-w-md mx-auto relative px-1">
        <!-- 1. HOME -->
        @php $active = request()->routeIs('payroll.dashboard'); @endphp
        <a href="{{ route('payroll.dashboard') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-home class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-home class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Home</span>
        </a>

        <!-- 2. PAYROLL (HISTORY) -->
        @php $active = request()->routeIs('payroll.history'); @endphp
        <a href="{{ route('payroll.history') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-banknotes class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-banknotes class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Payroll</span>
        </a>

        <!-- 3. SYIRKAH (SAVING TRANSACTIONS) -->
        @php $active = request()->routeIs('payroll.saving-transactions') || request()->routeIs('payroll.savings'); @endphp
        <a href="{{ route('payroll.saving-transactions') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-building-library class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-building-library class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Syirkah</span>
        </a>

        <!-- 4. MASTER GAJI (EMPLOYEE SALARIES) -->
        @php $active = request()->routeIs('payroll.employee-salaries') || request()->routeIs('payroll.payment-methods'); @endphp
        <a href="{{ route('payroll.employee-salaries') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-calculator class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-calculator class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight whitespace-nowrap">Master Gaji</span>
        </a>

        <!-- 5. PINJAMAN (LOANS) -->
        @php $active = request()->routeIs('payroll.loans'); @endphp
        <a href="{{ route('payroll.loans') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-hand-raised class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-hand-raised class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Pinjaman</span>
        </a>
      </div>
    </nav>
  @elseif(request()->routeIs('hr.*') || (auth()->user()->isAdmin && !request()->routeIs('payroll.*') && !request()->routeIs('home') && !request()->routeIs('attendance-history') && !request()->routeIs('user.*')))
    <!-- HR / ADMIN MOBILE BOTTOM BAR -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 block md:hidden border-t border-white/80 dark:border-gray-800/80 bg-white/80 dark:bg-gray-900/80 backdrop-blur-2xl shadow-2xl shadow-black/15 select-none transition-colors" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
      <div class="flex items-center h-16 max-w-md mx-auto relative px-1">
        <!-- 1. HOME (Dashboard) -->
        @php $active = request()->routeIs('hr.dashboard'); @endphp
        <a href="{{ route('hr.dashboard') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-home class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-home class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Home</span>
        </a>

        <!-- 2. ABSENSI -->
        @php $active = request()->routeIs('hr.attendances'); @endphp
        <a href="{{ route('hr.attendances') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-calendar-days class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-calendar-days class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Absensi</span>
        </a>

        <!-- 3. KARYAWAN -->
        @php $active = request()->routeIs('hr.employees'); @endphp
        <a href="{{ route('hr.employees') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-user-group class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-user-group class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Karyawan</span>
        </a>

        <!-- 4. LEMBUR -->
        @php $active = request()->routeIs('hr.overtime-approvals'); @endphp
        <a href="{{ route('hr.overtime-approvals') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-fire class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-fire class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Lembur</span>
        </a>

        <!-- 5. GANTI JAM -->
        @php $active = request()->routeIs('hr.replacement-approvals'); @endphp
        <a href="{{ route('hr.replacement-approvals') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-arrow-path class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-arrow-path class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight whitespace-nowrap">Ganti Jam</span>
        </a>
      </div>
    </nav>
  @else
    <!-- EMPLOYEE MOBILE BOTTOM BAR -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 block md:hidden border-t border-white/80 dark:border-gray-800/80 bg-white/80 dark:bg-gray-900/80 backdrop-blur-2xl shadow-2xl shadow-black/15 select-none transition-colors" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
      <div class="flex items-center h-16 max-w-md mx-auto relative px-1">
        <!-- 1. HOME -->
        @php $active = request()->routeIs('home'); @endphp
        <a href="{{ route('home') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-home class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-home class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Home</span>
        </a>

        <!-- 2. ABSENSI -->
        @php $active = request()->routeIs('attendance-history'); @endphp
        <a href="{{ route('attendance-history') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-calendar-days class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-calendar-days class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Absensi</span>
        </a>

        <!-- 3. GAJI -->
        @php $active = request()->routeIs('user.payslips') || request()->routeIs('user.payslip.*'); @endphp
        <a href="{{ route('user.payslips') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-banknotes class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-banknotes class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Gaji</span>
        </a>

        <!-- 4. LEMBUR -->
        @php $active = request()->routeIs('user.overtimes') || request()->routeIs('user.overtime*'); @endphp
        <a href="{{ route('user.overtimes') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-fire class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-fire class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight">Lembur</span>
        </a>

        <!-- 5. GANTI JAM -->
        @php $active = request()->routeIs('user.replacement-hours') || request()->routeIs('user.replacement*'); @endphp
        <a href="{{ route('user.replacement-hours') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-all duration-150 {{ $active ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
          <span class="absolute top-0 h-1 w-7 rounded-b-full bg-sky-500 dark:bg-sky-400 shadow-xs shadow-sky-500/50 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-7 w-7 flex items-center justify-center mb-0.5 rounded-xl transition-all duration-150 {{ $active ? 'bg-sky-500/10 dark:bg-sky-400/20 scale-105' : '' }}">
            @if($active)
              <x-heroicon-s-arrow-path class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            @else
              <x-heroicon-o-arrow-path class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none tracking-tight whitespace-nowrap">Ganti Jam</span>
        </a>
      </div>
    </nav>
  @endif
@endauth
