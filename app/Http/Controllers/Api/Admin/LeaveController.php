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
            /** @var \App\Models\User $u */
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
                    /** @var \App\Models\User $u */
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
        $id = $request->input('id');
        $action = $request->input('status'); // 'approve' or 'reject'
        $note = $request->input('note');
        
        $leave = LeaveRequest::with(['user.department', 'leaveType'])->findOrFail($id);
        $approver = $request->user();
        $isAdmin = $approver->role === 'administrator';

        // 1. Handle REJECTION
        if ($action === 'reject') {
            $leave->update([
                'status'         => $approver->role === 'hr' ? 'rejected' : 'rejected_spv',
                'review_note'    => $note,
                'reviewed_at'    => now(),
                'reviewed_by'    => $approver->id,
                'spv_review_note' => $leave->status === 'pending_spv' ? $note : $leave->spv_review_note,
                'spv_reviewed_at' => $leave->status === 'pending_spv' ? now() : $leave->spv_reviewed_at,
            ]);
            \App\Services\AuditService::log('leave_rejection', $leave, null, ['note' => $note], $leave->user_id);
            return response()->json(['success' => true, 'message' => 'Pengajuan berhasil ditolak.']);
        }

        // 2. Handle APPROVAL (Check Workflow)
        $dept = $leave->user->department;
        $workflowId = $dept ? $dept->leave_workflow_id : null;
        $currentStepNum = $leave->current_step ?? 1;

        // Advance to next step or final approval
        $nextStepCode = null;
        if ($workflowId) {
            $nextStepCode = \App\Models\ApprovalStep::where('workflow_id', $workflowId)
                ->where('step_number', $currentStepNum + 1)
                ->first();
        }

        if (!$nextStepCode) {
            // FINAL APPROVAL
            $oldLeave = $leave->replicate();
            $leave->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $approver->id,
                'review_note' => $note,
            ]);

            // Deduct quota logic
            $emp = $leave->user;
            if ($leave->type === 'cuti' || ($leave->leaveType && $leave->leaveType->code === 'AL')) {
                $emp->decrement('remaining_leave', $leave->total_days ?? $leave->work_days);
            } elseif ($leave->type === 'sakit' || ($leave->leaveType && $leave->leaveType->code === 'SL')) {
                $emp->decrement('sick_leave_remaining', $leave->total_days ?? $leave->work_days);
            }

            \App\Services\AuditService::log('leave_final_approval', $leave, ['status' => $oldLeave->status], ['status' => 'approved'], $leave->user_id);
            return response()->json(['success' => true, 'message' => 'Pengajuan disetujui secara final.']);
        } else {
            // Move to next step (e.g., from SPV to HR)
            $leave->update([
                'status' => ($nextStepCode->approver_type === 'hr') ? 'pending_hr' : 'pending_spv',
                'current_step' => $currentStepNum + 1,
                'spv_reviewed_at' => now(),
                'spv_reviewed_by' => $approver->id,
                'spv_review_note' => $note
            ]);

            \App\Services\AuditService::log('leave_step_approval', $leave, null, ['to_step' => $currentStepNum + 1], $leave->user_id);
            return response()->json(['success' => true, 'message' => 'Pengajuan disetujui dan diteruskan ke tahap berikutnya.']);
        }
    }

    /**
     * Get all leave policies for management.
     */
    public function getPolicies()
    {
        return response()->json([
            'success' => true,
            'data' => \App\Models\LeaveType::with(['policy.tiers'])->get()
        ]);
    }

    /**
     * Store or update a leave policy.
     */
    public function storePolicy(Request $request)
    {
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'description' => 'nullable|string',
            'default_quota' => 'required|integer',
            'min_service_months' => 'required|integer',
            'requires_attachment' => 'required|boolean',
            'allow_half_day' => 'required|boolean',
            'max_concurrent_leave' => 'nullable|integer',
        ]);

        $policy = \App\Models\LeavePolicy::updateOrCreate(
            ['leave_type_id' => $data['leave_type_id']],
            $data
        );

        // Audit Log
        \App\Services\AuditService::log('policy_update', $policy, null, $data);

        return response()->json(['success' => true, 'message' => 'Kebijakan berhasil diperbarui.']);
    }

    /**
     * Get all approval workflows.
     */
    public function getWorkflows()
    {
        return response()->json([
            'success' => true,
            'data' => \App\Models\ApprovalWorkflow::with('steps')->get()
        ]);
    }

    /**
     * Get specific workflow details.
     */
    public function showWorkflow($id)
    {
        $wf = \App\Models\ApprovalWorkflow::with('steps')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $wf]);
    }

    public function storeWorkflow(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'steps' => 'required|array',
            'steps.*.approver_type' => 'required|in:spv,hr',
            'steps.*.is_final' => 'boolean'
        ]);

        $workflow = \App\Models\ApprovalWorkflow::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ]);

        foreach ($data['steps'] as $index => $step) {
            $workflow->steps()->create([
                'step_number' => $index + 1,
                'approver_type' => $step['approver_type'],
                'is_final' => $step['is_final'] ?? false
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Alur berhasil dibuat.', 'data' => $workflow]);
    }

    public function updateWorkflow(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'steps' => 'required|array',
            'steps.*.approver_type' => 'required|in:spv,hr',
            'steps.*.is_final' => 'boolean'
        ]);

        $workflow = \App\Models\ApprovalWorkflow::findOrFail($id);
        $workflow->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ]);

        // Sync steps: delete all and recreate (simplest for small chains)
        $workflow->steps()->delete();
        foreach ($data['steps'] as $index => $step) {
            $workflow->steps()->create([
                'step_number' => $index + 1,
                'approver_type' => $step['approver_type'],
                'is_final' => $step['is_final'] ?? false
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Alur berhasil diperbarui.']);
    }

    public function deleteWorkflow($id)
    {
        $workflow = \App\Models\ApprovalWorkflow::findOrFail($id);
        $count = \App\Models\Department::where('leave_workflow_id', $id)->count();
        if ($count > 0) {
            return response()->json(['success' => false, 'message' => 'Alur ini tidak bisa dihapus karena masih digunakan oleh '.$count.' departemen.'], 422);
        }

        $workflow->steps()->delete();
        $workflow->delete();

        return response()->json(['success' => true, 'message' => 'Alur berhasil dihapus.']);
    }

    /**
     * Get audit logs for transparency.
     */
    public function getAuditLogs()
    {
        $logs = \App\Models\AuditLog::with(['causer', 'targetUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * FORCE action (Approve/Reject) by Admin to override hierarchy.
     */
    public function forceAction(Request $request, $id)
    {
        $action = $request->input('action'); // 'approve' or 'reject'
        $note = $request->input('note', 'FORCE ACTION BY ADMIN');
        $leave = LeaveRequest::findOrFail($id);
        $admin = $request->user();

        if ($leave->status === 'approved' || $leave->status === 'rejected') {
            return response()->json(['success' => false, 'message' => 'Pengajuan sudah selesai.'], 422);
        }

        $oldStatus = $leave->status;
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        $leave->update([
            'status' => $newStatus,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'review_note' => "[FORCE] " . $note,
        ]);

        // Audit Log
        \App\Services\AuditService::log('force_action', $leave, ['status' => $oldStatus], ['status' => $newStatus], $leave->user_id);

        if ($newStatus === 'approved') {
            // Deduct quota
            $emp = $leave->user;
             if ($leave->type === 'cuti' || ($leave->leaveType && $leave->leaveType->code === 'AL')) {
                $emp->decrement('remaining_leave', $leave->total_days ?? $leave->work_days);
            }
        }

        return response()->json(['success' => true, 'message' => 'Tindakan paksa (Force Action) berhasil dilakukan.']);
    }


    /**
     * Submit leave request on behalf of an employee (Proxy).
     */
    public function proxyStore(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'duration_type' => 'required|in:full_day,half_day',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $lt = \App\Models\LeaveType::findOrFail($validated['leave_type_id']);
        $validated['leave_type_code'] = $lt->code;
        $validated['leave_duration_type'] = $validated['duration_type'];

        try {
            $targetUser = \App\Models\User::findOrFail($validated['user_id']);
            $leave = resolve(\App\Services\LeaveService::class)->submitRequest($targetUser, $validated);

            \App\Services\AuditService::log('proxy_leave_submission', $leave, null, ['by_admin' => $request->user()->name], $targetUser->id);

            return response()->json(['success' => true, 'message' => 'Cuti berhasil diinput.', 'data' => $leave]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    protected function canApproveForUser(Request $request, $requesterUser): bool
    {
        $approver = $request->user();
        if ($approver->role === 'administrator') return true;
        
        // Simple hierarchy check: target user must be in same department and approver is spv
        return $approver->department_id === $requesterUser->department_id && $approver->role === 'spv';
    }
}
