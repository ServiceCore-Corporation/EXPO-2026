<?php
// GET    /planes
// GET    /planes/:id
// POST   /planes
// PUT    /planes/:id
// DELETE /planes/:id
// PATCH  /planes/:id/estado

requireAuth();
$db = getConexion();

// PATCH /planes/:id/estado
if ($metodo === 'PATCH' && $id !== null && $sub === 'estado') {
    requireRol([1]);
    $idPlan = (int)$id;
    $body   = jsonBody();
    $activo = isset($body['activo']) ? (int)$body['activo'] : null;
    if ($activo === null) responder(400, ["error" => "Campo 'activo' requerido"]);
    $stmt = $db->prepare("UPDATE plan SET activo = ? WHERE id_plan = ?");
    $stmt->bind_param("ii", $activo, $idPlan);
    $stmt->execute();
    $stmt->close(); $db->close();
    responder(200, ["mensaje" => "Estado del plan actualizado"]);
}

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM plan ORDER BY id_plan")->fetch_all(MYSQLI_ASSOC);
            $db->close();
            responder(200, $rows);
        }
        $idPlan = (int)$id;
        $stmt   = $db->prepare("SELECT * FROM plan WHERE id_plan = ? LIMIT 1");
        $stmt->bind_param("i", $idPlan);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Plan no encontrado"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1]);
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        $precio = (float)($body['precio'] ?? 0);
        $limU   = (int)($body['limite_usuarios'] ?? 0);
        $limT   = (int)($body['limite_tickets'] ?? 0);
        $activo = (int)($body['activo'] ?? 1);
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("INSERT INTO plan (nombre, precio, limite_usuarios, limite_tickets, activo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdiii", $nombre, $precio, $limU, $limT, $activo);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Plan creado", "id_plan" => $newId]);
        break;

    case 'PUT':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idPlan = (int)$id;
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        $precio = (float)($body['precio'] ?? 0);
        $limU   = (int)($body['limite_usuarios'] ?? 0);
        $limT   = (int)($body['limite_tickets'] ?? 0);
        $activo = (int)($body['activo'] ?? 1);
        if (empty($nombre)) responder(400, ["error" => "Nombre requerido"]);
        $stmt = $db->prepare("UPDATE plan SET nombre=?, precio=?, limite_usuarios=?, limite_tickets=?, activo=? WHERE id_plan=?");
        $stmt->bind_param("sdiiii", $nombre, $precio, $limU, $limT, $activo, $idPlan);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Plan actualizado"]);
        break;

    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idPlan = (int)$id;
        $stmt   = $db->prepare("DELETE FROM plan WHERE id_plan = ?");
        $stmt->bind_param("i", $idPlan);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Plan eliminado"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
