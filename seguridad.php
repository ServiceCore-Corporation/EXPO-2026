<?php
// Seguridad global - incluir en cada página protegida

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloquear caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar sesión activa
if (
    !isset($_SESSION['usuario_id']) ||
    !isset($_SESSION['autenticado']) ||
    $_SESSION['autenticado'] !== true
) {
    session_destroy();
    header("Location: /login.php?error=Sesion+expirada");
    exit();
}

// Verificar que el rol coincida con el permitido en la página
if (defined('ROL_REQUERIDO') && (int)$_SESSION['id_rol'] !== (int)ROL_REQUERIDO) {
    header("Location: /login.php?error=Acceso+denegado");
    exit();
}

// Regenerar ID de sesión cada 30 min para prevenir fijación
if (!isset($_SESSION['ultimo_regenerado'])) {
    $_SESSION['ultimo_regenerado'] = time();
} elseif (time() - $_SESSION['ultimo_regenerado'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['ultimo_regenerado'] = time();
}
