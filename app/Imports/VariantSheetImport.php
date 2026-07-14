<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VariantSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(protected VariantImportBuilder $builder) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {

            if ($row->every(fn($value) => blank($value) || $value === '-')) {
                continue;
            }

            $this->builder->addVariant(
                $row->toArray()
            );
        }
    }
}
