<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\User;
use App\Models\Position;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Management',  'code' => 'MGMT', 'desc' => 'Executive & Strategic Management'],
            ['name' => 'HR',          'code' => 'HRD',  'desc' => 'Human Resources Department'],
            ['name' => 'Finance',     'code' => 'FIN',  'desc' => 'Finance & Accounting'],
            ['name' => 'Operasional', 'code' => 'OPS',  'desc' => 'Operational & General Affairs'],
        ];

        foreach ($departments as $dept) {
            $createdDept = Department::updateOrCreate(
                ['name' => $dept['name']],
                [
                    'code' => $dept['code'],
                    'description' => $dept['desc']
                ]
            );

            // Update existing Users linked by string name
            User::where('department', $dept['name'])
                ->update(['department_id' => $createdDept->id]);

            // Update existing Positions linked by string name
            Position::where('department', $dept['name'])
                ->update(['department_id' => $createdDept->id]);
            
            $this->command->info("✓ Department Created: {$dept['name']} (Linked users & positions)");
        }
    }
}
