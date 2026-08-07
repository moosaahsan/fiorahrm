<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Team;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Shift;
use App\Models\CompanyOffDay;

class BranchMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create the primary "Head Office" branch
        // This acts as the default container for all existing data
        $branch = Branch::firstOrCreate(
            ['code' => 'HO'],
            [
                'name' => 'Head Office',
                'address' => 'Main Office Address',
                'timezone' => 'Asia/Karachi',
                'is_active' => true
            ]
        );

        $branchId = $branch->id;

        $this->command->info("Migrating existing data to Branch: {$branch->name} (ID: {$branchId})");

        // 2. Propagate Branch ID to all legacy records that don't have one
        
        $countEmployees = Employee::whereNull('branch_id')->update(['branch_id' => $branchId]);
        $this->command->info("- Migrated {$countEmployees} Employees");

        $countDepartments = Department::whereNull('branch_id')->update(['branch_id' => $branchId]);
        $this->command->info("- Migrated {$countDepartments} Departments");

        $countTeams = Team::whereNull('branch_id')->update(['branch_id' => $branchId]);
        $this->command->info("- Migrated {$countTeams} Teams");

        $countShifts = Shift::whereNull('branch_id')->update(['branch_id' => $branchId]);
        $this->command->info("- Migrated {$countShifts} Shifts");

        $countAttendance = Attendance::whereNull('branch_id')->update(['branch_id' => $branchId]);
        $this->command->info("- Migrated {$countAttendance} Attendance Logs");

        $countLeaves = Leave::whereNull('branch_id')->update(['branch_id' => $branchId]);
        $this->command->info("- Migrated {$countLeaves} Leave Records");

        $countOffDays = CompanyOffDay::whereNull('branch_id')->update(['branch_id' => $branchId]);
        $this->command->info("- Migrated {$countOffDays} Company Off Days");

        $this->command->info("✅ Migration completed successfully!");
    }
}
