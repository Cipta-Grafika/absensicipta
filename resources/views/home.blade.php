<x-app-layout>
  <div class="py-0 sm:py-10">
    <div class="mx-auto max-w-7xl px-0 sm:px-6 lg:px-8">
      
      <!-- FLUSH ON MOBILE, ROUNDED CARD ON DESKTOP -->
      <div class="overflow-hidden bg-white shadow-none sm:shadow-xl dark:bg-gray-800 rounded-none sm:rounded-xl p-4 sm:p-6 lg:p-8 border-b sm:border-0 border-gray-200 dark:border-gray-700/60">
        @livewire('scan-component')
      </div>

    </div>
  </div>
</x-app-layout>
