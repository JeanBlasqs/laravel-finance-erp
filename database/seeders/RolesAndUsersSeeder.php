<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegister;

class RolesAndUsersSeeder extends Seeder
{

    private const RESOURCES = [
        'customers'    => ['list', 'view', 'create', 'edit', 'delete'],
        'suppliers'    => ['list', 'view', 'create', 'edit', 'delete'],
        'categories'   => ['list', 'view', 'create', 'edit', 'delete'],
        'accounts'     => ['list', 'view', 'create', 'edit', 'delete'],
        'transactions' => ['list', 'view', 'create', 'edit', 'delete', 'pay'],
        'cash-flow'    => ['view'],
        'reports'      => ['view', 'export'],
        'users'        => ['list', 'view', 'create', 'edit', 'delete'],
    ];

    public function run(): void
    {
        app(PermissionRegister::class)->forgetCachedPermissions();

        foreach (self::RESOURCES as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$resource}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $financial = Role::firstOrCreate(['name' => 'financial', 'guard_name' => 'web']);
        $financial->syncPermissions(
            Permission::where('name', 'not like', 'users.%')
                ->where('name', 'not like', '%.delete')
                ->get()
        );

        $reader = Role::firstOrCreate(['name' => 'reader', 'guard_name' => 'web']);
        $reader->syncPermissions(
            Permission::where(function ($q) {
                $q->where('name', 'like', '%.list')
                  ->orWhere('name', 'like', '%.view');
            })->where('name', 'not like', 'users.%')->get()
        );

        $this->makeUser('Admin', 'admin@example.com', $admin);
        $this->makeUser('Financial', 'financial@example.com', $financial);
        $this->makeUser('Reader', 'reader@example.com', $reader);
    }

    private function makeUser(string $name, string $email, Role $role): void
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles([$role]);
    }
}