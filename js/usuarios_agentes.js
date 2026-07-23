(function () {
    'use strict';

    const $ = (sel) => document.querySelector(sel);
    const $all = (sel) => document.querySelectorAll(sel);

    /* ---------- Menú de usuario (header) ---------- */
    const botonUsuario = $('#botonUsuario');
    const menuUsuario = $('#menuUsuario');
    if (botonUsuario && menuUsuario) {
        botonUsuario.addEventListener('click', () => menuUsuario.classList.toggle('hidden'));
        document.addEventListener('click', (e) => {
            if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target)) {
                menuUsuario.classList.add('hidden');
            }
        });
    }

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

    /* ---------- Filtro por estado vía KPIs + búsqueda ---------- */
    const kpiGrid = $('#kpiGrid');
    let estadoActivoFiltro = '';

    function aplicarFiltro() {
        const texto = $('#buscadorAg').value.trim().toLowerCase();
        const estadoSelect = $('#filterEstadoAg').value;
        const estado = estadoActivoFiltro || estadoSelect;

        let visibles = 0;
        $all('.ag-row').forEach((row) => {
            const matchTexto = !texto || (row.dataset.nombre + ' ' + row.dataset.correo).toLowerCase().includes(texto);
            const matchEstado = !estado || row.dataset.estado === estado;
            const mostrar = matchTexto && matchEstado;
            row.classList.toggle('row-hidden', !mostrar);
            if (mostrar) visibles++;
        });

        $('#emptyAg').style.display = visibles === 0 ? 'flex' : 'none';
    }

    kpiGrid.addEventListener('click', function (e) {
        const kpi = e.target.closest('.kpi-clickable');
        if (!kpi) return;
        estadoActivoFiltro = kpi.dataset.filterEstado || '';
        $('#filterEstadoAg').value = estadoActivoFiltro;
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        kpi.classList.add('active');
        aplicarFiltro();
    });

    $('#buscadorAg').addEventListener('input', aplicarFiltro);
    $('#filterEstadoAg').addEventListener('change', function () {
        estadoActivoFiltro = '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        if (!this.value) $('.kpi-clickable[data-filter-estado=""]').classList.add('active');
        aplicarFiltro();
    });

    $('#btnLimpiarFiltrosAg').addEventListener('click', function () {
        estadoActivoFiltro = '';
        $('#filterEstadoAg').value = '';
        $('#buscadorAg').value = '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        $('.kpi-clickable[data-filter-estado=""]').classList.add('active');
        aplicarFiltro();
    });

    /* ---------- KPIs / contadores ---------- */
    function recalcularContadores() {
        let total = 0, activos = 0;
        $all('.ag-row').forEach((row) => {
            total++;
            if (row.dataset.estado === 'Activo') activos++;
        });
        $('[data-kpi="total"]').textContent = total;
        $('[data-kpi="activos"]').textContent = activos;
        $('[data-kpi="inactivos"]').textContent = total - activos;
    }

    /* ---------- Modal crear / editar ---------- */
    const modalAg = $('#modalAg');
    const formAg = $('#formAg');
    let modoEdicion = false;
    let filaEnEdicion = null;

    function limpiarErrores() {
        ['Nombre', 'Correo', 'Password'].forEach((campo) => {
            const err = $('#errorAg' + campo);
            if (err) err.textContent = '';
            $('#ag' + campo)?.closest('.input-icon')?.classList.remove('invalid');
        });
    }

    function abrirModalNuevo() {
        modoEdicion = false;
        filaEnEdicion = null;
        $('#modalAgTitulo').textContent = 'Nuevo Agente';
        $('#modalAgSub').textContent = 'Completa los datos para agregar un nuevo agente a tu equipo.';
        $('#btnGuardarAg').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Agente';
        formAg.reset();
        $('#agId').value = '';
        $('#agEstado').value = 'Activo';
        $('#grupoAgPassword').style.display = '';
        $('#agPassword').setAttribute('required', 'required');
        limpiarErrores();
        modalAg.classList.add('open');
        $('#agNombre').focus();
    }

    function abrirModalEditar(row) {
        modoEdicion = true;
        filaEnEdicion = row;
        $('#modalAgTitulo').textContent = 'Editar Agente';
        $('#modalAgSub').textContent = 'Actualiza los datos de este agente.';
        $('#btnGuardarAg').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Cambios';

        $('#agId').value = row.dataset.id;
        $('#agNombre').value = row.dataset.nombre;
        $('#agCorreo').value = row.dataset.correo;
        $('#agTelefono').value = row.dataset.telefono;
        $('#agEstado').value = row.dataset.estado;

        $('#grupoAgPassword').style.display = 'none';
        $('#agPassword').removeAttribute('required');
        $('#agPassword').value = '';

        limpiarErrores();
        modalAg.classList.add('open');
        $('#agNombre').focus();
    }

    function cerrarModalAg() {
        modalAg.classList.remove('open');
        filaEnEdicion = null;
    }

    $('#btnNuevoAg').addEventListener('click', abrirModalNuevo);
    $('#modalAgCerrar').addEventListener('click', cerrarModalAg);
    $('#btnCancelarAg').addEventListener('click', cerrarModalAg);
    modalAg.addEventListener('click', (e) => { if (e.target === modalAg) cerrarModalAg(); });

    function actualizarBadgeYToggle(row) {
        const badge = row.querySelector('[data-row-estado-badge]');
        badge.textContent = row.dataset.estado;
        badge.className = row.dataset.estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray';
        const icon = row.querySelector('[data-row-toggle-icon]');
        icon.textContent = row.dataset.estado === 'Activo' ? 'toggle_on' : 'toggle_off';
    }

    function existeCorreoDuplicado(correo, idExcluir) {
        return Array.from($all('.ag-row')).some(
            (row) => row.dataset.correo.toLowerCase() === correo.toLowerCase() && row.dataset.id !== idExcluir
        );
    }

    function fechaHoyAg() {
        const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        const d = new Date();
        return `${String(d.getDate()).padStart(2, '0')} ${meses[d.getMonth()]} ${d.getFullYear()}`;
    }

    function inicialesJs(nombre) {
        return nombre.trim().split(' ').slice(0, 2).map(p => p[0] || '').join('').toUpperCase();
    }

    function crearElementoFilaAg(data) {
        const tr = document.createElement('tr');
        tr.className = 'ag-row';
        tr.dataset.id = data.id;
        tr.dataset.nombre = data.nombre;
        tr.dataset.correo = data.correo;
        tr.dataset.telefono = data.telefono;
        tr.dataset.estado = data.estado;
        tr.dataset.fecha = data.fecha;
        tr.innerHTML = `
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="avatar small accent-purple">${inicialesJs(data.nombre)}</div>
                    <div>
                        <strong data-row-nombre>${data.nombre}</strong><br>
                        <span data-row-correo style="color:var(--text-muted);font-size:12px;">${data.correo}</span>
                    </div>
                </div>
            </td>
            <td><span style="color:var(--text-muted);">Todas</span></td>
            <td><span class="carga-badge">0 tickets</span></td>
            <td><span class="${data.estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray'}" data-row-estado-badge>${data.estado}</span></td>
            <td><span data-row-fecha>${data.fecha}</span></td>
            <td>
                <div class="row-actions">
                    <button class="row-icon-btn" data-action="editar" title="Editar agente">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="row-icon-btn toggle-btn" data-action="toggle-estado" title="Agregar / quitar del equipo activo">
                        <span class="material-symbols-outlined" data-row-toggle-icon>${data.estado === 'Activo' ? 'toggle_on' : 'toggle_off'}</span>
                    </button>
                </div>
            </td>
        `;
        return tr;
    }

    formAg.addEventListener('submit', async function (e) {
        e.preventDefault();
        limpiarErrores();

        const id = $('#agId').value;
        const nombre = $('#agNombre').value.trim();
        const correo = $('#agCorreo').value.trim();
        const password = $('#agPassword').value;
        const telefono = $('#agTelefono').value.trim();
        const estado = $('#agEstado').value;

        let valido = true;
        if (!nombre) {
            $('#agNombre').closest('.input-icon').classList.add('invalid');
            $('#errorAgNombre').textContent = 'El nombre es obligatorio.';
            valido = false;
        }
        const correoOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
        if (!correoOk) {
            $('#agCorreo').closest('.input-icon').classList.add('invalid');
            $('#errorAgCorreo').textContent = 'Ingresa un correo electrónico válido.';
            valido = false;
        } else if (existeCorreoDuplicado(correo, id)) {
            $('#agCorreo').closest('.input-icon').classList.add('invalid');
            $('#errorAgCorreo').textContent = 'Ya existe un usuario con este correo.';
            valido = false;
        }
        if (!modoEdicion) {
            if (!password || password.length < 8) {
                $('#agPassword').closest('.input-icon').classList.add('invalid');
                $('#errorAgPassword').textContent = 'La contraseña debe tener al menos 8 caracteres.';
                valido = false;
            }
        }
        if (!valido) return;

        const btnGuardar = $('#btnGuardarAg');
        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.disabled = true;

        try {
            const fd = new FormData();
            fd.append('accion', modoEdicion ? 'editar' : 'crear');
            if (modoEdicion) fd.append('id', id);
            fd.append('nombre', nombre);
            fd.append('correo', correo);
            fd.append('telefono', telefono);
            fd.append('estado', estado);
            if (!modoEdicion) fd.append('password', password);

            const res = await fetch('usuarios_agentes.php?ajax=1', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo guardar el agente.');

            if (modoEdicion && filaEnEdicion) {
                filaEnEdicion.dataset.nombre = nombre;
                filaEnEdicion.dataset.correo = correo;
                filaEnEdicion.dataset.telefono = telefono;
                filaEnEdicion.dataset.estado = estado;
                filaEnEdicion.querySelector('[data-row-nombre]').textContent = nombre;
                filaEnEdicion.querySelector('[data-row-correo]').textContent = correo;
                actualizarBadgeYToggle(filaEnEdicion);
                showToast('success', 'Agente actualizado', `Los datos de ${nombre} se guardaron correctamente.`);
            } else {
                const nuevaFila = crearElementoFilaAg({ id: data.id, nombre, correo, telefono, estado, fecha: fechaHoyAg() });
                $('#tablaAg').prepend(nuevaFila);
                showToast('success', 'Agente agregado', `${nombre} fue agregado a tu equipo.`);
            }

            recalcularContadores();
            aplicarFiltro();
            cerrarModalAg();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo guardar el agente.');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
        }
    });

    /* ---------- Modal de confirmación ---------- */
    const modalConfirmar = $('#modalConfirmarAg');
    let confirmCallback = null;
    function openConfirm(titulo, mensaje, onConfirm) {
        $('#modalConfirmarAgTitulo').textContent = titulo;
        $('#modalConfirmarAgMensaje').textContent = mensaje;
        confirmCallback = onConfirm;
        modalConfirmar.classList.add('open');
    }
    function closeConfirm() {
        modalConfirmar.classList.remove('open');
        confirmCallback = null;
    }
    $('#modalConfirmarAgCerrar').addEventListener('click', closeConfirm);
    $('#modalConfirmarAgCancelar').addEventListener('click', closeConfirm);
    $('#modalConfirmarAgAceptar').addEventListener('click', function () {
        const cb = confirmCallback;
        closeConfirm();
        if (typeof cb === 'function') cb();
    });
    modalConfirmar.addEventListener('click', (e) => { if (e.target === modalConfirmar) closeConfirm(); });

    /* ---------- Acciones de fila ---------- */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.row-icon-btn');
        if (!btn) return;
        const row = btn.closest('.ag-row');
        if (!row) return;
        const accion = btn.dataset.action;
        const nombre = row.dataset.nombre;

        if (accion === 'editar') {
            abrirModalEditar(row);
        }

        if (accion === 'toggle-estado') {
            const activarA = row.dataset.estado === 'Activo' ? 'Inactivo' : 'Activo';
            const verbo = activarA === 'Activo' ? 'reincorporar al equipo' : 'quitar del equipo activo';
            openConfirm(
                activarA === 'Activo' ? 'Reincorporar Agente' : 'Quitar Agente',
                `¿Seguro que deseas ${verbo} a ${nombre}${activarA === 'Inactivo' ? '? Dejará de recibir nuevas asignaciones.' : '?'}`,
                async () => {
                    try {
                        const fd = new FormData();
                        fd.append('accion', 'toggle-estado');
                        fd.append('id', row.dataset.id);
                        fd.append('activo', activarA === 'Activo' ? '1' : '0');

                        const res = await fetch('usuarios_agentes.php?ajax=1', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.mensaje || 'No se pudo actualizar el estado.');

                        row.dataset.estado = activarA;
                        actualizarBadgeYToggle(row);
                        recalcularContadores();
                        aplicarFiltro();
                        showToast(
                            activarA === 'Activo' ? 'success' : 'info',
                            'Equipo actualizado',
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
    recalcularContadores();
    aplicarFiltro();

    window.addEventListener('load', () => {
        $all('.animar').forEach((el, i) => {
            setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, i * 100);
        });
    });
})();
