<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasUlids;
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public static array $types = [
        'full-time',
        'contract',
        'part-time',
        'freelance',
        'probation',
        'intern',
        'pkl',
        'outsourcing',
        'volunteer',
    ];

    protected $fillable = [
        'nip',
        'name',
        'email',
        'password',
        'raw_password',
        'group',
        'type',
        'phone',
        'gender',
        'birth_date',
        'birth_place',
        'address',
        'city',
        'education_id',
        'division_id',
        'job_title_id',
        'profile_photo_path',
        'status',
        'count_wfo',
        'off_days',
    ];

    public static function generateNip(string $type = 'full-time', $date = null): string
    {
        $dt = $date ? \Carbon\Carbon::parse($date) : \Carbon\Carbon::now();
        $yy = $dt->format('y');
        $mm = $dt->format('m');

        $prefix = match ($type) {
            'part-time' => 'PT',
            'freelance' => 'FR',
            'probation' => 'PRB',
            'intern' => 'INT',
            'pkl' => 'PKL',
            default => '',
        };

        $padLength = in_array($prefix, ['PT', 'FR', 'PRB', 'INT', 'PKL']) ? 3 : 4;
        $base = $prefix . $yy . $mm;

        $existingNips = static::where('nip', 'like', $base . '%')->pluck('nip');

        $maxSeq = 0;
        foreach ($existingNips as $nip) {
            $seqStr = substr($nip, strlen($base));
            if (is_numeric($seqStr)) {
                $maxSeq = max($maxSeq, (int) $seqStr);
            }
        }

        $nextSeq = $maxSeq + 1;

        do {
            $candidate = $base . str_pad((string) $nextSeq, $padLength, '0', STR_PAD_LEFT);
            $exists = static::where('nip', $candidate)->exists();
            if ($exists) {
                $nextSeq++;
            }
        } while ($exists);

        return $candidate;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'raw_password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'datetime:Y-m-d',
            'password' => 'hashed',
            'count_wfo' => 'boolean',
            'off_days' => 'array',
        ];
    }

    public static $groups = ['user', 'admin', 'superadmin', 'payroll'];

    final public function overtimes()
    {
        return $this->hasMany(Overtime::class, 'employee_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->group === 'user';
    }

    final public function getIsUserAttribute(): bool
    {
        return $this->group === 'user';
    }

    final public function getIsAdminAttribute(): bool
    {
        return $this->group === 'admin' || $this->isSuperadmin;
    }

    final public function getIsSuperadminAttribute(): bool
    {
        return $this->group === 'superadmin';
    }

    final public function getIsNotAdminAttribute(): bool
    {
        return !$this->isAdmin;
    }

    final public function getIsPayrollAttribute(): bool
    {
        return $this->group === 'payroll';
    }

    public function education()
    {
        return $this->belongsTo(Education::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function salary()
    {
        return $this->hasOne(EmployeeSalary::class, 'employee_id');
    }

    public function paymentMethod()
    {
        return $this->hasOne(PaymentMethod::class, 'user_id');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    public function holidays()
    {
        return $this->belongsToMany(Holiday::class, 'holiday_user');
    }

    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class);
    }

    /**
     * Scope query to only include working employees (active or suspend).
     */
    public function scopeWorkingEmployees($query)
    {
        return $query->whereIn('status', ['active', 'suspend']);
    }
}
