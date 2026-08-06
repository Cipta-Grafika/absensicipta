<div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-8"
     x-data="{
       eventSource: null,
       init() {
         if (!window.EventSource) return;
         const period = '{{ $period }}';
         this.eventSource = new EventSource('/api/leaderboard/stream?period=' + period);
         this.eventSource.addEventListener('leaderboard_updated', (e) => {
           @this.call('$refresh');
         });
       },
       destroy() {
         if (this.eventSource) {
           this.eventSource.close();
         }
       }
     }">

  <!-- SECTION 1: LEADERBOARD SELURUH KARYAWAN (GLOBAL TOP 5) -->
  <div class="flex flex-col justify-between rounded-2xl border border-gray-100 bg-gray-50/50 p-5 dark:border-gray-700/60 dark:bg-gray-800/40">
    <div>
      <div class="mb-5 flex flex-wrap items-center justify-between gap-2 border-b border-gray-200/80 pb-4 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
              Leaderboard {{ $periodName }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              5 Top Karyawan Paling Rajin & Tepat Waktu — Seluruh Karyawan
            </p>
          </div>
        </div>
        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
          • Real-time Updated
        </span>
      </div>

      @if ($globalTopEmployees->isNotEmpty())
        <div class="flex flex-col gap-3">
          @foreach ($globalTopEmployees as $index => $st)
            @php
              $rank = $index + 1;
              $cardBg = match ($rank) {
                  1 => 'bg-gradient-to-r from-amber-100/70 via-amber-50/40 to-white dark:from-amber-950/40 dark:via-amber-900/20 dark:to-gray-800 border-amber-200/80 dark:border-amber-700/50',
                  2 => 'bg-gradient-to-r from-slate-100/70 via-slate-50/40 to-white dark:from-slate-800/40 dark:via-slate-800/20 dark:to-gray-800 border-slate-200/80 dark:border-slate-700/50',
                  3 => 'bg-gradient-to-r from-orange-100/70 via-amber-50/30 to-white dark:from-amber-950/30 dark:via-amber-900/10 dark:to-gray-800 border-amber-300/60 dark:border-amber-800/40',
                  default => 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700/60 hover:bg-gray-50/60 dark:hover:bg-gray-750',
              };
            @endphp

            <div class="flex items-center justify-between rounded-2xl border px-4 py-3.5 transition-all duration-200 hover:shadow-xs {{ $cardBg }}">
              <!-- Left Section: Medal + Avatar + Name & Custom Comment -->
              <div class="flex items-center gap-3.5">
                <!-- Rank Medal / Number Icon -->
                <div class="flex h-10 w-10 shrink-0 items-center justify-center">
                  @if ($rank === 1)
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-amber-300 via-amber-400 to-yellow-500 p-1.5 text-amber-950 shadow-md shadow-amber-400/30 ring-2 ring-amber-200 dark:ring-amber-500/40">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  @elseif ($rank === 2)
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-slate-200 via-slate-300 to-slate-400 p-1.5 text-slate-800 shadow-md shadow-slate-300/30 ring-2 ring-slate-200 dark:ring-slate-600">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  @elseif ($rank === 3)
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-amber-700 via-amber-800 to-amber-900 p-1.5 text-amber-100 shadow-md shadow-amber-900/30 ring-2 ring-amber-700/50">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-gray-50 text-sm font-semibold text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                      {{ $rank }}
                    </div>
                  @endif
                </div>

                <!-- Profile Avatar -->
                <div class="h-11 w-11 shrink-0 overflow-hidden rounded-2xl bg-indigo-100 ring-1 ring-gray-200 dark:bg-indigo-950 dark:ring-gray-700">
                  @if ($st->user?->profile_photo_url)
                    <img src="{{ $st->user->profile_photo_url }}" alt="{{ $st->user->name }}" class="h-full w-full object-cover">
                  @else
                    <div class="flex h-full w-full items-center justify-center text-sm font-bold text-indigo-600 dark:text-indigo-300">
                      {{ strtoupper(substr($st->user?->name ?? 'U', 0, 2)) }}
                    </div>
                  @endif
                </div>

                <!-- Name & Custom Rank Comment -->
                <div class="flex flex-col">
                  <span class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $st->user?->name ?? 'Karyawan' }}
                  </span>
                  <span class="text-xs font-medium text-amber-600 dark:text-amber-400">
                    {{ $globalRankComments[$index] ?? 'Terbaik' }}
                  </span>
                </div>
              </div>

              <!-- Right Section: Points Badge -->
              <div class="shrink-0">
                <div class="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50/90 px-3.5 py-1 text-xs font-bold text-emerald-800 shadow-2xs dark:border-emerald-700/80 dark:bg-emerald-950/80 dark:text-emerald-200">
                  <span class="mr-1.5 text-emerald-600 dark:text-emerald-400">•</span> {{ number_format($st->score, 0) }} Pts
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="py-6 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
          Belum ada statistik leaderboard seluruh karyawan untuk bulan {{ $periodName }}.
        </div>
      @endif
    </div>
  </div>

  <!-- SECTION 2: LEADERBOARD PER DIVISI (DIVISION TOP 5) -->
  <div class="flex flex-col justify-between rounded-2xl border border-gray-100 bg-gray-50/50 p-5 dark:border-gray-700/60 dark:bg-gray-800/40">
    <div>
      <div class="mb-5 flex flex-wrap items-center justify-between gap-2 border-b border-gray-200/80 pb-4 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
              Leaderboard {{ $divisionName ? 'Divisi ' . $divisionName : 'Per Divisi' }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              5 Top Karyawan Paling Rajin & Tepat Waktu — {{ $divisionName ? 'Divisi ' . $divisionName : 'Per Divisi' }}
            </p>
          </div>
        </div>
        <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-900/50 dark:text-sky-300">
          • Divisi Scoped
        </span>
      </div>

      @if ($divisionTopEmployees->isNotEmpty())
        <div class="flex flex-col gap-3">
          @foreach ($divisionTopEmployees as $index => $st)
            @php
              $rank = $index + 1;
              $cardBg = match ($rank) {
                  1 => 'bg-gradient-to-r from-amber-100/70 via-amber-50/40 to-white dark:from-amber-950/40 dark:via-amber-900/20 dark:to-gray-800 border-amber-200/80 dark:border-amber-700/50',
                  2 => 'bg-gradient-to-r from-slate-100/70 via-slate-50/40 to-white dark:from-slate-800/40 dark:via-slate-800/20 dark:to-gray-800 border-slate-200/80 dark:border-slate-700/50',
                  3 => 'bg-gradient-to-r from-orange-100/70 via-amber-50/30 to-white dark:from-amber-950/30 dark:via-amber-900/10 dark:to-gray-800 border-amber-300/60 dark:border-amber-800/40',
                  default => 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700/60 hover:bg-gray-50/60 dark:hover:bg-gray-750',
              };
            @endphp

            <div class="flex items-center justify-between rounded-2xl border px-4 py-3.5 transition-all duration-200 hover:shadow-xs {{ $cardBg }}">
              <!-- Left Section: Medal + Avatar + Name & Division Name -->
              <div class="flex items-center gap-3.5">
                <!-- Rank Medal / Number Icon -->
                <div class="flex h-10 w-10 shrink-0 items-center justify-center">
                  @if ($rank === 1)
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-amber-300 via-amber-400 to-yellow-500 p-1.5 text-amber-950 shadow-md shadow-amber-400/30 ring-2 ring-amber-200 dark:ring-amber-500/40">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  @elseif ($rank === 2)
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-slate-200 via-slate-300 to-slate-400 p-1.5 text-slate-800 shadow-md shadow-slate-300/30 ring-2 ring-slate-200 dark:ring-slate-600">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  @elseif ($rank === 3)
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-amber-700 via-amber-800 to-amber-900 p-1.5 text-amber-100 shadow-md shadow-amber-900/30 ring-2 ring-amber-700/50">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-gray-50 text-sm font-semibold text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                      {{ $rank }}
                    </div>
                  @endif
                </div>

                <!-- Profile Avatar -->
                <div class="h-11 w-11 shrink-0 overflow-hidden rounded-2xl bg-indigo-100 ring-1 ring-gray-200 dark:bg-indigo-950 dark:ring-gray-700">
                  @if ($st->user?->profile_photo_url)
                    <img src="{{ $st->user->profile_photo_url }}" alt="{{ $st->user->name }}" class="h-full w-full object-cover">
                  @else
                    <div class="flex h-full w-full items-center justify-center text-sm font-bold text-indigo-600 dark:text-indigo-300">
                      {{ strtoupper(substr($st->user?->name ?? 'U', 0, 2)) }}
                    </div>
                  @endif
                </div>

                <!-- Name & Division Name -->
                <div class="flex flex-col">
                  <span class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $st->user?->name ?? 'Karyawan' }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $st->division?->name ?? 'Tanpa Divisi' }}
                  </span>
                </div>
              </div>

              <!-- Right Section: Points Badge -->
              <div class="shrink-0">
                <div class="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50/90 px-3.5 py-1 text-xs font-bold text-emerald-800 shadow-2xs dark:border-emerald-700/80 dark:bg-emerald-950/80 dark:text-emerald-200">
                  <span class="mr-1.5 text-emerald-600 dark:text-emerald-400">•</span> {{ number_format($st->score, 0) }} Pts
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="py-6 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
          Belum ada statistik leaderboard per divisi untuk bulan {{ $periodName }}.
        </div>
      @endif
    </div>
  </div>

</div>
