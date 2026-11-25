<?php

use App\Http\Controllers\WebAuthController;
use App\Http\Middleware\RequireAssistantPermission;
use App\Http\Middleware\RequireS256;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [WebAuthController::class, 'show'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);

Mcp::oauthRoutes('oauth', registration: false);

Route::prefix('oauth')->as('passport.')->group(function () {
    Route::post('/token', [AccessTokenController::class, 'issueToken'])
        ->middleware('throttle')
        ->withoutMiddleware('web')
        ->name('token');

    Route::get('/authorize', [AuthorizationController::class, 'authorize'])
        ->middleware(['web', RequireS256::class, RequireAssistantPermission::class])
        ->name('authorizations.authorize');

    Route::middleware(['web', 'auth:web', RequireAssistantPermission::class])->group(function () {
        Route::post('/authorize', [ApproveAuthorizationController::class, 'approve'])
            ->name('authorizations.approve');

        Route::delete('/authorize', [DenyAuthorizationController::class, 'deny'])
            ->name('authorizations.deny');
    });
});
