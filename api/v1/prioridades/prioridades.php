<?php
requireAuth();
$db = getConexion();

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM prioridad ORDER BY id_prioridad")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idP  = (int)$id;
        $stmt = $db->prepare("SELECT * FROM prioridad WHERE id_prioridad = ? LIMIT 1");
        $stmt->bind_param("i", $idP);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Prioridad no encontrada"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1, 2]);
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("INSERT INTO prioridad (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Prioridad creada", "id_prioridad" => $newId]);
        break;

    case 'PUT':
        requireRol([1, 2]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idP    = (int)$id;
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("UPDATE prioridad SET nombre = ? WHERE id_prioridad = ?");
        $stmt->bind_param("si", $nombre, $idP);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Prioridad actualizada"]);
        break;

    case 'DELETE':
        requireRol([1, 2]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idP  = (int)$id;
        $stmt = $db->prepare("DELETE FROM prioridad WHERE id_prioridad = ?");
        $stmt->bind_param("i", $idP);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Prioridad eliminada"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
