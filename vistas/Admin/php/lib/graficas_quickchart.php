<?php
/**
 * php/lib/graficas_quickchart.php
 * -------------------------------------------------------------------------
 * DomPDF NO ejecuta JavaScript ni <canvas>, así que las gráficas de
 * Chart.js que se ven en el dashboard NO se pueden insertar tal cual
 * en el PDF. La solución estándar es generar la gráfica como una
 * IMAGEN estática y ponerla en el PDF con un <img>.
 *
 * Aquí usamos QuickChart.io (https://quickchart.io), un servicio gratuito
 * que recibe una configuración de Chart.js por URL y devuelve un PNG.
 * Esto evita tener que instalar Node/Puppeteer o una librería de
 * gráficas nativa de PHP.
 *
 * IMPORTANTE — requisitos para que esto funcione:
 *   1. El servidor donde corre generar_pdf.php debe tener salida a
 *      internet (para llamar a quickchart.io).
 *   2. En generar_pdf.php, DomPDF debe tener la opción:
 *        $options->set('isRemoteEnabled', true);
 *      para poder descargar imágenes remotas.
 *
 * ALTERNATIVA SIN INTERNET (si tu servidor está en una red cerrada):
 *   - Self-hostear QuickChart con Docker (misma API, sin salir a internet).
 *   - O generar las gráficas con la extensión GD de PHP directamente
 *     (más trabajo, pero sin dependencias externas). Si prefieres esa
 *     ruta, dime y te preparo esa variante.
 * -------------------------------------------------------------------------
 */

/**
 * Paleta de marca ServiceCore, para que las gráficas del PDF combinen
 * con el resto del documento y con el dashboard en pantalla.
 */
function paletaServiceCore(): array
{
    return [
        'ink'         => '#1e1858',
        'royal'       => '#5750ad',
        'periwinkle'  => '#7773eb',
        'mist'        => '#dfe7f4',
        // colores semánticos para estado/prioridad de tickets
        'exito'       => '#16a34a', // resuelto
        'alerta'      => '#e9a23b', // en progreso / media
        'peligro'     => '#dc2626', // crítica / vencido
        'neutro'      => '#9ca3af', // cerrado / sin dato
    ];
}

/**
 * Construye la URL de imagen para una gráfica de tipo dona (doughnut).
 * Se usa para "Tickets por estado".
 *
 * @param string[] $labels  Ej. ['Abierto', 'En progreso', 'Resuelto']
 * @param int[]    $datos   Ej. [41, 31, 176]
 * @param string[] $colores Colores hex en el mismo orden que $labels
 */
function urlGraficaDona(array $labels, array $datos, array $colores, string $titulo = ''): string
{
    $config = [
        'type' => 'doughnut',
        'data' => [
            'labels'   => $labels,
            'datasets' => [[
                'data'            => $datos,
                'backgroundColor' => $colores,
                'borderWidth'     => 0,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => ['position' => 'right', 'labels' => ['font' => ['size' => 12]]],
                'title'  => $titulo ? ['display' => true, 'text' => $titulo] : ['display' => false],
            ],
        ],
    ];

    return construirUrlQuickChart($config, 500, 260);
}

/**
 * Gráfica de barras verticales simple (una sola serie).
 * Se usa para "Tickets por prioridad".
 */
function urlGraficaBarras(array $labels, array $datos, string $colorBarras, string $titulo = ''): string
{
    $config = [
        'type' => 'bar',
        'data' => [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => $titulo,
                'data'            => $datos,
                'backgroundColor' => $colorBarras,
                'borderRadius'    => 4,
            ]],
        ],
        'options' => [
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => ['y' => ['beginAtZero' => true]],
        ],
    ];

    return construirUrlQuickChart($config, 500, 260);
}

/**
 * Gráfica de barras horizontales (útil cuando las etiquetas son largas,
 * como nombres de agentes).
 */
function urlGraficaBarrasHorizontales(array $labels, array $datos, string $colorBarras, string $titulo = ''): string
{
    $config = [
        'type' => 'bar',
        'data' => [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => $titulo,
                'data'            => $datos,
                'backgroundColor' => $colorBarras,
                'borderRadius'    => 4,
            ]],
        ],
        'options' => [
            'indexAxis' => 'y', // convierte las barras verticales en horizontales
            'plugins'   => ['legend' => ['display' => false]],
            'scales'    => ['x' => ['beginAtZero' => true]],
        ],
    ];

    return construirUrlQuickChart($config, 500, 280);
}

/**
 * Gráfica de línea con dos series (creados vs resueltos).
 * Se usa para "Tendencia de tickets".
 */
function urlGraficaLinea(array $labels, array $serieCreados, array $serieResueltos, array $colores): string
{
    $config = [
        'type' => 'line',
        'data' => [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Creados',
                    'data'            => $serieCreados,
                    'borderColor'     => $colores['royal'],
                    'backgroundColor' => $colores['royal'],
                    'fill'            => false,
                    'tension'         => 0.3,
                ],
                [
                    'label'           => 'Resueltos',
                    'data'            => $serieResueltos,
                    'borderColor'     => $colores['periwinkle'],
                    'backgroundColor' => $colores['periwinkle'],
                    'fill'            => false,
                    'tension'         => 0.3,
                ],
            ],
        ],
        'options' => [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales'  => ['y' => ['beginAtZero' => true]],
        ],
    ];

    return construirUrlQuickChart($config, 700, 280);
}

/**
 * Codifica una configuración de Chart.js como URL de imagen de QuickChart.
 * Uso interno — las funciones de arriba son las que debes llamar.
 */
function construirUrlQuickChart(array $config, int $ancho, int $alto): string
{
    $configJson = json_encode($config);

    return 'https://quickchart.io/chart'
        . '?c=' . urlencode($configJson)
        . '&width=' . $ancho
        . '&height=' . $alto
        . '&backgroundColor=white'
        . '&devicePixelRatio=2'; // nitidez al imprimir en PDF
}
