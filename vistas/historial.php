<?php
session_start();
define('ROLES_PERMITIDOS', [1, 2, 3, 4, 5]);
require_once '../seguridad.php';
require_once '../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');

function obtenerRutaDashboard($rol)
{
    switch ((int)$rol) {
        case 1:
            return 'Admin/dashboard_admin.php';
        case 2:
            return 'Admin_Empresa/dashboard_admin_emp.php';
        case 3:
            return 'Agente/dashboard_agente.php';
        case 4:
            return 'Supervisor/dashboard_supervisor.php';
        case 5:
            return 'Cliente/dashboard_cliente.php';
        default:
            return '../index.php';
    }
}

$dashboardRuta = obtenerRutaDashboard((int)($_SESSION['id_rol'] ?? 0));

$fUsuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0;
$fTicket  = isset($_GET['id_ticket']) ? (int)$_GET['id_ticket'] : 0;
$fAccion  = trim($_GET['accion'] ?? '');
$fDesde   = trim($_GET['desde'] ?? '');
$fHasta   = trim($_GET['hasta'] ?? '');

$condiciones = [];
$parametros = [];
$tipos = '';

if ($fUsuario > 0) { $condiciones[] = 'h.id_usuario = ?'; $parametros[] = $fUsuario; $tipos .= 'i'; }
if ($fTicket > 0)  { $condiciones[] = 'h.id_ticket = ?';  $parametros[] = $fTicket;  $tipos .= 'i'; }
if ($fAccion !== '') { $condiciones[] = 'h.accion LIKE ?'; $parametros[] = '%' . $fAccion . '%'; $tipos .= 's'; }
if ($fDesde !== '') { $condiciones[] = 'h.fecha >= ?'; $parametros[] = $fDesde . ' 00:00:00'; $tipos .= 's'; }
if ($fHasta !== '') { $condiciones[] = 'h.fecha <= ?'; $parametros[] = $fHasta . ' 23:59:59'; $tipos .= 's'; }

$where = $condiciones ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

$sql = "SELECT h.id_historial, h.id_ticket, h.id_usuario, h.accion, h.campo_modificado,
               h.valor_anterior, h.valor_nuevo, h.fecha,
               u.nombre AS usuario_nombre, u.correo AS usuario_correo,
               t.titulo AS ticket_titulo
        FROM historial h
        LEFT JOIN usuario u ON u.id_usuario = h.id_usuario
        LEFT JOIN ticket t ON t.id_ticket = h.id_ticket
        $where
        ORDER BY h.fecha DESC
        LIMIT 300";

$registros = [];
if ($parametros) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$parametros);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}
while ($row = $res->fetch_assoc()) $registros[] = $row;

