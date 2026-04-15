<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Attendance;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends BaseAdminController
{
    public function index(Request $request)
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
                'employee_id' => $a->user->employee_code,
                'position' => $a->user->position?->name ?? $a->user->position_name ?? 'Karyawan',
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

    public function storeManual(Request $request)
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
                'late_minutes' => 0,
                'duration_minutes' => 0
            ]
        );

        return response()->json(['success' => true, 'data' => $attendance]);
    }

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
}
