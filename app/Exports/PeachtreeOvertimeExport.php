<?php

namespace App\Exports;

use App\Models\Overtime;
use App\Models\OvertimeRate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PeachtreeOvertimeExport implements FromArray, WithHeadings
{
    public function __construct(
        protected ?string $month = null,
        protected ?string $year = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
        protected ?string $division = null,
        protected ?string $jobTitle = null,
        protected ?string $status = 'approved',
        protected array $config = []
    ) {
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee First Name',
            'Employee Middle Initial',
            'Employee Last Name',
            'Employee Name',
            'Check Number',
            'Date',
            'Cash Account',
            'Cash Amount',
            'Pay Period End',
            'Weeks in Pay Period',
            'Hours 1/Salary 1 Amount',
            'Hours 2/Salary 2 Amount',
            'Hours 3/Salary 3 Amount',
            'Hours 4/Salary 4 Amount',
            'Hours 5/Salary 5 Amount',
            'Hours 6/Salary 6 Amount',
            'Hours 7/Salary 7 Amount',
            'Hours 8/Salary 8 Amount',
            'Hours 9/Salary 9 Amount',
            'Hours 10/Salary 10 Amount',
            'Hours 11/Salary 11 Amount',
            'Hours 12/Salary 12 Amount',
            'Hours 13/Salary 13 Amount',
            'Hours 14/Salary 14 Amount',
            'Hours 15/Salary 15 Amount',
            'Hours 16/Salary 16 Amount',
            'Hours 17/Salary 17 Amount',
            'Hours 18/Salary 18 Amount',
            'Hours 19/Salary 19 Amount',
            'Hours 20/Salary 20 Amount',
            'Beginning Balance Transaction',
            'Cash Acnt Date Cleared In Bank Rec',
            'Number of Distributions',
            'Pay Field-Number',
            'Pay Field-Account',
            'Pay Acnt Date Cleared In Bank Rec',
            'Pay Field-Expense Account',
            'Pay Exp Acnt Date Cleared In Bank Rec',
            'Pay Field-Amount',
            'Pay Field-Memo Amount',
            'Number of Labor Distributions',
            'Job Pay-Field Number',
            'Job ID',
            'Job Pay Field-Hours',
            'Job Pay Field-Amount',
            'Used for Reimbursable Expense',
            'Transaction Period',
            'Transaction Number',
            'Pay Method and Frequency',
            'Employee Suffix'
        ];
    }

    public function getQuery(): Builder
    {
        $query = Overtime::with(['employee.division', 'employee.jobTitle', 'employee.salary', 'approver'])
            ->orderBy('overtime_date', 'asc')
            ->orderBy('id', 'asc');

        if (!empty($this->status) && $this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            $query->whereBetween('overtime_date', [
                Carbon::parse($this->dateFrom)->format('Y-m-d'),
                Carbon::parse($this->dateTo)->format('Y-m-d')
            ]);
        } elseif (!empty($this->dateFrom)) {
            $query->where('overtime_date', Carbon::parse($this->dateFrom)->format('Y-m-d'));
        } elseif (!empty($this->month)) {
            $date = Carbon::parse($this->month);
            $query->whereMonth('overtime_date', $date->month)
                  ->whereYear('overtime_date', $date->year);
        } elseif (!empty($this->year)) {
            $query->whereYear('overtime_date', $this->year);
        }

        if (!empty($this->division)) {
            $query->whereHas('employee', function (Builder $q) {
                $q->where('division_id', $this->division);
            });
        }

        if (!empty($this->jobTitle)) {
            $query->whereHas('employee', function (Builder $q) {
                $q->where('job_title_id', $this->jobTitle);
            });
        }

        return $query;
    }

    public function array(): array
    {
        $overtimes = $this->getQuery()->get();
        $rows = [];
        $txNumber = 1;

        $cashPrefix = $this->config['cash_account_prefix'] ?? '10010';
        $overtimePrefix = $this->config['overtime_account_prefix'] ?? '70020';
        $mealPrefix = $this->config['meal_account_prefix'] ?? '70060';
        $liabilityPrefix = $this->config['liability_account_prefix'] ?? '21120';
        $employerExpPrefix = $this->config['expense_account_prefix'] ?? '78010';
        $checkNumberCustom = $this->config['check_number_custom'] ?? null;
        $checkNumberMode = $this->config['check_number_mode'] ?? 'custom';
        $defaultPeriod = $this->config['transaction_period'] ?? null;
        $payMethod = $this->config['pay_method_frequency'] ?? '8';

        foreach ($overtimes as $overtime) {
            $emp = $overtime->employee;
            $empName = trim($emp?->name ?? 'Karyawan');
            $nameParts = preg_split('/\s+/', $empName);

            $firstName = $nameParts[0] ?? '';
            $middleInitial = '';
            $lastName = '';

            if (count($nameParts) == 2) {
                $lastName = $nameParts[1];
            } elseif (count($nameParts) >= 3) {
                if (strlen($nameParts[1]) <= 2) {
                    $middleInitial = rtrim($nameParts[1], '.');
                    $lastName = implode(' ', array_slice($nameParts, 2));
                } else {
                    $lastName = implode(' ', array_slice($nameParts, 1));
                }
            }

            $empId = $emp?->nip ?: ($emp?->id ? substr($emp->id, 0, 10) : 'EMP' . $overtime->id);

            // Check Number selection
            if ($checkNumberMode === 'nip') {
                $checkNumber = $emp?->nip ?: ('OVT' . $overtime->id);
            } elseif ($checkNumberMode === 'prefix_id') {
                $checkNumber = 'LEMBUR-' . $overtime->id;
            } else {
                $checkNumber = $checkNumberCustom ?? '';
            }

            $otDate = Carbon::parse($overtime->overtime_date);
            $formattedDate = $otDate->format('n/j/Y');
            $payPeriodEnd = (clone $otDate)->endOfMonth()->format('n/j/Y');
            $txPeriod = $defaultPeriod ?: ($otDate->month > 0 ? (string)$otDate->month : '15');

            // Division subaccount code
            $divId = $emp?->division_id ?? 1;
            $divSubCode = sprintf('%02d', (int)$divId);
            if (!empty($this->config['division_subaccount_code'])) {
                $divSubCode = $this->config['division_subaccount_code'];
            }

            $cashAccount = $cashPrefix . '-' . $divSubCode;
            $overtimeAccount = $overtimePrefix . '-' . $divSubCode;
            $mealAccount = $mealPrefix . '-' . $divSubCode;
            $liabilityAccount = $liabilityPrefix . '-' . $divSubCode;
            $employerExpAccount = $employerExpPrefix . '-' . $divSubCode;

            $durationHours = (float)($overtime->duration_hours ?? $overtime->calculateDuration());
            $totalPay = (float)($overtime->total_pay ?? $overtime->overtime_pay ?? 0);

            // Progressive calculation breakdown
            $payCalc = OvertimeRate::calculatePayForDuration($durationHours, $emp, $overtime->start_time, $overtime->end_time, $overtime->overtime_date ? $overtime->overtime_date->format('Y-m-d') : null);
            $calculatedMeal = (float)($payCalc['meal_allowance'] ?? 0);
            $primaryRate = (float)($overtime->applied_rate_amount ?? $payCalc['applied_rate_amount'] ?? 15000);

            if ($calculatedMeal > 0 && $totalPay > $calculatedMeal) {
                $mealPay = $calculatedMeal;
                $basePay = $totalPay - $calculatedMeal;
                $hours1 = (string)max(1, $durationHours);
                $hours3 = '1';
            } elseif ($durationHours > 3 && $primaryRate > 0 && $totalPay > (3 * $primaryRate)) {
                // Tier 1 (1-3 hours) and Tier 2/Meal remainder
                $basePay = 3 * $primaryRate;
                $mealPay = $totalPay - $basePay;
                $hours1 = '3';
                $hours3 = (string)($durationHours - 3);
            } elseif ($totalPay > 0) {
                $basePay = $totalPay;
                $mealPay = 0;
                $hours1 = $durationHours > 0 ? (string)$durationHours : '1';
                $hours3 = '0';
            } else {
                $basePay = 0;
                $mealPay = 0;
                $hours1 = '0';
                $hours3 = '0';
            }

            $cashAmount = (int) -round($totalPay);

            // Common header values for all 9 distribution rows
            $commonFields = [
                'Employee ID' => $empId,
                'Employee First Name' => $firstName,
                'Employee Middle Initial' => $middleInitial,
                'Employee Last Name' => $lastName,
                'Employee Name' => $empName,
                'Check Number' => $checkNumber,
                'Date' => $formattedDate,
                'Cash Account' => $cashAccount,
                'Cash Amount' => (string)$cashAmount,
                'Pay Period End' => $payPeriodEnd,
                'Weeks in Pay Period' => '1',
                'Hours 1/Salary 1 Amount' => $hours1,
                'Hours 2/Salary 2 Amount' => '0',
                'Hours 3/Salary 3 Amount' => $hours3,
                'Hours 4/Salary 4 Amount' => '0',
                'Hours 5/Salary 5 Amount' => '0',
                'Hours 6/Salary 6 Amount' => '0',
                'Hours 7/Salary 7 Amount' => '0',
                'Hours 8/Salary 8 Amount' => '0',
                'Hours 9/Salary 9 Amount' => '0',
                'Hours 10/Salary 10 Amount' => '0',
                'Hours 11/Salary 11 Amount' => '0',
                'Hours 12/Salary 12 Amount' => '0',
                'Hours 13/Salary 13 Amount' => '0',
                'Hours 14/Salary 14 Amount' => '0',
                'Hours 15/Salary 15 Amount' => '0',
                'Hours 16/Salary 16 Amount' => '0',
                'Hours 17/Salary 17 Amount' => '0',
                'Hours 18/Salary 18 Amount' => '0',
                'Hours 19/Salary 19 Amount' => '0',
                'Hours 20/Salary 20 Amount' => '0',
                'Beginning Balance Transaction' => 'FALSE',
                'Cash Acnt Date Cleared In Bank Rec' => '',
                'Number of Distributions' => '9',
            ];

            // 9 Distribution definitions: [Pay Field-Number, Pay Field-Account, Pay Field-Expense Account, Pay Field-Amount]
            $distributions = [
                ['number' => '1',  'acct' => $overtimeAccount,    'exp_acct' => '',                  'amount' => (string)(int)$basePay],
                ['number' => '2',  'acct' => $overtimeAccount,    'exp_acct' => '',                  'amount' => '0'],
                ['number' => '3',  'acct' => $mealAccount,        'exp_acct' => '',                  'amount' => (string)(int)$mealPay],
                ['number' => '22', 'acct' => $liabilityAccount,   'exp_acct' => '',                  'amount' => '0'],
                ['number' => '23', 'acct' => $liabilityAccount,   'exp_acct' => '',                  'amount' => '0'],
                ['number' => '24', 'acct' => $liabilityAccount,   'exp_acct' => '',                  'amount' => '0'],
                ['number' => '51', 'acct' => $liabilityAccount,   'exp_acct' => $employerExpAccount, 'amount' => '0'],
                ['number' => '52', 'acct' => $liabilityAccount,   'exp_acct' => $employerExpAccount, 'amount' => '0'],
                ['number' => '53', 'acct' => $liabilityAccount,   'exp_acct' => $employerExpAccount, 'amount' => '0'],
            ];

            foreach ($distributions as $dist) {
                $rows[] = array_merge(array_values($commonFields), [
                    $dist['number'],        // Pay Field-Number
                    $dist['acct'],          // Pay Field-Account
                    '',                     // Pay Acnt Date Cleared In Bank Rec
                    $dist['exp_acct'],      // Pay Field-Expense Account
                    '',                     // Pay Exp Acnt Date Cleared In Bank Rec
                    $dist['amount'],        // Pay Field-Amount
                    '0',                    // Pay Field-Memo Amount
                    '0',                    // Number of Labor Distributions
                    '0',                    // Job Pay-Field Number
                    '',                     // Job ID
                    '0',                    // Job Pay Field-Hours
                    '0',                    // Job Pay Field-Amount
                    'FALSE',                // Used for Reimbursable Expense
                    (string)$txPeriod,      // Transaction Period
                    (string)$txNumber,      // Transaction Number
                    (string)$payMethod,     // Pay Method and Frequency
                    ''                      // Employee Suffix
                ]);
            }

            $txNumber++;
        }

        return $rows;
    }

    /**
     * Generate raw CSV string with proper escaping and formatting
     */
    public function toCsvString(): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $this->headings());

        foreach ($this->array() as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
