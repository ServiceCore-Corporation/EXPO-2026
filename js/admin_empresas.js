(function () {
    'use strict';

    const $ = (sel) => document.querySelector(sel);
    const $all = (sel) => document.querySelectorAll(sel);


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
        ['Nombre', 'Correo', 'Password', 'Empresa'].forEach((campo) => {
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
        $('#grupoAEPassword').style.display = '';
        $('#aePassword').setAttribute('required', 'required');
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
        $('#aeEmpresa').value = row.dataset.idEmpresa || '';
        $('#aeTelefono').value = row.dataset.telefono;
        $('#aeEstado').value = row.dataset.estado;

        // Al editar no se pide/cambia la contraseña desde aquí
        $('#grupoAEPassword').style.display = 'none';
        $('#aePassword').removeAttribute('required');
        $('#aePassword').value = '';

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
        tr.dataset.idEmpresa = data.idEmpresa;
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

    formAE.addEventListener('submit', async function (e) {
        e.preventDefault();
        limpiarErroresAE();

        const id = $('#aeId').value;
        const nombre = $('#aeNombre').value.trim();
        const correo = $('#aeCorreo').value.trim();
        const password = $('#aePassword').value;
        const idEmpresaSel = $('#aeEmpresa').value;
        const empresaNombre = $('#aeEmpresa').selectedOptions[0]?.textContent || '';
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

        if (!idEmpresaSel) {
            $('#aeEmpresa').closest('.input-icon').classList.add('invalid');
            $('#errorAEEmpresa').textContent = 'Indica la empresa que administrará.';
            valido = false;
        }

        if (!modoEdicion && (!password || password.length < 8)) {
            $('#aePassword').closest('.input-icon').classList.add('invalid');
            $('#errorAEPassword').textContent = 'La contraseña debe tener al menos 8 caracteres.';
            valido = false;
        }

        if (!valido) return;

        const btnGuardar = $('#btnGuardarAE');
        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.disabled = true;

        try {
            const fd = new FormData();
            fd.append('accion', modoEdicion ? 'editar' : 'crear');
            if (modoEdicion) fd.append('id', id);
            fd.append('nombre', nombre);
            fd.append('correo', correo);
            fd.append('id_empresa', idEmpresaSel);
            fd.append('telefono', telefono);
            fd.append('estado', estado);
            if (!modoEdicion) fd.append('password', password);

            const res = await fetch('gestion_empresas.php?ajax=1', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo guardar.');

            if (modoEdicion && filaEnEdicion) {
                filaEnEdicion.dataset.idEmpresa = idEmpresaSel;
                filaEnEdicion.dataset.nombre = nombre;
                filaEnEdicion.dataset.correo = correo;
                filaEnEdicion.dataset.empresa = empresaNombre;
                filaEnEdicion.dataset.telefono = telefono;
                filaEnEdicion.dataset.estado = estado;

                filaEnEdicion.querySelector('[data-row-nombre]').textContent = nombre;
                filaEnEdicion.querySelector('[data-row-correo]').textContent = correo;
                filaEnEdicion.querySelector('[data-row-empresa]').textContent = empresaNombre;
                filaEnEdicion.querySelector('[data-row-telefono]').textContent = telefono || '—';
                filaEnEdicion.querySelector('.avatar').textContent = inicialesDe(nombre);
                actualizarBadgeYToggle(filaEnEdicion);

                showToast('success', 'Admin-Empresa actualizado', `Los datos de ${nombre} se guardaron correctamente.`);
            } else {
                const nuevaFila = crearElementoFila({
                    id: data.id, idEmpresa: idEmpresaSel, nombre, correo,
                    empresa: empresaNombre, telefono, estado, fecha: fechaHoy()
                });
                $('#tablaAE').prepend(nuevaFila);

                showToast('success', 'Admin-Empresa creado', `${nombre} fue registrado como administrador de ${empresaNombre}.`);
            }

            recalcularContadores();
            aplicarFiltro();
            cerrarModalAE();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo guardar el Admin-Empresa.');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
        }
    });

    /* ---------- Acciones sobre cada fila (editar / habilitar-deshabilitar) ---------- */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.row-icon-btn');
        if (!btn) return;

        const row = btn.closest('.ae-row');
        if (!row) return;
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
                async () => {
                    try {
                        const fd = new FormData();
                        fd.append('accion', 'toggle-estado');
                        fd.append('id', row.dataset.id);
                        fd.append('activo', activarA === 'Activo' ? '1' : '0');

                        const res = await fetch('gestion_empresas.php?ajax=1', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.mensaje || 'No se pudo actualizar el estado.');

                        row.dataset.estado = activarA;
                        actualizarBadgeYToggle(row);
                        recalcularContadores();
                        aplicarFiltro();
                        showToast(
                            activarA === 'Activo' ? 'success' : 'info',
                            'Estado actualizado',
                            `${nombre} ahora está ${activarA.toLowerCase()}.`
                        );
                    } catch (error) {
                        showToast('error', 'Error', error.message || 'No se pudo actualizar el estado.');
                    }
                }
            );
        }
    });

    /* ---------- Estado inicial ---------- */
    $all('#tablaAE .row-icon-btn.toggle-btn').forEach((btn) => {
        const row = btn.closest('.ae-row');
        if (!row) return;
        btn.classList.toggle('is-active', row.dataset.estado === 'Activo');
    });
    recalcularContadores();
    aplicarFiltro();

    /* ================= EMPRESAS (tabla `empresa`) ================= */

    function refrescarSelectEmpresasAE() {
        const select = $('#aeEmpresa');
        if (!select) return;
        const valorActual = select.value;
        const opciones = ['<option value="">Selecciona una empresa</option>'];
        $all('.emp-row').forEach((row) => {
            opciones.push(`<option value="${row.dataset.id}">${row.dataset.nombre}</option>`);
        });
        select.innerHTML = opciones.join('');
        if (valorActual) select.value = valorActual;
    }

    function aplicarFiltroEmp() {
        const texto = ($('#buscadorEmp')?.value || '').trim().toLowerCase();
        let visibles = 0;
        $all('.emp-row').forEach((row) => {
            const mostrar = !texto || (row.dataset.nombre + ' ' + row.dataset.correo).toLowerCase().includes(texto);
            row.classList.toggle('row-hidden', !mostrar);
            if (mostrar) visibles++;
        });
        const empty = $('#emptyEmp');
        if (empty) empty.style.display = visibles === 0 ? 'flex' : 'none';
    }
    $('#buscadorEmp')?.addEventListener('input', aplicarFiltroEmp);

    function recalcularContadoresEmp() {
        let total = 0, activas = 0;
        $all('.emp-row').forEach((row) => {
            total++;
            if (row.dataset.estado === 'Activo') activas++;
        });
        const elTotal = $('[data-kpi-emp="total"]');
        const elActivas = $('[data-kpi-emp="activas"]');
        const elInactivas = $('[data-kpi-emp="inactivas"]');
        if (elTotal) elTotal.textContent = total;
        if (elActivas) elActivas.textContent = activas;
        if (elInactivas) elInactivas.textContent = total - activas;
    }

    const modalEmpresa = $('#modalEmpresa');
    const formEmpresa = $('#formEmpresa');
    let modoEdicionEmp = false;
    let filaEmpEnEdicion = null;

    function limpiarErroresEmp() {
        ['Nombre', 'Correo', 'Telefono'].forEach((campo) => {
            const err = $('#errorEmp' + campo);
            if (err) err.textContent = '';
            $('#emp' + campo)?.closest('.input-icon')?.classList.remove('invalid');
        });
    }

    function abrirModalNuevaEmpresa() {
        modoEdicionEmp = false;
        filaEmpEnEdicion = null;
        $('#modalEmpresaTitulo').textContent = 'Nueva Empresa';
        $('#modalEmpresaSub').textContent = 'Completa los datos para registrar una nueva empresa cliente.';
        $('#btnGuardarEmpresa').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Empresa';
        formEmpresa.reset();
        $('#empId').value = '';
        $('#empEstado').value = 'Activo';
        limpiarErroresEmp();
        modalEmpresa.classList.add('open');
        $('#empNombre').focus();
    }

    function abrirModalEditarEmpresa(row) {
        modoEdicionEmp = true;
        filaEmpEnEdicion = row;
        $('#modalEmpresaTitulo').textContent = 'Editar Empresa';
        $('#modalEmpresaSub').textContent = 'Actualiza los datos de esta empresa.';
        $('#btnGuardarEmpresa').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Cambios';

        $('#empId').value = row.dataset.id;
        $('#empNombre').value = row.dataset.nombre;
        $('#empCorreo').value = row.dataset.correo;
        $('#empTelefono').value = row.dataset.telefono;
        $('#empEstado').value = row.dataset.estado;

        limpiarErroresEmp();
        modalEmpresa.classList.add('open');
        $('#empNombre').focus();
    }

    function cerrarModalEmpresa() {
        modalEmpresa.classList.remove('open');
        filaEmpEnEdicion = null;
    }

    $('#btnNuevaEmpresa')?.addEventListener('click', abrirModalNuevaEmpresa);
    $('#modalEmpresaCerrar')?.addEventListener('click', cerrarModalEmpresa);
    $('#btnCancelarEmpresa')?.addEventListener('click', cerrarModalEmpresa);
    modalEmpresa?.addEventListener('click', (e) => { if (e.target === modalEmpresa) cerrarModalEmpresa(); });

    function actualizarBadgeYToggleEmp(row) {
        const badge = row.querySelector('[data-row-estado-badge]');
        badge.textContent = row.dataset.estado;
        badge.className = row.dataset.estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray';
        const icon = row.querySelector('[data-row-toggle-icon]');
        icon.textContent = row.dataset.estado === 'Activo' ? 'toggle_on' : 'toggle_off';
        row.querySelector('.toggle-btn')?.classList.toggle('is-active', row.dataset.estado === 'Activo');
    }

    function existeNombreEmpresaDuplicado(nombre, idExcluir) {
        return Array.from($all('.emp-row')).some(
            (row) => row.dataset.nombre.toLowerCase() === nombre.toLowerCase() && row.dataset.id !== idExcluir
        );
    }

    function crearElementoFilaEmp(data) {
        const tr = document.createElement('tr');
        tr.className = 'emp-row';
        tr.dataset.id = data.id;
        tr.dataset.nombre = data.nombre;
        tr.dataset.correo = data.correo;
        tr.dataset.telefono = data.telefono;
        tr.dataset.estado = data.estado;
        tr.dataset.totalUsuarios = '0';
        tr.dataset.totalPagos = '0';
        tr.innerHTML = `
            <td>
                <span class="empresa-chip">
                    <span class="material-symbols-outlined">domain</span>
                    <strong data-row-nombre>${data.nombre}</strong>
                </span>
            </td>
            <td><span data-row-correo>${data.correo}</span></td>
            <td><span data-row-telefono>${data.telefono}</span></td>
            <td><span class="${data.estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray'}" data-row-estado-badge>${data.estado}</span></td>
            <td><span data-row-fecha>${data.fecha}</span></td>
            <td>
                <div class="row-actions">
                    <button class="row-icon-btn" data-action="editar-empresa" title="Editar empresa">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="row-icon-btn toggle-btn" data-action="toggle-estado-empresa" title="Activar / desactivar">
                        <span class="material-symbols-outlined" data-row-toggle-icon>${data.estado === 'Activo' ? 'toggle_on' : 'toggle_off'}</span>
                    </button>
                    <button class="row-icon-btn" data-action="eliminar-empresa" title="Eliminar empresa">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </td>
        `;
        return tr;
    }

    function fechaHoyEmp() {
        const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        const d = new Date();
        return `${String(d.getDate()).padStart(2, '0')} ${meses[d.getMonth()]} ${d.getFullYear()}`;
    }

    formEmpresa?.addEventListener('submit', async function (e) {
        e.preventDefault();
        limpiarErroresEmp();

        const id = $('#empId').value;
        const nombre = $('#empNombre').value.trim();
        const correo = $('#empCorreo').value.trim();
        const telefono = $('#empTelefono').value.replace(/\D/g, '');
        const estado = $('#empEstado').value;

        let valido = true;

        if (!nombre) {
            $('#empNombre').closest('.input-icon').classList.add('invalid');
            $('#errorEmpNombre').textContent = 'El nombre de la empresa es obligatorio.';
            valido = false;
        } else if (existeNombreEmpresaDuplicado(nombre, id)) {
            $('#empNombre').closest('.input-icon').classList.add('invalid');
            $('#errorEmpNombre').textContent = 'Ya existe una empresa con este nombre.';
            valido = false;
        }

        const correoOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
        if (!correoOk) {
            $('#empCorreo').closest('.input-icon').classList.add('invalid');
            $('#errorEmpCorreo').textContent = 'Ingresa un correo electrónico válido.';
            valido = false;
        }

        if (!telefono) {
            $('#empTelefono').closest('.input-icon').classList.add('invalid');
            $('#errorEmpTelefono').textContent = 'Ingresa un teléfono válido (solo números).';
            valido = false;
        }

        if (!valido) return;

        const btnGuardar = $('#btnGuardarEmpresa');
        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.disabled = true;

        try {
            const fd = new FormData();
            fd.append('accion', modoEdicionEmp ? 'editar-empresa' : 'crear-empresa');
            if (modoEdicionEmp) fd.append('id', id);
            fd.append('nombre', nombre);
            fd.append('correo', correo);
            fd.append('telefono', telefono);
            fd.append('estado', estado);

            const res = await fetch('gestion_empresas.php?ajax=1', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo guardar la empresa.');

            if (modoEdicionEmp && filaEmpEnEdicion) {
                filaEmpEnEdicion.dataset.nombre = nombre;
                filaEmpEnEdicion.dataset.correo = correo;
                filaEmpEnEdicion.dataset.telefono = telefono;
                filaEmpEnEdicion.dataset.estado = estado;

                filaEmpEnEdicion.querySelector('[data-row-nombre]').textContent = nombre;
                filaEmpEnEdicion.querySelector('[data-row-correo]').textContent = correo;
                filaEmpEnEdicion.querySelector('[data-row-telefono]').textContent = telefono;
                actualizarBadgeYToggleEmp(filaEmpEnEdicion);

                showToast('success', 'Empresa actualizada', `Los datos de ${nombre} se guardaron correctamente.`);
            } else {
                const nuevaFila = crearElementoFilaEmp({
                    id: data.id, nombre, correo, telefono, estado, fecha: fechaHoyEmp()
                });
                $('#tablaEmp').prepend(nuevaFila);
                showToast('success', 'Empresa creada', `${nombre} fue registrada correctamente.`);
            }

            recalcularContadoresEmp();
            aplicarFiltroEmp();
            refrescarSelectEmpresasAE();
            cerrarModalEmpresa();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo guardar la empresa.');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
        }
    });

    /* ---------- Modal de confirmación (empresas) ---------- */
    const modalConfirmarEmp = $('#modalConfirmarEmp');
    let confirmCallbackEmp = null;
    function openConfirmEmp(titulo, mensaje, onConfirm) {
        $('#modalConfirmarEmpTitulo').textContent = titulo;
        $('#modalConfirmarEmpMensaje').textContent = mensaje;
        confirmCallbackEmp = onConfirm;
        modalConfirmarEmp.classList.add('open');
    }
    function closeConfirmEmp() {
        modalConfirmarEmp.classList.remove('open');
        confirmCallbackEmp = null;
    }
    $('#modalConfirmarEmpCerrar')?.addEventListener('click', closeConfirmEmp);
    $('#modalConfirmarEmpCancelar')?.addEventListener('click', closeConfirmEmp);
    $('#modalConfirmarEmpAceptar')?.addEventListener('click', function () {
        const cb = confirmCallbackEmp;
        closeConfirmEmp();
        if (typeof cb === 'function') cb();
    });
    modalConfirmarEmp?.addEventListener('click', (e) => { if (e.target === modalConfirmarEmp) closeConfirmEmp(); });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.row-icon-btn');
        if (!btn) return;
        const row = btn.closest('.emp-row');
        if (!row) return;
        const accion = btn.dataset.action;
        const nombre = row.dataset.nombre;

        if (accion === 'editar-empresa') {
            abrirModalEditarEmpresa(row);
        }

        if (accion === 'toggle-estado-empresa') {
            const activarA = row.dataset.estado === 'Activo' ? 'Inactivo' : 'Activo';
            const verbo = activarA === 'Activo' ? 'activar' : 'desactivar';
            openConfirmEmp(
                activarA === 'Activo' ? 'Activar Empresa' : 'Desactivar Empresa',
                `¿Seguro que deseas ${verbo} la empresa ${nombre}?`,
                async () => {
                    try {
                        const fd = new FormData();
                        fd.append('accion', 'toggle-estado-empresa');
                        fd.append('id', row.dataset.id);
                        fd.append('activo', activarA === 'Activo' ? '1' : '0');
                        const res = await fetch('gestion_empresas.php?ajax=1', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.mensaje || 'No se pudo actualizar el estado.');

                        row.dataset.estado = activarA;
                        actualizarBadgeYToggleEmp(row);
                        recalcularContadoresEmp();
                        aplicarFiltroEmp();
                        showToast(
                            activarA === 'Activo' ? 'success' : 'info',
                            'Estado actualizado',
                            `${nombre} ahora está ${activarA.toLowerCase()}.`
                        );
                    } catch (error) {
                        showToast('error', 'Error', error.message || 'No se pudo actualizar el estado.');
                    }
                }
            );
        }

        if (accion === 'eliminar-empresa') {
            const totalUsuarios = parseInt(row.dataset.totalUsuarios || '0', 10);
            const totalPagos = parseInt(row.dataset.totalPagos || '0', 10);
            let advertencia = `¿Seguro que deseas eliminar la empresa ${nombre}? Esta acción no se puede deshacer.`;
            if (totalUsuarios > 0 || totalPagos > 0) {
                advertencia += ` También se eliminarán ${totalUsuarios} usuario(s) y ${totalPagos} pago(s) asociados a esta empresa.`;
            }
            openConfirmEmp('Eliminar Empresa', advertencia, async () => {
                try {
                    const fd = new FormData();
                    fd.append('accion', 'eliminar-empresa');
                    fd.append('id', row.dataset.id);
                    const res = await fetch('gestion_empresas.php?ajax=1', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.mensaje || 'No se pudo eliminar la empresa.');

                    row.remove();
                    recalcularContadoresEmp();
                    aplicarFiltroEmp();
                    refrescarSelectEmpresasAE();
                    showToast('success', 'Empresa eliminada', `${nombre} fue eliminada correctamente.`);
                } catch (error) {
                    showToast('error', 'Error', error.message || 'No se pudo eliminar la empresa.');
                }
            });
        }
    });

    $all('#tablaEmp .row-icon-btn.toggle-btn').forEach((btn) => {
        const row = btn.closest('.emp-row');
        btn.classList.toggle('is-active', row.dataset.estado === 'Activo');
    });
    recalcularContadoresEmp();
    aplicarFiltroEmp();

})();
