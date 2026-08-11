<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserForm extends Form
{
    public ?User $user = null;

    public $name = '';
    public $nip = '';
    public $email = '';
    public $phone = '';
    public $password = null;
    public $gender = null;
    public $city = '';
    public $address = '';
    public $group = 'user';
    public $type = 'full-time';
    public $birth_date = null;
    public $birth_place = '';
    public $status = 'active';
    public $division_id = null;
    public $education_id = null;
    public $job_title_id = null;
    public $photo = null;
    public bool $count_wfo = false;
    public array $off_days = [];

    public function rules()
    {
        $requiredOrNullable = $this->group === 'user' ? 'required' : 'nullable';
        $allowedGroups = Auth::user()?->isSuperadmin ? User::$groups : ['user'];

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($this->user)
            ],
            'nip' => [
                $requiredOrNullable,
                'string',
                'max:255',
                Rule::unique('users')->ignore($this->user)
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user)
            ],
            'phone' => ['required',  'string', 'min:5', 'max:255'],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'gender' => [$requiredOrNullable, 'in:male,female'],
            'city' => [$requiredOrNullable, 'string', 'max:255'],
            'address' => [$requiredOrNullable, 'string', 'max:255'],
            'group' => ['required', 'string', 'max:255', Rule::in($allowedGroups)],
            'type' => ['required', 'string', Rule::in(User::$types)],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive,resign,suspend,fired'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'education_id' => ['nullable', 'exists:educations,id'],
            'job_title_id' => ['nullable', 'exists:job_titles,id'],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'count_wfo' => ['boolean'],
            'off_days' => ['nullable', 'array'],
            'off_days.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
        ];
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->nip = $user->nip;
        $this->email = $user->email;
        $this->phone = $user->phone;
        if ($this->isAllowed()) {
            $this->password = $user->raw_password;
        }
        $this->gender = $user->gender;
        $this->city = $user->city;
        $this->address = $user->address;
        $this->group = $user->group;
        $this->type = $user->type ?? 'full-time';
        $this->birth_date = $user->birth_date
            ? \Illuminate\Support\Carbon::parse($user->birth_date)->format('Y-m-d')
            : null;
        $this->birth_place = $user->birth_place;
        $this->status = $user->status ?? 'active';
        $this->division_id = $user->division_id;
        $this->education_id = $user->education_id;
        $this->job_title_id = $user->job_title_id;
        $this->count_wfo = (bool) $user->count_wfo;
        $this->off_days = $user->off_days ?? [];
        return $this;
    }

    public function store()
    {
        if (!$this->isAllowed()) {
            return abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menambahkan pengguna atau peran ini.');
        }
        $this->validate();

        $data = $this->all();
        if ($this->group !== 'user') {
            $data['gender'] = $data['gender'] ?? 'male';
            $data['city'] = $data['city'] ?: '-';
            $data['address'] = $data['address'] ?: '-';
        }

        if (Auth::user()?->group === 'admin') {
            $data['division_id'] = Auth::user()->division_id;
        }

        if (!Auth::user()?->isSuperadmin) {
            $data['group'] = 'user';
            unset($data['count_wfo']);
        }

        /** @var User $user */
        $user = User::create([
            ...$data,
            'password' => Hash::make($this->password ?? 'password'),
            'raw_password' => $this->password ?? 'password',
        ]);
        if (isset($this->photo)) $user->updateProfilePhoto($this->photo);
        $this->reset();
    }

    public function update()
    {
        if (!$this->isAllowed()) {
            return abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memperbarui pengguna atau peran ini.');
        }
        $this->validate();

        $data = $this->all();
        if ($this->group !== 'user') {
            $data['gender'] = $data['gender'] ?? 'male';
            $data['city'] = $data['city'] ?: '-';
            $data['address'] = $data['address'] ?: '-';
        }

        if (Auth::user()?->group === 'admin') {
            $data['division_id'] = Auth::user()->division_id;
        }

        if (!Auth::user()?->isSuperadmin) {
            $data['group'] = 'user';
            unset($data['count_wfo']);
        }

        $this->user->update([
            ...$data,
            'password' => $this->password ? Hash::make($this->password) : $this->user?->password,
            'raw_password' => $this->password ?? $this->user?->raw_password,
        ]);
        if (isset($this->photo)) $this->user->updateProfilePhoto($this->photo);
        $this->reset();
    }

    public function deleteProfilePhoto()
    {
        if (!$this->isAllowed()) {
            return abort(403);
        }
        return $this->user->deleteProfilePhoto();
    }

    public function delete()
    {
        if (!$this->isAllowed()) {
            return abort(403);
        }
        $this->user->delete();
        $this->deleteProfilePhoto();
        $this->reset();
    }

    private function isAllowed()
    {
        $authUser = Auth::user();
        if (!$authUser) return false;

        // Superadmin has full access to manage all groups (user, admin, payroll, superadmin)
        if ($authUser->isSuperadmin) return true;

        // Division Admin can ONLY manage regular users in their own division
        if ($authUser->group === 'admin') {
            // Non-Superadmin CANNOT assign or set group to admin, payroll, or superadmin
            if ($this->group !== 'user') {
                return false;
            }

            // Non-Superadmin CANNOT edit existing accounts that have non-user role (admin, payroll, superadmin)
            if ($this->user && $this->user->group !== 'user') {
                return false;
            }

            // Non-Superadmin CANNOT edit users from another division
            if ($this->user && $this->user->division_id !== $authUser->division_id) {
                return false;
            }

            return true;
        }

        return false;
    }
}
