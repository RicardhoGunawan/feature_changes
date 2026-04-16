<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends BaseAdminController
{
    public function index(Request $request)
    {
        $this->validatePermission($request, 'view_dashboard');
        $user = $request->user();
        $uid = $user->id;

        $today = Carbon::today()->toDateString();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        // Base Query for Employees
        $empQuery = User::where('role', 'employee')->where('is_active', true);
        $totalEmployees = $empQuery->count();

        // 2. Today Stats
        $attQuery = Attendance::where('date', $today);
        $todayAttendance = $attQuery->get();
        $checkedInCount = $todayAttendance->count();
        $lateCount = $todayAttendance->where('status', 'late')->count();
        $presentCount = $todayAttendance->where('status', 'present')->count();

        $attendanceRate = $totalEmployees > 0
            ? round(($checkedInCount / $totalEmployees) * 100) . '%'
            : '0%';

        // 3. Pending Requests
        $leaveQuery = LeaveRequest::where('status', 'pending_spv');
        $pendingLeaves = $leaveQuery->count();

        // 4. Recent Activities
        $actQuery = Attendance::with('user.jobPosition')->where('date', $today);
        $recentActivities = $actQuery->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($a) {
                return [
                    'employee_name' => $a->user->name,
                    'position' => $a->user->jobPosition?->name ?? 'Karyawan',
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
            $dAtt = $dAttQuery->get();
            $chartData[] = [
                'date' => $date,
                'on_time' => $dAtt->where('status', 'present')->count(),
                'late' => $dAtt->where('status', 'late')->count(),
            ];
        }

        // 6. Monthly Stats
        $monthAttQuery = Attendance::whereYear('date', $thisYear)->whereMonth('date', $thisMonth);
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
}
