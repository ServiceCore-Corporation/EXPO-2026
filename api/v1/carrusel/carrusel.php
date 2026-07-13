<?php
// GET    /carrusel
// GET    /carrusel/:id
// POST   /carrusel
// PUT    /carrusel/:id
// DELETE /carrusel/:id

// No requiere autenticación para GET (página pública)
$db = getConexion();

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM carrusel ORDER BY id_carrusel DESC")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idC  = (int)$id;
        $stmt = $db->prepare("SELECT * FROM carrusel WHERE id_carrusel = ? LIMIT 1");
        $stmt->bind_param("i", $idC);
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
        $stmt = $db->prepare("INSERT INTO carrusel (imagen_url, descripcion, fecha_subida) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $url, $descripcion, $fecha);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Imagen de carrusel creada", "id_carrusel" => $newId]);
        break;

    case 'PUT':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idC        = (int)$id;
        $body       = jsonBody();
        $url        = trim($body['imagen_url'] ?? '');
        $descripcion = trim($body['descripcion'] ?? '');
        $stmt = $db->prepare("UPDATE carrusel SET imagen_url=?, descripcion=? WHERE id_carrusel=?");
        $stmt->bind_param("ssi", $url, $descripcion, $idC);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Carrusel actualizado"]);
        break;

    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idC  = (int)$id;
        $stmt = $db->prepare("DELETE FROM carrusel WHERE id_carrusel = ?");
        $stmt->bind_param("i", $idC);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Imagen eliminada del carrusel"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
