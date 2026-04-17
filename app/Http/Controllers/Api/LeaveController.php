<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }
    /**
     * Get available leave types for the picker.
     */
    public function types()
    {
        $types = \App\Models\LeaveType::where('is_active', true)
            ->with('policy')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type_code' => 'required|exists:leave_types,code', // New system uses code
            'type' => 'nullable', // Keep for legacy
            'leave_duration_type' => 'nullable|in:full_day,half_day',
            'half_day_session' => 'nullable|required_if:leave_duration_type,half_day|in:morning,afternoon',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|required_unless:leave_duration_type,half_day|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Default type if not provided
        $validated['leave_duration_type'] = $validated['leave_duration_type'] ?? 'full_day';

        try {
            $leave = $this->leaveService->submitRequest(
                $request->user(),
                $validated,
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan izin berhasil dikirim',
                'data' => $leave
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $limit = $request->get('limit', 10);

        // Eager load relasi peninjau
        $history = $user->leaveRequests()
            ->with(['supervisor', 'hr'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        // Transformasi agar format tanggal & nama cocok dengan Mobile
        $transformed = $history->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => $item->type,
                'start_date' => $item->start_date ? $item->start_date->toDateString() : null,
                'start_date_formatted' => $item->start_date ? $item->start_date->format('d M Y') : null,
                'end_date' => $item->end_date ? $item->end_date->toDateString() : null,
                'end_date_formatted' => $item->end_date ? $item->end_date->format('d M Y') : null,
                'work_days' => $item->work_days,
                'reason' => $item->reason,
                'status' => $item->status,
                'attachment' => $item->attachment,
                'leave_duration_type' => $item->leave_duration_type,
                'half_day_session' => $item->half_day_session,
                'remaining_leave_at_req' => $item->remaining_leave_at_req,
                'sick_leave_at_req' => $item->sick_leave_at_req,
                'spv_reviewer_name' => $item->supervisor->name ?? 'System',
                'spv_reviewed_at' => $item->spv_reviewed_at ? \Carbon\Carbon::parse($item->spv_reviewed_at)->addHours(7)->format('Y-m-d H:i:s') : null,
                'spv_review_note' => $item->spv_review_note,
                'reviewer_name' => $item->hr->name ?? 'Admin',
                'reviewed_at' => $item->reviewed_at ? \Carbon\Carbon::parse($item->reviewed_at)->addHours(7)->format('Y-m-d H:i:s') : null,
                'review_note' => $item->review_note,
                'created_at' => \Carbon\Carbon::parse($item->created_at)->addHours(7)->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformed, 
            'quota' => [
                'remaining_leave' => $user->remaining_leave,
                'sick_leave_remaining' => $user->sick_leave_remaining
            ],
            'pagination' => [
                'current_page' => $history->currentPage(),
                'total' => $history->total(),
            ]
        ]);
    }
}
