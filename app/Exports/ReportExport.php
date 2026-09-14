<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportExport implements FromArray, WithEvents, WithHeadings
{
    protected array $data;

    protected array $fields;

    public function __construct(array $data, array $fields)
    {
        $this->data = $data;
        $this->fields = $fields;
    }

    public function headings(): array
    {
        return $this->fields;
    }

    public function array(): array
    {
        return collect($this->data)
            ->map(function ($row) {
                return collect($this->fields)
                    ->map(function ($field) use ($row) {

                        $value = data_get($row, "{$field}.value");

                        logger()->debug('EXPORT VALUE', [
                            'field' => $field,
                            'value' => $value,
                            'type' => gettype($value),
                        ]);

                        return $value === null || $value === 0
                            ? ''
                            : $value;
                    })
                    ->toArray();
            })
            ->toArray();
    }

    private function applyCellStyle($sheet, string $cell, array $style): void
    {
        $excel_style = [];

        if (! empty($style['background'])) {
            $excel_style['fill'] = [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => ltrim($style['background'], '#'),
                ],
            ];
        }

        if (! empty($style['color'])) {
            $excel_style['font'] = [
                'color' => [
                    'rgb' => ltrim($style['color'], '#'),
                ],
            ];
        }

        $excel_style['alignment'] = [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ];

        $sheet
            ->getStyle($cell)
            ->applyFromArray($excel_style);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $sheet->setTitle('REPORTE');

                $last_column = $this->getLastColumn();
                $last_row = count($this->data) + 1;

                $sheet
                    ->getStyle("A1:{$last_column}1")
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => '24247F',
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => [
                                    'rgb' => 'D9DDE7',
                                ],
                            ],
                        ],
                    ]);

                if ($last_row >= 2) {
                    $sheet
                        ->getStyle("A2:{$last_column}{$last_row}")
                        ->applyFromArray([
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => [
                                        'rgb' => '000000',
                                    ],
                                ],
                            ],
                        ]);
                }

                foreach ($this->fields as $index => $field) {

                    $column = Coordinate::stringFromColumnIndex(
                        $index + 1
                    );

                    foreach ($this->data as $row_index => $row) {

                        $style = data_get($row, "{$field}.style");

                        if (empty($style)) {
                            continue;
                        }

                        $excel_row = $row_index + 2;

                        $this->applyCellStyle(
                            $sheet,
                            "{$column}{$excel_row}",
                            $style
                        );
                    }
                }

                foreach ($this->fields as $index => $field) {

                    $column = Coordinate::stringFromColumnIndex(
                        $index + 1
                    );

                    $sheet
                        ->getColumnDimension($column)
                        ->setWidth(20);
                }

                for ($row = 2; $row <= $last_row; $row++) {
                    $sheet
                        ->getRowDimension($row)
                        ->setRowHeight(25);
                }

                $sheet
                    ->getRowDimension(1)
                    ->setRowHeight(30);

                $sheet->freezePane('A2');

                if ($last_row >= 1) {
                    $sheet->setAutoFilter(
                        "A1:{$last_column}{$last_row}"
                    );
                }
            },
        ];
    }

    private function getLastColumn(): string
    {
        return Coordinate::stringFromColumnIndex(
            count($this->fields)
        );
    }
}
