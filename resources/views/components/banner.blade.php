@props(['style' => session('flash.bannerStyle', 'success'), 'message' => session('flash.banner')])

<div x-data="{
        show: true,
        style: {{ json_encode($style) }},
        message: {{ json_encode($message) }},
        timer: null,
        init() {
            if (this.message) {
                this.startTimer();
            }
        },
        startTimer() {
            if (this.timer) clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.show = false;
            }, 5000);
        }
    }"
    :class="{ 
        'bg-emerald-500/85 dark:bg-emerald-600/85 border-emerald-400/80 dark:border-emerald-500/80': style == 'success', 
        'bg-rose-500/85 dark:bg-rose-600/85 border-rose-400/80 dark:border-rose-500/80': style == 'danger', 
        'bg-amber-500/85 dark:bg-amber-600/85 border-amber-400/80 dark:border-amber-500/80': style == 'warning', 
        'bg-sky-500/85 dark:bg-sky-600/85 border-sky-400/80 dark:border-sky-500/80': style != 'success' && style != 'danger' && style != 'warning'
    }"
    class="fixed top-16 left-0 right-0 z-50 backdrop-blur-xl border-b shadow-lg transition-all select-none"
    style="display: none;"
    x-show="show && message"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    x-on:banner-message.window="
        style = event.detail.style;
        message = event.detail.message;
        show = true;
        startTimer();
    ">
    <div class="max-w-screen-xl mx-auto py-2.5 px-3 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between flex-wrap">
            <div class="w-0 flex-1 flex items-center min-w-0">
                <span class="flex p-1.5 rounded-lg bg-white/20 dark:bg-black/20 backdrop-blur-md">
                    <svg x-show="style == 'success'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="style == 'danger'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <svg x-show="style != 'success' && style != 'danger' && style != 'warning'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <svg x-show="style == 'warning'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4v.01 0 0 " />
                    </svg>
                </span>

                <p class="ms-3 font-medium text-sm text-white truncate drop-shadow-xs" x-text="message"></p>
            </div>

            <div class="shrink-0 sm:ms-3">
                <button
                    type="button"
                    class="-me-1 flex p-2 rounded-md focus:outline-none sm:-me-2 transition hover:bg-white/20 dark:hover:bg-black/20"
                    aria-label="Dismiss"
                    x-on:click="show = false">
                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
