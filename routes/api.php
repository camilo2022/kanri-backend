<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\RiskManagerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackTypeController;
use App\Http\Controllers\BloodTypeController;
use App\Http\Controllers\BootTypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClothingLineController;
use App\Http\Controllers\CompensationFundController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GarmentTypeController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HealthEntityController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PensionFundController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SilhouetteController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SubgroupController;
use App\Http\Controllers\SubmoduleController;
use App\Http\Controllers\TrademarkController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaistbandTypeController;
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

Route::prefix('/sizes')->group(function () {
    Route::controller(SizeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:sizes,sizes.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:sizes,sizes.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:sizes,sizes.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:sizes,sizes.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:sizes,sizes.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:sizes,sizes.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:sizes,sizes.restore']);
    });
});

Route::prefix('/classification')->group(function () {
    Route::prefix('/subgroups')->group(function () {
        Route::controller(SubgroupController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification,classification.subgroups.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification,classification.subgroups.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification,classification.subgroups.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification,classification.subgroups.update']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:classification,classification.subgroups.setting']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification,classification.subgroups.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification,classification.subgroups.restore']);
        });
    });

    Route::prefix('/groups')->group(function () {
        Route::controller(GroupController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification,classification.groups.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification,classification.groups.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification,classification.groups.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification,classification.groups.update']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:classification,classification.groups.setting']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification,classification.groups.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification,classification.groups.restore']);
        });
    });
});


Route::prefix('/clothing_lines')->group(function () {
    Route::controller(ClothingLineController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:clothing_lines,clothing_lines.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:clothing_lines,clothing_lines.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:clothing_lines,clothing_lines.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:clothing_lines,clothing_lines.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:clothing_lines,clothing_lines.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:clothing_lines,clothing_lines.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:clothing_lines,clothing_lines.restore']);
    });
});

Route::prefix('/garment_types')->group(function () {
    Route::controller(GarmentTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:garment_types,garment_types.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:garment_types,garment_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:garment_types,garment_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:garment_types,garment_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:garment_types,garment_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:garment_types,garment_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:garment_types,garment_types.restore']);
    });
});

Route::prefix('/boot_types')->group(function () {
    Route::controller(BootTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:boot_types,boot_types.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:boot_types,boot_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:boot_types,boot_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:boot_types,boot_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:boot_types,boot_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:boot_types,boot_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:boot_types,boot_types.restore']);
    });
});

Route::prefix('/back_types')->group(function () {
    Route::controller(BackTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:back_types,back_types.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:back_types,back_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:back_types,back_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:back_types,back_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:back_types,back_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:back_types,back_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:back_types,back_types.restore']);
    });
});

Route::prefix('/waistband_types')->group(function () {
    Route::controller(WaistbandTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:waistband_types,waistband_types.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:waistband_types,waistband_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:waistband_types,waistband_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:waistband_types,waistband_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:waistband_types,waistband_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:waistband_types,waistband_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:waistband_types,waistband_types.restore']);
    });
});

Route::prefix('/categorization')->group(function () {
    Route::prefix('/categories')->group(function () {
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.restore']);
        });

        Route::prefix('/subcategories')->group(function () {
            Route::controller(SubcategoryController::class)->group(function () {
                Route::get('/all/{module_id}', 'all')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.subcategories.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.subcategories.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.subcategories.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.subcategories.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.subcategories.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:categorization,categorization.categories.subcategories.restore']);
            });
        });
    });
});
