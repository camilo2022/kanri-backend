<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\BackType;
use App\Models\RiskManager;
use App\Models\BloodType;
use App\Models\BootType;
use App\Models\Category;
use App\Models\ClothingLine;
use App\Models\Collection;
use App\Models\Color;
use App\Models\CompensationFund;
use App\Models\HealthEntity;
use App\Models\FileSubtype;
use App\Models\FileType;
use App\Models\GarmentType;
use App\Models\Gender;
use App\Models\Group;
use App\Models\Item;
use App\Models\PensionFund;
use App\Models\Position;
use App\Models\Process;
use App\Models\Silhouette;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Subprocess;
use App\Models\Trademark;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Illuminate\Database\Seeder;

class ItemsAndSubitemsSeeder extends Seeder
{
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
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Foto', 'description' => 'Fotografía.'],
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
        $item->name = 'Administrador de Riesgos';
        $item->description = 'Listado de administradoras de riesgos laborales.';
        $item->save();

        $risk_manager = new RiskManager();
        $risk_manager->name = 'No aplica';
        $risk_manager->description = 'No aplica';
        $risk_manager->save();

        $item = new Item();
        $item->name = 'Entidades de Salud';
        $item->description = 'Listado de entidades promotoras de salud.';
        $item->save();

        $health_entity = new HealthEntity();
        $health_entity->name = 'No aplica';
        $health_entity->description = 'No aplica';
        $health_entity->save();

        $item = new Item();
        $item->name = 'Fondos de pensión';
        $item->description = 'Listado de fondos de pensión.';
        $item->save();

        $pension_fund = new PensionFund();
        $pension_fund->name = 'No aplica';
        $pension_fund->description = 'No aplica';
        $pension_fund->save();

        $item = new Item();
        $item->name = 'Cajas de compensación';
        $item->description = 'Listado de cajas de compensación.';
        $item->save();

        $compensation_fund = new CompensationFund();
        $compensation_fund->name = 'No aplica';
        $compensation_fund->description = 'No aplica';
        $compensation_fund->save();

        $item = new Item();
        $item->name = 'Siluetas';
        $item->description = 'Listado de siluetas.';
        $item->save();

        $silhouette = new Silhouette();
        $silhouette->name = 'No aplica';
        $silhouette->description = 'No aplica';
        $silhouette->save();

        $item = new Item();
        $item->name = 'Categorías';
        $item->description = 'Listado de categorías.';
        $item->save();

        $item = new Item();
        $item->name = 'Marcas';
        $item->description = 'Listado de marcas.';
        $item->save();

        $categories = [];

        $categories['DAMA'] = Category::create(['name' => 'DAMA', 'description' => 'DAMA']);
        $categories['CABALLERO'] = Category::create(['name' => 'CABALLERO', 'description' => 'CABALLERO']);
        $categories['JUVENIL DAMA'] = Category::create(['name' => 'JUVENIL DAMA', 'description' => 'JUVENIL DAMA']);
        $categories['JUVENIL CABALLERO'] = Category::create(['name' => 'JUVENIL CABALLERO', 'description' => 'JUVENIL CABALLERO']);
        $categories['NIÑA'] = Category::create(['name' => 'NIÑA', 'description' => 'NIÑA']);
        $categories['NIÑO'] = Category::create(['name' => 'NIÑO', 'description' => 'NIÑO']);

