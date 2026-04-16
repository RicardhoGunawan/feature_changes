<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class AttendanceService
{
    /**
     * Calculate distance between two points in meters (Haversine formula).
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2): int
    {
        $earthRadius = 6371000; // in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }

    /**
     * Process Check-in for a user.
     */
    public function checkIn(User $user, float $lat, float $lon): Attendance
    {
        $today = Carbon::today()->toDateString();

        // 1. Check if already checked in
        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            throw new Exception('Anda sudah check-in hari ini.');
        }

        // 2. Validate location if user has an office location
        if ($user->location) {
            $distance = $this->calculateDistance(
                $lat, $lon,
                $user->location->latitude,
                $user->location->longitude
            );

            if ($distance > $user->location->radius) {
                throw new Exception("Anda berada di luar radius kantor ({$distance}m). Maksimal {$user->location->radius}m.");
            }
        }

        // 3. Determine status and late minutes
        $status = 'present';
        $lateMinutes = 0;
        if ($user->shift) {
            $now = Carbon::now();
            // Use same date for comparison
            $shiftStart = Carbon::createFromTimeString($user->shift->start_time);
            $lateThreshold = $shiftStart->copy()->addMinutes($user->shift->late_tolerance_minutes);

            if ($now->greaterThan($lateThreshold)) {
                $status = 'late';
                // Calculate from the theoretical start time
                $lateMinutes = $now->diffInMinutes($shiftStart);
            }
        }

        // 4. Save
        return Attendance::create([
            'user_id' => $user->id,
            'shift_id' => $user->shift_id,
            'date' => $today,
            'check_in_time' => Carbon::now(),
            'check_in_latitude' => $lat,
            'check_in_longitude' => $lon,
            'check_in_location_id' => $user->location_id,
            'status' => $status,
            'late_minutes' => $lateMinutes,
        ]);
    }

    /**
     * Process Check-out for a user.
     */
    public function checkOut(User $user, float $lat, float $lon): Attendance
    {
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            throw new Exception('Anda belum melakukan check-in hari ini.');
        }

        if ($attendance->check_out_time) {
            throw new Exception('Anda sudah melakukan check-out hari ini.');
        }

        $checkOutTime = Carbon::now();
        $duration = 0;
        if ($attendance->check_in_time) {
            $duration = Carbon::parse($attendance->check_in_time)->diffInMinutes($checkOutTime);
        }

        $attendance->update([
            'check_out_time' => $checkOutTime,
            'check_out_latitude' => $lat,
            'check_out_longitude' => $lon,
            'check_out_location_id' => $user->location_id,
            'duration_minutes' => $duration
        ]);

        return $attendance;
    }
}
