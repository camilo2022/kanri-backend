<?php

namespace App\Http\Resources\Workflow\Operation;

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Category;
use App\Models\ClothingLine;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Employee;
use App\Models\FabricType;
use App\Models\GarmentType;
use App\Models\Group;
use App\Models\Piece;
use App\Models\Silhouette;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Supplier;
use App\Models\SupplyType;
use App\Models\ThreadType;
use App\Models\Trademark;
use App\Models\Variant;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'operation' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
            ],
            'model_types' => $this->model_types,
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    private $model_types = [
        'trademarks' => [
            'label' => 'Marcas',
            'model' => Trademark::class,
            'url' => '/trademarks/all',
            'value' => 'id',
            'option' => 'name'
        ],
        'silhouettes' => [
            'label' => 'Siluetas',
            'model' => Silhouette::class,
            'url' => '/silhouettes/all',
            'option' => 'name'
        ],
        'sizes' => [
            'label' => 'Tallas',
            'model' => Size::class,
            'url' => '/sizes/all',
            'option' => 'name'
        ],
        'subgroups' => [
            'label' => 'Subgrupos',
            'model' => Subgroup::class,
            'url' => '/classification/subgroups/all',
            'option' => 'name'
        ],
        'groups' => [
            'label' => 'Grupos',
            'model' => Group::class,
            'url' => '/classification/groups/all',
            'option' => 'name'
        ],
        'garment_types' => [
            'label' => 'Tipos de Prenda',
            'model' => GarmentType::class,
            'url' => '/garment_types/all',
            'option' => 'name'
        ],
        'boot_types' => [
            'label' => 'Tipos de Bota',
            'model' => BootType::class,
            'url' => '/boot_types/all',
            'option' => 'name'
        ],
        'back_types' => [
            'label' => 'Tipos de Trasero',
            'model' => BackType::class,
            'url' => '/back_types/all',
            'option' => 'name'
        ],
        'waistband_types' => [
            'label' => 'Tipos de Pretina',
            'model' => WaistbandType::class,
            'url' => '/waistband_types/all',
            'option' => 'name'
        ],
        'categories' => [
            'label' => 'Categorías',
            'model' => Category::class,
            'url' => '/categorization/categories/all',
            'option' => 'name'
        ],
        'subcategories' => [
            'label' => 'Subcategorías',
            'model' => Subcategory::class,
            'url' => '/categorization/subcategories/all',
            'option' => 'name'
        ],
        'wash_tones' => [
            'label' => 'Tonos de Lavado',
            'model' => WashTone::class,
            'url' => '/wash_tones/all',
            'option' => 'name'
        ],
        'colors' => [
            'label' => 'Colores',
            'model' => Color::class,
            'url' => '/colors/all',
            'option' => 'name',
            'field' => 'color_id',
        ],
        'collections' => [
            'label' => 'Colecciones',
            'model' => Collection::class,
            'url' => '/collections/all',
            'option' => 'name'
        ],
        'yoke_types' => [
            'label' => 'Tipos de Cotilla',
            'model' => YokeType::class,
            'url' => '/yoke_types/all',
            'option' => 'name'
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
        ],
        'fabric_types' => [
            'label' => 'Tipos de Tela',
            'model' => FabricType::class,
            'url' => '/fabric_types/all',
            'option' => 'name'
        ],
        'thread_types' => [
            'label' => 'Tipos de Hilo',
            'model' => ThreadType::class,
            'url' => '/thread_types/all',
            'option' => 'name'
        ],
        'supply_types' => [
            'label' => 'Tipos de Insumo',
            'model' => SupplyType::class,
            'url' => '/typification/supply_types/all',
            'option' => 'name',
            'field' => 'supply_type_id',
            'columns' => [
                'description' => 'Descripcion'
            ]
        ],
        'employees' => [
            'label' => 'Empleados',
            'model' => Employee::class,
            'url' => '/employees/all',
            'value' => 'id',
            'option' => ['person.names', 'person.last_names'],
            'field' => 'employee_id'
        ],
        'variants' => [
            'label' => 'Variantes',
            'model' => Variant::class,
            'url' => '/typification/variants/all/{supply_type_id}',
            'option' => 'name',
            'field' => 'variant_id',
            'param' => SupplyType::class,
            'params' => [
                Color::class
            ],
            'columns' => [
                'description' => 'Descripcion',
                'color_id' => 'Color',
                'supplier_id' => 'Proveedor'
            ]
        ]
    ];
}
