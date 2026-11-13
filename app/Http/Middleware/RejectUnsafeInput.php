<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Rechaza (422) los textos con caracteres de control o con etiquetas HTML/script, para que no se guarden en la base de
 * datos ni se muestren después en pantallas o reportes. Las contraseñas y las expresiones regulares se dejan pasar.
 */
class RejectUnsafeInput
{
    private const EXCLUDED_KEYS = ['password', 'password_confirmation', 'current_password', 'regex'];

    public function handle(Request $request, Closure $next)
    {
        $errors = [];

        foreach (Arr::dot($request->except(self::EXCLUDED_KEYS)) as $field => $value) {
            if (! is_string($value) || $this->isExcluded($field)) {
                continue;
            }

            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
                $errors[$field] = ['Contiene caracteres no permitidos.'];
            } elseif (preg_match('/<\s*\/?\s*[a-z!][^>]*>?/i', $value)) {
                $errors[$field] = ['No se permiten etiquetas HTML ni scripts.'];
            }
        }

        if ($errors) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $errors], 422);
        }

        return $next($request);
    }

    private function isExcluded(string $field): bool
    {
        return in_array(last(explode('.', $field)), self::EXCLUDED_KEYS, true);
    }
}
