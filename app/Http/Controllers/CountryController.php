<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\Country\CountryAllRequest;
use App\Http\Requests\Location\Country\CountrySettingsRequest;
use App\Http\Resources\Location\Country\CountryCollection;
use App\Http\Resources\Location\Country\CountryResource;
use App\Models\Country;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="Country",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=47),
 *     @OA\Property(property="has_audits", type="boolean", example=false, description="Solo viene en la lista (`all`): el país tiene historial de auditoría. No viene en la respuesta de `settings`."),
 *     @OA\Property(property="name", type="string", example="COLOMBIA"),
 *     @OA\Property(property="iso2", type="string", example="CO", description="Código ISO de 2 letras"),
 *     @OA\Property(property="iso3", type="string", example="COL", description="Código ISO de 3 letras"),
 *     @OA\Property(property="phone_code", type="string", example="57", description="Indicativo telefónico, sin el signo +"),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         nullable=true,
 *         description="Configuración del país. `validations` son las reglas con las que se valida el teléfono de las personas de ese país.",
 *         @OA\Property(property="validations", type="array", @OA\Items(ref="#/components/schemas/CountryPhoneValidation"))
 *     ),
 *     @OA\Property(property="created_at", type="string", nullable=true, example="2026-05-14 09:32:10"),
 *     @OA\Property(property="updated_at", type="string", nullable=true, example="2026-05-14 09:32:10")
 * )
 *
 * @OA\Schema(
 *     schema="CountryPhoneValidation",
 *     type="object",
 *     required={"id", "regex", "message", "created_at"},
 *     @OA\Property(property="id", type="integer", example=1, description="Identificador de la validación dentro del país; no se repite."),
 *     @OA\Property(property="regex", type="string", example="/^3\d{9}$/", description="Expresión regular PCRE válida, con delimitadores. No se repite entre las validaciones del país."),
 *     @OA\Property(property="message", type="string", maxLength=255, example="Debe ser un celular de 10 dígitos que empiece por 3."),
 *     @OA\Property(property="created_at", type="string", example="2026-11-12 08:00:00"),
 *     @OA\Property(property="updated_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null, description="Si trae fecha, la validación está desactivada (borrado lógico dentro de `settings`).")
 * )
 *
 * @OA\Schema(
 *     schema="CountryResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="country", ref="#/components/schemas/Country")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CountryListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="countries", type="array", @OA\Items(ref="#/components/schemas/Country")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CountrySettingsRequest",
 *     type="object",
 *     required={"settings"},
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         required={"validations"},
 *         description="Reemplaza completo el `settings` del país.",
 *         @OA\Property(property="validations", type="array", description="Validaciones del teléfono; puede ir vacío.", @OA\Items(ref="#/components/schemas/CountryPhoneValidation"))
 *     )
 * )
 */
class CountryController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/locations/countries/all",
     *     tags={"Locations › Countries"},
     *     summary="Listar los países",
     *     description="Lista paginada de países. Sin `per_page` devuelve todos en una sola página. Sin `column` y `dir` ordena por nombre. `search` busca por nombre (parcial) o por código ISO2, ISO3 o indicativo telefónico (exactos). La puede llamar quien tenga alguno de los permisos `locations.countries.all`, `people.store` o `people.update` (los formularios de personas la usan para elegir el país).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "name", "iso2", "phone_code"}, example="name")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de países cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CountryListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden."},
     *                 "errors": {"per_page": {"No debe ser mayor que 10000."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(CountryAllRequest $request)
    {
        try {
            $countries = Country::query()
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderBy('name');
                });

            $countries = $countries->paginate($request->integer('per_page', $countries->count()));

            return $this->successResponse(
                new CountryCollection($countries),
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
     * @OA\Put(
     *     path="/locations/countries/settings/{id}",
     *     tags={"Locations › Countries"},
     *     summary="Guardar la configuración de un país",
     *     description="Reemplaza completo el campo `settings` del país con el enviado (hoy solo contiene `validations`, las reglas del teléfono). Para conservar las validaciones existentes hay que reenviarlas todas. Cada validación lleva un `id` y un `regex` únicos dentro del país, y el `regex` debe ser una expresión regular válida. Responde 200 con el país actualizado. Requiere el permiso `locations.countries.settings`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CountrySettingsRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración guardada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CountryResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o país inexistente.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del país", "settings": "Configuración", "settings.validations": "Validaciones del teléfono", "settings.validations.0.regex": "Expresión regular (validación n° 1)"},
     *                 "errors": {"settings.validations.0.regex": {"Debe ser una expresión regular válida."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function settings(CountrySettingsRequest $request, $id)
    {
        try {
            $country = Country::findOrFail($id);
            $country->settings = $request->input('settings');
            $country->save();

            return $this->successResponse(
                new CountryResource($country),
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
}
