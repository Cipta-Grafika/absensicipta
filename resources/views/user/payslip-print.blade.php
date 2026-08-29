<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $payroll->employee->name }} - {{ \Carbon\Carbon::parse($payroll->period_month)->format('F Y') }}</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            background-color: white;
            font-size: 11pt;
            line-height: 1.4;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm 15mm;
            margin: 0 auto;
            background: white;
            box-sizing: border-box;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid black;
            margin-bottom: 15px;
            padding-bottom: 2px;
        }

        .header-table td {
            vertical-align: top;
        }

        .title-section {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 10.5pt;
            line-height: 1.2;
        }

        .info-table td {
            padding: 0 5px;
            vertical-align: top;
        }

        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid black;
        }

        .payroll-table th {
            border: 1px solid black;
            padding: 4px;
            text-align: center;
            font-weight: bold;
        }

        .payroll-table td {
            border-bottom: 1px solid black;
            padding: 2px 4px;
        }

        .payroll-table .total-row td {
            font-weight: bold;
            border-bottom: none;
        }

        .summary-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .summary-table td {
            vertical-align: top;
        }

        .total-payroll-box {
            text-align: right;
        }

        .total-payroll-title {
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 10px;
        }

        .total-payroll-amount {
            background-color: #bffff3;
            font-weight: bold;
            font-size: 12pt;
            padding: 5px 10px;
            display: inline-block;
            min-width: 150px;
            text-align: right;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            margin-bottom: 30px;
        }

        .attendance-table th, .attendance-table td {
            border: 1px solid black;
            padding: 2px 4px;
        }

        .attendance-table th {
            text-align: center;
            font-weight: bold;
        }

        .attendance-table td.label {
            width: 38%;
            padding-left: 15px;
        }

        .attendance-table td.value {
            width: 12%;
            text-align: center;
        }

        .signature-section {
            text-align: right;
            margin-top: 40px;
            padding-right: 20px;
        }

        .signature-qr {
            width: 80px;
            height: 80px;
            margin: 10px 0;
            display: inline-block;
        }

        @media print {
            body {
                background-color: transparent;
            }
            @page {
                size: A4;
                margin: 0;
            }
            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                padding: 10mm 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 16%; text-align: left;">
                    @if (isset($logoPath) && file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Cipta Grafika" style="max-height: 80px;">
                    @elseif (isset($logoBase64) && $logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Cipta Grafika" style="max-height: 80px;">
                    @else
                        <img src="{{ asset('cipta_grafika.png') }}" onerror="this.src='{{ asset('logo.png') }}'; this.onerror=null;" alt="Cipta Grafika" style="max-height: 80px;">
                    @endif
                </td>
                <td style="width: 64%; padding-left: 5px;">
                    <div style="font-weight: bold; color: #2A549B; font-size: 18pt;">Cipta Grafika</div>
                    <div style="font-size: 8.5pt; color: black; line-height: 1.2;">
                        Ruko Broadway Blok III No. B 17, Kompleks Galuh Mas - Karawang<br>
                        Telp : 0267 8455970-51<br>
                        Email : ciptagrafika@gmail.com
                    </div>
                </td>
                <td style="width: 20%; text-align: right; font-size: 8.5pt; line-height: 1.2; vertical-align: middle;">
                    <div>*Percetakan</div>
                    <div>*Toko Kertas</div>
                    <div>*Peralatan Kantor</div>
                    <div>*General Supplier</div>
                </td>
            </tr>
        </table>

        <!-- Title -->
        <div class="title-section">
            SLIP GAJI KARYAWAN
        </div>

        <!-- Employee Info -->
        <div style="margin-bottom: 10px;">
            <table class="info-table">
                <tr>
                    <td style="width: 20%; white-space: nowrap;">NIP</td>
                    <td style="width: 2%;">:</td>
                    <td style="width: 28%;">{{ $payroll->employee->nip ?? '-' }}</td>
                    <td style="width: 20%; white-space: nowrap;">Tanggal Masuk</td>
                    <td style="width: 2%;">:</td>
                    <td style="width: 28%;">{{ $payroll->employee->created_at ? $payroll->employee->created_at->format('d-M-Y') : '-' }}</td>
                </tr>
                <tr>
                    <td>Nama Karyawan</td>
                    <td>:</td>
                    <td>{{ strtoupper($payroll->employee->name) }}</td>
                    <td>Periode Gaji</td>
                    <td>:</td>
                    <td>{{ \Carbon\Carbon::parse($payroll->period_month)->format('F-y') }}</td>
                </tr>
                <tr>
                    <td>Divisi</td>
                    <td>:</td>
                    <td colspan="4">{{ strtoupper(optional($payroll->employee->division)->name ?? '-') }}</td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td>:</td>
                    <td colspan="4">{{ strtoupper(optional($payroll->employee->jobTitle)->name ?? '-') }}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>:</td>
                    <td colspan="4">KARYAWAN</td>
                </tr>
            </table>
        </div>

        <!-- Payroll Details Table -->
        <table class="payroll-table">
            <thead>
                <tr>
                    <th colspan="4" style="width: 50%;">Pendapatan</th>
                    <th colspan="4" style="width: 50%;">Potongan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Prepare Earnings
                    $earningRows = [];
                    $earningRows[] = ['name' => 'Gaji Pokok', 'amount' => $payroll->basic_salary_earned];
                    foreach($payroll->details->where('type', 'earning') as $e) {
                        $earningRows[] = ['name' => $e->name, 'amount' => $e->amount];
                    }
                    
                    // Prepare Deductions
                    $deductionRows = [];
                    $deductions = $payroll->details->where('type', 'deduction');
                    $coveredIds = [];

                    // 1. Absensi (Alpa / Mangkir / Absensi)
                    $absensiDetails = $deductions->filter(function($d) {
                        return stripos($d->name, 'Alpa') !== false 
                            || stripos($d->name, 'Mangkir') !== false 
                            || stripos($d->name, 'Absen') !== false;
                    });
                    foreach ($absensiDetails as $d) {
                        $coveredIds[] = $d->id;
                    }
                    $totalAbsensiAmount = $absensiDetails->sum('amount');
                    $absensiLabel = 'Absensi' . ($payroll->total_absent > 0 ? " ({$payroll->total_absent} Hari)" : '');

                    // 2. Telat (Terlambat / Keterlambatan / Telat / Penalti Terlambat)
                    $lateDetails = $deductions->filter(function($d) {
                        return stripos($d->name, 'Terlambat') !== false 
                            || stripos($d->name, 'Keterlambatan') !== false 
                            || stripos($d->name, 'Telat') !== false;
                    });
                    foreach ($lateDetails as $d) {
                        $coveredIds[] = $d->id;
                    }
                    $totalLateAmount = $lateDetails->sum('amount');
                    $lateMinutes = abs($payroll->total_late_minutes ?: 0);
                    $lateLabel = "Telat" . ($lateMinutes > 0 ? " ({$lateMinutes} menit)" : '');

                    // 3. IMP
                    $impDetails = $deductions->filter(function($d) {
                        return stripos($d->name, 'IMP') !== false;
                    });
                    foreach ($impDetails as $d) {
                        $coveredIds[] = $d->id;
                    }
                    $totalImpAmount = $impDetails->sum('amount');
                    $impLabel = 'IMP' . ($payroll->total_unreplaced_imp_hours > 0 ? " (" . round($payroll->total_unreplaced_imp_hours, 2) . " Jam)" : '');

                    // 4. Syirkah
                    $syirkahDetails = $deductions->filter(function($d) {
                        return stripos($d->name, 'Syirkah') !== false;
                    });
                    foreach ($syirkahDetails as $d) {
                        $coveredIds[] = $d->id;
                    }
                    $totalSyirkahAmount = $syirkahDetails->sum('amount');

                    // 5. Penalti Cuti
                    $cutiDetails = $deductions->filter(function($d) {
                        return stripos($d->name, 'Cuti') !== false;
                    });
                    foreach ($cutiDetails as $d) {
                        $coveredIds[] = $d->id;
                    }
                    $totalCutiAmount = $cutiDetails->sum('amount');
                    $cutiLabel = 'Penalti Cuti' . ($payroll->penalized_cuti_days > 0 ? " ({$payroll->penalized_cuti_days} Hari)" : '');

                    // 6. PPh 21
                    $pphDetails = $deductions->filter(function($d) {
                        return stripos($d->name, 'PPh') !== false || stripos($d->name, 'Pajak') !== false;
                    });
                    foreach ($pphDetails as $d) {
                        $coveredIds[] = $d->id;
                    }
                    $totalPphAmount = $pphDetails->sum('amount');

                    $deductionRows[] = ['name' => $absensiLabel, 'amount' => $totalAbsensiAmount];
                    $deductionRows[] = ['name' => $lateLabel, 'amount' => $totalLateAmount];
                    $deductionRows[] = ['name' => $impLabel, 'amount' => $totalImpAmount];
                    $deductionRows[] = ['name' => 'Syirkah', 'amount' => $totalSyirkahAmount];
                    if ($totalCutiAmount > 0 || $payroll->penalized_cuti_days > 0) {
                        $deductionRows[] = ['name' => $cutiLabel, 'amount' => $totalCutiAmount];
                    }
                    $deductionRows[] = ['name' => 'PPh 21', 'amount' => $totalPphAmount];

                    // 7. Dynamic remaining deductions (e.g. Pinjaman, Potongan Khusus/BCA, WFH, Sakit, Izin)
                    foreach ($deductions as $d) {
                        if (!in_array($d->id, $coveredIds)) {
                            $cleanName = str_ireplace(['Potongan WFH/WFA', 'Potongan Sakit', 'Potongan Izin'], ['WFH/A', 'Sakit', 'Izin'], $d->name);
                            $deductionRows[] = ['name' => $cleanName, 'amount' => $d->amount];
                        }
                    }
                    
                    $maxRows = max(count($earningRows), count($deductionRows));
                @endphp

                @for($i = 0; $i < $maxRows; $i++)
                    @php
                        $e = $earningRows[$i] ?? null;
                        $d = $deductionRows[$i] ?? null;
                    @endphp
                    <tr>
                        <td style="width: 25%;">{{ $e ? $e['name'] : '' }}</td>
                        <td style="width: 2%;">{{ $e ? ':' : '' }}</td>
                        <td style="width: 3%;">{{ $e ? 'Rp' : '' }}</td>
                        <td style="width: 20%; text-align: right; border-right: 1px solid black;">
                            {{ $e ? ($e['amount'] > 0 ? number_format($e['amount'], 0, ',', ',') : '-') : '' }}
                        </td>
                        
                        <td style="width: 25%; padding-left: 5px;">{{ $d ? $d['name'] : '' }}</td>
                        <td style="width: 2%;">{{ $d ? ':' : '' }}</td>
                        <td style="width: 3%;">{{ $d ? 'Rp' : '' }}</td>
                        <td style="width: 20%; text-align: right;">
                            {{ $d ? ($d['amount'] > 0 ? number_format($d['amount'], 0, ',', ',') : '-') : '' }}
                        </td>
                    </tr>
                @endfor
                
                <tr class="total-row">
                    <td>Total Pendapatan</td>
                    <td>:</td>
                    <td>Rp</td>
                    <td style="text-align: right; border-right: 1px solid black;">
                        {{ number_format($payroll->basic_salary_earned + $payroll->total_allowance + $payroll->total_overtime_pay, 0, ',', ',') }}
                    </td>
                    <td style="padding-left: 5px;">Total Potongan</td>
                    <td>:</td>
                    <td>Rp</td>
                    <td style="text-align: right;">
                        {{ number_format($payroll->total_deduction, 0, ',', ',') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Summary & Bank Section -->
        <table class="summary-table">
            <tr>
                <td style="width: 60%;">
                    @php
                        $pm = $payroll->employee->paymentMethod;
                    @endphp
                    @if($pm)
                        <div>Pembayaran Gaji telah dilakukan oleh Perusahaan</div>
                        <div>Secara transfer ke rekening karyawan</div>
                        <table style="margin-top: 5px;">
                            <tr>
                                <td style="width: 25%;">{{ $pm->payment_name }}</td>
                                <td style="width: 5%;">:</td>
                                <td style="font-weight: bold;">{{ preg_replace('/[\x{200B}-\x{200D}\x{200E}\x{200F}\x{202A}-\x{202E}\x{FEFF}]/u', '', $pm->bank_account ?? '-') }}</td>
                            </tr>
                            <tr>
                                <td>Atas Nama</td>
                                <td>:</td>
                                <td style="font-weight: bold;">{{ strtoupper($pm->account_name ?: $payroll->employee->name) }}</td>
                            </tr>
                        </table>
                    @else
                        <div>Pembayaran Gaji telah dilakukan oleh Perusahaan</div>
                        <div>Secara CASH kepada karyawan berikut:</div>
                        <table style="margin-top: 5px;">
                            <tr>
                                <td style="width: 25%;">Metode</td>
                                <td style="width: 5%;">:</td>
                                <td style="font-weight: bold;">CASH</td>
                            </tr>
                            <tr>
                                <td>Atas Nama</td>
                                <td>:</td>
                                <td style="font-weight: bold;">{{ strtoupper($payroll->employee->name) }}</td>
                            </tr>
                        </table>
                    @endif
                </td>
                <td style="width: 40%;" class="total-payroll-box">
                    <div class="total-payroll-title">TOTAL PAYROLL</div>
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="text-align: left; background-color: #bffff3; padding: 5px 10px; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact;">Rp</td>
                                <td style="text-align: right; background-color: #bffff3; padding: 5px 10px; font-weight: bold; font-size: 12pt; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                    {{ number_format($payroll->net_salary, 0, ',', ',') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Attendance Summary Table -->
        <table class="attendance-table">
            <thead>
                <tr>
                    <th colspan="4">Rekapitulasi Informasi Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label">Kehadiran</td>
                    <td class="value">{{ $payroll->total_present }}</td>
                    <td class="label">Terlambat (kali)</td>
                    <td class="value">{{ $payroll->late_days_count }}</td>
                </tr>
                <tr>
                    <td class="label">WFH/A</td>
                    <td class="value">{{ $payroll->total_wfh }}</td>
                    <td class="label">IMP (menit)</td>
                    <td class="value">{{ $payroll->total_unreplaced_imp_hours > 0 ? ($payroll->total_unreplaced_imp_hours * 60) : '0' }}</td>
                </tr>
                <tr>
                    <td class="label">Cuti</td>
                    <td class="value">{{ $payroll->penalized_cuti_days }}</td>
                    <td class="label">Pulang Lebih Dulu</td>
                    <td class="value"></td>
                </tr>
                <tr>
                    <td class="label">Sakit</td>
                    <td class="value">{{ $payroll->total_sick }}</td>
                    <td class="label">Lupa Absen</td>
                    <td class="value"></td>
                </tr>
                <tr>
                    <td class="label">Izin</td>
                    <td class="value">{{ $payroll->total_excused }}</td>
                    <td class="label">Izin Setengah Hari</td>
                    <td class="value"></td>
                </tr>
                <tr>
                    <td class="label">Alpa</td>
                    <td class="value">{{ $payroll->total_absent }}</td>
                    <td class="label">Izin Di awal Jam Kerja</td>
                    <td class="value"></td>
                </tr>
            </tbody>
        </table>

        <!-- Signature -->
        <div class="signature-section">
            <div style="font-weight: bold; margin-bottom: 5px;">Accounting</div>
            @if (isset($qrPath) && file_exists($qrPath))
                <img src="{{ $qrPath }}" class="signature-qr" alt="QR Code">
            @elseif (isset($qrDataUri) && $qrDataUri)
                <img src="{{ $qrDataUri }}" class="signature-qr" alt="QR Code">
            @else
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=PAYROLL-{{ $payroll->id }}" class="signature-qr" alt="QR Code">
            @endif
            <div style="font-weight: bold; margin-top: 5px;">Anggi Amelia</div>
        </div>
    </div>

    @if (!isset($isPdf) || !$isPdf)
    <!-- Auto Print Script -->
    <script type="text/javascript">
        const AUTO_CLOSE_DELAY = 2 * 60 * 1000;
        let autoCloseTimer = null;

        function scheduleAutoClose() {
            if (autoCloseTimer) {
                clearTimeout(autoCloseTimer);
            }
            autoCloseTimer = setTimeout(() => {
                window.close();
            }, AUTO_CLOSE_DELAY);
        }

        window.addEventListener('load', () => {
            setTimeout(() => {
                window.focus();
                window.print();
            }, 500);
            scheduleAutoClose();
        });
        
        window.addEventListener('afterprint', () => {
            window.close();
        });
    </script>
    @endif
</body>
</html>
