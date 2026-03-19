<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'password' => Hash::make('password123'),
        ]);

        $roles = Role::all();

        $user->syncRoles($roles);

        $permissions = Permission::all();

        $user->syncPermissions($permissions);
    }
}
