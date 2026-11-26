<?php

namespace App\Traits;

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Employee;
use App\Models\GarmentType;
use App\Models\Group;
use App\Models\Operation;
use App\Models\Piece;
use App\Models\Process;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Subprocess;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\Supply;
use App\Models\SupplyType;
use App\Models\Trademark;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;

trait ModelTypes
{
    public function getModelTypes()
    {
        return [
            'trademarks' => [
                'label' => 'MARCAS',
                'model' => Trademark::class,
                'url' => '/trademarks/all',
                'value' => 'id',
                'option' => 'name',
                'field' => 'trademark_id',
            ],
            'sizes' => [
                'label' => 'TALLAS',
                'model' => Size::class,
                'url' => '/sizes/all',
                'option' => 'name',
            ],
            'subgroups' => [
                'label' => 'SUBGRUPOS',
                'model' => Subgroup::class,
                'url' => '/classification/subgroups/all',
                'option' => 'name',
            ],
            'groups' => [
                'label' => 'GRUPOS',
                'model' => Group::class,
                'url' => '/classification/groups/all',
                'option' => 'name',
            ],
            'garment_types' => [
                'label' => 'TIPOS DE PRENDA',
                'model' => GarmentType::class,
                'url' => '/typification/garment_types/all',
                'option' => 'name',
            ],
            'boot_types' => [
                'label' => 'TIPOS DE BOTA',
                'model' => BootType::class,
                'url' => '/typification/boot_types/all',
                'option' => 'name',
            ],
            'back_types' => [
                'label' => 'TIPOS DE TRASERO',
                'model' => BackType::class,
                'url' => '/typification/back_types/all',
                'option' => 'name',
            ],
            'waistband_types' => [
                'label' => 'TIPOS DE PRETINA',
                'model' => WaistbandType::class,
                'url' => '/typification/waistband_types/all',
                'option' => 'name',
            ],
            'categories' => [
                'label' => 'CATEGORÍAS',
                'model' => Category::class,
                'url' => '/categorization/categories/all',
                'option' => 'name',
            ],
            'subcategories' => [
                'label' => 'SUBCATEGORÍAS',
                'model' => Subcategory::class,
                'url' => '/categorization/categories/subcategories/all/{category_id}',
                'option' => 'name',
                'field' => 'subcategory_id',
                'param' => Category::class,
            ],
            'wash_tones' => [
                'label' => 'TONOS DE LAVADO',
                'model' => WashTone::class,
                'url' => '/wash_tones/all',
                'option' => 'name',
                'field' => 'whash_tone_id',
            ],
            'colors' => [
                'label' => 'COLORES',
                'model' => Color::class,
                'url' => '/colors/all',
                'field' => 'color_id',
                'option' => ['settings.code', 'name'],
            ],
            'collections' => [
                'label' => 'COLECCIONES',
                'model' => Collection::class,
                'url' => '/collections/all',
                'option' => 'name',
            ],
            'yoke_types' => [
                'label' => 'TIPOS DE COTILLA',
                'model' => YokeType::class,
                'url' => '/typification/yoke_types/all',
                'option' => 'name',
            ],
            'pieces' => [
                'label' => 'PIEZAS',
                'model' => Piece::class,
                'url' => '/pieces/all',
                'option' => 'name',
                'field' => 'piece_id',
            ],
            'supplier_types' => [
                'label' => 'TIPO DE PROVEEDOR',
                'model' => SupplierType::class,
                'url' => '/typification/supplier_types/all',
                'field' => 'supplier_type_id',
                'option' => 'name',
            ],
            'supply_types' => [
                'label' => 'TIPO DE INSUMO',
                'model' => SupplyType::class,
                'url' => '/typification/supply_types/all?without_supplies=1',
                'option' => 'name',
                'field' => 'supply_type_id',
            ],
            'employees' => [
                'label' => 'EMPLEADOS',
                'model' => Employee::class,
                'url' => '/employees/all',
                'value' => 'id',
                'option' => ['person.names', 'person.last_names'],
            ],
            'supplies' => [
                'label' => 'INSUMOS',
                'model' => Supply::class,
                'url' => '/typification/supply_types/supplies/all/{supply_type_id}',
                'option' => 'name',
                'field' => 'supply_id',
                'param' => SupplyType::class,
                'params' => [
                    Color::class,
                ],
                'columns' => [
                    'description' => 'DESCRIPCIÓN',
                    'color' => 'COLOR',
                    'trademark' => 'MARCA',
                    'supplier' => 'PROVEEDOR',
                ],
                // Catálogo de cada columna que es una relación del insumo: si el valor guardado en una ficha ya no está entre
                // las relaciones de hoy (se quitó), su nombre se busca aquí
                'column_models' => [
                    'color' => Color::class,
                    'trademark' => Trademark::class,
                ],
            ],
            'subprocesses' => [
                'label' => 'SUBPROCESOS',
                'model' => Subprocess::class,
                'url' => '/workflow/processes/subprocesses/all?with_trashed=1',
                'option' => 'name',
                'field' => 'subprocess_id',
            ],
            'operations' => [
                'label' => 'OPERACIONES',
                'model' => Operation::class,
                'url' => '/workflow/processes/subprocesses/operations/all/{subprocess_id}?with_trashed=1',
                'option' => 'name',
                'field' => 'operation_id',
                'param' => Subprocess::class,
            ],
            'suppliers' => [
                'label' => 'PROVEEDORES',
                'model' => Supplier::class,
                'url' => '/typification/supplier_types/suppliers/all/{supplier_type_id}',
                'option' => 'name',
                'field' => 'supplier_id',
                'param' => SupplierType::class,
            ],
        ];
    }

