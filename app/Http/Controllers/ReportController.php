<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Exports\SourceReportExport;
use App\Http\Requests\Report\ReportAllRequest;
use App\Http\Requests\Report\ReportDeleteRequest;
use App\Http\Requests\Report\ReportExportRequest;
use App\Http\Requests\Report\ReportFindRequest;
use App\Http\Requests\Report\ReportGenerateRequest;
use App\Http\Requests\Report\ReportRestoreRequest;
use App\Http\Requests\Report\ReportStoreRequest;
use App\Http\Requests\Report\ReportUpdateRequest;
use App\Http\Resources\Report\DataCollection;
use App\Http\Resources\Report\ReportCollection;
use App\Http\Resources\Report\ReportResource;
use App\Models\Report;
use App\Services\ProductivityReportService;
use App\Services\TraceabilityReportService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Endpoints para gestionar reportes"
 * )
 *
 * @OA\PathItem(
 *     path="/reports",
 *     description="Rutas de gestión de reportes"
 * )
 *
 * @OA\Schema(
 *     schema="Report",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="item_id", type="integer", example=21),
 *     @OA\Property(property="name", type="string", example="No Aplica"),
 *     @OA\Property(property="description", type="string", example="No Aplica"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Reportes"),
 *         @OA\Property(property="description", type="string", example="Listado de reportes."),
 *         @OA\Property(property="settings", type="string", example="{}"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 */
class ReportController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/reports/all",
     *     tags={"Reports"},
     *     summary="Listar los reportes",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filtro de Busqueda.",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="juan")
     *     ),
     *
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         description="Columna a ordenar.",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"id","name","description","created_at", "updated_at"}, example="name")
     *     ),
     *
     *     @OA\Parameter(
     *         name="dir",
     *         in="query",
     *         description="Dirección de ordenamiento.",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"asc","desc"}, example="asc")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Registros por página.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Parameter(
     *         name="with_trashed",
     *         in="query",
     *         description="Registros inactivos.",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de reportes cargada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="reports",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Report")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(
     *                         property="pagination",
     *                         type="object",
     *                         @OA\Property(property="total", type="integer", example=1),
     *                         @OA\Property(property="count", type="integer", example=1),
     *                         @OA\Property(property="per_page", type="integer", example=1),
     *                         @OA\Property(property="current_page", type="integer", example=1),
     *                         @OA\Property(property="total_pages", type="integer", example=1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function all(ReportAllRequest $request)
    {
        try {
            $reports = Report::with(['item', 'permissions'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $reports = $reports->paginate($request->integer('per_page', $reports->count()));

            return $this->successResponse(
                new ReportCollection($reports),
                $this->getMessage('Success'),
                200
            );

        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    private function apply_filter($query, string $data, string $data_key, $values, string $type = 'select')
    {
        $parts = explode('.', $data_key);

        $column = array_pop($parts);

        if ($type === 'date_range') {

            $from = $values['from'] ?? null;
            $to = $values['to'] ?? null;

            if (empty($from) && empty($to)) {
                return $query;
            }

            if (empty($parts)) {
                if ($from && $to) {
                    return $query->whereBetween($column, [
                        $from.' 00:00:00',
                        $to.' 23:59:59',
                    ]);
                }

                if ($from) {
                    return $query->where($column, '>=', $from.' 00:00:00');
                }

                if ($to) {
                    return $query->where($column, '<=', $to.' 23:59:59');
                }
            }

            $relation = implode('.', $parts);

            return $query->whereHas($relation, function ($q) use ($column, $from, $to) {
                if ($from && $to) {
                    $q->whereBetween($column, [
                        $from.' 00:00:00',
                        $to.' 23:59:59',
                    ]);
                } elseif ($from) {
                    $q->where($column, '>=', $from.' 00:00:00');
                } elseif ($to) {
                    $q->where($column, '<=', $to.' 23:59:59');
                }
            });
        }

        if ($data === 'processes') {
            // Camino hasta la ficha según el data_key: en fichas es directo; en detalles de orden pasa por
            // production_order.technical_sheet
            $sheet_path = array_values(array_diff($parts, ['technical_sheet_details']));
            $processes_relation = implode('.', [...$sheet_path, 'technical_sheet_details_processes']);

            $process_filters = collect($values)
                ->map(function ($value) {

                    [$process_id, $status] = explode('_', $value, 2);

                    return [
                        'process_id' => (int) $process_id,
                        'status' => $status,
                    ];
                })
                ->groupBy('process_id');
            foreach ($process_filters as $process_id => $filters) {

                $statuses = $filters
                    ->pluck('status')
                    ->values()
                    ->toArray();

                $query->whereHas(
                    $processes_relation,
                    function ($q) use ($process_id, $statuses) {

                        $q->where('model_id', $process_id)
                            ->whereIn('status', $statuses);
                    }
                );
            }

            return $query;
        }

        if (empty($parts)) {
            return $query->whereIn($column, $values);
        }

        $relation = implode('.', $parts);

        return $query->whereHas($relation, function ($q) use ($column, $values) {
            $q->whereIn($column, $values);
        });
    }

    /**
     * Filas de un reporte con settings.source (trazabilidad, productividad): las arma su servicio y aquí se proyectan a
     * las columnas elegidas, con la forma de DataCollection (data: [etiqueta => [value, style]], fields: etiquetas).
     */
    private function sourceRows(Report $report, $request): array
    {
        $labels = collect($report->settings->fields ?? [])
            ->flatMap(fn ($group) => collect(data_get($group, 'fields', [])))
            ->mapWithKeys(fn ($field) => [data_get($field, 'data_key') => data_get($field, 'label')]);
        // Las columnas dinámicas de tallas llegan como «id_nombre» (374_T04), igual que en los demás reportes
        $columns = collect($request->validated('fields', []))
            ->map(function ($item) use ($labels) {
                $key = data_get($item, 'data_key');

                return preg_match('/^(\d+)_(.+)$/', (string) $key, $size)
                    ? ['label' => $size[2], 'size' => (int) $size[1]]
                    : ($labels->has($key) ? ['label' => $labels[$key], 'key' => $key] : null);
            })
            ->filter()
            ->values();

        $rows = match ($report->settings->source) {
            'traceability' => app(TraceabilityReportService::class)->rows($request->validated('filters', [])),
            'productivity' => app(ProductivityReportService::class)->rows($request->validated('filters', [])),
        };

        return [
            'data' => collect($rows)
                ->map(fn ($row) => $columns->mapWithKeys(fn ($column) => [$column['label'] => [
                    'value' => isset($column['size']) ? ($row['values']['sizes'][$column['size']] ?? null) : ($row['values'][$column['key']] ?? null),
                    'style' => $row['style'] ?? null,
                ]])->all())
                ->all(),
            'fields' => $columns->pluck('label')->all(),
            // Agrupación de filas en el Excel (+ / −): detalle 2, subtotal 1, franja y total 0
            'levels' => collect($rows)->map(fn ($row) => $row['level'] ?? 0)->all(),
        ];
    }

    public function generate(ReportGenerateRequest $request, $id)
    {
        try {
            $report = Report::findOrFail($id);

            // Reporte con filas calculadas (settings.source): las arma su servicio con la misma forma (data y fields)
            if ($report->settings->source ?? null) {
                return $this->successResponse($this->sourceRows($report, $request), $this->getMessage('Success'), 200);
            }

            $fields = $request->validated('fields', []);
            $filters = $request->validated('filters', []);

            $configured_fields = $report->settings->fields ?? [];

            $configured_filters = $report->settings->filters ?? [];

            $available_filters = collect($configured_filters)
                ->mapWithKeys(function ($filter, $key) {
                    return [
                        $key => [
                            'data' => $filter->data ?? null,
                            'data_key' => $filter->data_key ?? null,
                            'type' => $filter->type ?? 'select',
                        ],
                    ];
                })
                ->filter(fn ($filter) => ! empty($filter['data_key']))
                ->toArray();

            $query = $report->settings->model::query();

            $base_filters = $report->settings->base_filters ?? [];

            $dynamic_filters = $report->settings->dynamic_filters ?? [];

            foreach ($base_filters as $column => $config) {

                $operator = data_get($config, 'operator', '=');
                $value = data_get($config, 'value');

                $query->where($column, $operator, $value);
            }

            foreach ($dynamic_filters as $column => $config) {

                $operator = data_get($config, 'operator', '=');
                $value = $request->input($column);

                if ($value === null) {
                    continue;
                }

                $query->where($column, $operator, $value);
            }

            foreach ($filters as $filter_key => $values) {

                $filter_config = $available_filters[$filter_key];

                $query = $this->apply_filter(
                    $query,
                    $filter_config['data'],
                    $filter_config['data_key'],
                    $values,
                    $filter_config['type']
                );
            }

            $data = $query->get();

            $model_type = $request->input('model_type');
            $model_id = $request->input('model_id');

            return $this->successResponse(
                new DataCollection($data, $fields, $configured_fields, data_get($report->settings, 'context'), $model_type, $model_id),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function export(ReportExportRequest $request, $id)
    {
        try {
            $report = Report::findOrFail($id);

            // Reporte con filas calculadas (settings.source): mismas filas que generate, mismo Excel
            if ($report->settings->source ?? null) {
                $rows = $this->sourceRows($report, $request);

                return Excel::download(new SourceReportExport($rows['data'], $rows['fields'], $rows['levels']), 'report.xlsx');
            }

            $fields = $request->validated('fields', []);
            $filters = $request->validated('filters', []);

            $configured_fields = $report->settings->fields ?? [];

            $configured_filters = $report->settings->filters ?? [];

            $available_filters = collect($configured_filters)
                ->mapWithKeys(function ($filter, $key) {
                    return [
                        $key => [
                            'data' => $filter->data ?? null,
                            'data_key' => $filter->data_key ?? null,
                            'type' => $filter->type ?? 'select',
                        ],
                    ];
                })
                ->filter(fn ($filter) => ! empty($filter['data_key']))
                ->toArray();

            $query = $report->settings->model::query();

            $base_filters = $report->settings->base_filters ?? [];

            $dynamic_filters = $report->settings->dynamic_filters ?? [];

            foreach ($base_filters as $column => $config) {

                $operator = data_get($config, 'operator', '=');
                $value = data_get($config, 'value');

                $query->where($column, $operator, $value);
            }

            foreach ($dynamic_filters as $column => $config) {

                $operator = data_get($config, 'operator', '=');
                $value = $request->input($column);

                if ($value === null) {
                    continue;
                }

                $query->where($column, $operator, $value);
            }

            foreach ($filters as $filter_key => $values) {

                $filter_config = $available_filters[$filter_key];

                $query = $this->apply_filter($query, $filter_config['data'], $filter_config['data_key'], $values, $filter_config['type']);
            }

            $data = $query->get();

            $model_type = $request->input('model_type');
            $model_id = $request->input('model_id');

            $data_export = (new DataCollection($data, $fields, $configured_fields, data_get($report->settings, 'context'), $model_type, $model_id))
                ->toArray($request);

            return Excel::download(new ReportExport($data_export['data']->toArray(), $data_export['fields']), 'report.xlsx');

        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/reports/find/{id}",
     *     tags={"Reports"},
     *     summary="Obtener un tipo de trasero específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de trasero",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero cargado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="report",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Report")
     *                 )
     *             ),
     *
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del tipo de trasero no registrado.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example=1)
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function find(ReportFindRequest $request, $id)
    {
        try {
            $report = Report::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new ReportResource($report),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/reports/store",
     *     tags={"Reports"},
     *     summary="Crear un tipo de trasero",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","description"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="BOLSILLO",
     *                 description="Nombre del Tipo de Trasero"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="BOLSILLO",
     *                 description="Descripción del Tipo de Trasero"
     *              )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero creado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="report",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="NAME_BACK_TYPE"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_BACK_TYPE"),
     *                     @OA\Property(property="item_id", type="integer", example=21),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="id", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del Área"),
     *                 @OA\Property(property="description", type="string", example="Descripción del Área")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="Formato Inválido. El campo debe estar en mayúsculas.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    /*public function store(ReportStoreRequest $request)
    {
        try {
            $report = new Report;
            $report->name = $request->input('name');
            $report->description = $request->input('description');
            $report->settings = $request->input('settings');

            $report->save();

            return $this->successResponse(
                new ReportResource($report),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }*/

    /**
     * @OA\Put(
     *     path="/reports/update/{id}",
     *     tags={"Reports"},
     *     summary="Editar un tipo de trasero específica",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de trasero",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="BOLSILLO",
     *                 description="Nombre del Tipo de Trasero"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="BOLSILLO CORAZON",
     *                 description="Descripción del Tipo de Trasero"
     *              )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero editado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="report",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_MODULE_EXAMPLE"),
     *                     @OA\Property(property="description", type="string", example="NAME_DESCRIPTION_EXAMPLE"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del Tipo de Trasero"),
     *                 @OA\Property(property="description", type="string", example="Descripción del Tipo de Trasero")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="Formato Inválido. El campo debe estar en mayúsculas.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    /*public function update(ReportUpdateRequest $request, $id)
    {
        try {
            $report = Report::findOrFail($id);
            $report->name = $request->input('name');
            $report->description = $request->input('description');
            $report->settings = $request->input('settings');

            $report->save();

            return $this->successResponse(
                new ReportResource($report),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }*/

    /**
     * @OA\Delete(
     *     path="/reports/delete/{id}",
     *     tags={"Reports"},
     *     summary="Desactivar un tipo de trasero específica",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de trasero",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero desactivado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="report",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=21),
     *                     @OA\Property(property="name", type="string", example="NAME_MODULE_EXAMPLE"),
     *                     @OA\Property(property="description", type="string", example="NAME_DESCRIPTION_EXAMPLE"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del tipo de trasero")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    /*public function delete(ReportDeleteRequest $request, $id)
    {
        try {
            $report = Report::findOrFail($id);
            $report->delete();

            return $this->successResponse(
                new ReportResource($report),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }*/

    /**
     * @OA\Patch(
     *     path="/reports/restore/{id}",
     *     tags={"Reports"},
     *     summary="Restaurar un tipo de trasero específica",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de trasero",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero restaurado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="report",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=21),
     *                     @OA\Property(property="name", type="string", example="NAME_MODULE_EXAMPLE"),
     *                     @OA\Property(property="description", type="string", example="NAME_DESCRIPTION_EXAMPLE"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del tipo de trasero")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    /*public function restore(ReportRestoreRequest $request, $id)
    {
        try {
            $report = Report::withTrashed()->findOrFail($id);
            $report->restore();

            return $this->successResponse(
                new ReportResource($report),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }*/
}
