<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Identificador del puesto de trabajo (proceso, subproceso u operación) para su código QR, guardado en settings.uuid.
 *
 * Se trabaja en crudo porque el cast App\Casts\SettingsWithUuid lo oculta al leer `settings`
 * y lo conserva al guardar.
 */
trait HasSettingsUuid
{
    public function settingsUuid(): string
    {
        $settings = json_decode($this->getAttributes()['settings'] ?? 'null', true) ?: [];

        if (empty($settings['uuid'])) {
            $settings['uuid'] = (string) Str::uuid();
            $this->attributes['settings'] = json_encode($settings);
            // Sin eventos: el uuid no queda en la auditoría
            $this->saveQuietly();
        }

        return $settings['uuid'];
    }
}
