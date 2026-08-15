@props(['id' => null, 'maxWidth' => 'md', 'zIndex' => '999999'])

<x-modal :id="$id" :maxWidth="$maxWidth" :zIndex="$zIndex" {{ $attributes }}>
    <div class="relative bg-white/95 dark:bg-gray-900/95 backdrop-blur-md p-6 text-center select-none overflow-hidden rounded-2xl">
        <!-- TOP COLORED ACCENT BAR -->
        <div class="absolute top-0 left-0 right-0 h-1.5 rounded-t-2xl z-10 bg-rose-500 dark:bg-rose-600"></div>

        <!-- TOP-RIGHT CLOSE BUTTON (X) -->
        <button type="button" x-on:click="show = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 z-20">
            <x-heroicon-o-x-mark class="h-5 w-5" />
        </button>

        <!-- CENTERED COLORED ICON BADGE (RED / ROSE WARNING TRIANGLE) -->
        <div class="mx-auto mt-2 mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 shadow-xs shrink-0 relative z-10">
            <x-heroicon-s-exclamation-triangle class="h-10 w-10 animate-bounce" />
        </div>

        <!-- TITLE -->
        @if (isset($title))
            <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-2 leading-tight tracking-tight relative z-10">
                {{ $title }}
            </h3>
        @endif

        <!-- CONTENT / MESSAGE -->
        @if (isset($content))
            <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-6 font-medium px-1 sm:px-3 relative z-10">
                {{ $content }}
            </div>
        @endif

        <!-- FOOTER BUTTONS -->
        @if (isset($footer))
            <div class="flex items-center justify-center gap-3 pt-1 relative z-10">
                {{ $footer }}
            </div>
        @endif
    </div>
</x-modal>
