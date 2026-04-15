<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function today(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        
        $attendance = $user->attendances()
            ->where('date', $today)
            ->first();

        // Return flat structure for mobile compatibility
        return response()->json([
            'success' => true,
            'checked_in' => !!$attendance,
            'data' => $attendance ? [
                'id' => $attendance->id,
                'check_in_time' => $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->addHours(7)->format('H:i') : null,
                'check_out_time' => $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->addHours(7)->format('H:i') : null,
                'status' => $attendance->status,
            ] : null,
            'schedule' => [
                'name' => $user->shift->name ?? 'Reguler',
                'start' => $user->shift ? substr($user->shift->start_time, 0, 5) : '08:00',
                'end' => $user->shift ? substr($user->shift->end_time, 0, 5) : '17:00'
            ]
        ]);
    }

    public function checkin(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $attendance = $this->attendanceService->checkIn(
                $request->user(),
                $request->latitude,
                $request->longitude
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $attendance = $this->attendanceService->checkOut(
                $request->user(),
                $request->latitude,
                $request->longitude
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil',
                'data' => $attendance
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
        $filter = $request->get('filter', 'all');
        $query = $user->attendances();

        if ($filter !== 'all') {
            if ($filter === 'month') {
                $query->whereYear('date', date('Y'))->whereMonth('date', date('m'));
            } elseif (preg_match('/^\d{4}-\d{2}$/', $filter)) {
                [$year, $month] = explode('-', $filter);
                $query->whereYear('date', $year)->whereMonth('date', $month);
            }
        }

        $history = $query->orderBy('date', 'desc')->paginate(100); // Ambil lebih banyak untuk history

        // Transformasi data agar ramah untuk Mobile
        $history->getCollection()->transform(function ($item) {
            $carbonDate = \Carbon\Carbon::parse($item->date);
            return [
                'id' => $item->id,
                'date' => $carbonDate->toDateString(), // 2026-04-14
                'formatted_date' => $carbonDate->translatedFormat('d M Y'), 
                'status' => $item->status,
                'check_in_time' => $item->check_in_time,
                'check_out_time' => $item->check_out_time,
                'check_in_formatted' => $item->check_in_time ? \Carbon\Carbon::parse($item->check_in_time)->addHours(7)->format('H:i') : '--:--',
                'check_out_formatted' => $item->check_out_time ? \Carbon\Carbon::parse($item->check_out_time)->addHours(7)->format('H:i') : '--:--',
                'late_minutes' => $item->late_minutes,
                'duration_formatted' => $item->duration_minutes ? floor($item->duration_minutes / 60) . 'j ' . ($item->duration_minutes % 60) . 'm' : '',
                'check_in_latitude' => $item->check_in_latitude,
                'check_in_longitude' => $item->check_in_longitude,
                'is_incomplete' => $item->status === 'present' && !$item->check_out_time,
            ];
        });

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
