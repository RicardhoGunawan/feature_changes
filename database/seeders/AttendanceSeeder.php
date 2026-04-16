<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'employee')->get();
        // Also include the admin/director if they have an employee_code
        $admins = User::where('role', 'administrator')->get();
        $allUsers = $users->merge($admins);

        $now = Carbon::now();
        
        // Generate for last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            // Skip weekends
            if ($date->isWeekend()) continue;

            foreach ($allUsers as $user) {
                // Randomly skip some days (e.g. 10% chance to not attend)
                if (rand(1, 10) === 1) continue;

                $checkInBase = $date->copy()->setTime(8, 0, 0); // 08:00
                
                // Randomly decide status
                $rand = rand(1, 10);
                
                if ($rand <= 7) {
                    // 70% Present (On time) - Check in between 07:00 and 08:00
                    $checkIn = $date->copy()->setTime(7, rand(0, 59), rand(0, 59));
                    $status = 'present';
                    $lateMinutes = 0;
                } elseif ($rand <= 9) {
                    // 20% Late - Check in between 08:01 and 09:00
                    $checkIn = $date->copy()->setTime(8, rand(1, 59), rand(0, 59));
                    $status = 'late';
                    $lateMinutes = $checkIn->diffInMinutes($checkInBase);
                } else {
                    // 10% Incomplete (Check in early, but no check out)
                    $checkIn = $date->copy()->setTime(7, rand(30, 59), rand(0, 59));
                    $status = 'incomplete';
                    $lateMinutes = 0;
                }

                $checkOut = null;
                $duration = 0;

                if ($status !== 'incomplete') {
                    // Check out between 17:00 and 18:00
                    $checkOut = $date->copy()->setTime(17, rand(0, 59), rand(0, 59));
                    $duration = $checkIn->diffInMinutes($checkOut);
                }

                Attendance::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $date->toDateString()],
                    [
                        'check_in_time' => $checkIn,
                        'check_out_time' => $checkOut,
                        'status' => $status,
                        'late_minutes' => $lateMinutes,
                        'duration_minutes' => $duration,
                        'check_in_location_id' => $user->location_id,
                        'check_out_location_id' => $user->location_id,
                    ]
                );
            }
        }

        $this->command->info('✓ Attendance Seeder finished: Generated 7 days of dummy data for all employees.');
    }
}
