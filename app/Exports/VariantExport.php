<?php

namespace App\Exports;

use App\Models\SupplyType;
use App\Models\Variant;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VariantExport implements WithMultipleSheets
{
    protected $supply_type;
    protected $variants;

    public function __construct($supply_type_id)
    {
        $this->supply_type = SupplyType::findOrFail($supply_type_id);
        $this->variants = Variant::with('supply_type')
            ->whereHas('supply_type', fn($r) => $r->where('id', $this->supply_type->id))
            ->get();
    }

    public function sheets(): array
    {
        $sheets = [
            new VariantSheet(
                $this->supply_type,
                $this->variants
            ),
        ];

        foreach ($this->supply_type->settings->form ?? [] as $field) {

            if (
                $field->type === 'selectdinamic' &&
                ($field->cardinality ?? '') === 'multiple'
            ) {
                $sheets[] = new RelationSheet(
                    $this->supply_type,
                    $field,
                    $this->variants
                );
            }
        }

        return $sheets;
    }
}
