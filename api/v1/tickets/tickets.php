<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
requireAuth();
$db = getConexion();

$selectTicket = "
    SELECT t.*,
           c.nombre AS cliente,
           a.nombre AS agente,
           cat.nombre AS categoria,
           p.nombre AS prioridad,
           e.nombre AS estado
    FROM ticket t
    LEFT JOIN usuario c   ON c.id_usuario = t.id_usuario_cliente
    LEFT JOIN usuario a   ON a.id_usuario = t.id_usuario_agente
    LEFT JOIN categoria cat ON cat.id_categoria = t.id_categoria
    LEFT JOIN prioridad p   ON p.id_prioridad = t.id_prioridad
    LEFT JOIN estado e      ON e.id_estado = t.id_estado
";

// Sub-rutas por filtro
$filtros = ['cliente', 'agente', 'categoria', 'prioridad', 'estado'];
if ($metodo === 'GET' && in_array($id, $filtros) && $sub !== null) {
    $mapCampo = [
        'cliente'   => 't.id_usuario_cliente',
        'agente'    => 't.id_usuario_agente',
        'categoria' => 't.id_categoria',
        'prioridad' => 't.id_prioridad',
        'estado'    => 't.id_estado',
    ];
    $campo  = $mapCampo[$id];
    $valor  = (int)$sub;
    $stmt   = $db->prepare("$selectTicket WHERE $campo = ? ORDER BY t.fecha_creacion DESC");
    $stmt->bind_param("i", $valor);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

// PATCH /tickets/:id/estado
if ($metodo === 'PATCH' && $id !== null && $sub === 'estado') {
    $idTicket  = (int)$id;
    $body      = jsonBody();
    $id_estado = (int)($body['id_estado'] ?? 0);
    if (!$id_estado) responder(400, ["error" => "id_estado requerido"]);
    $stmt = $db->prepare("UPDATE ticket SET id_estado = ? WHERE id_ticket = ?");
    $stmt->bind_param("ii", $id_estado, $idTicket);
    $stmt->execute();
    $stmt->close(); $db->close();
    responder(200, ["mensaje" => "Estado del ticket actualizado"]);
}

// PATCH /tickets/:id/prioridad
if ($metodo === 'PATCH' && $id !== null && $sub === 'prioridad') {
    $idTicket    = (int)$id;
    $body        = jsonBody();
    $id_prioridad = (int)($body['id_prioridad'] ?? 0);
    if (!$id_prioridad) responder(400, ["error" => "id_prioridad requerido"]);
    $stmt = $db->prepare("UPDATE ticket SET id_prioridad = ? WHERE id_ticket = ?");
    $stmt->bind_param("ii", $id_prioridad, $idTicket);
    $stmt->execute();
    $stmt->close(); $db->close();
    responder(200, ["mensaje" => "Prioridad del ticket actualizada"]);
}

// PATCH /tickets/:id/asignar
if ($metodo === 'PATCH' && $id !== null && $sub === 'asignar') {
    requireRol([1, 4]); // Admin ServiceCore y Supervisor son quienes asignan agentes
    $idTicket   = (int)$id;
    $body       = jsonBody();
    $id_agente  = (int)($body['id_agente'] ?? 0);
    if (!$id_agente) responder(400, ["error" => "id_agente requerido"]);
    $stmt = $db->prepare("UPDATE ticket SET id_usuario_agente = ?, id_estado = IF(id_estado = 1, 2, id_estado) WHERE id_ticket = ?");
    $stmt->bind_param("ii", $id_agente, $idTicket);
    $stmt->execute();
    $stmt->close(); $db->close();
    responder(200, ["mensaje" => "Ticket asignado al agente"]);
}

// PATCH /tickets/:id/cerrar
if ($metodo === 'PATCH' && $id !== null && $sub === 'cerrar') {
    $idTicket    = (int)$id;
    $fechaCierre = date('Y-m-d H:i:s');
    // id_estado 4 = Cerrado (ver tabla `estado`)
    $stmt = $db->prepare("UPDATE ticket SET id_estado = 4, fecha_cierre = ? WHERE id_ticket = ?");
    $stmt->bind_param("si", $fechaCierre, $idTicket);
    $stmt->execute();
    $stmt->close(); $db->close();
    responder(200, ["mensaje" => "Ticket cerrado"]);
}

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("$selectTicket ORDER BY t.fecha_creacion DESC")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idTicket = (int)$id;
        $stmt = $db->prepare("$selectTicket WHERE t.id_ticket = ? LIMIT 1");
        $stmt->bind_param("i", $idTicket);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Ticket no encontrado"]);
        responder(200, $row);
        break;

    case 'POST':
    
        $body = jsonBody();
    
        $titulo       = trim($body['titulo'] ?? '');
        $descripcion  = trim($body['descripcion'] ?? '');
        $id_cliente   = (int)($body['id_usuario_cliente'] ?? 0);
        $tieneAgente  = !empty($body['id_usuario_agente']);
        $id_agente    = $tieneAgente ? (int)$body['id_usuario_agente'] : 0;
        $id_cat       = (int)($body['id_categoria'] ?? 0);
        $id_prio      = (int)($body['id_prioridad'] ?? 0);
        $id_estado    = (int)($body['id_estado'] ?? 1);
        $fecha        = date('Y-m-d H:i:s');
        $fecha_cierre = null;
    
        if ($titulo === '') responder(400, ["error" => "titulo requerido"]);
        if ($descripcion === '') responder(400, ["error" => "descripcion requerida"]);
        if ($id_cliente <= 0) responder(400, ["error" => "id_usuario_cliente requerido"]);
        if ($id_cat <= 0) responder(400, ["error" => "id_categoria requerida"]);
        if ($id_prio <= 0) responder(400, ["error" => "id_prioridad requerida"]);
    
        if ($tieneAgente) {
            $sql = "
                INSERT INTO ticket (
                    titulo, descripcion, id_usuario_cliente, id_usuario_agente,
                    id_categoria, id_prioridad, id_estado, fecha_creacion, fecha_cierre
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $db->prepare($sql);
            if (!$stmt) responder(500, ["error" => "Error preparando INSERT", "mysql" => $db->error]);
            $stmt->bind_param(
                "ssiiiiiss",
                $titulo, $descripcion, $id_cliente, $id_agente,
                $id_cat, $id_prio, $id_estado, $fecha, $fecha_cierre
            );
        } else {
            $sql = "
                INSERT INTO ticket (
                    titulo, descripcion, id_usuario_cliente, id_usuario_agente,
                    id_categoria, id_prioridad, id_estado, fecha_creacion, fecha_cierre
                )
                VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?)
            ";
            $stmt = $db->prepare($sql);
            if (!$stmt) responder(500, ["error" => "Error preparando INSERT", "mysql" => $db->error]);
            $stmt->bind_param(
                "ssiiiiss",
                $titulo, $descripcion, $id_cliente,
                $id_cat, $id_prio, $id_estado, $fecha, $fecha_cierre
            );
        }
    
        if (!$stmt->execute()) {
            responder(500, [
                "error" => "Error ejecutando INSERT",
                "errno" => $stmt->errno,
                "mysql" => $stmt->error
            ]);
        }
    
        $nuevoId = $stmt->insert_id;
    
        $stmt->close();
        $db->close();
    
        responder(201, [
            "mensaje" => "Ticket creado",
            "id_ticket" => $nuevoId
        ]);
    
        break;

    case 'PUT':
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idTicket    = (int)$id;
        $body        = jsonBody();
        $titulo      = trim($body['titulo'] ?? '');
        $descripcion = trim($body['descripcion'] ?? '');
        $id_cat      = (int)($body['id_categoria'] ?? 0);
        $id_prio     = (int)($body['id_prioridad'] ?? 0);
        $id_estado   = (int)($body['id_estado'] ?? 0);
        if (empty($titulo) || !$id_cat || !$id_prio || !$id_estado) {
            responder(400, ["error" => "titulo, id_categoria, id_prioridad, id_estado requeridos"]);
        }
        $stmt = $db->prepare("
            UPDATE ticket SET titulo=?, descripcion=?, id_categoria=?, id_prioridad=?, id_estado=?
            WHERE id_ticket=?
        ");
        $stmt->bind_param(
            "ssiiiii",
            $titulo,
            $descripcion,
            $id_cat,
            $id_prio,
            $id_estado,
            $idTicket
        );
                $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Ticket actualizado"]);
        break;

    case 'DELETE':
        requireRol([1, 2, 4]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idTicket = (int)$id;
        $stmt     = $db->prepare("DELETE FROM ticket WHERE id_ticket = ?");
        $stmt->bind_param("i", $idTicket);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Ticket eliminado"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}