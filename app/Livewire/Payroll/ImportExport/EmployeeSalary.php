<?php

namespace App\Livewire\Payroll\ImportExport;

use App\Exports\EmployeeSalariesExport;
use App\Imports\EmployeeSalariesImport;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\EmployeeSalary as EmployeeSalaryModel;
use Livewire\WithFileUploads;

class EmployeeSalary extends Component
{
    use InteractsWithBanner, WithFileUploads;

    public bool $previewing = false;
    public ?string $mode = null;
    public $file = null;

    protected $rules = [
        'file' => 'required|mimes:csv,xls,xlsx,ods'
    ];

    public function preview()
    {
        $this->previewing = !$this->previewing;
        $this->mode = $this->previewing ? 'export' : null;
    }

    public function render()
    {
        $salaries = null;
        if ($this->file) {
            $this->mode = 'import';
            $this->previewing = true;
            $import = new EmployeeSalariesImport(save: false);
            $salaries = Excel::toCollection($import, $this->file)
                ->first()
                ->map(function (\Illuminate\Support\Collection $v) use ($import) {
                    return $import->model($v->toArray());
                })->filter();
        } else if ($this->previewing && $this->mode == 'export') {
            $salaries = EmployeeSalaryModel::with('employee')->get();
        } else {
            $this->previewing = false;
            $this->mode = null;
        }
        
        return view('livewire.payroll.import-export.employee-salary', [
            'salaries' => $salaries
        ]);
    }

    public function import()
    {
        if (Auth::user()->isNotAdmin && !Auth::user()->isPayroll) {
            abort(403);
        }
        try {
            $this->validate();

            Excel::import(new EmployeeSalariesImport, $this->file);

            $this->banner(__('Success'));
            $this->reset();
        } catch (\Throwable $th) {
            $this->dangerBanner($th->getMessage());
        }
    }

    public function export()
    {
        if (Auth::user()->isNotAdmin && !Auth::user()->isPayroll) {
            abort(403);
        }
        return Excel::download(
            new EmployeeSalariesExport(),
            'employee_salaries.xlsx'
        );
    }
}
