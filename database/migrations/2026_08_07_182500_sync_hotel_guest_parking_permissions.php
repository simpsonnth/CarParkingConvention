<?php

declare(strict_types=1);

use App\Support\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionRegistry::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(PermissionRegistry::all());
    }

    public function down(): void
    {
        // Permissions are additive across deploys; do not drop on rollback.
    }
};
