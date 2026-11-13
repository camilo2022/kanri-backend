<?php

namespace App\Support;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Validador de la API: si el único problema de un id que viene en la URL es que no existe (regla `exists`), responde 404
 * en lugar de 422, porque el recurso que se pide no existe (no es un error del formulario).
 */
class ApiValidator extends Validator
{
    public function passes()
    {
        $passes = parent::passes();

        if (! $passes && $this->failsOnlyOnMissingRouteRecord()) {
            throw new NotFoundHttpException('El registro solicitado no existe.', new ModelNotFoundException());
        }

        return $passes;
    }

    private function failsOnlyOnMissingRouteRecord(): bool
    {
        $route = request()->route();
        if (! $route) {
            return false;
        }

        $routeParams = array_keys($route->parameters());
        foreach ($this->failed() as $attribute => $rules) {
            if (in_array($attribute, $routeParams, true) && array_key_exists('Exists', $rules)) {
                return true;
            }
        }

        return false;
    }
}
