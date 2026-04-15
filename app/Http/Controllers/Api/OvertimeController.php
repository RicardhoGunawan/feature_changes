<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OvertimeService;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    protected $overtimeService;

    public function __construct(OvertimeService $overtimeService)
    {
        $this->overtimeService = $overtimeService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'type' => 'required|in:biasa,libur,darurat',
            'location' => 'required|in:kantor,rumah,lapangan,lain',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xlsx,docx|max:5120',
        ]);

        try {
            $overtime = $this->overtimeService->submitRequest(
                $request->user(),
                $request->only(['date', 'start_time', 'end_time', 'type', 'location', 'description']),
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan lembur berhasil dikirim',
                'data' => $overtime
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

        $history = $user->overtimeRequests()
            ->orderBy('date', 'desc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'total' => $history->total(),
                'last_page' => $history->lastPage(),
            ]
        ]);
    }
}
