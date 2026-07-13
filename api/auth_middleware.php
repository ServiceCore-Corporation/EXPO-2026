<?php
function requireAuth(): array {
    if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
        http_response_code(401);
        echo json_encode(["error" => "No autenticado"]);
        exit();
    }
    return [
        'id'     => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['nombre'],
        'correo' => $_SESSION['correo'],
        'id_rol' => $_SESSION['id_rol'],
    ];
}

function requireRol(array $rolesPermitidos): array {
    $usuario = requireAuth();
    if (!in_array((int)$usuario['id_rol'], $rolesPermitidos)) {
        http_response_code(403);
        echo json_encode(["error" => "Sin permisos"]);
        exit();
    }
    return $usuario;
}

// Roles (tabla `rol`): 1=Admin ServiceCore, 2=Admin Empresa, 3=Agente, 4=Supervisor, 5=Cliente
