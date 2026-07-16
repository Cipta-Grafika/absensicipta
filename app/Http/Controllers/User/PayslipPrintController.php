<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Illuminate\Http\Request;

class PayslipPrintController extends Controller
{
    /**
     * Handle the incoming request to print the payslip.
     */
    public function print($id)
    {
        $payroll = Payroll::with(['employee.division', 'employee.jobTitle', 'details'])
            ->where('employee_id', auth()->id())
            ->findOrFail($id);

        return view('user.payslip-print', compact('payroll'));
    }
}
