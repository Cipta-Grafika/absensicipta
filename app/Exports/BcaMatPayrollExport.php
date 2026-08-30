<?php

namespace App\Exports;

use App\Models\Payroll;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BcaMatPayrollExport
{
    public string $periodMonth;
    public string $transactionDate;
    public string $bankType;
    public string $remark;
    public ?array $selectedPayrollIds;
    public bool $onlyWithAccount;
    public int $custType;
    public int $custResidence;

    public function __construct(
        string $periodMonth,
        string $transactionDate = '',
        string $bankType = 'BCA',
        string $remark = '',
        ?array $selectedPayrollIds = null,
        bool $onlyWithAccount = false,
        int $custType = 1,
        int $custResidence = 1
    ) {
        $this->periodMonth = $periodMonth;
        $this->transactionDate = $transactionDate ?: date('Y-m-d');
        $this->bankType = $bankType ?: 'BCA';
        $this->remark = $remark;
        $this->selectedPayrollIds = $selectedPayrollIds;
        $this->onlyWithAccount = $onlyWithAccount;
        $this->custType = $custType ?: 1;
        $this->custResidence = $custResidence ?: 1;
    }

    /**
     * Get the query/collection of payrolls sorted by employee creation date (oldest to newest).
     */
    public function getPayrolls(): Collection
    {
        $query = Payroll::with(['employee.paymentMethod', 'employee.division', 'employee.jobTitle'])
            ->whereHas('employee', function ($q) {
                $q->onlyEmployee();
            })
            ->where('period_month', $this->periodMonth)
            ->where('net_salary', '>', 0);

        if (!empty($this->selectedPayrollIds)) {
            $query->whereIn('payrolls.id', $this->selectedPayrollIds);
        }

        // Join users to strictly sort by oldest employee first (users.created_at asc, users.id asc)
        return $query->join('users', 'payrolls.employee_id', '=', 'users.id')
            ->select('payrolls.*')
            ->orderBy('users.created_at', 'asc')
            ->orderBy('users.id', 'asc')
            ->get();
    }

    /**
     * Generate the spreadsheet using the official BCA MAT template.
     */
    public function generateSpreadsheet(): Spreadsheet
    {
        $templatePath = resource_path('templates/template_mat_bca.xlsx');
        if (!file_exists($templatePath)) {
            $templatePath = base_path('docs/Template MAT Multi Payroll .xlsx');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheetByName('Data') ?: $spreadsheet->getSheet(0);

        // Ensure active sheet is 'Data'
        $spreadsheet->setActiveSheetIndexByName($sheet->getTitle());

        $initialHighestRow = $sheet->getHighestRow();

        $payrolls = $this->getPayrolls();
        $formattedDatePrefix = Carbon::parse($this->transactionDate)->format('dmY'); // e.g. 27082026 or 30082026

        $currentRow = 2;
        $sequence = 1;

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;
            $paymentMethod = $employee?->paymentMethod;

            // If onlyWithAccount is true and no bank account, skip
            if ($this->onlyWithAccount && empty($paymentMethod?->bank_account)) {
                continue;
            }

            // Transaction ID format: e.g. 27082026-001 (max 18 chars, unique per month, sequential)
            $transactionId = sprintf('%s-%03d', $formattedDatePrefix, $sequence);

            // Account number (rekening) as String to preserve leading zeros
            $accountNumber = $paymentMethod?->bank_account ? trim((string)$paymentMethod->bank_account) : '';

            // Receiver Name (uppercase, max 70 chars)
            $receiverName = strtoupper($paymentMethod?->account_name ?: ($employee?->name ?? ''));
            $receiverName = mb_substr($receiverName, 0, 70);

            // Amount (Net Salary) as float/number
            $amount = (float) $payroll->net_salary;

            // NIP (max 18 chars) - strictly set as text
            $nipStr = $employee?->nip ? mb_substr(trim((string)$employee->nip), 0, 18) : '';

            // Remark (max 18 chars)
            $remarkVal = $this->remark ? mb_substr($this->remark, 0, 18) : null;

            // Email
            $email = $employee?->email ? mb_substr((string)$employee->email, 0, 300) : null;

            // Fill Cells
            // Col A: No (leave null matching template / sample)
            $sheet->setCellValue('A' . $currentRow, null);

            // Col B: Transaction ID (explicit text)
            $sheet->setCellValueExplicit('B' . $currentRow, $transactionId, DataType::TYPE_STRING);

            // Col C: Transfer Type (BCA)
            $sheet->setCellValue('C' . $currentRow, $this->bankType);

            // Col D: Beneficiary ID (null)
            $sheet->setCellValue('D' . $currentRow, null);

            // Col E: Credited Account (explicit text)
            $sheet->setCellValueExplicit('E' . $currentRow, $accountNumber, DataType::TYPE_STRING);

            // Col F: Receiver Name
            $sheet->setCellValue('F' . $currentRow, $receiverName);

            // Col G: Amount (numeric with formatting _(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_))
            $sheet->setCellValue('G' . $currentRow, $amount);
            $sheet->getStyle('G' . $currentRow)->getNumberFormat()->setFormatCode('_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)');

            // Col H: NIP (strictly text)
            $sheet->setCellValueExplicit('H' . $currentRow, $nipStr, DataType::TYPE_STRING);
            $sheet->getStyle('H' . $currentRow)->getNumberFormat()->setFormatCode('@');

            // Col I: Remark
            $sheet->setCellValue('I' . $currentRow, $remarkVal);

            // Col J: Beneficiary email address
            $sheet->setCellValue('J' . $currentRow, $email);

            // Col K: Receiver Swift Code (null for BCA)
            $sheet->setCellValue('K' . $currentRow, null);

            // Col L: Receiver Cust Type (1 = Perorangan, 2 = Perusahaan, 3 = Pemerintah)
            $sheet->setCellValue('L' . $currentRow, (int) $this->custType);
            $sheet->getStyle('L' . $currentRow)->getNumberFormat()->setFormatCode('0');

            // Col M: Receiver Cust Residence (1 = Residence / Penduduk, 2 = Non Residence / Bukan Penduduk)
            $sheet->setCellValue('M' . $currentRow, (int) $this->custResidence);
            $sheet->getStyle('M' . $currentRow)->getNumberFormat()->setFormatCode('0');

            // Apply styling on row cells
            for ($col = 'A'; $col <= 'M'; $col++) {
                $cellCoordinate = $col . $currentRow;
                $style = $sheet->getStyle($cellCoordinate);

                // Font: Calibri 11
                $style->getFont()->setName('Calibri')->setSize(11);

                // Alignment: Center / Center
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Borders: Thin on all sides
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }

            $currentRow++;
            $sequence++;
        }

        // Clean any leftover empty rows from the template
        for ($r = $currentRow; $r <= $initialHighestRow; $r++) {
            for ($c = 'A'; $c <= 'M'; $c++) {
                $sheet->getCell($c . $r)->setValue(null);
                $sheet->getStyle($c . $r)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
            }
        }

        return $spreadsheet;
    }

    /**
     * Download the spreadsheet as a streamed Excel file response (Livewire compatible).
     */
    public function download(string $filename = '')
    {
        if (empty($filename)) {
            $dateStr = Carbon::parse($this->transactionDate)->format('dmY');
            $filename = sprintf('PAYROLL-%s_%s_%s.xlsx', strtoupper($this->bankType), $this->periodMonth, $dateStr);
        }

        $spreadsheet = $this->generateSpreadsheet();

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
