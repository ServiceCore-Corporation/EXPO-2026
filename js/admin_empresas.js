/* =========================================================
   admin_empresas.js — ServiceCore Corporation
   Vista: CRUD Admin-Empresas (Super Admin)
   ========================================================= */

(function () {
    'use strict';

    const $ = (sel) => document.querySelector(sel);
    const $all = (sel) => document.querySelectorAll(sel);

    let nextIdSeq = 100;

    /* ---------- Toast ---------- */
    function showToast(type, title, message) {
        const container = $('#toastContainer');
        if (!container) return;
        const icons = { success: 'check_circle', error: 'error', info: 'info' };
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span class="material-symbols-outlined">${icons[type] || 'info'}</span>
            <div><strong>${title}</strong><p>${message}</p></div>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 200);
        }, 4000);
    }

    /* ---------- Sidebar móvil ---------- */
    const sidebar = $('#sidebar');
    const btnSidebar = $('#btnSidebar');
    if (btnSidebar) btnSidebar.addEventListener('click', () => sidebar.classList.toggle('open'));

    /* ---------- Modal de confirmación genérico ---------- */
    const modalConfirmar = $('#modalConfirmarAE');
    let confirmCallback = null;
    function openConfirm(titulo, mensaje, onConfirm) {
        $('#modalConfirmarAETitulo').textContent = titulo;
        $('#modalConfirmarAEMensaje').textContent = mensaje;
        confirmCallback = onConfirm;
        modalConfirmar.classList.add('open');
    }
    function closeConfirm() {
        modalConfirmar.classList.remove('open');
        confirmCallback = null;
    }
    $('#modalConfirmarAECerrar').addEventListener('click', closeConfirm);
    $('#modalConfirmarAECancelar').addEventListener('click', closeConfirm);
    $('#modalConfirmarAEAceptar').addEventListener('click', function () {
        const cb = confirmCallback;
        closeConfirm();
        if (typeof cb === 'function') cb();
    });
    modalConfirmar.addEventListener('click', (e) => { if (e.target === modalConfirmar) closeConfirm(); });

    /* ---------- Filtro por estado vía KPIs + búsqueda ---------- */
    const kpiGrid = $('#kpiGrid');
    let estadoActivoFiltro = '';

    function aplicarFiltro() {
        const texto = $('#buscadorAE').value.trim().toLowerCase();
        const estadoSelect = $('#filterEstadoAE').value;
        const estado = estadoActivoFiltro || estadoSelect;

        let visibles = 0;
        $all('.ae-row').forEach((row) => {
            const matchTexto = !texto || (
                row.dataset.nombre + ' ' + row.dataset.correo + ' ' + row.dataset.empresa
            ).toLowerCase().includes(texto);
            const matchEstado = !estado || row.dataset.estado === estado;
            const mostrar = matchTexto && matchEstado;
            row.classList.toggle('row-hidden', !mostrar);
            if (mostrar) visibles++;
        });

        $('#emptyAE').style.display = visibles === 0 ? 'flex' : 'none';
    }

    kpiGrid.addEventListener('click', function (e) {
        const kpi = e.target.closest('.kpi-clickable');
        if (!kpi) return;

        estadoActivoFiltro = kpi.dataset.filterEstado || '';
        $('#filterEstadoAE').value = estadoActivoFiltro;
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        kpi.classList.add('active');

        aplicarFiltro();
    });

    $('#buscadorAE').addEventListener('input', aplicarFiltro);
    $('#filterEstadoAE').addEventListener('change', function () {
        estadoActivoFiltro = '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        const kpiTotalEl = document.querySelector('.kpi-clickable[data-filter-estado=""]');
        if (!this.value && kpiTotalEl) kpiTotalEl.classList.add('active');
        aplicarFiltro();
    });

    $('#btnLimpiarFiltrosAE').addEventListener('click', function () {
        estadoActivoFiltro = '';
        $('#filterEstadoAE').value = '';
        $('#buscadorAE').value = '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        $('.kpi-clickable[data-filter-estado=""]').classList.add('active');
        aplicarFiltro();
    });

    /* ---------- KPIs / contadores ---------- */
    function recalcularContadores() {
        let total = 0, activos = 0;
        const empresas = new Set();

        $all('.ae-row').forEach((row) => {
            total++;
            if (row.dataset.estado === 'Activo') activos++;
            if (row.dataset.empresa) empresas.add(row.dataset.empresa.trim().toLowerCase());
        });

        $('[data-kpi="total"]').textContent = total;
        $('[data-kpi="activos"]').textContent = activos;
        $('[data-kpi="inactivos"]').textContent = total - activos;
        $('[data-kpi="empresas"]').textContent = empresas.size;
    }

    /* ---------- Modal Admin-Empresa (crear / editar) ---------- */
    const modalAE = $('#modalAE');
    const formAE = $('#formAE');
    let modoEdicion = false;
    let filaEnEdicion = null;

    function limpiarErroresAE() {
        ['Nombre', 'Correo', 'Empresa'].forEach((campo) => {
            $('#errorAE' + campo).textContent = '';
            $('#ae' + campo).closest('.input-icon').classList.remove('invalid');
        });
    }

    function abrirModalNuevo() {
        modoEdicion = false;
        filaEnEdicion = null;
        $('#modalAETitulo').textContent = 'Nuevo Admin-Empresa';
        $('#modalAESub').textContent = 'Completa los datos para registrar un nuevo administrador de empresa.';
        $('#btnGuardarAE').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Admin-Empresa';
        formAE.reset();
        $('#aeId').value = '';
        $('#aeEstado').value = 'Activo';
        limpiarErroresAE();
        modalAE.classList.add('open');
        $('#aeNombre').focus();
    }

    function abrirModalEditar(row) {
        modoEdicion = true;
        filaEnEdicion = row;
        $('#modalAETitulo').textContent = 'Editar Admin-Empresa';
        $('#modalAESub').textContent = 'Actualiza los datos de este administrador de empresa.';
        $('#btnGuardarAE').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Cambios';

        $('#aeId').value = row.dataset.id;
        $('#aeNombre').value = row.dataset.nombre;
        $('#aeCorreo').value = row.dataset.correo;
        $('#aeEmpresa').value = row.dataset.empresa;
        $('#aeTelefono').value = row.dataset.telefono;
        $('#aeEstado').value = row.dataset.estado;

        limpiarErroresAE();
        modalAE.classList.add('open');
        $('#aeNombre').focus();
    }

    function cerrarModalAE() {
        modalAE.classList.remove('open');
        filaEnEdicion = null;
    }

    $('#btnNuevoAE').addEventListener('click', abrirModalNuevo);
    $('#modalAECerrar').addEventListener('click', cerrarModalAE);
    $('#btnCancelarAE').addEventListener('click', cerrarModalAE);
    modalAE.addEventListener('click', (e) => { if (e.target === modalAE) cerrarModalAE(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { cerrarModalAE(); closeConfirm(); }
    });

    function inicialesDe(nombre) {
        return nombre.trim().split(' ').slice(0, 2).map((p) => p[0] || '').join('').toUpperCase();
    }

    function fechaHoy() {
        const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        const d = new Date();
        return `${String(d.getDate()).padStart(2, '0')} ${meses[d.getMonth()]} ${d.getFullYear()}`;
    }

    function crearElementoFila(data) {
        const tr = document.createElement('tr');
        tr.className = 'ae-row';
        tr.dataset.id = data.id;
        tr.dataset.nombre = data.nombre;
        tr.dataset.correo = data.correo;
        tr.dataset.empresa = data.empresa;
        tr.dataset.telefono = data.telefono;
        tr.dataset.estado = data.estado;
        tr.dataset.fecha = data.fecha;

        tr.innerHTML = `
            <td>
                <div class="ae-user">
                    <div class="avatar small accent-purple">${inicialesDe(data.nombre)}</div>
                    <div class="ae-user-id">
                        <strong data-row-nombre>${data.nombre}</strong>
                        <span data-row-correo>${data.correo}</span>
                    </div>
                </div>
            </td>
            <td>
                <span class="empresa-chip">
                    <span class="material-symbols-outlined">domain</span>
                    <span data-row-empresa>${data.empresa}</span>
                </span>
            </td>
            <td><span data-row-telefono>${data.telefono || '—'}</span></td>
            <td><span class="${data.estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray'}" data-row-estado-badge>${data.estado}</span></td>
            <td><span data-row-fecha>${data.fecha}</span></td>
            <td>
                <div class="row-actions">
                    <button class="row-icon-btn" data-action="editar" title="Editar Admin-Empresa">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="row-icon-btn toggle-btn" data-action="toggle-estado" title="Habilitar / deshabilitar">
                        <span class="material-symbols-outlined" data-row-toggle-icon>${data.estado === 'Activo' ? 'toggle_on' : 'toggle_off'}</span>
                    </button>
                </div>
            </td>
        `;
        return tr;
    }

    function actualizarBadgeYToggle(row) {
        const badge = row.querySelector('[data-row-estado-badge]');
        badge.textContent = row.dataset.estado;
        badge.className = row.dataset.estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray';

        const icon = row.querySelector('[data-row-toggle-icon]');
        icon.textContent = row.dataset.estado === 'Activo' ? 'toggle_on' : 'toggle_off';

        const toggleBtn = row.querySelector('.toggle-btn');
        toggleBtn.classList.toggle('is-active', row.dataset.estado === 'Activo');
    }

    function existeCorreoDuplicado(correo, idExcluir) {
        return Array.from($all('.ae-row')).some(
            (row) => row.dataset.correo.toLowerCase() === correo.toLowerCase() && row.dataset.id !== idExcluir
        );
    }

    formAE.addEventListener('submit', function (e) {
        e.preventDefault();
        limpiarErroresAE();

        const id = $('#aeId').value;
        const nombre = $('#aeNombre').value.trim();
        const correo = $('#aeCorreo').value.trim();
        const empresa = $('#aeEmpresa').value.trim();
        const telefono = $('#aeTelefono').value.trim();
        const estado = $('#aeEstado').value;

        let valido = true;

        if (!nombre) {
            $('#aeNombre').closest('.input-icon').classList.add('invalid');
            $('#errorAENombre').textContent = 'El nombre es obligatorio.';
            valido = false;
        }

        const correoOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
        if (!correoOk) {
            $('#aeCorreo').closest('.input-icon').classList.add('invalid');
            $('#errorAECorreo').textContent = 'Ingresa un correo electrónico válido.';
            valido = false;
        } else if (existeCorreoDuplicado(correo, id)) {
            $('#aeCorreo').closest('.input-icon').classList.add('invalid');
            $('#errorAECorreo').textContent = 'Ya existe un Admin-Empresa con este correo.';
            valido = false;
        }

        if (!empresa) {
            $('#aeEmpresa').closest('.input-icon').classList.add('invalid');
            $('#errorAEEmpresa').textContent = 'Indica la empresa que administrará.';
            valido = false;
        }

        if (!valido) return;

        if (modoEdicion && filaEnEdicion) {
            filaEnEdicion.dataset.nombre = nombre;
            filaEnEdicion.dataset.correo = correo;
            filaEnEdicion.dataset.empresa = empresa;
            filaEnEdicion.dataset.telefono = telefono;
            filaEnEdicion.dataset.estado = estado;

            filaEnEdicion.querySelector('[data-row-nombre]').textContent = nombre;
            filaEnEdicion.querySelector('[data-row-correo]').textContent = correo;
            filaEnEdicion.querySelector('[data-row-empresa]').textContent = empresa;
            filaEnEdicion.querySelector('[data-row-telefono]').textContent = telefono || '—';
            filaEnEdicion.querySelector('.avatar').textContent = inicialesDe(nombre);
            actualizarBadgeYToggle(filaEnEdicion);

            showToast('success', 'Admin-Empresa actualizado', `Los datos de ${nombre} se guardaron correctamente.`);
        } else {
            nextIdSeq++;
            const nuevoId = `AE-${String(nextIdSeq).padStart(3, '0')}`;
            const nuevaFila = crearElementoFila({
                id: nuevoId, nombre, correo, empresa, telefono, estado, fecha: fechaHoy()
            });
            $('#tablaAE').prepend(nuevaFila);

            showToast('success', 'Admin-Empresa creado', `${nombre} fue registrado como administrador de ${empresa}.`);
        }

        recalcularContadores();
        aplicarFiltro();
        cerrarModalAE();
    });

    /* ---------- Acciones sobre cada fila (editar / habilitar-deshabilitar) ---------- */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.row-icon-btn');
        if (!btn) return;

        const row = btn.closest('.ae-row');
        const accion = btn.dataset.action;
        const nombre = row.dataset.nombre;

        if (accion === 'editar') {
            abrirModalEditar(row);
        }

        if (accion === 'toggle-estado') {
            const activarA = row.dataset.estado === 'Activo' ? 'Inactivo' : 'Activo';
            const verbo = activarA === 'Activo' ? 'habilitar' : 'deshabilitar';

            openConfirm(
                activarA === 'Activo' ? 'Habilitar Admin-Empresa' : 'Deshabilitar Admin-Empresa',
                `¿Seguro que deseas ${verbo} el acceso de ${nombre}${activarA === 'Inactivo' ? '? Perderá acceso al sistema.' : '?'}`,
                () => {
                    row.dataset.estado = activarA;
                    actualizarBadgeYToggle(row);
                    recalcularContadores();
                    aplicarFiltro();
                    showToast(
                        activarA === 'Activo' ? 'success' : 'info',
                        'Estado actualizado',
                        `${nombre} ahora está ${activarA.toLowerCase()}.`
                    );
                }
            );
        }
    });

    /* ---------- Estado inicial ---------- */
    $all('.row-icon-btn.toggle-btn').forEach((btn) => {
        const row = btn.closest('.ae-row');
        btn.classList.toggle('is-active', row.dataset.estado === 'Activo');
    });
    recalcularContadores();
    aplicarFiltro();

})();
