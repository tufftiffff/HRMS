<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Employee;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a specific completed period
        $period = PayrollPeriod::create([
            'period_month' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'payroll_status' => 'completed',
        ]);

        // 2. Generate a payslip for every employee for this period
        $employees = Employee::all();

        foreach ($employees as $emp) {
            Payslip::create([
                'employee_id' => $emp->employee_id,
                'period_id' => $period->period_id,
                'basic_salary' => $emp->base_salary,
                'total_allowances' => 500.00,
                'total_deductions' => 200.00,
                'net_salary' => ($emp->base_salary + 500) - 200,
                'generated_at' => now(),
            ]);
        }
    }
}