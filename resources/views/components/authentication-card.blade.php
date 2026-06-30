<style>
  .auth-bg-gradient {
    /* Blue sky / pastel accent for Light Theme */
    background: linear-gradient(135deg, #e0f2fe 0%, #eff6ff 50%, #f8fafc 100%);
  }
  .dark .auth-bg-gradient {
    /* Deep blue / sky accent for Dark Theme */
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0c4a6e 100%);
  }
</style>
<div class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 auth-bg-gradient transition-colors duration-500">
  <div>
    {{ $logo }}
  </div>

  <div class="mt-6 w-full overflow-hidden bg-transparent px-6 py-4 shadow-none sm:bg-white sm:shadow-md sm:dark:bg-gray-800 sm:max-w-md sm:rounded-lg">
    {{ $slot }}
  </div>
</div>
