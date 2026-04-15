<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends BaseAdminController
{
    public function index(Request $request)
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
                    'position' => $l->user->position?->name ?? $l->user->position_name ?? 'Karyawan',
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

    public function approve(Request $request)
    {
        $id = $request->get('id');
        $status = $request->get('status'); // 'approve' or 'reject'
        $note = $request->get('note', '-');
        
        $leave = LeaveRequest::findOrFail($id);
        $user = $request->user();

        // 1. Authorization Check based on status
        if ($leave->status === 'pending_spv') {
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
}
