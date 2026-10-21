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
use App\Models\Silhouette;
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
                'label' => 'Marcas',
                'model' => Trademark::class,
                'url' => '/trademarks/all',
                'value' => 'id',
                'option' => 'name',
            ],
            'silhouettes' => [
                'label' => 'Siluetas',
                'model' => Silhouette::class,
                'url' => '/silhouettes/all',
                'option' => 'name',
            ],
            'sizes' => [
                'label' => 'Tallas',
                'model' => Size::class,
                'url' => '/sizes/all',
                'option' => 'name',
            ],
            'subgroups' => [
                'label' => 'Subgrupos',
                'model' => Subgroup::class,
                'url' => '/classification/subgroups/all',
                'option' => 'name',
            ],
            'groups' => [
                'label' => 'Grupos',
                'model' => Group::class,
                'url' => '/classification/groups/all',
                'option' => 'name',
            ],
            'garment_types' => [
                'label' => 'Tipos de Prenda',
                'model' => GarmentType::class,
                'url' => '/garment_types/all',
                'option' => 'name',
            ],
            'boot_types' => [
                'label' => 'Tipos de Bota',
                'model' => BootType::class,
                'url' => '/boot_types/all',
                'option' => 'name',
            ],
            'back_types' => [
                'label' => 'Tipos de Trasero',
                'model' => BackType::class,
                'url' => '/back_types/all',
                'option' => 'name',
            ],
            'waistband_types' => [
                'label' => 'Tipos de Pretina',
                'model' => WaistbandType::class,
                'url' => '/waistband_types/all',
                'option' => 'name',
            ],
            'categories' => [
                'label' => 'Categorías',
                'model' => Category::class,
                'url' => '/categorization/categories/all',
                'option' => 'name',
            ],
            'subcategories' => [
                'label' => 'Subcategorías',
                'model' => Subcategory::class,
                'url' => '/categorization/categories/subcategories/all/{category_id}',
                'option' => 'name',
                'field' => 'subcategory_id',
                'param' => Category::class,
            ],
            'wash_tones' => [
                'label' => 'Tonos de Lavado',
                'model' => WashTone::class,
                'url' => '/wash_tones/all',
                'option' => 'name',
            ],
            'colors' => [
                'label' => 'Colores',
                'model' => Color::class,
                'url' => '/colors/all',
                'field' => 'color_id',
                'option' => ['settings.code', 'name'],
            ],
            'collections' => [
                'label' => 'Colecciones',
                'model' => Collection::class,
                'url' => '/collections/all',
                'option' => 'name',
            ],
            'yoke_types' => [
                'label' => 'Tipos de Cotilla',
                'model' => YokeType::class,
                'url' => '/yoke_types/all',
                'option' => 'name',
            ],
            'pieces' => [
                'label' => 'Piezas',
                'model' => Piece::class,
                'url' => '/pieces/all',
                'option' => 'name',
                'field' => 'piece_id',
            ],
            'supplier_types' => [
                'label' => 'Tipo de Proveedor',
                'model' => SupplierType::class,
                'url' => '/typification/supplier_types/all',
                'field' => 'supplier_type_id',
                'option' => 'name',
            ],
            'supply_types' => [
                'label' => 'Tipo de Insumo',
                'model' => SupplyType::class,
                'url' => '/typification/supply_types/all',
                'option' => 'name',
                'field' => 'supply_type_id',
            ],
            'employees' => [
                'label' => 'Empleados',
                'model' => Employee::class,
                'url' => '/employees/all',
                'value' => 'id',
                'option' => ['person.names', 'person.last_names'],
            ],
            'supplies' => [
                'label' => 'Insumos',
                'model' => Supply::class,
                'url' => '/typification/supply_types/supplies/all/{supply_type_id}',
                'option' => 'name',
                'field' => 'supply_id',
                'param' => SupplyType::class,
                'params' => [
                    Color::class,
                ],
                'columns' => [
                    'description' => 'Descripcion',
                    'color' => 'Color',
                    'supplier' => 'Proveedor',
                ],
            ],
            'suppliers' => [
                'label' => 'Proveedores',
                'model' => Supplier::class,
                'url' => '/typification/supplier_types/suppliers/all/{supplier_type_id}',
                'option' => 'name',
                'field' => 'supplier_id',
                'param' => SupplierType::class,
            ],
        ];
    }

    public function getModelTypesFromSupply()
    {
        return [
            'trademarks' => [
                'label' => 'Marcas',
                'model' => Trademark::class,
                'url' => '/trademarks/all',
                'value' => 'id',
                'option' => 'name',
            ],
            'silhouettes' => [
                'label' => 'Siluetas',
                'model' => Silhouette::class,
                'url' => '/silhouettes/all',
                'option' => 'name',
            ],
            'sizes' => [
                'label' => 'Tallas',
                'model' => Size::class,
                'url' => '/sizes/all',
                'option' => 'name',
            ],
            'subgroups' => [
                'label' => 'Subgrupos',
                'model' => Subgroup::class,
                'url' => '/classification/subgroups/all',
                'option' => 'name',
            ],
            'groups' => [
                'label' => 'Grupos',
                'model' => Group::class,
                'url' => '/classification/groups/all',
                'option' => 'name',
            ],
            'garment_types' => [
                'label' => 'Tipos de Prenda',
                'model' => GarmentType::class,
                'url' => '/garment_types/all',
                'option' => 'name',
            ],
            'boot_types' => [
                'label' => 'Tipos de Bota',
                'model' => BootType::class,
                'url' => '/boot_types/all',
                'option' => 'name',
            ],
            'back_types' => [
                'label' => 'Tipos de Trasero',
                'model' => BackType::class,
                'url' => '/back_types/all',
                'option' => 'name',
            ],
            'waistband_types' => [
                'label' => 'Tipos de Pretina',
                'model' => WaistbandType::class,
                'url' => '/waistband_types/all',
                'option' => 'name',
            ],
            'categories' => [
                'label' => 'Categorías',
                'model' => Category::class,
                'url' => '/categorization/categories/all',
                'option' => 'name',
            ],
            'subcategories' => [
                'label' => 'Subcategorías',
                'model' => Subcategory::class,
                'url' => '/categorization/subcategories/all',
                'option' => 'name',
            ],
            'wash_tones' => [
                'label' => 'Tonos de Lavado',
                'model' => WashTone::class,
                'url' => '/wash_tones/all',
                'option' => 'name',
            ],
            'colors' => [
                'label' => 'Colores',
                'model' => Color::class,
                'url' => '/colors/all',
                'field' => 'color_id',
                'option' => ['settings.code', 'name'],
            ],
            'collections' => [
                'label' => 'Colecciones',
                'model' => Collection::class,
                'url' => '/collections/all',
                'option' => 'name',
            ],
            'yoke_types' => [
                'label' => 'Tipos de Cotilla',
                'model' => YokeType::class,
                'url' => '/yoke_types/all',
                'option' => 'name',
            ],
            'pieces' => [
                'label' => 'Piezas',
                'model' => Piece::class,
                'url' => '/pieces/all',
                'option' => 'name',
                'field' => 'piece_id',
            ],
            'suppliers' => [
                'label' => 'Proveedores',
                'model' => Supplier::class,
                'url' => '/suppliers/all',
                'field' => 'supplier_id',
                'option' => 'name',
            ],
            'employees' => [
                'label' => 'Empleados',
                'model' => Employee::class,
                'url' => '/employees/all',
                'value' => 'id',
                'option' => ['person.names', 'person.last_names'],
            ],
        ];
    }

    public function getModelTypesFromReports()
    {
        return [
            'trademarks' => [
                'label' => 'Marcas',
                'model' => Trademark::class,
                'url' => '/trademarks/all',
                'value' => 'id',
                'option' => 'name',
            ],
            'subgroups' => [
                'label' => 'Subgrupos',
                'model' => Subgroup::class,
                'url' => '/classification/subgroups/all',
                'option' => 'name',
            ],
            'groups' => [
                'label' => 'Grupos',
                'model' => Group::class,
                'url' => '/classification/groups/all',
                'option' => 'name',
            ],
            'garment_types' => [
                'label' => 'Tipos de Prenda',
                'model' => GarmentType::class,
                'url' => '/garment_types/all',
                'option' => 'name',
            ],
            'boot_types' => [
                'label' => 'Tipos de Bota',
                'model' => BootType::class,
                'url' => '/boot_types/all',
                'option' => 'name',
            ],
            'back_types' => [
                'label' => 'Tipos de Trasero',
                'model' => BackType::class,
                'url' => '/back_types/all',
                'option' => 'name',
            ],
            'waistband_types' => [
                'label' => 'Tipos de Pretina',
                'model' => WaistbandType::class,
                'url' => '/waistband_types/all',
                'option' => 'name',
            ],
            'categories' => [
                'label' => 'Categorías',
                'model' => Category::class,
                'url' => '/categorization/categories/all',
                'option' => 'name',
            ],
            'subcategories' => [
                'label' => 'Subcategorías',
                'model' => Subcategory::class,
                'url' => '/categorization/categories/subcategories/all/{category_id}',
                'option' => 'name',
                'field' => 'subcategory_id',
                'param' => Category::class,
            ],
            'wash_tones' => [
                'label' => 'Tonos de Lavado',
                'model' => WashTone::class,
                'url' => '/wash_tones/all',
                'option' => 'name',
            ],
            'colors' => [
                'label' => 'Colores',
                'model' => Color::class,
                'url' => '/colors/all',
                'field' => 'color_id',
                'option' => ['settings.code', 'name'],
            ],
            'collections' => [
                'label' => 'Colecciones',
                'model' => Collection::class,
                'url' => '/collections/all',
                'option' => 'name',
            ],
            'yoke_types' => [
                'label' => 'Tipos de Cotilla',
                'model' => YokeType::class,
                'url' => '/yoke_types/all',
                'option' => 'name',
            ],
            'pieces' => [
                'label' => 'Piezas',
                'model' => Piece::class,
                'url' => '/pieces/all',
                'option' => 'name',
                'field' => 'piece_id',
            ],
            'suppliers' => [
                'label' => 'Proveedores',
                'model' => Supplier::class,
                'url' => '/suppliers/all',
                'field' => 'supplier_id',
                'option' => 'name',
            ],
            'supply_types' => [
                'label' => 'Tipos de Insumo',
                'model' => SupplyType::class,
                'url' => '/typification/supply_types/all',
                'option' => 'name',
                'field' => 'supply_type_id',
            ],
            'employees' => [
                'label' => 'Empleados',
                'model' => Employee::class,
                'url' => '/employees/all',
                'value' => 'id',
                'option' => ['person.names', 'person.last_names'],
            ],
            'supplies' => [
                'label' => 'Insumos',
                'model' => Supply::class,
                'url' => '/typification/supply_types/supplies/all/{supply_type_id}',
                'option' => 'name',
                'field' => 'supply_id',
                'param' => SupplyType::class,
                'params' => [
                    Color::class,
                ],
                'columns' => [
                    'description' => 'Descripcion',
                    'color' => 'Color',
                    'supplier' => 'Proveedor',
                ],
            ],
            'processes' => [
                'label' => 'Procesos',
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
                'label' => 'Marca',
                'short' => 'MA',
                'model' => Trademark::class,
                'url' => '/trademarks/all',
                'value' => 'id',
                'option' => 'name',
                'show' => true,
            ],
            'subgroup' => [
                'label' => 'Subgrupo',
                'short' => 'SG',
                'model' => Subgroup::class,
                'url' => '/classification/subgroups/all',
                'option' => 'name',
                'show' => true,
            ],
            'group' => [
                'label' => 'Grupo',
                'short' => 'GR',
                'model' => Group::class,
                'url' => '/classification/groups/all',
                'option' => 'name',
                'show' => true,
            ],
            'garment_type' => [
                'label' => 'Tipo de Prenda',
                'short' => 'TP',
                'model' => GarmentType::class,
                'url' => '/garment_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'boot_type' => [
                'label' => 'Tipo de Bota',
                'short' => 'TB',
                'model' => BootType::class,
                'url' => '/boot_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'back_type' => [
                'label' => 'Tipo de Trasero',
                'short' => 'TT',
                'model' => BackType::class,
                'url' => '/back_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'waistband_type' => [
                'label' => 'Tipo de Pretina',
                'short' => 'PR',
                'model' => WaistbandType::class,
                'url' => '/waistband_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'category' => [
                'label' => 'Categoría',
                'short' => 'CA',
                'model' => Category::class,
                'url' => '/categorization/categories/all',
                'option' => 'name',
                'show' => true,
            ],
            'subcategory' => [
                'label' => 'Subcategoría',
                'short' => 'SC',
                'model' => Subcategory::class,
                'url' => '/categorization/categories/subcategories/all/{category_id}',
                'option' => 'name',
                'field' => 'subcategory_id',
                'param' => 'category',
                'show' => true,
            ],
            'wash_tone' => [
                'label' => 'Tono de Lavado',
                'short' => 'TL',
                'model' => WashTone::class,
                'url' => '/wash_tones/all',
                'option' => 'name',
                'show' => true,
            ],
            'color' => [
                'label' => 'Color',
                'short' => 'CO',
                'model' => Color::class,
                'url' => '/colors/all',
                'field' => 'color_id',
                'option' => ['settings.code', 'name'],
                'show' => true,
            ],
            'collection' => [
                'label' => 'Colección',
                'short' => 'CL',
                'model' => Collection::class,
                'url' => '/collections/all',
                'option' => 'name',
                'show' => true,
            ],
            'yoke_type' => [
                'label' => 'Tipo de Cotilla',
                'short' => 'TC',
                'model' => YokeType::class,
                'url' => '/yoke_types/all',
                'option' => 'name',
                'show' => true,
            ],
            'process' => [
                'label' => 'Proceso',
                'short' => 'PC',
                'model' => Process::class,
                'url' => '/workflow/processes/all',
                'value' => 'id',
                'option' => 'name',
                'show' => false,
            ],
            'subprocess' => [
                'label' => 'Subproceso',
                'short' => 'SP',
                'model' => Subprocess::class,
                'url' => '/workflow/processes/subprocess/all',
                'value' => 'id',
                'option' => 'name',
                'show' => false,
                'nested' => 'subprocesses',
            ],
            'operation' => [
                'label' => 'Operación',
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
