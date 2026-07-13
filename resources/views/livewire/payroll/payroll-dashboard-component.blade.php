<x-slot name="header">
  <div class="relative flex items-center justify-between">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      {{ __('Payroll Dashboard') }} - {{ $currentMonth }}
    </h2>
  </div>
</x-slot>

<div class="py-0 sm:py-12">
  <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
      
      <!-- Card: Total Employees -->
      <div class="flex flex-col rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Karyawan</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $totalEmployees }}</p>
          </div>
          <div class="rounded-full bg-blue-100 p-3 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
            <x-heroicon-o-users class="h-6 w-6" />
          </div>
        </div>
      </div>

      <!-- Card: Total Paid Out -->
      <div class="flex flex-col rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Gaji Dibayar</p>
            <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">Rp {{ number_format($totalPaidOut, 0, ',', '.') }}</p>
          </div>
          <div class="rounded-full bg-green-100 p-3 text-green-600 dark:bg-green-900/50 dark:text-green-400">
            <x-heroicon-o-banknotes class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
          <span class="font-medium text-green-600 dark:text-green-400">{{ $paidCount }}</span>
          <span class="ml-2 text-gray-500 dark:text-gray-400">Slip Gaji (Paid)</span>
        </div>
      </div>

      <!-- Card: Total Draft -->
      <div class="flex flex-col rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Estimasi Draft Gaji</p>
            <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">Rp {{ number_format($totalDraft, 0, ',', '.') }}</p>
          </div>
          <div class="rounded-full bg-yellow-100 p-3 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400">
            <x-heroicon-o-document-text class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
          <span class="font-medium text-yellow-600 dark:text-yellow-400">{{ $draftCount }}</span>
          <span class="ml-2 text-gray-500 dark:text-gray-400">Slip Gaji (Draft)</span>
        </div>
      </div>

      <!-- Card: Quick Actions -->
      <div class="flex flex-col justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 p-6 shadow-sm text-white">
        <h3 class="text-lg font-bold">Akses Cepat</h3>
        <p class="mt-1 text-sm text-indigo-100">Jalankan proses penggajian bulan ini.</p>
        <a href="{{ route('payroll.history') }}" class="mt-4 inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-medium text-indigo-600 shadow-sm hover:bg-gray-50">
          Proses Gaji Sekarang &rarr;
        </a>
      </div>

    </div>

    <!-- Info Banner -->
    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Alur Kerja Sistem Penggajian (Payroll)</h3>
        <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-3 text-sm text-gray-600 dark:text-gray-400">
          
          <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400 font-bold mb-3">1</div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-200">Atur Master Gaji</h4>
            <p class="mt-1">Pilih tipe gaji harian/bulanan dan atur besaran gaji pokok serta tunjangan untuk setiap karyawan di menu Master Gaji.</p>
          </div>

          <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400 font-bold mb-3">2</div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-200">Generate Payroll</h4>
            <p class="mt-1">Tentukan rentang tanggal cut-off absensi. Sistem akan menarik data kehadiran, lembur, dan ganti jam secara otomatis.</p>
          </div>

          <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400 font-bold mb-3">3</div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-200">Review & Paid</h4>
            <p class="mt-1">Tinjau slip gaji yang berstatus Draft di Riwayat Gaji, ubah status ke Paid agar karyawan bisa melihat dan mengunduh slip mereka.</p>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
