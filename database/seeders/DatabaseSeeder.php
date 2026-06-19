<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'nathan-simpson@outlook.com'],
            [
                'name' => 'Nathan Simpson',
                'password' => bcrypt('bacon'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        $attendant = User::firstOrCreate(
            ['email' => 'staff@twickenham.com'],
            [
                'name' => 'Parking Staff',
                'password' => bcrypt('remote'),
                'email_verified_at' => now(),
            ]
        );
        $attendant->syncRoles(['attendant']);
    }
}
