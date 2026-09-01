<div x-data="{
    canInstall: false,
    dismissed: localStorage.getItem('pwa_prompt_dismissed') === 'true',
    init() {
      window.addEventListener('pwa-installable', (e) => {
        if (!this.dismissed) {
          this.canInstall = true;
        }
      });
      window.addEventListener('pwa-installed', () => {
        this.canInstall = false;
      });
    },
    install() {
      if (window.installPwaApp) {
        window.installPwaApp();
      }
    },
    dismiss() {
      this.canInstall = false;
      this.dismissed = true;
      localStorage.setItem('pwa_prompt_dismissed', 'true');
    }
}" 
x-show="canInstall"
x-transition:enter="transition ease-out duration-300 transform"
x-transition:enter-start="opacity-0 translate-y-4"
x-transition:enter-end="opacity-100 translate-y-0"
x-transition:leave="transition ease-in duration-200 transform"
x-transition:leave-start="opacity-100 translate-y-0"
x-transition:leave-end="opacity-0 translate-y-4"
style="display: none;"
class="fixed bottom-20 sm:bottom-6 left-4 right-4 sm:left-auto sm:right-6 sm:max-w-md z-50 p-4 rounded-2xl bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border border-sky-200 dark:border-sky-800 shadow-2xl shadow-sky-900/20">
  <div class="flex items-center gap-3.5">
    <div class="h-12 w-12 rounded-xl bg-white dark:bg-gray-800 p-1.5 ring-1 ring-black/5 dark:ring-white/10 shrink-0 shadow-xs flex items-center justify-center">
      <img src="{{ asset('icons/icon-96x96.png') }}" alt="Absensi Cipta" class="h-full w-full object-contain">
    </div>
    <div class="flex-1 min-w-0">
      <div class="text-sm font-bold text-gray-900 dark:text-white truncate">Install Aplikasi Absensi</div>
      <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Akses lebih cepat & praktis langsung dari layar utama HP Anda.</div>
    </div>
  </div>
  <div class="mt-3.5 flex items-center justify-end gap-2">
    <button @click="dismiss" type="button" class="px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
      Nanti Saja
    </button>
    <button @click="install" type="button" class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 active:scale-95 rounded-lg shadow-sm transition cursor-pointer">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
      <span>Install</span>
    </button>
  </div>
</div>
