<?php

namespace App\Imports;

use App\Models\SupplyType;
use Illuminate\Support\Facades\Storage;

class ImportErrorReport
{
    public function generate(array $errors, array $variants, SupplyType $supply_type): string
    {
        $lines = [];

        $fields = collect($supply_type->settings->form ?? [])
            ->keyBy('field');

        $lines[] = str_repeat('=', 80);
        $lines[] = 'REPORTE DE ERRORES DE IMPORTACIÓN';
        $lines[] = str_repeat('=', 80);
        $lines[] = '';

        /**
         * Agrupar errores por fila
         */
        $grouped = [];

        foreach ($errors as $attribute => $messages) {

            if (!preg_match('/variants\.(\d+)\.(.+)/', $attribute, $matches)) {
                continue;
            }

            $index = (int) $matches[1];
            $path = $matches[2];

            $grouped[$index][] = [
                'path' => $path,
                'messages' => $messages,
            ];
        }

        /**
         * Construcción del TXT
         */
        foreach ($grouped as $index => $attributes) {

            $lines[] = str_repeat('=', 80);
            $lines[] = 'FILA ' . ($index + 2);
            $lines[] = str_repeat('=', 80);
            $lines[] = '';

            foreach ($attributes as $attribute) {

                $path = $attribute['path'];

                $field = str_replace(
                    ['class.', 'values.'],
                    '',
                    $path
                );

                $label = $fields[$field]->label ?? $field;

                $value = data_get(
                    $variants[$index],
                    $path
                );

                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                if ($value === null || $value === '') {
                    $value = '-';
                }

                $lines[] = "Campo : {$label}";
                $lines[] = "Valor : {$value}";
                $lines[] = "Errores:";

                foreach ($attribute['messages'] as $message) {
                    $lines[] = "  • {$message}";
                }

                $lines[] = '';
            }

            $lines[] = '';
        }

        $filename = 'imports/errors_' . now()->format('Ymd_His') . '.txt';

        Storage::disk('public')->put(
            $filename,
            implode(PHP_EOL, $lines)
        );

        return $filename;
    }
}