    public function getModelTypesFromReports()
    {
        return [
            'trademarks' => [
                'label' => 'MARCAS',
                'model' => Trademark::class,
                'url' => '/trademarks/all',
                'value' => 'id',
                'option' => 'name',
            ],
            'subgroups' => [
                'label' => 'SUBGRUPOS',
                'model' => Subgroup::class,
                'url' => '/classification/subgroups/all',
                'option' => 'name',
            ],
            'groups' => [
                'label' => 'GRUPOS',
                'model' => Group::class,
                'url' => '/classification/groups/all',
                'option' => 'name',
            ],
            'garment_types' => [
                'label' => 'TIPOS DE PRENDA',
                'model' => GarmentType::class,
                'url' => '/typification/garment_types/all',
                'option' => 'name',
            ],
            'boot_types' => [
                'label' => 'TIPOS DE BOTA',
                'model' => BootType::class,
                'url' => '/typification/boot_types/all',
                'option' => 'name',
            ],
            'back_types' => [
                'label' => 'TIPOS DE TRASERO',
                'model' => BackType::class,
                'url' => '/typification/back_types/all',
                'option' => 'name',
            ],
            'waistband_types' => [
                'label' => 'TIPOS DE PRETINA',
                'model' => WaistbandType::class,
                'url' => '/typification/waistband_types/all',
                'option' => 'name',
            ],
            'categories' => [
                'label' => 'CATEGORÍAS',
                'model' => Category::class,
                'url' => '/categorization/categories/all',
                'option' => 'name',
            ],
            'subcategories' => [
                'label' => 'SUBCATEGORÍAS',
                'model' => Subcategory::class,
                'url' => '/categorization/categories/subcategories/all/{category_id}',
                'option' => 'name',
                'field' => 'subcategory_id',
                'param' => Category::class,
            ],
            'wash_tones' => [
                'label' => 'TONOS DE LAVADO',
                'model' => WashTone::class,
                'url' => '/wash_tones/all',
                'option' => 'name',
            ],
            'colors' => [
                'label' => 'COLORES',
                'model' => Color::class,
                'url' => '/colors/all',
                'field' => 'color_id',
                'option' => ['settings.code', 'name'],
            ],
            'collections' => [
                'label' => 'COLECCIONES',
                'model' => Collection::class,
                'url' => '/collections/all',
                'option' => 'name',
            ],
            'yoke_types' => [
                'label' => 'TIPOS DE COTILLA',
                'model' => YokeType::class,
                'url' => '/typification/yoke_types/all',
                'option' => 'name',
            ],
            'pieces' => [
                'label' => 'PIEZAS',
                'model' => Piece::class,
                'url' => '/pieces/all',
                'option' => 'name',
                'field' => 'piece_id',
            ],
            'suppliers' => [
                'label' => 'PROVEEDORES',
                'model' => Supplier::class,
                'url' => '/suppliers/all',
                'field' => 'supplier_id',
                'option' => 'name',
            ],
            'supply_types' => [
                'label' => 'TIPOS DE INSUMO',
                'model' => SupplyType::class,
                'url' => '/typification/supply_types/all?without_supplies=1',
                'option' => 'name',
                'field' => 'supply_type_id',
            ],
            'employees' => [
                'label' => 'EMPLEADOS',
                'model' => Employee::class,
                'url' => '/employees/all',
                'value' => 'id',
                'option' => ['person.names', 'person.last_names'],
            ],
            'supplies' => [
                'label' => 'INSUMOS',
                'model' => Supply::class,
                'url' => '/typification/supply_types/supplies/all/{supply_type_id}',
                'option' => 'name',
                'field' => 'supply_id',
                'param' => SupplyType::class,
                'params' => [
                    Color::class,
                ],
                'columns' => [
                    'description' => 'DESCRIPCIÓN',
                    'color' => 'COLOR',
                    'supplier' => 'PROVEEDOR',
                ],
            ],
            'processes' => [
                'label' => 'PROCESOS',
                'model' => Process::class,
                'url' => '/workflow/processes/all',
                'value' => 'id',
                'option' => 'name',
            ],
            'route_processes' => [
                'label' => 'PROCESOS',
                'model' => Process::class,
                'url' => '/workflow/processes/all',
                'value' => 'id',
                'option' => 'name',
            ],
        ];
    }

