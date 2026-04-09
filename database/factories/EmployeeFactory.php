<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Department;
use App\Models\Position;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'user_id' => User::factory(), 
        'department_id' => Department::factory(),
        'position_id' => Position::factory(),
        
        'employee_code' => 'EMP-' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'employee_status' => fake()->randomElement(['probation', 'active', 'resigned']), // 
        'hire_date' => fake()->date(),
        'base_salary' => fake()->randomFloat(2, 3000, 15000), // 
        'phone' => fake()->phoneNumber(),
        'address' => fake()->address(),
        ];
    }
}
