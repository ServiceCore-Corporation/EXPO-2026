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

    /* ---------- Filtros ---------- */
    const kpiGrid = $('#kpiGridTk');
    let estadoActivoFiltro = '';

    function aplicarFiltroTk() {
        const texto = $('#buscadorTk').value.trim().toLowerCase();
        const categoria = $('#filterCategoriaTk').value;
        const prioridad = $('#filterPrioridadTk').value;

        let visibles = 0;
        $all('.tk-row').forEach((row) => {
            const matchTexto = !texto || (row.dataset.titulo + ' ' + row.dataset.cliente).toLowerCase().includes(texto);
            const matchEstado = !estadoActivoFiltro || row.dataset.estado === estadoActivoFiltro;
            const matchCategoria = !categoria || row.dataset.categoria === categoria;
            const matchPrioridad = !prioridad || row.dataset.prioridad === prioridad;
            const mostrar = matchTexto && matchEstado && matchCategoria && matchPrioridad;
            row.classList.toggle('row-hidden', !mostrar);
            if (mostrar) visibles++;
        });
        $('#emptyTk').style.display = visibles === 0 ? 'flex' : 'none';
    }

    kpiGrid.addEventListener('click', function (e) {
        const kpi = e.target.closest('.kpi-clickable');
        if (!kpi) return;
        estadoActivoFiltro = kpi.dataset.filterEstado || '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        kpi.classList.add('active');
        aplicarFiltroTk();
    });

    $('#buscadorTk').addEventListener('input', aplicarFiltroTk);
    $('#filterCategoriaTk').addEventListener('change', aplicarFiltroTk);
    $('#filterPrioridadTk').addEventListener('change', aplicarFiltroTk);

    $('#btnLimpiarFiltrosTk').addEventListener('click', function () {
        estadoActivoFiltro = '';
        $('#filterCategoriaTk').value = '';
        $('#filterPrioridadTk').value = '';
        $('#buscadorTk').value = '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        $('.kpi-clickable[data-filter-estado=""]').classList.add('active');
        aplicarFiltroTk();
    });

    /* ---------- Cambiar estado inline ---------- */
    document.addEventListener('change', async function (e) {
        const select = e.target.closest('.select-estado-tk');
        if (!select) return;
        const row = select.closest('.tk-row');
        const idTicket = row.dataset.id;
        const idEstado = select.value;
        const nombreEstado = select.options[select.selectedIndex].text;
        const original = select.dataset.idEstado;

        try {
            const fd = new FormData();
            fd.append('accion', 'cambiar-estado');
            fd.append('id_ticket', idTicket);
            fd.append('id_estado', idEstado);
            const res = await fetch('gestion_tickets.php?ajax=1', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo actualizar el estado.');

            row.dataset.estado = nombreEstado;
            select.dataset.idEstado = idEstado;
            showToast('success', 'Estado actualizado', `El ticket #TK-${String(idTicket).padStart(4, '0')} ahora está "${nombreEstado}".`);
            aplicarFiltroTk();
        } catch (error) {
            select.value = original;
            showToast('error', 'Error', error.message || 'No se pudo actualizar el estado.');
        }
    });

    /* ---------- Modal: asignar agente ---------- */
    const modalAsignarTk = $('#modalAsignarTk');
    let ticketEnAsignacion = null;

    function abrirModalAsignarTk(row) {
        ticketEnAsignacion = row;
        $('#asignarTkTicketId').textContent = `#TK-${String(row.dataset.id).padStart(4, '0')} — ${row.dataset.titulo}`;
        $('#selectAgenteTk').value = row.dataset.agenteId || '';
        $('#errorAgenteTk').textContent = '';
        modalAsignarTk.classList.add('open');
    }
    function cerrarModalAsignarTk() {
        modalAsignarTk.classList.remove('open');
        ticketEnAsignacion = null;
    }
    $('#modalAsignarTkCerrar').addEventListener('click', cerrarModalAsignarTk);
    $('#btnCancelarAsignarTk').addEventListener('click', cerrarModalAsignarTk);
    modalAsignarTk.addEventListener('click', (e) => { if (e.target === modalAsignarTk) cerrarModalAsignarTk(); });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-asignar-tk');
        if (!btn) return;
        const row = btn.closest('.tk-row');
        if (row) abrirModalAsignarTk(row);
    });

    $('#btnConfirmarAsignarTk').addEventListener('click', async function () {
        if (!ticketEnAsignacion) return;
        const idAgente = $('#selectAgenteTk').value;
        $('#errorAgenteTk').textContent = '';
        if (!idAgente) {
            $('#errorAgenteTk').textContent = 'Selecciona un agente.';
            return;
        }

        const btn = this;
        const textoOriginal = btn.innerHTML;
        btn.disabled = true;

        try {
            const fd = new FormData();
            fd.append('accion', 'asignar-agente');
            fd.append('id_ticket', ticketEnAsignacion.dataset.id);
            fd.append('id_agente', idAgente);
            const res = await fetch('gestion_tickets.php?ajax=1', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo asignar el ticket.');

            const nombreAgente = $('#selectAgenteTk').options[$('#selectAgenteTk').selectedIndex].text;
            ticketEnAsignacion.dataset.agenteId = idAgente;
            ticketEnAsignacion.querySelector('[data-cell-agente]').textContent = nombreAgente;

            showToast('success', 'Ticket asignado', `Se asignó a ${nombreAgente}.`);
            cerrarModalAsignarTk();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo asignar el ticket.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        }
    });

    aplicarFiltroTk();

    window.addEventListener('load', () => {
        $all('.animar').forEach((el, i) => {
            setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, i * 100);
        });
    });
})();
