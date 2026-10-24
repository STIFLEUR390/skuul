<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Give existing installs the cash session permissions.
 *
 * The seeder runs once, at install, so a school already running never got
 * these and its till screen stayed closed. A permission the install already
 * has is left alone, so its own role changes are kept.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private array $grants = [
        'manage cash session' => ['admin'],
        'read cash session' => ['admin'],
    ];

    public function up(): void
    {
        foreach ($this->grants as $name => $roleNames) {
            if (Permission::query()->where('name', $name)->where('guard_name', 'web')->exists()) {
                continue;
            }

            $permission = Permission::findOrCreate($name, 'web');

            $roleIds = DB::table('roles')
                ->whereIn('name', [...$roleNames, 'platform-admin'])
                ->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->id,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
