<?php

namespace App\Http\Resources\TechnicalSheet;

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Employee;
use App\Models\FabricType;
use App\Models\GarmentType;
use App\Models\Group;
use App\Models\Silhouette;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Supplier;
use App\Models\SupplyType;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Models\ThreadType;
use App\Models\Trademark;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalSheetResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'technical_sheet' => [
                'id' => $this->id,
                'model_id' => $this->model_id,
                'model_type' => $this->model_type,
                'code' => $this->code,
                'date' => $this->date,
                'product_id' => $this->product_id,
                'collection_id' => $this->collection_id,
                'garment_type_id' => $this->garment_type_id,
                'subgroup_id' => $this->subgroup_id,
                'wash_tone_id' => $this->wash_tone_id,
                'color_id' => $this->color_id,
                'waistband_type_id' => $this->waistband_type_id,
                'measure_of_waistband'  => $this->measure_of_waistband,
                'boot_type_id'  => $this->boot_type_id,
                'yoke_type_id' => $this->yoke_type_id,
                'back_type_id' => $this->back_type_id,
                'physical_sample' => $this->physical_sample,
                'number_of_buttons' => $this->number_of_buttons,
                'description' => $this->description,
                'observation' => $this->observation,
                'status' => $this->status,
                'pattern_maker_id' => $this->pattern_maker_id,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'technical_sheet_details' => $this->technical_sheet_details,
                'pattern_maker' => $this->pattern_maker,
                'product' => $this->product,
                'collection' => $this->collection,
                'garment_type' => $this->garment_type,
                'subgroup' => $this->subgroup,
                'wash_tone' => $this->wash_tone,
                'color' => $this->color,
                'waistband_type' => $this->waistband_type,
                'boot_type' => $this->boot_type,
                'yoke_type' => $this->yoke_type,
                'back_type' => $this->back_type,
                'photo_d' => $this->photo_d,
                'photo_t' => $this->photo_t
            ],
            'status_technical_sheet' => TechnicalSheet::statuses(),
            'status_technical_sheet_detail' => TechnicalSheetDetail::statuses(),
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
            'option' => 'name'
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
            'url' => '/supply_types/all',
            'option' => 'name'
        ],
        'employees' => [
            'label' => 'Empleados',
            'model' => Employee::class,
            'url' => '/employees/all',
            'value' => 'id',
            'option' => ['person.names', 'person.last_names']
        ],
    ];
}
