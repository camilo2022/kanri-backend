<?php

namespace App\Exports;

use App\Models\SupplyType;
use App\Models\Supply;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SupplyExport implements WithMultipleSheets
{
    protected $supply_type;
    protected $supplies;

    public function __construct($supply_type_id)
    {
        $this->supply_type = SupplyType::findOrFail($supply_type_id);
        $this->supplies = Supply::with('supply_type')
            ->whereHas('supply_type', fn($r) => $r->where('id', $this->supply_type->id))
            ->get();
    }

    public function sheets(): array
    {
        $sheets = [
            new SupplySheet(
                $this->supply_type,
                $this->supplies
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
                    $this->supplies
                );
            }
        }

        return $sheets;
    }
}
