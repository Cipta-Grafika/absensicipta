<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class UsersExport implements FromView
{
    /**
     * @param array<string> $groups
     */
    public function __construct(private array $groups = ['user'])
    {
    }

    public function view(): View
    {
        $query = User::whereIn('group', $this->groups);
        
        if (auth()->user()->group === 'admin') {
            $query->where('division_id', auth()->user()->division_id)
                  ->where('group', '!=', 'superadmin');
        }

        return view('admin.import-export.export-users', [
            'users' => $query->get(),
        ]);
    }
}
