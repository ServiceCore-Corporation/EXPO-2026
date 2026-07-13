async function obtenerPlanes() {
    return await peticion('/api/planes');
}

async function obtenerPlan(idPlan) {
    return await peticion(`/api/planes/${idPlan}`);
}

async function crearPlan(datosPlan) {
    return await peticion('/api/planes', 'POST', datosPlan);
}

async function actualizarPlan(idPlan, datosPlan) {
    return await peticion(`/api/planes/${idPlan}`, 'PUT', datosPlan);
}

async function eliminarPlan(idPlan) {
    return await peticion(`/api/planes/${idPlan}`, 'DELETE');
}

// Activa o desactiva un plan
async function cambiarEstadoPlan(idPlan, activo) {
    return await peticion(`/api/planes/${idPlan}/estado`, 'PATCH', { activo });
}

function renderizarTablaPlanes(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="6" class="sin-datos">Sin planes</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(plan => `
        <tr>
            <td>${plan.id_plan}</td>
            <td>${plan.nombre}</td>
            <td>$${parseFloat(plan.precio).toFixed(2)}</td>
            <td>${plan.limite_usuarios}</td>
            <td>${plan.limite_tickets}</td>
            <td class="acciones">
                <button onclick="editarPlan(${plan.id_plan})">Editar</button>
                <button onclick="cambiarEstadoPlan(${plan.id_plan}, ${plan.activo == 1 ? 0 : 1}).then(() => cargarTablaPlanes())">
                    ${plan.activo == 1 ? 'Desactivar' : 'Activar'}
                </button>
                <button class="peligro" onclick="confirmarEliminarPlan(${plan.id_plan})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

async function cargarTablaPlanes(idTbody = 'cuerpo-planes') {
    try {
        const lista = await obtenerPlanes();
        renderizarTablaPlanes(lista, idTbody);
    } catch (error) {
        console.error('Error cargando planes:', error);
    }
}

async function editarPlan(idPlan) {
    return await obtenerPlan(idPlan);
}

// ── PAGOS ─────────────────────────────────────────────────────

async function obtenerPagos() {
    return await peticion('/api/pagos');
}

async function obtenerPago(idPago) {
    return await peticion(`/api/pagos/${idPago}`);
}

async function crearPago(datosPago) {
    return await peticion('/api/pagos', 'POST', datosPago);
}

async function actualizarPago(idPago, datosPago) {
    return await peticion(`/api/pagos/${idPago}`, 'PUT', datosPago);
}

async function eliminarPago(idPago) {
    return await peticion(`/api/pagos/${idPago}`, 'DELETE');
}

async function obtenerPagosPorEmpresa(idEmpresa) {
    return await peticion(`/api/pagos/empresa/${idEmpresa}`);
}

function badgeEstadoPago(estado) {
    estado = parseInt(estado);
    if (estado === 1) return { texto: 'Pagado',    clase: 'badge-green' };
    if (estado === 2) return { texto: 'Rechazado', clase: 'badge-red' };
    return { texto: 'Pendiente', clase: 'badge-yellow' };
}

function renderizarTablaPagos(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="6" class="sin-datos">Sin pagos</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(pago => {
        const estadoInfo = badgeEstadoPago(pago.estado);
        return `
        <tr class="pago-row" data-estado="${pago.estado}">
            <td>
                <span class="plan-nombre">
                    <span class="material-symbols-outlined">business</span>
                    <strong>${pago.empresa || '—'}</strong>
                </span>
            </td>
            <td>Q${parseFloat(pago.monto).toFixed(2)}</td>
            <td>${pago.metodo_pago}</td>
            <td>${new Date(pago.fecha_pago).toLocaleDateString('es-GT')}</td>
            <td><span class="badge ${estadoInfo.clase}">${estadoInfo.texto}</span></td>
            <td>
                <div class="row-actions">
                    <button class="row-icon-btn" data-action="editar-pago" data-id="${pago.id_pago}" title="Editar pago">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="row-icon-btn" data-action="eliminar-pago" data-id="${pago.id_pago}" title="Eliminar pago">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

async function cargarTablaPagos(idTbody = 'cuerpo-pagos') {
    try {
        const lista = await obtenerPagos();
        renderizarTablaPagos(lista, idTbody);
        return lista;
    } catch (error) {
        console.error('Error cargando pagos:', error);
        return [];
    }
}

async function editarPago(idPago) {
    const pago = await obtenerPago(idPago);
    return pago;
}
