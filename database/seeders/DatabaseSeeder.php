<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@lms.test'],
            [
                'name' => 'Admin',
                'password' => 'Admin@123',
                'role' => User::ROLE_ADMIN,
            ]
        );

        $this->call(ClassSubjectLessonSeeder::class);
        $this->call(DemoUsersSeeder::class);
    }
}
