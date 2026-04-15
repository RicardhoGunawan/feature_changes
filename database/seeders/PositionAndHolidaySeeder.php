<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;
use App\Models\Holiday;
use App\Models\User;

class PositionAndHolidaySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Positions (Hierarchy)
        $director = Position::create(['name' => 'Director', 'level' => 4, 'department' => 'Management']);
        
        $manager = Position::create([
            'name' => 'Manager HR', 
            'level' => 3, 
            'department' => 'HR',
            'parent_id' => $director->id
        ]);
        
        $supervisor = Position::create([
            'name' => 'Supervisor HR', 
            'level' => 2, 
            'department' => 'HR',
            'parent_id' => $manager->id
        ]);
        
        $staff = Position::create([
            'name' => 'Staff HR', 
            'level' => 1, 
            'department' => 'HR',
            'parent_id' => $supervisor->id
        ]);

        // 2. Create Dummy Holidays
        Holiday::create(['name' => 'Libur Idul Fitri', 'date' => '2026-03-30', 'type' => 'national']);
        Holiday::create(['name' => 'Hari Buruh', 'date' => '2026-05-01', 'type' => 'national']);
        Holiday::create(['name' => 'Hari Kemerdekaan', 'date' => '2026-08-17', 'type' => 'national']);
        Holiday::create(['name' => 'Libur Akhir Tahun', 'date' => '2026-12-31', 'type' => 'company']);

        // 3. Assign some existing users to these positions (Optional/Example)
        // This assumes you have some users. If not, this part will be skipped.
        $users = User::limit(4)->get();
        if ($users->count() >= 4) {
            $users[0]->update(['position_id' => $director->id]);
            $users[1]->update(['position_id' => $manager->id]);
            $users[2]->update(['position_id' => $supervisor->id]);
            $users[3]->update(['position_id' => $staff->id]);
        }
    }
}
