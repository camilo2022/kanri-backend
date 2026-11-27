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
    private function home(): ?string
    {
        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? null);

        if (! $home && function_exists('posix_getpwuid')) {
            $home = posix_getpwuid(posix_geteuid())['dir'] ?? null;
        }

        if (! $home && preg_match('#^(/home[0-9]*/[^/]+)/#', base_path().'/', $matches)) {
            $home = $matches[1];
        }

        return $home ?: null;
    }

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

        // El servidor web arranca a Chrome sin HOME y entonces no encuentra las fuentes de ~/.fonts, que el pie necesita
        // La ruta sale de la configuración y no del .env, para que siga funcionando cuando la configuración está en caché
        $browser = (new BrowserFactory(config('services.chrome.path') ?: null))->createBrowser([
            'noSandbox' => true,
            'windowSize' => [1240, 1754],
            'envVariables' => array_filter(['HOME' => $this->home()]),
        ]);

        try {
            $page = $browser->createPage();
            $page->navigate('file:///'.str_replace('\\', '/', $file))->waitForNavigation();

            // Sin esperar, en un servidor sin fuentes instaladas se imprime antes de que carguen las incrustadas y el texto sale invisible
            $page->evaluate('Promise.all(Array.from(document.fonts).map(font => font.load().catch(() => null))).then(() => document.fonts.ready).then(() => true)')->getReturnValue(30000);

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
