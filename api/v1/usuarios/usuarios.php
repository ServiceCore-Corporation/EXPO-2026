<?php
// GET    /usuarios
// GET    /usuarios/:id
// POST   /usuarios
// PUT    /usuarios/:id
// DELETE /usuarios/:id
// PATCH  /usuarios/:id/estado
// GET    /usuarios/empresa/:idEmpresa
// GET    /usuarios/rol/:idRol

requireAuth();
$db = getConexion();

// GET /usuarios/empresa/:idEmpresa
if ($metodo === 'GET' && $id === 'empresa' && $sub !== null) {
    $idEmpresa = (int)$sub;
    $stmt = $db->prepare("
        SELECT u.id_usuario, u.nombre, u.correo, u.activo, u.fecha_creacion,
               r.nombre AS rol, u.id_rol, u.id_empresa
        FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
        WHERE u.id_empresa = ?
    ");
    $stmt->bind_param("i", $idEmpresa);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

// GET /usuarios/rol/:idRol
if ($metodo === 'GET' && $id === 'rol' && $sub !== null) {
    $idRol = (int)$sub;
    $stmt = $db->prepare("
        SELECT u.id_usuario, u.nombre, u.correo, u.activo, u.fecha_creacion,
               r.nombre AS rol, u.id_rol, u.id_empresa
        FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
        WHERE u.id_rol = ?
    ");
    $stmt->bind_param("i", $idRol);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

// PATCH /usuarios/:id/estado
if ($metodo === 'PATCH' && $id !== null && $sub === 'estado') {
    requireRol([1, 2]);
    $idUsuario = (int)$id;
    $body      = jsonBody();
    $activo    = isset($body['activo']) ? (int)$body['activo'] : null;
    if ($activo === null) responder(400, ["error" => "Campo 'activo' requerido"]);
    $stmt = $db->prepare("UPDATE usuario SET activo = ? WHERE id_usuario = ?");
    $stmt->bind_param("ii", $activo, $idUsuario);
    $stmt->execute();
    $stmt->close(); $db->close();
    responder(200, ["mensaje" => "Estado actualizado"]);
}

switch ($metodo) {

    // GET /usuarios
    case 'GET':
        if ($id === null) {
            $resultado = $db->query("
                SELECT u.id_usuario, u.nombre, u.correo, u.activo,
                       u.fecha_creacion, u.id_empresa,
                       r.nombre AS rol, u.id_rol
                FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
                ORDER BY u.id_usuario DESC
            ");
            $rows = $resultado->fetch_all(MYSQLI_ASSOC);
            $db->close();
            responder(200, $rows);
        }

        // GET /usuarios/:id
        $idUsuario = (int)$id;
        $stmt = $db->prepare("
            SELECT u.id_usuario, u.nombre, u.correo, u.activo,
                   u.fecha_creacion, u.id_empresa,
                   r.nombre AS rol, u.id_rol
            FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
            WHERE u.id_usuario = ? LIMIT 1
        ");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Usuario no encontrado"]);
        responder(200, $row);
        break;

    // POST /usuarios
    case 'POST':
        requireRol([1, 2]);
        $body       = jsonBody();
        $nombre     = trim($body['nombre'] ?? '');
        $correo     = trim($body['correo'] ?? '');
        $pass       = trim($body['password'] ?? '');
        $id_rol     = (int)($body['id_rol'] ?? 0);
        $id_empresa = (int)($body['id_empresa'] ?? 0);

        if (empty($nombre) || empty($correo) || empty($pass) || !$id_rol || !$id_empresa) {
            responder(400, ["error" => "Campos requeridos: nombre, correo, password, id_rol, id_empresa"]);
        }

        $hash  = password_hash($pass, PASSWORD_BCRYPT);
        $activo = 1;
        $fecha  = date('Y-m-d H:i:s');

        $stmt = $db->prepare("
            INSERT INTO usuario (nombre, correo, pass, id_rol, activo, fecha_creacion, id_empresa)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssiiisi", $nombre, $correo, $hash, $id_rol, $activo, $fecha, $id_empresa);

        // Corregir bind: 7 params
        $stmt->close();
        $stmt = $db->prepare("
            INSERT INTO usuario (nombre, correo, pass, id_rol, activo, fecha_creacion, id_empresa)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssiiss", $nombre, $correo, $hash, $id_rol, $activo, $fecha, $id_empresa);

        if (!$stmt->execute()) {
            $db->close();
            responder(409, ["error" => "Correo ya registrado o error al crear usuario"]);
        }
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Usuario creado", "id_usuario" => $newId]);
        break;

    // PUT /usuarios/:id
    case 'PUT':
        requireRol([1, 2]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idUsuario  = (int)$id;
        $body       = jsonBody();
        $nombre     = trim($body['nombre'] ?? '');
        $correo     = trim($body['correo'] ?? '');
        $id_rol     = (int)($body['id_rol'] ?? 0);
        $id_empresa = (int)($body['id_empresa'] ?? 0);

        if (empty($nombre) || empty($correo) || !$id_rol || !$id_empresa) {
            responder(400, ["error" => "Campos requeridos: nombre, correo, id_rol, id_empresa"]);
        }

        $stmt = $db->prepare("
            UPDATE usuario SET nombre=?, correo=?, id_rol=?, id_empresa=?
            WHERE id_usuario=?
        ");
        $stmt->bind_param("ssiii", $nombre, $correo, $id_rol, $id_empresa, $idUsuario);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Usuario actualizado"]);
        break;

    // DELETE /usuarios/:id
    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idUsuario = (int)$id;
        $stmt = $db->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Usuario eliminado"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
