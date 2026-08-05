<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanFeedback extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'scan_feedbacks';

    protected $fillable = [
        'category',
        'title',
        'message',
        'icon',
        'badge_color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Pick a random active feedback for a category and format message with user name.
     */
    public static function getRandomFeedback(string $category, string $userName): array
    {
        $feedbacks = static::where('category', $category)->where('is_active', true)->get();

        if ($feedbacks->isEmpty()) {
            // Fallback default message if no feedback in DB
            $fallback = static::getFallbackFeedback($category, $userName);
            return $fallback;
        }

        /** @var self $selected */
        $selected = $feedbacks->random();
        $message = str_replace(['{name}', '{nama}'], $userName, $selected->message);

        return [
            'type' => $selected->category,
            'title' => $selected->title,
            'message' => $message,
            'icon' => $selected->icon,
            'badge_color' => $selected->badge_color,
        ];
    }

    private static function getFallbackFeedback(string $category, string $userName): array
    {
        return match ($category) {
            'super_early' => [
                'type' => 'super_early',
                'title' => 'Luar Biasa!',
                'message' => "Gokill {$userName}! Kamu datang sangat awal hari ini. Pertahankan!",
                'icon' => 'fire',
                'badge_color' => 'green',
            ],
            'early' => [
                'type' => 'early',
                'title' => 'Hebat!',
                'message' => "Hebat {$userName}! Datang lebih awal hari ini.",
                'icon' => 'sparkles',
                'badge_color' => 'green',
            ],
            'on_time' => [
                'type' => 'on_time',
                'title' => 'Tepat Waktu!',
                'message' => "Tepat waktu {$userName}! Semangat kerjanya hari ini.",
                'icon' => 'check-circle',
                'badge_color' => 'blue',
            ],
            'late_mild' => [
                'type' => 'late_mild',
                'title' => 'Perhatian!',
                'message' => "Hampir terlambat {$userName}! Tetap semangat dan usahakan datang lebih awal besok.",
                'icon' => 'clock',
                'badge_color' => 'amber',
            ],
            'late_severe' => [
                'type' => 'late_severe',
                'title' => 'Terlambat!',
                'message' => "Waduh {$userName}! Kamu terlambat hari ini. Mari tingkatkan kedisiplinan esok hari.",
                'icon' => 'exclamation-triangle',
                'badge_color' => 'red',
            ],
            'out' => [
                'type' => 'out',
                'title' => 'Terima Kasih!',
                'message' => "Terima kasih atas kerja kerasmu hari ini, {$userName}! Selamat beristirahat.",
                'icon' => 'heart',
                'badge_color' => 'purple',
            ],
            default => [
                'type' => 'info',
                'title' => 'Absensi Berhasil',
                'message' => "Semangat bertugas, {$userName}!",
                'icon' => 'check',
                'badge_color' => 'blue',
            ],
        };
    }
}
