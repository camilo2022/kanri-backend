<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {

        $roles_and_permissions = [
            [
                'role' => [
                    'name' => 'users',
                    'title' => 'Usuarios',
                    'description' => 'Gestión de usuarios.',
                ],

                'permissions' => [
                    [
                        'name' => 'users.all',
                        'title' => 'Listar usuarios',
                        'description' => 'Permite listar todos los usuarios del sistema.',
                    ],
                    [
                        'name' => 'users.find',
                        'title' => 'Consultar usuario',
                        'description' => 'Permite consultar la información de un usuario específico.',
                    ],
                    [
                        'name' => 'users.store',
                        'title' => 'Crear usuario',
                        'description' => 'Permite crear nuevos usuarios en el sistema.',
                    ],
                    [
                        'name' => 'users.update',
                        'title' => 'Actualizar usuario',
                        'description' => 'Permite actualizar la información de un usuario.',
                    ],
                    [
                        'name' => 'users.delete',
                        'title' => 'Eliminar usuario',
                        'description' => 'Permite eliminar usuarios del sistema.',
                    ],
                    [
                        'name' => 'users.restore',
                        'title' => 'Restaurar usuario',
                        'description' => 'Permite restaurar usuarios eliminados.',
                    ],
                    [
                        'name' => 'users.authorization.assign',
                        'title' => 'Asignar roles o permisos',
                        'description' => 'Permite asignar roles o permisos a los usuarios.',
                    ],
                    [
                        'name' => 'users.authorization.remove',
                        'title' => 'Remover roles o permisos',
                        'description' => 'Permite remover roles o permisos asignados a los usuarios.',
                    ],
                ]
            ],

            [
                'role' => [
                    'name' => 'authorization',
                    'title' => 'Autorización',
                    'description' => 'Gestión de roles y permisos del sistema.',
                ],

                'permissions' => [
                    [
                        'name' => 'authorization.roles.all',
                        'title' => 'Listar roles',
                        'description' => 'Permite listar todos los roles del sistema.',
                    ],
                    [
                        'name' => 'authorization.roles.find',
                        'title' => 'Consultar rol',
                        'description' => 'Permite consultar la información de un rol específico.',
                    ],
                    [
                        'name' => 'authorization.roles.store',
                        'title' => 'Crear rol',
                        'description' => 'Permite crear nuevos roles en el sistema.',
                    ],
                    [
                        'name' => 'authorization.roles.update',
                        'title' => 'Actualizar rol',
                        'description' => 'Permite actualizar la información de un rol.',
                    ],

                    [
                        'name' => 'authorization.permissions.all',
                        'title' => 'Listar permisos',
                        'description' => 'Permite listar todos los permisos del sistema.',
                    ],
                    [
                        'name' => 'authorization.permissions.find',
                        'title' => 'Consultar permiso',
                        'description' => 'Permite consultar la información de un permiso específico.',
                    ],
                    [
                        'name' => 'authorization.permissions.store',
                        'title' => 'Crear permiso',
                        'description' => 'Permite crear nuevos permisos en el sistema.',
                    ],
                    [
                        'name' => 'authorization.permissions.update',
                        'title' => 'Actualizar permiso',
                        'description' => 'Permite actualizar la información de un permiso.',
                    ],
                ]
            ],

            [
                'role' => [
                    'name' => 'navegation',
                    'title' => 'Navegación',
                    'description' => 'Gestión de módulos y submódulos del sistema.',
                ],

                'permissions' => [
                    [
                        'name' => 'navegation.modules.all',
                        'title' => 'Listar módulos',
                        'description' => 'Permite listar todos los módulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.find',
                        'title' => 'Consultar módulo',
                        'description' => 'Permite consultar la información de un módulo específico.',
                    ],
                    [
                        'name' => 'navegation.modules.store',
                        'title' => 'Crear módulo',
                        'description' => 'Permite crear nuevos módulos en el sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.update',
                        'title' => 'Actualizar módulo',
                        'description' => 'Permite actualizar la información de un módulo.',
                    ],
                    [
                        'name' => 'navegation.modules.delete',
                        'title' => 'Eliminar módulo',
                        'description' => 'Permite eliminar módulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.restore',
                        'title' => 'Restaurar módulo',
                        'description' => 'Permite restaurar módulos eliminados.',
                    ],
                    [
                        'name' => 'navegation.submodules.all',
                        'title' => 'Listar submódulos',
                        'description' => 'Permite listar todos los submódulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.submodules.find',
                        'title' => 'Consultar submódulo',
                        'description' => 'Permite consultar la información de un submódulo específico.',
                    ],
                    [
                        'name' => 'navegation.submodules.store',
                        'title' => 'Crear submódulo',
                        'description' => 'Permite crear nuevos submódulos en el sistema.',
                    ],
                    [
                        'name' => 'navegation.submodules.update',
                        'title' => 'Actualizar submódulo',
                        'description' => 'Permite actualizar la información de un submódulo.',
                    ],
                    [
                        'name' => 'navegation.submodules.delete',
                        'title' => 'Eliminar submódulo',
                        'description' => 'Permite eliminar submódulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.submodules.restore',
                        'title' => 'Restaurar submódulo',
                        'description' => 'Permite restaurar submódulos eliminados.',
                    ],
                ]
            ],

        ];

        foreach ($roles_and_permissions as $role_and_permission) {

            $role = Role::create([
                'name' => $role_and_permission['role']['name'],
                'title' => $role_and_permission['role']['title'],
                'description' => $role_and_permission['role']['description']
            ]);

            foreach ($role_and_permission['permissions'] as $permission) {
                $permission = Permission::create([
                    'name' => $permission['name'],
                    'title' => $permission['title'],
                    'description' => $permission['description'],
                ]);
                $role->givePermissionTo($permission);
            }
        }
    }
}
