<?php

use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AssistantKeyController;
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
use App\Models\Productivity;
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

Route::prefix('/assistant')->group(function () {
    Route::prefix('/key')->group(function () {
        Route::controller(AssistantKeyController::class)->group(function () {
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::delete('/delete', 'delete')->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/authorization')->group(function () {
    Route::prefix('/permissions')->group(function () {
        Route::controller(PermissionController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Permission::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/roles')->group(function () {
        Route::controller(RoleController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Role::class)->middleware(['auth:sanctum']);
    });
});

Route::prefix('/banks')->group(function () {
    Route::controller(BankController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Bank::class)->middleware(['auth:sanctum']);
});

Route::prefix('/cache')->group(function () {
    Route::prefix('/builders')->group(function () {
        Route::controller(BuilderController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{uuid}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{uuid}', 'delete')->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/categorization')->group(function () {
    Route::prefix('/categories')->group(function () {
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Category::class)->middleware(['auth:sanctum']);

        Route::prefix('/subcategories')->group(function () {
            Route::controller(SubcategoryController::class)->group(function () {
                Route::get('/all/{category_id}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Subcategory::class)->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/classification')->group(function () {
    Route::prefix('/groups')->group(function () {
        Route::controller(GroupController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Group::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/subgroups')->group(function () {
        Route::controller(SubgroupController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Subgroup::class)->middleware(['auth:sanctum']);
    });
});

Route::prefix('/collections')->group(function () {
    Route::controller(CollectionController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Collection::class)->middleware(['auth:sanctum']);
});

Route::prefix('/colors')->group(function () {
    Route::controller(ColorController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Color::class)->middleware(['auth:sanctum']);
});

Route::prefix('/compensation_funds')->group(function () {
    Route::controller(CompensationFundController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', CompensationFund::class)->middleware(['auth:sanctum']);
});

Route::prefix('/employees')->group(function () {
    Route::controller(EmployeeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Employee::class)->middleware(['auth:sanctum']);
});

Route::prefix('/genders')->group(function () {
    Route::controller(GenderController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Gender::class)->middleware(['auth:sanctum']);
});

Route::prefix('/health_entities')->group(function () {
    Route::controller(HealthEntityController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', HealthEntity::class)->middleware(['auth:sanctum']);
});

Route::prefix('/management')->group(function () {
    Route::prefix('/collections')->group(function () {
        Route::controller(ManagementCollectionController::class)->group(function () {
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/save', 'save')->middleware(['auth:sanctum']);
        });
    });

    Route::prefix('/production')->group(function () {
        Route::controller(ManagementProductionController::class)->group(function () {
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/save', 'save')->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/movements')->group(function () {
    Route::controller(MovementController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/save', 'save')->middleware(['auth:sanctum']);
        Route::post('/approve', 'approve')->middleware(['auth:sanctum']);
        Route::post('/decline', 'decline')->middleware(['auth:sanctum']);
        Route::post('/cancel', 'cancel')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Movement::class)->middleware(['auth:sanctum']);

    Route::prefix('/production_order_details')->group(function () {
        Route::controller(ProductionOrderDetailController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/navegation')->group(function () {
    Route::prefix('/modules')->group(function () {
        Route::controller(ModuleController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Module::class)->middleware(['auth:sanctum']);

        Route::prefix('/submodules')->group(function () {
            Route::controller(SubmoduleController::class)->group(function () {
                Route::get('/all/{module_id}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Submodule::class)->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/organizational_structure')->group(function () {
    Route::prefix('/areas')->group(function () {
        Route::controller(AreaController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Area::class)->middleware(['auth:sanctum']);

        Route::prefix('/positions')->group(function () {
            Route::controller(PositionController::class)->group(function () {
                Route::get('/all/{area_id}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
                Route::prefix('/authorization')->group(function () {
                    Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum']);
                    Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum']);
                });
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Position::class)->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/pension_funds')->group(function () {
    Route::controller(PensionFundController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', PensionFund::class)->middleware(['auth:sanctum']);
});

Route::prefix('/people')->group(function () {
    Route::controller(PersonController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Person::class)->middleware(['auth:sanctum']);
});

Route::prefix('/locations')->group(function () {
    Route::prefix('/countries')->group(function () {
        Route::controller(CountryController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::put('/settings/{id}', 'settings')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Country::class)->middleware(['auth:sanctum']);
    });
    Route::get('/departments/all/{country_id}', [DepartmentController::class, 'all'])->middleware(['auth:sanctum']);
    Route::get('/cities/all/{department_id}', [CityController::class, 'all'])->middleware(['auth:sanctum']);
});

Route::prefix('/pieces')->group(function () {
    Route::controller(PieceController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Piece::class)->middleware(['auth:sanctum']);
});

Route::prefix('/productivity')->group(function () {
    Route::controller(ProductivityController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Productivity::class)->middleware(['auth:sanctum']);

    Route::prefix('/details')->group(function () {
        Route::controller(ProductivityDetailController::class)->group(function () {
            Route::get('/all/{productivity_id}', 'all')->middleware(['auth:sanctum']);
            Route::get('/movement/{movement_id}', 'movement')->middleware(['auth:sanctum']);
            Route::get('/technical_sheet/{technical_sheet_id}', 'technical_sheet')->middleware(['auth:sanctum']);
            Route::get('/options/{productivity_id}', 'options')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::post('/schedule/{productivity_id}', 'schedule_store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::get('/reprocess/{id}', 'reprocess_all')->middleware(['auth:sanctum']);
            Route::post('/reprocess/{id}', 'reprocess_store')->middleware(['auth:sanctum']);
            Route::delete('/reprocess/{id}/{index}', 'reprocess_delete')->middleware(['auth:sanctum']);
            Route::prefix('/workstation')->group(function () {
                Route::get('/mine', 'workstation_mine')->middleware(['auth:sanctum']);
                Route::get('/{token}', 'workstation')->middleware(['auth:sanctum']);
                Route::get('/{token}/reference/{uuid}', 'reference')->middleware(['auth:sanctum']);
                Route::post('/{token}/start', 'start')->middleware(['auth:sanctum']);
                Route::post('/{token}/stop', 'stop_store')->middleware(['auth:sanctum']);
                Route::post('/{token}/stop_end', 'stop_end')->middleware(['auth:sanctum']);
                Route::post('/{token}/plus', 'plus')->middleware(['auth:sanctum']);
                Route::post('/{token}/minus', 'minus')->middleware(['auth:sanctum']);
                Route::post('/{token}/finish', 'finish')->middleware(['auth:sanctum']);
            });
        });
    });
});

Route::prefix('/products')->group(function () {
    Route::controller(ProductController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Product::class)->middleware(['auth:sanctum']);
});

Route::prefix('/reports')->group(function () {
    Route::controller(ReportController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/{id}/generate', 'generate')->middleware(['auth:sanctum']);
        Route::post('/{id}/export', 'export')->middleware(['auth:sanctum']);
    });
});

Route::prefix('/risk_managers')->group(function () {
    Route::controller(RiskManagerController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', RiskManager::class)->middleware(['auth:sanctum']);
});

Route::prefix('/rules')->group(function () {
    Route::controller(RuleController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Rule::class)->middleware(['auth:sanctum']);
});

Route::prefix('/sizes')->group(function () {
    Route::controller(SizeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Size::class)->middleware(['auth:sanctum']);
});

Route::prefix('/technical_sheets')->group(function () {
    Route::controller(TechnicalSheetController::class)->group(function () {
        Route::get('/find', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::get('/pdf/{uuid}/{name?}', 'pdf')->middleware(['signed'])->name('technical_sheets.pdf');
        Route::get('/process_pdf/{uuid}/{name?}', 'process_pdf')->middleware(['signed'])->name('technical_sheets.process_pdf');
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', TechnicalSheet::class)->middleware(['auth:sanctum']);

    Route::prefix('/production_orders')->group(function () {
        Route::controller(ProductionOrderController::class)->group(function () {
            Route::get('/all/{technical_sheet_id?}', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::get('/pdf/{uuid}/{name?}', 'pdf')->middleware(['signed'])->name('production_orders.technical_sheets.pdf');
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', ProductionOrder::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/transformations')->group(function () {
        Route::controller(TransformationController::class)->group(function () {
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/trademarks')->group(function () {
    Route::controller(TrademarkController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        Route::prefix('/size')->group(function () {
            Route::post('/assign/{id}', 'size_assign')->middleware(['auth:sanctum']);
            Route::post('/remove/{id}', 'size_remove')->middleware(['auth:sanctum']);
        });
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Trademark::class)->middleware(['auth:sanctum']);
});

Route::prefix('/typification')->group(function () {
    Route::prefix('/account_types')->group(function () {
        Route::controller(AccountTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', AccountType::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/back_types')->group(function () {
        Route::controller(BackTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', BackType::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/blood_types')->group(function () {
        Route::controller(BloodTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', BloodType::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/boot_types')->group(function () {
        Route::controller(BootTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', BootType::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/file_types')->group(function () {
        Route::controller(FileTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', FileType::class)->middleware(['auth:sanctum']);

        Route::prefix('/file_subtypes')->group(function () {
            Route::controller(FileSubtypeController::class)->group(function () {
                Route::get('/all/{file_type_id}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', FileSubtype::class)->middleware(['auth:sanctum']);
        });
    });

    Route::prefix('/garment_types')->group(function () {
        Route::controller(GarmentTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', GarmentType::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/person_types')->group(function () {
        Route::controller(PersonTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', PersonType::class)->middleware(['auth:sanctum']);

        Route::prefix('/document_types')->group(function () {
            Route::controller(DocumentTypeController::class)->group(function () {
                Route::get('/all/{person_type_id}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
                Route::put('/settings/{id}', 'settings')->middleware(['auth:sanctum']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', DocumentType::class)->middleware(['auth:sanctum']);
        });
    });

    Route::prefix('/stop_types')->group(function () {
        Route::controller(StopTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', StopType::class)->middleware(['auth:sanctum']);

        Route::prefix('/stops')->group(function () {
            Route::controller(StopController::class)->group(function () {
                Route::get('/all/{stop_type_id?}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Stop::class)->middleware(['auth:sanctum']);
        });
    });

    Route::prefix('/supplier_types')->group(function () {
        Route::controller(SupplierTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', SupplierType::class)->middleware(['auth:sanctum']);

        Route::prefix('/suppliers')->group(function () {
            Route::controller(SupplierController::class)->group(function () {
                Route::get('/all/{supplier_type_id}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Supplier::class)->middleware(['auth:sanctum']);
        });
    });

    Route::prefix('/supply_types')->group(function () {
        Route::controller(SupplyTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', SupplyType::class)->middleware(['auth:sanctum']);

        Route::prefix('/supplies')->group(function () {
            Route::controller(SupplyController::class)->group(function () {
                Route::get('/all/{supply_type_id}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
                Route::get('/export/{supply_type_id}', 'export')->middleware(['auth:sanctum']);
                Route::post('/import/{supply_type_id}', 'import')->middleware(['auth:sanctum']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Supply::class)->middleware(['auth:sanctum']);
        });
    });

    Route::prefix('/typologies')->group(function () {
        Route::controller(TypologyController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Typology::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/waistband_types')->group(function () {
        Route::controller(WaistbandTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', WaistbandType::class)->middleware(['auth:sanctum']);
    });

    Route::prefix('/yoke_types')->group(function () {
        Route::controller(YokeTypeController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', YokeType::class)->middleware(['auth:sanctum']);
    });
});

Route::prefix('/users')->group(function () {
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', User::class)->middleware(['auth:sanctum']);
    Route::controller(UserController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
        Route::prefix('/authorization')->group(function () {
            Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum']);
            Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum']);
        });
        Route::prefix('/workflow')->group(function () {
            Route::post('/assign/{id}', 'assign_workflow')->middleware(['auth:sanctum']);
            Route::post('/remove/{id}', 'remove_workflow')->middleware(['auth:sanctum']);
        });
    });
});

Route::prefix('/wash_tones')->group(function () {
    Route::controller(WashToneController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
        Route::post('/store', 'store')->middleware(['auth:sanctum']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
    });
    Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', WashTone::class)->middleware(['auth:sanctum']);
});

Route::prefix('/workflow')->group(function () {
    Route::prefix('/processes')->group(function () {
        Route::controller(ProcessController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
            Route::post('/store', 'store')->middleware(['auth:sanctum']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
            Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
            Route::get('/qr/{id}', 'qr')->middleware(['auth:sanctum']);
        });
        Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Process::class)->middleware(['auth:sanctum']);

        Route::prefix('/subprocesses')->group(function () {
            Route::controller(SubprocessController::class)->group(function () {
                Route::get('/all/{process_id?}', 'all')->middleware(['auth:sanctum']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                Route::post('/store', 'store')->middleware(['auth:sanctum']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
                Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
                Route::get('/qr/{id}', 'qr')->middleware(['auth:sanctum']);
            });
            Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Subprocess::class)->middleware(['auth:sanctum']);

            Route::prefix('/operations')->group(function () {
                Route::controller(OperationController::class)->group(function () {
                    Route::get('/all/{subprocess_id}', 'all')->middleware(['auth:sanctum']);
                    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
                    Route::post('/store', 'store')->middleware(['auth:sanctum']);
                    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum']);
                    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum']);
                    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum']);
                    Route::put('/setting/{id}', 'setting')->middleware(['auth:sanctum']);
                    Route::get('/qr/{id}', 'qr')->middleware(['auth:sanctum']);
                });
                Route::get('/audit/{id}', [AuditController::class, 'timeline'])->defaults('model', Operation::class)->middleware(['auth:sanctum']);
            });
        });
    });
});
