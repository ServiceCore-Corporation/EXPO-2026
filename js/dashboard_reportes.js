// Obtiene el resumen general del dashboard
async function obtenerResumenDashboard() {
    return await peticion('/api/dashboard');
}

// Obtiene estadísticas de tickets para el dashboard
async function obtenerDashboardTickets() {
    return await peticion('/api/dashboard/tickets');
}

// Obtiene estadísticas de usuarios para el dashboard
async function obtenerDashboardUsuarios() {
    return await peticion('/api/dashboard/usuarios');
}

// Obtiene estadísticas de pagos para el dashboard
async function obtenerDashboardPagos() {
    return await peticion('/api/dashboard/pagos');
}

// Obtiene estadísticas de empresas para el dashboard
async function obtenerDashboardEmpresas() {
    return await peticion('/api/dashboard/empresas');
}

// Carga y pinta las tarjetas del resumen general
async function cargarResumenDashboard() {
    try {
        const datos = await obtenerResumenDashboard();

        // Pintar contadores en las tarjetas
        const elTickets  = document.getElementById('stat-tickets');
        const elUsuarios = document.getElementById('stat-usuarios');
        const elEmpresas = document.getElementById('stat-empresas');
        const elIngresos = document.getElementById('stat-ingresos');

        if (elTickets)  elTickets.textContent  = datos.resumen.tickets;
        if (elUsuarios) elUsuarios.textContent = datos.resumen.usuarios;
        if (elEmpresas) elEmpresas.textContent = datos.resumen.empresas;
        if (elIngresos) elIngresos.textContent = '$' + parseFloat(datos.resumen.ingresos).toLocaleString('es-GT');

        // Pintar tickets por estado si existe el contenedor
        const contenedorEstados = document.getElementById('estado-tickets');
        if (contenedorEstados && datos.tickets_por_estado) {
            contenedorEstados.innerHTML = datos.tickets_por_estado.map(e => `
                <div class="fila-estado">
                    <span>${e.estado}</span>
                    <span class="badge">${e.total}</span>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error cargando dashboard:', error);
    }
}

// Carga los tickets recientes del dashboard en una tabla
async function cargarTicketsRecientes(idTbody = 'tabla-tickets-recientes') {
    try {
        const datos  = await obtenerDashboardTickets();
        const cuerpo = document.getElementById(idTbody);
        if (!cuerpo) return;

        // Pintar contadores de estados
        const elPendientes = document.getElementById('stat-pendientes');
        const elProceso    = document.getElementById('stat-proceso');
        const elCerrados   = document.getElementById('stat-cerrados');
        if (elPendientes) elPendientes.textContent = datos.pendientes;
        if (elProceso)    elProceso.textContent    = datos.en_proceso;
        if (elCerrados)   elCerrados.textContent   = datos.cerrados;

        cuerpo.innerHTML = (datos.recientes || []).map(t => `
            <tr>
                <td>#TK-${t.id_ticket}</td>
                <td>${t.titulo}</td>
                <td><span class="badge">${t.estado}</span></td>
                <td>${t.prioridad}</td>
                <td>${t.cliente || '—'}</td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="sin-datos">Sin tickets recientes</td></tr>';
    } catch (error) {
        console.error('Error cargando tickets recientes:', error);
    }
}

// ── REPORTES ──────────────────────────────────────────────────

async function obtenerReporteTickets() {
    return await peticion('/api/reportes/tickets');
}

async function obtenerReportePorEstado() {
    return await peticion('/api/reportes/tickets-estado');
}

async function obtenerReportePorPrioridad() {
    return await peticion('/api/reportes/tickets-prioridad');
}

async function obtenerReportePorCategoria() {
    return await peticion('/api/reportes/tickets-categoria');
}

async function obtenerReportePorAgente() {
    return await peticion('/api/reportes/tickets-agente');
}

async function obtenerReportePorEmpresa() {
    return await peticion('/api/reportes/tickets-empresa');
}

async function obtenerReporteHistorial() {
    return await peticion('/api/reportes/historial');
}

async function obtenerReportePagos() {
    return await peticion('/api/reportes/pagos');
}

// Carga cualquier reporte y lo pinta en una tabla genérica
async function cargarReporte(tipo, idTbody) {
    const mapaReportes = {
        'tickets':           obtenerReporteTickets,
        'tickets-estado':    obtenerReportePorEstado,
        'tickets-prioridad': obtenerReportePorPrioridad,
        'tickets-categoria': obtenerReportePorCategoria,
        'tickets-agente':    obtenerReportePorAgente,
        'tickets-empresa':   obtenerReportePorEmpresa,
        'historial':         obtenerReporteHistorial,
        'pagos':             obtenerReportePagos,
    };

    const funcionReporte = mapaReportes[tipo];
    if (!funcionReporte) return;

    try {
        const lista  = await funcionReporte();
        const cuerpo = document.getElementById(idTbody);
        if (!cuerpo || lista.length === 0) return;

        // Generar encabezados dinámicos con las claves del primer objeto
        const claves = Object.keys(lista[0]);
        const encabezado = document.getElementById('encabezado-reporte');
        if (encabezado) {
            encabezado.innerHTML = claves.map(c => `<th>${c.replace(/_/g, ' ')}</th>`).join('');
        }

        cuerpo.innerHTML = lista.map(fila =>
            `<tr>${claves.map(c => `<td>${fila[c] ?? '—'}</td>`).join('')}</tr>`
        ).join('');
    } catch (error) {
        console.error('Error cargando reporte:', error);
    }
}
