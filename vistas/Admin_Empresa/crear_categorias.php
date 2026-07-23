<?php
define('ROL_REQUERIDO', 2);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

// ---- AJAX ----
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    function responderCatE($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    switch ($accion) {
        case 'crear':
            $nombre = trim($_POST['nombre'] ?? '');
            if ($nombre === '') responderCatE(false, ['mensaje' => 'El nombre de la categoría es obligatorio.']);

            $stmtChk = $conn->prepare("SELECT id_categoria FROM categoria WHERE nombre = ? LIMIT 1");
            $stmtChk->bind_param('s', $nombre);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderCatE(false, ['mensaje' => 'Ya existe una categoría con ese nombre.']);
            }

            $stmt = $conn->prepare("INSERT INTO categoria (nombre) VALUES (?)");
            $stmt->bind_param('s', $nombre);
            $ok = $stmt->execute();
            responderCatE($ok, ['mensaje' => 'Categoría creada correctamente.', 'id' => $conn->insert_id]);
            break;

        case 'editar':
            $id     = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            if ($id <= 0 || $nombre === '') responderCatE(false, ['mensaje' => 'El nombre de la categoría es obligatorio.']);

            $stmtChk = $conn->prepare("SELECT id_categoria FROM categoria WHERE nombre = ? AND id_categoria != ? LIMIT 1");
            $stmtChk->bind_param('si', $nombre, $id);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderCatE(false, ['mensaje' => 'Ya existe otra categoría con ese nombre.']);
            }

            $stmt = $conn->prepare("UPDATE categoria SET nombre = ? WHERE id_categoria = ?");
            $stmt->bind_param('si', $nombre, $id);
            $ok = $stmt->execute();
            responderCatE($ok, ['mensaje' => 'Categoría actualizada correctamente.']);
            break;

        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) responderCatE(false, ['mensaje' => 'ID inválido.']);

            $stmtChk = $conn->prepare("SELECT COUNT(*) AS c FROM ticket WHERE id_categoria = ?");
            $stmtChk->bind_param('i', $id);
            $stmtChk->execute();
            $enUso = (int)$stmtChk->get_result()->fetch_assoc()['c'];
            if ($enUso > 0) {
                responderCatE(false, ['mensaje' => "No se puede eliminar: tiene $enUso ticket(s) asociados a esta categoría."]);
            }

            $stmt = $conn->prepare("DELETE FROM categoria WHERE id_categoria = ?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            responderCatE($ok, ['mensaje' => 'Categoría eliminada correctamente.']);
            break;

        default:
            responderCatE(false, ['mensaje' => 'Acción no reconocida.']);
    }
}

