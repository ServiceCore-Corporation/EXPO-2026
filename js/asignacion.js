(function () {
    'use strict';

    const $ = (sel) => document.querySelector(sel);
    const $all = (sel) => document.querySelectorAll(sel);

    // Agentes ya pre-filtrados desde el servidor (solo los que cubren
    // alguna de las categorías asignadas al supervisor). Ver bloque
    // <script> en 40_asignacion_tickets.php para el origen de este dato.
    const AGENTES = Array.isArray(window.AGENTES_DISPONIBLES) ? window.AGENTES_DISPONIBLES : [];

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
    const modalConfirmar = $('#modalConfirmar');
    let confirmCallback = null;

    function openConfirm(titulo, mensaje, onConfirm) {
        $('#modalConfirmarTitulo').textContent = titulo;
        $('#modalConfirmarMensaje').textContent = mensaje;
        confirmCallback = onConfirm;
        modalConfirmar.classList.add('open');
    }
    function closeConfirm() {
        modalConfirmar.classList.remove('open');
        confirmCallback = null;
    }
    $('#modalConfirmarCerrar').addEventListener('click', closeConfirm);
    $('#modalConfirmarCancelar').addEventListener('click', closeConfirm);
    $('#modalConfirmarAceptar').addEventListener('click', function () {
        const cb = confirmCallback;
        closeConfirm();
        if (typeof cb === 'function') cb();
    });
    modalConfirmar.addEventListener('click', (e) => { if (e.target === modalConfirmar) closeConfirm(); });

    /* ---------- Filtros de la tabla ---------- */
    const filterCategoria = $('#filterCategoria');
    const filterEstado = $('#filterEstado');
    const filterAsignacion = $('#filterAsignacion');
    const buscador = $('#buscadorTickets');
    const emptyState = $('#emptyState');

    function aplicarFiltros() {
        const cat = filterCategoria.value;
        const estado = filterEstado.value;
        const asignacion = filterAsignacion.value;
        const texto = buscador.value.trim().toLowerCase();

        let visibles = 0;

        $all('#ticketsTable tbody tr').forEach((row) => {
            // Nota: esta tabla SOLO contiene tickets ya restringidos a las
            // categorías del supervisor (filtrado en PHP). Estos filtros de
            // cliente son de conveniencia, no la restricción de seguridad.
            const matchCat = !cat || row.dataset.cat === cat;
            const matchEstado = !estado || row.dataset.estado === estado;

            const tieneAgente = !!row.dataset.agenteId;
            const matchAsignacion =
                !asignacion ||
                (asignacion === 'sin' && !tieneAgente) ||
                (asignacion === 'con' && tieneAgente);

            const haystack = (row.dataset.id + ' ' + row.dataset.asunto + ' ' + row.dataset.cliente).toLowerCase();
            const matchTexto = !texto || haystack.includes(texto);

            const mostrar = matchCat && matchEstado && matchAsignacion && matchTexto;
            row.classList.toggle('row-hidden', !mostrar);
            if (mostrar) visibles++;
        });

        emptyState.style.display = visibles === 0 ? 'block' : 'none';
    }

    [filterCategoria, filterEstado, filterAsignacion].forEach((el) => el.addEventListener('change', aplicarFiltros));
    buscador.addEventListener('input', aplicarFiltros);

    /* ---------- KPIs dinámicos ---------- */
    function recalcularKpis() {
        const filas = $all('#ticketsTable tbody tr');
        let total = filas.length;
        let sinAsignar = 0;
        let enProceso = 0;

        filas.forEach((row) => {
            if (!row.dataset.agenteId) sinAsignar++;
            if (row.dataset.estado === 'En proceso') enProceso++;
        });

        const kpis = $all('.kpi h3');
        if (kpis[0]) kpis[0].textContent = total;
        if (kpis[1]) kpis[1].textContent = sinAsignar;
        if (kpis[2]) kpis[2].textContent = enProceso;

        const badgeNotif = $('#btnNotifications small');
        if (badgeNotif) badgeNotif.textContent = sinAsignar;
    }

    /* ---------- Carga de agentes (panel lateral) ---------- */
    function actualizarCargaAgente(agenteId, delta) {
        const badge = document.querySelector(`[data-carga-de="${agenteId}"]`);
        if (!badge) return;
        const actual = parseInt(badge.textContent, 10) || 0;
        const nuevo = Math.max(0, actual + delta);
        badge.textContent = `${nuevo} tickets`;
    }

    /* ---------- Modal de asignación ---------- */
    const modalAsignar = $('#modalAsignar');
    const selectAgente = $('#selectAgente');
    const formAsignar = $('#formAsignar');
    let ticketEnEdicion = null; // referencia a la fila <tr> actual

    function poblarAgentesPara(catKey) {
        selectAgente.innerHTML = '';

        const compatibles = AGENTES.filter((a) => a.estado === 'Activo' && a.categorias.includes(catKey));

        if (compatibles.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No hay agentes disponibles para esta categoría';
            selectAgente.appendChild(opt);
            selectAgente.disabled = true;
            return;
        }

        selectAgente.disabled = false;
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecciona un agente...';
        selectAgente.appendChild(placeholder);

        compatibles.forEach((a) => {
            const opt = document.createElement('option');
            opt.value = a.id;
            opt.textContent = `${a.nombre} (${a.carga} tickets activos)`;
            selectAgente.appendChild(opt);
        });
    }

    function abrirModalAsignar(row) {
        ticketEnEdicion = row;

        $('#asignarTicketId').textContent = row.dataset.id;
        $('#resumenAsunto').textContent = row.dataset.asunto;
        $('#resumenCliente').textContent = row.dataset.cliente;
        $('#resumenCategoria').textContent = row.dataset.cat;
        $('#resumenPrioridad').textContent = row.dataset.prio;

        const agenteActualWrap = $('#resumenAgenteActualWrap');
        if (row.dataset.agenteNombre) {
            agenteActualWrap.style.display = 'flex';
            $('#resumenAgenteActual').textContent = row.dataset.agenteNombre;
        } else {
            agenteActualWrap.style.display = 'none';
        }

        poblarAgentesPara(row.dataset.catkey);
        $('#notaAsignacion').value = '';
        $('#errorAgente').textContent = '';
        selectAgente.closest('.input-icon').classList.remove('invalid');

        modalAsignar.classList.add('open');
    }

    function cerrarModalAsignar() {
        modalAsignar.classList.remove('open');
        ticketEnEdicion = null;
    }

    $all('.btn-asignar').forEach((btn) => {
        btn.addEventListener('click', function () {
            const row = this.closest('tr');
            abrirModalAsignar(row);
        });
    });

    $('#modalAsignarCerrar').addEventListener('click', cerrarModalAsignar);
    $('#btnCancelarAsignar').addEventListener('click', cerrarModalAsignar);
    modalAsignar.addEventListener('click', (e) => { if (e.target === modalAsignar) cerrarModalAsignar(); });

    formAsignar.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!ticketEnEdicion) return;

        const agenteId = selectAgente.value;
        if (!agenteId) {
            selectAgente.closest('.input-icon').classList.add('invalid');
            $('#errorAgente').textContent = 'Selecciona un agente para continuar.';
            return;
        }

        const agenteSeleccionado = AGENTES.find((a) => a.id === agenteId);
        const yaAsignado = !!ticketEnEdicion.dataset.agenteId;
        const agenteAnteriorId = ticketEnEdicion.dataset.agenteId;
        const ticketId = ticketEnEdicion.dataset.id;

        const ejecutarAsignacion = () => {
            // Si había un agente anterior distinto, libera su carga.
            if (yaAsignado && agenteAnteriorId && agenteAnteriorId !== agenteId) {
                actualizarCargaAgente(agenteAnteriorId, -1);
            }
            if (!yaAsignado || agenteAnteriorId !== agenteId) {
                actualizarCargaAgente(agenteId, +1);
            }

            ticketEnEdicion.dataset.agenteId = agenteId;
            ticketEnEdicion.dataset.agenteNombre = agenteSeleccionado.nombre;

            const celdaAgente = ticketEnEdicion.querySelector('[data-agente-cell]');
            celdaAgente.innerHTML = agenteSeleccionado.nombre;

            // Si el ticket estaba pendiente, pasa a "En proceso" al asignarse.
            if (ticketEnEdicion.dataset.estado === 'Pendiente') {
                ticketEnEdicion.dataset.estado = 'En proceso';
                const badgeEstado = ticketEnEdicion.querySelector('[data-estado-badge]');
                badgeEstado.textContent = 'En proceso';
                badgeEstado.className = 'badge badge-blue';
            }

            const btnAccion = ticketEnEdicion.querySelector('.btn-asignar');
            btnAccion.innerHTML = '<span class="material-symbols-outlined">assignment_ind</span> Reasignar';

            recalcularKpis();
            aplicarFiltros();
            cerrarModalAsignar();

            showToast(
                'success',
                yaAsignado ? 'Ticket reasignado' : 'Ticket asignado',
                `${ticketId} se asignó a ${agenteSeleccionado.nombre}.`
            );
        };

        if (yaAsignado && agenteAnteriorId !== agenteId) {
            const nombreAnterior = ticketEnEdicion.dataset.agenteNombre;
            openConfirm(
                'Confirmar reasignación',
                `Este ticket ya está asignado a ${nombreAnterior}. ¿Deseas reasignarlo a ${agenteSeleccionado.nombre}?`,
                ejecutarAsignacion
            );
        } else if (yaAsignado && agenteAnteriorId === agenteId) {
            showToast('info', 'Sin cambios', 'El ticket ya estaba asignado a este agente.');
            cerrarModalAsignar();
        } else {
            ejecutarAsignacion();
        }
    });

    /* ---------- Estado inicial ---------- */
    recalcularKpis();
    aplicarFiltros();

})();
