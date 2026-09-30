<?php

use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackTypeController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BloodTypeController;
use App\Http\Controllers\BootTypeController;
use App\Http\Controllers\BuilderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CompensationFundController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FabricTypeController;
use App\Http\Controllers\FileSubtypeController;
use App\Http\Controllers\FileTypeController;
use App\Http\Controllers\GarmentTypeController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HealthEntityController;
use App\Http\Controllers\ManagementCollectionController;
use App\Http\Controllers\ManagementProductionController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\PensionFundController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonTypeController;
use App\Http\Controllers\PieceController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiskManagerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SilhouetteController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SubgroupController;
use App\Http\Controllers\SubmoduleController;
use App\Http\Controllers\SubprocessController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierTypeController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\SupplyTypeController;
use App\Http\Controllers\TechnicalSheetController;
use App\Http\Controllers\ThreadTypeController;
use App\Http\Controllers\TrademarkController;
use App\Http\Controllers\TransformationController;
use App\Http\Controllers\TypologyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaistbandTypeController;
use App\Http\Controllers\WashToneController;
use App\Http\Controllers\YokeTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('/account_types')->group(function () {
    Route::controller(AccountTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:account_types.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:account_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:account_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:account_types.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:account_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:account_types.restore']);
    });
});

Route::prefix('/audits')->group(function () {
    Route::controller(AuditController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:audits.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:audits.find']);
    });
});

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
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.roles.all|navegation.modules.submodules.store|navegation.modules.submodules.update|users.authorization.assign|users.authorization.remove|authorization.permissions.store|authorization.permissions.update|organizational_structure.areas.positions.authorization.assign|organizational_structure.areas.positions.authorization.remove']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.roles.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.roles.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.roles.update']);
        });
    });

    Route::prefix('/permissions')->group(function () {
        Route::controller(PermissionController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.permissions.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.permissions.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.permissions.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.permissions.update']);
        });
    });
});

Route::prefix('/back_types')->group(function () {
    Route::controller(BackTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:back_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:back_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:back_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:back_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:back_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:back_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:back_types.restore']);
    });
});

Route::prefix('/banks')->group(function () {
    Route::controller(BankController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:banks.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:banks.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:banks.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:banks.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:banks.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:banks.restore']);
    });
});

Route::prefix('/blood_types')->group(function () {
    Route::controller(BloodTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:blood_types.all|people.store|people.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:blood_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:blood_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:blood_types.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:blood_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:blood_types.restore']);
    });
});

Route::prefix('/boot_types')->group(function () {
    Route::controller(BootTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:boot_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:boot_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:boot_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:boot_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:boot_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:boot_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:boot_types.restore']);
    });
});

Route::prefix('/cache')->group(function () {
    Route::prefix('/builder')->group(function () {
        Route::controller(BuilderController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:cache.builders.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:cache.builders.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:cache.builders.store']);
            Route::put('/update/{uuid}', 'update')->middleware(['auth:sanctum', 'can:cache.builders.update']);
            Route::delete('/delete/{uuid}', 'delete')->middleware(['auth:sanctum', 'can:cache.builders.delete']);
        });
    });
});

Route::prefix('/categorization')->group(function () {
    Route::prefix('/categories')->group(function () {
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:categorization.categories.all|trademarks.store|trademarks.update|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:categorization.categories.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:categorization.categories.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:categorization.categories.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:categorization.categories.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:categorization.categories.restore']);
        });

        Route::prefix('/subcategories')->group(function () {
            Route::controller(SubcategoryController::class)->group(function () {
                Route::get('/all/{category_id}', 'all')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.all|categorization.categories.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.restore']);
            });
        });
    });
});

Route::prefix('/classification')->group(function () {
    Route::prefix('/groups')->group(function () {
        Route::controller(GroupController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification.groups.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification.groups.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification.groups.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification.groups.update']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:classification.groups.setting']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification.groups.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification.groups.restore']);
        });
    });

    Route::prefix('/subgroups')->group(function () {
        Route::controller(SubgroupController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification.subgroups.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification.subgroups.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification.subgroups.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification.subgroups.update']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:classification.subgroups.setting']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification.subgroups.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification.subgroups.restore']);
        });
    });
});

Route::prefix('/collections')->group(function () {
    Route::controller(CollectionController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:collections.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:collections.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:collections.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:collections.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:collections.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:collections.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:collections.restore']);
    });
});

Route::prefix('/compensation_funds')->group(function () {
    Route::controller(CompensationFundController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:compensation_funds.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:compensation_funds.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:compensation_funds.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:compensation_funds.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:compensation_funds.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:compensation_funds.restore']);
    });
});

Route::prefix('/colors')->group(function () {
    Route::controller(ColorController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:colors.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:colors.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:colors.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:colors.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:colors.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:colors.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:colors.restore']);
    });
});

Route::prefix('/employees')->group(function () {
    Route::controller(EmployeeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:employees.all|users.store|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:employees.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:employees.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:employees.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:employees.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:employees.restore']);
    });
});