$usuarios = [];
$resU = $conn->query("SELECT id_usuario, nombre FROM usuario ORDER BY nombre ASC");
while ($row = $resU->fetch_assoc()) $usuarios[] = $row;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial y Auditoría | ServiceCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/gestion.css">
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
            <img src="../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="flex flex-col flex-1 gap-2">
            <a href="Admin/dashboard_admin.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="Admin/gestion_empresas.php" class="menu-item ">
                <span class="material-symbols-outlined">business</span> Gestion de Empresas
            </a>
            <a href="Admin/gestion_carrusel.php" class="menu-item">
                <span class="material-symbols-outlined">view_carousel</span>Gestión de Carrusel
            </a>
            <a href="Admin/gestion_galeria.php" class="menu-item">
                <span class="material-symbols-outlined">photo_library</span>Gestión de Galería
            </a>
            <a href="Admin/gestion_planes.php" class="menu-item">
                <span class="material-symbols-outlined">workspace_premium</span>Planes
            </a>
            <a href="Admin/gestion_pagos.php" class="menu-item">
                <span class="material-symbols-outlined">payments</span>Pagos
            </a>
            <a href="Admin/reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
            <a href="historial.php" class="menu-item activo">
                <span class="material-symbols-outlined">history</span>Historial y Auditoría
            </a>
        </nav>

        <br><br>
        <!-- Cerrar sesión -->
        <a href="../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">

        <div class="page-header">
            <section class="mb-8">
                <h2 class="text-4xl font-bold text-[#1e1858]">Registro de Historial y Auditoría</h2>
                <p class="text-gray-500 mt-2">Consulta los cambios registrados sobre los tickets: quién los hizo, qué campo cambió y cuándo.</p>
            </section>
    
            <button id="btnExportar" class="btn-primary-sc">
                <span class="material-symbols-outlined">download</span>
                Exportar CSV
            </button>
        </div>

        <div class="card-sc overflow-hidden">

            <form method="GET" class="filtros-sc">
                <div>
                    <label class="form-label-sc">Usuario</label>
                    <select name="id_usuario" class="form-input-sc">
                        <option value="0">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id_usuario'] ?>" <?= $fUsuario == $u['id_usuario'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label-sc"># Ticket</label>
                    <input type="number" name="id_ticket" class="form-input-sc" placeholder="Ej. 14" value="<?= $fTicket ?: '' ?>">
                </div>
                <div>
                    <label class="form-label-sc">Acción</label>
                    <input type="text" name="accion" class="form-input-sc" placeholder="Ej. actualización" value="<?= htmlspecialchars($fAccion) ?>">
                </div>
                <div>
                    <label class="form-label-sc">Desde</label>
                    <input type="date" name="desde" class="form-input-sc" value="<?= htmlspecialchars($fDesde) ?>">
                </div>
                <div>
                    <label class="form-label-sc">Hasta</label>
                    <input type="date" name="hasta" class="form-input-sc" value="<?= htmlspecialchars($fHasta) ?>">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary-sc w-full justify-center">
                        <span class="material-symbols-outlined">filter_alt</span>
                        Filtrar
                    </button>
                    <?php if ($fUsuario || $fTicket || $fAccion || $fDesde || $fHasta): ?>
                        <a href="historial.php" class="btn-icon-sc delete" title="Limpiar filtros">
                            <span class="material-symbols-outlined">close</span>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="table-sc" id="tablaHistorial">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Ticket</th>
                            <th>Acción</th>
                            <th>Campo</th>
                            <th>Valor anterior</th>
                            <th>Valor nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$registros): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <span class="material-symbols-outlined">history_toggle_off</span>
                                        No se encontraron registros con los filtros seleccionados.
                                    </div>
                                </td>
                            </tr>
                        <?php else: foreach ($registros as $r): ?>
                            <tr>
                                <td class="text-gray-500 whitespace-nowrap"><?= date('d M Y · H:i', strtotime($r['fecha'])) ?></td>
                                <td>
                                    <div class="font-medium text-[#1e1858]"><?= htmlspecialchars($r['usuario_nombre'] ?? 'Usuario eliminado') ?></div>
                                    <div class="text-xs text-gray-400"><?= htmlspecialchars($r['usuario_correo'] ?? '') ?></div>
                                </td>
                                <td>
                                    #<?= (int)$r['id_ticket'] ?>
                                    <?php if ($r['ticket_titulo']): ?>
                                        <div class="text-xs text-gray-400"><?= htmlspecialchars($r['ticket_titulo']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $accionLower = mb_strtolower($r['accion']);
                                        $clase = 'badge-otro';
                                        if (str_contains($accionLower, 'crea')) $clase = 'badge-crear';
                                        elseif (str_contains($accionLower, 'edit') || str_contains($accionLower, 'actualiz')) $clase = 'badge-editar';
                                        elseif (str_contains($accionLower, 'elimin') || str_contains($accionLower, 'cierr')) $clase = 'badge-eliminar';
                                    ?>
                                    <span class="badge-sc <?= $clase ?>"><?= htmlspecialchars($r['accion']) ?></span>
                                </td>
                                <td class="text-gray-600"><?= htmlspecialchars($r['campo_modificado']) ?></td>
                                <td class="text-gray-500 max-w-[180px] truncate" title="<?= htmlspecialchars($r['valor_anterior']) ?>"><?= htmlspecialchars($r['valor_anterior']) ?></td>
                                <td class="text-[#1e1858] font-medium max-w-[180px] truncate" title="<?= htmlspecialchars($r['valor_nuevo']) ?>"><?= htmlspecialchars($r['valor_nuevo']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 text-xs text-gray-400 border-t border-[#e4e6fb]">
                Mostrando <?= count($registros) ?> registro(s) — máximo 300 por consulta.
            </div>
        </div>
    </main>
    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <script>
    document.getElementById('btnExportar')?.addEventListener('click', () => {
        const tabla = document.getElementById('tablaHistorial');
        const filas = [...tabla.querySelectorAll('tr')];
        if (filas.length <= 1) { alert('No hay registros para exportar.'); return; }
        const csv = filas.map(fila => [...fila.querySelectorAll('th, td')]
            .map(c => `"${c.innerText.replace(/"/g, '""').replace(/\s+/g, ' ').trim()}"`)
            .join(',')).join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const enlace = document.createElement('a');
        enlace.href = URL.createObjectURL(blob);
        enlace.download = `historial_auditoria_${new Date().toISOString().slice(0,10)}.csv`;
        enlace.click();
    });
    </script>
    <script src="../js/dashboard_admin.js"></script>
</body>
</html>
