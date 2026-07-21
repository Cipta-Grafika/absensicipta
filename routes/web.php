<?php

use App\Helpers;
use App\Http\Controllers\Admin\BarcodeController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserAttendanceController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

Route::get('/', function () {
    // return view('welcome');
    return redirect('/login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', fn () => Auth::user()->isAdmin ? redirect('/hr') : redirect('/home'));

    // USER AREA
    Route::middleware('user')->group(function () {
        Route::get('/home', HomeController::class)->name('home');


        Route::get('/attendance-history', [UserAttendanceController::class, 'history'])
            ->name('attendance-history');

        Route::get('/overtimes', \App\Livewire\User\OvertimeComponent::class)
            ->name('user.overtimes');

        Route::get('/replacement-hours', \App\Livewire\User\ReplacementHourComponent::class)
            ->name('user.replacement-hours');

        Route::get('/user/payslips/{id}/print', [\App\Http\Controllers\User\PayslipPrintController::class, 'print'])
            ->name('user.payslip.print');
    });

    // HR AREA (formerly ADMIN)
    Route::prefix('hr')->middleware('admin')->group(function () {
        Route::get('/', fn () => redirect('/hr/dashboard'));
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('hr.dashboard');

        // Superadmin ONLY routes (Global Master Data & Barcodes)
        Route::middleware([App\Http\Middleware\SuperAdminMiddleware::class])->group(function () {
            // Barcode
            Route::resource('/barcodes', BarcodeController::class)
                ->only(['index', 'show', 'create', 'store', 'edit', 'update'])
                ->names([
                    'index' => 'hr.barcodes',
                    'show' => 'hr.barcodes.show',
                    'create' => 'hr.barcodes.create',
                    'store' => 'hr.barcodes.store',
                    'edit' => 'hr.barcodes.edit',
                    'update' => 'hr.barcodes.update',
                ]);
            Route::get('/barcodes/download/all', [BarcodeController::class, 'downloadAll'])
                ->name('hr.barcodes.downloadall');
            Route::get('/barcodes/{id}/download', [BarcodeController::class, 'download'])
                ->name('hr.barcodes.download');

            // Global Master Data
            Route::get('/masterdata/division', [MasterDataController::class, 'division'])
                ->name('hr.masters.division');
            Route::get('/masterdata/job-title', [MasterDataController::class, 'jobTitle'])
                ->name('hr.masters.job-title');
            Route::get('/masterdata/education', [MasterDataController::class, 'education'])
                ->name('hr.masters.education');
            Route::get('/masterdata/shift', [MasterDataController::class, 'shift'])
                ->name('hr.masters.shift');
        });

        // User/Employee/Karyawan
        Route::resource('/employees', EmployeeController::class)
            ->only(['index'])
            ->names(['index' => 'hr.employees']);

        // Scoped Master Data (Allowed for Admin)
        Route::get('/masterdata/admin', [MasterDataController::class, 'admin'])
            ->name('hr.masters.admin');

        // Presence/Absensi
        Route::get('/attendances', [AttendanceController::class, 'index'])
            ->name('hr.attendances');

        // Presence/Absensi
        Route::get('/attendances/report', [AttendanceController::class, 'report'])
            ->name('hr.attendances.report');

        // Replacement Approval (Ganti Jam)
        Route::get('/replacement-approvals', \App\Livewire\Admin\ReplacementApprovalComponent::class)
            ->name('hr.replacement-approvals');
            
        Route::get('/replacement-approvals/report', [\App\Http\Controllers\Admin\ReplacementApprovalController::class, 'report'])
            ->name('hr.replacement-approvals.report');

        // Overtime Approval (Lembur)
        Route::get('/overtime-approvals', \App\Livewire\Admin\OvertimeApprovalComponent::class)
            ->name('hr.overtime-approvals');
            
        Route::get('/overtime-approvals/report', [\App\Http\Controllers\Admin\OvertimeApprovalController::class, 'report'])
            ->name('hr.overtime-approvals.report');

        // Import/Export
        Route::get('/import-export/users', [ImportExportController::class, 'users'])
            ->name('hr.import-export.users');
        Route::get('/import-export/attendances', [ImportExportController::class, 'attendances'])
            ->name('hr.import-export.attendances');

        Route::post('/users/import', [ImportExportController::class, 'importUsers'])
            ->name('hr.users.import');
        Route::post('/attendances/import', [ImportExportController::class, 'importAttendances'])
            ->name('hr.attendances.import');

        Route::get('/users/export', [ImportExportController::class, 'exportUsers'])
            ->name('hr.users.export');
        Route::get('/attendances/export', [ImportExportController::class, 'exportAttendances'])
            ->name('hr.attendances.export');
    });

    // Payroll Group
    Route::group(['prefix' => 'payroll', 'as' => 'payroll.', 'middleware' => ['payroll']], function () {
        Route::get('/', \App\Livewire\Payroll\PayrollDashboardComponent::class)->name('dashboard');
        Route::get('/employee-salaries', \App\Livewire\Payroll\EmployeeSalaryComponent::class)->name('employee-salaries');
        Route::get('/payment-methods', \App\Livewire\Payroll\PaymentMethodComponent::class)->name('payment-methods');
        Route::get('/history', \App\Livewire\Payroll\PayrollHistoryComponent::class)->name('history');
        Route::get('/savings', \App\Livewire\Payroll\SavingComponent::class)->name('savings');
        Route::get('/saving-transactions', \App\Livewire\Payroll\SavingTransactionComponent::class)->name('saving-transactions');
        Route::get('/loans', \App\Livewire\Payroll\LoanComponent::class)->name('loans');

        // Import/Export
        Route::get('/import-export/employee-salaries', [\App\Http\Controllers\Payroll\ImportExportController::class, 'employeeSalaries'])->name('import-export.employee-salaries');
        Route::get('/import-export/payment-methods', [\App\Http\Controllers\Payroll\ImportExportController::class, 'paymentMethods'])->name('import-export.payment-methods');
        Route::get('/import-export/savings', [\App\Http\Controllers\Payroll\ImportExportController::class, 'savings'])->name('import-export.savings');
    });

    // User Group (for Payslips)
    Route::get('/user/payslips', \App\Livewire\User\PayslipComponent::class)->name('user.payslips');

});

Livewire::setUpdateRoute(function ($handle) {
    return Route::post(Helpers::getNonRootBaseUrlPath() . '/livewire/update', $handle);
});

Livewire::setScriptRoute(function ($handle) {
    $path = config('app.debug') ? '/livewire/livewire.js' : '/livewire/livewire.min.js';
    return Route::get(url($path), $handle);
});

