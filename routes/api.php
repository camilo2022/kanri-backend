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
use App\Http\Controllers\CityController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CompensationFundController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FileSubtypeController;
use App\Http\Controllers\FileTypeController;
use App\Http\Controllers\GarmentTypeController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HealthEntityController;
use App\Http\Controllers\ManagementCollectionController;
use App\Http\Controllers\ManagementProductionController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\MovementController;
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
use App\Http\Controllers\ProductionOrderDetailController;
use App\Http\Controllers\ProductivityController;
use App\Http\Controllers\ProductivityDetailController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiskManagerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\StopTypeController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SubgroupController;
use App\Http\Controllers\SubmoduleController;
use App\Http\Controllers\SubprocessController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierTypeController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\SupplyTypeController;
use App\Http\Controllers\TechnicalSheetController;
use App\Http\Controllers\TrademarkController;
use App\Http\Controllers\TransformationController;
use App\Http\Controllers\TypologyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaistbandTypeController;
use App\Http\Controllers\WashToneController;
use App\Http\Controllers\YokeTypeController;
use App\Models\AccountType;
use App\Models\Area;
use App\Models\BackType;
use App\Models\Bank;
use App\Models\BloodType;
use App\Models\BootType;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\CompensationFund;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\FileSubtype;
use App\Models\FileType;
use App\Models\GarmentType;
use App\Models\Gender;
use App\Models\Group;
use App\Models\HealthEntity;
use App\Models\Module;
use App\Models\Movement;
use App\Models\Operation;
use App\Models\PensionFund;
use App\Models\Permission;
use App\Models\Country;
use App\Models\Person;
use App\Models\PersonType;
use App\Models\Piece;
use App\Models\Position;
use App\Models\Process;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\RiskManager;
use App\Models\Role;
use App\Models\Rule;
use App\Models\Size;
use App\Models\Stop;
use App\Models\StopType;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Submodule;
use App\Models\Subprocess;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\Supply;
use App\Models\SupplyType;
use App\Models\TechnicalSheet;
use App\Models\Trademark;
use App\Models\Typology;
use App\Models\User;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('/logout', 'logout')->middleware(['auth:sanctum']);
        Route::get('/user', 'user')->middleware(['auth:sanctum']);
    });
});

