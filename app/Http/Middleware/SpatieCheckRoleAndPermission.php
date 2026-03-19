<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Role;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SpatieCheckRoleAndPermission
{

    use ApiMessage, ApiResponser;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role, $permission)
    {
        $roleModel = Role::where('name', $role)->first();
        $permissionModel = Permission::where('name', $permission)->first();

        $roleTitle = $roleModel?->title ?? $role;
        $permissionTitle = $permissionModel?->title ?? $permission;

        if (!Auth::user()->hasRole($role) && !Auth::user()->hasDirectPermission($permission)) {
            $message = "No está autorizado para realizar esta acción. Falta el rol: {$roleTitle} y falta el permiso: {$permissionTitle}. Contacte al administrador para obtener asistencia o solicitar autorización.";
            return $this->errorResponse(
                [
                    'message' => $message
                ],
                403
            );

            throw new AuthorizationException();
        }

        if (!Auth::user()->hasRole($role)) {
            $message = "No está autorizado para realizar esta acción. Falta el rol: {$roleTitle}. Contacte al administrador para obtener asistencia o solicitar autorización.";
            return $this->errorResponse(
                [
                    'message' => $message
                ],
                403
            );

            throw new AuthorizationException();
        }

        if (!Auth::user()->hasDirectPermission($permission)) {
            $message = "No está autorizado para realizar esta acción. Falta el permiso: {$permissionTitle}. Contacte al administrador para obtener asistencia o solicitar autorización.";
            return $this->errorResponse(
                [
                    'message' => $message
                ],
                403
            );

            throw new AuthorizationException();
        }

        return $next($request);
    }
}
