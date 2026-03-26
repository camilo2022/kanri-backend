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
                        'name' => 'navegation.modules.submodules.all',
                        'title' => 'Listar submódulos',
                        'description' => 'Permite listar todos los submódulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.find',
                        'title' => 'Consultar submódulo',
                        'description' => 'Permite consultar la información de un submódulo específico.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.store',
                        'title' => 'Crear submódulo',
                        'description' => 'Permite crear nuevos submódulos en el sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.update',
                        'title' => 'Actualizar submódulo',
                        'description' => 'Permite actualizar la información de un submódulo.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.delete',
                        'title' => 'Eliminar submódulo',
                        'description' => 'Permite eliminar submódulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.restore',
                        'title' => 'Restaurar submódulo',
                        'description' => 'Permite restaurar submódulos eliminados.',
                    ],
                ]
            ],

            [
                'role' => [
                    'name' => 'people',
                    'title' => 'Personas',
                    'description' => 'Gestión de personas.',
                ],
                'permissions' => [
                    ['name' => 'people.all', 'title' => 'Listar personas', 'description' => 'Permite listar todas las personas.'],
                    ['name' => 'people.find', 'title' => 'Consultar persona', 'description' => 'Permite consultar una persona.'],
                    ['name' => 'people.store', 'title' => 'Crear persona', 'description' => 'Permite crear personas.'],
                    ['name' => 'people.update', 'title' => 'Actualizar persona', 'description' => 'Permite actualizar personas.'],
                    ['name' => 'people.delete', 'title' => 'Eliminar persona', 'description' => 'Permite eliminar personas.'],
                    ['name' => 'people.restore', 'title' => 'Restaurar persona', 'description' => 'Permite restaurar personas.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'employees',
                    'title' => 'Empleados',
                    'description' => 'Gestión de empleados.',
                ],
                'permissions' => [
                    ['name' => 'employees.all', 'title' => 'Listar empleados', 'description' => 'Permite listar empleados.'],
                    ['name' => 'employees.find', 'title' => 'Consultar empleado', 'description' => 'Permite consultar un empleado.'],
                    ['name' => 'employees.store', 'title' => 'Crear empleado', 'description' => 'Permite crear empleados.'],
                    ['name' => 'employees.update', 'title' => 'Actualizar empleado', 'description' => 'Permite actualizar empleados.'],
                    ['name' => 'employees.delete', 'title' => 'Eliminar empleado', 'description' => 'Permite eliminar empleados.'],
                    ['name' => 'employees.restore', 'title' => 'Restaurar empleado', 'description' => 'Permite restaurar empleados.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'areas',
                    'title' => 'Áreas',
                    'description' => 'Gestión de áreas.',
                ],
                'permissions' => [
                    ['name' => 'areas.all', 'title' => 'Listar áreas', 'description' => 'Permite listar áreas.'],
                    ['name' => 'areas.find', 'title' => 'Consultar área', 'description' => 'Permite consultar un área.'],
                    ['name' => 'areas.store', 'title' => 'Crear área', 'description' => 'Permite crear áreas.'],
                    ['name' => 'areas.update', 'title' => 'Actualizar área', 'description' => 'Permite actualizar áreas.'],
                    ['name' => 'areas.delete', 'title' => 'Eliminar área', 'description' => 'Permite eliminar áreas.'],
                    ['name' => 'areas.restore', 'title' => 'Restaurar área', 'description' => 'Permite restaurar áreas.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'positions',
                    'title' => 'Cargos',
                    'description' => 'Gestión de cargos.',
                ],
                'permissions' => [
                    ['name' => 'positions.all', 'title' => 'Listar cargos', 'description' => 'Permite listar cargos.'],
                    ['name' => 'positions.find', 'title' => 'Consultar cargo', 'description' => 'Permite consultar un cargo.'],
                    ['name' => 'positions.store', 'title' => 'Crear cargo', 'description' => 'Permite crear cargos.'],
                    ['name' => 'positions.update', 'title' => 'Actualizar cargo', 'description' => 'Permite actualizar cargos.'],
                    ['name' => 'positions.delete', 'title' => 'Eliminar cargo', 'description' => 'Permite eliminar cargos.'],
                    ['name' => 'positions.restore', 'title' => 'Restaurar cargo', 'description' => 'Permite restaurar cargos.'],
                    ['name' => 'positions.authorization.assign', 'title' => 'Asignar permisos', 'description' => 'Permite asignar permisos a cargos.'],
                    ['name' => 'positions.authorization.remove', 'title' => 'Remover permisos', 'description' => 'Permite remover permisos de cargos.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'genders',
                    'title' => 'Géneros',
                    'description' => 'Gestión de géneros.',
                ],
                'permissions' => [
                    ['name' => 'genders.all', 'title' => 'Listar géneros', 'description' => 'Permite listar géneros.'],
                    ['name' => 'genders.find', 'title' => 'Consultar género', 'description' => 'Permite consultar género.'],
                    ['name' => 'genders.store', 'title' => 'Crear género', 'description' => 'Permite crear géneros.'],
                    ['name' => 'genders.update', 'title' => 'Actualizar género', 'description' => 'Permite actualizar géneros.'],
                    ['name' => 'genders.delete', 'title' => 'Eliminar género', 'description' => 'Permite eliminar géneros.'],
                    ['name' => 'genders.restore', 'title' => 'Restaurar género', 'description' => 'Permite restaurar géneros.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'blood_types',
                    'title' => 'Tipos de sangre',
                    'description' => 'Gestión de tipos de sangre.',
                ],
                'permissions' => [
                    ['name' => 'blood_types.all', 'title' => 'Listar tipos de sangre', 'description' => 'Permite listar tipos de sangre.'],
                    ['name' => 'blood_types.find', 'title' => 'Consultar tipo de sangre', 'description' => 'Permite consultar tipo de sangre.'],
                    ['name' => 'blood_types.store', 'title' => 'Crear tipo de sangre', 'description' => 'Permite crear tipos de sangre.'],
                    ['name' => 'blood_types.update', 'title' => 'Actualizar tipo de sangre', 'description' => 'Permite actualizar tipos de sangre.'],
                    ['name' => 'blood_types.delete', 'title' => 'Eliminar tipo de sangre', 'description' => 'Permite eliminar tipos de sangre.'],
                    ['name' => 'blood_types.restore', 'title' => 'Restaurar tipo de sangre', 'description' => 'Permite restaurar tipos de sangre.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'arl',
                    'title' => 'ARL',
                    'description' => 'Gestión de ARL.',
                ],
                'permissions' => [
                    ['name' => 'arl.all', 'title' => 'Listar ARL', 'description' => 'Permite listar ARL.'],
                    ['name' => 'arl.find', 'title' => 'Consultar ARL', 'description' => 'Permite consultar ARL.'],
                    ['name' => 'arl.store', 'title' => 'Crear ARL', 'description' => 'Permite crear ARL.'],
                    ['name' => 'arl.update', 'title' => 'Actualizar ARL', 'description' => 'Permite actualizar ARL.'],
                    ['name' => 'arl.delete', 'title' => 'Eliminar ARL', 'description' => 'Permite eliminar ARL.'],
                    ['name' => 'arl.restore', 'title' => 'Restaurar ARL', 'description' => 'Permite restaurar ARL.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'eps',
                    'title' => 'EPS',
                    'description' => 'Gestión de EPS.',
                ],
                'permissions' => [
                    ['name' => 'eps.all', 'title' => 'Listar EPS', 'description' => 'Permite listar EPS.'],
                    ['name' => 'eps.find', 'title' => 'Consultar EPS', 'description' => 'Permite consultar EPS.'],
                    ['name' => 'eps.store', 'title' => 'Crear EPS', 'description' => 'Permite crear EPS.'],
                    ['name' => 'eps.update', 'title' => 'Actualizar EPS', 'description' => 'Permite actualizar EPS.'],
                    ['name' => 'eps.delete', 'title' => 'Eliminar EPS', 'description' => 'Permite eliminar EPS.'],
                    ['name' => 'eps.restore', 'title' => 'Restaurar EPS', 'description' => 'Permite restaurar EPS.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'pension_funds',
                    'title' => 'Fondos de pensión',
                    'description' => 'Gestión de fondos de pensión.',
                ],
                'permissions' => [
                    ['name' => 'pension_funds.all', 'title' => 'Listar fondos', 'description' => 'Permite listar fondos.'],
                    ['name' => 'pension_funds.find', 'title' => 'Consultar fondo', 'description' => 'Permite consultar fondo.'],
                    ['name' => 'pension_funds.store', 'title' => 'Crear fondo', 'description' => 'Permite crear fondos.'],
                    ['name' => 'pension_funds.update', 'title' => 'Actualizar fondo', 'description' => 'Permite actualizar fondos.'],
                    ['name' => 'pension_funds.delete', 'title' => 'Eliminar fondo', 'description' => 'Permite eliminar fondos.'],
                    ['name' => 'pension_funds.restore', 'title' => 'Restaurar fondo', 'description' => 'Permite restaurar fondos.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'compensation_funds',
                    'title' => 'Cajas de compensación',
                    'description' => 'Gestión de cajas de compensación.',
                ],
                'permissions' => [
                    ['name' => 'compensation_funds.all', 'title' => 'Listar cajas', 'description' => 'Permite listar cajas.'],
                    ['name' => 'compensation_funds.find', 'title' => 'Consultar caja', 'description' => 'Permite consultar caja.'],
                    ['name' => 'compensation_funds.store', 'title' => 'Crear caja', 'description' => 'Permite crear cajas.'],
                    ['name' => 'compensation_funds.update', 'title' => 'Actualizar caja', 'description' => 'Permite actualizar cajas.'],
                    ['name' => 'compensation_funds.delete', 'title' => 'Eliminar caja', 'description' => 'Permite eliminar cajas.'],
                    ['name' => 'compensation_funds.restore', 'title' => 'Restaurar caja', 'description' => 'Permite restaurar cajas.'],
                ]
            ]
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
