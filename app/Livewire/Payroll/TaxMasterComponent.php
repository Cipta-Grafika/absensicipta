<?php

namespace App\Livewire\Payroll;

use App\Models\TaxMaster;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class TaxMasterComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $search = '';
    public $categoryFilter = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;

    // Form fields
    public $tax_id = null;
    public $category = 'TER A';
    public $code = '';
    public $name = '';
    public $min_gross_income = 0;
    public $max_gross_income = null;
    public $rate_percentage = 0.0;
    public $description = '';

    // Selected item for delete
    public $taxToDelete = null;

    protected function rules()
    {
        return [
            'category' => 'required|string|max:50',
            'code' => 'required|string|max:50|unique:tax_masters,code,' . $this->tax_id,
            'name' => 'required|string|max:255',
            'min_gross_income' => 'required|numeric|min:0',
            'max_gross_income' => 'nullable|numeric|gte:min_gross_income',
            'rate_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:500',
        ];
    }

    protected function messages()
    {
        return [
            'category.required' => 'Kategori pajak wajib diisi.',
            'code.required' => 'Kode tarif wajib diisi.',
            'code.unique' => 'Kode tarif ini sudah digunakan.',
            'name.required' => 'Nama / label lapisan tarif wajib diisi.',
            'min_gross_income.required' => 'Penghasilan bruto minimal wajib diisi.',
            'max_gross_income.gte' => 'Penghasilan bruto maksimal harus lebih besar atau sama dengan minimal.',
            'rate_percentage.required' => 'Persentase tarif wajib diisi.',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['tax_id', 'code', 'name', 'min_gross_income', 'max_gross_income', 'rate_percentage', 'description']);
        $this->category = 'TER A';

        // Auto suggest next code
        $lastTax = TaxMaster::where('category', 'TER A')->orderBy('code', 'desc')->first();
        if ($lastTax && preg_match('/TER-A-(\d+)/', $lastTax->code, $m)) {
            $nextSeq = sprintf('%02d', ((int) $m[1]) + 1);
            $this->code = "TER-A-{$nextSeq}";
        } else {
            $this->code = 'TER-A-01';
        }

        $this->isModalOpen = true;
    }

    public function edit(string $id)
    {
        $this->resetValidation();
        $tax = TaxMaster::findOrFail($id);

        $this->tax_id = $tax->id;
        $this->category = $tax->category;
        $this->code = $tax->code;
        $this->name = $tax->name;
        $this->min_gross_income = $tax->min_gross_income;
        $this->max_gross_income = $tax->max_gross_income;
        $this->rate_percentage = $tax->rate_percentage;
        $this->description = $tax->description ?? '';

        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    public function autoGenerateName()
    {
        if ($this->max_gross_income === '' || $this->max_gross_income === null) {
            $minFormatted = number_format((float) $this->min_gross_income, 0, ',', '.');
            $this->name = "{$this->category} - Di atas Rp {$minFormatted} ({$this->rate_percentage}%)";
        } elseif ((float) $this->min_gross_income <= 0) {
            $maxFormatted = number_format((float) $this->max_gross_income, 0, ',', '.');
            $this->name = "{$this->category} - s.d Rp {$maxFormatted} ({$this->rate_percentage}%)";
        } else {
            $minFormatted = number_format((float) $this->min_gross_income, 0, ',', '.');
            $maxFormatted = number_format((float) $this->max_gross_income, 0, ',', '.');
            $this->name = "{$this->category} - Rp {$minFormatted} s.d Rp {$maxFormatted} ({$this->rate_percentage}%)";
        }
    }

    public function save()
    {
        $this->validate();

        TaxMaster::updateOrCreate(
            ['id' => $this->tax_id],
            [
                'category' => $this->category,
                'code' => $this->code,
                'name' => $this->name,
                'min_gross_income' => (float) $this->min_gross_income,
                'max_gross_income' => ($this->max_gross_income !== '' && $this->max_gross_income !== null) ? (float) $this->max_gross_income : null,
                'rate_percentage' => (float) $this->rate_percentage,
                'description' => $this->description ?: null,
            ]
        );

        $this->closeModal();
        $this->banner('Data Master Pajak PPh 21 berhasil disimpan.');
    }

    public function confirmDelete(string $id)
    {
        $tax = TaxMaster::withCount('employeeSalaries')->findOrFail($id);
        $this->taxToDelete = $tax;
        $this->isDeleteModalOpen = true;
    }

    public function closeDeleteModal()
    {
        $this->isDeleteModalOpen = false;
        $this->taxToDelete = null;
    }

    public function delete()
    {
        if ($this->taxToDelete) {
            $tax = TaxMaster::findOrFail($this->taxToDelete->id);
            $tax->delete();
            $this->closeDeleteModal();
            $this->banner('Lapisan tarif pajak berhasil dihapus.');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);

        $query = TaxMaster::withCount('employeeSalaries')
            ->when($this->categoryFilter, function ($q) {
                $q->where('category', $this->categoryFilter);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('name', 'like', '%' . $this->search . '%')
                        ->orWhere('category', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('rate_percentage', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('category', 'asc')
            ->orderBy('min_gross_income', 'asc');

        $taxes = $query->paginate(15);
        $categories = TaxMaster::select('category')->distinct()->pluck('category');

        $totalTaxes = TaxMaster::count();
        $minRate = TaxMaster::min('rate_percentage') ?? 0;
        $maxRate = TaxMaster::max('rate_percentage') ?? 0;

        return view('livewire.payroll.tax-master-component', [
            'taxes' => $taxes,
            'categories' => $categories,
            'totalTaxes' => $totalTaxes,
            'minRate' => $minRate,
            'maxRate' => $maxRate,
        ])->layout('layouts.app');
    }
}
