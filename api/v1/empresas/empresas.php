<?php
// GET    /empresas
// GET    /empresas/:id
// POST   /empresas
// PUT    /empresas/:id
// DELETE /empresas/:id
// GET    /empresas/:id/usuarios
// GET    /empresas/:id/pagos

requireAuth();
$db = getConexion();

// GET /empresas/:id/usuarios
if ($metodo === 'GET' && $id !== null && $sub === 'usuarios') {
    $idEmpresa = (int)$id;
    $stmt = $db->prepare("
        SELECT u.id_usuario, u.nombre, u.correo, u.activo, u.fecha_creacion,
               r.nombre AS rol, u.id_rol
        FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
        WHERE u.id_empresa = ?
    ");
    $stmt->bind_param("i", $idEmpresa);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

// GET /empresas/:id/pagos
if ($metodo === 'GET' && $id !== null && $sub === 'pagos') {
    $idEmpresa = (int)$id;
    $stmt = $db->prepare("SELECT * FROM pago WHERE id_empresa = ? ORDER BY fecha_pago DESC");
    $stmt->bind_param("i", $idEmpresa);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM empresa ORDER BY id_empresa DESC")->fetch_all(MYSQLI_ASSOC);
            $db->close();
            responder(200, $rows);
        }
        $idEmpresa = (int)$id;
        $stmt = $db->prepare("SELECT * FROM empresa WHERE id_empresa = ? LIMIT 1");
        $stmt->bind_param("i", $idEmpresa);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Empresa no encontrada"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1]);
        $body    = jsonBody();
        $nombre  = trim($body['nombre'] ?? '');
        $correo  = trim($body['correo_contacto'] ?? '');
        $tel     = (int)($body['telefono'] ?? 0);
        $estado  = (int)($body['estado'] ?? 1);
        $fecha   = date('Y-m-d H:i:s');
        if (empty($nombre) || empty($correo)) responder(400, ["error" => "nombre y correo_contacto requeridos"]);
        $stmt = $db->prepare("
            INSERT INTO empresa (nombre, correo_contacto, telefono, estado, fecha_registro)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssiis", $nombre, $correo, $tel, $estado, $fecha);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Empresa creada", "id_empresa" => $newId]);
        break;

    case 'PUT':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idEmpresa = (int)$id;
        $body   = jsonBody();
        $nombre = trim($body['nombre'] ?? '');
        $correo = trim($body['correo_contacto'] ?? '');
        $tel    = (int)($body['telefono'] ?? 0);
        $estado = (int)($body['estado'] ?? 1);
        if (empty($nombre) || empty($correo)) responder(400, ["error" => "nombre y correo_contacto requeridos"]);
        $stmt = $db->prepare("
            UPDATE empresa SET nombre=?, correo_contacto=?, telefono=?, estado=?
            WHERE id_empresa=?
        ");
        $stmt->bind_param("ssiii", $nombre, $correo, $tel, $estado, $idEmpresa);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Empresa actualizada"]);
        break;

    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idEmpresa = (int)$id;
        $stmt = $db->prepare("DELETE FROM empresa WHERE id_empresa = ?");
        $stmt->bind_param("i", $idEmpresa);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Empresa eliminada"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
