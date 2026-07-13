<?php

define('ROL_REQUERIDO', 1);
require_once '../../seguridad.php';
require_once '../../conexion.php';

$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

$carpetaFisica = __DIR__ . '/uploads/galeria/';
// Ruta ABSOLUTA desde la raíz del sitio: así la imagen se ve igual desde
// index.php (en la raíz) que desde este panel (2 niveles más profundo).
$rutaPublicaBase = '/vistas/Admin/uploads/galeria/';

// BLOQUE AJAX (CRUD)
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    function responderGal($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    function subirImagenGal($campo, $carpetaFisica, $rutaPublicaBase) {
        if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) return null;
        if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) responderGal(false, ['mensaje' => 'Error al subir la imagen.']);
        $permitidos = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $permitidos)) responderGal(false, ['mensaje' => 'Formato de imagen no permitido.']);
        if ($_FILES[$campo]['size'] > 5 * 1024 * 1024) responderGal(false, ['mensaje' => 'La imagen supera los 5MB.']);
        if (!is_dir($carpetaFisica)) mkdir($carpetaFisica, 0775, true);
        $nombreNuevo = 'gal_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $carpetaFisica . $nombreNuevo)) {
            responderGal(false, ['mensaje' => 'No se pudo guardar la imagen.']);
        }
        return $rutaPublicaBase . $nombreNuevo;
    }

    switch ($accion) {
        case 'listar':
            $items = [];
            $res = $conn->query("SELECT id_galeria, imagen_url, descripcion, fecha_subida FROM galeria ORDER BY id_galeria DESC");
            while ($row = $res->fetch_assoc()) $items[] = $row;
            responderGal(true, ['items' => $items]);
            break;

        case 'crear':
            $descripcion = trim($_POST['descripcion'] ?? '');
            if ($descripcion === '') responderGal(false, ['mensaje' => 'La descripción es obligatoria.']);
            $ruta = subirImagenGal('imagen', $carpetaFisica, $rutaPublicaBase);
            $urlManual = trim($_POST['imagen_url_manual'] ?? '');
            if (!$ruta && $urlManual !== '') $ruta = $urlManual;
            if (!$ruta) responderGal(false, ['mensaje' => 'Sube una imagen o indica una URL.']);
            $stmt = $conn->prepare("INSERT INTO galeria (imagen_url, descripcion, fecha_subida) VALUES (?, ?, NOW())");
            $stmt->bind_param('ss', $ruta, $descripcion);
            responderGal($stmt->execute(), ['mensaje' => 'Imagen agregada a la galería.']);
            break;

        case 'editar':
            $id = (int)($_POST['id_galeria'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            if ($id <= 0 || $descripcion === '') responderGal(false, ['mensaje' => 'Datos incompletos.']);
            $ruta = subirImagenGal('imagen', $carpetaFisica, $rutaPublicaBase);
            $urlManual = trim($_POST['imagen_url_manual'] ?? '');
            if (!$ruta && $urlManual !== '') $ruta = $urlManual;
            if ($ruta) {
                $stmt = $conn->prepare("UPDATE galeria SET descripcion=?, imagen_url=? WHERE id_galeria=?");
                $stmt->bind_param('ssi', $descripcion, $ruta, $id);
            } else {
                $stmt = $conn->prepare("UPDATE galeria SET descripcion=? WHERE id_galeria=?");
                $stmt->bind_param('si', $descripcion, $id);
            }
            responderGal($stmt->execute(), ['mensaje' => 'Imagen actualizada.']);
            break;

        case 'eliminar':
            $id = (int)($_POST['id_galeria'] ?? 0);
            if ($id <= 0) responderGal(false, ['mensaje' => 'ID inválido.']);
            $res = $conn->query("SELECT imagen_url FROM galeria WHERE id_galeria=" . $id);
            if ($row = $res->fetch_assoc()) {
                $nombreArchivo = basename($row['imagen_url']);
                if (str_starts_with($nombreArchivo, 'gal_')) {
                    $rutaFisica = $carpetaFisica . $nombreArchivo;
                    if (file_exists($rutaFisica)) {
                        @unlink($rutaFisica);
                    }
                }
            }
            $stmt = $conn->prepare("DELETE FROM galeria WHERE id_galeria=?");
            $stmt->bind_param('i', $id);
            responderGal($stmt->execute(), ['mensaje' => 'Imagen eliminada.']);
            break;

        default:
            responderGal(false, ['mensaje' => 'Acción no reconocida.']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Galería | ServiceCore</title>
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
            <a href="gestion_carrusel.php" class="menu-item">
                <span class="material-symbols-outlined">view_carousel</span>Gestión de Carrusel
            </a>
            <a href="gestion_galeria.php" class="menu-item activo">
                <span class="material-symbols-outlined">photo_library</span>Gestión de Galería
            </a>
            <a href="gestion_planes.php" class="menu-item">
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
        
        <div class="page-header">
            <section class="mb-8">
                <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Galería</h2>
                <p class="text-gray-500 mt-2">Administra las imágenes que se muestran en la sección de funcionalidades del sitio público.</p>
            </section>
            <button id="btnNuevo" class="btn-primary-sc">
                <span class="material-symbols-outlined">add_photo_alternate</span>
                Nueva imagen
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
                <tbody id="tablaGaleria">
                    <tr><td colspan="4"><div class="spinner-sc"></div></td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay-sc" id="modalGaleria">
        <div class="modal-box-sc">
            <div class="modal-title-sc" id="modalGaleriaTitulo">Nueva imagen</div>
            <form id="formGaleria">
                <input type="hidden" name="id_galeria" id="idGaleria">
                <input type="hidden" name="accion" id="accionGaleria" value="crear">
                <div class="mb-4">
                    <label class="form-label-sc">Descripción</label>
                    <input type="text" name="descripcion" id="descripcionGaleria" class="form-input-sc" placeholder="Ej. Panel unificado de tickets" required maxlength="255">
                </div>
                <div class="mb-2">
                    <label class="form-label-sc">Imagen</label>
                    <div class="upload-drop-sc" id="dropGaleria">
                        <span class="material-symbols-outlined text-[#5750ad] text-3xl">cloud_upload</span>
                        <p class="text-sm text-gray-600 mt-1">Haz clic para subir una imagen (JPG, PNG, WEBP · máx 5MB)</p>
                        <input type="file" name="imagen" id="inputImagenGaleria" accept="image/*" class="hidden">
                    </div>
                    <img id="previewGaleria" class="upload-preview-sc" alt="Previsualización">
                </div>
                <div class="mb-4">
                    <label class="form-label-sc">O pega una URL de imagen (opcional)</label>
                    <input type="text" name="imagen_url_manual" id="urlManualGaleria" class="form-input-sc" placeholder="https://...">
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" id="btnCancelarGaleria" class="px-5 py-2.5 rounded-xl font-semibold text-gray-500 hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit" class="btn-primary-sc">
                        <span class="material-symbols-outlined">save</span>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-sc" id="toastGaleria">
        <span class="material-symbols-outlined">check_circle</span>
        <span id="toastGaleriaTexto">Listo</span>
    </div>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <script>
    const URL_AJAX = 'galeria.php?ajax=1';

    const tabla = document.getElementById('tablaGaleria');
    const modal = document.getElementById('modalGaleria');
    const modalTitulo = document.getElementById('modalGaleriaTitulo');
    const form = document.getElementById('formGaleria');
    const inputAccion = document.getElementById('accionGaleria');
    const inputId = document.getElementById('idGaleria');
    const inputDescripcion = document.getElementById('descripcionGaleria');
    const inputArchivo = document.getElementById('inputImagenGaleria');
    const dropZone = document.getElementById('dropGaleria');
    const preview = document.getElementById('previewGaleria');
    const toast = document.getElementById('toastGaleria');
    const toastTexto = document.getElementById('toastGaleriaTexto');

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
    function resolverImagen(ruta) { if (!ruta) return ''; return ruta.startsWith('http') ? ruta : ruta; }

    function renderTabla() {
        if (!cache.length) {
            tabla.innerHTML = `<tr><td colspan="4"><div class="empty-state"><span class="material-symbols-outlined">photo_library</span>Aún no hay imágenes en la galería.</div></td></tr>`;
            return;
        }
        tabla.innerHTML = cache.map(item => `
            <tr>
                <td><img class="thumb-sc" src="${resolverImagen(item.imagen_url)}" alt="${escapeHtml(item.descripcion)}"></td>
                <td class="font-medium text-[#1e1858]">${escapeHtml(item.descripcion)}</td>
                <td class="text-gray-500">${formatearFecha(item.fecha_subida)}</td>
                <td class="text-right pr-6">
                    <div class="flex justify-end gap-2">
                        <button class="btn-icon-sc edit" title="Editar" onclick="abrirEditar(${item.id_galeria})">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                        <button class="btn-icon-sc delete" title="Eliminar" onclick="eliminarItem(${item.id_galeria})">
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
            tabla.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-6">Error al cargar la galería.</td></tr>`;
        }
    }

    function abrirNuevo() {
        form.reset();
        inputAccion.value = 'crear';
        inputId.value = '';
        preview.style.display = 'none';
        modalTitulo.textContent = 'Nueva imagen';
        modal.classList.add('activo');
    }
    window.abrirEditar = function (id) {
        const item = cache.find(i => i.id_galeria == id);
        if (!item) return;
        form.reset();
        inputAccion.value = 'editar';
        inputId.value = item.id_galeria;
        inputDescripcion.value = item.descripcion;
        preview.src = resolverImagen(item.imagen_url);
        preview.style.display = 'block';
        modalTitulo.textContent = 'Editar imagen';
        modal.classList.add('activo');
    };
    window.eliminarItem = async function (id) {
        if (!confirm('¿Eliminar esta imagen de la galería?')) return;
        const fd = new FormData();
        fd.append('accion', 'eliminar');
        fd.append('id_galeria', id);
        const res = await fetch(URL_AJAX, { method: 'POST', body: fd });
        const data = await res.json();
        mostrarToast(data.mensaje, data.ok ? 'exito' : 'error');
        if (data.ok) cargarLista();
    };
    function cerrarModal() { modal.classList.remove('activo'); }

    document.getElementById('btnNuevo').addEventListener('click', abrirNuevo);
    document.getElementById('btnCancelarGaleria').addEventListener('click', cerrarModal);
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