    public function getModelTypesFromRules()
    {
        return [
            'trademark' => [
                'label' => 'MARCA',
                'short' => 'MA',
                'model' => Trademark::class,
                'url' => '/trademarks/all',
                'value' => 'id',
                'option' => 'name',
                'show' => true,
            ],
            'subgroup' => [
                'label' => 'SUBGRUPO',
                'short' => 'SG',
                'model' => Subgroup::class,
                'url' => '/classification/subgroups/all',
                'option' => 'name',
                'show' => true,
            ],
            'group' => [
                'label' => 'GRUPO',
                'short' => 'GR',
                'model' => Group::class,
                'url' => '/classification/groups/all',
                'option' => 'name',
                'show' => true,
            ],
            'garment_type' => [
                'label' => 'TIPO DE PRENDA',
                'short' => 'TP',
                'model' => GarmentType::class,
                'url' => '/typification/garment_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'boot_type' => [
                'label' => 'TIPO DE BOTA',
                'short' => 'TB',
                'model' => BootType::class,
                'url' => '/typification/boot_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'back_type' => [
                'label' => 'TIPO DE TRASERO',
                'short' => 'TT',
                'model' => BackType::class,
                'url' => '/typification/back_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'waistband_type' => [
                'label' => 'TIPO DE PRETINA',
                'short' => 'PR',
                'model' => WaistbandType::class,
                'url' => '/typification/waistband_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'category' => [
                'label' => 'CATEGORÍA',
                'short' => 'CA',
                'model' => Category::class,
                'url' => '/categorization/categories/all',
                'option' => 'name',
                'show' => true,
            ],
            'subcategory' => [
                'label' => 'SUBCATEGORÍA',
                'short' => 'SC',
                'model' => Subcategory::class,
                'url' => '/categorization/categories/subcategories/all/{category_id}',
                'option' => 'name',
                'field' => 'subcategory_id',
                'param' => 'category',
                'show' => true,
            ],
            'wash_tone' => [
                'label' => 'TONO DE LAVADO',
                'short' => 'TL',
                'model' => WashTone::class,
                'url' => '/wash_tones/all',
                'option' => 'name',
                'show' => true,
            ],
            'color' => [
                'label' => 'COLOR',
                'short' => 'CO',
                'model' => Color::class,
                'url' => '/colors/all',
                'field' => 'color_id',
                'option' => ['settings.code', 'name'],
                'show' => true,
            ],
            'collection' => [
                'label' => 'COLECCIÓN',
                'short' => 'CL',
                'model' => Collection::class,
                'url' => '/collections/all',
                'option' => 'name',
                'show' => true,
            ],
            'yoke_type' => [
                'label' => 'TIPO DE COTILLA',
                'short' => 'TC',
                'model' => YokeType::class,
                'url' => '/typification/yoke_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'process' => [
                'label' => 'PROCESO',
                'short' => 'PC',
                'model' => Process::class,
                'url' => '/workflow/processes/all',
                'value' => 'id',
                'option' => 'name',
                'show' => false,
            ],
            'subprocess' => [
                'label' => 'SUBPROCESO',
                'short' => 'SP',
                'model' => Subprocess::class,
                'url' => '/workflow/processes/subprocess/all',
                'value' => 'id',
                'option' => 'name',
                'show' => false,
                'nested' => 'subprocesses',
            ],
            'operation' => [
                'label' => 'OPERACIÓN',
                'short' => 'OP',
                'model' => Operation::class,
                'url' => '/workflow/processes/subprocesses/operations/all',
                'value' => 'id',
                'option' => 'name',
                'show' => false,
                'nested' => 'operations',
            ],
        ];
    }
}
