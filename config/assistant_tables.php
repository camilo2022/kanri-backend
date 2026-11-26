<?php

/*
|--------------------------------------------------------------------------
| Tablas que el asistente puede consultar por SQL (query_data)
|--------------------------------------------------------------------------
|
| El permiso para leer una tabla es el de la ruta `all` que la lista (basta uno de los permisos de la lista).
| Las tablas hijas y de unión usan el permiso de la ruta que las trae. Lo que no está aquí no se puede consultar.
|
| - forbidden: nunca, con ningún permiso (tokens, OAuth, sesiones, cachés, jobs, migraciones y cuentas bancarias).
| - hidden_columns: columnas que nunca se leen.
| - catalog_table / catalog_items_table: los catálogos viven en `subitems`, separados por `item_id`; cada
|   catálogo tiene sus permisos. `items` (nombre de cada catálogo) se lee con cualquier permiso de catálogo.
| - tables: tabla => permisos que la abren.
|
| Generado de la captura de las rutas `all` (2026-11-26); se edita a mano.
|--------------------------------------------------------------------------
*/

return [

    'forbidden' => array (
  0 => 'personal_access_tokens',
  1 => 'oauth_access_tokens',
  2 => 'oauth_auth_codes',
  3 => 'oauth_clients',
  4 => 'oauth_device_codes',
  5 => 'oauth_refresh_tokens',
  6 => 'sessions',
  7 => 'cache',
  8 => 'cache_locks',
  9 => 'jobs',
  10 => 'job_batches',
  11 => 'failed_jobs',
  12 => 'migrations',
  13 => 'bank_accounts',
),

    'hidden_columns' => [
        'users' => ['password', 'remember_token'],
    ],

    'catalog_table' => 'subitems',

    'catalog_items_table' => 'items',

    'catalogs' => [
        1 => ['name' => 'Tipos de archivos', 'permissions' => array (
  0 => 'typification.file_types.all',
)],
        2 => ['name' => 'Subtipos de archivos', 'permissions' => array (
  0 => 'typification.file_types.file_subtypes.all',
)],
        3 => ['name' => 'Áreas', 'permissions' => array (
  0 => 'organizational_structure.areas.all',
  1 => 'employees.store',
  2 => 'employees.update',
)],
        4 => ['name' => 'Cargos', 'permissions' => array (
  0 => 'organizational_structure.areas.positions.all',
  1 => 'employees.store',
  2 => 'employees.update',
)],
        5 => ['name' => 'Géneros', 'permissions' => array (
  0 => 'genders.all',
  1 => 'people.store',
  2 => 'people.update',
)],
        6 => ['name' => 'Tipos de sangre', 'permissions' => array (
  0 => 'blood_types.all',
  1 => 'people.store',
  2 => 'people.update',
)],
        7 => ['name' => 'Administradora de Riesgos', 'permissions' => array (
  0 => 'risk_managers.all',
  1 => 'employees.store',
  2 => 'employees.update',
)],
        8 => ['name' => 'Entidades de Salud', 'permissions' => array (
  0 => 'health_entities.all',
  1 => 'employees.store',
  2 => 'employees.update',
)],
        9 => ['name' => 'Fondos de pensión', 'permissions' => array (
  0 => 'pension_funds.all',
  1 => 'employees.store',
  2 => 'employees.update',
)],
        10 => ['name' => 'Cajas de compensación', 'permissions' => array (
  0 => 'compensation_funds.all',
  1 => 'employees.store',
  2 => 'employees.update',
)],
        11 => ['name' => 'Categorías', 'permissions' => array (
  0 => 'categorization.categories.all',
  1 => 'trademarks.store',
  2 => 'trademarks.update',
  3 => 'workflow.processes.settings',
  4 => 'workflow.processes.subprocesses.settings',
  5 => 'workflow.processes.subprocesses.operations.settings',
)],
        12 => ['name' => 'Subcategorías', 'permissions' => array (
  0 => 'categorization.categories.subcategories.all',
  1 => 'categorization.categories.all',
  2 => 'workflow.processes.settings',
  3 => 'workflow.processes.subprocesses.settings',
  4 => 'workflow.processes.subprocesses.operations.settings',
)],
        13 => ['name' => 'Grupos', 'permissions' => array (
  0 => 'classification.groups.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        14 => ['name' => 'Subgrupos', 'permissions' => array (
  0 => 'classification.subgroups.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        15 => ['name' => 'Marcas', 'permissions' => array (
  0 => 'trademarks.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        16 => ['name' => 'Tallas', 'permissions' => array (
  0 => 'sizes.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
  4 => 'trademarks.find',
)],
        17 => ['name' => 'Tipos de Prenda', 'permissions' => array (
  0 => 'garment_types.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        18 => ['name' => 'Tipos de Bota', 'permissions' => array (
  0 => 'boot_types.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        19 => ['name' => 'Tipos de Cotilla', 'permissions' => array (
  0 => 'yoke_types.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        20 => ['name' => 'Tipos de Trasero', 'permissions' => array (
  0 => 'back_types.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        21 => ['name' => 'Tipos de Pretina', 'permissions' => array (
  0 => 'waistband_types.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        22 => ['name' => 'Colores', 'permissions' => array (
  0 => 'colors.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        23 => ['name' => 'Tonos de Lavado', 'permissions' => array (
  0 => 'wash_tones.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        24 => ['name' => 'Procesos', 'permissions' => array (
  0 => 'workflow.processes.all',
  1 => 'users.workflow.assign',
  2 => 'employees.store',
  3 => 'employees.update',
)],
        25 => ['name' => 'Subprocesos', 'permissions' => array (
  0 => 'workflow.processes.subprocesses.all',
)],
        26 => ['name' => 'Operaciones', 'permissions' => array (
  0 => 'workflow.processes.subprocesses.operations.all',
)],
        27 => ['name' => 'Tipos de Proveedores', 'permissions' => array (
  0 => 'typification.supplier_types.all',
)],
        28 => ['name' => 'Tipos de Insumo', 'permissions' => array (
  0 => 'typification.supply_types.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        29 => ['name' => 'Insumos', 'permissions' => array (
  0 => 'typification.supply_types.supplies.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        30 => ['name' => 'Colección', 'permissions' => array (
  0 => 'collections.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        31 => ['name' => 'Piezas', 'permissions' => array (
  0 => 'pieces.all',
  1 => 'workflow.processes.settings',
  2 => 'workflow.processes.subprocesses.settings',
  3 => 'workflow.processes.subprocesses.operations.settings',
)],
        35 => ['name' => 'Tipos de Personas', 'permissions' => array (
  0 => 'typification.person_types.all',
)],
        36 => ['name' => 'Tipos de Documentos', 'permissions' => array (
  0 => 'typification.person_types.document_types.all',
)],
        37 => ['name' => 'Proveedores', 'permissions' => array (
  0 => 'typification.supplier_types.suppliers.all',
  1 => 'typification.supply_types.supplies.store',
  2 => 'typification.supply_types.supplies.update',
  3 => 'technical_sheets.production_orders.store',
  4 => 'technical_sheets.production_orders.update',
  5 => 'management.production.save',
)],
        38 => ['name' => 'Bancos', 'permissions' => array (
  0 => 'banks.all',
)],
        39 => ['name' => 'Tipos de Cuenta', 'permissions' => array (
  0 => 'account_types.all',
  1 => 'banks.settings',
)],
        40 => ['name' => 'Reportes', 'permissions' => array (
  0 => 'reports.all',
)],
        41 => ['name' => 'Tipologias', 'permissions' => array (
  0 => 'typification.typologies.all',
)],
        42 => ['name' => 'Tipos de Parada', 'permissions' => array (
  0 => 'stop_types.all',
)],
        43 => ['name' => 'Parada', 'permissions' => array (
  0 => 'stop_types.stops.all',
  1 => 'productivity.workstation',
)],
        44 => ['name' => 'Reglas', 'permissions' => array (
  0 => 'rules.all',
)],
    ],

    'tables' => [
        'users' => array (
  0 => 'users.all',
),
        'employees' => array (
  0 => 'employees.all',
  1 => 'users.store',
  2 => 'workflow.processes.settings',
  3 => 'workflow.processes.subprocesses.settings',
  4 => 'workflow.processes.subprocesses.operations.settings',
),
        'people' => array (
  0 => 'people.all',
  1 => 'employees.store',
  2 => 'employees.update',
),
        'files' => array (
  0 => 'people.all',
  1 => 'employees.store',
  2 => 'employees.update',
  3 => 'users.all',
),
        'roles' => array (
  0 => 'authorization.roles.all',
  1 => 'navegation.modules.submodules.store',
  2 => 'navegation.modules.submodules.update',
  3 => 'users.authorization.assign',
  4 => 'users.authorization.remove',
  5 => 'authorization.permissions.store',
  6 => 'authorization.permissions.update',
  7 => 'organizational_structure.areas.positions.authorization.assign',
  8 => 'organizational_structure.areas.positions.authorization.remove',
),
        'permissions' => array (
  0 => 'authorization.permissions.all',
),
        'modules' => array (
  0 => 'navegation.modules.all',
),
        'submodules' => array (
  0 => 'navegation.modules.submodules.all',
),
        'countries' => array (
  0 => 'locations.countries.all',
  1 => 'people.store',
  2 => 'people.update',
),
        'departments' => array (
  0 => 'locations.countries.all',
  1 => 'people.store',
  2 => 'people.update',
),
        'cities' => array (
  0 => 'locations.countries.all',
  1 => 'people.store',
  2 => 'people.update',
),
        'continents' => array (
  0 => 'locations.countries.all',
  1 => 'people.store',
  2 => 'people.update',
),
        'regions' => array (
  0 => 'locations.countries.all',
  1 => 'people.store',
  2 => 'people.update',
),
        'movements' => array (
  0 => 'movements.all',
),
        'movement_details' => array (
  0 => 'movements.all',
),
        'movement_detail_quantities' => array (
  0 => 'movements.all',
),
        'movement_detail_redistributions' => array (
  0 => 'movements.all',
),
        'production_order_details' => array (
  0 => 'movements.production_order_details.all',
  1 => 'movements.find',
  2 => 'movements.save',
  3 => 'products.all',
),
        'production_order_detail_quantities' => array (
  0 => 'movements.production_order_details.all',
  1 => 'movements.find',
  2 => 'movements.save',
  3 => 'products.all',
),
        'production_orders' => array (
  0 => 'technical_sheets.production_orders.all',
  1 => 'products.all',
),
        'products' => array (
  0 => 'products.all',
),
        'technical_sheets' => array (
  0 => 'products.all',
),
        'technical_sheet_details' => array (
  0 => 'products.all',
),
        'model_has_product' => array (
  0 => 'products.all',
),
        'model_has_production_order' => array (
  0 => 'technical_sheets.production_orders.all',
),
        'productivity' => array (
  0 => 'productivity.all',
),
        'productivity_details' => array (
  0 => 'productivity.find',
),
        'productivity_detail_models' => array (
  0 => 'productivity.find',
),
        'model_has_roles' => array (
  0 => 'users.all',
  1 => 'authorization.roles.all',
  2 => 'navegation.modules.submodules.store',
  3 => 'navegation.modules.submodules.update',
  4 => 'users.authorization.assign',
  5 => 'users.authorization.remove',
  6 => 'authorization.permissions.store',
  7 => 'authorization.permissions.update',
  8 => 'organizational_structure.areas.positions.authorization.assign',
  9 => 'organizational_structure.areas.positions.authorization.remove',
),
        'model_has_permissions' => array (
  0 => 'users.all',
  1 => 'authorization.permissions.all',
),
        'role_has_permissions' => array (
  0 => 'authorization.roles.all',
  1 => 'navegation.modules.submodules.store',
  2 => 'navegation.modules.submodules.update',
  3 => 'users.authorization.assign',
  4 => 'users.authorization.remove',
  5 => 'authorization.permissions.store',
  6 => 'authorization.permissions.update',
  7 => 'organizational_structure.areas.positions.authorization.assign',
  8 => 'organizational_structure.areas.positions.authorization.remove',
),
        'model_has_subitems' => array (
  0 => 'categorization.categories.all',
  1 => 'trademarks.store',
  2 => 'trademarks.update',
  3 => 'workflow.processes.settings',
  4 => 'workflow.processes.subprocesses.settings',
  5 => 'workflow.processes.subprocesses.operations.settings',
  6 => 'movements.all',
  7 => 'movements.production_order_details.all',
  8 => 'movements.find',
  9 => 'movements.save',
  10 => 'organizational_structure.areas.all',
  11 => 'employees.store',
  12 => 'employees.update',
  13 => 'products.all',
  14 => 'rules.all',
  15 => 'trademarks.all',
  16 => 'typification.file_types.all',
  17 => 'typification.file_types.file_subtypes.all',
  18 => 'typification.person_types.all',
  19 => 'typification.supply_types.all',
  20 => 'typification.supply_types.supplies.all',
  21 => 'typification.typologies.all',
  22 => 'workflow.processes.all',
  23 => 'users.workflow.assign',
  24 => 'workflow.processes.subprocesses.all',
),
        'audits' => array (
  0 => 'audits.all',
),
    ],

];