Route::prefix('/authorization')->group(function () {
    Route::prefix('/permissions')->group(function () {
        Route::controller(PermissionController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.permissions.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.permissions.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.permissions.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.permissions.update']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Permission::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });

    Route::prefix('/roles')->group(function () {
        Route::controller(RoleController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.roles.all|navegation.modules.submodules.store|navegation.modules.submodules.update|users.authorization.assign|users.authorization.remove|authorization.permissions.store|authorization.permissions.update|organizational_structure.areas.positions.authorization.assign|organizational_structure.areas.positions.authorization.remove']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.roles.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.roles.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.roles.update']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Role::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });
});

Route::prefix('/banks')->group(function () {
    Route::controller(BankController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:banks.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:banks.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:banks.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:banks.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:banks.settings']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:banks.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:banks.restore']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Bank::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/cache')->group(function () {
    Route::prefix('/builders')->group(function () {
        Route::controller(BuilderController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:cache.builders.all']);
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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Category::class)->middleware(['auth:sanctum', 'can:audits.all']);

        Route::prefix('/subcategories')->group(function () {
            Route::controller(SubcategoryController::class)->group(function () {
                Route::get('/all/{category_id}', 'all')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.all|categorization.categories.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.restore']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Subcategory::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification.groups.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification.groups.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Group::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });

    Route::prefix('/subgroups')->group(function () {
        Route::controller(SubgroupController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification.subgroups.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification.subgroups.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification.subgroups.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification.subgroups.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification.subgroups.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification.subgroups.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Subgroup::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });
});

Route::prefix('/collections')->group(function () {
    Route::controller(CollectionController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:collections.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:collections.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:collections.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:collections.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:collections.settings']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:collections.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:collections.restore']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Collection::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/colors')->group(function () {
    Route::controller(ColorController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:colors.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:colors.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:colors.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:colors.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:colors.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:colors.restore']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Color::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', CompensationFund::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Employee::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Gender::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', HealthEntity::class)->middleware(['auth:sanctum', 'can:audits.all']);
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

Route::prefix('/movements')->group(function () {
    Route::controller(MovementController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:movements.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:movements.find']);
        Route::post('/save', 'save')->middleware(['auth:sanctum', 'can:movements.save']);
        Route::post('/approve', 'approve')->middleware(['auth:sanctum', 'can:movements.approve']);
        Route::post('/decline', 'decline')->middleware(['auth:sanctum', 'can:movements.decline']);
        Route::post('/cancel', 'cancel')->middleware(['auth:sanctum', 'can:movements.cancel']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Movement::class)->middleware(['auth:sanctum', 'can:audits.all']);

    Route::prefix('/production_order_details')->group(function () {
        Route::controller(ProductionOrderDetailController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:movements.production_order_details.all|movements.find|movements.save']);
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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Module::class)->middleware(['auth:sanctum', 'can:audits.all']);

        Route::prefix('/submodules')->group(function () {
            Route::controller(SubmoduleController::class)->group(function () {
                Route::get('/all/{module_id}', 'all')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.find|navegation.modules.submodules.update']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.restore']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Submodule::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Area::class)->middleware(['auth:sanctum', 'can:audits.all']);

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
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Position::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', PensionFund::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Person::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/locations')->group(function () {
    Route::prefix('/countries')->group(function () {
        Route::controller(CountryController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:locations.countries.all|people.store|people.update']);
            Route::put('/settings/{id}', 'settings')->middleware(['auth:sanctum', 'can:locations.countries.settings']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Country::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });
    Route::get('/departments/all/{country_id}', [DepartmentController::class, 'all'])->middleware(['auth:sanctum', 'can:locations.countries.all|people.store|people.update']);
    Route::get('/cities/all/{department_id}', [CityController::class, 'all'])->middleware(['auth:sanctum', 'can:locations.countries.all|people.store|people.update']);
});

Route::prefix('/pieces')->group(function () {
    Route::controller(PieceController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:pieces.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:pieces.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:pieces.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:pieces.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:pieces.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:pieces.restore']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Piece::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/productivity')->group(function () {
    Route::controller(ProductivityController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:productivity.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:productivity.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:productivity.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:productivity.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:productivity.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:productivity.restore']);
    });

    Route::prefix('/details')->group(function () {
        Route::controller(ProductivityDetailController::class)->group(function () {
            Route::get('/all/{productivity_id}', 'all')->middleware(['auth:sanctum', 'can:productivity.find']);
            Route::get('/movement/{movement_id}', 'movement')->middleware(['auth:sanctum', 'can:movements.find']);
            Route::get('/technical_sheet/{technical_sheet_id}', 'technical_sheet')->middleware(['auth:sanctum', 'can:products.find']);
            Route::get('/options/{productivity_id}', 'options')->middleware(['auth:sanctum', 'can:productivity.store']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:productivity.store']);
            Route::post('/schedule/{productivity_id}', 'schedule_store')->middleware(['auth:sanctum', 'can:productivity.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:productivity.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:productivity.delete']);
            Route::get('/reprocess/{id}', 'reprocess_all')->middleware(['auth:sanctum', 'can:productivity.reprocess']);
            Route::post('/reprocess/{id}', 'reprocess_store')->middleware(['auth:sanctum', 'can:productivity.reprocess']);
            Route::delete('/reprocess/{id}/{index}', 'reprocess_delete')->middleware(['auth:sanctum', 'can:productivity.reprocess']);
            Route::prefix('/workstation')->group(function () {
                Route::get('/mine', 'workstation_mine')->middleware(['auth:sanctum', 'can:productivity.workstation']);
                Route::get('/{token}', 'workstation')->middleware(['auth:sanctum', 'can:productivity.workstation']);
                Route::get('/{token}/reference/{uuid}', 'reference')->middleware(['auth:sanctum', 'can:productivity.workstation']);
                Route::post('/{token}/start', 'start')->middleware(['auth:sanctum', 'can:productivity.workstation']);
                Route::post('/{token}/stop', 'stop_store')->middleware(['auth:sanctum', 'can:productivity.workstation']);
                Route::post('/{token}/stop_end', 'stop_end')->middleware(['auth:sanctum', 'can:productivity.workstation']);
                Route::post('/{token}/plus', 'plus')->middleware(['auth:sanctum', 'can:productivity.workstation']);
                Route::post('/{token}/minus', 'minus')->middleware(['auth:sanctum', 'can:productivity.workstation']);
                Route::post('/{token}/finish', 'finish')->middleware(['auth:sanctum', 'can:productivity.workstation']);
            });
        });
    });
});

Route::prefix('/products')->group(function () {
    Route::controller(ProductController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:products.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:products.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:products.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:products.update']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Product::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', RiskManager::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/rules')->group(function () {
    Route::controller(RuleController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:rules.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:rules.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:rules.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:rules.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:rules.setting']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:rules.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:rules.restore']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Rule::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/sizes')->group(function () {
    Route::controller(SizeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:sizes.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings|trademarks.find']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:sizes.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:sizes.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:sizes.update']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:sizes.settings']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:sizes.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:sizes.restore']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Size::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/technical_sheets')->group(function () {
    Route::controller(TechnicalSheetController::class)->group(function () {
        Route::get('/find', 'find')->middleware(['auth:sanctum', 'can:technical_sheets.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:technical_sheets.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:technical_sheets.update']);
        Route::get('/pdf/{uuid}/{name?}', 'pdf')->middleware(['signed'])->name('technical_sheets.pdf');
        Route::get('/download_pdf/{uuid}', 'pdf')->middleware(['auth:sanctum']);
        Route::get('/process_pdf/{uuid}/{name?}', 'process_pdf')->middleware(['signed'])->name('technical_sheets.process_pdf');
        Route::get('/download_process_pdf/{uuid}', 'process_pdf')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', TechnicalSheet::class)->middleware(['auth:sanctum', 'can:audits.all']);

    Route::prefix('/production_orders')->group(function () {
        Route::controller(ProductionOrderController::class)->group(function () {
            Route::get('/all/{technical_sheet_id?}', 'all')->middleware(['auth:sanctum', 'can:technical_sheets.production_orders.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:technical_sheets.production_orders.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:technical_sheets.production_orders.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:technical_sheets.production_orders.update']);
            Route::get('/pdf/{uuid}/{name?}', 'pdf')->middleware(['signed'])->name('production_orders.technical_sheets.pdf');
            Route::get('/download_pdf/{uuid}', 'pdf')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', ProductionOrder::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });

    Route::prefix('/transformations')->group(function () {
        Route::controller(TransformationController::class)->group(function () {
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
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Trademark::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/typification')->group(function () {
    Route::prefix('/account_types')->group(function () {
        Route::controller(AccountTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:account_types.all|banks.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:account_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:account_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:account_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:account_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:account_types.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', AccountType::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });

    Route::prefix('/back_types')->group(function () {
        Route::controller(BackTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:back_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:back_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:back_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:back_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:back_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:back_types.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', BackType::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', BloodType::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });

    Route::prefix('/boot_types')->group(function () {
        Route::controller(BootTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:boot_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:boot_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:boot_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:boot_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:boot_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:boot_types.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', BootType::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', FileType::class)->middleware(['auth:sanctum', 'can:audits.all']);

        Route::prefix('/file_subtypes')->group(function () {
            Route::controller(FileSubtypeController::class)->group(function () {
                Route::get('/all/{file_type_id}', 'all')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.file_types.file_subtypes.restore']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', FileSubtype::class)->middleware(['auth:sanctum', 'can:audits.all']);
        });
    });

    Route::prefix('/garment_types')->group(function () {
        Route::controller(GarmentTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:garment_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:garment_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:garment_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:garment_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:garment_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:garment_types.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', GarmentType::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', PersonType::class)->middleware(['auth:sanctum', 'can:audits.all']);

        Route::prefix('/document_types')->group(function () {
            Route::controller(DocumentTypeController::class)->group(function () {
                Route::get('/all/{person_type_id}', 'all')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.restore']);
                Route::put('/settings/{id}', 'settings')->middleware(['auth:sanctum', 'can:typification.person_types.document_types.settings']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', DocumentType::class)->middleware(['auth:sanctum', 'can:audits.all']);
        });
    });

    Route::prefix('/stop_types')->group(function () {
        Route::controller(StopTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:stop_types.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:stop_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:stop_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:stop_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:stop_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:stop_types.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', StopType::class)->middleware(['auth:sanctum', 'can:audits.all']);

        Route::prefix('/stops')->group(function () {
            Route::controller(StopController::class)->group(function () {
                Route::get('/all/{stop_type_id?}', 'all')->middleware(['auth:sanctum', 'can:stop_types.stops.all|productivity.workstation']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:stop_types.stops.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:stop_types.stops.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:stop_types.stops.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:stop_types.stops.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:stop_types.stops.restore']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Stop::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', SupplierType::class)->middleware(['auth:sanctum', 'can:audits.all']);

        Route::prefix('/suppliers')->group(function () {
            Route::controller(SupplierController::class)->group(function () {
                Route::get('/all/{supplier_type_id}', 'all')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.all|typification.supply_types.supplies.store|typification.supply_types.supplies.update|technical_sheets.production_orders.store|technical_sheets.production_orders.update|management.production.save']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.supplier_types.suppliers.restore']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Supplier::class)->middleware(['auth:sanctum', 'can:audits.all']);
        });
    });

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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', SupplyType::class)->middleware(['auth:sanctum', 'can:audits.all']);

        Route::prefix('/supplies')->group(function () {
            Route::controller(SupplyController::class)->group(function () {
                Route::get('/all/{supply_type_id}', 'all')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.restore']);
                Route::get('/export/{supply_type_id}', 'export')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.export']);
                Route::post('/import/{supply_type_id}', 'import')->middleware(['auth:sanctum', 'can:typification.supply_types.supplies.import']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Supply::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Typology::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });

    Route::prefix('/waistband_types')->group(function () {
        Route::controller(WaistbandTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:waistband_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:waistband_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:waistband_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:waistband_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:waistband_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:waistband_types.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', WaistbandType::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });

    Route::prefix('/yoke_types')->group(function () {
        Route::controller(YokeTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:yoke_types.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:yoke_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:yoke_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:yoke_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:yoke_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:yoke_types.restore']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', YokeType::class)->middleware(['auth:sanctum', 'can:audits.all']);
    });
});

Route::prefix('/users')->group(function () {
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', User::class)->middleware(['auth:sanctum', 'can:audits.all']);
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
        Route::prefix('/workflow')->group(function () {
            Route::post('/assign/{id}', 'assign_workflow')->middleware(['auth:sanctum', 'can:users.workflow.assign']);
            Route::post('/remove/{id}', 'remove_workflow')->middleware(['auth:sanctum', 'can:users.workflow.remove']);
        });
    });
});

Route::prefix('/wash_tones')->group(function () {
    Route::controller(WashToneController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:wash_tones.all|workflow.processes.settings|workflow.processes.subprocesses.settings|workflow.processes.subprocesses.operations.settings']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:wash_tones.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:wash_tones.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:wash_tones.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:wash_tones.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:wash_tones.restore']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', WashTone::class)->middleware(['auth:sanctum', 'can:audits.all']);
});

Route::prefix('/workflow')->group(function () {
    Route::prefix('/processes')->group(function () {
        Route::controller(ProcessController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:workflow.processes.all|users.workflow.assign|employees.store|employees.update']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:workflow.processes.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:workflow.processes.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:workflow.processes.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:workflow.processes.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:workflow.processes.restore']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:workflow.processes.settings']);
            Route::get('/qr/{id}', 'qr')->middleware(['auth:sanctum', 'can:workflow.processes.qr']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Process::class)->middleware(['auth:sanctum', 'can:audits.all']);

        Route::prefix('/subprocesses')->group(function () {
            Route::controller(SubprocessController::class)->group(function () {
                Route::get('/all/{process_id?}', 'all')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.find']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.restore']);
                Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.settings']);
                Route::get('/qr/{id}', 'qr')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.qr']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Subprocess::class)->middleware(['auth:sanctum', 'can:audits.all']);

            Route::prefix('/operations')->group(function () {
                Route::controller(OperationController::class)->group(function () {
                    Route::get('/all/{subprocess_id}', 'all')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.all']);
                    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.find']);
                    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.store']);
                    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.update']);
                    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.delete']);
                    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.restore']);
                    Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.settings']);
                    Route::get('/qr/{id}', 'qr')->middleware(['auth:sanctum', 'can:workflow.processes.subprocesses.operations.qr']);
                });
                Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Operation::class)->middleware(['auth:sanctum', 'can:audits.all']);
            });
        });
    });
});
