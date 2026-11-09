<x-app-layout>
  <x-slot name="header">
    <div class="relative flex items-center">
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        {{ __('Admin') }}
      </h2>
      <div class="absolute right-0 flex items-center gap-2">
        @if (Auth::user()->isSuperadmin)
          <button type="button" x-data @click.prevent="Livewire.dispatch('show-telegram-tester')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-sky-500 hover:bg-sky-600 active:bg-sky-700 text-white shadow-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            title="Uji coba pengiriman pesan CetakiaBot ke grup Telegram">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
            </svg>
            <span>Test Webhook CetakiaBot (Grup)</span>
          </button>

          <x-button type="button" x-data @click.prevent="Livewire.dispatch('show-creating')" class="!py-1.5 !px-3">
            <x-heroicon-o-plus class="mr-1.5 h-4 w-4" />
            Tambah
          </x-button>
        @endif
      </div>
    </div>
  </x-slot>

  <div class="py-0 sm:py-6">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/90 dark:border-white/15 ring-1 ring-black/5 dark:ring-white/10 shadow-2xl shadow-slate-900/10 dark:shadow-black/50 sm:rounded-2xl">
        <div class="p-6 lg:p-8">
          @livewire('admin.master-data.admin')
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
