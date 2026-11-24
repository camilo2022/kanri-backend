<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\BloodType;
use App\Models\CompensationFund;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Gender;
use App\Models\HealthEntity;
use App\Models\PensionFund;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\RiskManager;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::all();
        $permissions = Permission::all();

        $person = new Person();
        $person->document_type_id = DocumentType::where('name', 'NO APLICA')->value('id');
        $person->document = '0000000000';
        $person->names = 'Super';
        $person->last_names = 'Admin';
        $person->email = 'superadmin@kanri.com';
        $person->gender_id = Gender::where('name', 'NO APLICA')->value('id');
        $person->birth_date = '1990-01-01';
        $person->blood_type_id = BloodType::where('name', 'NO APLICA')->value('id');
        $person->address = 'Dirección principal';
        $person->phone = '0000000000';
        $person->save();

        $employee = new Employee();
        $employee->person_id = $person->id;
        $employee->operation_center = 'Principal';
        $employee->position_id = Position::where('name', 'NO APLICA')->value('id');
        $employee->risk_manager_id = RiskManager::where('name', 'NO APLICA')->value('id');
        $employee->health_entity_id = HealthEntity::where('name', 'NO APLICA')->value('id');
        $employee->pension_fund_id = PensionFund::where('name', 'NO APLICA')->value('id');
        $employee->compensation_fund_id = CompensationFund::where('name', 'NO APLICA')->value('id');
        $employee->start_date = Carbon::now();
        $employee->end_date = null;
        $employee->save();

        $user = new User();
        $user->employee_id = $employee->id;
        $user->username = DB::select('CALL generate_username(?)', [$person->id])[0]->username;
        $user->password = Hash::make('P4ssw0rd.');
        $user->save();

        $user->syncRoles($roles);
        $user->syncPermissions($permissions);
    }
}
