<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends BaseAdminController
{
    /**
     * List leave requests.
     *
     * - Administrators see ALL requests.
     * - Employees with a position see requests from their subordinate positions.
     */
    public function debugHierarchy()
    {
        $report = [];
        foreach (\App\Models\User::all() as $u) {
            $mgr = $u->getReportingManager();
            $report[] = [
                'user' => $u->username,
                'position_id' => $u->position_id,
                'parent_pos_id' => $u->jobPosition ? $u->jobPosition->parent_id : null,
                'manager_username' => $mgr ? $mgr->username : null,
                'manager_id' => $mgr ? $mgr->id : null,
            ];
        }
        return response()->json($report);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status', 'all');
        $month  = $request->input('month');
        $type   = $request->input('type', 'all');
        $userId = $request->input('user_id');
        $department = $request->input('department');

        $query = LeaveRequest::with(['user.jobPosition', 'supervisor', 'hr']);

        if ($department) {
            $query->whereHas('user', function($q) use ($department) {
                $q->where('department_id', $department);
            });
        }

        // ── Visibility Filter Based on Role/Position ──────────────────────────────
        $isAdmin = ($user->role === 'administrator');

        // Jika user adalah Employee biasa, ATAU jika sedang secara spesifik melihat antrean "pending_spv"
        // Maka batasi pencarian HANYA ke bawahan langsungnya.
        if (!$isAdmin || ltrim($status) === 'pending_spv') {
            if ($user->position_id) {
                // Jangan filter 'is_active' di sini, biarkan getReportingManager() mengecek validitasnya
                $allUsers = \App\Models\User::whereNotNull('position_id')->get();
                $reportingUserIds = [];
                foreach ($allUsers as $u) {
                    if ($u->id == $user->id) continue;
                    $manager = $u->getReportingManager();
                    if ($manager && $manager->id == $user->id) {
                        $reportingUserIds[] = $u->id;
                    }
                }

                if (empty($reportingUserIds)) {
                    $query->whereRaw('0 = 1');
                } else {
                    $query->whereIn('user_id', $reportingUserIds);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki wewenang approval.'], 403);
            }
        }

        // ── Filters ───────────────────────────────────────────────────────────────
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
                'id'                   => $l->id,
                'status'               => $l->status,
                'type'                 => $l->type,
                'leave_duration_type'  => $l->leave_duration_type,
                'half_day_session'     => $l->half_day_session,
                'reason'               => $l->reason,
                'work_days'            => $l->work_days,
                'total_days'           => $l->total_days,
                'start_date'           => $l->start_date ? $l->start_date->format('Y-m-d') : null,
                'start_date_formatted' => $l->start_date ? $l->start_date->format('d M Y') : null,
                'end_date'             => $l->end_date ? $l->end_date->format('Y-m-d') : null,
                'end_date_formatted'   => $l->end_date ? $l->end_date->format('d M Y') : null,
                'created_at'           => $l->created_at->addHours(7)->format('d M Y H:i'),
                'attachment'           => $l->attachment,
                'employee'             => [
                    'id'             => $l->user->id,
                    'name'           => $l->user->name,
                    'position'       => $l->user->jobPosition?->name ?? 'Karyawan',
                    'department'     => $l->user->department?->name ?? '-',
                    'employee_id'    => $l->user->employee_code,
                    'remaining_leave'=> $l->user->remaining_leave,
                ],
                'spv_reviewed_at'   => $l->spv_reviewed_at ? $l->spv_reviewed_at->addHours(7)->format('d M Y H:i') : null,
                'spv_reviewer_name' => $l->supervisor?->name ?? '-',
                'spv_review_note'   => $l->spv_review_note,
                'reviewed_at'       => $l->reviewed_at ? $l->reviewed_at->addHours(7)->format('d M Y H:i') : null,
                'reviewer_name'     => $l->hr?->name ?? '-',
                'review_note'       => $l->review_note,
            ];
        });

        return response()->json([
            'success' => true, 
            'data' => $requests,
            'departments' => \App\Models\Department::orderBy('name', 'asc')->get(['id', 'name'])
        ]);
    }

    /**
     * Approve or reject a leave request.
     *
     * Logic:
     * - pending_spv: approver must be in a parent position relative to requester
     * - pending_hr : approver must be administrator OR have top-level position
     */
    public function approve(Request $request)
    {
        $id     = $request->input('id');
        $status = $request->input('status'); // 'approve' or 'reject'
        $note   = $request->input('note', '-');

        $leave   = LeaveRequest::with('user.jobPosition')->findOrFail($id);
        $approver = $request->user();
        $isAdmin  = ($approver->role === 'administrator');

        // ── Authorization: Check if approver has position authority ───────────────
        if ($leave->status === 'pending_spv') {
            if (!$isAdmin && !$this->canApproveForUser($request, $leave->user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang untuk menyetujui pengajuan ini. Periksa konfigurasi jabatan Anda.'
                ], 403);
            }
        } elseif ($leave->status === 'pending_hr') {
            // Final approval: must be administrator (or top-level position)
            if (!$isAdmin) {
                $isTopLevel = $approver->jobPosition && !$approver->jobPosition->parent_id;
                if (!$isTopLevel) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Persetujuan final hanya dapat dilakukan oleh Administrator atau jabatan tertinggi.'
                    ], 403);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan ini sudah selesai diproses.'
            ], 422);
        }

        // ── Process Rejection ────────────────────────────────────────────────────
        if ($status === 'reject') {
            $leave->update([
                'status'         => $leave->status === 'pending_spv' ? 'rejected_spv' : 'rejected',
                'spv_reviewed_at'  => $leave->status === 'pending_spv' ? now() : $leave->spv_reviewed_at,
                'spv_reviewed_by'  => $leave->status === 'pending_spv' ? $approver->id : $leave->spv_reviewed_by,
                'spv_review_note'  => $leave->status === 'pending_spv' ? $note : $leave->spv_review_note,
                'reviewed_at'      => $leave->status === 'pending_hr' ? now() : null,
                'reviewed_by'      => $leave->status === 'pending_hr' ? $approver->id : null,
                'review_note'      => $leave->status === 'pending_hr' ? $note : null,
            ]);
            return response()->json(['success' => true, 'message' => 'Pengajuan ditolak.']);
        }

        // ── Process Approval ─────────────────────────────────────────────────────
        if ($leave->status === 'pending_spv') {
            $leave->update([
                'status'           => 'pending_hr',
                'spv_reviewed_at'  => now(),
                'spv_reviewed_by'  => $approver->id,
                'spv_review_note'  => $note,
            ]);
            return response()->json(['success' => true, 'message' => 'Disetujui, diteruskan ke persetujuan final.']);
        }

        // Final Approval (pending_hr)
        $leave->update([
            'status'      => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $approver->id,
            'review_note' => $note,
        ]);

        // Deduct quota
        $emp = $leave->user;
        if ($leave->type === 'cuti') {
            $emp->decrement('remaining_leave', $leave->total_days ?? $leave->work_days);
        } elseif ($leave->type === 'sakit') {
            $emp->decrement('sick_leave_remaining', $leave->total_days ?? $leave->work_days);
        }

        return response()->json(['success' => true, 'message' => 'Pengajuan disetujui secara final.']);
    }

    // ── Private Helpers ──────────────────────────────────────────────────────────

    protected function canApproveForUser($request, $subordinateUser): bool
    {
        $approver = $request->user();
        
        if (!$approver->position_id) {
            return false;
        }

        $manager = $subordinateUser->getReportingManager();
        return $manager && $manager->id == $approver->id;
    }

    /**
     * Recursively get all position IDs that are children of the given position.
     */
    private function getSubordinatePositionIds(int $positionId): array
    {
        $ids = [];
        $children = \App\Models\Position::where('parent_id', $positionId)->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getSubordinatePositionIds($child->id));
        }

        return $ids;
    }
}
