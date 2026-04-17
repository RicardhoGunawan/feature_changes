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
                'employee_code' => $e->employee_code,
                'email' => $e->email,
                'phone' => $e->phone,
                'position_name' => $e->jobPosition?->name ?? '-',
                'position_id' => $e->position_id,
                'department_name' => $e->department?->name ?? '-',
                'department_id' => $e->department_id,
                'role' => $e->role,
                'is_active' => $e->is_active,
                'shift_id' => $e->shift_id,
                'shift_name' => $e->shift?->name,
                'work_hours' => $e->shift ? substr($e->shift->start_time, 0, 5) . ' - ' . substr($e->shift->end_time, 0, 5) : '-',
                'location_id' => $e->location_id,
                'profile_photo' => $e->profile_photo,
                'join_date' => $e->join_date ? $e->join_date->format('Y-m-d') : null,
                'employee_type' => $e->employee_type,
                'remaining_leave' => $e->remaining_leave,
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
            'join_date' => 'nullable|date',
            'employee_type' => 'nullable|in:permanent,contract,probation',
            'annual_leave_quota' => 'nullable|integer',
        ]);

        if (isset($validated['position_id'])) {
            $pos = \App\Models\Position::find($validated['position_id']);
            if ($pos) {
                $validated['department_id'] = $pos->department_id;
            }
        }

        // Map initial quota to remaining_leave if provided
        if (isset($validated['annual_leave_quota'])) {
            $validated['remaining_leave'] = $validated['annual_leave_quota'];
            unset($validated['annual_leave_quota']);
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
            $validated['employee_code'] = $request->get('employee_code') ?: 'EMP-' . strtoupper(Str::random(6));
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

    /**
     * Adjust employee leave quota manually.
     */
    public function adjustQuota(Request $request, User $user)
    {
        $this->validatePermission($request, 'manage_employee');
        
        $data = $request->validate([
            'type' => 'required|in:annual,sick',
            'amount' => 'required|integer', // Can be negative
            'reason' => 'required|string',
        ]);

        $oldValue = $data['type'] === 'annual' ? $user->remaining_leave : $user->sick_leave_remaining;
        
        if ($data['type'] === 'annual') {
            $user->increment('remaining_leave', $data['amount']);
        } else {
            $user->increment('sick_leave_remaining', $data['amount']);
        }

        $newValue = $data['type'] === 'annual' ? $user->remaining_leave : $user->sick_leave_remaining;

        // Audit Log (Requirement #10)
        \App\Services\AuditService::log(
            'quota_adjustment', 
            $user, 
            ['remaining_leave' => $oldValue], 
            ['remaining_leave' => $newValue, 'reason' => $data['reason']], 
            $user->id
        );

        return response()->json(['success' => true, 'message' => 'Kuota berhasil disesuaikan.']);
    }
}
