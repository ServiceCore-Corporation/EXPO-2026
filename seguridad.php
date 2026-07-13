<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (
    !isset($_SESSION['usuario_id']) ||
    !isset($_SESSION['autenticado']) ||
    $_SESSION['autenticado'] !== true
) {
    session_destroy();
    header("Location: /login.php?error=Sesion+expirada");
    exit();
}

if (defined('ROL_REQUERIDO') && (int)$_SESSION['id_rol'] !== (int)ROL_REQUERIDO) {
    header("Location: /login.php?error=Acceso+denegado");
    exit();
}

if (defined('ROLES_PERMITIDOS') && !in_array((int)$_SESSION['id_rol'], ROLES_PERMITIDOS, true)) {
    header("Location: /login.php?error=Acceso+denegado");
    exit();
}

if (!isset($_SESSION['ultimo_regenerado'])) {
    $_SESSION['ultimo_regenerado'] = time();
} elseif (time() - $_SESSION['ultimo_regenerado'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['ultimo_regenerado'] = time();
}
