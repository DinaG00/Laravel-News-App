<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole  = Role::firstOrCreate(['name' => 'user']);

        // Create permissions
        $permissions = [
            'view markets',
            'view news',
            'view exchange',
            'save articles',
            'save markets',
            'manage notifications',
            'manage users',
            'manage content',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Admin gets everything
        $adminRole->givePermissionTo(Permission::all());

        // Regular user gets content permissions
        $userRole->givePermissionTo([
            'view markets',
            'view news',
            'view exchange',
            'save articles',
            'save markets',
            'manage notifications',
        ]);

        // Assign admin role to first user if exists
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole('admin');
        }
    }
}
