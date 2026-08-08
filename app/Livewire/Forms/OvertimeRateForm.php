<?php

namespace App\Livewire\Forms;

use App\Models\OvertimeRate;
use Illuminate\Support\Facades\Auth;
use Livewire\Form;

class OvertimeRateForm extends Form
{
    public ?OvertimeRate $rate = null;

    public $name = '';
    public $min_hours = 0;
    public $max_hours = 24;
    public $rate_amount = 0;
    public $rate_type = 'per_hour';
    public $division_id = null;
    public $employee_type = 'all';
    public $meal_allowance = 0;

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'min_hours' => ['required', 'numeric', 'min:0'],
            'max_hours' => ['required', 'numeric', 'gte:min_hours'],
            'rate_amount' => ['required', 'numeric', 'min:0'],
            'rate_type' => ['required', 'in:per_hour,flat_package'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'employee_type' => ['nullable', 'string'],
            'meal_allowance' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function setRate(OvertimeRate $rate)
    {
        $this->rate = $rate;
        $this->name = $rate->name;
        $this->min_hours = $rate->min_hours;
        $this->max_hours = $rate->max_hours;
        $this->rate_amount = $rate->rate_amount;
        $this->rate_type = $rate->rate_type;
        $this->division_id = $rate->division_id;
        $this->employee_type = $rate->employee_type ?? 'all';
        $this->meal_allowance = $rate->meal_allowance ?? 0;
        return $this;
    }

    public function store()
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            return abort(403);
        }

        if (!$user->isSuperadmin) {
            $this->division_id = $user->division_id;
        }

        $this->validate();
        OvertimeRate::create($this->all());
        $this->reset();
    }

    public function update()
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            return abort(403);
        }

        if (!$user->isSuperadmin && $this->rate->division_id !== $user->division_id) {
            return abort(403);
        }

        if (!$user->isSuperadmin) {
            $this->division_id = $user->division_id;
        }

        $this->validate();
        $this->rate->update($this->all());
        $this->reset();
    }

    public function delete()
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            return abort(403);
        }

        if (!$user->isSuperadmin && $this->rate->division_id !== $user->division_id) {
            return abort(403);
        }

        $this->rate->delete();
        $this->reset();
    }
}
