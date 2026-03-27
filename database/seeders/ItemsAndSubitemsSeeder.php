<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Arl;
use App\Models\BloodType;
use App\Models\CompensationFund;
use App\Models\Eps;
use App\Models\FileSubtype;
use App\Models\FileType;
use App\Models\Gender;
use App\Models\Item;
use App\Models\PensionFund;
use App\Models\Position;
use Illuminate\Database\Seeder;

class ItemsAndSubitemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $item = new Item();
        $item->name = 'Tipos de archivos';
        $item->description = 'Clasificación estandarizada de archivos según su naturaleza, uso y propósito dentro de procesos administrativos, legales o documentales.';
        $item->save();

        FileType::insert([
            ['item_id' => FileType::ITEM_ID, 'name' => 'Archivos personales', 'description' => 'Documentos relacionados con la información personal del empleado.'],
            ['item_id' => FileType::ITEM_ID, 'name' => 'Archivos multimedia', 'description' => 'Archivos en formato imagen, video, audio u otros formatos digitales.'],
        ]);

        $item = new Item();
        $item->name = 'Subtipos de archivos';
        $item->description = 'Clasificación específica de archivos según su contenido y función.';
        $item->save();

        FileSubtype::insert([
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Documento de identidad', 'description' => 'Cédula, tarjeta de identidad o documento oficial.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Hoja de vida', 'description' => 'Currículum vitae del empleado.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Certificados laborales', 'description' => 'Soportes de experiencia laboral previa.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Certificados académicos', 'description' => 'Diplomas, actas o certificados de estudio.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'RUT', 'description' => 'Registro Único Tributario.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Foto', 'description' => 'Fotografía del empleado.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Imagen', 'description' => 'Archivo de imagen (JPG, PNG, etc.).'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Video', 'description' => 'Archivo de video (MP4, AVI, etc.).'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Audio', 'description' => 'Archivo de audio (MP3, WAV, etc.).'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Documento digital', 'description' => 'Archivo digital como PDF, Word o Excel.']
        ]);

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