Route::prefix('/garment_types')->group(function () {
    Route::controller(GarmentTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:garment_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:garment_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:garment_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:garment_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:garment_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:garment_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:garment_types.restore']);
    });
});

Route::prefix('/genders')->group(function () {
    Route::controller(GenderController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:genders.all|people.store|people.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:genders.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:genders.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:genders.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:genders.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:genders.restore']);
    });
});

Route::prefix('/health_entities')->group(function () {
    Route::controller(HealthEntityController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:health_entities.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:health_entities.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:health_entities.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:health_entities.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:health_entities.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:health_entities.restore']);
    });
});

Route::prefix('/management')->group(function () {
    Route::prefix('/collections')->group(function () {
        Route::controller(ManagementCollectionController::class)->group(function () {
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:management.collections.find']);
            Route::post('/save', 'save')->middleware(['auth:sanctum', 'can:management.collections.save']);
        });
    });
    Route::prefix('/production')->group(function () {
        Route::controller(ManagementProductionController::class)->group(function () {
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:management.production.find']);
            Route::post('/save', 'save')->middleware(['auth:sanctum', 'can:management.production.save']);
        });
    });
});

Route::prefix('/navegation')->group(function () {
    Route::prefix('/modules')->group(function () {
        Route::controller(ModuleController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:navegation.modules.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation.modules.find|navegation.modules.update']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation.modules.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation.modules.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation.modules.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation.modules.restore']);
        });

        Route::prefix('/submodules')->group(function () {
            Route::controller(SubmoduleController::class)->group(function () {
                Route::get('/all/{module_id}', 'all')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.find|navegation.modules.submodules.update']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.restore']);
            });
        });
    });
});

Route::prefix('/organizational_structure')->group(function () {
    Route::prefix('/areas')->group(function () {
        Route::controller(AreaController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:organizational_structure.areas.all|employees.store|employees.update']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:organizational_structure.areas.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:organizational_structure.areas.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:organizational_structure.areas.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:organizational_structure.areas.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:organizational_structure.areas.restore']);
        });

        Route::prefix('/positions')->group(function () {
            Route::controller(PositionController::class)->group(function () {
                Route::get('/all/{area_id}', 'all')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.all|employees.store|employees.update']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.restore']);
                Route::prefix('/authorization')->group(function () {
                    Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.authorization.assign']);
                    Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.authorization.remove']);
                });
            });
        });
    });
});

Route::prefix('/pension_funds')->group(function () {
    Route::controller(PensionFundController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:pension_funds.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:pension_funds.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:pension_funds.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:pension_funds.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:pension_funds.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:pension_funds.restore']);
    });
});

Route::prefix('/people')->group(function () {
    Route::controller(PersonController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:people.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:people.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:people.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:people.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:people.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:people.restore']);
    });
});

Route::prefix('/pieces')->group(function () {
    Route::controller(PieceController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:pieces.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:pieces.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:pieces.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:pieces.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:pieces.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:pieces.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:pieces.restore']);
    });
});

Route::prefix('/products')->group(function () {
    Route::controller(ProductController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:products.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:products.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:products.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:products.update']);
    });
});

Route::prefix('/reports')->group(function () {
    Route::controller(ReportController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:reports.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:reports.find']);
        Route::post('/{id}/generate', 'generate')->middleware(['auth:sanctum']);
        Route::post('/{id}/export', 'export')->middleware(['auth:sanctum']);
    });
});

Route::prefix('/risk_managers')->group(function () {
    Route::controller(RiskManagerController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:risk_managers.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:risk_managers.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:risk_managers.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:risk_managers.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:risk_managers.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:risk_managers.restore']);
    });
});

Route::prefix('/sizes')->group(function () {
    Route::controller(SizeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:sizes.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings|trademarks.find']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:sizes.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:sizes.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:sizes.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:sizes.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:sizes.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:sizes.restore']);
    });
});

Route::prefix('/technical_sheets')->group(function () {
    Route::controller(TechnicalSheetController::class)->group(function () {
        Route::get('/find', 'find')->middleware(['auth:sanctum', 'can:technical_sheets.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:technical_sheets.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:technical_sheets.update']);
        Route::get('/pdf/{uuid}', 'pdf')->name('technical_sheets.pdf');
    });
    Route::prefix('/production_orders')->group(function () {
        Route::controller(ProductionOrderController::class)->group(function () {
            Route::get('/all/{technical_sheet_id?}', 'all')->middleware(['auth:sanctum', 'can:technical_sheets.production_orders.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:technical_sheets.production_orders.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:technical_sheets.production_orders.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:technical_sheets.production_orders.update']);
            Route::get('/pdf/{uuid}', 'pdf')->name('production_orders.technical_sheets.pdf');
        });
    });
    Route::prefix('/transformations')->group(function () {
        Route::controller(TransformationController::class)->group(function () {
            Route::get('/all/{technical_sheet_id}', 'all')->middleware(['auth:sanctum', 'can:technical_sheets.transformations.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:technical_sheets.transformations.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:technical_sheets.transformations.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:technical_sheets.transformations.update']);
        });
    });
});

