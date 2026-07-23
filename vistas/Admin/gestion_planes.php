<?php
define('ROL_REQUERIDO', 1); // 1 = Administrador General
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

$planes = [];
$resPlanes = $conn->query("SELECT id_plan, nombre, precio, limite_usuarios, limite_tickets, activo FROM plan ORDER BY precio ASC");
if ($resPlanes && $resPlanes->num_rows > 0) {
    while ($row = $resPlanes->fetch_assoc()) {
        $planes[] = [
            'id' => (int)$row['id_plan'],
            'nombre' => $row['nombre'],
            'precio' => (float)$row['precio'],
            'limite_usuarios' => (int)$row['limite_usuarios'],
            'limite_tickets' => (int)$row['limite_tickets'],
            'estado' => ((int)$row['activo'] === 1) ? 'Activo' : 'Inactivo',
        ];
    }
}
$conn->close();

$kpiTotal     = count($planes);
$kpiActivos   = count(array_filter($planes, fn($p) => $p['estado'] === 'Activo'));
$kpiInactivos = $kpiTotal - $kpiActivos;

function badgeClassPlan($estado) {
    return $estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray';
}

function formatoQuetzales($valor) {
    $valor = (float) $valor;
    if (floor($valor) == $valor) {
        return 'Q' . number_format($valor, 0);
    }
    return 'Q' . number_format($valor, 2);
}

