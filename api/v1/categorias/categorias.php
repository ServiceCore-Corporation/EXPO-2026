<?php
// GET    /categorias
// GET    /categorias/:id
// POST   /categorias
// PUT    /categorias/:id
// DELETE /categorias/:id

requireAuth();
$db = getConexion();

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM categoria ORDER BY id_categoria")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idCat = (int)$id;
        $stmt  = $db->prepare("SELECT * FROM categoria WHERE id_categoria = ? LIMIT 1");
        $stmt->bind_param("i", $idCat);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Categoria no encontrada"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1, 2]);
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("INSERT INTO categoria (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Categoria creada", "id_categoria" => $newId]);
        break;

    case 'PUT':
        requireRol([1, 2]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idCat  = (int)$id;
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("UPDATE categoria SET nombre = ? WHERE id_categoria = ?");
        $stmt->bind_param("si", $nombre, $idCat);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Categoria actualizada"]);
        break;

    case 'DELETE':
        requireRol([1, 2]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idCat = (int)$id;
        $stmt  = $db->prepare("DELETE FROM categoria WHERE id_categoria = ?");
        $stmt->bind_param("i", $idCat);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Categoria eliminada"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