// ---- Categorías reales + estadísticas ----
$categorias = [];
$resCat = $conn->query("
    SELECT c.id_categoria, c.nombre,
           (SELECT COUNT(*) FROM ticket t WHERE t.id_categoria = c.id_categoria) AS tickets,
           (SELECT COUNT(*) FROM asignar_categoria ac WHERE ac.id_categoria = c.id_categoria) AS agentes
    FROM categoria c
    ORDER BY c.nombre ASC
");
while ($row = $resCat->fetch_assoc()) {
    $categorias[] = [
        'id'      => (int)$row['id_categoria'],
        'nombre'  => $row['nombre'],
        'tickets' => (int)$row['tickets'],
        'agentes' => (int)$row['agentes'],
    ];
}

$kpiTotalCat     = count($categorias);
$kpiConTickets   = count(array_filter($categorias, fn($c) => $c['tickets'] > 0));
$kpiSinAgente    = count(array_filter($categorias, fn($c) => $c['agentes'] === 0));
$kpiTicketsTotal = array_sum(array_column($categorias, 'tickets'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Gestión de Categorías — ServiceCore Corporation</title>
<link rel="icon" type="image/png" href="../../img/LogoNav.png">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            <a href="crear_categorias.php" class="menu-item activo">
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

  <!-- MAIN -->
  <main class="contenido ml-64 pt-24 px-8 pb-10">

    <!-- Page heading -->
    <section class="mb-6">
      <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Categorías</h2>
      <p class="text-gray-500 mt-2">
        Crea y administra las categorías que se asignan a los tickets de tu empresa.
      </p>
    </section>

    <!-- KPIs -->
    <section class="kpi-grid">
        <article class="card kpi primary">
            <div class="kpi-icon"><span class="material-symbols-outlined">category</span></div>
            <div>
                <p>Total Categorías</p>
                <h3 data-kpi-cat="total"><?= $kpiTotalCat ?></h3>
                <span>En el sistema</span>
            </div>
        </article>
        <article class="card kpi blue">
            <div class="kpi-icon"><span class="material-symbols-outlined">confirmation_number</span></div>
            <div>
                <p>Tickets categorizados</p>
                <h3 data-kpi-cat="tickets"><?= $kpiTicketsTotal ?></h3>
                <span>En todas las categorías</span>
            </div>
        </article>
        <article class="card kpi yellow">
            <div class="kpi-icon"><span class="material-symbols-outlined">person_off</span></div>
            <div>
                <p>Sin agente asignado</p>
                <h3 data-kpi-cat="sinagente"><?= $kpiSinAgente ?></h3>
                <span>Requieren asignación</span>
            </div>
        </article>
        <article class="card kpi green">
            <div class="kpi-icon"><span class="material-symbols-outlined">trending_up</span></div>
            <div>
                <p>Con actividad</p>
                <h3 data-kpi-cat="activas"><?= $kpiConTickets ?></h3>
                <span>Con al menos 1 ticket</span>
            </div>
        </article>
    </section>

    <!-- Toolbar -->
    <section class="controls-bar">
        <div class="controls-left">
            <div class="search-box">
                <span class="material-symbols-outlined">search</span>
                <input type="search" id="buscadorCat" placeholder="Buscar categoría...">
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <a href="asignar_categoria.php" class="btn btn-light">
                <span class="material-symbols-outlined">sell</span> Ir a Asignar Categoría
            </a>
            <button class="btn btn-primary" id="btnNuevaCat">
                <span class="material-symbols-outlined">add</span> Nueva Categoría
            </button>
        </div>
    </section>

    <!-- Listado -->
    <section class="table-wrap card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Tickets</th>
                    <th>Agentes asignados</th>
                    <th class="th-actions">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaCat">
                <?php foreach ($categorias as $c): ?>
                    <tr class="cat-row" data-id="<?= $c['id'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>" data-tickets="<?= $c['tickets'] ?>">
                        <td>
                            <span class="chip-cat" style="background:var(--primary-soft);">
                                <span class="material-symbols-outlined" style="font-size:16px;">sell</span>
                                <strong data-row-nombre><?= htmlspecialchars($c['nombre']) ?></strong>
                            </span>
                        </td>
                        <td><span data-row-tickets><?= $c['tickets'] ?></span> ticket(s)</td>
                        <td><span data-row-agentes><?= $c['agentes'] ?></span> agente(s)</td>
                        <td>
                            <div class="row-actions">
                                <button class="row-icon-btn" data-action="editar" title="Editar categoría">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button class="row-icon-btn danger" data-action="eliminar" title="Eliminar categoría">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="empty-table" id="emptyCat" style="display:<?= empty($categorias) ? 'flex' : 'none' ?>;">
            <span class="material-symbols-outlined">search_off</span>
            No hay categorías registradas todavía.
        </p>
    </section>

  </main>
  <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
    <p>© 2026 ServiceCore Corporation</p>
  </footer>

<!-- MODAL CREAR / EDITAR -->
<div class="modal" id="modalCat">
    <div class="modal-content">
        <button class="modal-close" id="modalCatCerrar">×</button>
        <div class="modal-icon"><span class="material-symbols-outlined">category</span></div>
        <h3 id="modalCatTitulo">Nueva Categoría</h3>
        <p id="modalCatSub">Completa el nombre de la nueva categoría.</p>

        <form id="formCat" novalidate>
            <input type="hidden" id="catId">
            <div class="form-group" style="text-align:left;">
                <label for="catNombre">Nombre de la categoría</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">sell</span>
                    <input type="text" id="catNombre" placeholder="Ej. Hardware, Software, Redes...">
                </div>
                <small class="field-error" id="errorCatNombre"></small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-light" id="btnCancelarCat">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarCat">
                    <span class="material-symbols-outlined">save</span> Guardar Categoría
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMAR -->
<div class="modal" id="modalConfirmarCat">
    <div class="modal-content">
        <button class="modal-close" id="modalConfirmarCatCerrar">×</button>
        <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
        <h3 id="modalConfirmarCatTitulo">Confirmar acción</h3>
        <p id="modalConfirmarCatMensaje">¿Deseas continuar con esta acción?</p>
        <div class="modal-actions">
            <button class="btn btn-light" id="modalConfirmarCatCancelar">Cancelar</button>
            <button class="btn btn-danger" id="modalConfirmarCatAceptar">Confirmar</button>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="../../js/crear_categorias_emp.js"></script>
</body>
</html>
