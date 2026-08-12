@props(['isOpen' => false, 'title' => 'Yakin ingin menghapus data ini secara permanen?', 'deleteAction' => 'delete', 'cancelAction' => 'cancelDelete'])

<div x-data="{ show: @js((bool)$isOpen) }" x-init="$watch('show', value => { if(!value) $wire.call('{{ $cancelAction }}'); })">
    <template x-teleport="body">
        <div x-show="show" class="fixed inset-0 z-[999999] flex items-center justify-center p-4 overflow-y-auto overflow-x-hidden" style="display: none;">
            <!-- BACKDROP GLASSMORPHISM -->
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[999998] bg-gray-900/60 dark:bg-gray-950/75 backdrop-blur-xs transform-gpu"
                 wire:click="{{ $cancelAction }}"></div>

            <!-- MODAL CARD CONTAINER -->
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative z-[999999] w-full max-w-sm sm:max-w-md rounded-2xl bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border border-white/60 dark:border-gray-700/60 shadow-2xl p-6 text-center transform-gpu transition-all max-h-[90vh] overflow-y-auto my-auto select-none">

                <!-- TOP COLORED ACCENT BAR -->
                <div class="absolute top-0 left-0 right-0 h-1.5 rounded-t-2xl z-10 bg-rose-500 dark:bg-rose-600"></div>

                <!-- TOP-RIGHT CLOSE BUTTON (X) -->
                <button type="button" wire:click="{{ $cancelAction }}" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 z-20">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>

                <!-- CENTERED COLORED ICON BADGE -->
                <div class="mx-auto mt-2 mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 shadow-xs shrink-0 relative z-10">
                    <x-heroicon-s-exclamation-triangle class="h-10 w-10 animate-bounce" />
                </div>

                <!-- TITLE -->
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-2 leading-tight tracking-tight relative z-10">
                    Konfirmasi Hapus
                </h3>

                <!-- MESSAGE -->
                <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-6 font-medium px-1 sm:px-3 relative z-10">
                    {{ $title }}
                </div>

                <!-- FOOTER BUTTONS -->
                <div class="flex items-center justify-center gap-3 pt-1 relative z-10">
                    <x-danger-button wire:click="{{ $deleteAction }}" wire:loading.attr="disabled">
                        Ya, Hapus
                    </x-danger-button>
                    <x-secondary-button wire:click="{{ $cancelAction }}" wire:loading.attr="disabled">
                        Batal
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </template>
</div>
