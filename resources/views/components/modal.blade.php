@props(['id', 'maxWidth', 'onclose' => null, 'zIndex' => '250'])

@php
  $id = $id ?? md5($attributes->wire('model'));
  $baseZIndex = (int) ($zIndex ?? 250);
  $backdropZIndex = $baseZIndex + 1;
  $cardZIndex = $baseZIndex + 5;

  $maxWidth = [
      'sm' => 'sm:max-w-sm',
      'md' => 'sm:max-w-md',
      'lg' => 'sm:max-w-lg',
      'xl' => 'sm:max-w-xl',
      '2xl' => 'sm:max-w-2xl',
      '3xl' => 'sm:max-w-3xl',
      '4xl' => 'sm:max-w-4xl',
  ][$maxWidth ?? '2xl'];
@endphp

<div x-data="{ show: @entangle($attributes->wire('model')).live }" x-on:close.stop="show = false; {{ $onclose }}"
  x-on:keydown.escape.window="show = false; {{ $onclose }}" id="{{ $id }}"
  x-init="$watch('show', value => {
      if (value) {
          document.body.classList.add('overflow-y-hidden');
      } else {
          let openModals = Array.from(document.querySelectorAll('.jetstream-modal')).some(el => {
              try {
                  return Alpine.$data(el).show === true;
              } catch (e) {
                  return false;
              }
          });
          if (!openModals) {
              document.body.classList.remove('overflow-y-hidden');
          }
      }
  })">
  <template x-teleport="body">
    <div x-show="show" class="jetstream-modal fixed inset-0 flex min-h-full items-center justify-center overflow-y-auto p-3 sm:p-4 my-auto" style="display: none; z-index: {{ $baseZIndex }};">
      <!-- Backdrop (Lightweight, hardware-accelerated blur) -->
      <div x-show="show"
        class="fixed inset-0 bg-gray-900/60 dark:bg-gray-950/80 backdrop-blur-xs sm:backdrop-blur-sm transition-opacity"
        style="z-index: {{ $backdropZIndex }};"
        x-on:click="show = false; {{ $onclose }}"
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
      </div>

      <!-- Modal Dialog Card (Snappy 150ms Hardware-Accelerated transition) -->
      <div x-show="show"
        class="w-full {{ $maxWidth }} transform overflow-hidden rounded-2xl bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border border-white/60 dark:border-gray-700/60 shadow-2xl transition-all sm:mx-auto max-h-[82vh] sm:max-h-[88vh] flex flex-col my-auto will-change-transform"
        style="z-index: {{ $cardZIndex }};"
        x-trap.inert="show"
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-[0.98] translate-y-2 sm:translate-y-0"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-[0.98] translate-y-2 sm:translate-y-0">
        {{ $slot }}
      </div>
    </div>
  </template>
</div>
