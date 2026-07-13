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

    try {
        // El correo es único: verificar que no pertenezca a otro usuario
        $stmtChk = $db->prepare("SELECT id_usuario FROM usuario WHERE correo = ? AND id_usuario != ? LIMIT 1");
        $stmtChk->bind_param("si", $correo, $idUsuario);
        $stmtChk->execute();
        if ($stmtChk->get_result()->fetch_assoc()) {
            $stmtChk->close(); $db->close();
            responder(409, ["error" => "Ese correo ya está en uso por otra cuenta"]);
        }
        $stmtChk->close();

        // Confirmar que el usuario realmente existe antes de actualizar
        // (si el id de sesión no corresponde a ningún registro, el UPDATE
        // "tendría éxito" sin cambiar nada y reportaría un falso positivo).
        $stmtExiste = $db->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ? LIMIT 1");
        $stmtExiste->bind_param("i", $idUsuario);
        $stmtExiste->execute();
        $existe = $stmtExiste->get_result()->fetch_assoc();
        $stmtExiste->close();
        if (!$existe) {
            $db->close();
            responder(404, ["error" => "No se encontró tu usuario en la base de datos (sesión inválida). Vuelve a iniciar sesión."]);
        }

        // Nota: la empresa NO se edita aquí (es una entidad compartida por id_empresa,
        // renombrarla afectaría a todos los usuarios de esa empresa).
        $stmt = $db->prepare("
            UPDATE usuario
            SET nombre = ?, apellidos = ?, correo = ?, telefono = ?, departamento = ?, cargo = ?, direccion = ?
            WHERE id_usuario = ?
        ");
        if (!$stmt) {
            $db->close();
            responder(500, ["error" => "Error preparando la actualización: " . $db->error]);
        }
        $stmt->bind_param(
            "sssssssi",
            $nombre, $apellidos, $correo, $telefono, $departamento, $cargo, $direccion, $idUsuario
        );

        $ok = $stmt->execute();
        if (!$ok) {
            $errorSql = $stmt->error;
            $stmt->close(); $db->close();
            responder(500, ["error" => "No se pudo actualizar el perfil: " . $errorSql]);
        }
        $filasAfectadas = $stmt->affected_rows;
        $stmt->close();

        // affected_rows en 0 no siempre es un error: mysqli reporta 0 si los
        // valores enviados son idénticos a los que ya estaban guardados.
        // Confirmamos releyendo la fila para asegurarnos de que sí quedó
        // como se esperaba, en vez de confiar ciegamente en el resultado.
        $stmtVerif = $db->prepare("SELECT nombre, apellidos, correo, telefono, departamento, cargo, direccion FROM usuario WHERE id_usuario = ? LIMIT 1");
        $stmtVerif->bind_param("i", $idUsuario);
        $stmtVerif->execute();
        $filaGuardada = $stmtVerif->get_result()->fetch_assoc();
        $stmtVerif->close();

        $coincide = $filaGuardada
            && $filaGuardada['nombre'] === $nombre
            && (string)$filaGuardada['apellidos'] === $apellidos
            && $filaGuardada['correo'] === $correo;

        if (!$coincide) {
            $db->close();
            responder(500, ["error" => "El perfil no se guardó correctamente. Intenta de nuevo."]);
        }

        // Mantener la sesión sincronizada con el nombre/correo nuevos
        $_SESSION['nombre'] = $nombre;
        $_SESSION['correo'] = $correo;

        $db->close();
        responder(200, ["mensaje" => "Perfil actualizado correctamente", "filas_afectadas" => $filasAfectadas]);
    } catch (\Throwable $e) {
        error_log('[api/perfil PUT] ' . $e->getMessage());
        $db->close();
        responder(500, ["error" => "Error del servidor al guardar el perfil: " . $e->getMessage()]);
    }
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
    try {
        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $codigoError = $_FILES['foto']['error'] ?? 'sin_archivo';
            responder(400, ["error" => "No se recibió ninguna imagen (código: {$codigoError})"]);
        }

        $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $tipo       = mime_content_type($_FILES['foto']['tmp_name']);
        if (!isset($permitidos[$tipo])) {
            responder(400, ["error" => "Formato no permitido. Usa JPG, PNG o WEBP"]);
        }
        if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            responder(400, ["error" => "La imagen no debe superar 2MB"]);
        }

        // Ruta física en disco (funciona igual en XAMPP/Windows y en Hostinger/Linux,
        // porque __DIR__ siempre usa el separador correcto del sistema operativo).
        $carpeta = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
                 . DIRECTORY_SEPARATOR . 'vistas' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'perfil' . DIRECTORY_SEPARATOR;

        if (!is_dir($carpeta)) {
            if (!mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
                responder(500, ["error" => "No se pudo crear la carpeta de subida (revisa permisos de escritura en vistas/uploads/perfil)."]);
            }
        }
        if (!is_writable($carpeta)) {
            responder(500, ["error" => "La carpeta vistas/uploads/perfil no tiene permisos de escritura."]);
        }

        // Nombre de archivo único e impredecible: usuario + timestamp + bytes
        // aleatorios, así nunca se pisan dos fotos aunque se suban en el mismo segundo.
        $nombreArchivo = 'usuario_' . $idUsuario . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $permitidos[$tipo];
        $rutaFisica    = $carpeta . $nombreArchivo;
        $rutaPublica   = 'uploads/perfil/' . $nombreArchivo;

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $rutaFisica)) {
            responder(500, ["error" => "No se pudo mover la imagen a la carpeta de destino."]);
        }

        // Buscar y eliminar la foto anterior del usuario (si existía) para no
        // dejar archivos huérfanos ocupando espacio en el servidor.
        $stmtOld = $db->prepare("SELECT foto FROM usuario WHERE id_usuario = ? LIMIT 1");
        $stmtOld->bind_param("i", $idUsuario);
        $stmtOld->execute();
        $fotoAnterior = $stmtOld->get_result()->fetch_assoc()['foto'] ?? null;
        $stmtOld->close();

        $stmt = $db->prepare("UPDATE usuario SET foto = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $rutaPublica, $idUsuario);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            // Si falla el guardado en BD, no dejamos el archivo huérfano en disco.
            @unlink($rutaFisica);
            $db->close();
            responder(500, ["error" => "No se pudo registrar la foto en la base de datos."]);
        }

        if (!empty($fotoAnterior) && basename($fotoAnterior) !== $nombreArchivo) {
            $rutaAnterior = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
                          . DIRECTORY_SEPARATOR . 'vistas' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fotoAnterior);
            if (is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }

        $db->close();
        responder(200, ["mensaje" => "Foto actualizada", "foto" => $rutaPublica]);
    } catch (\Throwable $e) {
        error_log('[api/perfil POST foto] ' . $e->getMessage());
        responder(500, ["error" => "Error del servidor al subir la foto: " . $e->getMessage()]);
    }
}

responder(404, ["error" => "Endpoint de perfil no encontrado"]);
