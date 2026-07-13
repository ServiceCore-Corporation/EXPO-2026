<?php
// GET    /asignaciones
// GET    /asignaciones/:id
// POST   /asignaciones
// PUT    /asignaciones/:id
// DELETE /asignaciones/:id
// GET    /asignaciones/ticket/:idTicket
// GET    /asignaciones/agente/:idAgente
// GET    /asignaciones/supervisor/:idSupervisor

requireAuth();
$db = getConexion();

$selectAsig = "
    SELECT at.*,
           t.titulo AS ticket,
           c.nombre AS cliente,
           a.nombre AS agente,
           s.nombre AS supervisor
    FROM asignar_ticket at
    LEFT JOIN ticket t   ON t.id_ticket = at.id_ticket
    LEFT JOIN usuario c  ON c.id_usuario = at.id_cliente
    LEFT JOIN usuario a  ON a.id_usuario = at.id_agente
    LEFT JOIN usuario s  ON s.id_usuario = at.id_supervisor
";

// Filtros
$filtros = ['ticket', 'agente', 'supervisor'];
if ($metodo === 'GET' && in_array($id, $filtros) && $sub !== null) {
    $mapCampo = [
        'ticket'     => 'at.id_ticket',
        'agente'     => 'at.id_agente',
        'supervisor' => 'at.id_supervisor',
    ];
    $campo = $mapCampo[$id];
    $valor = (int)$sub;
    $stmt  = $db->prepare("$selectAsig WHERE $campo = ?");
    $stmt->bind_param("i", $valor);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("$selectAsig ORDER BY at.id_asignar_ticket DESC")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idAsig = (int)$id;
        $stmt   = $db->prepare("$selectAsig WHERE at.id_asignar_ticket = ? LIMIT 1");
        $stmt->bind_param("i", $idAsig);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Asignacion no encontrada"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1, 4]);
        $body        = jsonBody();
        $id_ticket   = (int)($body['id_ticket'] ?? 0);
        $id_cliente  = (int)($body['id_cliente'] ?? 0);
        $id_agente   = (int)($body['id_agente'] ?? 0);
        $id_super    = (int)($body['id_supervisor'] ?? 0);
        if (!$id_ticket || !$id_cliente || !$id_agente || !$id_super) {
            responder(400, ["error" => "id_ticket, id_cliente, id_agente, id_supervisor requeridos"]);
        }
        $stmt = $db->prepare("
            INSERT INTO asignar_ticket (id_ticket, id_cliente, id_agente, id_supervisor)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiii", $id_ticket, $id_cliente, $id_agente, $id_super);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Asignacion creada", "id_asignar_ticket" => $newId]);
        break;

    case 'PUT':
        requireRol([1, 4]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idAsig     = (int)$id;
        $body       = jsonBody();
        $id_ticket  = (int)($body['id_ticket'] ?? 0);
        $id_cliente = (int)($body['id_cliente'] ?? 0);
        $id_agente  = (int)($body['id_agente'] ?? 0);
        $id_super   = (int)($body['id_supervisor'] ?? 0);
        $stmt = $db->prepare("
            UPDATE asignar_ticket SET id_ticket=?, id_cliente=?, id_agente=?, id_supervisor=?
            WHERE id_asignar_ticket=?
        ");
        $stmt->bind_param("iiiii", $id_ticket, $id_cliente, $id_agente, $id_super, $idAsig);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Asignacion actualizada"]);
        break;

    case 'DELETE':
        requireRol([1, 4]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idAsig = (int)$id;
        $stmt   = $db->prepare("DELETE FROM asignar_ticket WHERE id_asignar_ticket = ?");
        $stmt->bind_param("i", $idAsig);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Asignacion eliminada"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
