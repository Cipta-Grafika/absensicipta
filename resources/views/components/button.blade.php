@php
  $class =
      'inline-flex items-center px-4 py-2 bg-sky-500 dark:bg-sky-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-white hover:bg-sky-600 dark:hover:bg-sky-400 focus:bg-sky-600 dark:focus:bg-sky-400 active:bg-sky-700 dark:active:bg-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 transition ease-in-out duration-150';
@endphp

@if (!isset($attributes['href']))
  <button {{ $attributes->merge(['type' => 'submit', 'class' => $class]) }}>
    {{ $slot }}
  </button>
@else
  <a {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
  </a>
@endif
