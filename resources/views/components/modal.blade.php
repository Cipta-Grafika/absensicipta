@props(['id', 'maxWidth', 'onclose' => null])

@php
  $id = $id ?? md5($attributes->wire('model'));

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
          setTimeout(() => {
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
          }, 50);
      }
  })">
  <template x-teleport="body">
    <div x-show="show" class="jetstream-modal fixed inset-0 z-[250] flex min-h-full items-center justify-center overflow-y-auto p-3 sm:p-4 my-auto" style="display: none;">
      <!-- Backdrop -->
      <div x-show="show"
        class="fixed inset-0 z-[251] bg-gray-900/60 dark:bg-gray-950/80 backdrop-blur-md transition-opacity"
        x-on:click="show = false; {{ $onclose }}"
        x-transition:enter="ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
      </div>

      <!-- Modal Dialog Card -->
      <div x-show="show"
        class="w-full {{ $maxWidth }} transform overflow-hidden rounded-2xl bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border border-white/60 dark:border-gray-700/60 shadow-2xl transition-all sm:mx-auto max-h-[82vh] sm:max-h-[88vh] flex flex-col my-auto z-[255]"
        x-trap.inert="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        {{ $slot }}
      </div>
    </div>
  </template>
</div>
