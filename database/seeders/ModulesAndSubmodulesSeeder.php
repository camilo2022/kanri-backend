<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Submodule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModulesAndSubmodulesSeeder extends Seeder
{
    public function run(): void
    {

        $modules_and_submodules = [
            [
                'module' => ['name' => 'Configuración', 'icon' => 'FaCogs'],
                'submodules' => [
                    ['name' => 'Módulos', 'url' => '/modules', 'icon' => 'FaBuffer', 'permission' => 'navegation.modules.all'],
                    ['name' => 'Usuarios', 'url' => '/users', 'icon' => 'FaUserFriends', 'permission' => 'users.all'],
                    ['name' => 'Personas', 'url' => '/people', 'icon' => 'FaUsers', 'permission' => 'people.all'],
                    ['name' => 'Empleados', 'url' => '/employees', 'icon' => 'FaHospitalUser', 'permission' => 'employees.all'],
                    ['name' => 'Roles', 'url' => '/roles', 'icon' => 'FaShieldAlt', 'permission' => 'authorization.roles.all'],
                    ['name' => 'Permisos', 'url' => '/permissions', 'icon' => 'FaUserLock', 'permission' => 'authorization.permissions.all'],
                ],
            ],
            [
                'module' => ['name' => 'Administración', 'icon' => 'FaToolbox'],
                'submodules' => [
                    ['name' => 'Áreas', 'url' => '/areas', 'icon' => 'FaMapMarkedAlt', 'permission' => 'organizational_structure.areas.all'],
                    ['name' => 'Géneros', 'url' => '/genders', 'icon' => 'FaVenusMars', 'permission' => 'genders.all'],
                    ['name' => 'Tipos de Sangre', 'url' => '/blood_types', 'icon' => 'FaTint', 'permission' => 'blood_types.all'],
                    ['name' => 'Admin de Riesgos', 'url' => '/risk_managers', 'icon' => 'FaBuilding', 'permission' => 'risk_managers.all'],
                    ['name' => 'Entidades de Salud', 'url' => '/health_entities', 'icon' => 'FaHospital', 'permission' => 'health_entities.all'],
                    ['name' => 'C. de Compensación', 'url' => '/compensation_funds', 'icon' => 'FaHandsHelping', 'permission' => 'compensation_funds.all'],
                    ['name' => 'Fondos de Pensión', 'url' => '/pension_funds', 'icon' => 'FaPiggyBank', 'permission' => 'pension_funds.all'],
                ],
            ],
            [
                'module' => ['name' => 'Caracterizacion', 'icon' => 'FaTag'],
                'submodules' => [
                    ['name' => 'Marcas', 'url' => '/trademarks', 'icon' => 'FaTags', 'permission' => 'trademarks.all'],
                    ['name' => 'Tallas', 'url' => '/sizes', 'icon' => 'FaTape', 'permission' => 'sizes.all'],
                    ['name' => 'Subgrupos', 'url' => '/subgroups', 'icon' => 'FaObjectGroup', 'permission' => 'classification.subgroups.all'],
                    ['name' => 'Grupos', 'url' => '/groups', 'icon' => 'FaBoxes', 'permission' => 'classification.groups.all'],
                    ['name' => 'Piezas', 'url' => '/pieces', 'icon' => 'FaStream', 'permission' => 'sizes.restore'],
                    ['name' => 'Tipos de Prenda', 'url' => '/garment_types', 'icon' => 'FaThList', 'permission' => 'pieces.restore'],
                    ['name' => 'Tipos de Bota', 'url' => '/boot_types', 'icon' => 'FaShoePrints', 'permission' => 'garment_types.restore'],
                    ['name' => 'Tipo de Trasero', 'url' => '/back_types', 'icon' => 'FaBookmark', 'permission' => 'boot_types.restore'],
                    ['name' => 'Tipos de Pretina', 'url' => '/waistband_types', 'icon' => 'FaGripLines', 'permission' => 'back_types.restore'],
                    ['name' => 'Categorías', 'url' => '/categories', 'icon' => 'FaListUl', 'permission' => 'waistband_types.restore'],
                    ['name' => 'Procesos', 'url' => '/processes', 'icon' => 'FaAlignLeft', 'permission' => 'workflow.processes.all'],
                    ['name' => 'Tonos de Lavado', 'url' => '/wash_tones', 'icon' => 'FaFillDrip', 'permission' => 'workflow.processes.subprocesses.restore'],
                    ['name' => 'Colores', 'url' => '/colors', 'icon' => 'FaPalette', 'permission' => 'workflow.processes.subprocesses.operations.delete'],
                    ['name' => 'Colecciones', 'url' => '/collections', 'icon' => 'FaCubes', 'permission' => 'wash_tones.update'],
                    ['name' => 'Tipos de Cotilla', 'url' => '/yoke_types', 'icon' => 'FaDraftingCompass', 'permission' => 'colors.update'],
                    ['name' => 'T. de Proveedores', 'url' => '/supplier_types', 'icon' => 'FaCampground', 'permission' => 'typification.supplier_types.all'],
                    ['name' => 'Tipos de Insumo', 'url' => '/supply_types', 'icon' => 'FaToolbox', 'permission' => 'yoke_types.find'],
                    ['name' => 'Tipos de Archivo', 'url' => '/file_types', 'icon' => 'FaFileAlt', 'permission' => 'typification.file_types.all'],
                    ['name' => 'Tipos de Personas', 'url' => '/person_types', 'icon' => 'FaUsers', 'permission' => 'typification.person_types.all'],
                    ['name' => 'Bancos', 'url' => '/banks', 'icon' => 'FaLandmark', 'permission' => 'banks.all'],
                    ['name' => 'Tipos de Cuenta', 'url' => '/account_types', 'icon' => 'FaCcMastercard', 'permission' => 'account_types.all'],
                    ['name' => 'Tipologia', 'url' => '/typologies', 'icon' => 'FaTypo3', 'permission' => 'typification.typologies.all'],
                    ['name' => 'Tipos de Parada', 'url' => '/stop_types', 'icon' => 'FaStopCircle', 'permission' => 'stop_types.all'],
                    ['name' => 'Reglas', 'url' => '/rules', 'icon' => 'FaRuler', 'permission' => 'rules.all'],
                ],
            ],
            [
                'module' => ['name' => 'Gestión', 'icon' => 'FaCogs'],
                'submodules' => [
                    ['name' => 'G. de Colecciones', 'url' => '/management/collections', 'icon' => 'FaFolder', 'permission' => 'navegation.modules.store'],
                    ['name' => 'Productos', 'url' => '/products', 'icon' => 'FaBoxes', 'permission' => 'products.all'],
                    ['name' => 'G. de Producción', 'url' => '/management/production', 'icon' => 'FaCubes', 'permission' => 'technical_sheets.production_orders.all'],
                    ['name' => 'C. de Producción', 'url' => '/production_schedule', 'icon' => 'FaCalendarAlt', 'permission' => 'technical_sheets.production_orders.find'],
                    ['name' => 'Reportes', 'url' => '/reports', 'icon' => 'FaFileInvoice', 'permission' => 'reports.view'],
                ],
            ],
            [
                'module' => ['name' => 'Planta', 'icon' => 'FaCity'],
                'submodules' => [
                    ['name' => 'Movimientos', 'url' => '/movements', 'icon' => 'FaExchangeAlt', 'permission' => 'movements.all'],
                    ['name' => 'Productividad', 'url' => '/productivity', 'icon' => 'FaBusinessTime', 'permission' => 'productivity.all'],
                ],
            ],
        ];

        $permissions = Permission::pluck('id', 'name');

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
                    'permission_id' => $permissions[$submodule['permission']],
                ]);
            }
        }
    }
}
