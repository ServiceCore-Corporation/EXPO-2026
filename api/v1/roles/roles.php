<?php
// GET    /roles
// GET    /roles/:id
// POST   /roles
// PUT    /roles/:id
// DELETE /roles/:id

requireAuth();
$db = getConexion();

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM rol ORDER BY id_rol")->fetch_all(MYSQLI_ASSOC);
            $db->close();
            responder(200, $rows);
        }
        $idRol = (int)$id;
        $stmt  = $db->prepare("SELECT * FROM rol WHERE id_rol = ? LIMIT 1");
        $stmt->bind_param("i", $idRol);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Rol no encontrado"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1]);
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("INSERT INTO rol (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Rol creado", "id_rol" => $newId]);
        break;

    case 'PUT':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idRol  = (int)$id;
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("UPDATE rol SET nombre = ? WHERE id_rol = ?");
        $stmt->bind_param("si", $nombre, $idRol);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Rol actualizado"]);
        break;

    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idRol = (int)$id;
        $stmt  = $db->prepare("DELETE FROM rol WHERE id_rol = ?");
        $stmt->bind_param("i", $idRol);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Rol eliminado"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
