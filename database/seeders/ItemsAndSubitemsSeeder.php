<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Arl;
use App\Models\BloodType;
use App\Models\CompensationFund;
use App\Models\Eps;
use App\Models\Gender;
use App\Models\Item;
use App\Models\PensionFund;
use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemsAndSubitemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $item = new Item();
        $item->name = 'Áreas';
        $item->description = 'Listado de áreas disponibles en la organización.';
        $item->save();

        $area = new Area();
        $area->name = 'No aplica';
        $area->description = 'No aplica';
        $area->save();

        $item = new Item();
        $item->name = 'Cargos';
        $item->description = 'Listado de cargos disponibles en la organización.';
        $item->save();

        $position = new Position();
        $position->name = 'No aplica';
        $position->description = 'No aplica';
        $position->save();

        $position->area()->sync([$area->id]);

        $item = new Item();
        $item->name = 'Géneros';
        $item->description = 'Listado de géneros disponibles.';
        $item->save();

        $gender = new Gender();
        $gender->name = 'No aplica';
        $gender->description = 'No aplica';
        $gender->save();

        $item = new Item();
        $item->name = 'Tipos de sangre';
        $item->description = 'Listado de tipos de sangre.';
        $item->save();

        $blood_type = new BloodType();
        $blood_type->name = 'No aplica';
        $blood_type->description = 'No aplica';
        $blood_type->save();

        $item = new Item();
        $item->name = 'ARL';
        $item->description = 'Listado de administradoras de riesgos laborales.';
        $item->save();

        $arl = new Arl();
        $arl->name = 'No aplica';
        $arl->description = 'No aplica';
        $arl->save();

        $item = new Item();
        $item->name = 'EPS';
        $item->description = 'Listado de entidades promotoras de salud.';
        $item->save();

        $eps = new Eps();
        $eps->name = 'No aplica';
        $eps->description = 'No aplica';
        $eps->save();

        $item = new Item();
        $item->name = 'Fondo de pensión';
        $item->description = 'Listado de fondos de pensión.';
        $item->save();

        $pension_fund = new PensionFund();
        $pension_fund->name = 'No aplica';
        $pension_fund->description = 'No aplica';
        $pension_fund->save();

        $item = new Item();
        $item->name = 'Caja de compensación';
        $item->description = 'Listado de cajas de compensación.';
        $item->save();

        $compensation_fund = new CompensationFund();
        $compensation_fund->name = 'No aplica';
        $compensation_fund->description = 'No aplica';
        $compensation_fund->save();
    }
}
