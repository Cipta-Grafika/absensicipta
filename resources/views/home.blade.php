<x-app-layout>
  <div class="py-0 sm:py-10">
    <div class="mx-auto max-w-7xl px-0 sm:px-6 lg:px-8">
      
      <!-- FLUSH ON MOBILE, ROUNDED CARD ON DESKTOP -->
      <div class="overflow-hidden bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/80 dark:border-gray-800/80 shadow-2xl shadow-black/5 rounded-none sm:rounded-2xl p-4 sm:p-6 lg:p-8 transition-all duration-300">
        @livewire('scan-component')
      </div>

    </div>
  </div>
</x-app-layout>
