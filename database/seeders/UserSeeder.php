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
        $roles = Role::all();
        $permissions = Permission::all();

        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'name' => "Usuario $i",
                'email' => "usuario$i@example.com",
                'password' => Hash::make('password123'),
            ]);

            $user->syncRoles($roles);
            $user->syncPermissions($permissions);
        }
    }
}
