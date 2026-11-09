<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.employees.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    public function updateWarningLetter(\Illuminate\Http\Request $request, User $employee)
    {
        if (!auth()->user()?->isSuperadmin) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: Hanya Superadmin yang memiliki wewenang untuk mengubah Surat Peringatan.',
            ], 403);
        }

        $validated = $request->validate([
            'field' => 'required|string|in:splt,sp1,sp2,sp3,cutoff',
            'value' => 'nullable|boolean',
        ]);

        $field = $validated['field'];
        $value = array_key_exists('value', $validated) ? (bool)$validated['value'] : !$employee->$field;

        $employee->$field = $value;
        $employee->save();

        $labels = [
            'splt' => 'SPLT',
            'sp1' => 'SP 1',
            'sp2' => 'SP 2',
            'sp3' => 'SP 3',
            'cutoff' => 'CUT OFF',
        ];

        return response()->json([
            'success' => true,
            'message' => "Status {$labels[$field]} untuk {$employee->name} berhasil diperbarui.",
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'field' => $field,
                'value' => (bool)$employee->$field,
                'splt' => (bool)$employee->splt,
                'sp1' => (bool)$employee->sp1,
                'sp2' => (bool)$employee->sp2,
                'sp3' => (bool)$employee->sp3,
                'cutoff' => (bool)$employee->cutoff,
            ],
        ]);
    }

    public function search(\Illuminate\Http\Request $request)
    {
        $search = $request->query('q');
        
        $query = User::where('group', 'user')->whereIn('status', ['active', 'suspend']);

        if ($search) {
            if (strlen($search) < 2) {
                return response()->json([]);
            }
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name')->limit(20)->get(['id', 'name', 'nip']);

        return response()->json($employees);
    }
}
