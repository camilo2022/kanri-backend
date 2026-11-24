<?php

namespace App\Services;

use HeadlessChromium\BrowserFactory;

/**
 * Genera PDF con Chrome sin ventana (chrome-php): el HTML se imprime igual que en el navegador (esquinas redondeadas,
 * sombras, SVG, columnas flexibles), cosa que dompdf no dibuja. Necesita Chrome o Chromium en el servidor (lo busca solo
 * o en la variable CHROME_PATH) y la extensión sockets de PHP.
 */
class ChromePdfService
{
    /**
     * @param  string  $html  Página completa
     * @param  array{header?: string, footer?: string, margin_top?: float, margin_bottom?: float, margin_side?: float, landscape?: bool}  $options
     *   Encabezado y pie se repiten en cada página (HTML con estilos en línea; `pageNumber` y `totalPages` como clases de
     *   span). Márgenes en milímetros. `landscape`: A4 horizontal.
     */
    public function render(string $html, array $options = []): string
    {
        $temp_dir = storage_path('app/temp');
        if (! is_dir($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }

        // Se abre como archivo para que las fuentes e imágenes locales (file://) carguen
        $file = $temp_dir.'/pdf_'.uniqid().'.html';
        file_put_contents($file, $html);

        $browser = (new BrowserFactory)->createBrowser([
            'noSandbox' => true,
            'windowSize' => [1240, 1754],
        ]);

        try {
            $page = $browser->createPage();
            $page->navigate('file:///'.str_replace('\\', '/', $file))->waitForNavigation();

            $mm = fn (float $value) => $value / 25.4;

            return base64_decode($page->pdf([
                'printBackground' => true,
                'paperWidth' => $mm(empty($options['landscape']) ? 210 : 297),
                'paperHeight' => $mm(empty($options['landscape']) ? 297 : 210),
                'marginTop' => $mm($options['margin_top'] ?? 10),
                'marginBottom' => $mm($options['margin_bottom'] ?? 10),
                'marginLeft' => $mm($options['margin_side'] ?? 12),
                'marginRight' => $mm($options['margin_side'] ?? 12),
                'displayHeaderFooter' => isset($options['header']) || isset($options['footer']),
                'headerTemplate' => $options['header'] ?? '<span></span>',
                'footerTemplate' => $options['footer'] ?? '<span></span>',
                'preferCSSPageSize' => false,
            ])->getBase64(60000));
        } finally {
            $browser->close();
            @unlink($file);
        }
    }
}
