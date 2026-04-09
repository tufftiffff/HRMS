<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicantProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Assuming applicant_id links to user_id, we will set this in the Seeder
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->city(),
            'avatar_path' => null,
            'resume_path' => 'resumes/dummy.pdf', // Dummy path for UI testing
        ];
    }
}