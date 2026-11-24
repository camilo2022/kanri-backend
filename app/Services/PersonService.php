<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\DocumentType;

class PersonService
{
    /**
     * Niveles de ubicación que puede tener una persona: la clave viaja en la petición y la clase se guarda en
     * people.location_type.
     */
    public const LOCATION_TYPES = [
        'country' => Country::class,
        'department' => Department::class,
        'city' => City::class,
    ];

    /**
     * Mensaje de error del documento según las reglas activas de su tipo, o null si cumple alguna. Un tipo sin reglas
     * no se puede usar.
     */
    public function documentError(int $document_type_id, string $document): ?string
    {
        $validations = $this->activeValidations(data_get(DocumentType::find($document_type_id)?->settings, 'validations'));

        if ($validations->isEmpty()) {
            return 'El tipo de documento no tiene reglas de validación configuradas.';
        }

        return $this->firstMatchError($validations, $document);
    }

    /**
     * Mensaje de error del teléfono según las reglas activas de su país, o null si cumple alguna. Un país sin reglas
     * no se puede usar.
     */
    public function phoneError(int $country_id, string $phone): ?string
    {
        $validations = $this->activeValidations(data_get(Country::find($country_id)?->settings, 'validations'));

        if ($validations->isEmpty()) {
            return 'El país no tiene reglas de validación de teléfono configuradas.';
        }

        return $this->firstMatchError($validations, $phone);
    }

    /**
     * La ubicación debe ser el último nivel disponible: si el país tiene departamentos hay que elegir uno, y si el
     * departamento tiene ciudades hay que elegir una. Devuelve el mensaje de error o null.
     */
    public function locationError(string $type, int $id): ?string
    {
        $class = self::LOCATION_TYPES[$type] ?? null;
        $location = $class ? $class::find($id) : null;

        if (! $location) {
            return 'La ubicación no existe.';
        }

        if ($location instanceof Country && $location->departments()->exists()) {
            return 'Debe elegir el departamento.';
        }

        if ($location instanceof Department && $location->cities()->exists()) {
            return 'Debe elegir la ciudad.';
        }

        return null;
    }

    private function activeValidations($validations)
    {
        return collect($validations ?? [])->filter(fn ($item) => empty($item->deleted_at ?? null) && filled($item->regex ?? null));
    }

    private function firstMatchError($validations, string $value): ?string
    {
        foreach ($validations as $item) {
            if (@preg_match($item->regex, $value) === 1) {
                return null;
            }
        }

        return $validations->pluck('message')->filter()->implode("\n") ?: 'Formato inválido.';
    }
}
