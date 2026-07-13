<?php
// GET    /historial
// GET    /historial/:id
// POST   /historial
// DELETE /historial/:id
// GET    /historial/ticket/:idTicket
// GET    /historial/usuario/:idUsuario

requireAuth();
$db = getConexion();

$selectHist = "
    SELECT h.*, t.titulo AS ticket, u.nombre AS usuario
    FROM historial h
    LEFT JOIN ticket t  ON t.id_ticket = h.id_ticket
    LEFT JOIN usuario u ON u.id_usuario = h.id_usuario
";

// Filtros
if ($metodo === 'GET' && $id === 'ticket' && $sub !== null) {
    $idTicket = (int)$sub;
    $stmt = $db->prepare("$selectHist WHERE h.id_ticket = ? ORDER BY h.fecha DESC");
    $stmt->bind_param("i", $idTicket);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

if ($metodo === 'GET' && $id === 'usuario' && $sub !== null) {
    $idUsuario = (int)$sub;
    $stmt = $db->prepare("$selectHist WHERE h.id_usuario = ? ORDER BY h.fecha DESC");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("$selectHist ORDER BY h.fecha DESC")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idHist = (int)$id;
        $stmt   = $db->prepare("$selectHist WHERE h.id_historial = ? LIMIT 1");
        $stmt->bind_param("i", $idHist);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Historial no encontrado"]);
        responder(200, $row);
        break;

    case 'POST':
        $body            = jsonBody();
        $id_ticket       = (int)($body['id_ticket'] ?? 0);
        $id_usuario      = (int)($body['id_usuario'] ?? $_SESSION['usuario_id']);
        $accion          = trim($body['accion'] ?? '');
        $campo           = trim($body['campo_modificado'] ?? '');
        $valor_anterior  = trim($body['valor_anterior'] ?? '');
        $valor_nuevo     = trim($body['valor_nuevo'] ?? '');
        $fecha           = date('Y-m-d H:i:s');
        if (!$id_ticket || empty($accion)) {
            responder(400, ["error" => "id_ticket y accion requeridos"]);
        }
        $stmt = $db->prepare("
            INSERT INTO historial (id_ticket, id_usuario, accion, campo_modificado, valor_anterior, valor_nuevo, fecha)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisssss", $id_ticket, $id_usuario, $accion, $campo, $valor_anterior, $valor_nuevo, $fecha);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Historial registrado", "id_historial" => $newId]);
        break;

    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idHist = (int)$id;
        $stmt   = $db->prepare("DELETE FROM historial WHERE id_historial = ?");
        $stmt->bind_param("i", $idHist);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Historial eliminado"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
