<?php
/**
 * vistas/Admin/php/generar_pdf.php
 * -------------------------------------------------------------------------
 * Genera y descarga el reporte de mesa de servicio en PDF, respetando
 * los mismos filtros que el usuario tiene aplicados en el dashboard.
 * Usa las mismas funciones de datos que api_datos_reporte.php, así que
 * el PDF y la pantalla nunca se desincronizan.
 *
 * Requiere que dompdf esté instalado en el proyecto:
 *   composer require dompdf/dompdf
 * -------------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../api/auth_middleware.php';
requireRol([1]); // Solo Admin ServiceCore

$autoloadDompdf = __DIR__ . '/../../../vendor/autoload.php';
if (file_exists($autoloadDompdf)) {
    require_once $autoloadDompdf;
}
if (!class_exists('Dompdf\\Dompdf')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Falta instalar dompdf en el servidor.\n";
    echo "Ejecuta en la raiz del proyecto: composer require dompdf/dompdf\n";
    exit();
}
require_once __DIR__ . '/lib/consultas_reportes.php';
require_once __DIR__ . '/lib/graficas_quickchart.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ---- Filtros y datos (idéntico criterio que el dashboard en vivo) ----
$filtros  = normalizarFiltros($_GET);
$metricas = obtenerMetricas($filtros);
$graficas = obtenerDatosGraficas($filtros);
$tickets  = obtenerListadoTickets($filtros, 50);

// ---- Construir las imágenes de las 4 gráficas ----
$paleta = paletaServiceCore();

$urlGraficaEstado = urlGraficaDona(
    array_keys($graficas['por_estado']),
    array_values($graficas['por_estado']),
    [$paleta['peligro'], $paleta['alerta'], $paleta['exito'], $paleta['neutro']],
    'Tickets por estado'
);

$urlGraficaPrioridad = urlGraficaBarras(
    array_keys($graficas['por_prioridad']),
    array_values($graficas['por_prioridad']),
    $paleta['royal'],
    'Tickets por prioridad'
);

$urlGraficaTendencia = urlGraficaLinea(
    $graficas['tendencia']['dias'],
    $graficas['tendencia']['creados'],
    $graficas['tendencia']['resueltos'],
    $paleta
);

$urlGraficaAgentes = urlGraficaBarrasHorizontales(
    array_keys($graficas['por_agente']),
    array_values($graficas['por_agente']),
    $paleta['periwinkle'],
    'Tickets cerrados por agente'
);

$fechaGeneracion = date('d/m/Y H:i');

// ---- Renderizar la plantilla PHP a un string de HTML ----
ob_start();
require __DIR__ . '/plantilla_pdf_reporte.php';
$html = ob_get_clean();

// ---- Configurar y ejecutar DomPDF ----
$opciones = new Options();
$opciones->set('isRemoteEnabled', true);
$opciones->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($opciones);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$canvas->page_text(500, 770, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 8, [0.6, 0.6, 0.6]);

$nombreArchivo = 'reporte-tickets-' . date('Y-m-d') . '.pdf';
$dompdf->stream($nombreArchivo, ['Attachment' => true]);
