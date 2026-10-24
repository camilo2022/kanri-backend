<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Cast de `settings` para procesos, subprocesos y operaciones (igual que 'object', con el uuid protegido).
 *
 * El `uuid` de settings identifica el puesto de trabajo en su código QR:
 * - Al leer: devuelve el objeto sin `uuid`. Es privado y nunca sale en la API, en las relaciones anidadas ni en
 *   la auditoría.
 * - Al guardar: si el valor nuevo no trae `uuid`, conserva el que ya estaba guardado. Así, crear, editar o
 *   configurar no invalida el QR impreso en el puesto.
 * - Solo se lee y se escribe en crudo con el trait App\Traits\HasSettingsUuid.
 */
class SettingsWithUuid implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        $settings = json_decode($value);

        if (is_object($settings)) {
            unset($settings->uuid);
        }

        return $settings;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        $settings = json_decode(json_encode($value), true);
        $stored = json_decode($attributes[$key] ?? 'null', true);

        if (is_array($settings) && empty($settings['uuid']) && ! empty($stored['uuid'])) {
            $settings['uuid'] = $stored['uuid'];
        }

        return json_encode($settings);
    }
}
