(function () {
    'use strict';

    const $ = (sel) => document.querySelector(sel);
    const $all = (sel) => document.querySelectorAll(sel);

    
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

    
    let pagosCache = [];
    let estadoActivoFiltro = '';

    
    function actualizarKpis(lista) {
        const total = lista
            .filter(p => parseInt(p.estado) === 1)
            .reduce((acc, p) => acc + parseFloat(p.monto), 0);
        const pagados = lista.filter(p => parseInt(p.estado) === 1).length;
        const pendientes = lista.filter(p => parseInt(p.estado) === 0).length;

        const elTotal = $('[data-kpi="total"]');
        const elPagados = $('[data-kpi="pagados"]');
        const elPendientes = $('[data-kpi="pendientes"]');

        if (elTotal) { elTotal.textContent = 'Q' + total.toFixed(2); elTotal.classList.remove('kpi-loading'); }
        if (elPagados) { elPagados.textContent = pagados; elPagados.classList.remove('kpi-loading'); }
        if (elPendientes) { elPendientes.textContent = pendientes; elPendientes.classList.remove('kpi-loading'); }
    }

    
    function aplicarFiltro() {
        const texto = ($('#buscadorPagos').value || '').trim().toLowerCase();
        const estadoSelect = $('#filterEstadoPago').value;
        const estado = estadoActivoFiltro !== '' ? estadoActivoFiltro : estadoSelect;

        let visibles = 0;
        $all('.pago-row').forEach((row) => {
            const empresa = (row.querySelector('strong')?.textContent || '').toLowerCase();
            const matchTexto = !texto || empresa.includes(texto);
            const matchEstado = estado === '' || row.dataset.estado === String(estado);
            const mostrar = matchTexto && matchEstado;
            row.classList.toggle('row-hidden', !mostrar);
            if (mostrar) visibles++;
        });

        const empty = $('#emptyPagos');
        if (empty) empty.style.display = visibles === 0 ? 'flex' : 'none';
    }

    
    async function cargarPagos() {
        try {
            pagosCache = await obtenerPagos();
            renderizarTablaPagos(pagosCache, 'cuerpo-pagos');
            actualizarKpis(pagosCache);
            aplicarFiltro();
        } catch (error) {
            $('#cuerpo-pagos').innerHTML = `<tr><td colspan="6" class="sin-datos">No se pudieron cargar los pagos.</td></tr>`;
            showToast('error', 'Error', error.message || 'No se pudo conectar con el servidor.');
        }
    }

    
    const kpiGrid = $('#kpiGrid');
    if (kpiGrid) {
        kpiGrid.addEventListener('click', function (e) {
            const kpi = e.target.closest('.kpi-clickable');
            if (!kpi) return;
            estadoActivoFiltro = kpi.dataset.filterEstado ?? '';
            $('#filterEstadoPago').value = estadoActivoFiltro;
            $all('.kpi-clickable').forEach(k => k.classList.remove('active'));
            kpi.classList.add('active');
            aplicarFiltro();
        });
    }

    $('#buscadorPagos')?.addEventListener('input', aplicarFiltro);
    $('#filterEstadoPago')?.addEventListener('change', function () {
        estadoActivoFiltro = '';
        aplicarFiltro();
    });
    $('#btnLimpiarFiltrosPago')?.addEventListener('click', function () {
        $('#buscadorPagos').value = '';
        $('#filterEstadoPago').value = '';
        estadoActivoFiltro = '';
        $all('.kpi-clickable').forEach(k => k.classList.remove('active'));
        $('.kpi-clickable[data-filter-estado=""]')?.classList.add('active');
        aplicarFiltro();
    });

    
    const modalPago = $('#modalPago');
    const formPago = $('#formPago');

    function abrirModalNuevo() {
        formPago.reset();
        $('#pagoId').value = '';
        $('#pagoEstado').value = '1';
        $('#pagoFecha').value = new Date().toISOString().slice(0, 10);
        $('#modalPagoTitulo').textContent = 'Registrar Pago';
        $('#btnGuardarPago').innerHTML = '<span class="material-symbols-outlined">save</span> Registrar Pago';
        $all('.field-error').forEach(el => el.textContent = '');
        modalPago.classList.add('open');
    }

    async function abrirModalEditar(idPago) {
        try {
            const pago = await obtenerPago(idPago);
            $('#pagoId').value = pago.id_pago;
            $('#pagoEmpresa').value = pago.id_empresa;
            $('#pagoMonto').value = pago.monto;
            $('#pagoEstado').value = pago.estado;
            $('#pagoMetodo').value = pago.metodo_pago;
            $('#pagoFecha').value = (pago.fecha_pago || '').slice(0, 10);
            $('#modalPagoTitulo').textContent = 'Editar Pago';
            $('#btnGuardarPago').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Cambios';
            $all('.field-error').forEach(el => el.textContent = '');
            modalPago.classList.add('open');
        } catch (error) {
            showToast('error', 'Error', 'No se pudo cargar el pago.');
        }
    }

    function cerrarModalPago() {
        modalPago.classList.remove('open');
    }

    $('#btnNuevoPago')?.addEventListener('click', abrirModalNuevo);
    $('#modalPagoCerrar')?.addEventListener('click', cerrarModalPago);
    $('#btnCancelarPago')?.addEventListener('click', cerrarModalPago);
    modalPago?.addEventListener('click', (e) => { if (e.target === modalPago) cerrarModalPago(); });

    formPago?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const idPago = $('#pagoId').value;
        const idEmpresa = $('#pagoEmpresa').value;
        const monto = parseFloat($('#pagoMonto').value);
        const fecha = $('#pagoFecha').value;

        let valido = true;
        if (!idEmpresa) { $('#errorPagoEmpresa').textContent = 'Selecciona una empresa'; valido = false; }
        else $('#errorPagoEmpresa').textContent = '';

        if (!monto || monto <= 0) { $('#errorPagoMonto').textContent = 'Ingresa un monto válido'; valido = false; }
        else $('#errorPagoMonto').textContent = '';

        if (!fecha) { $('#errorPagoFecha').textContent = 'Selecciona una fecha'; valido = false; }
        else $('#errorPagoFecha').textContent = '';

        if (!valido) return;

        const datosPago = {
            id_empresa: parseInt(idEmpresa),
            monto: monto,
            metodo_pago: $('#pagoMetodo').value,
            fecha_pago: fecha + ' 00:00:00',
            estado: parseInt($('#pagoEstado').value)
        };

        try {
            if (idPago) {
                await actualizarPago(idPago, datosPago);
                showToast('success', 'Pago actualizado', 'Los cambios se guardaron correctamente.');
            } else {
                await crearPago(datosPago);
                showToast('success', 'Pago registrado', 'El pago se agregó correctamente.');
            }
            cerrarModalPago();
            cargarPagos();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo guardar el pago.');
        }
    });

    
    const modalConfirmar = $('#modalConfirmarPago');
    let confirmCallback = null;

    function openConfirm(titulo, mensaje, onConfirm) {
        $('#modalConfirmarPagoTitulo').textContent = titulo;
        $('#modalConfirmarPagoMensaje').textContent = mensaje;
        confirmCallback = onConfirm;
        modalConfirmar.classList.add('open');
    }
    function closeConfirm() {
        modalConfirmar.classList.remove('open');
        confirmCallback = null;
    }
    $('#modalConfirmarPagoCerrar')?.addEventListener('click', closeConfirm);
    $('#modalConfirmarPagoCancelar')?.addEventListener('click', closeConfirm);
    $('#modalConfirmarPagoAceptar')?.addEventListener('click', function () {
        const cb = confirmCallback;
        closeConfirm();
        if (typeof cb === 'function') cb();
    });
    modalConfirmar?.addEventListener('click', (e) => { if (e.target === modalConfirmar) closeConfirm(); });

    
    $('#cuerpo-pagos')?.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const id = btn.dataset.id;

        if (btn.dataset.action === 'editar-pago') {
            abrirModalEditar(id);
        } else if (btn.dataset.action === 'eliminar-pago') {
            openConfirm('Eliminar pago', '¿Deseas eliminar este registro de pago? Esta acción no se puede deshacer.', async () => {
                try {
                    await eliminarPago(id);
                    showToast('success', 'Pago eliminado', 'El registro se eliminó correctamente.');
                    cargarPagos();
                } catch (error) {
                    showToast('error', 'Error', error.message || 'No se pudo eliminar el pago.');
                }
            });
        }
    });

    
    document.addEventListener('DOMContentLoaded', cargarPagos);
})();
