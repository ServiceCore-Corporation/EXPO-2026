<?php
define('ROL_REQUERIDO', 2);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);
$idUsuarioActual = (int)$_SESSION['usuario_id'];

$idEmpresa = 0;
$stmtEmp = $conn->prepare("SELECT id_empresa FROM usuario WHERE id_usuario = ? LIMIT 1");
$stmtEmp->bind_param('i', $idUsuarioActual);
$stmtEmp->execute();
$rowEmp = $stmtEmp->get_result()->fetch_assoc();
$stmtEmp->close();
if ($rowEmp) $idEmpresa = (int)$rowEmp['id_empresa'];

// ---- Filtro de fechas (opcional) ----
$fDesde = trim($_GET['desde'] ?? '');
$fHasta = trim($_GET['hasta'] ?? '');
$condicionFecha = '';
$paramsFecha = [];
$tiposFecha = '';
if ($fDesde !== '') { $condicionFecha .= ' AND t.fecha_creacion >= ?'; $paramsFecha[] = $fDesde . ' 00:00:00'; $tiposFecha .= 's'; }
if ($fHasta !== '') { $condicionFecha .= ' AND t.fecha_creacion <= ?'; $paramsFecha[] = $fHasta . ' 23:59:59'; $tiposFecha .= 's'; }

