<?php
// GET /reportes/tickets
// GET /reportes/tickets-estado
// GET /reportes/tickets-prioridad
// GET /reportes/tickets-categoria
// GET /reportes/tickets-agente
// GET /reportes/tickets-empresa
// GET /reportes/historial
// GET /reportes/pagos

requireAuth();
$db     = getConexion();
$accion = $id ?? ''; // partes[1]

if ($metodo !== 'GET') {
    $db->close();
    responder(405, ["error" => "Metodo no permitido"]);
}

switch ($accion) {

    // General de tickets
    case 'tickets':
        $rows = $db->query("
            SELECT t.id_ticket, t.titulo, t.fecha_creacion, t.fecha_cierre,
                   c.nombre AS cliente, a.nombre AS agente,
                   cat.nombre AS categoria, p.nombre AS prioridad, e.nombre AS estado
            FROM ticket t
            LEFT JOIN usuario c   ON c.id_usuario = t.id_usuario_cliente
            LEFT JOIN usuario a   ON a.id_usuario = t.id_usuario_agente
            LEFT JOIN categoria cat ON cat.id_categoria = t.id_categoria
            LEFT JOIN prioridad p   ON p.id_prioridad = t.id_prioridad
            LEFT JOIN estado e      ON e.id_estado = t.id_estado
            ORDER BY t.fecha_creacion DESC
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, $rows);
        break;

    // Tickets agrupados por estado
    case 'tickets-estado':
        $rows = $db->query("
            SELECT e.nombre AS estado, COUNT(t.id_ticket) AS total
            FROM ticket t
            LEFT JOIN estado e ON e.id_estado = t.id_estado
            GROUP BY t.id_estado, e.nombre
            ORDER BY total DESC
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, $rows);
        break;

    // Tickets agrupados por prioridad
    case 'tickets-prioridad':
        $rows = $db->query("
            SELECT p.nombre AS prioridad, COUNT(t.id_ticket) AS total
            FROM ticket t
            LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
            GROUP BY t.id_prioridad, p.nombre
            ORDER BY total DESC
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, $rows);
        break;

    // Tickets agrupados por categoría
    case 'tickets-categoria':
        $rows = $db->query("
            SELECT cat.nombre AS categoria, COUNT(t.id_ticket) AS total
            FROM ticket t
            LEFT JOIN categoria cat ON cat.id_categoria = t.id_categoria
            GROUP BY t.id_categoria, cat.nombre
            ORDER BY total DESC
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, $rows);
        break;

    // Tickets por agente
    case 'tickets-agente':
        $rows = $db->query("
            SELECT u.nombre AS agente, COUNT(t.id_ticket) AS total,
                   SUM(CASE WHEN e.nombre = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados,
                   SUM(CASE WHEN e.nombre = 'En proceso' THEN 1 ELSE 0 END) AS en_proceso
            FROM ticket t
            LEFT JOIN usuario u ON u.id_usuario = t.id_usuario_agente
            LEFT JOIN estado e  ON e.id_estado = t.id_estado
            GROUP BY t.id_usuario_agente, u.nombre
            ORDER BY total DESC
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, $rows);
        break;

    // Tickets por empresa
    case 'tickets-empresa':
        $rows = $db->query("
            SELECT emp.nombre AS empresa, COUNT(t.id_ticket) AS total
            FROM ticket t
            LEFT JOIN usuario u  ON u.id_usuario = t.id_usuario_cliente
            LEFT JOIN empresa emp ON emp.id_empresa = u.id_empresa
            GROUP BY u.id_empresa, emp.nombre
            ORDER BY total DESC
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, $rows);
        break;

    // Reporte de historial
    case 'historial':
        $rows = $db->query("
            SELECT h.*, t.titulo AS ticket, u.nombre AS usuario
            FROM historial h
            LEFT JOIN ticket t  ON t.id_ticket = h.id_ticket
            LEFT JOIN usuario u ON u.id_usuario = h.id_usuario
            ORDER BY h.fecha DESC
            LIMIT 500
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, $rows);
        break;

    // Reporte de pagos
    case 'pagos':
        $rows = $db->query("
            SELECT p.*, e.nombre AS empresa
            FROM pago p
            LEFT JOIN empresa e ON e.id_empresa = p.id_empresa
            ORDER BY p.fecha_pago DESC
        ")->fetch_all(MYSQLI_ASSOC);
        $db->close();
        responder(200, $rows);
        break;

    default:
        $db->close();
        responder(404, ["error" => "Reporte no encontrado"]);
}
