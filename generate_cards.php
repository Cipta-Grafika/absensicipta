<?php
function renderTrend($statName, $upColor, $downColor) {
    return <<<HTML
        @if (\$stats['{$statName}']['is_up'])
          <span class="flex items-center text-xs font-medium text-{$upColor}-600 dark:text-{$upColor}-400">
            <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            {{ \$stats['{$statName}']['trend'] }}
          </span>
        @elseif (\$stats['{$statName}']['is_down'])
          <span class="flex items-center text-xs font-medium text-{$downColor}-600 dark:text-{$downColor}-400">
            <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>
            {{ \$stats['{$statName}']['trend'] }}
          </span>
        @else
          <span class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
            <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>
            0%
          </span>
        @endif
HTML;
}

function renderCard($title, $countVar, $iconSvg, $iconColor, $statName, $upColor, $downColor, $footer) {
    $trend = renderTrend($statName, $upColor, $downColor);
    return <<<HTML
    <div class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/80">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">
          {$title} <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="text-{$iconColor}-500 dark:text-{$iconColor}-400">
          {$iconSvg}
        </div>
      </div>
      <div class="mt-2 flex items-baseline gap-2">
        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ \${$countVar} }}</p>
{$trend}
      </div>
      <div class="mt-3">
        <!-- Pseudo sparkline (decorative) -->
        <svg class="h-6 w-full text-{$iconColor}-100 dark:text-{$iconColor}-900/30" preserveAspectRatio="none" viewBox="0 0 100 20" fill="currentColor">
          <path d="M0 20 L0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10 L100 20 Z" opacity="0.5"></path>
          <path d="M0 15 L10 12 L20 18 L30 10 L40 14 L50 5 L60 12 L70 8 L80 16 L90 6 L100 10" fill="none" stroke="currentColor" stroke-width="1.5" class="text-{$iconColor}-400 dark:text-{$iconColor}-500"></path>
        </svg>
      </div>
    </div>
HTML;
}

$hadirSvg = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
$wfhSvg = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>';
$izinSvg = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';

$sakitSvg = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
$cutiSvg = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
$absenSvg = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

$html = '<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">' . "\n";

// Container 1
$html .= <<<HTML
  <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50/50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50 rounded-t-xl">
      <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
      <h3 class="font-medium text-gray-700 dark:text-gray-300">Kehadiran</h3>
    </div>
    <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
HTML;
$html .= "\n" . renderCard('Hadir', 'presentCount', $hadirSvg, 'green', 'present', 'green', 'red', 'Trmsk telat: {{ $lateCount }}');
$html .= "\n" . renderCard('WFH', 'wfhCount', $wfhSvg, 'purple', 'wfh', 'green', 'red', 'Work From Home');
$html .= "\n" . renderCard('Izin', 'excusedCount', $izinSvg, 'blue', 'excused', 'red', 'green', 'Izin Resmi');
$html .= "\n    </div>\n  </div>\n";

// Container 2
$html .= <<<HTML
  <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50/50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50 rounded-t-xl">
      <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <h3 class="font-medium text-gray-700 dark:text-gray-300">Ketidakhadiran</h3>
    </div>
    <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
HTML;
$html .= "\n" . renderCard('Sakit', 'sickCount', $sakitSvg, 'yellow', 'sick', 'red', 'green', 'Masa Penyembuhan');
$html .= "\n" . renderCard('Cuti', 'leaveCount', $cutiSvg, 'teal', 'leave', 'red', 'green', 'Sedang Cuti');
$html .= "\n" . renderCard('Absen', 'absentCount', $absenSvg, 'red', 'absent', 'red', 'green', 'Tanpa Keterangan');
$html .= "\n    </div>\n  </div>\n";

$html .= "</div>\n";

file_put_contents('new_cards.html', $html);
?>
