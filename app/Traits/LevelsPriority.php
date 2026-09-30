<?php

namespace App\Traits;

trait LevelsPriority
{
    public function getLevelsPriority()
    {
        return [
            [
                'value' => 1,
                'label' => 'Muy Baja',
                'color' => '#16A34A',
                'background' => '#DCFCE7',
            ],
            [
                'value' => 2,
                'label' => 'Baja',
                'color' => '#65A30D',
                'background' => '#ECFCCB',
            ],
            [
                'value' => 3,
                'label' => 'Media',
                'color' => '#CA8A04',
                'background' => '#FEF9C3',
            ],
            [
                'value' => 4,
                'label' => 'Alta',
                'color' => '#EA580C',
                'background' => '#FFEDD5',
            ],
            [
                'value' => 5,
                'label' => 'Muy alta',
                'color' => '#DC2626',
                'background' => '#FEE2E2',
            ],
        ];
    }

    public function getPriorityChecks(): array
    {
        return [
            'current_run' => [
                'value' => 'current_run',
                'label' => 'Correría actual',
            ],
            'supply' => [
                'value' => 'supply',
                'label' => 'Insumo',
            ],
            'sale' => [
                'value' => 'sale',
                'label' => 'Venta',
            ],
            'rolls' => [
                'value' => 'rolls',
                'label' => 'Rollos',
            ],
        ];
    }

    public function getPriorityRules(): array
    {
        return [
            [
                'value' => 1,
                'rules' => [
                    'minimum_checks' => 0,
                    'maximum_checks' => 1,
                ],

            ],
            [
                'value' => 2,
                'rules' => [
                    'minimum_checks' => 2,
                    'excluded_combinations' => [
                        ['current_run', 'sale'],
                    ],
                ],

            ],
            [
                'value' => 3,
                'rules' => [
                    'required' => [
                        'current_run',
                        'sale',
                    ],
                ],

            ],
            [
                'value' => 4,
                'rules' => [
                    'required' => [
                        'current_run',
                        'sale',
                    ],
                    'one_of' => [
                        'supply',
                        'rolls',
                    ],
                ],
            ],
            [
                'value' => 5,
                'rules' => [
                    'required' => [
                        'current_run',
                        'supply',
                        'sale',
                        'rolls',
                    ],
                ],
            ],
        ];
    }
}
