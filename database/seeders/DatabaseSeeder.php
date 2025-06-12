<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Section;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create ROOT user first if it doesn't exist
        $rootUser = User::where('role', UserRole::ROOT)->first();
        if (!$rootUser) {
            $rootUser = User::factory()->root()->create();
        }

        // Create approved offices first
        $approvedOffices = Office::factory()
            ->approved()
            ->count(3)
            ->create();

        // Create approved sections for the offices
        foreach ($approvedOffices as $office) {
            Section::factory()
                ->approved()
                ->for($office)
                ->count(2)
                ->create();
        }

        // Create ADMINISTRATOR users and assign them to offices
        foreach ($approvedOffices as $office) {
            User::factory()
                ->administrator()
                ->approved()
                ->create([
                    'office_id' => $office->id,
                    'email' => "admin.{$office->acronym}@paperchase.com",
                ]);
        }

        // Create some regular USER accounts (approved)
        User::factory()
            ->user()
            ->approved()
            ->count(15)
            ->create();

        // Create some pending USER accounts (not yet approved)
        User::factory()
            ->user()
            ->pending()
            ->count(5)
            ->create();

        // Create proposed offices (pending approval)
        Office::factory()
            ->proposed()
            ->count(2)
            ->create();

        // Create proposed sections (pending approval)
        Section::factory()
            ->proposed()
            ->count(3)
            ->create();
    }
}
