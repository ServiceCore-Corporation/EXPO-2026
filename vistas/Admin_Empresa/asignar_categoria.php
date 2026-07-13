<?php
define('ROL_REQUERIDO', 2); // 2 = Admin de Empresa
require_once '../../seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

// Categorías de ejemplo (tabla categoria: id_categoria, nombre)
$categorias = [
    ['id_categoria' => 1, 'nombre' => 'Soporte Técnico'],
    ['id_categoria' => 2, 'nombre' => 'Facturación'],
    ['id_categoria' => 3, 'nombre' => 'Ventas'],
    ['id_categoria' => 4, 'nombre' => 'Recursos Humanos'],
    ['id_categoria' => 5, 'nombre' => 'Infraestructura'],
];

// Supervisores de ejemplo (tabla usuarios filtrada por id_rol = 4)
// 'categorias' representa lo que ya existe en la tabla asignar_categoria para ese supervisor.
$supervisores = [
    ['id' => 1, 'nombre' => 'Carlos Méndez',   'correo' => 'carlos.mendez@servicecore.com',  'activo' => true,  'categorias' => [1, 3]],
    ['id' => 2, 'nombre' => 'Ana López',       'correo' => 'ana.lopez@servicecore.com',      'activo' => true,  'categorias' => []],
    ['id' => 3, 'nombre' => 'Jorge Ramírez',   'correo' => 'jorge.ramirez@servicecore.com',  'activo' => false, 'categorias' => [2]],
    ['id' => 4, 'nombre' => 'Lucía Fernández', 'correo' => 'lucia.fernandez@servicecore.com','activo' => true,  'categorias' => [1, 2, 4]],
    ['id' => 5, 'nombre' => 'Diego Herrera',   'correo' => 'diego.herrera@servicecore.com',  'activo' => true,  'categorias' => []],
];

$kpiTotalSupervisores = count($supervisores);
$kpiTotalCategorias   = count($categorias);
$kpiAsignados  = count(array_filter($supervisores, fn($s) => count($s['categorias']) > 0));
$kpiSinAsignar = $kpiTotalSupervisores - $kpiAsignados;

function inicialesUsuario($nombre) {
    $partes = explode(' ', trim($nombre));
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) { $ini .= mb_substr($p, 0, 1); }
    return mb_strtoupper($ini);
}

function badgeEstadoSupervisor(bool $activo): string {
    return $activo ? 'badge badge-green' : 'badge badge-gray';
}

