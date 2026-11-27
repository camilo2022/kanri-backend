<?php

namespace App\Services;

use App\Models\Permission;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;

class PermissionService
{
    use ApiResponser;

    /**
     * Lo llama el authorize() de cada FormRequest, así que sirve igual para las rutas de la API y para el MCP. Basta
     * con tener uno de los permisos; si no se tiene ninguno responde 403 con la lista de los permisos que se aceptan.
     *
     * @param  array<int, string>  $permissions
     */
    public function authorize(?Authenticatable $user, array $permissions): bool
    {
        // Solo permisos directos: el rol agrupa, no da acceso a todas sus acciones
        if ($user && $user->getDirectPermissions()->whereIn('name', $permissions)->isNotEmpty()) {
            return true;
        }

        $titles = Permission::whereIn('name', $permissions)->get()->pluck('title')->join(', ');
        $message = "No está autorizado para realizar esta acción. No cuentas con ninguno de los permisos: {$titles}. Contacte al administrador para obtener asistencia o solicitar autorización.";

        throw new HttpResponseException($this->errorResponse(['message' => $message], 403));
    }
}
