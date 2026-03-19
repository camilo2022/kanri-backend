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
                    'name' => 'Usuarios',
                    'icon' => 'cisGroup',
                ],

                'submodules' => [
                    [
                        'name' => 'Listar usuarios',
                        'url' => '',
                        'icon' => 'cibSitepoint',
                        'permission_id' => '1'
                    ],
                ]
            ],

            [
                'module' => [
                    'name' => 'authorization',
                    'icon' => 'cilLockLocked',
                ],

                'submodules' => [
                    [
                        'name' => 'Roles',
                        'url' => '/roles',
                        'icon' => 'cilUser',
                        'permission_id' => '9'
                    ],
                    [
                        'name' => 'Permisos',
                        'url' => '/permissions',
                        'icon' => 'cilShieldAlt',
                        'permission_id' => '13'
                    ],
                ]
            ]
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
