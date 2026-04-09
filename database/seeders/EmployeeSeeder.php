<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a Dummy Department
        // We use 'Information Technology' to match your other seeder
        $dept = Department::firstOrCreate(
            ['department_name' => 'Information Technology'], 
            ['de_description' => 'Handles IT infrastructure and software.']
        );

        // 2. Create a Dummy Position (LINKED TO DEPARTMENT)
        $pos = Position::firstOrCreate(
            ['position_name' => 'Software Engineer'],
            [
                'pos_description' => 'Standard Dev Role',
                'department_id' => $dept->department_id // <--- FIXED: Now requires this ID
            ]
        );

        // 3. Create the User Login
        // Note: I removed 'user_id' => '3' so the DB auto-increments it safely
        $user = User::create([
            'name' => 'John Employee',
            'email' => 'employee@example.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
        ]);

        // 4. Create the Employee Profile
        Employee::create([
            'user_id' => $user->user_id,         
            'department_id' => $dept->department_id, 
            'position_id' => $pos->position_id,   
            'employee_code' => 'EMP-0001',
            'employee_status' => 'active',
            'hire_date' => now(),
            'base_salary' => 5000.00,
            'phone' => '0123456789',
            'address' => '123 Tech Street'
        ]);
    }
}