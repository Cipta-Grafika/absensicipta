@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
  <div class="flex flex-col min-h-0 flex-1 overflow-hidden">
    <div class="px-6 pt-5 pb-3 shrink-0 border-b border-gray-200 dark:border-gray-700">
      <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
        {{ $title }}
      </div>
    </div>

    <div class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 overflow-y-auto min-h-0 flex-1 space-y-4">
      {{ $content }}
    </div>

    <div class="flex flex-row justify-end bg-gray-50 px-6 py-3.5 text-end dark:bg-gray-800/80 shrink-0 border-t border-gray-200 dark:border-gray-700">
      {{ $footer }}
    </div>
  </div>
</x-modal>
