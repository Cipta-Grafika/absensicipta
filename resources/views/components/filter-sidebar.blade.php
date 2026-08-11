@props(['id' => 'filter-sidebar', 'maxWidth' => 'md'])

@php
  $maxWidth = [
      'sm' => 'sm:max-w-sm',
      'md' => 'sm:max-w-md',
      'lg' => 'sm:max-w-lg',
      'xl' => 'sm:max-w-xl',
      '2xl' => 'sm:max-w-2xl',
  ][$maxWidth ?? 'md'];
@endphp

<div id="{{ $id }}">
  <template x-teleport="body">
    <div x-show="filterOpen"
      class="fixed inset-0 z-[250]" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" style="display: none;">
      
      <!-- Backdrop -->
      <div x-show="filterOpen" class="fixed inset-0 z-[251] bg-gray-900/60 dark:bg-gray-950/80 backdrop-blur-xs sm:backdrop-blur-sm transition-opacity" x-on:click="filterOpen = false"
        x-transition:enter="ease-out duration-150" 
        x-transition:enter-start="opacity-0" 
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" 
        x-transition:leave-start="opacity-100" 
        x-transition:leave-end="opacity-0">
      </div>

      <!-- Sidebar Container -->
      <div class="fixed inset-0 z-[252] overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            
            <!-- Sidebar Panel -->
            <div x-show="filterOpen"
              class="pointer-events-auto {{ $maxWidth }} w-screen h-full overflow-y-auto bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border-l border-white/60 dark:border-gray-800 shadow-2xl relative flex flex-col will-change-transform"
              x-trap.inert.noscroll="filterOpen" 
              x-transition:enter="transform transition ease-out duration-200"
              x-transition:enter-start="translate-x-full"
              x-transition:enter-end="translate-x-0" 
              x-transition:leave="transform transition ease-in duration-150"
              x-transition:leave-start="translate-x-0"
              x-transition:leave-end="translate-x-full">
        
        <div class="flex items-center justify-between border-b px-6 py-4 dark:border-gray-700">
          <div class="flex items-center gap-2 text-lg font-medium text-gray-900 dark:text-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-sky-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
            </svg>
            {{ $title }}
          </div>
          <div class="flex items-center gap-2">
            @if (isset($actions))
                {{ $actions }}
            @endif
            <button type="button" x-on:click="filterOpen = false" class="rounded-md border p-1 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:border-gray-600 dark:hover:bg-gray-700">
              <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="px-6 py-4 pb-20 text-sm text-gray-600 dark:text-gray-400">
          {{ $content }}
        </div>

        @if (isset($footer))
          <div class="absolute bottom-0 w-full border-t bg-gray-100 px-6 py-4 text-end dark:border-gray-700 dark:bg-gray-800">
            {{ $footer }}
          </div>
        @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
</div>
