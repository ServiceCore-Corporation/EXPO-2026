<?php
// Motor compartido de "Mi Perfil". No se enlaza directo desde el menú:
// cada rol tiene su propio vistas/<Rol>/perfil.php que define ROL_REQUERIDO
// y $dashboardRuta antes de incluir este archivo.

if (!defined('ROL_REQUERIDO')) {
    define('ROLES_PERMITIDOS', [1, 2, 3, 4, 5]);
}
require_once __DIR__ . '/../seguridad.php';
require_once __DIR__ . '/../conexion.php';

if (!isset($dashboardRuta)) {
    $dashboardRuta = 'index.php';
}

$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);

$usuario = [
    'id'              => '',
    'nombre'          => $_SESSION['nombre'] ?? '',
    'apellidos'       => '',
    'nombreCompleto'  => $_SESSION['nombre'] ?? '',
    'correo'          => $_SESSION['correo'] ?? '',
    'telefono'        => '',
    'empresa'         => '',
    'departamento'    => '',
    'cargo'           => '',
    'direccion'       => '',
    'rol'             => '',
    'estado'          => 'Activo',
    'fechaCreacion'   => '',
    'ultimoAcceso'    => '',
    'foto'            => '',
];

if ($idUsuario > 0) {
    $sql = "
        SELECT u.*, r.nombre AS rol, e.nombre AS empresa
        FROM usuario u
        LEFT JOIN rol r ON u.id_rol = r.id_rol
        LEFT JOIN empresa e ON u.id_empresa = e.id_empresa
        WHERE u.id_usuario = ? LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $usuario['id']            = sprintf('USR-%03d', $row['id_usuario']);
            $usuario['nombre']        = $row['nombre'] ?? $usuario['nombre'];
            $usuario['apellidos']     = $row['apellidos'] ?? '';
            $usuario['nombreCompleto']= trim(($row['nombre'] ?? '') . ' ' . ($row['apellidos'] ?? '')) ?: $usuario['nombreCompleto'];
            $usuario['correo']        = $row['correo'] ?? $usuario['correo'];
            $usuario['telefono']      = $row['telefono'] ?? '';
            $usuario['empresa']       = $row['empresa'] ?? '';
            $usuario['departamento']  = $row['departamento'] ?? '';
            $usuario['cargo']         = $row['cargo'] ?? '';
            $usuario['direccion']     = $row['direccion'] ?? '';
            $usuario['rol']           = $row['rol'] ?? '';
            $usuario['estado']        = isset($row['activo']) ? (((int)$row['activo'] === 1) ? 'Activo' : 'Inactivo') : $usuario['estado'];
            $usuario['fechaCreacion'] = !empty($row['fecha_creacion']) ? date('d M Y', strtotime($row['fecha_creacion'])) : '';
            $usuario['ultimoAcceso']  = !empty($row['ultimo_acceso']) ? date('d M Y — H:i', strtotime($row['ultimo_acceso'])) : '';
            $usuario['foto']          = $row['foto'] ?? '';
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

if (!function_exists('badgeClassPerfil')) {
function badgeClassPerfil($text) {
    $map = [
        'Administrador' => 'badge badge-purple',
        'Supervisor'    => 'badge badge-blue',
        'Agente'        => 'badge badge-green',
        'Cliente'       => 'badge badge-gray',
        'Activo'        => 'badge badge-green',
        'Inactivo'      => 'badge badge-gray',
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil y Seguridad — ServiceCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/perfil.css">
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="../../img/logogi.png" alt="ServiceCore Corporation" class="logo">
        </div>

        <nav class="menu">
            <a href="<?= htmlspecialchars($dashboardRuta) ?>" class="menu-item"><span class="material-symbols-outlined">insights</span>Dashboard</a>
            <a href="<?= htmlspecialchars($dashboardRuta) ?>" class="menu-item"><span class="material-symbols-outlined">confirmation_number</span>Tickets</a>
            <a href="perfil.php" class="menu-item active"><span class="material-symbols-outlined">account_circle</span>Mi Perfil</a>
            <a href="../historial.php" class="menu-item"><span class="material-symbols-outlined">history</span>Historial</a>
        </nav>

        <div class="sidebar-box">
            <p class="small-title">Estado de la cuenta</p>
            <strong class="estado-dot"><span></span><?= htmlspecialchars($usuario['estado']) ?></strong>
            <span>Último acceso: <?= htmlspecialchars($usuario['ultimoAcceso']) ?></span>
        </div>
    </aside>

    <header class="topbar">
        <button class="icon-btn mobile-only" id="btnSidebar"><span class="material-symbols-outlined">menu</span></button>
        <div>
            <p class="eyebrow">Panel general</p>
            <h2>Mi Perfil y Seguridad</h2>
        </div>
        <div class="top-actions">
            <div class="search-box">
                <span class="material-symbols-outlined">search</span>
                <input type="search" placeholder="Buscar en mi cuenta...">
            </div>
            <div class="top-actions-links">
                <a href="<?= htmlspecialchars($dashboardRuta) ?>" class="top-link">Dashboard</a>
                <a href="historial.php" class="top-link">Historial</a>
                <a href="../../logout.php" class="top-link text-red">Cerrar sesión</a>
            </div>
            <div class="profile" id="profileBtn">
                <div class="avatar"><?= htmlspecialchars(iniciales($usuario['nombreCompleto'])) ?></div>
                <div>
                    <strong><?= htmlspecialchars($usuario['nombreCompleto']) ?></strong>
                    <span><?= htmlspecialchars($usuario['empresa']) ?> / <?= htmlspecialchars($usuario['rol']) ?></span>
                </div>
                
                <span class="material-symbols-outlined profile-arrow">expand_more</span>
                <div class="sidebar-user-menu" id="userMenu">

    <a href="#" class="user-option" data-action="settings">
        <span class="material-symbols-outlined">settings</span>
        Configuración
    </a>

    <a href="perfil.php" class="user-option">
        <span class="material-symbols-outlined">person</span>
        Perfil
    </a>

    <a href="../../logout.php" class="user-option logout">
        <span class="material-symbols-outlined">logout</span>
        Cerrar Sesión
    </a>
</div>
            </div>
        </div>
    </header>

    <main class="content">

        <section class="page-head">
            <nav class="breadcrumb" aria-label="Ubicación actual">
                <a href="#">Panel</a>
                <span class="material-symbols-outlined">chevron_right</span>
                <span>Mi Perfil</span>
            </nav>
            <h1>Mi Perfil y Seguridad</h1>
            <p>Desde esta sección puedes administrar tu información personal, actualizar tus preferencias y proteger el acceso a tu cuenta.</p>
        </section>

        <!-- Tarjetas resumen -->
        <section class="summary-grid">
            <article class="card summary-card">
                <div class="summary-avatar-wrap">
                    <div class="avatar large" id="resumenAvatar"><?= htmlspecialchars(iniciales($usuario['nombreCompleto'])) ?></div>
                </div>
                <div>
                    <p class="eyebrow">Usuario</p>
                    <h3 id="resumenNombre"><?= htmlspecialchars($usuario['nombreCompleto']) ?></h3>
                    <span><?= htmlspecialchars($usuario['cargo']) ?></span>
                </div>
            </article>

            <article class="card summary-card">
                <div class="summary-icon blue"><span class="material-symbols-outlined">badge</span></div>
                <div>
                    <p class="eyebrow">Rol asignado</p>
                    <h3><span class="<?= badgeClassPerfil($usuario['rol']) ?> badge-lg"><?= htmlspecialchars($usuario['rol']) ?></span></h3>
                    <span>Permisos dentro del sistema</span>
                </div>
            </article>

            <article class="card summary-card">
                <div class="summary-icon <?= $usuario['estado'] === 'Activo' ? 'green' : 'gray' ?>">
                    <span class="material-symbols-outlined"><?= $usuario['estado'] === 'Activo' ? 'check_circle' : 'cancel' ?></span>
                </div>
                <div>
                    <p class="eyebrow">Estado de la cuenta</p>
                    <h3><span class="<?= badgeClassPerfil($usuario['estado']) ?> badge-lg"><?= htmlspecialchars($usuario['estado']) ?></span></h3>
                    <span>Acceso al sistema habilitado</span>
                </div>
            </article>
        </section>

        <!-- columna izquierda (formularios) + columna derecha (info / actividad) -->
        <section class="profile-layout">

            <div class="profile-main">

                <!-- Información Personal -->
                <article class="card" id="tarjetaInformacionPersonal">
                    <div class="section-head">
                        <div>
                            <p class="eyebrow">Datos generales</p>
                            <h3>Información Personal</h3>
                        </div>
                    </div>

                    <form id="formPerfil" novalidate>
                        <div class="photo-edit-row">
                            <div class="photo-circle-wrap">
                                <?php if (!empty($usuario['foto'])): ?>
                                    <img id="fotoPreview" class="photo-circle" src="../<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto de perfil">
                                    <div id="fotoPlaceholder" class="photo-circle placeholder" style="display:none;"><?= htmlspecialchars(iniciales($usuario['nombreCompleto'])) ?></div>
                                <?php else: ?>
                                    <img id="fotoPreview" class="photo-circle" src="" alt="Foto de perfil" style="display:none;">
                                    <div id="fotoPlaceholder" class="photo-circle placeholder"><?= htmlspecialchars(iniciales($usuario['nombreCompleto'])) ?></div>
                                <?php endif; ?>
                                <button type="button" class="photo-edit-btn" id="btnCambiarFoto" title="Cambiar fotografía">
                                    <span class="material-symbols-outlined">photo_camera</span>
                                </button>
                                <input type="file" id="fotoInput" accept="image/png, image/jpeg, image/webp" hidden>
                            </div>
                            <div>
                                <strong>Fotografía de perfil</strong>
                                <p class="hint">JPG, PNG o WEBP. Tamaño máximo recomendado 2 MB.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="campoNombre">Nombre completo</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">person</span>
                                    <input type="text" id="campoNombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" placeholder="Nombre" required>
                                </div>
                                <small class="field-error" id="errorNombre"></small>
                            </div>

                            <div class="form-group">
                                <label for="campoApellidos">Apellidos</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">badge</span>
                                    <input type="text" id="campoApellidos" name="apellidos" value="<?= htmlspecialchars($usuario['apellidos']) ?>" placeholder="Apellidos" required>
                                </div>
                                <small class="field-error" id="errorApellidos"></small>
                            </div>

                            <div class="form-group">
                                <label for="campoCorreo">Correo electrónico</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">mail</span>
                                    <input type="email" id="campoCorreo" name="correo" value="<?= htmlspecialchars($usuario['correo']) ?>" placeholder="correo@empresa.com" required>
                                </div>
                                <small class="field-error" id="errorCorreo"></small>
                            </div>

                            <div class="form-group">
                                <label for="campoTelefono">Teléfono</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">call</span>
                                    <input type="tel" id="campoTelefono" name="telefono" value="<?= htmlspecialchars($usuario['telefono']) ?>" placeholder="+502 0000-0000" required>
                                </div>
                                <small class="field-error" id="errorTelefono"></small>
                            </div>

                            <div class="form-group">
                                <label for="campoEmpresa">Empresa</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">apartment</span>
                                    <input type="text" id="campoEmpresa" name="empresa" value="<?= htmlspecialchars($usuario['empresa']) ?>" placeholder="Empresa" required>
                                </div>
                                <small class="field-error" id="errorEmpresa"></small>
                            </div>

                            <div class="form-group">
                                <label for="campoDepartamento">Departamento</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">domain</span>
                                    <input type="text" id="campoDepartamento" name="departamento" value="<?= htmlspecialchars($usuario['departamento']) ?>" placeholder="Departamento" required>
                                </div>
                                <small class="field-error" id="errorDepartamento"></small>
                            </div>

                            <div class="form-group">
                                <label for="campoCargo">Cargo</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">work</span>
                                    <input type="text" id="campoCargo" name="cargo" value="<?= htmlspecialchars($usuario['cargo']) ?>" placeholder="Cargo" required>
                                </div>
                                <small class="field-error" id="errorCargo"></small>
                            </div>

                            <div class="form-group span-2">
                                <label for="campoDireccion">Dirección <span class="optional">(opcional)</span></label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">location_on</span>
                                    <input type="text" id="campoDireccion" name="direccion" value="<?= htmlspecialchars($usuario['direccion']) ?>" placeholder="Dirección de contacto">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="btnGuardarCambios">
                                <span class="material-symbols-outlined">save</span> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </article>

                <!-- Seguridad de la Cuenta -->
                <article class="card" id="tarjetaSeguridad">
                    <div class="section-head">
                        <div>
                            <p class="eyebrow">Protección de acceso</p>
                            <h3>Seguridad de la Cuenta</h3>
                        </div>
                    </div>

                    <form id="formSeguridad" novalidate>
                        <div class="form-grid one-col">
                            <div class="form-group">
                                <label for="passActual">Contraseña actual</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">lock</span>
                                    <input type="password" id="passActual" name="passActual" placeholder="Ingresa tu contraseña actual" required>
                                    <button type="button" class="toggle-pass" data-target="passActual" tabindex="-1">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                                <small class="field-error" id="errorPassActual"></small>
                            </div>

                            <div class="form-group">
                                <label for="passNueva">Nueva contraseña</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">key</span>
                                    <input type="password" id="passNueva" name="passNueva" placeholder="Crea una nueva contraseña" required>
                                    <button type="button" class="toggle-pass" data-target="passNueva" tabindex="-1">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>

                                <div class="strength-meter">
                                    <div class="strength-bar"><span id="strengthFill"></span></div>
                                    <small id="strengthLabel">Seguridad de la contraseña</small>
                                </div>

                                <ul class="pass-rules" id="passChecklist">
                                    <li data-rule="length"><span class="material-symbols-outlined">radio_button_unchecked</span>Mínimo 8 caracteres</li>
                                    <li data-rule="mayus"><span class="material-symbols-outlined">radio_button_unchecked</span>Una letra mayúscula</li>
                                    <li data-rule="minus"><span class="material-symbols-outlined">radio_button_unchecked</span>Una letra minúscula</li>
                                    <li data-rule="numero"><span class="material-symbols-outlined">radio_button_unchecked</span>Un número</li>
                                    <li data-rule="especial"><span class="material-symbols-outlined">radio_button_unchecked</span>Un carácter especial</li>
                                </ul>
                            </div>

                            <div class="form-group">
                                <label for="passConfirmar">Confirmar nueva contraseña</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">check_circle</span>
                                    <input type="password" id="passConfirmar" name="passConfirmar" placeholder="Repite la nueva contraseña" required>
                                    <button type="button" class="toggle-pass" data-target="passConfirmar" tabindex="-1">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                                <small class="field-error" id="errorPassConfirmar"></small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-dark" id="btnActualizarPassword">
                                <span class="material-symbols-outlined">verified_user</span> Actualizar Contraseña
                            </button>
                        </div>
                    </form>
                </article>

                <!-- Preferencias del Usuario -->
                <article class="card" id="tarjetaPreferencias">
                    <div class="section-head">
                        <div>
                            <p class="eyebrow">Personalización</p>
                            <h3>Preferencias del Usuario</h3>
                        </div>
                    </div>

                    <form id="formPreferencias" novalidate>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="prefIdioma">Idioma del sistema</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">language</span>
                                    <select id="prefIdioma" name="idioma">
                                        <option value="es" selected>Español</option>
                                        <option value="en">English</option>
                                        <option value="pt">Português</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tema de la interfaz</label>
                                <div class="theme-switch" role="group" aria-label="Tema de la interfaz">
                                    <button type="button" class="theme-option active" data-theme="claro">
                                        <span class="material-symbols-outlined">light_mode</span> Claro
                                    </button>
                                    <button type="button" class="theme-option" data-theme="oscuro">
                                        <span class="material-symbols-outlined">dark_mode</span> Oscuro
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pref-toggles">
                            <div class="toggle-row">
                                <div>
                                    <strong>Notificaciones por correo electrónico</strong>
                                    <p>Recibe avisos importantes directamente en tu correo.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="notifCorreo" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-row">
                                <div>
                                    <strong>Notificaciones internas del sistema</strong>
                                    <p>Alertas y mensajes dentro de la plataforma.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="notifInterna" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-row">
                                <div>
                                    <strong>Recordatorios</strong>
                                    <p>Recordatorios sobre tickets y tareas pendientes.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="notifRecordatorio">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="btnGuardarPreferencias">
                                <span class="material-symbols-outlined">tune</span> Guardar Preferencias
                            </button>
                        </div>
                    </form>
                </article>

            </div>

            <div class="profile-side">

                <!-- Información general de la cuenta -->
                <article class="card">
                    <div class="section-head">
                        <div>
                            <p class="eyebrow">Resumen</p>
                            <h3>Información de la Cuenta</h3>
                        </div>
                    </div>
                    <dl class="account-info">
                        <div><dt>Nombre completo</dt><dd><?= htmlspecialchars($usuario['nombreCompleto']) ?></dd></div>
                        <div><dt>Correo electrónico</dt><dd><?= htmlspecialchars($usuario['correo']) ?></dd></div>
                        <div><dt>Rol</dt><dd><span class="<?= badgeClassPerfil($usuario['rol']) ?>"><?= htmlspecialchars($usuario['rol']) ?></span></dd></div>
                        <div><dt>Empresa</dt><dd><?= htmlspecialchars($usuario['empresa']) ?></dd></div>
                        <div><dt>ID de usuario</dt><dd><?= htmlspecialchars($usuario['id']) ?></dd></div>
                        <div><dt>Cuenta creada</dt><dd><?= htmlspecialchars($usuario['fechaCreacion']) ?></dd></div>
                        <div><dt>Último acceso</dt><dd><?= htmlspecialchars($usuario['ultimoAcceso']) ?></dd></div>
                        <div><dt>Estado</dt><dd><span class="<?= badgeClassPerfil($usuario['estado']) ?>"><?= htmlspecialchars($usuario['estado']) ?></span></dd></div>
                    </dl>
                </article>

                <!-- Actividad reciente -->
                <article class="card">
                    <div class="section-head">
                        <div>
                            <p class="eyebrow">Bitácora</p>
                            <h3>Actividad Reciente</h3>
                        </div>
                    </div>
                    <div class="timeline">
                        <?php if (empty($actividad)): ?>
                            <p class="hint">Aún no hay actividad registrada en tu cuenta.</p>
                        <?php endif; ?>
                        <?php foreach ($actividad as $a): ?>
                            <div class="timeline-item <?= $a['type'] ?>">
                                <span class="material-symbols-outlined"><?= $a['icon'] ?></span>
                                <div>
                                    <strong><?= htmlspecialchars($a['title']) ?></strong>
                                    <p><?= htmlspecialchars($a['text']) ?></p>
                                    <small><?= htmlspecialchars($a['time']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

            </div>
        </section>

        <footer class="footer">© 2026 ServiceCore Corporation — Mi Perfil y Seguridad.</footer>
    </main>

    <!-- Modal de confirmación reutilizable -->
    <div class="modal" id="modalConfirmar">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarTitulo">Confirmar acción</h3>
            <p id="modalConfirmarMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarCancelar">Cancelar</button>
                <button class="btn btn-primary" id="modalConfirmarAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Contenedor de notificaciones tipo Toast -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/api.js"></script>
    <script src="../../js/perfil.js"></script>
</body>
</html>
