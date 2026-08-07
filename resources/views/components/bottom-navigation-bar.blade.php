<!-- FIXED MOBILE BOTTOM NAVIGATION BAR -->
@auth
  @if(!auth()->user()->isAdmin && !auth()->user()->isPayroll)
    <!-- EMPLOYEE MOBILE BOTTOM BAR -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 block md:hidden border-t border-gray-200/90 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur-lg shadow-lg select-none" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
      <div class="flex items-center h-16 max-w-md mx-auto relative px-1">
        <!-- 1. HOME -->
        @php $active = request()->routeIs('home'); @endphp
        <a href="{{ route('home') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-home class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-home class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight">Home</span>
        </a>

        <!-- 2. ABSENSI -->
        @php $active = request()->routeIs('attendance-history'); @endphp
        <a href="{{ route('attendance-history') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-calendar-days class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-calendar-days class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight">Absensi</span>
        </a>

        <!-- 3. GAJI -->
        @php $active = request()->routeIs('user.payslips') || request()->routeIs('user.payslip.*'); @endphp
        <a href="{{ route('user.payslips') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-banknotes class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-banknotes class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight">Gaji</span>
        </a>

        <!-- 4. LEMBUR -->
        @php $active = request()->routeIs('user.overtimes') || request()->routeIs('user.overtime*'); @endphp
        <a href="{{ route('user.overtimes') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-fire class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-fire class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight">Lembur</span>
        </a>

        <!-- 5. GANTI JAM -->
        @php $active = request()->routeIs('user.replacement-hours') || request()->routeIs('user.replacement*'); @endphp
        <a href="{{ route('user.replacement-hours') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-arrow-path class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-arrow-path class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight whitespace-nowrap">Ganti Jam</span>
        </a>
      </div>
    </nav>
  @elseif(auth()->user()->isAdmin)
    <!-- HR / ADMIN MOBILE BOTTOM BAR -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 block md:hidden border-t border-gray-200/90 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur-lg shadow-lg select-none" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
      <div class="flex items-center h-16 max-w-md mx-auto relative px-1">
        <!-- 1. HOME (Dashboard) -->
        @php $active = request()->routeIs('hr.dashboard'); @endphp
        <a href="{{ route('hr.dashboard') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-home class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-home class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight">Home</span>
        </a>

        <!-- 2. ABSENSI -->
        @php $active = request()->routeIs('hr.attendances'); @endphp
        <a href="{{ route('hr.attendances') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-calendar-days class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-calendar-days class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight">Absensi</span>
        </a>

        <!-- 3. KARYAWAN -->
        @php $active = request()->routeIs('hr.employees'); @endphp
        <a href="{{ route('hr.employees') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-user-group class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-user-group class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight">Karyawan</span>
        </a>

        <!-- 4. LEMBUR -->
        @php $active = request()->routeIs('hr.overtime-approvals'); @endphp
        <a href="{{ route('hr.overtime-approvals') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-fire class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-fire class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight">Lembur</span>
        </a>

        <!-- 5. GANTI JAM -->
        @php $active = request()->routeIs('hr.replacement-approvals'); @endphp
        <a href="{{ route('hr.replacement-approvals') }}" class="group relative flex flex-1 flex-col items-center justify-center h-full py-1 text-center transition-colors duration-150 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
          <span class="absolute top-0 h-0.5 w-8 rounded-full bg-blue-600 dark:bg-blue-400 transition-all duration-200 origin-center {{ $active ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0' }}"></span>
          <div class="h-6 w-6 flex items-center justify-center mb-0.5">
            @if($active)
              <x-heroicon-s-arrow-path class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            @else
              <x-heroicon-o-arrow-path class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
            @endif
          </div>
          <span class="text-[11px] leading-none font-bold tracking-tight whitespace-nowrap">Ganti Jam</span>
        </a>
      </div>
    </nav>
  @endif
@endauth
