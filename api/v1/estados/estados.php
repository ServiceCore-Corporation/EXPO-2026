<?php
requireAuth();
$db = getConexion();

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM estado ORDER BY id_estado")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idE  = (int)$id;
        $stmt = $db->prepare("SELECT * FROM estado WHERE id_estado = ? LIMIT 1");
        $stmt->bind_param("i", $idE);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Estado no encontrado"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1, 2]);
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("INSERT INTO estado (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Estado creado", "id_estado" => $newId]);
        break;

    case 'PUT':
        requireRol([1, 2]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idE    = (int)$id;
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("UPDATE estado SET nombre = ? WHERE id_estado = ?");
        $stmt->bind_param("si", $nombre, $idE);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Estado actualizado"]);
        break;

    case 'DELETE':
        requireRol([1, 2]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idE  = (int)$id;
        $stmt = $db->prepare("DELETE FROM estado WHERE id_estado = ?");
        $stmt->bind_param("i", $idE);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Estado eliminado"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
