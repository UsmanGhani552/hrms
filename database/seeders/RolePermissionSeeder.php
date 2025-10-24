<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $roles = [
            'employee',
            'hr',
            'admin',
        ];
        $permissions = [
            'search employees',
            'edit attendence',
            'user filter',
            'view holiday',
            'upload payroll',
            'edit payroll',
            'delete payroll',
            'leave approval'
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
        $hrRole = Role::where('name', 'hr')->first();
        $adminRole = Role::where('name', 'admin')->first();

        $hrRole->syncPermissions($permissions);
        $adminRole->syncPermissions($permissions);

        $admin = $this->createUser('Peter', 'peter@koderspedia.com', 'admin123');
        $admin->assignRole('admin');

        $hr = $this->createUser('Hr User', 'hr@koderspedia.com', 'admin123');
        $hr->assignRole('hr');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function createUser($name, $email, $password)
    {
        return User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make($password),
        ]);
    }
}
