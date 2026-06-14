<?php
// GET    /galeria
// GET    /galeria/:id
// POST   /galeria
// PUT    /galeria/:id
// DELETE /galeria/:id

$db = getConexion();

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM galeria ORDER BY id_galeria DESC")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idG  = (int)$id;
        $stmt = $db->prepare("SELECT * FROM galeria WHERE id_galeria = ? LIMIT 1");
        $stmt->bind_param("i", $idG);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Imagen no encontrada"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1]);
        $body       = jsonBody();
        $url        = trim($body['imagen_url'] ?? '');
        $descripcion = trim($body['descripcion'] ?? '');
        $fecha      = date('Y-m-d H:i:s');
        if (empty($url)) responder(400, ["error" => "imagen_url requerida"]);
        $stmt = $db->prepare("INSERT INTO galeria (imagen_url, descripcion, fecha_subida) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $url, $descripcion, $fecha);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Imagen de galeria creada", "id_galeria" => $newId]);
        break;

    case 'PUT':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idG        = (int)$id;
        $body       = jsonBody();
        $url        = trim($body['imagen_url'] ?? '');
        $descripcion = trim($body['descripcion'] ?? '');
        $stmt = $db->prepare("UPDATE galeria SET imagen_url=?, descripcion=? WHERE id_galeria=?");
        $stmt->bind_param("ssi", $url, $descripcion, $idG);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Galeria actualizada"]);
        break;

    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idG  = (int)$id;
        $stmt = $db->prepare("DELETE FROM galeria WHERE id_galeria = ?");
        $stmt->bind_param("i", $idG);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Imagen eliminada de galeria"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
