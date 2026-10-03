<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplySheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(protected SupplyImportBuilder $builder) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {

            if ($row->every(fn ($value) => blank($value) || $value === '-')) {
                continue;
            }

            $this->builder->addSupply(
                $row->toArray()
            );
        }
    }
}
