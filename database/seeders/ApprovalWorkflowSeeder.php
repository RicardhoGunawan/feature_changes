<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalStep;
use App\Models\Department;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Office Workflow (Requirement #1: Simple)
        $office = ApprovalWorkflow::updateOrCreate(['name' => 'Office Workflow'], [
            'description' => 'Approval flow for office employees (Manager Only)'
        ]);
        
        ApprovalStep::updateOrCreate([
            'workflow_id' => $office->id,
            'step_number' => 1
        ], [
            'approver_type' => 'manager',
            'is_final' => true // Directly approved after manager signs
        ]);

        // 2. Field Workflow (Requirement #1 & #2: Detailed)
        $field = ApprovalWorkflow::updateOrCreate(['name' => 'Field Workflow'], [
            'description' => 'Approval flow for field employees (Manager -> HR)'
        ]);

        ApprovalStep::updateOrCreate([
            'workflow_id' => $field->id,
            'step_number' => 1
        ], [
            'approver_type' => 'manager',
            'is_final' => false
        ]);

        ApprovalStep::updateOrCreate([
            'workflow_id' => $field->id,
            'step_number' => 2
        ], [
            'approver_type' => 'hr',
            'is_final' => true
        ]);

        // 3. Link existing departments to defaults
        // (Assigning HR and Management to Office, others to Field as example)
        Department::whereIn('name', ['HR', 'Management', 'Finance'])->update(['leave_workflow_id' => $office->id]);
        Department::whereNotIn('name', ['HR', 'Management', 'Finance'])->update(['leave_workflow_id' => $field->id]);

        $this->command->info('✓ Dynamic Approval Workflows seeded successfully!');
    }
}
