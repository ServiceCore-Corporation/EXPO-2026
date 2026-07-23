<?php
define('ROL_REQUERIDO', 1);
require_once '../../seguridad.php';
require_once '../../conexion.php';

$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

$empresas = [];
$resEmp = $conn->query("SELECT id_empresa, nombre FROM empresa ORDER BY nombre ASC");
if ($resEmp && $resEmp->num_rows > 0) {
    while ($row = $resEmp->fetch_assoc()) {
        $empresas[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/gestion_pagos.css">
</head>
<body>
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
            <a href="gestion_planes.php" class="menu-item">
                <span class="material-symbols-outlined">workspace_premium</span>Planes
            </a>
            <a href="gestion_pagos.php" class="menu-item activo">
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
            <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Pagos</h2>
            <p class="text-gray-500 mt-2">Consulta y registra los pagos realizados por cada empresa.</p>
        </section>

        <section class="kpi-grid" id="kpiGrid">
            <article class="card kpi primary kpi-clickable active" data-filter-estado="">
                <div class="kpi-icon"><span class="material-symbols-outlined">payments</span></div>
                <div>
                    <p>Total recaudado</p>
                    <h3 data-kpi="total" class="kpi-loading">Q0.00</h3>
                    <span>Suma de pagos confirmados</span>
                </div>
            </article>
            <article class="card kpi green kpi-clickable" data-filter-estado="1">
                <div class="kpi-icon"><span class="material-symbols-outlined">check_circle</span></div>
                <div>
                    <p>Pagos confirmados</p>
                    <h3 data-kpi="pagados" class="kpi-loading">0</h3>
                    <span>Marcados como pagados</span>
                </div>
            </article>
            <article class="card kpi gray kpi-clickable" data-filter-estado="0">
                <div class="kpi-icon"><span class="material-symbols-outlined">hourglass_empty</span></div>
                <div>
                    <p>Pendientes</p>
                    <h3 data-kpi="pendientes" class="kpi-loading">0</h3>
                    <span>Aún sin confirmar</span>
                </div>
            </article>
        </section>

        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorPagos" placeholder="Buscar por empresa...">
                </div>
                <select id="filterEstadoPago" class="input-small">
                    <option value="">Todos los estados</option>
                    <option value="1">Pagado</option>
                    <option value="0">Pendiente</option>
                    <option value="2">Rechazado</option>
                </select>
                <button class="btn btn-light" id="btnLimpiarFiltrosPago">
                    <span class="material-symbols-outlined">refresh</span> Limpiar filtros
                </button>
            </div>
            <button class="btn btn-primary" id="btnNuevoPago">
                <span class="material-symbols-outlined">add</span> Registrar Pago
            </button>
        </section>

        <section class="table-wrap card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-pagos">
                    <tr><td colspan="6" class="sin-datos">Cargando pagos…</td></tr>
                </tbody>
            </table>

            <p class="empty-table" id="emptyPagos" style="display:none;">
                <span class="material-symbols-outlined">search_off</span>
                No se encontraron pagos que coincidan con tu búsqueda.
            </p>
        </section>

    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <div class="modal" id="modalPago">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalPagoCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">payments</span></div>
            <h3 id="modalPagoTitulo">Registrar Pago</h3>
            <p id="modalPagoSub">Completa los datos del pago recibido.</p>

            <form id="formPago" novalidate>
                <input type="hidden" id="pagoId">

                <div class="form-group" style="text-align:left;">
                    <label for="pagoEmpresa">Empresa</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">business</span>
                        <select id="pagoEmpresa">
                            <option value="">Selecciona una empresa</option>
                            <?php foreach ($empresas as $e): ?>
                                <option value="<?= (int)$e['id_empresa'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <small class="field-error" id="errorPagoEmpresa"></small>
                </div>

                <div class="form-row">
                    <div class="form-group" style="text-align:left;">
                        <label for="pagoMonto">Monto (Q)</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">payments</span>
                            <input type="number" id="pagoMonto" min="0" step="0.01" placeholder="199.00">
                        </div>
                        <small class="field-error" id="errorPagoMonto"></small>
                    </div>

                    <div class="form-group" style="text-align:left;">
                        <label for="pagoEstado">Estado</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">toggle_on</span>
                            <select id="pagoEstado">
                                <option value="1">Pagado</option>
                                <option value="0">Pendiente</option>
                                <option value="2">Rechazado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="text-align:left;">
                        <label for="pagoMetodo">Método de pago</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">credit_card</span>
                            <select id="pagoMetodo">
                                <option value="Tarjeta">Tarjeta</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="text-align:left;">
                        <label for="pagoFecha">Fecha</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">event</span>
                            <input type="date" id="pagoFecha">
                        </div>
                        <small class="field-error" id="errorPagoFecha"></small>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarPago">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPago">
                        <span class="material-symbols-outlined">save</span> Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="modalConfirmarPago">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarPagoCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarPagoTitulo">Confirmar acción</h3>
            <p id="modalConfirmarPagoMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarPagoCancelar">Cancelar</button>
                <button class="btn btn-danger" id="modalConfirmarPagoAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/api.js"></script>
    <script src="../../js/planes_pagos.js"></script>
    <script src="../../js/gestion_pagos.js"></script>
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
