<?php

namespace App\Mcp\Tools\Pdf;

use App\Mcp\ActorResolver;
use App\Mcp\McpFailure;
use App\Models\Product;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_pdf_link')]
#[Title('Obtener el enlace de un PDF')]
#[Description('Entrega el enlace para abrir en el navegador un PDF de Kanri: la ficha técnica de un lote (type=technical_sheet), la ficha de procesos de una referencia (type=process_sheet) o la orden de producción de un lote (type=production_order). Úsala siempre que la persona pida un PDF, una ficha o una orden para ver o descargar; el PDF nunca se pide con read_data ni se pega en el chat. La referencia puede venir con su lote (BO001-A) o separada en reference y lot; la ficha de procesos no lleva lote. Entrégale el enlace tal cual: al hacer clic se abre el PDF en el navegador. Si la referencia tiene varios lotes y no dijo cuál, pregúntaselo.')]
#[IsReadOnly]
#[IsIdempotent]
class GetPdfLinkTool extends Tool
{
    private const DOCUMENTS = [
        'technical_sheet' => 'Ficha técnica',
        'process_sheet' => 'Ficha de procesos',
        'production_order' => 'Orden de producción',
    ];

    // Los mismos permisos que pide la pantalla para mostrar el botón de cada PDF
    private const PERMISSIONS = [
        'technical_sheet' => ['products.find', 'products.update'],
        'process_sheet' => ['products.find'],
        'production_order' => ['products.find', 'products.update'],
    ];

    public function handle(Request $request): Response
    {
        try {
            $actor = app(ActorResolver::class)->resolve($request->get('ai_key'));
            $type = (string) $request->get('type');

            if (! array_key_exists($type, self::DOCUMENTS)) {
                throw McpFailure::invalid(['type' => ['Debe ser technical_sheet, process_sheet o production_order.']], 'No sé qué PDF quieres: la ficha técnica, la ficha de procesos o la orden de producción.');
            }

            $this->authorize($actor['user'], $type);

            [$code, $lot] = $this->reference((string) $request->get('reference'), $request->get('lot'));
            $document = $this->document($type, $code, $lot);
        } catch (McpFailure $failure) {
            return $failure->response();
        }

        return Response::json([
            'ok' => true,
            'document' => self::DOCUMENTS[$type],
            'reference' => $document['reference'],
            'file_name' => $document['file_name'],
            'link' => $document['link'],
            'message' => 'Entrégale este enlace a la persona: al hacer clic se abre el PDF en el navegador.',
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(array_keys(self::DOCUMENTS))->description('technical_sheet: ficha técnica de un lote. process_sheet: ficha de procesos de una referencia. production_order: orden de producción de un lote.')->required(),
            'reference' => $schema->string()->description('Referencia que pide la persona, con su lote si lo dijo. Ejemplos: ZA462046-A o ZA462046.')->required(),
            'lot' => $schema->string()->description('Lote (A, B…) cuando la referencia viene sin él. No aplica a la ficha de procesos.'),
            'ai_key' => $schema->string()->description('Llave de IA de la persona que consulta. Sin ella se trabaja con el usuario del conector, que no tiene permisos del negocio.'),
        ];
    }

    private function authorize(User $user, string $type): void
    {
        foreach (self::PERMISSIONS[$type] as $permission) {
            try {
                app(PermissionService::class)->authorize($user, [$permission]);
            } catch (HttpResponseException) {
                throw McpFailure::forbidden([$permission], 'No tienes permiso para ver este PDF en Kanri. Pídeselo a un administrador.');
            }
        }
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function reference(string $reference, mixed $lot): array
    {
        $reference = strtoupper(trim($reference));
        $lot = strtoupper(trim((string) $lot));

        if ($reference === '') {
            throw McpFailure::invalid(['reference' => ['Falta la referencia.']], 'Dime de qué referencia quieres el PDF.');
        }

        if (preg_match('/^(.+)-([A-Z]{1,2})$/', $reference, $matches)) {
            return [$matches[1], $lot ?: $matches[2]];
        }

        return [$reference, $lot ?: null];
    }

    /**
     * @return array{reference: string, file_name: string, link: string}
     */
    private function document(string $type, string $code, ?string $lot): array
    {
        $product = Product::where('code', $code)->first();
        $technical_sheet = $product?->technical_sheet ?? $product?->technical_sheets->first();

        if (! $technical_sheet) {
            throw McpFailure::notFound("No encontré la referencia {$code} con su ficha técnica en Kanri.");
        }

        if ($type === 'process_sheet') {
            if (! $technical_sheet->uuid) {
                throw McpFailure::notFound("La referencia {$code} no tiene ficha de procesos para descargar.");
            }

            return [
                'reference' => $code,
                'file_name' => $technical_sheet::processPdfName($product->code).'.pdf',
                'link' => $technical_sheet::processPdfUrl($technical_sheet->uuid, $product->code),
            ];
        }

        $lots = $technical_sheet->production_orders()->orderBy('cut')->pluck('cut')->all();

        if ($lots === []) {
            throw McpFailure::notFound("La referencia {$code} todavía no tiene lotes (órdenes de producción).");
        }

        if ($lot === null && count($lots) > 1) {
            throw McpFailure::invalid(['lot' => ['La referencia tiene varios lotes: '.implode(', ', $lots).'.']], "La referencia {$code} tiene varios lotes (".implode(', ', $lots).'). Pregúntale a la persona de cuál lote quiere el PDF.');
        }

        $production_order = $technical_sheet->production_orders()->where('cut', $lot ?? $lots[0])->first();

        if (! $production_order?->uuid) {
            throw McpFailure::notFound("No encontré el lote {$lot} de la referencia {$code}. Los lotes que tiene son: ".implode(', ', $lots).'.');
        }

        return $type === 'technical_sheet'
            ? ['reference' => $code.'-'.$production_order->cut, 'file_name' => $production_order->technicalSheetPdfName().'.pdf', 'link' => $production_order->technicalSheetPdfUrl()]
            : ['reference' => $code.'-'.$production_order->cut, 'file_name' => $production_order->pdfName().'.pdf', 'link' => $production_order->pdfUrl()];
    }
}