function ejecutarConScopeEmpresa($conn, $sqlBase, $idEmpresa, $condicionFecha, $paramsFecha, $tiposFecha) {
    $sql = $sqlBase . $condicionFecha;
    $stmt = $conn->prepare($sql);
    $tipos = 'i' . $tiposFecha;
    $params = array_merge([$idEmpresa], $paramsFecha);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

// ---- KPIs generales ----
$resKpi = ejecutarConScopeEmpresa($conn, "
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN e.nombre = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados,
           SUM(CASE WHEN e.nombre = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes,
           AVG(CASE WHEN e.nombre = 'Cerrado' AND t.fecha_cierre IS NOT NULL AND t.fecha_cierre != '0000-00-00 00:00:00'
                    THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) END) AS horas_prom
    FROM ticket t
    JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
    LEFT JOIN estado e ON e.id_estado = t.id_estado
    WHERE cli.id_empresa = ?
", $idEmpresa, $condicionFecha, $paramsFecha, $tiposFecha)->fetch_assoc();

$kpiTotal      = (int)($resKpi['total'] ?? 0);
$kpiCerrados   = (int)($resKpi['cerrados'] ?? 0);
$kpiPendientes = (int)($resKpi['pendientes'] ?? 0);
$kpiTasaResolucion = $kpiTotal > 0 ? round(($kpiCerrados / $kpiTotal) * 100) : 0;
$horasProm = $resKpi['horas_prom'] !== null ? round((float)$resKpi['horas_prom'], 1) : null;
$kpiTiempoProm = $horasProm !== null ? ($horasProm >= 24 ? round($horasProm / 24, 1) . ' días' : $horasProm . ' hrs') : '—';

// ---- Tickets por estado ----
$porEstado = [];
$res2 = $conn->prepare("
    SELECT e.nombre AS etiqueta, COUNT(*) AS total
    FROM ticket t
    JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
    LEFT JOIN estado e ON e.id_estado = t.id_estado
    WHERE cli.id_empresa = ?" . $condicionFecha . "
    GROUP BY t.id_estado, e.nombre
");
$res2->bind_param('i' . $tiposFecha, ...array_merge([$idEmpresa], $paramsFecha));
$res2->execute();
$r = $res2->get_result();
while ($row = $r->fetch_assoc()) { $porEstado[] = $row; }

// ---- Tickets por prioridad ----
$porPrioridad = [];
$res3 = $conn->prepare("
    SELECT p.nombre AS etiqueta, COUNT(*) AS total
    FROM ticket t
    JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
    LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
    WHERE cli.id_empresa = ?" . $condicionFecha . "
    GROUP BY t.id_prioridad, p.nombre
");
$res3->bind_param('i' . $tiposFecha, ...array_merge([$idEmpresa], $paramsFecha));
$res3->execute();
$r = $res3->get_result();
while ($row = $r->fetch_assoc()) { $porPrioridad[] = $row; }

// ---- Tickets por categoría ----
$porCategoria = [];
$res4 = $conn->prepare("
    SELECT cat.nombre AS etiqueta, COUNT(*) AS total
    FROM ticket t
    JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
    LEFT JOIN categoria cat ON cat.id_categoria = t.id_categoria
    WHERE cli.id_empresa = ?" . $condicionFecha . "
    GROUP BY t.id_categoria, cat.nombre
");
$res4->bind_param('i' . $tiposFecha, ...array_merge([$idEmpresa], $paramsFecha));
$res4->execute();
$r = $res4->get_result();
while ($row = $r->fetch_assoc()) { $porCategoria[] = $row; }

// ---- Rendimiento por agente ----
$porAgente = [];
$res5 = $conn->prepare("
    SELECT ag.nombre AS agente,
           COUNT(*) AS asignados,
           SUM(CASE WHEN e.nombre = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados
    FROM ticket t
    JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
    JOIN usuario ag ON ag.id_usuario = t.id_usuario_agente
    LEFT JOIN estado e ON e.id_estado = t.id_estado
    WHERE cli.id_empresa = ?" . $condicionFecha . "
    GROUP BY t.id_usuario_agente, ag.nombre
    ORDER BY asignados DESC
");
$res5->bind_param('i' . $tiposFecha, ...array_merge([$idEmpresa], $paramsFecha));
$res5->execute();
$r = $res5->get_result();
while ($row = $r->fetch_assoc()) {
    $asignados = (int)$row['asignados'];
    $cerrados = (int)$row['cerrados'];
    $porAgente[] = [
        'agente'    => $row['agente'],
        'asignados' => $asignados,
        'cerrados'  => $cerrados,
        'tasa'      => $asignados > 0 ? round(($cerrados / $asignados) * 100) : 0,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <link rel="stylesheet" href="../../css/asignacion.css">
    <link rel="stylesheet" href="../../css/supervisor_equipo.css">
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
            <a href="asignar_categoria.php" class="menu-item">
                <span class="material-symbols-outlined">sell</span>Asignar Categoría
            </a>
            <a href="gestion_tickets.php" class="menu-item">
                <span class="material-symbols-outlined">confirmation_number</span>Gestión de Tickets
            </a>
            <a href="reportes.php" class="menu-item activo">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>

            <div class="flex-grow"></div>
            <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
                <span class="material-symbols-outlined">logout</span>
                Cerrar Sesión
            </a>
        </nav>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Reportes</h2>
            <p class="text-gray-500 mt-2">Analítica de tickets y desempeño de tu empresa.</p>
        </section>

        <!-- Filtro de fechas -->
        <form method="GET" class="controls-bar" style="align-items:flex-end;">
            <div class="controls-left" style="align-items:flex-end;">
                <div>
                    <label style="display:block; font-size:12px; color:var(--text-muted); margin-bottom:4px;">Desde</label>
                    <input type="date" name="desde" class="input-small" value="<?= htmlspecialchars($fDesde) ?>">
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:var(--text-muted); margin-bottom:4px;">Hasta</label>
                    <input type="date" name="hasta" class="input-small" value="<?= htmlspecialchars($fHasta) ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined">filter_alt</span> Filtrar
                </button>
                <?php if ($fDesde || $fHasta): ?>
                    <a href="reportes.php" class="btn btn-light">
                        <span class="material-symbols-outlined">close</span> Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- KPIs -->
        <section class="kpi-grid">
            <article class="card kpi primary">
                <div class="kpi-icon"><span class="material-symbols-outlined">confirmation_number</span></div>
                <div><p>Total Tickets</p><h3><?= $kpiTotal ?></h3><span>En el periodo</span></div>
            </article>
            <article class="card kpi green">
                <div class="kpi-icon"><span class="material-symbols-outlined">task_alt</span></div>
                <div><p>Tasa de Resolución</p><h3><?= $kpiTasaResolucion ?>%</h3><span><?= $kpiCerrados ?> cerrados</span></div>
            </article>
            <article class="card kpi yellow">
                <div class="kpi-icon"><span class="material-symbols-outlined">pending_actions</span></div>
                <div><p>Pendientes</p><h3><?= $kpiPendientes ?></h3><span>Sin iniciar</span></div>
            </article>
            <article class="card kpi blue">
                <div class="kpi-icon"><span class="material-symbols-outlined">schedule</span></div>
                <div><p>Tiempo Promedio de Cierre</p><h3 style="font-size:1.75rem;"><?= $kpiTiempoProm ?></h3><span>Tickets cerrados</span></div>
            </article>
        </section>

        <!-- Gráficas -->
        <div class="grid-2">
            <article class="card">
                <div class="section-head"><div><p class="eyebrow">Distribución</p><h3>Tickets por Estado</h3></div></div>
                <canvas id="graficaEstadoEmp" height="220"></canvas>
            </article>
            <article class="card">
                <div class="section-head"><div><p class="eyebrow">Distribución</p><h3>Tickets por Prioridad</h3></div></div>
                <canvas id="graficaPrioridadEmp" height="220"></canvas>
            </article>
        </div>

        <article class="card" style="margin-top:24px;">
            <div class="section-head"><div><p class="eyebrow">Distribución</p><h3>Tickets por Categoría</h3></div></div>
            <canvas id="graficaCategoriaEmp" height="120"></canvas>
        </article>

        <!-- Rendimiento por agente -->
        <article class="card" style="margin-top:24px;">
            <div class="section-head"><div><p class="eyebrow">Equipo</p><h3>Rendimiento por Agente</h3></div></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Agente</th><th>Asignados</th><th>Cerrados</th><th>Tasa de resolución</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$porAgente): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:24px;">Sin datos en el periodo seleccionado.</td></tr>
                        <?php else: foreach ($porAgente as $a): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($a['agente']) ?></strong></td>
                                <td><?= $a['asignados'] ?></td>
                                <td><?= $a['cerrados'] ?></td>
                                <td>
                                    <span class="badge <?= $a['tasa'] >= 70 ? 'badge-green' : ($a['tasa'] >= 40 ? 'badge-yellow' : 'badge-red') ?>"><?= $a['tasa'] ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <script>
        const botonUsuario = document.getElementById('botonUsuario');
        const menuUsuario = document.getElementById('menuUsuario');
        botonUsuario.addEventListener('click', () => menuUsuario.classList.toggle('hidden'));
        document.addEventListener('click', (e) => {
            if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target)) menuUsuario.classList.add('hidden');
        });

        const PALETA = ['#5750ad', '#7773eb', '#16a34a', '#e9a23b', '#dc2626', '#3b82f6', '#9ca3af'];

        new Chart(document.getElementById('graficaEstadoEmp'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($porEstado, 'etiqueta'), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{ data: <?= json_encode(array_map('intval', array_column($porEstado, 'total'))) ?>, backgroundColor: PALETA }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('graficaPrioridadEmp'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($porPrioridad, 'etiqueta'), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{ label: 'Tickets', data: <?= json_encode(array_map('intval', array_column($porPrioridad, 'total'))) ?>, backgroundColor: '#5750ad' }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        new Chart(document.getElementById('graficaCategoriaEmp'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($porCategoria, 'etiqueta'), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{ label: 'Tickets', data: <?= json_encode(array_map('intval', array_column($porCategoria, 'total'))) ?>, backgroundColor: '#7773eb' }]
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        window.addEventListener('load', () => {
            document.querySelectorAll('.animar').forEach((el, i) => {
                setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, i * 100);
            });
        });
    </script>
</body>
</html>
