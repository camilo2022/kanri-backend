<?php

namespace Database\Seeders;

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
use App\Models\FileSubtype;
use App\Models\FileType;
use App\Models\GarmentType;
use App\Models\Gender;
use App\Models\Group;
use App\Models\HealthEntity;
use App\Models\Item;
use App\Models\Operation;
use App\Models\PensionFund;
use App\Models\PersonType;
use App\Models\Piece;
use App\Models\Position;
use App\Models\Process;
use App\Models\Report;
use App\Models\RiskManager;
use App\Models\Rule;
use App\Models\Size;
use App\Models\Stop;
use App\Models\StopType;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Subprocess;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\Supply;
use App\Models\SupplyType;
use App\Models\Trademark;
use App\Models\Typology;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Illuminate\Database\Seeder;

class ItemsAndSubitemsSeeder extends Seeder
{
    public function run(): void
    {

        $item = new Item;
        $item->id = FileType::ITEM_ID;
        $item->name = 'Tipos de archivos';
        $item->description = 'Clasificación estandarizada de archivos según su naturaleza, uso y propósito dentro de procesos administrativos, legales o documentales.';
        $item->save();

        FileType::insert([
            ['item_id' => FileType::ITEM_ID, 'name' => 'Archivos personales', 'description' => 'Documentos relacionados con la información personal del empleado.'],
            ['item_id' => FileType::ITEM_ID, 'name' => 'Archivos multimedia', 'description' => 'Archivos en formato imagen, video, audio u otros formatos digitales.'],
            ['item_id' => FileType::ITEM_ID, 'name' => 'Archivos de productos', 'description' => 'Archivos en formato imagen o video relacionados con productos.'],
        ]);

        $item = new Item;
        $item->id = FileSubtype::ITEM_ID;
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
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Documento digital', 'description' => 'Archivo digital como PDF, Word o Excel.'],
        ]);

        // Áreas
        $item = new Item;
        $item->id = Area::ITEM_ID;
        $item->name = 'Áreas';
        $item->description = 'Listado de áreas disponibles en la organización.';
        $item->save();

        $area = new Area;
        $area->name = 'NO APLICA';
        $area->description = 'NO APLICA';
        $area->save();

        // Cargos
        $item = new Item;
        $item->id = Position::ITEM_ID;
        $item->name = 'Cargos';
        $item->description = 'Listado de cargos disponibles en la organización.';
        $item->save();

        $position = new Position;
        $position->name = 'NO APLICA';
        $position->description = 'NO APLICA';
        $position->save();

        // Asociado el cargo con el área
        $position->area()->sync([$area->id]);

        // Géneros
        $item = new Item;
        $item->id = Gender::ITEM_ID;
        $item->name = 'Géneros';
        $item->description = 'Listado de géneros disponibles.';
        $item->save();

        $gender = new Gender;
        $gender->name = 'NO APLICA';
        $gender->description = 'NO APLICA';
        $gender->save();

        // Tipos de Sangre
        $item = new Item;
        $item->id = BloodType::ITEM_ID;
        $item->name = 'Tipos de sangre';
        $item->description = 'Listado de tipos de sangre.';
        $item->save();

        $blood_type = new BloodType;
        $blood_type->name = 'NO APLICA';
        $blood_type->description = 'NO APLICA';
        $blood_type->save();

        // Administradoras de Riesgos
        $item = new Item;
        $item->id = RiskManager::ITEM_ID;
        $item->name = 'Administradora de Riesgos';
        $item->description = 'Listado de administradoras de riesgos laborales.';
        $item->save();

        $risk_manager = new RiskManager;
        $risk_manager->name = 'NO APLICA';
        $risk_manager->description = 'NO APLICA';
        $risk_manager->save();

        // Entidades de Salud
        $item = new Item;
        $item->id = HealthEntity::ITEM_ID;
        $item->name = 'Entidades de Salud';
        $item->description = 'Listado de entidades promotoras de salud.';
        $item->save();

        $health_entity = new HealthEntity;
        $health_entity->name = 'NO APLICA';
        $health_entity->description = 'NO APLICA';
        $health_entity->save();

        // Fondos de Pensión
        $item = new Item;
        $item->id = PensionFund::ITEM_ID;
        $item->name = 'Fondos de pensión';
        $item->description = 'Listado de fondos de pensión.';
        $item->save();

        $pension_fund = new PensionFund;
        $pension_fund->name = 'NO APLICA';
        $pension_fund->description = 'NO APLICA';
        $pension_fund->save();

        // Cajas de Compensación
        $item = new Item;
        $item->id = CompensationFund::ITEM_ID;
        $item->name = 'Cajas de compensación';
        $item->description = 'Listado de cajas de compensación.';
        $item->save();

        $compensation_fund = new CompensationFund;
        $compensation_fund->name = 'NO APLICA';
        $compensation_fund->description = 'NO APLICA';
        $compensation_fund->save();

        // Categorías
        $item = new Item;
        $item->id = Category::ITEM_ID;
        $item->name = 'Categorías';
        $item->description = 'Listado de categorías.';
        $item->save();

        $categories = [];

        $categories['NO APLICA'] = Category::create(['name' => 'NO APLICA', 'description' => 'NO APLICA']);
        $categories['CLASICO'] = Category::create(['name' => 'CLASICO', 'description' => 'CLASICO']);
        $categories['MOLDEADOR'] = Category::create(['name' => 'MOLDEADOR', 'description' => 'MOLDEADOR']);
        $categories['MINILOOK'] = Category::create(['name' => 'MINILOOK', 'description' => 'MINILOOK']);
        $categories['MODA'] = Category::create(['name' => 'MODA', 'description' => 'MODA']);
        $categories['MONOLOOK'] = Category::create(['name' => 'MONOLOOK', 'description' => 'MONOLOOK']);

        // Subcategorías
        $item = new Item;
        $item->id = Subcategory::ITEM_ID;
        $item->name = 'Subcategorías';
        $item->description = 'Listado de subcategorías.';
        $item->save();

        $subcategories = [
            ['name' => 'NO APLICA', 'category' => 'NO APLICA'],
            ['name' => 'JEAN SKINNY', 'category' => 'CLASICO'],
            ['name' => 'JEAN SKINNY CARGO', 'category' => 'CLASICO'],
            ['name' => 'CLASICO CABALLERO', 'category' => 'CLASICO'],
            ['name' => 'JEAN MOLDEADOR SIN BOLSILLO', 'category' => 'MOLDEADOR'],
            ['name' => 'JEAN MOLDEADOR CON BOLSILLO', 'category' => 'MOLDEADOR'],
            ['name' => 'FALDA LARGA', 'category' => 'MINILOOK'],
            ['name' => 'FALDA MIDI', 'category' => 'MINILOOK'],
            ['name' => 'FALDA CORTA', 'category' => 'MINILOOK'],
            ['name' => 'FALDA SHORT', 'category' => 'MINILOOK'],
            ['name' => 'SHORT LARGO', 'category' => 'MINILOOK'],
            ['name' => 'SHORT CORTO', 'category' => 'MINILOOK'],
            ['name' => 'SHORT MINI', 'category' => 'MINILOOK'],
            ['name' => 'BERMUDA CLASICA', 'category' => 'MINILOOK'],
            ['name' => 'BERMUDA BAGGY', 'category' => 'MINILOOK'],
            ['name' => 'BERMUDA SLIM', 'category' => 'MINILOOK'],
            ['name' => 'CHAQUETA LARGA', 'category' => 'MINILOOK'],
            ['name' => 'CHAQUETA MEDIA', 'category' => 'MINILOOK'],
            ['name' => 'CHAQUETA CORTA', 'category' => 'MINILOOK'],
            ['name' => 'TOP CORTO', 'category' => 'MINILOOK'],
            ['name' => 'TOP MEDIO', 'category' => 'MINILOOK'],
            ['name' => 'CROPPED', 'category' => 'MODA'],
            ['name' => 'PALAZZO', 'category' => 'MODA'],
            ['name' => 'STOVEPIPE', 'category' => 'MODA'],
            ['name' => 'WIDE LEG', 'category' => 'MODA'],
            ['name' => 'VESTIDO CORTO', 'category' => 'MONOLOOK'],
            ['name' => 'VESTIDO MEDIO', 'category' => 'MONOLOOK'],
            ['name' => 'VESTIDO LARGO', 'category' => 'MONOLOOK'],
            ['name' => 'BRAGA CORTA', 'category' => 'MONOLOOK'],
            ['name' => 'BRAGA MEDIA', 'category' => 'MONOLOOK'],
            ['name' => 'BRAGA LARGA', 'category' => 'MONOLOOK'],
            ['name' => 'JUMPER CORTA', 'category' => 'MONOLOOK'],
            ['name' => 'JUMPER MEDIA', 'category' => 'MONOLOOK'],
            ['name' => 'JUMPER LARGA', 'category' => 'MONOLOOK'],
        ];

        // Asociar las subcategorías con las categorías
        foreach ($subcategories as $data) {

            // Aca creo cada subcategoría y luego la asocio con la categoría correspondiente si existe en el array de categorías
            $subcategory = Subcategory::create([
                'name' => $data['name'],
                'description' => $data['name'],
            ]);

            if (isset($categories[$data['category']])) {
                $subcategory->category()->sync(
                    [$categories[$data['category']]->id]
                );
            }
        }

        // Grupos
        $item = new Item;
        $item->id = Group::ITEM_ID;
        $item->name = 'Grupos';
        $item->description = 'Listado de grupos.';
        $item->save();

        $groups = [];

        $groups['NO APLICA'] = Group::create(['name' => 'NO APLICA', 'description' => 'NO APLICA']);
        $groups['DAMA'] = Group::create(['name' => 'DAMA', 'description' => 'DAMA']);
        $groups['CABALLERO'] = Group::create(['name' => 'CABALLERO', 'description' => 'CABALLERO']);
        $groups['JUVENIL DAMA'] = Group::create(['name' => 'JUVENIL DAMA', 'description' => 'JUVENIL DAMA']);
        $groups['JUVENIL CABALLERO'] = Group::create(['name' => 'JUVENIL CABALLERO', 'description' => 'JUVENIL CABALLERO']);
        $groups['NIÑA'] = Group::create(['name' => 'NIÑA', 'description' => 'NIÑA']);
        $groups['NIÑO'] = Group::create(['name' => 'NIÑO', 'description' => 'NIÑO']);

        // Subgrupos
        $item = new Item;
        $item->id = Subgroup::ITEM_ID;
        $item->name = 'Subgrupos';
        $item->description = 'Listado de subgrupos.';
        $item->save();

        $subcategory = new Subgroup;
        $subcategory->name = 'NO APLICA';
        $subcategory->description = 'NO APLICA';
        $subcategory->save();

        // Marcas
        $item = new Item;
        $item->id = Trademark::ITEM_ID;
        $item->name = 'Marcas';
        $item->description = 'Listado de marcas.';
        $item->save();

        $trademarks = [
            [
                'name' => 'BLESS ORIGINAL',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^O5[0-9]{6}$/',
                        'message' => 'Debe iniciar con 05 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^BO[0-9]{6}$/',
                        'message' => 'Debe iniciar con BO y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'BLESS',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^5[0-9]{6}$/',
                        'message' => 'Debe iniciar con 5 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^BL[0-9]{6}$/',
                        'message' => 'Debe iniciar con BL y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'BLESS 23',
                'group' => 'CABALLERO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^8[0-9]{6}$/',
                        'message' => 'Debe iniciar con 8 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^BS[0-9]{6}$/',
                        'message' => 'Debe iniciar con BS y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'BLESS JUNIOR',
                'group' => 'JUVENIL DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^BJ[0-9]{6}$/',
                        'message' => 'Debe iniciar con BJ y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'ZARETH',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^7[0-9]{6}$/',
                        'message' => 'Debe iniciar con 7 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^ZA[0-9]{6}$/',
                        'message' => 'Debe iniciar con ZA y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'ZARETH PREMIUM',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^P7[0-9]{6}$/',
                        'message' => 'Debe iniciar con P7 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^ZP[0-9]{6}$/',
                        'message' => 'Debe iniciar con ZP y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'ZARETH CURVI',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^BJ[0-9]{6}$/',
                        'message' => 'Debe iniciar con BJ y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^ZC[0-9]{6}$/',
                        'message' => 'Debe iniciar con ZC y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'ZARETH TEENS',
                'group' => 'JUVENIL DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^4[0-9]{6}$/',
                        'message' => 'Debe iniciar con 4 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^ZT[0-9]{6}$/',
                        'message' => 'Debe iniciar con ZT y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'ZARETH REBELS',
                'group' => 'CABALLERO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^R4[0-9]{6}$/',
                        'message' => 'Debe iniciar con R4 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^ZR[0-9]{6}$/',
                        'message' => 'Debe iniciar con ZR y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'CALIFORNIA DAMA',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^CC[0-9]{6}$/',
                        'message' => 'Debe iniciar con CC y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^CD[0-9]{6}$/',
                        'message' => 'Debe iniciar con CD y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'CALIFORNIA CABALLERO',
                'group' => 'CABALLERO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^CRC[0-9]{6}$/',
                        'message' => 'Debe iniciar con CRC y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^CC[0-9]{6}$/',
                        'message' => 'Debe iniciar con CC y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'CALIFORNIA KIDS',
                'group' => 'NIÑO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^CK[0-9]{6}$/',
                        'message' => 'Debe iniciar con CK y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'MICHELL VILLAMIZAR',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^MV[0-9]{6}$/',
                        'message' => 'Debe iniciar con MV y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'MICHELL PLUS',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^MPV[0-9]{6}$/',
                        'message' => 'Debe iniciar con MPV y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^MP[0-9]{6}$/',
                        'message' => 'Debe iniciar con MP y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'NEW YORK DAMA',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^YD[0-9]{6}$/',
                        'message' => 'Debe iniciar con YD y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'NEW YORK KIDS',
                'group' => 'NIÑO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^YK[0-9]{6}$/',
                        'message' => 'Debe iniciar con YK y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'NEW YORK CABALLERO',
                'group' => 'CABALLERO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^YC[0-9]{6}$/',
                        'message' => 'Debe iniciar con YC y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'SHIREL CLASSIC',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^C9[0-9]{6}$/',
                        'message' => 'Debe iniciar con C9 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^SC[0-9]{6}$/',
                        'message' => 'Debe iniciar con SC y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'SHIREL',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^9[0-9]{6}$/',
                        'message' => 'Debe iniciar con 9 y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^SH[0-9]{6}$/',
                        'message' => 'Debe iniciar con SH y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'SHIREL PLUS',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^SP[0-9]{6}$/',
                        'message' => 'Debe iniciar con SP y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'DHARA',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^DH[0-9]{6}$/',
                        'message' => 'Debe iniciar con DH y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'STARA DENIM',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^STDM[0-9]{6}$/',
                        'message' => 'Debe iniciar con STDM y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^SD[0-9]{6}$/',
                        'message' => 'Debe iniciar con SD y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'STARA MEN',
                'group' => 'CABALLERO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^SM[0-9]{6}$/',
                        'message' => 'Debe iniciar con SM y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'STARA',
                'group' => 'CABALLERO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^STA[0-9]{6}$/',
                        'message' => 'Debe iniciar con STA y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^ST[0-9]{6}$/',
                        'message' => 'Debe iniciar con ST y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'NEON',
                'group' => 'JUVENIL CABALLERO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^N[0-9]{6}$/',
                        'message' => 'Debe iniciar con N y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'regex' => '/^NE[0-9]{6}$/',
                        'message' => 'Debe iniciar con NE y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'NEON KIDS',
                'group' => 'NIÑO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^NK[0-9]{6}$/',
                        'message' => 'Debe iniciar con NK y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'LOA CABALLERO',
                'group' => 'CABALLERO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^LC[0-9]{6}$/',
                        'message' => 'Debe iniciar con LC y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'LOA DAMA',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^LD[0-9]{6}$/',
                        'message' => 'Debe iniciar con LD y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
            [
                'name' => 'LOA KIDS',
                'group' => 'NIÑO',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^LK[0-9]{6}$/',
                        'message' => 'Debe iniciar con LK y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ],
                ],
            ],
        ];

        // Creo cada marca y luego la asocio con el grupo correspondiente si existe en el array de grupos
        foreach ($trademarks as $data) {

            $trademark = Trademark::create([
                'name' => $data['name'],
                'description' => $data['name'],
                'settings' => [
                    'validations' => $data['validations'] ?? [],
                ],
            ]);

            if (isset($groups[$data['group']])) {
                $trademark->auditSync(
                    'group',
                    [$groups[$data['group']]->id]
                );
            }
        }

        // Tallas
        $item = new Item;
        $item->id = Size::ITEM_ID;
        $item->name = 'Tallas';
        $item->description = 'Listado de tallas.';
        // Formato de nombre, descripción y código; se cambia en Tallas → Configurar
        $item->settings = (object) [
            'formats' => (object) [
                'name' => (object) [
                    'regex' => '/^T{talla}$/',
                    'message' => 'Debe ser "T" seguido de la talla (talla XXL → TXXL).',
                ],
                'description' => (object) [
                    'regex' => '/^TALLA {talla}$/',
                    'message' => 'Debe ser "Talla" seguido de la talla (talla XXL → Talla XXL).',
                ],
                'code' => (object) [
                    'regex' => '/^(?:\d[02468]|XXS|XS|S|M|L|XL|XXL|U)$/',
                    'message' => 'Debe ser la talla: un número par de 2 dígitos o XXS, XS, S, M, L, XL, XXL o U.',
                ],
            ],
        ];

        $item->save();

        $size = new Size;
        $size->name = 'NO APLICA';
        $size->description = 'NO APLICA';
        $size->save();

        // Tipos de Prenda
        $item = new Item;
        $item->id = GarmentType::ITEM_ID;
        $item->name = 'Tipos de Prenda';
        $item->description = 'Listado de tipos de prenda.';
        $item->save();

        $garment_types = [
            'NO APLICA',
            'CLASICO',
            'SEMICLASICO',
            'TIPO 1',
            'TIPO 2',
            'TIPO 3',
            'MODA',
            'SEMIMODA',
        ];

        foreach ($garment_types as $name) {
            GarmentType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        // Tipos de Bota
        $item = new Item;
        $item->id = BootType::ITEM_ID;
        $item->name = 'Tipos de Bota';
        $item->description = 'Listado de tipos de bota.';
        $item->save();

        $boot_types = [
            'AJUSTADA',
            'ANCHA',
            'ANCHA NORMAL',
            'BOTA CORDON CAUCHO',
            'CAMPANA',
            'CAMPANA FLECO',
            'DOBLADA',
            'ENCAUCHADA',
            'FLECO',
            'GUARDA POLVO',
            'NO APLICA',
            'NORMAL',
            'NORMAL CIERRE',
            'RECTA',
            'RECTA DESFLECADA',
            'RECTA NORMAL',
            'SLIM',
            'SOLTAR',
        ];

        foreach ($boot_types as $name) {
            BootType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        // Tipos de Cotilla
        $item = new Item;
        $item->id = YokeType::ITEM_ID;
        $item->name = 'Tipos de Cotilla';
        $item->description = 'Listado de tipos de cotilla.';
        $item->save();

        $yoke_types = [
            'NO APLICA',
            'CERRADORA',
            'DOBLE COTILLA',
            'SOBREPUESTA',
            'FILETE',
        ];

        foreach ($yoke_types as $name) {
            YokeType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        // Tipos de Trasero
        $item = new Item;
        $item->id = BackType::ITEM_ID;
        $item->name = 'Tipos de Trasero';
        $item->description = 'Listado de tipos de trasero.';
        $item->save();

        $back_types = [
            'NO APLICA',
            'BOLSILLO',
            'COTILLA',
            'BOLSILLO RIBETE',
            'RIBETE COTILLA',
        ];

        foreach ($back_types as $name) {
            BackType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        // Tipos de Pretina
        $item = new Item;
        $item->id = WaistbandType::ITEM_ID;
        $item->name = 'Tipos de Pretina';
        $item->description = 'Listado de tipos de pretina.';
        $item->save();

        $waistband_types = [
            'NO APLICA',
            '1 BOTÓN',
            '2 BOTÓN',
            '3 BOTÓN',
            '4 BOTÓN',
        ];

        foreach ($waistband_types as $name) {
            WaistbandType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        // Colores
        $item = new Item;
        $item->id = Color::ITEM_ID;
        $item->name = 'Colores';
        $item->description = 'Listado de colores.';
        $item->save();

        $colors = [
            ['name' => 'SIN COLOR', 'code' => '1001'],
            ['name' => 'AZUL PANTONE', 'code' => '1002'],
            ['name' => 'SALMON', 'code' => '1003'],
            ['name' => 'ANIMAL PRINT', 'code' => '1004'],
            ['name' => 'UVA', 'code' => '1005'],
            ['name' => 'MANDARINA', 'code' => '1006'],
            ['name' => 'ESTAMPADO', 'code' => '1007'],
            ['name' => 'CAMEL', 'code' => '1008'],
            ['name' => 'MILITAR', 'code' => '1009'],
            ['name' => 'AZUL REY', 'code' => '1010'],
            ['name' => 'VERDE MILITAR', 'code' => '1011'],
            ['name' => 'VINOTINTO', 'code' => '1012'],
            ['name' => 'PALOROSA', 'code' => '1013'],
            ['name' => 'AZUL', 'code' => '1014'],
            ['name' => 'AZUL CIELO', 'code' => '1015'],
            ['name' => 'VERDE OLIVA', 'code' => '1016'],
            ['name' => 'CAFE LECHE', 'code' => '1017'],
            ['name' => 'BLANCO', 'code' => '1018'],
            ['name' => 'NEGRO', 'code' => '1019'],
            ['name' => 'GRIS', 'code' => '1020'],
            ['name' => 'AZUL PETROLEO', 'code' => '1021'],
            ['name' => 'CREMA', 'code' => '1022'],
            ['name' => 'CAQUI OSCURO', 'code' => '1023'],
            ['name' => 'MOSTAZA', 'code' => '1024'],
            ['name' => 'VERDE OSCURO', 'code' => '1025'],
            ['name' => 'HUESO', 'code' => '1026'],
            ['name' => 'BEIGE', 'code' => '1027'],
            ['name' => 'LILA', 'code' => '1028'],
            ['name' => 'VIOLETA', 'code' => '1029'],
            ['name' => 'FUCSIA', 'code' => '1030'],
            ['name' => 'CLARO', 'code' => '1031'],
            ['name' => 'MEDIO OSUCRO', 'code' => '1032'],
            ['name' => 'MEDIO', 'code' => '1033'],
            ['name' => 'MEDIO CLARO', 'code' => '1034'],
            ['name' => 'OSCURO', 'code' => '1035'],
            ['name' => 'OSCURO FIJACIÓN', 'code' => '1036'],
            ['name' => 'COLOR', 'code' => '1037'],
            ['name' => 'GRIS OSCURO', 'code' => '1038'],
            ['name' => 'GRIS CLARO', 'code' => '1039'],
            ['name' => 'CLARO DIRTY', 'code' => '1040'],
            ['name' => 'MEDIO DIRTY', 'code' => '1041'],
            ['name' => 'MEDIO OSCURO DIRTY', 'code' => '1042'],
            ['name' => 'OSCURO DIRTY', 'code' => '1043'],
            ['name' => 'OSCURO RESINA', 'code' => '1044'],
        ];

        foreach ($colors as $color) {
            Color::create([
                'name' => $color['name'],
                'description' => $color['name'],
                'settings' => [
                    'code' => $color['code'],
                ],
            ]);
        }

        // Tonos de Lavado
        $item = new Item;
        $item->id = WashTone::ITEM_ID;
        $item->name = 'Tonos de Lavado';
        $item->description = 'Listado de tonos de lavado.';
        $item->save();

        $wash_tones = [
            ['name' => 'HIELO', 'code' => '1001'],
            ['name' => 'BLANCO', 'code' => '1002'],
            ['name' => 'COLOR', 'code' => '1003'],
            ['name' => 'CLARO', 'code' => '1004'],
            ['name' => 'MEDIO', 'code' => '1005'],
            ['name' => 'GRIS', 'code' => '1006'],
            ['name' => 'OSCURO PD', 'code' => '1007'],
            ['name' => 'NEGRO', 'code' => '1008'],
            ['name' => 'MEDIO CLARO', 'code' => '1009'],
            ['name' => 'MEDIO OSCURO', 'code' => '1010'],
            ['name' => 'CLARO DIRTY', 'code' => '1011'],
            ['name' => 'MEDIO DIRTY', 'code' => '1012'],
            ['name' => 'OSCURO JEAN', 'code' => '1013'],
            ['name' => 'OSCURO DIRTY', 'code' => '1014'],
            ['name' => 'FIJACION', 'code' => '1015'],
            ['name' => 'GRIS OSCURO', 'code' => '1016'],
            ['name' => 'GRIS CLARO', 'code' => '1017'],
            ['name' => 'MEDIO OSCURO DIRTY', 'code' => '1018'],
            ['name' => 'OSCURO RESINA', 'code' => '1019'],
        ];

        foreach ($wash_tones as $wash_tone) {
            WashTone::create([
                'name' => $wash_tone['name'],
                'description' => $wash_tone['name'],
                'settings' => [
                    'code' => $wash_tone['code'],
                ],
            ]);
        }

        // Procesos
        $item = new Item;
        $item->id = Process::ITEM_ID;
        $item->name = 'Procesos';
        $item->description = 'Listado de procesos.';
        $item->save();

        $processes = [
            ['name' => 'DISEÑO', 'subprocesses' => false],
            ['name' => 'CORTE', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'PREPARACION', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'SATELITE', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'CONFECCION', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'ENSAMBLE', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'PRETINTORERIA', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'LAVANDERIA', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'TERMINACIÓN', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'BODEGA', 'subprocesses' => false],
        ];

        foreach ($processes as $data) {

            Process::create([
                'name' => $data['name'],
                'description' => $data['name'],
                'settings' => [
                    'subprocesses' => $data['subprocesses'] ?? [],
                    'in_technical_sheet' => $data['in_technical_sheet'] ?? false,
                ],
            ]);
        }

        // Subprocesos
        $item = new Item;
        $item->id = Subprocess::ITEM_ID;
        $item->name = 'Subprocesos';
        $item->description = 'Listado de subprocesos.';
        $item->save();

        $subprocess = new Subprocess;
        $subprocess->name = 'NO APLICA';
        $subprocess->description = 'NO APLICA';
        $subprocess->save();

        // Operaciones
        $item = new Item;
        $item->id = Operation::ITEM_ID;
        $item->name = 'Operaciones';
        $item->description = 'Listado de operaciones.';
        $item->save();

        $operation = new Operation;
        $operation->name = 'NO APLICA';
        $operation->description = 'NO APLICA';
        $operation->save();

        // Tipos de Proveedores
        $item = new Item;
        $item->id = SupplierType::ITEM_ID;
        $item->name = 'Tipos de Proveedores';
        $item->description = 'Listado de tipos de proveedores.';
        $item->save();

        $supplier_types = [];

        $supplier_types['NO APLICA'] = SupplierType::create(['name' => 'NO APLICA', 'description' => 'NO APLICA']);

        $supplier_types['SERVICIO'] = SupplierType::create(['name' => 'SERVICIO', 'description' => 'SERVICIO']);

        $supplier_types['MATERIA PRIMA'] = SupplierType::create(['name' => 'MATERIA PRIMA', 'description' => 'MATERIA PRIMA']);

        // Tipos de Insumo
        $item = new Item;
        $item->id = SupplyType::ITEM_ID;
        $item->name = 'Tipos de Insumo';
        $item->description = 'Listado de tipos de insumo.';
        $item->save();

        $supply_types = [];

        $supply_types['NO APLICA'] = SupplyType::create(['name' => 'NO APLICA', 'description' => 'NO APLICA', 'settings' => [
            'in_technical_sheet' => false,
        ]]);

        $supply_types['TELA'] = SupplyType::create(['name' => 'TELA', 'description' => 'TELA', 'settings' => [
            'in_technical_sheet' => true,
            'has_supplies' => true,
        ]]);

        $supply_types['HILO'] = SupplyType::create(['name' => 'HILO', 'description' => 'HILO', 'settings' => [
            'in_technical_sheet' => true,
            'has_supplies' => true,
        ]]);

        // Insumos
        $item = new Item;
        $item->id = Supply::ITEM_ID;
        $item->name = 'Insumos';
        $item->description = 'Listado de insumos.';
        $item->save();

        $supplies = [
            ['name' => 'NO APLICA', 'supply_type' => 'NO APLICA'],
            ['name' => 'ROSAL', 'supply_type' => 'TELA'],
            ['name' => 'DOBLE', 'supply_type' => 'TELA'],
            ['name' => 'PASAR', 'supply_type' => 'TELA'],
            ['name' => 'NEGRO', 'supply_type' => 'HILO'],
        ];

        foreach ($supplies as $data) {

            $supply = Supply::create([
                'name' => $data['name'],
                'description' => $data['name'],
            ]);

            if (isset($supply_types[$data['supply_type']])) {
                $supply->supply_type()->sync(
                    [$supply_types[$data['supply_type']]->id]
                );
            }
        }

        // Proveedores
        $item = new Item;
        $item->id = Supplier::ITEM_ID;
        $item->name = 'Proveedores';
        $item->description = 'Listado de proveedores.';
        $item->save();

        $suppliers = [
            ['name' => 'TEXTILES Y CONFECCIONES DEL NORTE S.A.S.', 'description' => 'PROVEEDOR DE INSUMOS Y SERVICIOS TEXTILES', 'supplier_type' => 'SERVICIO'],
            ['name' => 'POLYTEXTILES S.A.S.', 'description' => 'PROVEEDOR DE MATERIA PRIMA', 'supplier_type' => 'MATERIA PRIMA'],
            ['name' => 'TEXTILES ANDINOS S.A.S.', 'description' => 'PROVEEDOR DE MATERIAS PRIMAS TEXTILES', 'supplier_type' => 'MATERIA PRIMA'],
        ];

        foreach ($suppliers as $data) {

            $supplier = Supplier::create([
                'name' => $data['name'],
                'description' => $data['description'],
            ]);

            if (isset($supply_types[$data['supplier_type']])) {
                $supplier->supplier_type()->sync(
                    [$supply_types[$data['supplier_type']]->id]
                );
            }
        }

        // Colecciones
        $item = new Item;
        $item->id = Collection::ITEM_ID;
        $item->name = 'Colección';
        $item->description = 'Listado de colecciones.';
        // Formato de nombre, descripción y código; se cambia en Colecciones → Configurar
        $item->settings = (object) [
            'formats' => (object) [
                'name' => (object) [
                    'regex' => '/^C(?<numero>\d)(?<anio>(?:19|20)\d{2})$/',
                    'message' => 'Debe ser "C", un número de una cifra y el año (C52026).',
                ],
                'description' => (object) [
                    'regex' => '/^.+ {anio}$/',
                    'message' => 'Debe terminar en el año del nombre (COLOMBIA MODA 2026).',
                ],
                'code' => (object) [
                    'regex' => '/^0{numero}{anio}$/',
                    'message' => 'Debe ser 0, el número y el año del nombre: 6 cifras (C52026 → 052026).',
                ],
            ],
        ];
        $item->save();

        $collections = [
            [
                'name' => 'NO APLICA',
                'description' => 'NO APLICA',
                'settings' => [
                    'code' => 'NOAP',
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-12-31',
                ],
            ],
            [
                'name' => 'COLOMBIA MODA',
                'description' => 'COLOMBIA MODA',
                'settings' => [
                    'code' => 'C52026',
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-07-31',
                ],
            ],
            [
                'name' => 'VERANO URBANO',
                'description' => 'COLECCIÓN VERANO URBANO',
                'settings' => [
                    'code' => 'VU2026',
                    'start_date' => '2026-03-01',
                    'end_date' => '2026-05-31',
                ],
            ],
            [
                'name' => 'DENIM ESSENCE',
                'description' => 'COLECCIÓN DENIM ESSENCE',
                'settings' => [
                    'code' => 'DE2026',
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-08-31',
                ],
            ],
            [
                'name' => 'OTOÑO ELEGANTE',
                'description' => 'COLECCIÓN OTOÑO ELEGANTE',
                'settings' => [
                    'code' => 'OE2026',
                    'start_date' => '2026-09-01',
                    'end_date' => '2026-11-30',
                ],
            ],
        ];

        foreach ($collections as $data) {
            Collection::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'settings' => $data['settings'],
            ]);
        }

        // Piezas
        $item = new Item;
        $item->id = Piece::ITEM_ID;
        $item->name = 'Piezas';
        $item->description = 'Listado de piezas.';
        $item->save();

        $piece = new Piece;
        $piece->name = 'NO APLICA';
        $piece->description = 'NO APLICA';
        $piece->save();

        // Tipos de Personas
        $item = new Item;
        $item->id = PersonType::ITEM_ID;
        $item->name = 'Tipos de Personas';
        $item->description = 'Listado de tipos de personas.';
        $item->save();

        $person_type = new PersonType;
        $person_type->name = 'NO APLICA';
        $person_type->description = 'NO APLICA';
        $person_type->save();

        // Tipos de Documentos
        $item = new Item;
        $item->id = DocumentType::ITEM_ID;
        $item->name = 'Tipos de Documentos';
        $item->description = 'Listado de tipos de documentos.';
        $item->save();

        $document_type = new DocumentType;
        $document_type->name = 'NO APLICA';
        $document_type->description = 'NO APLICA';
        $document_type->save();

        // Bancos
        $item = new Item;
        $item->id = Bank::ITEM_ID;
        $item->name = 'Bancos';
        $item->description = 'Listado de bancos.';
        $item->save();

        $bank = new Bank;
        $bank->name = 'NO APLICA';
        $bank->description = 'NO APLICA';
        $bank->save();

        // Tipos de Cuenta
        $item = new Item;
        $item->id = AccountType::ITEM_ID;
        $item->name = 'Tipos de Cuenta';
        $item->description = 'Listado de tipos de cuenta.';
        $item->save();

        $account_type = new AccountType;
        $account_type->name = 'NO APLICA';
        $account_type->description = 'NO APLICA';
        $account_type->save();

        // Reportes
        $item = new Item;
        $item->id = Report::ITEM_ID;
        $item->name = 'Reportes';
        $item->description = 'Listado de reportes.';
        $item->save();

        $report = new Report;
        $report->name = 'NO APLICA';
        $report->description = 'NO APLICA';
        $report->save();

        // Tipologías
        $item = new Item;
        $item->id = Typology::ITEM_ID;
        $item->name = 'Tipologías';
        $item->description = 'Listado de tipologías.';
        $item->save();

        $typology = new Typology;
        $typology->name = 'NO APLICA';
        $typology->description = 'NO APLICA';
        $typology->save();

        // Tipos de parada, paradas y reglas: catálogos que se llenan desde la aplicación
        $item = new Item;
        $item->id = StopType::ITEM_ID;
        $item->name = 'Tipos de Parada';
        $item->description = 'Listado de tipos de parada';
        $item->save();

        $item = new Item;
        $item->id = Stop::ITEM_ID;
        $item->name = 'Parada';
        $item->description = 'Listado paradas';
        $item->save();

        $item = new Item;
        $item->id = Rule::ITEM_ID;
        $item->name = 'Reglas';
        $item->description = 'Estándares de tiempo por proceso, subproceso u operación';
        $item->settings = [
            'scope' => [
                ['key' => 'process', 'required' => true],
                ['key' => 'subprocess', 'required' => false],
                ['key' => 'operation', 'required' => false],
            ],
            'weights' => ['group' => 2, 'category' => 3, 'trademark' => 1, 'subcategory' => 4],
        ];
        $item->save();
    }
}
