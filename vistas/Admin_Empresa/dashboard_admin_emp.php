<?php
define('ROL_REQUERIDO', 2);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);
$idUsuario     = (int)$_SESSION['usuario_id'];

// Empresa del Admin de Empresa autenticado: todo lo que ve este dashboard
// debe estar limitado a su propia empresa (multi-tenant).
$idEmpresa = 0;
$stmtEmp = $conn->prepare("SELECT id_empresa FROM usuario WHERE id_usuario = ? LIMIT 1");
$stmtEmp->bind_param('i', $idUsuario);
$stmtEmp->execute();
$rowEmp = $stmtEmp->get_result()->fetch_assoc();
$stmtEmp->close();
if ($rowEmp) $idEmpresa = (int)$rowEmp['id_empresa'];

// Tickets de la empresa: los clientes con id_empresa = $idEmpresa son el ancla,
// ya que ticket no tiene id_empresa directo.
$statTotal = 0; $statCerrados = 0; $statPendientes = 0;
$stmtT = $conn->prepare("
    SELECT e.nombre AS estado, COUNT(*) AS total
    FROM ticket t
    JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
    JOIN estado e ON e.id_estado = t.id_estado
    WHERE cli.id_empresa = ?
    GROUP BY t.id_estado, e.nombre
");
$stmtT->bind_param('i', $idEmpresa);
$stmtT->execute();
$resT = $stmtT->get_result();
while ($row = $resT->fetch_assoc()) {
    $statTotal += (int)$row['total'];
    if ($row['estado'] === 'Cerrado') $statCerrados = (int)$row['total'];
    if ($row['estado'] === 'Pendiente') $statPendientes = (int)$row['total'];
}
$stmtT->close();

$ticketsRecientes = [];
$stmtR = $conn->prepare("
    SELECT t.id_ticket, t.titulo, e.nombre AS estado, p.nombre AS prioridad
    FROM ticket t
    JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
    LEFT JOIN estado e ON e.id_estado = t.id_estado
    LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
    WHERE cli.id_empresa = ?
    ORDER BY t.fecha_creacion DESC
    LIMIT 8
");
$stmtR->bind_param('i', $idEmpresa);
$stmtR->execute();
$resR = $stmtR->get_result();
while ($row = $resR->fetch_assoc()) { $ticketsRecientes[] = $row; }
$stmtR->close();

$usuariosPorRol = [];
$stmtU = $conn->prepare("
    SELECT r.nombre AS rol, COUNT(u.id_usuario) AS total
    FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
    WHERE u.id_empresa = ?
    GROUP BY u.id_rol, r.nombre
");
$stmtU->bind_param('i', $idEmpresa);
$stmtU->execute();
$resU = $stmtU->get_result();
while ($row = $resU->fetch_assoc()) { $usuariosPorRol[] = $row; }
$stmtU->close();

function colorEstadoEmp($estado) {
    $mapa = [
        'Pendiente'  => 'bg-yellow-100 text-yellow-700',
        'En proceso' => 'bg-blue-100 text-blue-700',
        'Cerrado'    => 'bg-green-100 text-green-700',
        'Cancelado'  => 'bg-red-100 text-red-700',
    ];
    return $mapa[$estado] ?? 'bg-gray-100 text-gray-700';
}
function colorPrioridadEmp($p) {
    $mapa = ['Alta' => 'text-red-600', 'Media' => 'text-orange-500', 'Baja' => 'text-blue-500'];
    return $mapa[$p] ?? 'text-gray-600';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empresa | ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/dashboard_admin_emp.css">
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
            <a href="dashboard_admin_emp.php" class="menu-item activo">
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
            <a href="reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
            
            <div class="flex-grow"></div>
            <!-- Cerrar sesión -->
            <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
                <span class="material-symbols-outlined">logout</span>
                Cerrar Sesión
            </a>
            
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-4xl font-bold text-[#1e1858]">Resumen General</h2>
                <p class="text-gray-500 mt-2">Métricas y estadísticas del sistema</p>
            </div>
            <button class="boton flex items-center gap-2">
                <span class="material-symbols-outlined">download</span>Exportar Reporte
            </button>
        </section>

        <!-- Tarjetas estadísticas -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="tarjeta animar md:col-span-2">
                <p class="text-gray-500 uppercase text-sm">Total Tickets</p>
                <h3 class="text-5xl font-black mt-2" id="stat-total"><?= $statTotal ?></h3>
            </div>
            <div class="tarjeta animar border-l-4 border-green-500">
                <p class="text-gray-500">Cerrados</p>
                <h3 class="text-3xl font-bold mt-2" id="stat-cerrados"><?= $statCerrados ?></h3>
            </div>
            <div class="tarjeta animar border-l-4 border-yellow-500">
                <p class="text-gray-500">Pendientes</p>
                <h3 class="text-3xl font-bold mt-2" id="stat-pendientes"><?= $statPendientes ?></h3>
            </div>
        </section>

        <!-- Actividad reciente y alertas -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="tarjeta animar lg:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold">Tickets Recientes</h3>
                    <a href="gestion_tickets.php" class="text-sm font-semibold text-[#5750ad] hover:underline">Ver todos</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-sm">
                            <tr>
                                <th class="p-4 text-left">Ticket</th>
                                <th class="p-4 text-left">Título</th>
                                <th class="p-4 text-left">Estado</th>
                                <th class="p-4 text-left">Prioridad</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-tickets">
                            <?php if (!$ticketsRecientes): ?>
                                <tr><td colspan="4" class="p-4 text-center text-gray-400">Sin tickets</td></tr>
                            <?php else: foreach ($ticketsRecientes as $t): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 font-bold">#TK-<?= (int)$t['id_ticket'] ?></td>
                                    <td class="p-4 font-medium"><?= htmlspecialchars($t['titulo']) ?></td>
                                    <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-bold <?= colorEstadoEmp($t['estado']) ?>"><?= htmlspecialchars($t['estado'] ?? '—') ?></span></td>
                                    <td class="p-4 font-bold <?= colorPrioridadEmp($t['prioridad']) ?>"><?= htmlspecialchars($t['prioridad'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tarjeta animar">
                <h3 class="text-xl font-bold mb-4">Usuarios del Sistema</h3>
                <div id="lista-usuarios" class="space-y-3">
                    <?php if (!$usuariosPorRol): ?>
                        <p class="text-gray-400 text-sm">Sin usuarios registrados.</p>
                    <?php else: foreach ($usuariosPorRol as $r): ?>
                        <div class="flex justify-between items-center py-2 border-b last:border-0">
                            <span class="text-sm font-medium"><?= htmlspecialchars($r['rol']) ?></span>
                            <span class="bg-[#5750ad]/10 text-[#5750ad] px-3 py-1 rounded-full text-xs font-bold"><?= (int)$r['total'] ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </section>
    </main>
    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <script src="../../js/dashboard_admin_empresa.js"></script>
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
