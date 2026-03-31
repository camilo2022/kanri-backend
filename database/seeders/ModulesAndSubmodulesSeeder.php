<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Submodule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModulesAndSubmodulesSeeder extends Seeder
{
    public function run(): void
    {

        $modules_and_submodules = [
            [
                'module' => [
                    'name' => 'Gestion',
                    'icon' => 'FaGear',
                ],

                'submodules' => [
                    [
                        'name' => 'Usuarios',
                        'url' => '/users',
                        'icon' => 'FaUser',
                        'permission_id' => 1
                    ],
                    [
                        'name' => 'Roles',
                        'url' => '/roles',
                        'icon' => 'FaAddressCard',
                        'permission_id' => 9
                    ],
                    [
                        'name' => 'Permisos',
                        'url' => '/permissions',
                        'icon' => 'FaAddressCard',
                        'permission_id' => 13
                    ],
                    [
                        'name' => 'Módulos',
                        'url' => '/modules',
                        'icon' => 'FaBars',
                        'permission_id' => 17
                    ],
                    [
                        'name' => 'Personas',
                        'url' => '/people',
                        'icon' => 'FaBars',
                        'permission_id' => 29
                    ],
                ]
            ],
        ];

        foreach ($modules_and_submodules as $item) {

            $module = Module::create([
                'name' => $item['module']['name'],
                'icon' => $item['module']['icon'],
            ]);

            foreach ($item['submodules'] as $submodule) {

                Submodule::create([
                    'module_id' => $module->id,
                    'name' => $submodule['name'],
                    'url' => $submodule['url'],
                    'icon' => $submodule['icon'],
                    'permission_id' => $submodule['permission_id'],
                ]);
            }
        }
    }
}
