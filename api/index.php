//Se agrego carpeta de API
<?php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_middleware.php';

// Parsear URI
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base   = '/api';
$uri    = str_replace($base, '', $uri);
$uri    = trim($uri, '/');
$partes = explode('/', $uri);
$metodo = $_SERVER['REQUEST_METHOD'];

// Helpers
function jsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function responder(int $code, mixed $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Router principal
$recurso = $partes[0] ?? '';
$id      = $partes[1] ?? null;
$sub     = $partes[2] ?? null;
$subId   = $partes[3] ?? null;

switch ($recurso) {

    case 'auth':
        require_once __DIR__ . '/v1/auth/auth.php';
        break;

    case 'usuarios':
        require_once __DIR__ . '/v1/usuarios/usuarios.php';
        break;

    case 'roles':
        require_once __DIR__ . '/v1/roles/roles.php';
        break;

    case 'empresas':
        require_once __DIR__ . '/v1/empresas/empresas.php';
        break;

    case 'planes':
        require_once __DIR__ . '/v1/planes/planes.php';
        break;

    case 'cuentas':
        require_once __DIR__ . '/v1/cuentas/cuentas.php';
        break;

    case 'pagos':
        require_once __DIR__ . '/v1/pagos/pagos.php';
        break;

    case 'categorias':
        require_once __DIR__ . '/v1/categorias/categorias.php';
        break;

    case 'prioridades':
        require_once __DIR__ . '/v1/prioridades/prioridades.php';
        break;

    case 'estados':
        require_once __DIR__ . '/v1/estados/estados.php';
        break;

    case 'tickets':
        require_once __DIR__ . '/v1/tickets/tickets.php';
        break;

    case 'asignaciones':
        require_once __DIR__ . '/v1/asignaciones/asignaciones.php';
        break;

    case 'historial':
        require_once __DIR__ . '/v1/historial/historial.php';
        break;

    case 'archivos':
        require_once __DIR__ . '/v1/archivos/archivos.php';
        break;

    case 'carrusel':
        require_once __DIR__ . '/v1/carrusel/carrusel.php';
        break;

    case 'galeria':
        require_once __DIR__ . '/v1/galeria/galeria.php';
        break;

    case 'reportes':
        require_once __DIR__ . '/v1/reportes/reportes.php';
        break;

    case 'dashboard':
        require_once __DIR__ . '/v1/dashboard/dashboard.php';
        break;

    default:
        responder(404, ["error" => "Endpoint no encontrado"]);
}
