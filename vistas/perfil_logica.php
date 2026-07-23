<?php
/**
 * vistas/perfil_logica.php
 * -------------------------------------------------------------------------
 * Capa de datos del módulo "Mi Perfil". NO contiene HTML ni control de
 * acceso: cada vistas/<Rol>/perfil.php define su propio ROL_REQUERIDO,
 * hace su propio require_once de seguridad.php/conexion.php, y luego
 * llama a cargarDatosPerfil() para obtener los datos del usuario y su
 * actividad reciente. La vista (header, sidebar, formularios) vive
 * completa dentro de cada archivo de rol, no aquí.
 * -------------------------------------------------------------------------
 */

if (!function_exists('cargarDatosPerfil')) {
    function cargarDatosPerfil(mysqli $conn, int $idUsuario): array
    {
        $usuario = [
            'id'             => '',
            'nombre'         => $_SESSION['nombre'] ?? '',
            'apellidos'      => '',
            'nombreCompleto' => $_SESSION['nombre'] ?? '',
            'correo'         => $_SESSION['correo'] ?? '',
            'telefono'       => '',
            'empresa'        => '',
            'departamento'   => '',
            'cargo'          => '',
            'direccion'      => '',
            'rol'            => '',
            'estado'         => 'Activo',
            'fechaCreacion'  => '',
            'ultimoAcceso'   => '',
            'foto'           => '',
        ];

        if ($idUsuario > 0) {
            $stmt = $conn->prepare("
                SELECT u.*, r.nombre AS rol, e.nombre AS empresa
                FROM usuario u
                LEFT JOIN rol r ON u.id_rol = r.id_rol
                LEFT JOIN empresa e ON u.id_empresa = e.id_empresa
                WHERE u.id_usuario = ? LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('i', $idUsuario);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    $usuario['id']             = sprintf('USR-%03d', $row['id_usuario']);
                    $usuario['nombre']         = $row['nombre'] ?? $usuario['nombre'];
                    $usuario['apellidos']      = $row['apellidos'] ?? '';
                    $usuario['nombreCompleto'] = trim(($row['nombre'] ?? '') . ' ' . ($row['apellidos'] ?? '')) ?: $usuario['nombreCompleto'];
                    $usuario['correo']         = $row['correo'] ?? $usuario['correo'];
                    $usuario['telefono']       = $row['telefono'] ?? '';
                    $usuario['empresa']        = $row['empresa'] ?? '';
                    $usuario['departamento']   = $row['departamento'] ?? '';
                    $usuario['cargo']          = $row['cargo'] ?? '';
                    $usuario['direccion']      = $row['direccion'] ?? '';
                    $usuario['rol']            = $row['rol'] ?? '';
                    $usuario['estado']         = isset($row['activo']) ? (((int)$row['activo'] === 1) ? 'Activo' : 'Inactivo') : $usuario['estado'];
                    $usuario['fechaCreacion']  = !empty($row['fecha_creacion']) ? date('d M Y', strtotime($row['fecha_creacion'])) : '';
                    $usuario['ultimoAcceso']   = !empty($row['ultimo_acceso']) ? date('d M Y — H:i', strtotime($row['ultimo_acceso'])) : '';
                    $usuario['foto']           = $row['foto'] ?? '';
                }
            }
        }

        $actividad = [];
        $stmtAct = $conn->prepare("
            SELECT h.accion, h.campo_modificado, h.valor_anterior, h.valor_nuevo, h.fecha,
                   t.titulo AS ticket, t.id_ticket
            FROM historial h
            LEFT JOIN ticket t ON t.id_ticket = h.id_ticket
            WHERE h.id_usuario = ?
            ORDER BY h.fecha DESC
            LIMIT 6
        ");
        if ($stmtAct) {
            $stmtAct->bind_param('i', $idUsuario);
            $stmtAct->execute();
            $filasAct = $stmtAct->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtAct->close();

            foreach ($filasAct as $r) {
                $texto = $r['ticket'] ? "Ticket #{$r['id_ticket']} — {$r['ticket']}" : ($r['campo_modificado'] ?: 'Sin detalle');
                if ($r['campo_modificado'] && $r['ticket']) {
                    $texto .= " ({$r['campo_modificado']}: {$r['valor_anterior']} → {$r['valor_nuevo']})";
                }
                $actividad[] = [
                    'icon'  => 'history',
                    'title' => $r['accion'],
                    'text'  => $texto,
                    'time'  => !empty($r['fecha']) ? date('d M Y — H:i', strtotime($r['fecha'])) : '',
                    'type'  => 'blue',
                ];
            }
        }
        if (!empty($usuario['ultimoAcceso'])) {
            array_unshift($actividad, [
                'icon'  => 'login',
                'title' => 'Inicio de sesión',
                'text'  => 'Último acceso registrado a la cuenta.',
                'time'  => $usuario['ultimoAcceso'],
                'type'  => 'primary',
            ]);
        }

        return [$usuario, $actividad];
    }
}

if (!function_exists('badgeClassPerfil')) {
    function badgeClassPerfil($text) {
        $map = [
            'Admin ServiceCore' => 'badge badge-purple',
            'Admin Empresa'     => 'badge badge-purple',
            'Administrador'     => 'badge badge-purple',
            'Supervisor'        => 'badge badge-blue',
            'Agente'            => 'badge badge-green',
            'Cliente'           => 'badge badge-gray',
            'Activo'            => 'badge badge-green',
            'Inactivo'          => 'badge badge-gray',
        ];
        return $map[$text] ?? 'badge badge-gray';
    }
}

if (!function_exists('iniciales')) {
    function iniciales($nombreCompleto) {
        $partes = explode(' ', trim($nombreCompleto));
        $ini = '';
        foreach (array_slice($partes, 0, 2) as $p) { $ini .= mb_substr($p, 0, 1); }
        return mb_strtoupper($ini);
    }
}
