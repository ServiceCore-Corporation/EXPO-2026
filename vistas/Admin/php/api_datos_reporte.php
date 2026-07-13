<?php
/**
 * vistas/Admin/php/api_datos_reporte.php
 * -------------------------------------------------------------------------
 * Endpoint AJAX que reportes.php consulta cada vez que el usuario cambia
 * un filtro. Devuelve JSON con métricas, datasets para Chart.js y el
 * listado de tickets. Usa las mismas funciones que generar_pdf.php para
 * que pantalla y PDF nunca se desincronicen.
 * -------------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../api/auth_middleware.php';
requireRol([1]); // Solo Admin ServiceCore

require_once __DIR__ . '/lib/consultas_reportes.php';

try {
    $filtros  = normalizarFiltros($_GET);
    $metricas = obtenerMetricas($filtros);
    $graficas = obtenerDatosGraficas($filtros);
    $tickets  = obtenerListadoTickets($filtros, 10);

    echo json_encode([
        'filtros_aplicados' => $filtros,
        'metricas'          => $metricas,
        'graficas'          => $graficas,
        'tickets'           => $tickets,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo generar el reporte', 'detalle' => $e->getMessage()]);
}
