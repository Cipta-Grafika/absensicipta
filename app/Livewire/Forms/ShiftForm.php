<?php

namespace App\Livewire\Forms;

use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ShiftForm extends Form
{
    public ?Shift $shift = null;

    public $name = '';
    public $start_time = null;
    public $end_time = null;
    public $division_id = null;

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shifts')->ignore($this->shift)
            ],
            'start_time' => ['required'],
            'end_time' => ['nullable'],
            'division_id' => ['nullable', 'exists:divisions,id'],
        ];
    }

    public function setShift(Shift $shift)
    {
        $this->shift = $shift;
        $this->name = $shift->name;
        $this->start_time = $shift->start_time;
        $this->end_time = $shift->end_time;
        $this->division_id = $shift->division_id;
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
        Shift::create($this->all());
        $this->reset();
    }

    public function update()
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            return abort(403);
        }

        if (!$user->isSuperadmin && $this->shift->division_id !== $user->division_id) {
            return abort(403);
        }

        if (!$user->isSuperadmin) {
            $this->division_id = $user->division_id;
        }

        $this->validate();
        $this->shift->update($this->all());
        $this->reset();
    }

    public function delete()
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            return abort(403);
        }

        if (!$user->isSuperadmin && $this->shift->division_id !== $user->division_id) {
            return abort(403);
        }

        $this->shift->delete();
        $this->reset();
    }
}
