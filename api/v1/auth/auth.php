<?php
// POST /auth/login
// POST /auth/logout
// GET  /auth/me

$accion = $id ?? ''; // partes[1]

switch ($metodo . ':' . $accion) {

    // ── POST /auth/login ─────────────────────────────────────
    case 'POST:login':
        $body   = jsonBody();
        $correo = trim($body['correo'] ?? '');
        $pass   = trim($body['password'] ?? '');

        if (empty($correo) || empty($pass)) {
            responder(400, ["error" => "Correo y contraseña requeridos"]);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            responder(400, ["error" => "Correo invalido"]);
        }

        $db   = getConexion();
        $stmt = $db->prepare("
            SELECT id_usuario, nombre, correo, pass, id_rol, activo
            FROM usuario WHERE correo = ? LIMIT 1
        ");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $res     = $stmt->get_result();
        $usuario = $res->fetch_assoc();
        $stmt->close();

        if (!$usuario) {
            $db->close();
            responder(401, ["error" => "Credenciales incorrectas"]);
        }

        if ((int)$usuario['activo'] !== 1) {
            $db->close();
            responder(403, ["error" => "Usuario desactivado"]);
        }

        if (!password_verify($pass, $usuario['pass'])) {
            $db->close();
            responder(401, ["error" => "Credenciales incorrectas"]);
        }

        $db->close();

        session_regenerate_id(true);
        $_SESSION['usuario_id']  = (int)$usuario['id_usuario'];
        $_SESSION['nombre']      = $usuario['nombre'];
        $_SESSION['correo']      = $usuario['correo'];
        $_SESSION['id_rol']      = (int)$usuario['id_rol'];
        $_SESSION['autenticado'] = true;
        $_SESSION['token']       = bin2hex(random_bytes(32));

        responder(200, [
            "mensaje"  => "Login exitoso",
            "usuario"  => [
                "id"     => $_SESSION['usuario_id'],
                "nombre" => $_SESSION['nombre'],
                "correo" => $_SESSION['correo'],
                "id_rol" => $_SESSION['id_rol'],
            ]
        ]);
        break;

    // ── POST /auth/logout ────────────────────────────────────
    case 'POST:logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }
        session_destroy();
        responder(200, ["mensaje" => "Sesion cerrada"]);
        break;

    // ── GET /auth/me ─────────────────────────────────────────
    case 'GET:me':
        $usuario = requireAuth();
        $db      = getConexion();
        $stmt    = $db->prepare("
            SELECT u.id_usuario, u.nombre, u.correo, u.activo,
                   u.fecha_creacion, u.id_empresa,
                   r.nombre AS rol
            FROM usuario u
            JOIN rol r ON r.id_rol = u.id_rol
            WHERE u.id_usuario = ? LIMIT 1
        ");
        $stmt->bind_param("i", $usuario['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->close();

        if (!$row) responder(404, ["error" => "Usuario no encontrado"]);
        responder(200, $row);
        break;

    default:
        responder(404, ["error" => "Accion de auth no encontrada"]);
}
