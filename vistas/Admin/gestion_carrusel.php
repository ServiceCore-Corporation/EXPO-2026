<?php
//Gestión de Carrusel 
define('ROL_REQUERIDO', 1);
require_once '../../seguridad.php';
require_once '../../conexion.php';

$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

$carpetaFisica = __DIR__ . '/uploads/carrusel/';
// Ruta ABSOLUTA desde la raíz del sitio: así la imagen se ve igual desde
// index.php (en la raíz) que desde este panel (2 niveles más profundo).
$rutaPublicaBase = '/vistas/Admin/uploads/carrusel/';

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    function responderCar($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    function subirImagenCar($campo, $carpetaFisica, $rutaPublicaBase) {
        if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) return null;
        if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) responderCar(false, ['mensaje' => 'Error al subir la imagen.']);
        $permitidos = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $permitidos)) responderCar(false, ['mensaje' => 'Formato de imagen no permitido.']);
        if ($_FILES[$campo]['size'] > 5 * 1024 * 1024) responderCar(false, ['mensaje' => 'La imagen supera los 5MB.']);
        if (!is_dir($carpetaFisica)) mkdir($carpetaFisica, 0775, true);
        $nombreNuevo = 'car_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $carpetaFisica . $nombreNuevo)) {
            responderCar(false, ['mensaje' => 'No se pudo guardar la imagen.']);
        }
        return $rutaPublicaBase . $nombreNuevo;
    }

    switch ($accion) {
        case 'listar':
            $items = [];
            $res = $conn->query("SELECT id_carrusel, imagen_url, descripcion, fecha_subida FROM carrusel ORDER BY fecha_subida DESC");
            while ($row = $res->fetch_assoc()) $items[] = $row;
            responderCar(true, ['items' => $items]);
            break;

        case 'crear':
            $descripcion = trim($_POST['descripcion'] ?? '');
            if ($descripcion === '') responderCar(false, ['mensaje' => 'La descripción es obligatoria.']);
            $ruta = subirImagenCar('imagen', $carpetaFisica, $rutaPublicaBase);
            $urlManual = trim($_POST['imagen_url_manual'] ?? '');
            if (!$ruta && $urlManual !== '') $ruta = $urlManual;
            if (!$ruta) responderCar(false, ['mensaje' => 'Sube una imagen o indica una URL.']);
            $stmt = $conn->prepare("INSERT INTO carrusel (imagen_url, descripcion, fecha_subida) VALUES (?, ?, NOW())");
            $stmt->bind_param('ss', $ruta, $descripcion);
            responderCar($stmt->execute(), ['mensaje' => 'Slide agregado al carrusel.']);
            break;

        case 'editar':
            $id = (int)($_POST['id_carrusel'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            if ($id <= 0 || $descripcion === '') responderCar(false, ['mensaje' => 'Datos incompletos.']);
            $ruta = subirImagenCar('imagen', $carpetaFisica, $rutaPublicaBase);
            $urlManual = trim($_POST['imagen_url_manual'] ?? '');
            if (!$ruta && $urlManual !== '') $ruta = $urlManual;
            if ($ruta) {
                $stmt = $conn->prepare("UPDATE carrusel SET descripcion=?, imagen_url=? WHERE id_carrusel=?");
                $stmt->bind_param('ssi', $descripcion, $ruta, $id);
            } else {
                $stmt = $conn->prepare("UPDATE carrusel SET descripcion=? WHERE id_carrusel=?");
                $stmt->bind_param('si', $descripcion, $id);
            }
            responderCar($stmt->execute(), ['mensaje' => 'Slide actualizado.']);
            break;

        case 'eliminar':
            $id = (int)($_POST['id_carrusel'] ?? 0);
            if ($id <= 0) responderCar(false, ['mensaje' => 'ID inválido.']);
            $res = $conn->query("SELECT imagen_url FROM carrusel WHERE id_carrusel=" . $id);
            if ($row = $res->fetch_assoc()) {
                // Solo borramos archivos que subimos nosotros (prefijo car_), nunca URLs externas
                $nombreArchivo = basename($row['imagen_url']);
                if (str_starts_with($nombreArchivo, 'car_')) {
                    $rutaFisica = $carpetaFisica . $nombreArchivo;
                    if (file_exists($rutaFisica)) {
                        @unlink($rutaFisica);
                    }
                }
            }
            $stmt = $conn->prepare("DELETE FROM carrusel WHERE id_carrusel=?");
            $stmt->bind_param('i', $id);
            responderCar($stmt->execute(), ['mensaje' => 'Slide eliminado.']);
            break;

        default:
            responderCar(false, ['mensaje' => 'Acción no reconocida.']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Carrusel | ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/gestion.css">
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
            <a href="gestion_carrusel.php" class="menu-item activo">
                <span class="material-symbols-outlined">view_carousel</span>Gestión de Carrusel
            </a>
            <a href="gestion_galeria.php" class="menu-item">
                <span class="material-symbols-outlined">photo_library</span>Gestión de Galería
            </a>
            <a href="gestion_planes.php" class="menu-item">
                <span class="material-symbols-outlined">workspace_premium</span>Planes
            </a>
            <a href="gestion_pagos.php" class="menu-item">
                <span class="material-symbols-outlined">payments</span>Pagos
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial y Auditoría
            </a>
            <a href="reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
        </nav>

        <div class="flex-grow"></div>
        <!-- Cerrar sesión -->
        <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">

        <div class="page-header">
            <section class="mb-8">
                <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Carrusel</h2>
                <p class="text-gray-500 mt-2">Administra las imágenes del carrusel principal de la página de inicio.</p>
            </section>
            <button id="btnNuevo" class="btn-primary-sc">
                <span class="material-symbols-outlined">add_photo_alternate</span>
                Nuevo slide
            </button>
        </div>

        <div class="card-sc overflow-hidden">
            <table class="table-sc">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Descripción</th>
                        <th>Fecha de subida</th>
                        <th class="text-right pr-6">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaCarrusel">
                    <tr><td colspan="4"><div class="spinner-sc"></div></td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay-sc" id="modalCarrusel">
        <div class="modal-box-sc">
            <div class="modal-title-sc" id="modalCarruselTitulo">Nuevo slide</div>
            <form id="formCarrusel">
                <input type="hidden" name="id_carrusel" id="idCarrusel">
                <input type="hidden" name="accion" id="accionCarrusel" value="crear">
                <div class="mb-4">
                    <label class="form-label-sc">Descripción</label>
                    <input type="text" name="descripcion" id="descripcionCarrusel" class="form-input-sc" placeholder="Ej. Bienvenido a ServiceCore" required maxlength="255">
                </div>
                <div class="mb-2">
                    <label class="form-label-sc">Imagen</label>
                    <div class="upload-drop-sc" id="dropCarrusel">
                        <span class="material-symbols-outlined text-[#5750ad] text-3xl">cloud_upload</span>
                        <p class="text-sm text-gray-600 mt-1">Haz clic para subir una imagen (JPG, PNG, WEBP · máx 5MB)</p>
                        <input type="file" name="imagen" id="inputImagenCarrusel" accept="image/*" class="hidden">
                    </div>
                    <img id="previewCarrusel" class="upload-preview-sc" alt="Previsualización">
                </div>
                <div class="mb-4">
                    <label class="form-label-sc">O pega una URL de imagen (opcional)</label>
                    <input type="text" name="imagen_url_manual" id="urlManualCarrusel" class="form-input-sc" placeholder="https://...">
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" id="btnCancelarCarrusel" class="px-5 py-2.5 rounded-xl font-semibold text-gray-500 hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit" class="btn-primary-sc">
                        <span class="material-symbols-outlined">save</span>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-sc" id="toastCarrusel">
        <span class="material-symbols-outlined">check_circle</span>
        <span id="toastCarruselTexto">Listo</span>
    </div>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <script>
    const URL_AJAX = 'carrusel.php?ajax=1';

    const tabla = document.getElementById('tablaCarrusel');
    const modal = document.getElementById('modalCarrusel');
    const modalTitulo = document.getElementById('modalCarruselTitulo');
    const form = document.getElementById('formCarrusel');
    const inputAccion = document.getElementById('accionCarrusel');
    const inputId = document.getElementById('idCarrusel');
    const inputDescripcion = document.getElementById('descripcionCarrusel');
    const inputArchivo = document.getElementById('inputImagenCarrusel');
    const dropZone = document.getElementById('dropCarrusel');
    const preview = document.getElementById('previewCarrusel');
    const toast = document.getElementById('toastCarrusel');
    const toastTexto = document.getElementById('toastCarruselTexto');

    let cache = [];

    function mostrarToast(mensaje, tipo = 'exito') {
        toast.className = 'toast-sc ' + tipo;
        toastTexto.textContent = mensaje;
        toast.querySelector('.material-symbols-outlined').textContent = tipo === 'exito' ? 'check_circle' : 'error';
        requestAnimationFrame(() => toast.classList.add('mostrar'));
        setTimeout(() => toast.classList.remove('mostrar'), 3200);
    }
    function formatearFecha(f) {
        if (!f) return '—';
        const d = new Date(f.replace(' ', 'T'));
        if (isNaN(d)) return f;
        return d.toLocaleDateString('es-GT', {day:'2-digit',month:'short',year:'numeric'}) + ' · ' + d.toLocaleTimeString('es-GT', {hour:'2-digit',minute:'2-digit'});
    }
    function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }
    function resolverImagen(ruta) { return ruta || ''; }

    function renderTabla() {
        if (!cache.length) {
            tabla.innerHTML = `<tr><td colspan="4"><div class="empty-state"><span class="material-symbols-outlined">view_carousel</span>Aún no hay slides en el carrusel.</div></td></tr>`;
            return;
        }
        tabla.innerHTML = cache.map(item => `
            <tr>
                <td><img class="thumb-sc" src="${resolverImagen(item.imagen_url)}" alt="${escapeHtml(item.descripcion)}"></td>
                <td class="font-medium text-[#1e1858]">${escapeHtml(item.descripcion)}</td>
                <td class="text-gray-500">${formatearFecha(item.fecha_subida)}</td>
                <td class="text-right pr-6">
                    <div class="flex justify-end gap-2">
                        <button class="btn-icon-sc edit" title="Editar" onclick="abrirEditar(${item.id_carrusel})">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                        <button class="btn-icon-sc delete" title="Eliminar" onclick="eliminarItem(${item.id_carrusel})">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </div>
                </td>
            </tr>`).join('');
    }

    async function cargarLista() {
        tabla.innerHTML = `<tr><td colspan="4"><div class="spinner-sc"></div></td></tr>`;
        try {
            const res = await fetch(`${URL_AJAX}&accion=listar`);
            const data = await res.json();
            cache = data.items || [];
            renderTabla();
        } catch (e) {
            tabla.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-6">Error al cargar el carrusel.</td></tr>`;
        }
    }

    function abrirNuevo() {
        form.reset();
        inputAccion.value = 'crear';
        inputId.value = '';
        preview.style.display = 'none';
        modalTitulo.textContent = 'Nuevo slide';
        modal.classList.add('activo');
    }
    window.abrirEditar = function (id) {
        const item = cache.find(i => i.id_carrusel == id);
        if (!item) return;
        form.reset();
        inputAccion.value = 'editar';
        inputId.value = item.id_carrusel;
        inputDescripcion.value = item.descripcion;
        preview.src = resolverImagen(item.imagen_url);
        preview.style.display = 'block';
        modalTitulo.textContent = 'Editar slide';
        modal.classList.add('activo');
    };
    window.eliminarItem = async function (id) {
        if (!confirm('¿Eliminar este slide del carrusel?')) return;
        const fd = new FormData();
        fd.append('accion', 'eliminar');
        fd.append('id_carrusel', id);
        const res = await fetch(URL_AJAX, { method: 'POST', body: fd });
        const data = await res.json();
        mostrarToast(data.mensaje, data.ok ? 'exito' : 'error');
        if (data.ok) cargarLista();
    };
    function cerrarModal() { modal.classList.remove('activo'); }

    document.getElementById('btnNuevo').addEventListener('click', abrirNuevo);
    document.getElementById('btnCancelarCarrusel').addEventListener('click', cerrarModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) cerrarModal(); });
    dropZone.addEventListener('click', () => inputArchivo.click());
    inputArchivo.addEventListener('change', () => {
        const file = inputArchivo.files[0];
        if (!file) return;
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            const res = await fetch(URL_AJAX, { method: 'POST', body: fd });
            const data = await res.json();
            mostrarToast(data.mensaje, data.ok ? 'exito' : 'error');
            if (data.ok) { cerrarModal(); cargarLista(); }
        } catch (err) {
            mostrarToast('Error de conexión al guardar.', 'error');
        } finally { btn.disabled = false; }
    });

    cargarLista();
    </script>
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
