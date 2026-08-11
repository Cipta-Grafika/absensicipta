<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Carbon;

class PayslipPrintController extends Controller
{
    /**
     * Handle direct PDF download for payslip with password encryption.
     */
    public function print($id)
    {
        $user = auth()->user();
        $payrollQuery = Payroll::with(['employee.division', 'employee.jobTitle', 'employee.paymentMethod', 'details']);

        if (!$user->isAdmin) {
            $payrollQuery->where('employee_id', $user->id);
        }

        $payroll = $payrollQuery->findOrFail($id);

        // 1. Physical Logo path
        $logoPath = public_path('cipta_grafika.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('logo.png');
        }

        // 2. Physical QR Code temp file path (allows Dompdf to render native PNG image directly from disk)
        $qrPath = storage_path('app/qr_payslip_' . $payroll->id . '.png');
        try {
            $qrCode = new QrCode('PAYROLL-' . $payroll->id);
            $writer = new PngWriter();
            $writer->write($qrCode)->saveToFile($qrPath);
        } catch (\Throwable $e) {
            $qrPath = null;
        }

        $html = view('user.payslip-print', [
            'payroll' => $payroll,
            'isPdf' => true,
            'logoPath' => $logoPath,
            'qrPath' => $qrPath,
        ])->render();

        // Inject PDF specific styling overrides to ensure single-page fit without changing any layout format
        $pdfStyles = '
        <style>
            @page { margin: 10mm 12mm; }
            .page { width: 100% !important; min-height: auto !important; padding: 0 !important; }
            .payroll-table { margin-bottom: 10px !important; }
            .attendance-table { margin-bottom: 15px !important; }
            .signature-section { margin-top: 15px !important; }
        </style>';

        $html = str_replace('</head>', $pdfStyles . '</head>', $html);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'chroot' => [public_path(), storage_path()],
        ]);

        $pdf->render();

        // Encrypt PDF file with employee's password
        $canvas = $pdf->getDomPDF()->getCanvas();
        $cpdf = $canvas->get_cpdf();
        if ($cpdf) {
            $emp = $payroll->employee;
            $pdfPassword = $emp?->birth_date ? Carbon::parse($emp->birth_date)->format('dmY') : ($emp?->nip ?? '123456');
            $cpdf->setEncryption($pdfPassword, config('app.key'), ['print']);
        }

        $pdfOutput = $pdf->output();

        // Clean up temporary QR Code file
        if ($qrPath && file_exists($qrPath)) {
            @unlink($qrPath);
        }

        $periodMonth = Carbon::parse($payroll->period_month)->format('F_Y');
        $employeeNip = $payroll->employee?->nip ?? 'User';
        $filename = "Slip_Gaji_{$employeeNip}_{$periodMonth}.pdf";

        return response()->streamDownload(function () use ($pdfOutput) {
            echo $pdfOutput;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
