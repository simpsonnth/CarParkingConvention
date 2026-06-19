<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();

        User::query()->each(function (User $user): void {
            $legacyRole = $user->getAttribute('role') ?? 'attendant';
            $user->syncRoles([in_array($legacyRole, ['admin', 'attendant'], true) ? $legacyRole : 'attendant']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('attendant')->after('password');
        });

        User::query()->each(function (User $user): void {
            $role = $user->hasRole('admin') ? 'admin' : 'attendant';
            $user->forceFill(['role' => $role])->saveQuietly();
            $user->syncRoles([]);
            $user->syncPermissions([]);
        });
    }
};
