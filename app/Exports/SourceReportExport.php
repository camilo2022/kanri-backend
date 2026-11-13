<?php

namespace App\Exports;

use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Excel de los reportes con filas calculadas (settings.source: trazabilidad, productividad): el mismo formato de
 * ReportExport (no se modifica) más la agrupación de filas de Excel (+ / −): los renglones de cada proceso en el nivel 2,
 * el subtotal del proceso en el 1 y la franja de la referencia y su total sin agrupar, con el resumen debajo.
 */
class SourceReportExport extends ReportExport
{
    /**
     * @param  array<int, int>  $levels  Nivel de agrupación de cada fila de datos (0 = sin agrupar)
     */
    public function __construct(array $data, array $fields, protected array $levels)
    {
        parent::__construct($data, $fields);
    }

    public function registerEvents(): array
    {
        $events = parent::registerEvents();
        $format = $events[AfterSheet::class];

        $events[AfterSheet::class] = function (AfterSheet $event) use ($format) {
            $format($event);

            $sheet = $event->sheet->getDelegate();

            foreach ($this->levels as $index => $level) {
                if ($level > 0) {
                    $sheet->getRowDimension($index + 2)->setOutlineLevel($level);
                }
            }

            $sheet->setShowSummaryBelow(true);
        };

        return $events;
    }
}
