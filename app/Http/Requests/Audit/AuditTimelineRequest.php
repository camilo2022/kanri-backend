<?php

namespace App\Http\Requests\Audit;

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
use App\Models\Country;
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
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuditTimelineRequest extends FormRequest
{
    public const PERMISSIONS = [
        Permission::class => 'authorization.permissions.audit',
        Role::class => 'authorization.roles.audit',
        Bank::class => 'banks.audit',
        Category::class => 'categorization.categories.audit',
        Subcategory::class => 'categorization.categories.subcategories.audit',
        Group::class => 'classification.groups.audit',
        Subgroup::class => 'classification.subgroups.audit',
        Collection::class => 'collections.audit',
        Color::class => 'colors.audit',
        CompensationFund::class => 'compensation_funds.audit',
        Employee::class => 'employees.audit',
        Gender::class => 'genders.audit',
        HealthEntity::class => 'health_entities.audit',
        Movement::class => 'movements.audit',
        Module::class => 'navegation.modules.audit',
        Submodule::class => 'navegation.modules.submodules.audit',
        Area::class => 'organizational_structure.areas.audit',
        Position::class => 'organizational_structure.areas.positions.audit',
        PensionFund::class => 'pension_funds.audit',
        Person::class => 'people.audit',
        Country::class => 'locations.countries.audit',
        Piece::class => 'pieces.audit',
        Product::class => 'products.audit',
        TechnicalSheet::class => 'products.audit',
        RiskManager::class => 'risk_managers.audit',
        Rule::class => 'rules.audit',
        Size::class => 'sizes.audit',
        ProductionOrder::class => 'technical_sheets.production_orders.audit',
        Productivity::class => 'productivity.audit',
        Trademark::class => 'trademarks.audit',
        AccountType::class => 'account_types.audit',
        BackType::class => 'back_types.audit',
        BloodType::class => 'blood_types.audit',
        BootType::class => 'boot_types.audit',
        FileType::class => 'typification.file_types.audit',
        FileSubtype::class => 'typification.file_types.file_subtypes.audit',
        GarmentType::class => 'garment_types.audit',
        PersonType::class => 'typification.person_types.audit',
        DocumentType::class => 'typification.person_types.document_types.audit',
        StopType::class => 'stop_types.audit',
        Stop::class => 'stop_types.stops.audit',
        SupplierType::class => 'typification.supplier_types.audit',
        Supplier::class => 'typification.supplier_types.suppliers.audit',
        SupplyType::class => 'typification.supply_types.audit',
        Supply::class => 'typification.supply_types.supplies.audit',
        Typology::class => 'typification.typologies.audit',
        WaistbandType::class => 'waistband_types.audit',
        YokeType::class => 'yoke_types.audit',
        User::class => 'users.audit',
        WashTone::class => 'wash_tones.audit',
        Process::class => 'workflow.processes.audit',
        Subprocess::class => 'workflow.processes.subprocesses.audit',
        Operation::class => 'workflow.processes.subprocesses.operations.audit',
    ];

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        // Cada historial pide la auditoría de su pantalla (rol → permiso .audit); el modelo viene del defaults de la ruta
        $permission = self::PERMISSIONS[$this->route()?->defaults['model'] ?? null] ?? null;

        return $permission ? [$permission] : [];
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
        ];
    }
}
