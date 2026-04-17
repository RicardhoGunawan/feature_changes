<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminViewController extends Controller
{
    private function checkAccess(string $permission)
    {
        // Require login (simple check since session might not be used)
        // Note: This relies on the same logic used in AuthController for consistency
        $user = auth()->user();
        if (!$user)
            return true; // Let the frontend handle if no session in web
        if ($user->role === 'administrator' || $user->role === 'admin')
            return true;

        $roleNames = $user->roles->pluck('name')->toArray();
        $hasPerm = \Illuminate\Support\Facades\DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->whereIn('roles.name', $roleNames)
            ->where('permissions.name', $permission)
            ->exists();

        if (!$hasPerm) {
            abort(404);
        }
        return true;
    }

    public function dashboard()
    {
        $this->checkAccess('view_dashboard');
        return view('admin.index');
    }

    public function employees()
    {
        $this->checkAccess('view_employee');
        return view('admin.employees');
    }

    public function attendance()
    {
        $this->checkAccess('view_attendance');
        return view('admin.attendance');
    }

    public function leave()
    {
        $this->checkAccess('view_leave');
        return view('admin.leave');
    }

    public function locations()
    {
        $this->checkAccess('manage_location');
        return view('admin.locations');
    }

    public function schedules()
    {
        $this->checkAccess('manage_schedule');
        return view('admin.schedules');
    }

    public function holidays()
    {
        $this->checkAccess('manage_schedule');
        return view('admin.holidays');
    }

    public function roles()
    {
        $this->checkAccess('view_roles');
        return view('admin.roles');
    }

    public function departments()
    {
        $this->checkAccess('view_roles');
        return view('admin.departments');
    }

    public function positions()
    {
        $this->checkAccess('view_roles');
        return view('admin.positions');
    }

    public function leavePolicies()
    {
        $this->checkAccess('manage_leave');
        return view('admin.leave_policies');
    }

    public function approvalWorkflows()
    {
        $this->checkAccess('manage_leave');
        return view('admin.approval_workflows');
    }

    public function auditLogs()
    {
        $this->checkAccess('view_logs');
        return view('admin.audit_logs');
    }

    public function login()
    {
        return view('admin.login');
    }
}
