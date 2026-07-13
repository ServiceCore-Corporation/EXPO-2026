<?php
// GET   /perfil              -> datos del perfil del usuario en sesión
// GET   /perfil/actividad    -> actividad reciente real (tabla historial)
// PUT   /perfil              -> actualiza datos personales
// PATCH /perfil/password     -> cambia la contraseña
// POST  /perfil/foto         -> sube/actualiza la foto de perfil

requireAuth();
$db = getConexion();

$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
if (!$idUsuario) {
    responder(401, ["error" => "No autenticado"]);
}

// ── GET /perfil/actividad ──────────────────────────────────────────────
if ($metodo === 'GET' && $id === 'actividad') {
    $stmt = $db->prepare("
        SELECT h.id_historial, h.accion, h.campo_modificado, h.valor_anterior, h.valor_nuevo, h.fecha,
               t.titulo AS ticket, t.id_ticket
        FROM historial h
        LEFT JOIN ticket t ON t.id_ticket = h.id_ticket
        WHERE h.id_usuario = ?
        ORDER BY h.fecha DESC
        LIMIT 8
    ");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $actividad = array_map(function ($r) {
        return [
            'icon'  => 'history',
            'title' => $r['accion'],
            'text'  => $r['ticket']
                ? "Ticket #{$r['id_ticket']} — {$r['ticket']}" . ($r['campo_modificado'] ? " ({$r['campo_modificado']}: {$r['valor_anterior']} → {$r['valor_nuevo']})" : '')
                : $r['campo_modificado'],
            'time'  => $r['fecha'],
            'type'  => 'blue',
        ];
    }, $rows);

    $db->close();
    responder(200, $actividad);
}

// ── GET /perfil ─────────────────────────────────────────────────────────
if ($metodo === 'GET' && $id === null) {
    $stmt = $db->prepare("
        SELECT u.*, r.nombre AS rol, e.nombre AS empresa
        FROM usuario u
        LEFT JOIN rol r ON u.id_rol = r.id_rol
        LEFT JOIN empresa e ON u.id_empresa = e.id_empresa
        WHERE u.id_usuario = ? LIMIT 1
    ");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    if (!$row) responder(404, ["error" => "Usuario no encontrado"]);
    unset($row['pass']);
    responder(200, $row);
}

// ── PUT /perfil (datos personales) ───────────────────────────────────────
if ($metodo === 'PUT' && $id === null) {
    $body = jsonBody();

    $nombre       = trim($body['nombre'] ?? '');
    $apellidos    = trim($body['apellidos'] ?? '');
    $correo       = trim($body['correo'] ?? '');
    $telefono     = trim($body['telefono'] ?? '');
    $departamento = trim($body['departamento'] ?? '');
    $cargo        = trim($body['cargo'] ?? '');
    $direccion    = trim($body['direccion'] ?? '');

    if ($nombre === '' || $correo === '') {
        responder(400, ["error" => "Nombre y correo son obligatorios"]);
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responder(400, ["error" => "Correo inválido"]);
    }

    // El correo es único: verificar que no pertenezca a otro usuario
    $stmtChk = $db->prepare("SELECT id_usuario FROM usuario WHERE correo = ? AND id_usuario != ? LIMIT 1");
    $stmtChk->bind_param("si", $correo, $idUsuario);
    $stmtChk->execute();
    if ($stmtChk->get_result()->fetch_assoc()) {
        $stmtChk->close(); $db->close();
        responder(409, ["error" => "Ese correo ya está en uso por otra cuenta"]);
    }
    $stmtChk->close();

    // Nota: la empresa NO se edita aquí (es una entidad compartida por id_empresa,
    // renombrarla afectaría a todos los usuarios de esa empresa).
    $stmt = $db->prepare("
        UPDATE usuario
        SET nombre = ?, apellidos = ?, correo = ?, telefono = ?, departamento = ?, cargo = ?, direccion = ?
        WHERE id_usuario = ?
    ");
    $stmt->bind_param(
        "sssssssi",
        $nombre, $apellidos, $correo, $telefono, $departamento, $cargo, $direccion, $idUsuario
    );
    $stmt->execute();
    $stmt->close();

    // Mantener la sesión sincronizada con el nombre/correo nuevos
    $_SESSION['nombre'] = $nombre;
    $_SESSION['correo'] = $correo;

    $db->close();
    responder(200, ["mensaje" => "Perfil actualizado correctamente"]);
}

// ── PATCH /perfil/password ───────────────────────────────────────────────
if ($metodo === 'PATCH' && $id === 'password') {
    $body        = jsonBody();
    $passActual  = (string)($body['passActual'] ?? '');
    $passNueva   = (string)($body['passNueva'] ?? '');

    if ($passActual === '' || $passNueva === '') {
        responder(400, ["error" => "Debes indicar la contraseña actual y la nueva"]);
    }
    if (strlen($passNueva) < 8) {
        responder(400, ["error" => "La nueva contraseña debe tener al menos 8 caracteres"]);
    }

    $stmt = $db->prepare("SELECT pass FROM usuario WHERE id_usuario = ? LIMIT 1");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($passActual, $row['pass'])) {
        $db->close();
        responder(401, ["error" => "La contraseña actual no es correcta"]);
    }

    $hash = password_hash($passNueva, PASSWORD_BCRYPT);
    $stmtU = $db->prepare("UPDATE usuario SET pass = ? WHERE id_usuario = ?");
    $stmtU->bind_param("si", $hash, $idUsuario);
    $stmtU->execute();
    $stmtU->close();
    $db->close();

    responder(200, ["mensaje" => "Contraseña actualizada correctamente"]);
}

// ── POST /perfil/foto ────────────────────────────────────────────────────
if ($metodo === 'POST' && $id === 'foto') {
    if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        responder(400, ["error" => "No se recibió ninguna imagen"]);
    }

    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tipo       = mime_content_type($_FILES['foto']['tmp_name']);
    if (!isset($permitidos[$tipo])) {
        responder(400, ["error" => "Formato no permitido. Usa JPG, PNG o WEBP"]);
    }
    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
        responder(400, ["error" => "La imagen no debe superar 2MB"]);
    }

    $carpeta = __DIR__ . '/../../../vistas/uploads/perfil/';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    $nombreArchivo = 'usuario_' . $idUsuario . '_' . time() . '.' . $permitidos[$tipo];
    $rutaFisica    = $carpeta . $nombreArchivo;
    $rutaPublica   = 'uploads/perfil/' . $nombreArchivo;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $rutaFisica)) {
        responder(500, ["error" => "No se pudo guardar la imagen"]);
    }

    $stmt = $db->prepare("UPDATE usuario SET foto = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $rutaPublica, $idUsuario);
    $stmt->execute();
    $stmt->close();
    $db->close();

    responder(200, ["mensaje" => "Foto actualizada", "foto" => $rutaPublica]);
}

responder(404, ["error" => "Endpoint de perfil no encontrado"]);
