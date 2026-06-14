<?php
// Cierre de sesión seguro

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar variables de sesión
$_SESSION = [];

// Eliminar cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $parametros = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );
}

// Destruir sesión en el servidor
session_destroy();

// Bloquear caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

header("Location: /login.php");
exit();
