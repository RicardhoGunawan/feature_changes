<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends BaseAdminController
{
    public function index(Request $request)
    {
        $this->validatePermission($request, 'view_employee');
        $user = $request->user();
        $mode = $request->get('mode');
        
        $query = User::with(['shift', 'location', 'supervisor', 'jobPosition', 'department']);

        if ($user->role === 'spv' && $mode !== 'supervisors') {
            $query->where('supervisor_id', $user->id);
        }

        if ($mode === 'supervisors') {
            $employees = $query->where('role', 'administrator')->get();
        } else {
            $employees = $query->get();
        }

        $mapped = $employees->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'username' => $e->username,
                'employee_id' => $e->employee_code,
                'email' => $e->email,
                'phone' => $e->phone,
                'position' => $e->jobPosition?->name ?? 'Karyawan',
                'position_id' => $e->position_id,
                'department' => $e->department?->name ?? '-',
                'department_id' => $e->department_id,
                'role' => $e->role,
                'is_active' => $e->is_active,
                'shift_id' => $e->shift_id,
                'shift_name' => $e->shift?->name,
                'work_hours' => $e->shift ? substr($e->shift->start_time, 0, 5) . ' - ' . substr($e->shift->end_time, 0, 5) : '-',
                'location_id' => $e->location_id,
                'profile_photo' => $e->profile_photo,
            ];
        });

        return response()->json(['success' => true, 'data' => $mapped]);
    }

    public function store(Request $request)
    {
        $this->validatePermission($request, 'manage_employee');
        $id = $request->get('id');
        
        $validated = $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,username,' . $id,
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'position_id' => 'nullable|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|in:administrator,employee',
            'shift_id' => 'nullable|exists:shifts,id',
            'location_id' => 'nullable|exists:office_locations,id',
            'password' => $id ? 'nullable|min:6' : 'required|min:6',
        ]);

        if (isset($validated['position_id'])) {
            $pos = \App\Models\Position::find($validated['position_id']);
            if ($pos) {
                $validated['department_id'] = $pos->department_id;
            }
        }

        if ($id) {
            $user = User::findOrFail($id);
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }
            $user->update($validated);
        } else {
            $validated['password'] = Hash::make($validated['password']);
            $validated['employee_code'] = $request->get('employee_id') ?: 'EMP-' . strtoupper(Str::random(6));
            $user = User::create($validated);
        }

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function destroy(Request $request)
    {
        $this->validatePermission($request, 'manage_employee');
        $id = $request->get('id');
        User::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Karyawan berhasil dihapus']);
    }

    public function updateStatus(Request $request, User $user)
    {
        $this->validatePermission($request, 'manage_employee');
        $request->validate(['is_active' => 'required|boolean']);
        $user->update(['is_active' => $request->is_active]);
        return response()->json(['success' => true, 'message' => 'Status user berhasil diperbarui']);
    }
}
