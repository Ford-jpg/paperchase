<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
class OfficeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a ROOT user to act as proposer/approver
        $rootUser = User::where('role', UserRole::ROOT)->first();
        
        // If no ROOT user exists, create one
        if (!$rootUser) {
            $rootUser = User::factory()->root()->create();
        }

        return [
            'name' => fake()->company(),
            'acronym' => fake()->lexify('???'),
            'head_name' => fake()->name(),
            'designation' => fake()->jobTitle(),
            'proposed_by' => $rootUser->id,
            'proposed_at' => now(),
            'approved_by' => $rootUser->id,
            'approved_at' => now(),
        ];
    }

    /**
     * Create an office that's only proposed (not approved yet)
     */
    public function proposed(): static
    {
        return $this->state(function (array $attributes) {
            $proposer = User::where('role', UserRole::ADMINISTRATOR)->first() 
                ?? User::factory()->administrator()->create();

            return [
                'proposed_by' => $proposer->id,
                'proposed_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
            ];
        });
    }

    /**
     * Create an office that's fully approved
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $rootUser = User::where('role', UserRole::ROOT)->first()
                ?? User::factory()->root()->create();

            return [
                'proposed_by' => $rootUser->id,
                'proposed_at' => now()->subDays(1),
                'approved_by' => $rootUser->id,
                'approved_at' => now(),
            ];
        });
    }
}
