<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UserDetail",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="employee_id", type="integer", example=1),
 *     @OA\Property(property="email", type="string", example="email_user_example"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="employee",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="person_id", type="integer", example=1),
 *         @OA\Property(property="operation_center", type="string", example="Principal"),
 *         @OA\Property(property="position_id", type="integer", example=2),
 *         @OA\Property(property="risk_manager_id", type="integer", example=5),
 *         @OA\Property(property="health_entity_id", type="integer", example=6),
 *         @OA\Property(property="pension_fund_id", type="integer", example=7),
 *         @OA\Property(property="compensation_fund_id", type="integer", example=8),
 *         @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30 00:00:00"),
 *         @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *         @OA\Property(
 *             property="position",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="item_id", type="integer", example=4),
 *             @OA\Property(property="name", type="string", example="Super"),
 *             @OA\Property(property="description", type="string", example="Admin"),
 *             @OA\Property(property="settings", type="object", nullable=true),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
 *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *             @OA\Property(
 *                 property="area",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="item_id", type="integer", example=4),
 *                     @OA\Property(property="name", type="string", example="Super"),
 *                     @OA\Property(property="description", type="string", example="Admin"),
 *                     @OA\Property(property="settings", type="object", nullable=true),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *                     @OA\Property(
 *                         property="pivot",
 *                         type="object",
 *                         @OA\Property(property="model_type", type="string", example="App\\Models\\Position"),
 *                         @OA\Property(property="model_id", type="integer", example=1),
 *                         @OA\Property(property="subitem_id", type="integer", example=1)
 *                     )
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="person",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="document", type="string", example="0000000000"),
 *                 @OA\Property(property="names", type="string", example="Super"),
 *                 @OA\Property(property="last_names", type="string", example="Admin"),
 *                 @OA\Property(property="gender_id", type="integer", example=15),
 *                 @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
 *                 @OA\Property(property="blood_type_id", type="integer", example=16),
 *                 @OA\Property(property="address", type="string", example="Dirección principal"),
 *                 @OA\Property(property="phone", type="string", example="0000000000"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
 *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *             )
 *         ),
 *         @OA\Property(
 *             property="roles",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="users"),
 *                 @OA\Property(property="guard_name", type="string", example="api"),
 *                 @OA\Property(property="title", type="string", example="Usuarios."),
 *                 @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *                 @OA\Property(
 *                     property="pivot",
 *                     type="object",
 *                     @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
 *                     @OA\Property(property="model_id", type="integer", example=1),
 *                     @OA\Property(property="role_id", type="integer", example=1)
 *                 )
 *             )
 *         ),
 *         @OA\Property(
 *             property="permissions",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="users.all"),
 *                 @OA\Property(property="guard_name", type="string", example="api"),
 *                 @OA\Property(property="title", type="string", example="Usuarios."),
 *                 @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *                 @OA\Property(
 *                     property="pivot",
 *                     type="object",
 *                     @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
 *                     @OA\Property(property="model_id", type="integer", example=1),
 *                     @OA\Property(property="permission_id", type="integer", example=1)
 *                 )
 *             )
 *         )
 *     )
 * )
 */
class UserSchemas {}
