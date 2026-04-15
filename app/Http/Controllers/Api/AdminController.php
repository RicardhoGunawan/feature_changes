<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Shift;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\Holiday;
use App\Models\OvertimeRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->validatePermission($request, 'view_dashboard');
        $user = $request->user();
        $isSpv = $user->role === 'spv';
        $uid = $user->id;

        $today = Carbon::today()->toDateString();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        // Base Query for Employees
        $empQuery = User::where('role', 'employee')->where('is_active', true);
        if ($isSpv) {
            $empQuery->where('supervisor_id', $uid);
        }
        $totalEmployees = $empQuery->count();

        // 2. Today Stats
        $attQuery = Attendance::where('date', $today);
        if ($isSpv) {
            $attQuery->whereHas('user', function ($q) use ($uid) {
                $q->where('supervisor_id', $uid);
            });
        }
        $todayAttendance = $attQuery->get();
        $checkedInCount = $todayAttendance->count();
        $lateCount = $todayAttendance->where('status', 'late')->count();
        $presentCount = $todayAttendance->where('status', 'present')->count();

        $attendanceRate = $totalEmployees > 0
            ? round(($checkedInCount / $totalEmployees) * 100) . '%'
            : '0%';

        // 3. Pending Requests
        $leaveQuery = LeaveRequest::where('status', 'pending_spv');
        if ($isSpv) {
            $leaveQuery->where('supervisor_id', $uid);
        }
        $pendingLeaves = $leaveQuery->count();

        // 4. Recent Activities
        $actQuery = Attendance::with('user')->where('date', $today);
        if ($isSpv) {
            $actQuery->whereHas('user', function ($q) use ($uid) {
                $q->where('supervisor_id', $uid);
            });
        }
        $recentActivities = $actQuery->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($a) {
                return [
                    'employee_name' => $a->user->name,
                    'position' => $a->user->position ?? 'Karyawan',
                    'time' => $a->check_out_time ? $a->check_out_time->addHours(7)->format('H:i') : $a->check_in_time->addHours(7)->format('H:i'),
                    'action' => $a->check_out_time ? 'Check Out' : 'Check In',
                    'status' => $a->status,
                ];
            });

        // 5. Chart Data (Last 7 Days)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $dAttQuery = Attendance::where('date', $date);
            if ($isSpv) {
                $dAttQuery->whereHas('user', function ($q) use ($uid) {
                    $q->where('supervisor_id', $uid);
                });
            }
            $dAtt = $dAttQuery->get();
            $chartData[] = [
                'date' => $date,
                'on_time' => $dAtt->where('status', 'present')->count(),
                'late' => $dAtt->where('status', 'late')->count(),
            ];
        }

        // 6. Monthly Stats
        $monthAttQuery = Attendance::whereYear('date', $thisYear)->whereMonth('date', $thisMonth);
        if ($isSpv) {
            $monthAttQuery->whereHas('user', function ($q) use ($uid) {
                $q->where('supervisor_id', $uid);
            });
        }
        $monthAtt = $monthAttQuery->get();
        $monthlyStats = [
            'total_attendance' => $monthAtt->count(),
            'on_time_count' => $monthAtt->where('status', 'present')->count(),
            'late_count' => $monthAtt->where('status', 'late')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'employees' => [
                    'total_employees' => $totalEmployees,
                ],
                'today' => [
                    'checked_in' => $checkedInCount,
                    'late' => $lateCount,
                    'present' => $presentCount,
                    'attendance_rate' => $attendanceRate,
                ],
                'pending_leave' => $pendingLeaves,
                'recent_activities' => $recentActivities,
                'chart_data' => $chartData,
                'this_month' => $monthlyStats,
            ]
        ]);
    }

    public function employees(Request $request)
    {
        // Allow if user has either view_employee or view_attendance (for filters)
        $user = $request->user();
        if ($user->role !== 'admin') {
            $roleNames = $user->roles->pluck('name')->toArray();
            $canViewEmp = DB::table('permissions')->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')->whereIn('roles.name', $roleNames)->where('permissions.name', 'view_employee')->exists();
            $canViewAtt = DB::table('permissions')->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')->whereIn('roles.name', $roleNames)->where('permissions.name', 'view_attendance')->exists();

            if (!$canViewEmp && !$canViewAtt) {
                abort(response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin view_employee'], 403));
            }
        }

        $mode = $request->get('mode');
        $query = User::with(['shift', 'location', 'supervisor']);

        if ($user->role === 'spv' && $mode !== 'supervisors') {
            $query->where('supervisor_id', $user->id);
        }

        if ($mode === 'supervisors') {
            $employees = $query->whereIn('role', ['admin', 'spv', 'hr'])->get();
        } else {
            $employees = $query->get();
        }

        $mapped = $employees->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'username' => $e->username,
                'employee_id' => $e->employee_code, // Alias untuk dashboard
                'email' => $e->email,
                'phone' => $e->phone,
                'position' => $e->position,
                'position_id' => $e->position_id,
                'department' => $e->department,
                'role' => $e->role,
                'is_active' => $e->is_active,
                'supervisor_id' => $e->supervisor_id,
                'supervisor_name' => $e->supervisor?->name, // Ambil nama langsung
                'shift_id' => $e->shift_id,
                'shift_name' => $e->shift?->name, // Ambil nama langsung
                'location_id' => $e->location_id,
                'profile_photo' => $e->profile_photo,
            ];
        });

        return response()->json(['success' => true, 'data' => $mapped]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $this->validatePermission($request, 'manage_employee');
        $request->validate(['is_active' => 'required|boolean']);
        $user->update(['is_active' => $request->is_active]);
        return response()->json(['success' => true, 'message' => 'Status user berhasil diperbarui']);
    }

    // --- Shift Management ---
    public function schedules(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $shifts = Shift::all()->map(function ($s) {
            return [
                'id' => $s->id,
                'shift_name' => $s->name, // Alias name ke shift_name untuk dashboard
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
                'late_tolerance_minutes' => $s->late_tolerance_minutes
            ];
        });
        return response()->json(['success' => true, 'data' => $shifts]);
    }

    public function storeSchedule(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $validated = $request->validate([
            'shift_name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'late_tolerance_minutes' => 'integer',
        ]);

        $shift = Shift::create([
            'name' => $validated['shift_name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'late_tolerance_minutes' => $validated['late_tolerance_minutes'] ?? 0,
        ]);

        return response()->json(['success' => true, 'data' => $shift]);
    }

    // --- Location Management ---
    public function locations(Request $request)
    {
        $this->validatePermission($request, 'manage_location');
        return response()->json(['success' => true, 'data' => OfficeLocation::all()]);
    }

    public function storeLocation(Request $request)
    {
        $this->validatePermission($request, 'manage_location');
        $validated = $request->validate([
            'name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer',
        ]);
        $location = OfficeLocation::create($validated);
        return response()->json(['success' => true, 'data' => $location]);
    }

    // --- Attendance Management ---
    public function allAttendance(Request $request)
    {
        $this->validatePermission($request, 'view_attendance');
        $user = $request->user();
        $startDate = $request->get('start_date', Carbon::today()->subDays(7)->toDateString());
        $endDate = $request->get('end_date', Carbon::today()->toDateString());
        $status = $request->get('status');
        $employeeId = $request->get('employee_id');

        $query = Attendance::with(['user', 'user.shift'])->whereBetween('date', [$startDate, $endDate]);

        if ($user->role === 'spv') {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('supervisor_id', $user->id);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($employeeId) {
            $query->where('user_id', $employeeId);
        }

        $attendance = $query->orderBy('date', 'desc')->get();

        $mapped = $attendance->map(function ($a) {
            $duration = $a->duration_minutes ?: 0;
            return [
                'id' => $a->id,
                'employee_name' => $a->user->name,
                'employee_id' => $a->user->employee_code, // Tambah ini
                'position' => $a->user->position ?? 'Karyawan',
                'date' => $a->date,
                'formatted_date' => Carbon::parse($a->date)->format('d M Y'),
                'shift_name' => $a->user->shift->name ?? 'Reguler',
                'shift_hours' => $a->user->shift
                    ? substr($a->user->shift->start_time, 0, 5) . ' - ' . substr($a->user->shift->end_time, 0, 5)
                    : '08:00 - 17:00',
                'check_in_formatted' => $a->check_in_time ? $a->check_in_time->addHours(7)->format('H:i') : '--:--',
                'check_out_formatted' => $a->check_out_time ? $a->check_out_time->addHours(7)->format('H:i') : '--:--',
                'duration_formatted' => $duration > 0 ? floor($duration / 60) . 'j ' . ($duration % 60) . 'm' : '0j',
                'status' => $a->status,
                'late' => $a->late_minutes
            ];
        });

        $summary = [
            'total_records' => $attendance->count(),
            'present_count' => $attendance->where('status', 'present')->count(),
            'late_count' => $attendance->where('status', 'late')->count(),
            'total_hours' => round($attendance->sum('duration_minutes') / 60, 1),
        ];

        return response()->json([
            'success' => true,
            'data' => $mapped,
            'summary' => $summary
        ]);
    }

    // --- Leave Approval ---
    public function leaveRequests(Request $request)
    {
        $this->validatePermission($request, 'view_leave');
        $user = $request->user();
        $status = $request->get('status', 'all');
        $month = $request->get('month'); // YYYY-MM
        $type = $request->get('type', 'all');
        $userId = $request->get('user_id');

        $query = LeaveRequest::with(['user', 'supervisor', 'hr']);

        if ($user->role === 'spv') {
            $query->where('supervisor_id', $user->id);
        }

        if ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        if ($type !== 'all' && $type !== '') {
            $query->where('type', $type);
        }

        if ($month) {
            $carbonMonth = Carbon::parse($month . '-01');
            $query->whereYear('start_date', $carbonMonth->year)
                ->whereMonth('start_date', $carbonMonth->month);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $requests = $query->orderBy('created_at', 'desc')->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'status' => $l->status,
                'type' => $l->type,
                'leave_duration_type' => $l->leave_duration_type,
                'half_day_session' => $l->half_day_session,
                'reason' => $l->reason,
                'work_days' => $l->work_days,
                'total_days' => $l->total_days,
                'start_date' => $l->start_date->format('Y-m-d'),
                'start_date_formatted' => $l->start_date->format('d M Y'),
                'end_date' => $l->end_date->format('Y-m-d'),
                'end_date_formatted' => $l->end_date->format('d M Y'),
                'created_at' => $l->created_at->addHours(7)->format('d M Y H:i'),
                'attachment' => $l->attachment,
                'employee' => [
                    'id' => $l->user->id,
                    'name' => $l->user->name,
                    'position' => $l->user->position ?? 'Karyawan',
                    'department' => $l->user->department ?? '-',
                    'employee_id' => $l->user->employee_code,
                    'remaining_leave' => $l->user->remaining_leave,
                ],
                'spv_reviewed_at' => $l->spv_reviewed_at ? $l->spv_reviewed_at->addHours(7)->format('d M Y H:i') : null,
                'spv_reviewer_name' => $l->supervisor?->name ?? 'System',
                'spv_review_note' => $l->spv_review_note,
                'reviewed_at' => $l->reviewed_at ? $l->reviewed_at->addHours(7)->format('d M Y H:i') : null,
                'reviewer_name' => $l->hr?->name ?? 'Admin',
                'review_note' => $l->review_note,
            ];
        });

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function approveLeave(Request $request)
    {
        $id = $request->get('id');
        $status = $request->get('status'); // 'approve' or 'reject'
        $note = $request->get('note', '-');
        
        $leave = LeaveRequest::findOrFail($id);
        $user = $request->user();

        // 1. Authorization Check based on status
        if ($leave->status === 'pending_spv') {
            // Must be the reporting manager or admin
            $reportingManager = $leave->user->getReportingManager();
            if ($user->role !== 'admin' && (!$reportingManager || $user->id !== $reportingManager->id)) {
                return response()->json(['success' => false, 'message' => 'Anda bukan atasan yang berwenang untuk tahap ini.'], 403);
            }
        } elseif ($leave->status === 'pending_hr') {
            $this->validatePermission($request, 'approve_leave_hr');
        } else {
            return response()->json(['success' => false, 'message' => 'Pengajuan ini sudah selesai diproses.'], 422);
        }

        // 2. Process Approval/Rejection
        if ($status === 'reject') {
            $leave->update([
                'status' => $leave->status === 'pending_spv' ? 'rejected_spv' : 'rejected',
                'spv_reviewed_at' => $leave->status === 'pending_spv' ? now() : $leave->spv_reviewed_at,
                'spv_reviewed_by' => $leave->status === 'pending_spv' ? $user->id : $leave->spv_reviewed_by,
                'spv_review_note' => $leave->status === 'pending_spv' ? $note : $leave->spv_review_note,
                'reviewed_at' => $leave->status === 'pending_hr' ? now() : null,
                'reviewed_by' => $leave->status === 'pending_hr' ? $user->id : null,
                'review_note' => $leave->status === 'pending_hr' ? $note : null,
            ]);
            return response()->json(['success' => true, 'message' => 'Pengajuan ditolak']);
        }

        // 3. Handle Approval & Next Step
        if ($leave->status === 'pending_spv') {
            // Check if there is ANOTHER level of hierarchy above this approver
            // For now, let's keep it simple: Spv -> HR. 
            // In a more complex setup, we could set it to 'pending_manager' etc.
            // But to match current UI, we'll go to pending_hr.
            $leave->update([
                'status' => 'pending_hr',
                'spv_reviewed_at' => now(),
                'spv_reviewed_by' => $user->id,
                'spv_review_note' => $note
            ]);
            return response()->json(['success' => true, 'message' => 'Disetujui dan diteruskan ke HR']);
        } else {
            // Final Approval by HR
            $leave->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $user->id,
                'review_note' => $note
            ]);

            // Deduct quota
            $emp = $leave->user;
            if ($leave->type === 'cuti') {
                $emp->decrement('remaining_leave', $leave->total_days);
            } elseif ($leave->type === 'sakit') {
                $emp->decrement('sick_leave_remaining', $leave->total_days);
            }

            return response()->json(['success' => true, 'message' => 'Pengajuan disetujui secara final']);
        }
    }

    // --- Overtime Approval ---
    public function overtimeRequests(Request $request)
    {
        $this->validatePermission($request, 'view_attendance');
        $user = $request->user();
        $status = $request->get('status', 'pending');
        $query = OvertimeRequest::with('user');

        if ($user->role === 'spv') {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('supervisor_id', $user->id);
            });
        }

        $requests = $query->where('status', $status)->get();
        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function storeEmployee(Request $request)
    {
        $this->validatePermission($request, 'manage_employee');
        $id = $request->get('id');
        $validated = $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,username,' . $id,
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'position' => 'nullable',
            'position_id' => 'nullable',
            'department' => 'nullable',
            'role' => 'required|in:admin,spv,hr,employee',
            'supervisor_id' => 'nullable',
            'shift_id' => 'nullable',
            'location_id' => 'nullable',
            'password' => $id ? 'nullable|min:6' : 'required|min:6',
        ]);

        if ($id) {
            $user = User::findOrFail($id);
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }
            $user->update($validated);
            $user->syncRoles($validated['role']); // Tambahkan ini
        } else {
            $validated['password'] = Hash::make($validated['password']);
            $validated['employee_code'] = $request->get('employee_id') ?: 'EMP-' . strtoupper(Str::random(6));
            $user = User::create($validated);
            $user->assignRole($validated['role']); // Tambahkan ini
        }

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function deleteEmployee(Request $request)
    {
        $this->validatePermission($request, 'manage_employee');
        $id = $request->get('id');
        User::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Karyawan berhasil dihapus']);
    }

    public function storeManualAttendance(Request $request)
    {
        $this->validatePermission($request, 'manage_attendance');
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'required',
            'check_out' => 'nullable',
            'status' => 'required|in:present,late,incomplete',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $validated['employee_id'], 'date' => $validated['date']],
            [
                'check_in_time' => $validated['date'] . ' ' . $validated['check_in'],
                'check_out_time' => $validated['check_out'] ? $validated['date'] . ' ' . $validated['check_out'] : null,
                'status' => $validated['status'],
                'late_minutes' => 0, // Simplified for manual entry
                'duration_minutes' => 0 // Simplified or could be calculated
            ]
        );

        return response()->json(['success' => true, 'data' => $attendance]);
    }

    public function approveOvertime(Request $request, OvertimeRequest $overtime)
    {
        $this->validatePermission($request, 'view_attendance');
        $request->validate(['status' => 'required|in:approved,rejected']);

        $overtime->update([
            'status' => $request->status,
            'approved_at' => $request->status === 'approved' ? now() : null,
            'approved_by_id' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Status pengajuan lembur diperbarui']);
    }

    // --- Holiday Management ---
    public function getHolidays(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule'); // Use schedule permission
        $holidays = Holiday::orderBy('date', 'asc')->get();
        return response()->json(['success' => true, 'data' => $holidays]);
    }

    public function storeHoliday(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $id = $request->get('id');
        $validated = $request->validate([
            'name' => 'required|string',
            'date' => 'required|date|unique:holidays,date,' . $id,
            'type' => 'required|in:national,company'
        ]);

        if ($id) {
            $holiday = Holiday::findOrFail($id);
            $holiday->update($validated);
        } else {
            $holiday = Holiday::create($validated);
        }

        return response()->json(['success' => true, 'data' => $holiday]);
    }

    public function deleteHoliday(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $id = $request->get('id');
        Holiday::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Hari libur berhasil dihapus']);
    }

    // --- Position Management ---
    public function getPositions(Request $request)
    {
        $this->validatePermission($request, 'view_roles'); // Reuse role permission or use a new one
        $positions = Position::with(['parent'])->orderBy('level', 'desc')->get();
        return response()->json(['success' => true, 'data' => $positions]);
    }

    public function storePosition(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $id = $request->get('id');
        $validated = $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:positions,id',
            'department' => 'nullable|string',
            'level' => 'required|integer'
        ]);

        if ($id) {
            $position = Position::findOrFail($id);
            $position->update($validated);
        } else {
            $position = Position::create($validated);
        }

        return response()->json(['success' => true, 'data' => $position]);
    }

    public function deletePosition(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $id = $request->get('id');
        $position = Position::findOrFail($id);

        if ($position->users()->exists()) {
            return response()->json(['success' => false, 'message' => 'Jabatan tidak bisa dihapus karena masih memiliki karyawan aktif.'], 422);
        }

        $position->delete();
        return response()->json(['success' => true, 'message' => 'Jabatan berhasil dihapus']);
    }

    // --- Role & Permission Management ---
    public function getRolesConfig(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $permissions = Permission::where('guard_name', 'web')->get()->map(function ($p) {
            return [
                'id' => $p->name, // JS expects name/slug as id
                'permission_name' => $this->getPermissionLabel($p->name),
            ];
        });

        $roles = Role::where('guard_name', 'web')->get()->map(function ($r) {
            return [
                'role' => $r->name,
                'role_label' => strtoupper($r->name),
                'permissions' => $r->permissions->pluck('name'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'master_permissions' => $permissions,
                'roles_config' => $roles
            ]
        ]);
    }

    public function updateRolePermissions(Request $request)
    {
        $this->validatePermission($request, 'manage_roles');
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'array',
        ]);

        // Sync for both guards to keep them in parity
        foreach (['web', 'api'] as $guard) {
            $role = Role::where('name', $validated['role'])->where('guard_name', $guard)->first();
            if ($role) {
                $role->syncPermissions($validated['permissions'] ?? []);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Hak akses untuk role ' . strtoupper($validated['role']) . ' berhasil diperbarui'
        ]);
    }

    private function validatePermission(Request $request, string $permission)
    {
        $user = $request->user();
        if ($user->role === 'admin')
            return true;

        $roleNames = $user->roles->pluck('name')->toArray();
        $hasPerm = DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->whereIn('roles.name', $roleNames)
            ->where('permissions.name', $permission)
            ->exists();

        if (!$hasPerm) {
            abort(response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki izin ' . $permission
            ], 403));
        }

        return true;
    }

    private function getPermissionLabel($name)
    {
        $labels = [
            'view_attendance' => 'Lihat Laporan Absensi',
            'manage_attendance' => 'Kelola/Input Absen Manual',
            'view_leave' => 'Lihat Pengajuan Izin & Cuti',
            'approve_leave_spv' => 'Persetujuan Izin (Level SPV)',
            'approve_leave_hr' => 'Persetujuan Izin (Level HR/Final)',
            'view_employee' => 'Lihat Data Karyawan',
            'manage_employee' => 'Tambah/Edit/Hapus Karyawan',
            'manage_location' => 'Atur Lokasi Kantor & Radius',
            'manage_schedule' => 'Atur Jadwal Kerja & Shift',
            'view_roles' => 'Lihat Role & Hak Akses',
            'manage_roles' => 'Kelola Hak Akses (RBAC)',
        ];

        return $labels[$name] ?? ucwords(str_replace('_', ' ', $name));
    }
}

