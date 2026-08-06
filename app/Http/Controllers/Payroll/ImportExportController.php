<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;

class ImportExportController extends Controller
{
    public function employeeSalaries()
    {
        return view('payroll.import-export.employee-salaries');
    }

    public function paymentMethods()
    {
        return view('payroll.import-export.payment-methods');
    }

    public function savings()
    {
        return view('payroll.import-export.savings');
    }

    public function savingTransactions()
    {
        return view('payroll.import-export.saving-transactions');
    }
}
