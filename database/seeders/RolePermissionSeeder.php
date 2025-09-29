<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'employee',
            'hr',
            'admin',
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = $this->createUser('Peter', 'peter@koderspedia.com', 'admin123');
        $admin->assignRole('admin');

        $hr = $this->createUser('Hr User', 'hr@koderspedia.com', 'admin123');
        $hr->assignRole('hr');
    }

    private function createUser($name, $email, $password)
    {
        return User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make($password),
        ]);
    }
}
