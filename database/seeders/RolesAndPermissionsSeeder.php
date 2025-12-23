<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'permission-list',
            'permission-create',
            'permission-edit',
            'permission-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->syncPermissions([
            'user-list',
            'user-create',
            'user-edit',
            'role-list',
            'permission-list',
        ]);

        // Default role is "user"
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->syncPermissions([
            'user-list',
        ]);

        // Create demo users
        $admin = User::firstOrCreate(
            ['email' => 'admin@aol.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Assdd12121!'),
            ]
        );
        $admin->syncRoles(['Admin']);

        $manager = User::firstOrCreate(
            ['email' => 'manager@aol.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('Aa12333'),
            ]
        );
        $manager->syncRoles(['Manager']);

        $regularUser = User::firstOrCreate(
            ['email' => 'user@aol.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('Aa121212'),
            ]
        );
        $regularUser->syncRoles(['user']);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Demo users created:');
        $this->command->info('Admin: admin@aol.com / password');
        $this->command->info('Manager: manager@aol.com / password');
        $this->command->info('User: user@aol.com / password');
    }
}

