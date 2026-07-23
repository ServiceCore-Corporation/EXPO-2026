<?php
/**
 * vistas/Cliente/perfil.php
 * Perfil propio de este rol (id_rol = 5). Header y sidebar
 * son EXACTAMENTE los mismos que usa el resto de las páginas de este rol
 * (mismo markup, mismas clases, mismos iconos) para mantener consistencia
 * visual total con el sistema. Solo reutiliza funciones puras desde
 * perfil_logica.php; la vista y el control de acceso son propios de este
 * archivo.
 */
define('ROL_REQUERIDO', 5);
require_once __DIR__ . '/../../seguridad.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../perfil_logica.php';

$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
[$usuario, $actividad] = cargarDatosPerfil($conn, $idUsuario);
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil y Seguridad — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/perfil.css">
</head>
<body>

    <!-- ENCABEZADO (idéntico al resto del sistema para este rol) -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Mesa de Ayuda</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Cliente</p>
            </div>
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold overflow-hidden">
                <?php if (!empty($_SESSION['foto'])): ?>
                    <img src="../<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Foto de perfil" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div id="menuUsuario" class="hidden absolute right-0 top-14 w-52 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50">
                <a href="perfil.php#tarjetaPreferencias" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">settings</span>Configuración
                </a>
                <a href="perfil.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">person</span>Perfil
                </a>
                <a href="../../logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 text-red-600 transition">
                    <span class="material-symbols-outlined">logout</span>Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- MENÚ LATERAL (idéntico al resto del sistema para este rol) -->
    <aside class="fixed left-0 top-0 w-64 h-screen bg-[#1e1858] text-white p-6 flex flex-col">
        <div class="flex flex-col items-center mb-8">
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="flex flex-col flex-1 gap-2">
            <a href="dashboard_cliente.php" class="menu-item">
                <span class="material-symbols-outlined">confirmation_number</span>Mis Tickets
            </a>
            <a href="perfil.php" class="menu-item activo">
                <span class="material-symbols-outlined">person</span>Perfil
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
        </nav>

        <div class="mt-4 p-4 rounded-xl bg-white/10 text-sm">
            <p class="text-xs uppercase tracking-wide text-white/60 mb-1">Estado de la cuenta</p>
            <strong class="flex items-center gap-2 text-green-300">
                <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                <?= htmlspecialchars($usuario['estado']) ?>
            </strong>
            <span class="text-white/60 text-xs">Último acceso: <?= htmlspecialchars($usuario['ultimoAcceso']) ?></span>
        </div>

        <!-- Cerrar sesión -->
        <a href="../../logout.php" class="mt-4 flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

<main class="contenido ml-64 pt-24 px-8 pb-10">

        <section class="page-head">
            <nav class="breadcrumb" aria-label="Ubicación actual">
                <a href="dashboard_cliente.php">Panel</a>
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
                    <div class="avatar large" id="resumenAvatar">
                        <?php if (!empty($usuario['foto'])): ?>
                            <img src="../<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto de perfil" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= htmlspecialchars(iniciales($usuario['nombreCompleto'])) ?>
                        <?php endif; ?>
                    </div>
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
    <script>
        const botonUsuario = document.getElementById("botonUsuario");
        const menuUsuario  = document.getElementById("menuUsuario");
        botonUsuario.addEventListener("click", () => menuUsuario.classList.toggle("hidden"));
        document.addEventListener("click", (e) => {
            if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
                menuUsuario.classList.add("hidden");
        });
    </script>
</body>
</html>
