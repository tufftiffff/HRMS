<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Employee;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Get random employees
        $employees = Employee::inRandomOrder()->take(20)->get();

        // Create 5 records for each of these 20 employees (Total = 100 rows)
        foreach ($employees as $emp) {
            for ($i = 0; $i < 5; $i++) {
                Attendance::create([
                    'employee_id' => $emp->employee_id,
                    'date' => now()->subDays($i), // Past 5 days
                    'clock_in_time' => '09:00:00',
                    'clock_out_time' => '18:00:00',
                    'at_status' => 'present',
                    'late_minutes' => 0
                ]);
            }
        }
    }
}