        $trademarks = [
            [
                'name' => 'BLESS ORIGINAL',
                'category' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '^(05[0-9]{6}|BO[0-9]{6})$',
                        'message' => 'Debe iniciar con 05 o BO y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ]
                ]
            ],
            [
                'name' => 'BLESS',
                'category' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '^(5[0-9]{6}|BL[0-9]{6})$',
                        'message' => 'Debe iniciar con 5 o BL y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ]
                ]
            ],
            ['name' => 'BLESS 23', 'category' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(8[0-9]{6}|BS[0-9]{6})$',
                    'message' => 'Debe iniciar con 8 o BS y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'BLESS JUNIOR', 'category' => 'JUVENIL DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(BJ[0-9]{6})$',
                    'message' => 'Debe iniciar con BJ y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(7[0-9]{6}|ZA[0-9]{6})$',
                    'message' => 'Debe iniciar con 7 o ZA y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH PREMIUM', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(P7[0-9]{6}|ZP[0-9]{6})$',
                    'message' => 'Debe iniciar con P7 o ZP y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH CURVI', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(BJ[0-9]{6}|ZC[0-9]{6})$',
                    'message' => 'Debe iniciar con BJ o ZC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH TEENS', 'category' => 'JUVENIL DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(4[0-9]{6}|ZT[0-9]{6})$',
                    'message' => 'Debe iniciar con 4 o ZT y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH REBELS', 'category' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(R4[0-9]{6}|ZR[0-9]{6})$',
                    'message' => 'Debe iniciar con R4 o ZR y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'CALIFORNIA DAMA', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(CC[0-9]{6}|CD[0-9]{6})$',
                    'message' => 'Debe iniciar con CC o CD y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'CALIFORNIA CABALLERO', 'category' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(CRC[0-9]{6}|CC[0-9]{6})$',
                    'message' => 'Debe iniciar con CRC o CC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'CALIFORNIA KIDS', 'category' => 'NIÑO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(CK[0-9]{6})$',
                    'message' => 'Debe iniciar con CK y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'MICHELL VILLAMIZAR', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(MV[0-9]{6})$',
                    'message' => 'Debe iniciar con MV y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'MICHELL PLUS', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(MPV[0-9]{6}|MP[0-9]{6})$',
                    'message' => 'Debe iniciar con MPV oMP y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEW YORK DAMA', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(YD[0-9]{6})$',
                    'message' => 'Debe iniciar con YD y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEW YORK KIDS', 'category' => 'NIÑO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(YK[0-9]{6})$',
                    'message' => 'Debe iniciar con YK y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEW YORK CABALLERO', 'category' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(YC[0-9]{6})$',
                    'message' => 'Debe iniciar con YC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'SHIREL CLASSIC', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(C9[0-9]{6}|SC[0-9]{6})$',
                    'message' => 'Debe iniciar con C9 o SC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'SHIREL', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(9[0-9]{6}|SH[0-9]{6})$',
                    'message' => 'Debe iniciar con 9 o SH y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'SHIREL PLUS', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(SP[0-9]{6})$',
                    'message' => 'Debe iniciar con SP y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'DHARA', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(DH[0-9]{6})$',
                    'message' => 'Debe iniciar con DH y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'STARA DENIM', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(STDM[0-9]{6}|SD[0-9]{6})$',
                    'message' => 'Debe iniciar con STDM o SD y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'STARA MEN', 'category' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(SM[0-9]{6})$',
                    'message' => 'Debe iniciar con SM y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'STARA', 'category' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(STA[0-9]{6}|ST[0-9]{6})$',
                    'message' => 'Debe iniciar con STA o ST y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEON', 'category' => 'JUVENIL CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(N[0-9]{6}|NE[0-9]{6})$',
                    'message' => 'Debe iniciar con N o NE y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEON KIDS', 'category' => 'NIÑO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(NK[0-9]{6})$',
                    'message' => 'Debe iniciar con NK y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'LOA CABALLERO', 'category' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(LC[0-9]{6})$',
                    'message' => 'Debe iniciar con LC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'LOA DAMA', 'category' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(LD[0-9]{6})$',
                    'message' => 'Debe iniciar con LD y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'LOA KIDS', 'category' => 'NIÑO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '^(LK[0-9]{6})$',
                    'message' => 'Debe iniciar con LK y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]]
        ];

        foreach ($trademarks as $data) {

            $trademark = Trademark::create([
                'name' => $data['name'],
                'description' => $data['name'],
                'settings' => [
                    'validations' => $data['validations'] ?? []
                ]
            ]);

            if (isset($categories[$data['category']])) {
                $trademark->auditSync(
                    'category',
                    [$categories[$data['category']]->id]
                );
            }
        }


        $item = new Item();
        $item->name = 'Subcategorías';
        $item->description = 'Listado de subcategorías.';
        $item->save();

        $subcategory = new Subcategory();
        $subcategory->name = 'No aplica';
        $subcategory->description = 'No aplica';
        $subcategory->save();

        $item = new Item();
        $item->name = 'Tallas';
        $item->description = 'Listado de tallas.';
        $item->save();

        $size = new Size();
        $size->name = 'No aplica';
        $size->description = 'No aplica';
        $size->save();

        $item = new Item();
        $item->name = 'Subgrupos';
        $item->description = 'Listado de subgrupos.';
        $item->save();

        $subcategory = new Subgroup();
        $subcategory->name = 'No aplica';
        $subcategory->description = 'No aplica';
        $subcategory->save();

        $item = new Item();
        $item->name = 'Grupos';
        $item->description = 'Listado de grupos.';
        $item->save();

        $group = new Group();
        $group->name = 'No aplica';
        $group->description = 'No aplica';
        $group->save();

        $item = new Item();
        $item->name = 'Tipos de Prenda';
        $item->description = 'Listado de tipos de prenda.';
        $item->save();

        $garment_type = new GarmentType();
        $garment_type->name = 'No aplica';
        $garment_type->description = 'No aplica';
        $garment_type->save();

        $item = new Item();
        $item->name = 'Tipos de Bota';
        $item->description = 'Listado de tipos de bota.';
        $item->save();

        $boot_type = new BootType();
        $boot_type->name = 'No aplica';
        $boot_type->description = 'No aplica';
        $boot_type->save();

        $item = new Item();
        $item->name = 'Líneas';
        $item->description = 'Listado de líneas.';
        $item->save();

        $clothing_line = new ClothingLine();
        $clothing_line->name = 'No aplica';
        $clothing_line->description = 'No aplica';
        $clothing_line->save();

        $item = new Item();
        $item->name = 'Tipos de Trasero';
        $item->description = 'Listado de tipos de trasero.';
        $item->save();

        $back_type = new BackType();
        $back_type->name = 'No aplica';
        $back_type->description = 'No aplica';
        $back_type->save();

        $item = new Item();
        $item->name = 'Tipos de Pretina';
        $item->description = 'Listado de tipos de pretina.';
        $item->save();

        $waistband_type = new WaistbandType();
        $waistband_type->name = 'No aplica';
        $waistband_type->description = 'No aplica';
        $waistband_type->save();

        $item = new Item();
        $item->name = 'Procesos';
        $item->description = 'Listado de procesos.';
        $item->save();

        $processes = [
            ['name' => 'DISEÑO', 'subprocesses' => false],
            ['name' => 'CORTE', 'subprocesses' => false],
            ['name' => 'PREPARACION', 'subprocesses' => false],
            ['name' => 'SATELITE', 'subprocesses' => false],
            ['name' => 'CONFECCION', 'subprocesses' => false],
            ['name' => 'ENSAMBLE', 'subprocesses' => false],
            ['name' => 'PRETINTORERIA', 'subprocesses' => false],
            ['name' => 'LAVANDERIA', 'subprocesses' => false],
            ['name' => 'TERMINACIÓN', 'subprocesses' => false],
            ['name' => 'BODEGA', 'subprocesses' => false],
        ];

        foreach ($processes as $data) {

            Process::create([
                'name' => $data['name'],
                'description' => $data['name'],
                'settings' => [
                    'subprocesses' => $data['subprocesses'] ?? []
                ]
            ]);
        }

        $item = new Item();
        $item->name = 'Subprocesos';
        $item->description = 'Listado de subprocesos.';
        $item->save();

        $subprocess = new Subprocess();
        $subprocess->name = 'No aplica';
        $subprocess->description = 'No aplica';
        $subprocess->save();

        $item = new Item();
        $item->name = 'Tipos de Cotilla';
        $item->description = 'Listado de tipos de cotilla.';
        $item->save();

        $yoke_type = new YokeType();
        $yoke_type->name = 'No aplica';
        $yoke_type->description = 'No aplica';
        $yoke_type->save();

        $item = new Item();
        $item->name = 'Colección';
        $item->description = 'Listado de colecciones.';
        $item->save();

        $collection = new Collection();
        $collection->name = 'No aplica';
        $collection->description = 'No aplica';
        $collection->save();

        $item = new Item();
        $item->name = 'Colores';
        $item->description = 'Listado de colores.';
        $item->save();

        $color = new Color();
        $color->name = 'No aplica';
        $color->description = 'No aplica';
        $color->save();

        $item = new Item();
        $item->name = 'Tonos de Lavado';
        $item->description = 'Listado de tonos de lavado.';
        $item->save();

        $wash_tone = new WashTone();
        $wash_tone->name = 'No aplica';
        $wash_tone->description = 'No aplica';
        $wash_tone->save();
    }
}