function formatoNumero($valor) {
    return number_format((int) $valor, 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Planes — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/gestion_planes.css">
</head>
<body>
    <!-- ENCABEZADO -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Panel Administrador</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Administrador</p>
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
                <a href="../perfil.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">person</span>Perfil
                </a>
                <a href="../../logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 text-red-600 transition">
                    <span class="material-symbols-outlined">logout</span>Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- MENÚ LATERAL -->
    <aside class="fixed left-0 top-0 w-64 h-screen bg-[#1e1858] text-white p-6 flex flex-col">
        <div class="flex flex-col items-center mb-8">
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="flex flex-col flex-1 gap-2">
            <a href="dashboard_admin.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_empresas.php" class="menu-item ">
                <span class="material-symbols-outlined">business</span> Gestion de Empresas
            </a>
            <a href="gestion_carrusel.php" class="menu-item">
                <span class="material-symbols-outlined">view_carousel</span>Gestión de Carrusel
            </a>
            <a href="gestion_galeria.php" class="menu-item">
                <span class="material-symbols-outlined">photo_library</span>Gestión de Galería
            </a>
            <a href="gestion_planes.php" class="menu-item activo">
                <span class="material-symbols-outlined">workspace_premium</span>Planes
            </a>
            <a href="gestion_pagos.php" class="menu-item">
                <span class="material-symbols-outlined">payments</span>Pagos
            </a>
            <a href="reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial y Auditoría
            </a>
        </nav>

        <br><br>
        <!-- Cerrar sesión -->
        <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Planes</h2>
            <p class="text-gray-500 mt-2">Crea, edita y administra los planes que ofreces a las empresas.</p>
        </section>

        <!-- KPIs -->
        <section class="kpi-grid" id="kpiGrid">
            <article class="card kpi primary kpi-clickable active" data-filter-estado="">
                <div class="kpi-icon"><span class="material-symbols-outlined">workspace_premium</span></div>
                <div>
                    <p>Total de planes</p>
                    <h3 data-kpi="total"><?= $kpiTotal ?></h3>
                    <span>Planes registrados</span>
                </div>
            </article>
            <article class="card kpi green kpi-clickable" data-filter-estado="Activo">
                <div class="kpi-icon"><span class="material-symbols-outlined">toggle_on</span></div>
                <div>
                    <p>Planes activos</p>
                    <h3 data-kpi="activos"><?= $kpiActivos ?></h3>
                    <span>Disponibles para contratar</span>
                </div>
            </article>
            <article class="card kpi gray kpi-clickable" data-filter-estado="Inactivo">
                <div class="kpi-icon"><span class="material-symbols-outlined">toggle_off</span></div>
                <div>
                    <p>Planes inactivos</p>
                    <h3 data-kpi="inactivos"><?= $kpiInactivos ?></h3>
                    <span>No disponibles actualmente</span>
                </div>
            </article>
        </section>

        <!-- Barra de controles -->
        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorPlanes" placeholder="Buscar plan...">
                </div>
                <select id="filterEstadoPlan" class="input-small">
                    <option value="">Todos los estados</option>
                    <option value="Activo">Solo activos</option>
                    <option value="Inactivo">Solo inactivos</option>
                </select>
                <button class="btn btn-light" id="btnLimpiarFiltrosPlan">
                    <span class="material-symbols-outlined">refresh</span> Limpiar filtros
                </button>
            </div>
            <button class="btn btn-primary" id="btnNuevoPlan">
                <span class="material-symbols-outlined">add</span> Nuevo Plan
            </button>
        </section>

        <!-- Listado -->
        <section class="table-wrap card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Usuarios</th>
                        <th>Tickets</th>
                        <th>Estado</th>
                        <th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaPlanes">
                    <?php foreach ($planes as $p): ?>
                        <tr class="plan-row"
                            data-id="<?= htmlspecialchars($p['id']) ?>"
                            data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                            data-precio="<?= $p['precio'] ?>"
                            data-limite-usuarios="<?= $p['limite_usuarios'] ?>"
                            data-limite-tickets="<?= $p['limite_tickets'] ?>"
                            data-estado="<?= htmlspecialchars($p['estado']) ?>"
                        >
                            <td>
                                <span class="plan-nombre">
                                    <span class="material-symbols-outlined">workspace_premium</span>
                                    <strong data-row-nombre><?= htmlspecialchars($p['nombre']) ?></strong>
                                </span>
                            </td>
                            <td><span data-row-precio><?= formatoQuetzales($p['precio']) ?></span></td>
                            <td><span data-row-usuarios><?= formatoNumero($p['limite_usuarios']) ?></span></td>
                            <td><span data-row-tickets><?= formatoNumero($p['limite_tickets']) ?></span></td>
                            <td><span class="<?= badgeClassPlan($p['estado']) ?>" data-row-estado-badge><?= htmlspecialchars($p['estado']) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="row-icon-btn" data-action="editar" title="Editar plan">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="row-icon-btn toggle-btn" data-action="toggle-estado" title="Activar / desactivar plan">
                                        <span class="material-symbols-outlined" data-row-toggle-icon><?= $p['estado'] === 'Activo' ? 'toggle_on' : 'toggle_off' ?></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="empty-table" id="emptyPlanes" style="display:<?= empty($planes) ? 'flex' : 'none' ?>;">
                <span class="material-symbols-outlined">search_off</span>
                No se encontraron planes que coincidan con tu búsqueda.
            </p>
        </section>

    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <!-- Modal: crear / editar plan -->
    <div class="modal" id="modalPlan">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalPlanCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">workspace_premium</span></div>
            <h3 id="modalPlanTitulo">Nuevo Plan</h3>
            <p id="modalPlanSub">Completa los datos para registrar un nuevo plan de servicio.</p>

            <form id="formPlan" novalidate>
                <input type="hidden" id="planId">

                <div class="form-group" style="text-align:left;">
                    <label for="planNombre">Nombre del plan</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">workspace_premium</span>
                        <input type="text" id="planNombre" placeholder="Ej. Básico, Profesional, Empresarial">
                    </div>
                    <small class="field-error" id="errorPlanNombre"></small>
                </div>

                <div class="form-row">
                    <div class="form-group" style="text-align:left;">
                        <label for="planPrecio">Precio (Q)</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">payments</span>
                            <input type="number" id="planPrecio" min="0" step="0.01" placeholder="99.00">
                        </div>
                        <small class="field-error" id="errorPlanPrecio"></small>
                    </div>

                    <div class="form-group" style="text-align:left;">
                        <label for="planEstado">Estado</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">toggle_on</span>
                            <select id="planEstado">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="text-align:left;">
                        <label for="planLimiteUsuarios">Límite de usuarios</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">group</span>
                            <input type="number" id="planLimiteUsuarios" min="1" step="1" placeholder="20">
                        </div>
                        <small class="field-error" id="errorPlanLimiteUsuarios"></small>
                    </div>

                    <div class="form-group" style="text-align:left;">
                        <label for="planLimiteTickets">Límite de tickets</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">confirmation_number</span>
                            <input type="number" id="planLimiteTickets" min="1" step="1" placeholder="500">
                        </div>
                        <small class="field-error" id="errorPlanLimiteTickets"></small>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarPlan">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPlan">
                        <span class="material-symbols-outlined">save</span> Crear Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación reutilizable -->
    <div class="modal" id="modalConfirmarPlan">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarPlanCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarPlanTitulo">Confirmar acción</h3>
            <p id="modalConfirmarPlanMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarPlanCancelar">Cancelar</button>
                <button class="btn btn-danger" id="modalConfirmarPlanAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/api.js"></script>
    <script src="../../js/planes_pagos.js"></script>
    <script src="../../js/gestion_planes.js"></script>
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