Route::prefix('/trademarks')->group(function () {
    Route::controller(TrademarkController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:trademarks.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:trademarks.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:trademarks.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:trademarks.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:trademarks.settings']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:trademarks.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:trademarks.restore']);
        Route::prefix('/size')->group(function () {
            Route::post('/assign/{id}', 'size_assign')->middleware(['auth:sanctum', 'can:trademarks.size.assign']);
            Route::post('/remove/{id}', 'size_remove')->middleware(['auth:sanctum', 'can:trademarks.size.remove']);
        });
    });
});

Route::prefix('/typification')->group(function () {
    Route::prefix('/supply_types')->group(function () {
        Route::controller(SupplyTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:typification.supply_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.supply_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.supply_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.supply_types.update']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:typification.supply_types.settings']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.supply_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.supply_types.restore']);
        });

        Route::prefix('/supplies')->group(function () {
            Route::controller(SupplyController::class)->group(function () {
                Route::get('/all/{supply_type_id}', 'all')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.update']);
                Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.setting']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.restore']);
                Route::get('/export/{supply_type_id}', 'export')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.export']);
                Route::post('/import/{supply_type_id}', 'import')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.import']);
            });
        });
    });

    Route::prefix('/file_types')->group(function () {
        Route::controller(FileTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:typification.file_types.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.file_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.file_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.file_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.file_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.file_types.restore']);
        });

        Route::prefix('/file_subtypes')->group(function () {
            Route::controller(FileSubtypeController::class)->group(function () {
                Route::get('/all/{file_type_id}', 'all')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.restore']);
            });
        });
    });

    Route::prefix('/person_types')->group(function () {
        Route::controller(PersonTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:typification.person_types.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.person_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.person_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.person_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.person_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.person_types.restore']);
        });

        Route::prefix('/document_types')->group(function () {
            Route::controller(DocumentTypeController::class)->group(function () {
                Route::get('/all/{file_type_id}', 'all')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.restore']);
                Route::put('/settings/{id}', 'settings')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.settings']);
            });
        });
    });

    Route::prefix('/supplier_types')->group(function () {
        Route::controller(SupplierTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:typification.supplier_types.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.supplier_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.supplier_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.supplier_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.supplier_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.supplier_types.restore']);
        });

        Route::prefix('/suppliers')->group(function () {
            Route::controller(SupplierController::class)->group(function () {
                Route::get('/all/{supplier_type_id}', 'all')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.restore']);
            });
        });
    });

    Route::prefix('/typologies')->group(function () {
        Route::controller(TypologyController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:typification.typologies.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.typologies.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.typologies.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.typologies.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.typologies.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.typologies.restore']);
        });
    });
});

Route::prefix('/users')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:users.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:users.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:users.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:users.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:users.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:users.restore']);
        Route::prefix('/authorization')->group(function () {
            Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum', 'can:users.authorization.assign']);
            Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum', 'can:users.authorization.remove']);
        });
    });
});

Route::prefix('/waistband_types')->group(function () {
    Route::controller(WaistbandTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:waistband_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:waistband_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:waistband_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:waistband_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:waistband_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:waistband_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:waistband_types.restore']);
    });
});

Route::prefix('/wash_tones')->group(function () {
    Route::controller(WashToneController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:wash_tones.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:wash_tones.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:wash_tones.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:wash_tones.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:wash_tones.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:wash_tones.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:wash_tones.restore']);
    });
});

Route::prefix('/workflow')->group(function () {
    Route::prefix('/processes')->group(function () {
        Route::controller(ProcessController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:workflow.processes.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:workflow.processes.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:workflow.processes.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:workflow.processes.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:workflow.processes.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:workflow.processes.restore']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:workflow.processes.settings']);
        });

        Route::prefix('/subprocesses')->group(function () {
            Route::controller(SubprocessController::class)->group(function () {
                Route::get('/all/{process_id}', 'all')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.restore']);
                Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.settings']);
            });

            Route::prefix('/operations')->group(function () {
                Route::controller(OperationController::class)->group(function () {
                    Route::get('/all/{subprocess_id}', 'all')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.all']);
                    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.find']);
                    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.store']);
                    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.update']);
                    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.delete']);
                    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.restore']);
                    Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.settings']);
                });
            });
        });
    });
});

Route::prefix('/yoke_types')->group(function () {
    Route::controller(YokeTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:yoke_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:yoke_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:yoke_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:yoke_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:yoke_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:yoke_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:yoke_types.restore']);
    });
});

/*
Route::prefix('/silhouettes')->group(function () {
    Route::controller(SilhouetteController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:silhouettes.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:silhouettes.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:silhouettes.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:silhouettes.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:silhouettes.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:silhouettes.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:silhouettes.restore']);
    });
});

Route::prefix('/fabric_types')->group(function () {
    Route::controller(FabricTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:fabric_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:fabric_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:fabric_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:fabric_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:fabric_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:fabric_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:fabric_types.restore']);
    });
});

Route::prefix('/thread_types')->group(function () {
    Route::controller(ThreadTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:thread_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:thread_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:thread_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:thread_types.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:thread_types.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:thread_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:thread_types.restore']);
    });
});
*/
