<?php
// GET /dashboard
// GET /dashboard/tickets
// GET /dashboard/usuarios
// GET /dashboard/pagos
// GET /dashboard/empresas

requireRol([1, 4]);
$db     = getConexion();
$accion = $id ?? '';

if ($metodo !== 'GET') {
    $db->close();
    responder(405, ["error" => "Metodo no permitido"]);
}

switch ($accion) {

    // Resumen general
    case '':
        $totalTickets  = $db->query("SELECT COUNT(*) AS total FROM ticket")->fetch_assoc()['total'];
        $totalUsuarios = $db->query("SELECT COUNT(*) AS total FROM usuario")->fetch_assoc()['total'];
        $totalEmpresas = $db->query("SELECT COUNT(*) AS total FROM empresa")->fetch_assoc()['total'];
        $totalPagos    = $db->query("SELECT COALESCE(SUM(monto),0) AS total FROM pago")->fetch_assoc()['total'];

        $ticketsPorEstado = $db->query("
            SELECT e.nombre AS estado, COUNT(t.id_ticket) AS total
            FROM ticket t LEFT JOIN estado e ON e.id_estado = t.id_estado
            GROUP BY t.id_estado, e.nombre
        ")->fetch_all(MYSQLI_ASSOC);

        $db->close();
        responder(200, [
            "resumen" => [
                "tickets"  => (int)$totalTickets,
                "usuarios" => (int)$totalUsuarios,
                "empresas" => (int)$totalEmpresas,
                "ingresos" => (float)$totalPagos,
            ],
            "tickets_por_estado" => $ticketsPorEstado,
        ]);
        break;

    // Detalle de tickets para dashboard
    case 'tickets':
        $abiertos  = $db->query("SELECT COUNT(*) AS c FROM ticket t JOIN estado e ON e.id_estado=t.id_estado WHERE e.nombre='Pendiente'")->fetch_assoc()['c'];
        $proceso   = $db->query("SELECT COUNT(*) AS c FROM ticket t JOIN estado e ON e.id_estado=t.id_estado WHERE e.nombre='En proceso'")->fetch_assoc()['c'];
        $cerrados  = $db->query("SELECT COUNT(*) AS c FROM ticket t JOIN estado e ON e.id_estado=t.id_estado WHERE e.nombre='Cerrado'")->fetch_assoc()['c'];
        $recientes = $db->query("
            SELECT t.id_ticket, t.titulo, e.nombre AS estado, p.nombre AS prioridad,
                   u.nombre AS cliente, t.fecha_creacion
            FROM ticket t
            LEFT JOIN estado e    ON e.id_estado = t.id_estado
            LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
            LEFT JOIN usuario u   ON u.id_usuario = t.id_usuario_cliente
            ORDER BY t.fecha_creacion DESC LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, [
            "pendientes" => (int)$abiertos,
            "en_proceso" => (int)$proceso,
            "cerrados"   => (int)$cerrados,
            "recientes"  => $recientes,
        ]);
        break;

    // Detalle de usuarios para dashboard
    case 'usuarios':
        $total   = $db->query("SELECT COUNT(*) AS c FROM usuario")->fetch_assoc()['c'];
        $activos = $db->query("SELECT COUNT(*) AS c FROM usuario WHERE activo=1")->fetch_assoc()['c'];
        $porRol  = $db->query("
            SELECT r.nombre AS rol, COUNT(u.id_usuario) AS total
            FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
            GROUP BY u.id_rol, r.nombre
        ")->fetch_all(MYSQLI_ASSOC);
        $recientes = $db->query("
            SELECT u.id_usuario, u.nombre, u.correo, r.nombre AS rol,
                   u.activo, u.fecha_creacion
            FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
            ORDER BY u.fecha_creacion DESC LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, [
            "total"     => (int)$total,
            "activos"   => (int)$activos,
            "por_rol"   => $porRol,
            "recientes" => $recientes,
        ]);
        break;

    // Detalle de pagos para dashboard
    case 'pagos':
        $totalMonto   = $db->query("SELECT COALESCE(SUM(monto),0) AS t FROM pago")->fetch_assoc()['t'];
        $totalRegistros = $db->query("SELECT COUNT(*) AS c FROM pago")->fetch_assoc()['c'];
        $recientes    = $db->query("
            SELECT p.*, e.nombre AS empresa
            FROM pago p LEFT JOIN empresa e ON e.id_empresa = p.id_empresa
            ORDER BY p.fecha_pago DESC LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, [
            "total_ingresos"  => (float)$totalMonto,
            "total_pagos"     => (int)$totalRegistros,
            "recientes"       => $recientes,
        ]);
        break;

    // Detalle de empresas para dashboard
    case 'empresas':
        $total    = $db->query("SELECT COUNT(*) AS c FROM empresa")->fetch_assoc()['c'];
        $activas  = $db->query("SELECT COUNT(*) AS c FROM empresa WHERE estado=1")->fetch_assoc()['c'];
        $recientes = $db->query("
            SELECT e.*, COUNT(u.id_usuario) AS usuarios
            FROM empresa e
            LEFT JOIN usuario u ON u.id_empresa = e.id_empresa
            GROUP BY e.id_empresa
            ORDER BY e.fecha_registro DESC LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, [
            "total"     => (int)$total,
            "activas"   => (int)$activas,
            "recientes" => $recientes,
        ]);
        break;

    default:
        $db->close();
        responder(404, ["error" => "Seccion de dashboard no encontrada"]);
}
