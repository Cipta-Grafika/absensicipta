<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case LATE = 'late';
    case WFH = 'wfh';
    case SICK = 'sick';
    case LEAVE = 'leave';
    case DAYOFF = 'dayoff';
    case IMP = 'imp';
    case EXCUSED = 'excused';
    case ABSENT = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Hadir',
            self::LATE => 'Terlambat',
            self::WFH => 'WFH',
            self::SICK => 'Sakit',
            self::LEAVE => 'Cuti',
            self::DAYOFF => 'Libur / Off',
            self::IMP => 'Izin Meninggalkan Pekerjaan (IMP)',
            self::EXCUSED => 'Izin',
            self::ABSENT => 'Alpa',
        };
    }
}
