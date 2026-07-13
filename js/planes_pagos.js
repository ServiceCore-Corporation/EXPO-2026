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
    try {
        const plan = await obtenerPlan(idPlan);
        document.getElementById('plan-id').value             = plan.id_plan;
        document.getElementById('plan-nombre').value         = plan.nombre;
        document.getElementById('plan-precio').value         = plan.precio;
        document.getElementById('plan-limite-usuarios').value = plan.limite_usuarios;
        document.getElementById('plan-limite-tickets').value  = plan.limite_tickets;
        document.getElementById('titulo-formulario-plan').textContent = 'Editar Plan';
    } catch (error) {
        mostrarMensaje('msg-planes', 'Error al cargar plan.', true);
    }
}

async function confirmarEliminarPlan(idPlan) {
    if (!confirm('¿Eliminar este plan?')) return;
    try {
        await eliminarPlan(idPlan);
        mostrarMensaje('msg-planes', 'Plan eliminado.');
        cargarTablaPlanes();
    } catch (error) {
        mostrarMensaje('msg-planes', error.message, true);
    }
}

const formularioPlan = document.getElementById('formulario-plan');
if (formularioPlan) {
    formularioPlan.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idPlan    = document.getElementById('plan-id').value;
        const datosPlan = {
            nombre:          document.getElementById('plan-nombre').value.trim(),
            precio:          parseFloat(document.getElementById('plan-precio').value),
            limite_usuarios: parseInt(document.getElementById('plan-limite-usuarios').value),
            limite_tickets:  parseInt(document.getElementById('plan-limite-tickets').value),
            activo:          1
        };

        try {
            if (idPlan) {
                await actualizarPlan(idPlan, datosPlan);
                mostrarMensaje('msg-planes', 'Plan actualizado.');
            } else {
                await crearPlan(datosPlan);
                mostrarMensaje('msg-planes', 'Plan creado.');
            }
            limpiarFormulario('formulario-plan');
            document.getElementById('plan-id').value = '';
            document.getElementById('titulo-formulario-plan').textContent = 'Nuevo Plan';
            cargarTablaPlanes();
        } catch (error) {
            mostrarMensaje('msg-planes', error.message, true);
        }
    });
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

function renderizarTablaPagos(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="5" class="sin-datos">Sin pagos</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(pago => `
        <tr>
            <td>${pago.id_pago}</td>
            <td>${pago.empresa || '—'}</td>
            <td>$${parseFloat(pago.monto).toFixed(2)}</td>
            <td>${pago.metodo_pago}</td>
            <td>${new Date(pago.fecha_pago).toLocaleDateString('es-GT')}</td>
            <td class="acciones">
                <button class="peligro" onclick="confirmarEliminarPago(${pago.id_pago})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

async function cargarTablaPagos(idTbody = 'cuerpo-pagos') {
    try {
        const lista = await obtenerPagos();
        renderizarTablaPagos(lista, idTbody);
    } catch (error) {
        console.error('Error cargando pagos:', error);
    }
}

async function confirmarEliminarPago(idPago) {
    if (!confirm('¿Eliminar este pago?')) return;
    try {
        await eliminarPago(idPago);
        mostrarMensaje('msg-pagos', 'Pago eliminado.');
        cargarTablaPagos();
    } catch (error) {
        mostrarMensaje('msg-pagos', error.message, true);
    }
}

const formularioPago = document.getElementById('formulario-pago');
if (formularioPago) {
    formularioPago.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idPago    = document.getElementById('pago-id').value;
        const datosPago = {
            id_empresa:  parseInt(document.getElementById('pago-empresa').value),
            monto:       parseFloat(document.getElementById('pago-monto').value),
            metodo_pago: document.getElementById('pago-metodo').value.trim(),
            fecha_pago:  document.getElementById('pago-fecha').value,
            estado:      1
        };

        try {
            if (idPago) {
                await actualizarPago(idPago, datosPago);
                mostrarMensaje('msg-pagos', 'Pago actualizado.');
            } else {
                await crearPago(datosPago);
                mostrarMensaje('msg-pagos', 'Pago registrado.');
            }
            limpiarFormulario('formulario-pago');
            document.getElementById('pago-id').value = '';
            cargarTablaPagos();
        } catch (error) {
            mostrarMensaje('msg-pagos', error.message, true);
        }
    });
}
