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
use App\Models\Piece;
use App\Models\Process;
use App\Models\Silhouette;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Supplier;
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
}
