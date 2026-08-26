<?php

namespace App\Imports;

use App\Models\SupplyType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SupplyImport implements WithMultipleSheets
{
    protected SupplyImportBuilder $builder;

    public function __construct(protected SupplyType $supply_type)
    {
        $this->builder = new SupplyImportBuilder($supply_type);
    }

    public function sheets(): array
    {
        $sheets = [
            'SUPPLIES' => new SupplySheetImport($this->builder),
        ];

        foreach ($this->multipleRelations() as $field) {

            $sheetName = str($field->model)
                ->classBasename()
                ->plural()
                ->upper()
                ->toString();

            $sheets[$sheetName] = new RelationSheetImport(
                $this->builder,
                $field
            );
        }

        return $sheets;
    }

    public function builder(): SupplyImportBuilder
    {
        return $this->builder;
    }


    protected function multipleRelations(): Collection
    {
        return collect($this->supply_type->settings->form ?? [])
            ->where('type', 'selectdinamic')
            ->where('cardinality', 'multiple');
    }
}
