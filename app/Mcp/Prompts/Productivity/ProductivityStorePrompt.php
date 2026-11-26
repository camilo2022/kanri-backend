<?php

namespace App\Mcp\Prompts\Productivity;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityStorePrompt extends RoutePrompt
{
    protected string $name = 'productivity_store';

    protected string $title = 'Crear un turno';

    protected string $description = 'Crear un turno';

    protected string $method = 'POST';

    protected string $path = '/productivity/store';

    protected string $business = 'Crea un turno de productividad de un proceso: fecha, hora de inicio, hora de fin y meta por hora (settings.goal_hour). Se rechaza si la fecha es anterior a hoy, si el horario se cruza con otro turno activo del mismo proceso ese día, o si el proceso se mide por lotes y no se envía la meta por hora. El estado del turno no se guarda: se calcula con el reloj (Por iniciar, En curso, Terminado). Efecto: solo crea el turno, sin programación; los puestos abren trabajo escaneando su QR dentro del horario. Observación: el Request solo valida goal_hour y el controlador guarda todo el arreglo settings recibido. La IA debe confirmar proceso, fecha, horario y meta. Es reversible mientras no inicie ni tenga registros (se desactiva).';
}
