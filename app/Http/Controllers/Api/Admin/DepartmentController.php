<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends BaseAdminController
{
    public function index(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $departments = Department::withCount('users')->orderBy('name', 'asc')->get();
        return response()->json(['success' => true, 'data' => $departments]);
    }

    public function store(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $id = $request->get('id');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:departments,code,' . ($id ?? 'NULL'),
            'description' => 'nullable|string'
        ]);

        if ($id) {
            $department = Department::findOrFail($id);
            $department->update($validated);
        } else {
            $department = Department::create($validated);
        }

        return response()->json(['success' => true, 'data' => $department]);
    }

    public function destroy(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $id = $request->get('id');
        $department = Department::findOrFail($id);

        if ($department->users()->exists()) {
            return response()->json([
                'success' => false, 
                'message' => 'Departemen tidak bisa dihapus karena masih memiliki karyawan aktif.'
            ], 422);
        }

        $department->delete();
        return response()->json(['success' => true, 'message' => 'Departemen berhasil dihapus']);
    }
}