function badgeCategorias(int $total): string {
    return $total > 0 ? 'badge badge-blue' : 'badge badge-gray';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Categoría — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/asignar_categoria.css">
</head>
<body>
    <!-- ENCABEZADO -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Panel de Empresa</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Admin Empresa</p>
            </div>
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold">
                <?= mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1)) ?>
            </div>
            <div id="menuUsuario" class="hidden absolute right-0 top-14 w-52 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50">
                <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
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

    <!-- MENÚ LATERAL -->
    <aside class="fixed left-0 top-0 w-64 h-screen bg-[#1e1858] text-white p-6 flex flex-col">
        <div class="flex flex-col items-center mb-8">
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="flex flex-col flex-1 gap-2">
            <a href="dashboard_admin_emp.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_usuarios.php" class="menu-item">
                <span class="material-symbols-outlined">group</span>Gestion de Usuarios
            </a>
            <a href="crear_categorias.php" class="menu-item">
                <span class="material-symbols-outlined">category</span>Gestion de Categorías
            </a>
            <a href="asignar_categoria.php" class="menu-item activo">
                <span class="material-symbols-outlined">sell</span>Asignar Categoría
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>

            <div class="flex-grow"></div>
            <!-- Cerrar sesión -->
            <a href="../../logout.php" class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
                <span class="material-symbols-outlined">logout</span>
                Cerrar Sesión
            </a>
        </nav>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10" id="content">

        <!-- Page heading -->
        <section class="mb-6">
            <h2 class="text-4xl font-bold text-[#1e1858]">Asignar Categoría</h2>
            <p class="text-gray-500 mt-2">
                Define qué categorías de tickets puede ver y gestionar cada Supervisor de tu empresa.
            </p>
        </section>

        <!-- KPIs / filtros rápidos -->
        <section class="kpi-grid" id="kpiGrid">
            <article class="card kpi primary kpi-clickable active" data-filter="todos">
                <div class="kpi-icon"><span class="material-symbols-outlined">supervisor_account</span></div>
                <div>
                    <p>Total de supervisores</p>
                    <h3 data-kpi="total"><?= $kpiTotalSupervisores ?></h3>
                    <span>Ver todos</span>
                </div>
            </article>
            <article class="card kpi purple kpi-clickable" data-filter="categorias">
                <div class="kpi-icon"><span class="material-symbols-outlined">category</span></div>
                <div>
                    <p>Total de categorías</p>
                    <h3 data-kpi="categorias"><?= $kpiTotalCategorias ?></h3>
                    <span>Disponibles en tu empresa</span>
                </div>
            </article>
            <article class="card kpi green kpi-clickable" data-filter="asignados">
                <div class="kpi-icon"><span class="material-symbols-outlined">task_alt</span></div>
                <div>
                    <p>Supervisores asignados</p>
                    <h3 data-kpi="asignados"><?= $kpiAsignados ?></h3>
                    <span>Con al menos 1 categoría</span>
                </div>
            </article>
            <article class="card kpi gray kpi-clickable" data-filter="sin-asignar">
                <div class="kpi-icon"><span class="material-symbols-outlined">report</span></div>
                <div>
                    <p>Sin asignar</p>
                    <h3 data-kpi="sin-asignar"><?= $kpiSinAsignar ?></h3>
                    <span>Aún sin categorías</span>
                </div>
            </article>
        </section>

        <!-- Barra de controles -->
        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorSupervisores" placeholder="Buscar por nombre o correo...">
                </div>
                <button class="btn btn-light" id="btnLimpiarFiltros">
                    <span class="material-symbols-outlined">refresh</span> Limpiar filtros
                </button>
            </div>
            <span class="filtro-activo-tag" id="filtroActivoTag">Mostrando: <strong>Todos</strong></span>
        </section>

        <!-- Lista de supervisores -->
        <section class="supervisor-grid" id="supervisorGrid">
            <?php foreach ($supervisores as $s): ?>
                <?php $totalCats = count($s['categorias']); ?>
                <article class="supervisor-card"
                    data-id="<?= $s['id'] ?>"
                    data-nombre="<?= htmlspecialchars($s['nombre']) ?>"
                    data-correo="<?= htmlspecialchars($s['correo']) ?>"
                    data-activo="<?= $s['activo'] ? '1' : '0' ?>"
                    data-categorias="<?= htmlspecialchars(implode(',', $s['categorias'])) ?>"
                    data-total-categorias="<?= $totalCats ?>"
                >
                    <div class="supervisor-card-top">
                        <div class="avatar small accent-blue"><?= htmlspecialchars(inicialesUsuario($s['nombre'])) ?></div>
                        <div class="supervisor-card-id">
                            <strong><?= htmlspecialchars($s['nombre']) ?></strong>
                            <span><?= htmlspecialchars($s['correo']) ?></span>
                        </div>
                    </div>

                    <div class="supervisor-card-badges">
                        <span class="<?= badgeEstadoSupervisor($s['activo']) ?>">
                            <?= $s['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                        <span class="<?= badgeCategorias($totalCats) ?>" data-badge-categorias>
                            <?= $totalCats ?> categoría<?= $totalCats === 1 ? '' : 's' ?>
                        </span>
                    </div>

                    <button class="btn btn-primary btn-full" data-action="asignar-categoria">
                        <span class="material-symbols-outlined">sell</span> Asignar Categoría
                    </button>
                </article>
            <?php endforeach; ?>

            <p class="empty-column" id="emptyState" style="display:<?= empty($supervisores) ? 'block' : 'none' ?>;">
                No se encontraron supervisores que coincidan con los filtros.
            </p>
        </section>
    </main>

    <!-- Modal: asignar categoría -->
    <div class="modal" id="modalAsignarCategoria">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalAsignarCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">sell</span></div>
            <h3>Asignar Categoría</h3>
            <p>Selecciona las categorías de tickets que este supervisor podrá ver y gestionar.</p>

            <div class="modal-supervisor-info" id="modalSupervisorInfo">
                <div class="avatar accent-blue" id="modalSupervisorAvatar">--</div>
                <div class="modal-supervisor-text">
                    <strong id="modalSupervisorNombre">—</strong>
                    <span id="modalSupervisorCorreo">—</span>
                </div>
            </div>

            <form id="formAsignarCategoria" novalidate>
                <input type="hidden" id="asignarIdUsuario">

                <div class="category-checklist" id="categoryChecklist">
                    <?php if (empty($categorias)): ?>
                        <p class="empty-column">No hay categorías registradas todavía.</p>
                    <?php else: ?>
                        <?php foreach ($categorias as $cat): ?>
                            <label class="category-check-item">
                                <input type="checkbox" name="categorias[]" value="<?= (int) $cat['id_categoria'] ?>">
                                <span class="material-symbols-outlined">category</span>
                                <span class="category-check-nombre"><?= htmlspecialchars($cat['nombre']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <small class="field-error" id="errorAsignarCategoria"></small>

                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarAsignar">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarAsignar">
                        <span class="material-symbols-outlined">save</span> Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <script src="../../js/asignar_categoria.js"></script>
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
