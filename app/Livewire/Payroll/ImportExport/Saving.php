<?php

namespace App\Livewire\Payroll\ImportExport;

use App\Exports\SavingsExport;
use App\Imports\SavingsImport;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Saving as SavingModel;
use Livewire\WithFileUploads;

class Saving extends Component
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
        $savings = null;
        if ($this->file) {
            $this->mode = 'import';
            $this->previewing = true;
            $import = new SavingsImport(save: false);
            $savings = Excel::toCollection($import, $this->file)
                ->first()
                ->map(function (\Illuminate\Support\Collection $v) use ($import) {
                    return $import->model($v->toArray());
                })->filter();
        } else if ($this->previewing && $this->mode == 'export') {
            $savings = SavingModel::all();
        } else {
            $this->previewing = false;
            $this->mode = null;
        }
        
        return view('livewire.payroll.import-export.saving', [
            'savings' => $savings
        ]);
    }

    public function import()
    {
        if (Auth::user()->isNotAdmin && !Auth::user()->isPayroll && !Auth::user()->isSyirkah) {
            abort(403);
        }
        try {
            $this->validate();

            Excel::import(new SavingsImport, $this->file);

            $this->banner(__('Success'));
            $this->reset();
        } catch (\Throwable $th) {
            $this->dangerBanner($th->getMessage());
        }
    }

    public function export()
    {
        if (Auth::user()->isNotAdmin && !Auth::user()->isPayroll && !Auth::user()->isSyirkah) {
            abort(403);
        }
        return Excel::download(
            new SavingsExport(),
            'savings.xlsx'
        );
    }
}
