@props(['id' => 'alert-modal'])

<div x-data="{
        show: false,
        type: 'danger', // 'danger' | 'error' | 'warning' | 'success' | 'info' | 'super_early' | 'early' | 'on_time' | 'late_mild' | 'late_severe' | 'out'
        title: '',
        message: '',
        icon: '',
        badgeColor: '',
        buttonText: 'Mengerti',
        init() {
            @if(session()->has('flash.banner'))
                this.showAlertModal({
                    type: '{{ session('flash.bannerStyle', 'danger') }}',
                    message: {!! json_encode(session('flash.banner')) !!}
                });
            @elseif(session()->has('status'))
                this.showAlertModal({
                    type: 'info',
                    message: {!! json_encode(session('status')) !!}
                });
            @elseif(session()->has('success'))
                this.showAlertModal({
                    type: 'success',
                    message: {!! json_encode(session('success')) !!}
                });
            @elseif(session()->has('error'))
                this.showAlertModal({
                    type: 'danger',
                    message: {!! json_encode(session('error')) !!}
                });
            @elseif(session()->has('warning'))
                this.showAlertModal({
                    type: 'warning',
                    message: {!! json_encode(session('warning')) !!}
                });
            @endif
        },
        showAlertModal(detail) {
            let data = Array.isArray(detail) ? detail[0] : detail;
            if (typeof data === 'string') {
                this.message = data;
                this.type = 'danger';
                this.title = 'Pemberitahuan';
                this.icon = 'exclamation-triangle';
                this.badgeColor = 'red';
                this.buttonText = 'Mengerti';
            } else if (data && typeof data === 'object') {
                this.type = data.type || data.style || 'danger';
                this.icon = data.icon || '';
                this.badgeColor = data.badge_color || data.badgeColor || '';

                if (data.title && typeof data.title === 'string' && data.title.trim() !== '') {
                    this.title = data.title;
                } else {
                    switch (this.type) {
                        case 'success':
                            this.title = 'Berhasil';
                            break;
                        case 'on_time':
                            this.title = 'Tepat Waktu!';
                            break;
                        case 'early':
                            this.title = 'Hebat!';
                            break;
                        case 'super_early':
                            this.title = 'Luar Biasa!';
                            break;
                        case 'late_mild':
                            this.title = 'Tingkatkan Kedisiplinan!';
                            break;
                        case 'late_severe':
                            this.title = 'Terlambat!';
                            break;
                        case 'out':
                            this.title = 'Terima Kasih!';
                            break;
                        case 'warning':
                            this.title = 'Peringatan';
                            break;
                        case 'danger':
                        case 'error':
                            this.title = 'Gagal';
                            break;
                        case 'info':
                            this.title = 'Informasi';
                            break;
                        default:
                            this.title = 'Pemberitahuan';
                            break;
                    }
                }

                this.message = data.message || data.text || data.banner || '';

                if (data.buttonText) {
                    this.buttonText = data.buttonText;
                } else if (['success', 'on_time', 'early', 'super_early', 'late_mild', 'late_severe', 'out'].includes(this.type)) {
                    this.buttonText = 'Siap, Lanjutkan!';
                } else {
                    this.buttonText = 'Mengerti';
                }
            }
            if (this.message && typeof this.message === 'string') {
                // Strip redundant leading prefixes
                this.message = this.message.replace(/^(Absen Gagal|Absen Masuk gagal|Absen Keluar gagal|Presensi Gagal|Gagal)\s*:\s*/i, '');
                this.show = true;
            }
        },
        closeAlertModal() {
            this.show = false;
        }
    }"
    x-on:alert-modal.window="showAlertModal($event.detail)"
    x-on:banner-message.window="
        if ($event.detail) {
            showAlertModal($event.detail);
        }
    "
    x-cloak>
    
    <template x-teleport="body">
        <div x-show="show" 
             class="fixed inset-0 z-[999999] flex items-center justify-center p-4 overflow-y-auto overflow-x-hidden" 
             style="display: none;">
            
            <!-- BACKDROP GLASSMORPHISM (z-[999998]) -->
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[999998] bg-gray-900/60 dark:bg-gray-950/75 backdrop-blur-xs transform-gpu"
                 @click="closeAlertModal()"></div>

            <!-- MODAL CARD CONTAINER (z-[999999] TOPMOST PRIORITY) -->
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative z-[999999] w-full max-w-sm sm:max-w-md rounded-2xl bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border border-white/60 dark:border-gray-700/60 shadow-2xl p-6 text-center transform-gpu transition-all max-h-[90vh] overflow-y-auto my-auto select-none">

                <!-- TOP COLORED ACCENT BAR -->
                <div class="absolute top-0 left-0 right-0 h-1.5 rounded-t-2xl z-10"
                     :class="{
                        'bg-rose-500 dark:bg-rose-600': ['danger', 'error', 'late_severe'].includes(type) || badgeColor === 'red',
                        'bg-amber-500 dark:bg-amber-600': ['warning', 'late_mild'].includes(type) || badgeColor === 'amber',
                        'bg-emerald-500 dark:bg-emerald-600': ['success', 'on_time', 'early'].includes(type) || badgeColor === 'green',
                        'bg-purple-500 dark:bg-purple-600': ['out', 'super_early'].includes(type) || badgeColor === 'purple',
                        'bg-blue-500 dark:bg-blue-600': type === 'info' || badgeColor === 'blue'
                     }"></div>

                <!-- TOP-RIGHT CLOSE BUTTON (X) -->
                <button type="button" @click="closeAlertModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 z-20">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>

                <!-- CENTERED COLORED ICON BADGE -->
                <div class="mx-auto mt-2 mb-4 flex items-center justify-center shrink-0 relative z-10 h-16 w-16 rounded-2xl shadow-xs"
                     :class="{
                        'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60': ['danger', 'error', 'late_severe'].includes(type) || badgeColor === 'red',
                        'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60': ['warning', 'late_mild'].includes(type) || badgeColor === 'amber',
                        'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60': ['success', 'on_time', 'early'].includes(type) || badgeColor === 'green',
                        'bg-purple-100 text-purple-600 dark:bg-purple-950/70 dark:text-purple-400 border border-purple-200 dark:border-purple-800/60': ['out', 'super_early'].includes(type) || badgeColor === 'purple',
                        'bg-blue-100 text-blue-600 dark:bg-blue-950/70 dark:text-blue-400 border border-blue-200 dark:border-blue-800/60': type === 'info' || badgeColor === 'blue'
                     }">
                    
                    <!-- FIRE ICON FOR SUPER_EARLY / FIRE -->
                    <template x-if="icon === 'fire' || type === 'super_early'">
                        <x-heroicon-s-fire class="h-10 w-10 text-purple-600 dark:text-purple-400 animate-bounce" />
                    </template>

                    <!-- SPARKLES ICON FOR EARLY / SPARKLES -->
                    <template x-if="icon === 'sparkles' || type === 'early'">
                        <x-heroicon-s-sparkles class="h-10 w-10 text-emerald-600 dark:text-emerald-400 animate-bounce" />
                    </template>

                    <!-- HEART ICON FOR PULANG / APRESIASI KERJA KERAS (OUT) -->
                    <template x-if="icon === 'heart' || type === 'out'">
                        <x-heroicon-s-heart class="h-10 w-10 text-purple-600 dark:text-purple-400 animate-pulse" />
                    </template>

                    <!-- CLOCK / EXCLAMATION CIRCLE FOR LATE_MILD (HAMPIR TERLAMBAT / PERINGATAN KEDISIPLINAN) -->
                    <template x-if="icon === 'clock' || type === 'late_mild'">
                        <x-heroicon-s-clock class="h-10 w-10 text-amber-600 dark:text-amber-400 animate-pulse" />
                    </template>

                    <!-- EXCLAMATION TRIANGLE FOR LATE_SEVERE / DANGER / ERROR (TERLAMBAT / KECEWA) -->
                    <template x-if="icon === 'exclamation-triangle' || ['late_severe', 'danger', 'error'].includes(type)">
                        <x-heroicon-s-exclamation-triangle class="h-10 w-10 text-rose-600 dark:text-rose-400 animate-bounce" />
                    </template>

                    <!-- CHECK CIRCLE FOR ON_TIME / SUCCESS (APRESIASI TEPAT WAKTU) -->
                    <template x-if="icon === 'check-circle' || ['on_time', 'success'].includes(type)">
                        <x-heroicon-s-check-circle class="h-10 w-10 text-emerald-600 dark:text-emerald-400" />
                    </template>

                    <!-- EXCLAMATION CIRCLE FOR WARNING DEFAULT -->
                    <template x-if="type === 'warning' && !['clock', 'exclamation-triangle'].includes(icon)">
                        <x-heroicon-s-exclamation-circle class="h-10 w-10 text-amber-600 dark:text-amber-400" />
                    </template>

                    <!-- INFORMATION CIRCLE FOR INFO DEFAULT -->
                    <template x-if="type === 'info' && !['fire', 'sparkles', 'heart', 'clock', 'exclamation-triangle', 'check-circle'].includes(icon)">
                        <x-heroicon-s-information-circle class="h-10 w-10 text-blue-600 dark:text-blue-400" />
                    </template>
                </div>

                <!-- TITLE -->
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-2 leading-tight tracking-tight relative z-10" x-text="title"></h3>

                <!-- MESSAGE CONTENT (STRAIGHTFORWARD DETAIL TEKS PESAN ERROR/INFORMASI/UCAPAN) -->
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-6 font-medium px-1 sm:px-3 relative z-10" x-text="message"></p>

                <!-- BOTTOM ACTION BUTTON -->
                <div class="pt-1 relative z-10">
                    <button type="button" @click="closeAlertModal()"
                            class="w-full rounded-xl px-5 py-3 text-sm font-bold text-white shadow-lg transition-all transform active:scale-95 focus:outline-none focus:ring-4"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 focus:ring-emerald-300 dark:focus:ring-emerald-800': ['success', 'on_time', 'early'].includes(type) || badgeColor === 'green',
                                'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:ring-blue-300 dark:focus:ring-blue-800': ['late_severe', 'late_mild', 'out', 'super_early', 'info'].includes(type) || ['blue', 'purple'].includes(badgeColor),
                                'bg-rose-600 hover:bg-rose-700 dark:bg-rose-500 dark:hover:bg-rose-600 focus:ring-rose-300 dark:focus:ring-rose-800': ['danger', 'error'].includes(type) && !['late_severe', 'late_mild', 'out', 'super_early'].includes(type),
                                'bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 focus:ring-amber-300 dark:focus:ring-amber-800': type === 'warning' && !['late_severe', 'late_mild'].includes(type)
                            }">
                        <span x-text="buttonText"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
