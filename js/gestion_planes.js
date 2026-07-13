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
    const modalConfirmar = $('#modalConfirmarPlan');
    let confirmCallback = null;
    function openConfirm(titulo, mensaje, onConfirm) {
        $('#modalConfirmarPlanTitulo').textContent = titulo;
        $('#modalConfirmarPlanMensaje').textContent = mensaje;
        confirmCallback = onConfirm;
        modalConfirmar.classList.add('open');
    }
    function closeConfirm() {
        modalConfirmar.classList.remove('open');
        confirmCallback = null;
    }
    $('#modalConfirmarPlanCerrar').addEventListener('click', closeConfirm);
    $('#modalConfirmarPlanCancelar').addEventListener('click', closeConfirm);
    $('#modalConfirmarPlanAceptar').addEventListener('click', function () {
        const cb = confirmCallback;
        closeConfirm();
        if (typeof cb === 'function') cb();
    });
    modalConfirmar.addEventListener('click', (e) => { if (e.target === modalConfirmar) closeConfirm(); });

    /* ---------- Filtro por estado vía KPIs + búsqueda ---------- */
    const kpiGrid = $('#kpiGrid');
    let estadoActivoFiltro = '';

    function aplicarFiltro() {
        const texto = $('#buscadorPlanes').value.trim().toLowerCase();
        const estadoSelect = $('#filterEstadoPlan').value;
        const estado = estadoActivoFiltro || estadoSelect;

        let visibles = 0;
        $all('.plan-row').forEach((row) => {
            const matchTexto = !texto || row.dataset.nombre.toLowerCase().includes(texto);
            const matchEstado = !estado || row.dataset.estado === estado;
            const mostrar = matchTexto && matchEstado;
            row.classList.toggle('row-hidden', !mostrar);
            if (mostrar) visibles++;
        });

        $('#emptyPlanes').style.display = visibles === 0 ? 'flex' : 'none';
    }

    kpiGrid.addEventListener('click', function (e) {
        const kpi = e.target.closest('.kpi-clickable');
        if (!kpi) return;

        estadoActivoFiltro = kpi.dataset.filterEstado || '';
        $('#filterEstadoPlan').value = estadoActivoFiltro;
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        kpi.classList.add('active');

        aplicarFiltro();
    });

    $('#buscadorPlanes').addEventListener('input', aplicarFiltro);
    $('#filterEstadoPlan').addEventListener('change', function () {
        estadoActivoFiltro = '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        const kpiTotalEl = document.querySelector('.kpi-clickable[data-filter-estado=""]');
        if (!this.value && kpiTotalEl) kpiTotalEl.classList.add('active');
        aplicarFiltro();
    });

    $('#btnLimpiarFiltrosPlan').addEventListener('click', function () {
        estadoActivoFiltro = '';
        $('#filterEstadoPlan').value = '';
        $('#buscadorPlanes').value = '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        $('.kpi-clickable[data-filter-estado=""]').classList.add('active');
        aplicarFiltro();
    });

    /* ---------- KPIs / contadores ---------- */
    function recalcularContadores() {
        let total = 0, activos = 0;

        $all('.plan-row').forEach((row) => {
            total++;
            if (row.dataset.estado === 'Activo') activos++;
        });

        $('[data-kpi="total"]').textContent = total;
        $('[data-kpi="activos"]').textContent = activos;
        $('[data-kpi="inactivos"]').textContent = total - activos;
    }

    /* ---------- Formato ---------- */
    function formatoQuetzales(valor) {
        const num = Number(valor);
        const decimales = Number.isInteger(num) ? 0 : 2;
        return 'Q' + num.toLocaleString('es-GT', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
    }

    function formatoNumero(valor) {
        return Number(valor).toLocaleString('es-GT');
    }

    /* ---------- Modal Plan (crear / editar) ---------- */
    const modalPlan = $('#modalPlan');
    const formPlan = $('#formPlan');
    let modoEdicion = false;
    let filaEnEdicion = null;

    function limpiarErroresPlan() {
        ['Nombre', 'Precio', 'LimiteUsuarios', 'LimiteTickets'].forEach((campo) => {
            $('#errorPlan' + campo).textContent = '';
            $('#plan' + campo).closest('.input-icon').classList.remove('invalid');
        });
    }

    function abrirModalNuevo() {
        modoEdicion = false;
        filaEnEdicion = null;
        $('#modalPlanTitulo').textContent = 'Nuevo Plan';
        $('#modalPlanSub').textContent = 'Completa los datos para registrar un nuevo plan de servicio.';
        $('#btnGuardarPlan').innerHTML = '<span class="material-symbols-outlined">save</span> Crear Plan';
        formPlan.reset();
        $('#planId').value = '';
        $('#planEstado').value = 'Activo';
        limpiarErroresPlan();
        modalPlan.classList.add('open');
        $('#planNombre').focus();
    }

    function abrirModalEditar(row) {
        modoEdicion = true;
        filaEnEdicion = row;
        $('#modalPlanTitulo').textContent = 'Editar Plan';
        $('#modalPlanSub').textContent = 'Actualiza los datos de este plan de servicio.';
        $('#btnGuardarPlan').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Cambios';

        $('#planId').value = row.dataset.id;
        $('#planNombre').value = row.dataset.nombre;
        $('#planPrecio').value = row.dataset.precio;
        $('#planLimiteUsuarios').value = row.dataset.limiteUsuarios;
        $('#planLimiteTickets').value = row.dataset.limiteTickets;
        $('#planEstado').value = row.dataset.estado;

        limpiarErroresPlan();
        modalPlan.classList.add('open');
        $('#planNombre').focus();
    }

    function cerrarModalPlan() {
        modalPlan.classList.remove('open');
        filaEnEdicion = null;
    }

    $('#btnNuevoPlan').addEventListener('click', abrirModalNuevo);
    $('#modalPlanCerrar').addEventListener('click', cerrarModalPlan);
    $('#btnCancelarPlan').addEventListener('click', cerrarModalPlan);
    modalPlan.addEventListener('click', (e) => { if (e.target === modalPlan) cerrarModalPlan(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { cerrarModalPlan(); closeConfirm(); }
    });

    function crearElementoFila(data) {
        const tr = document.createElement('tr');
        tr.className = 'plan-row';
        tr.dataset.id = data.id;
        tr.dataset.nombre = data.nombre;
        tr.dataset.precio = data.precio;
        tr.dataset.limiteUsuarios = data.limite_usuarios;
        tr.dataset.limiteTickets = data.limite_tickets;
        tr.dataset.estado = data.estado;

        tr.innerHTML = `
            <td>
                <span class="plan-nombre">
                    <span class="material-symbols-outlined">workspace_premium</span>
                    <strong data-row-nombre>${data.nombre}</strong>
                </span>
            </td>
            <td><span data-row-precio>${formatoQuetzales(data.precio)}</span></td>
            <td><span data-row-usuarios>${formatoNumero(data.limite_usuarios)}</span></td>
            <td><span data-row-tickets>${formatoNumero(data.limite_tickets)}</span></td>
            <td><span class="${data.estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray'}" data-row-estado-badge>${data.estado}</span></td>
            <td>
                <div class="row-actions">
                    <button class="row-icon-btn" data-action="editar" title="Editar plan">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="row-icon-btn toggle-btn" data-action="toggle-estado" title="Activar / desactivar plan">
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

    function existeNombreDuplicado(nombre, idExcluir) {
        return Array.from($all('.plan-row')).some(
            (row) => row.dataset.nombre.toLowerCase() === nombre.toLowerCase() && row.dataset.id !== idExcluir
        );
    }

    formPlan.addEventListener('submit', async function (e) {
        e.preventDefault();
        limpiarErroresPlan();

        const id = $('#planId').value;
        const nombre = $('#planNombre').value.trim();
        const precio = $('#planPrecio').value;
        const limiteUsuarios = $('#planLimiteUsuarios').value;
        const limiteTickets = $('#planLimiteTickets').value;
        const estado = $('#planEstado').value;

        let valido = true;

        if (!nombre) {
            $('#planNombre').closest('.input-icon').classList.add('invalid');
            $('#errorPlanNombre').textContent = 'El nombre del plan es obligatorio.';
            valido = false;
        } else if (existeNombreDuplicado(nombre, id)) {
            $('#planNombre').closest('.input-icon').classList.add('invalid');
            $('#errorPlanNombre').textContent = 'Ya existe un plan con este nombre.';
            valido = false;
        }

        if (!precio || Number(precio) <= 0) {
            $('#planPrecio').closest('.input-icon').classList.add('invalid');
            $('#errorPlanPrecio').textContent = 'Ingresa un precio válido.';
            valido = false;
        }

        if (!limiteUsuarios || Number(limiteUsuarios) <= 0) {
            $('#planLimiteUsuarios').closest('.input-icon').classList.add('invalid');
            $('#errorPlanLimiteUsuarios').textContent = 'Ingresa un límite de usuarios válido.';
            valido = false;
        }

        if (!limiteTickets || Number(limiteTickets) <= 0) {
            $('#planLimiteTickets').closest('.input-icon').classList.add('invalid');
            $('#errorPlanLimiteTickets').textContent = 'Ingresa un límite de tickets válido.';
            valido = false;
        }

        if (!valido) return;

        const payload = {
            nombre,
            precio: Number(precio),
            limite_usuarios: Number(limiteUsuarios),
            limite_tickets: Number(limiteTickets),
            activo: estado === 'Activo' ? 1 : 0
        };

        const btnGuardar = $('#btnGuardarPlan');
        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.disabled = true;

        try {
            if (modoEdicion && filaEnEdicion) {
                await actualizarPlan(filaEnEdicion.dataset.id, payload);

                filaEnEdicion.dataset.nombre = nombre;
                filaEnEdicion.dataset.precio = precio;
                filaEnEdicion.dataset.limiteUsuarios = limiteUsuarios;
                filaEnEdicion.dataset.limiteTickets = limiteTickets;
                filaEnEdicion.dataset.estado = estado;

                filaEnEdicion.querySelector('[data-row-nombre]').textContent = nombre;
                filaEnEdicion.querySelector('[data-row-precio]').textContent = formatoQuetzales(precio);
                filaEnEdicion.querySelector('[data-row-usuarios]').textContent = formatoNumero(limiteUsuarios);
                filaEnEdicion.querySelector('[data-row-tickets]').textContent = formatoNumero(limiteTickets);
                actualizarBadgeYToggle(filaEnEdicion);

                showToast('success', 'Plan actualizado', `Los datos de ${nombre} se guardaron correctamente.`);
            } else {
                const respuesta = await crearPlan(payload);
                const nuevaFila = crearElementoFila({
                    id: respuesta.id_plan, nombre, precio, limite_usuarios: limiteUsuarios,
                    limite_tickets: limiteTickets, estado
                });
                $('#tablaPlanes').prepend(nuevaFila);

                showToast('success', 'Plan creado', `El plan ${nombre} fue registrado correctamente.`);
            }

            recalcularContadores();
            aplicarFiltro();
            cerrarModalPlan();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo guardar el plan.');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
        }
    });

    /* ---------- Acciones sobre cada fila (editar / activar-desactivar) ---------- */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.row-icon-btn');
        if (!btn) return;

        const row = btn.closest('.plan-row');
        const accion = btn.dataset.action;
        const nombre = row.dataset.nombre;

        if (accion === 'editar') {
            abrirModalEditar(row);
        }

        if (accion === 'toggle-estado') {
            const activarA = row.dataset.estado === 'Activo' ? 'Inactivo' : 'Activo';
            const verbo = activarA === 'Activo' ? 'activar' : 'desactivar';

            openConfirm(
                activarA === 'Activo' ? 'Activar Plan' : 'Desactivar Plan',
                `¿Seguro que deseas ${verbo} el plan ${nombre}${activarA === 'Inactivo' ? '? Las empresas no podrán contratarlo mientras esté inactivo.' : '?'}`,
                async () => {
                    try {
                        await cambiarEstadoPlan(row.dataset.id, activarA === 'Activo' ? 1 : 0);

                        row.dataset.estado = activarA;
                        actualizarBadgeYToggle(row);
                        recalcularContadores();
                        aplicarFiltro();
                        showToast(
                            activarA === 'Activo' ? 'success' : 'info',
                            'Estado actualizado',
                            `El plan ${nombre} ahora está ${activarA.toLowerCase()}.`
                        );
                    } catch (error) {
                        showToast('error', 'Error', error.message || 'No se pudo actualizar el estado del plan.');
                    }
                }
            );
        }
    });

    /* ---------- Estado inicial ---------- */
    $all('.row-icon-btn.toggle-btn').forEach((btn) => {
        const row = btn.closest('.plan-row');
        btn.classList.toggle('is-active', row.dataset.estado === 'Activo');
    });
    recalcularContadores();
    aplicarFiltro();

})();
