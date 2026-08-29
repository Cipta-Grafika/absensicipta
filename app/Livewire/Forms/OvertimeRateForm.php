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
    public $meal_min_start_time = null;
    public $meal_max_start_time = null;
    public $meal_min_duration = null;
    public $meal_condition_type = 'start_time_gte';

    /**
     * Normalize numeric fields before validation — empty strings from Livewire inputs
     * are NOT automatically converted to null/0, so we sanitize them explicitly.
     */
    protected function sanitizeNumericFields(): void
    {
        $this->meal_allowance = is_numeric($this->meal_allowance) && $this->meal_allowance !== '' ? (float) $this->meal_allowance : 0;
        $this->rate_amount    = is_numeric($this->rate_amount) && $this->rate_amount !== ''    ? (float) $this->rate_amount    : 0;
        $this->min_hours      = is_numeric($this->min_hours) && $this->min_hours !== ''        ? (float) $this->min_hours      : 0;
        $this->max_hours      = is_numeric($this->max_hours) && $this->max_hours !== ''        ? (float) $this->max_hours      : 24;

        $this->meal_min_duration   = (is_numeric($this->meal_min_duration) && $this->meal_min_duration !== '') ? (float) $this->meal_min_duration : null;
        $this->meal_min_start_time = !empty($this->meal_min_start_time) ? trim($this->meal_min_start_time) : null;
        $this->meal_max_start_time = !empty($this->meal_max_start_time) ? trim($this->meal_max_start_time) : null;
        $this->meal_condition_type = !empty($this->meal_condition_type) ? $this->meal_condition_type : 'start_time_gte';
    }

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
            'meal_min_start_time' => ['nullable', 'string'],
            'meal_max_start_time' => ['nullable', 'string'],
            'meal_min_duration' => ['nullable', 'numeric', 'min:0'],
            'meal_condition_type' => ['nullable', 'string', 'in:start_time_gte,crosses_time,always'],
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
        $this->meal_min_start_time = $rate->meal_min_start_time ? substr($rate->meal_min_start_time, 0, 5) : null;
        $this->meal_max_start_time = $rate->meal_max_start_time ? substr($rate->meal_max_start_time, 0, 5) : null;
        $this->meal_min_duration = $rate->meal_min_duration;
        $this->meal_condition_type = $rate->meal_condition_type ?? 'start_time_gte';
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

        $this->sanitizeNumericFields();
        $this->validate();
        OvertimeRate::create([
            'name'                => $this->name,
            'min_hours'           => $this->min_hours,
            'max_hours'           => $this->max_hours,
            'rate_amount'         => $this->rate_amount,
            'rate_type'           => $this->rate_type,
            'division_id'         => $this->division_id,
            'employee_type'       => $this->employee_type,
            'meal_allowance'      => $this->meal_allowance,
            'meal_min_start_time' => $this->meal_min_start_time,
            'meal_max_start_time' => $this->meal_max_start_time,
            'meal_min_duration'   => $this->meal_min_duration,
            'meal_condition_type' => $this->meal_condition_type,
        ]);
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

        $this->sanitizeNumericFields();
        $this->validate();
        $this->rate->update([
            'name'                => $this->name,
            'min_hours'           => $this->min_hours,
            'max_hours'           => $this->max_hours,
            'rate_amount'         => $this->rate_amount,
            'rate_type'           => $this->rate_type,
            'division_id'         => $this->division_id,
            'employee_type'       => $this->employee_type,
            'meal_allowance'      => $this->meal_allowance,
            'meal_min_start_time' => $this->meal_min_start_time,
            'meal_max_start_time' => $this->meal_max_start_time,
            'meal_min_duration'   => $this->meal_min_duration,
            'meal_condition_type' => $this->meal_condition_type,
        ]);
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
