<?php

namespace App\Imports;

use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class WorkSchedulesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public function __construct(public bool $save = true) {}

    public function model(array $row)
    {
        $nip = trim((string)($row['nip'] ?? $row['NIP'] ?? ''));
        $name = trim((string)($row['nama_karyawan'] ?? $row['nama'] ?? $row['Nama Karyawan'] ?? ''));
        $dateRaw = trim((string)($row['tanggal'] ?? $row['Tanggal'] ?? ''));
        $statusStr = trim((string)($row['status'] ?? $row['Status'] ?? ''));
        $divisionName = trim((string)($row['divisi'] ?? $row['Divisi'] ?? ''));
        $note = trim((string)($row['catatan'] ?? $row['note'] ?? $row['Catatan'] ?? ''));

        // 1. Check if both NIP and Name are empty
        if (empty($nip) && empty($name)) {
            throw new \Exception("Gagal Import: Kolom NIP dan Nama Karyawan tidak boleh kosong bersamaan.");
        }

        $userQuery = User::where('group', 'user');

        // 2. Find and Validate User
        if (!empty($nip) && !empty($name)) {
            $user = (clone $userQuery)->where('nip', $nip)->first();
            if (!$user) {
                throw new \Exception("Gagal Import: Karyawan dengan NIP '{$nip}' tidak ditemukan.");
            }
            if (strtolower(trim($user->name)) !== strtolower(trim($name))) {
                throw new \Exception("Gagal Import: Data NIP '{$nip}' terdaftar atas nama '{$user->name}', bukan '{$name}'. Mohon periksa kembali data Excel.");
            }
        } elseif (!empty($nip)) {
            $user = (clone $userQuery)->where('nip', $nip)->first();
            if (!$user) {
                throw new \Exception("Gagal Import: Karyawan dengan NIP '{$nip}' tidak ditemukan.");
            }
        } else {
            $usersMatched = (clone $userQuery)->where('name', 'like', $name)->get();
            if ($usersMatched->isEmpty()) {
                throw new \Exception("Gagal Import: Karyawan dengan nama '{$name}' tidak ditemukan.");
            } elseif ($usersMatched->count() > 1) {
                throw new \Exception("Gagal Import: Terdapat lebih dari 1 karyawan bernama '{$name}'. Cantumkan NIP untuk identifikasi unik.");
            }
            $user = $usersMatched->first();
        }

        // 3. Validate Division if specified in Excel
        if (!empty($divisionName)) {
            $actualDivision = $user->division?->name ?? 'Tanpa Divisi';
            if (strtolower(trim($actualDivision)) !== strtolower(trim($divisionName))) {
                throw new \Exception("Gagal Import: Karyawan '{$user->name}' terdaftar di Divisi '{$actualDivision}', tidak cocok dengan Divisi '{$divisionName}' di Excel.");
            }
        }

        // 4. Validate Admin Scope
        $authUser = Auth::user();
        if ($authUser && $authUser->group === 'admin') {
            if ($user->division_id !== $authUser->division_id) {
                throw new \Exception("Gagal Import (Akses Ditolak): Karyawan '{$user->name}' berada di luar divisi Anda.");
            }
        }

        // 5. Parse Status
        $statusLower = strtolower($statusStr);
        $isWorkingDay = 1;
        if (in_array($statusLower, ['libur', 'day off', 'off', '0', 'hari libur', 'false'])) {
            $isWorkingDay = 0;
        } elseif (in_array($statusLower, ['kerja', 'masuk', '1', 'hari kerja', 'true', 'wajib masuk'])) {
            $isWorkingDay = 1;
        }

        // 6. Parse Date
        if (empty($dateRaw)) {
            throw new \Exception("Gagal Import: Kolom tanggal tidak boleh kosong untuk karyawan '{$user->name}'.");
        }

        try {
            if (is_numeric($dateRaw)) {
                $parsedDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateRaw))->format('Y-m-d');
            } else {
                $parsedDate = Carbon::parse($dateRaw)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            throw new \Exception("Gagal Import: Format tanggal '{$dateRaw}' tidak valid untuk karyawan '{$user->name}'. Gunakan format YYYY-MM-DD.");
        }

        $schedule = (new WorkSchedule)->forceFill([
            'user_id' => $user->id,
            'date' => $parsedDate,
            'is_working_day' => $isWorkingDay,
            'note' => $note,
            'created_by' => $authUser?->id,
        ]);

        if ($this->save) {
            WorkSchedule::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $parsedDate,
                ],
                [
                    'is_working_day' => $isWorkingDay,
                    'note' => $note,
                    'created_by' => $authUser?->id,
                ]
            );
        }

        return $schedule;
    }

    public function rules(): array
    {
        return [];
    }

    public function onFailure(Failure ...$failures)
    {
        $messages = [];
        foreach ($failures as $failure) {
            $messages[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
        }
        throw new \Exception(implode('<br>', $messages));
    }
}
