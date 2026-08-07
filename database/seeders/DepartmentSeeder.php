<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get the primary branch (Head Office)
        $branch = Branch::where('code', 'HO')->first();
        
        if (!$branch) {
            $branch = Branch::first();
        }

        if (!$branch) {
            $this->command->error("❌ No branch found. Please run BranchMigrationSeeder first.");
            return;
        }

        // 2. Create the "General" Department
        $department = Department::firstOrCreate(
            ['name' => 'General'],
            [
                'slug' => 'general',
                'branch_id' => $branch->id,
                'manager_id' => null, // You can assign an admin ID here if known
            ]
        );

        $this->command->info("✅ 'General' Department ensured in branch: {$branch->name}");

        // 3. Optional: Assign employees without departments to this general one
        // (Note: In this system, employees usually link to Teams, and Teams to Departments)
        // If there are employees directly without teams/departments, you can handle them here.
    }
}
