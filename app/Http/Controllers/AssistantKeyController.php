<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assistant\Key\AssistantKeyDeleteRequest;
use App\Http\Requests\Assistant\Key\AssistantKeyStoreRequest;
use App\Mcp\ActorResolver;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="AssistantKeyResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="key", type="string", description="La llave de IA completa. Solo se muestra esta vez: no se puede volver a consultar.", example="45|aB3dEfGhIjKlMnOpQrStUvWxYz0123456789abcd"),
 *         @OA\Property(property="expires_at", type="string", description="Hora en la que vence aunque se siga usando (duración máxima).", example="2026-11-26 16:30:00"),
 *         @OA\Property(property="inactivity_minutes", type="integer", description="Minutos sin usarla tras los cuales deja de servir.", example=30)
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="AssistantKeyDeleteResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="deleted", type="boolean", description="`true` si había una llave activa y se revocó; `false` si ya no había ninguna.", example=true)
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 */
class AssistantKeyController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Post(
     *     path="/assistant/key/store",
     *     tags={"Assistant › Keys"},
     *     summary="Generar mi llave de IA",
     *     description="Crea la llave que la persona pega en el chat del asistente para que todo lo que dicte quede a su nombre. Solo hay una por persona: al generar una nueva, la anterior se revoca. La llave se muestra una sola vez. No sirve para las demás rutas de esta API: solo la acepta el asistente (`/mcp`). Deja de servir tras los minutos de inactividad que indica la respuesta, a la hora `expires_at`, al cerrar sesión o al revocarla. Permiso: `assistant.connect`.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(response=201, description="Llave generada", @OA\JsonContent(ref="#/components/schemas/AssistantKeyResponse")),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(AssistantKeyStoreRequest $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->where('name', ActorResolver::KEY_NAME)->delete();

            $expires_at = now()->addHours(config('assistant.ai_token_max_hours'));
            $token = $user->createToken(ActorResolver::KEY_NAME, [ActorResolver::KEY_ABILITY], $expires_at);

            return $this->successResponse(
                [
                    'key' => $token->plainTextToken,
                    'expires_at' => $expires_at->format('Y-m-d H:i:s'),
                    'inactivity_minutes' => config('assistant.ai_token_inactivity_minutes'),
                ],
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
    }

    /**
     * @OA\Delete(
     *     path="/assistant/key/delete",
     *     tags={"Assistant › Keys"},
     *     summary="Revocar mi llave de IA",
     *     description="Revoca la llave de IA de la persona: el asistente deja de poder trabajar a su nombre. Se puede repetir; si ya no había llave, responde `deleted: false`. Permiso: `assistant.connect`.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(response=200, description="Llave revocada", @OA\JsonContent(ref="#/components/schemas/AssistantKeyDeleteResponse")),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(AssistantKeyDeleteRequest $request)
    {
        try {
            $deleted = $request->user()->tokens()->where('name', ActorResolver::KEY_NAME)->delete();

            return $this->successResponse(
                ['deleted' => $deleted > 0],
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
