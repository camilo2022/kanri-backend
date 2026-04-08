<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\RiskManagerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloodTypeController;
use App\Http\Controllers\CompensationFundController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\HealthEntityController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PensionFundController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SilhouetteController;
use App\Http\Controllers\SubmoduleController;
use App\Http\Controllers\TrademarkController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('/logout', 'logout')->middleware(['auth:sanctum']);
        Route::get('/user', 'user')->middleware(['auth:sanctum']);
    });
});

Route::prefix('/authorization')->group(function () {
    Route::prefix('/roles')->group(function () {
        Route::controller(RoleController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization,authorization.roles.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization,authorization.roles.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization,authorization.roles.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization,authorization.roles.update']);
        });
    });

    Route::prefix('/permissions')->group(function () {
        Route::controller(PermissionController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization,authorization.permissions.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization,authorization.permissions.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization,authorization.permissions.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization,authorization.permissions.update']);
        });
    });
});

Route::prefix('/users')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:users,users.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:users,users.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:users,users.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:users,users.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:users,users.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:users,users.restore']);
        Route::prefix('/authorization')->group(function () {
            Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum', 'can:users,users.authorization.assign']);
            Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum', 'can:users,users.authorization.remove']);
        });
    });
});

Route::prefix('/navegation')->group(function () {
    Route::prefix('/modules')->group(function () {
        Route::controller(ModuleController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.restore']);
        });

        Route::prefix('/submodules')->group(function () {
            Route::controller(SubmoduleController::class)->group(function () {
                Route::get('/all/{module_id}', 'all')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.submodules.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.submodules.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.submodules.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.submodules.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.submodules.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation,navegation.modules.submodules.restore']);
            });
        });
    });
});

Route::prefix('/people')->group(function () {
    Route::controller(PersonController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:people,people.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:people,people.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:people,people.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:people,people.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:people,people.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:people,people.restore']);
    });
});

Route::prefix('/employees')->group(function () {
    Route::controller(EmployeeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:employees,employees.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:employees,employees.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:employees,employees.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:employees,employees.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:employees,employees.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:employees,employees.restore']);
    });
});

Route::prefix('/organizational_structure')->group(function () {
    Route::prefix('/areas')->group(function () {
        Route::controller(AreaController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.restore']);
        });

        Route::prefix('/positions')->group(function () {
            Route::controller(PositionController::class)->group(function () {
                Route::get('/all/{area_id}', 'all')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.positions.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.positions.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.positions.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.positions.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.positions.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.positions.restore']);
                Route::prefix('/authorization')->group(function () {
                    Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.positions.authorization.assign']);
                    Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum', 'can:organizational_structure,organizational_structure.areas.positions.authorization.remove']);
                });
            });
        });
    });
});

Route::prefix('/genders')->group(function () {
    Route::controller(GenderController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:genders,genders.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:genders,genders.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:genders,genders.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:genders,genders.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:genders,genders.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:genders,genders.restore']);
    });
});

Route::prefix('/blood_types')->group(function () {
    Route::controller(BloodTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:blood_types,blood_types.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:blood_types,blood_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:blood_types,blood_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:blood_types,blood_types.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:blood_types,blood_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:blood_types,blood_types.restore']);
    });
});

Route::prefix('/health_entities')->group(function () {
    Route::controller(HealthEntityController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:health_entities,health_entities.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:health_entities,health_entities.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:health_entities,health_entities.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:health_entities,health_entities.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:health_entities,health_entities.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:health_entities,health_entities.restore']);
    });
});

Route::prefix('/risk_manager')->group(function () {
    Route::controller(RiskManagerController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:risk_managers,risk_managers.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:risk_managers,risk_managers.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:risk_managers,risk_managers.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:risk_managers,risk_managers.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:risk_managers,risk_managers.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:risk_managers,risk_managers.restore']);
    });
});

Route::prefix('/pension_funds')->group(function () {
    Route::controller(PensionFundController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:pension_funds,pension_funds.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:pension_funds,pension_funds.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:pension_funds,pension_funds.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:pension_funds,pension_funds.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:pension_funds,pension_funds.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:pension_funds,pension_funds.restore']);
    });
});

Route::prefix('/compensation_funds')->group(function () {
    Route::controller(CompensationFundController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:compensation_funds,compensation_funds.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:compensation_funds,compensation_funds.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:compensation_funds,compensation_funds.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:compensation_funds,compensation_funds.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:compensation_funds,compensation_funds.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:compensation_funds,compensation_funds.restore']);
    });
});

Route::prefix('/trademarks')->group(function () {
    Route::controller(TrademarkController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:trademarks,trademarks.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:trademarks,trademarks.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:trademarks,trademarks.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:trademarks,trademarks.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:trademarks,trademarks.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:trademarks,trademarks.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:trademarks,trademarks.restore']);
    });
});

Route::prefix('/silhouettes')->group(function () {
    Route::controller(SilhouetteController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:silhouettes,silhouettes.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:silhouettes,silhouettes.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:silhouettes,silhouettes.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:silhouettes,silhouettes.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:silhouettes,silhouettes.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:silhouettes,silhouettes.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:silhouettes,silhouettes.restore']);
    });
});
