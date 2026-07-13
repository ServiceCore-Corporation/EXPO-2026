<?php
// GET    /mensajes/ticket/:idTicket
// GET    /mensajes/nuevos/:idTicket/:ultimoId
// PATCH  /mensajes/ticket/:idTicket/leidos
// POST   /mensajes
requireAuth();
$db = getConexion();



$tabla = 'mensaje';
$check = $db->query("SHOW TABLES LIKE 'mensaje'");
if ($check && $check->num_rows === 0) {
    $tabla = 'mensajes';
}

$selectMensaje = "
    SELECT m.*, u.nombre AS remitente
    FROM `$tabla` m
    LEFT JOIN usuario u ON u.id_usuario = m.id_usuario
";

// GET /mensajes/ticket/:idTicket
if ($metodo === 'GET' && $id === 'ticket' && $sub !== null) {
    $idTicket = (int)$sub;
    $stmt = $db->prepare("$selectMensaje WHERE m.id_ticket = ? ORDER BY m.fecha_envio ASC");
    $stmt->bind_param("i", $idTicket);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $db->close();
    responder(200, $rows);
}

// GET /mensajes/nuevos/:idTicket/:ultimoId
if ($metodo === 'GET' && $id === 'nuevos' && $sub !== null && $subId !== null) {
    $idTicket = (int)$sub;
    $ultimoId = (int)$subId;
    $stmt = $db->prepare("$selectMensaje WHERE m.id_ticket = ? AND m.id_mensaje > ? ORDER BY m.fecha_envio ASC");
    $stmt->bind_param("ii", $idTicket, $ultimoId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $db->close();
    responder(200, $rows);
}

// PATCH /mensajes/ticket/:idTicket/leidos
if ($metodo === 'PATCH' && $id === 'ticket' && $sub !== null && $subId === 'leidos') {
    $idTicket = (int)$sub;
    $stmt = $db->prepare("UPDATE `$tabla` SET leido = 1 WHERE id_ticket = ? AND leido = 0");
    $stmt->bind_param("i", $idTicket);
    $stmt->execute();
    $stmt->close();
    $db->close();
    responder(200, ["mensaje" => "Mensajes marcados como leídos"]);
}

switch ($metodo) {
    case 'POST':
        $body = jsonBody();
        $id_ticket = (int)($body['id_ticket'] ?? 0);
        $contenido = trim($body['contenido'] ?? '');
        $id_usuario = (int)($_SESSION['usuario_id'] ?? 0);

        if (!$id_ticket || $contenido === '') {
            responder(400, ["error" => "id_ticket y contenido requeridos"]);
        }

        // El chat solo puede usarse una vez que el supervisor asignó un agente al ticket.
        $stmtChk = $db->prepare("SELECT id_usuario_agente FROM ticket WHERE id_ticket = ? LIMIT 1");
        $stmtChk->bind_param("i", $id_ticket);
        $stmtChk->execute();
        $ticketRow = $stmtChk->get_result()->fetch_assoc();
        $stmtChk->close();

        if (!$ticketRow) {
            $db->close();
            responder(404, ["error" => "Ticket no encontrado"]);
        }
        if (empty($ticketRow['id_usuario_agente'])) {
            $db->close();
            responder(409, ["error" => "Agente no asignado"]);
        }

        $fecha_envio = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO `$tabla` (id_ticket, id_usuario, contenido, fecha_envio, leido) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("iiss", $id_ticket, $id_usuario, $contenido, $fecha_envio);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        $stmt = $db->prepare("$selectMensaje WHERE m.id_mensaje = ? LIMIT 1");
        $stmt->bind_param("i", $newId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->close();

        if (!$row) {
            responder(500, ["error" => "No se pudo recuperar el mensaje insertado"]);
        }

        responder(201, $row);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
