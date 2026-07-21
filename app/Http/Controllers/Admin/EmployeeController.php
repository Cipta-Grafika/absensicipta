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

    public function search(\Illuminate\Http\Request $request)
    {
        $search = $request->query('q');
        
        $query = User::where('group', 'user');

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
