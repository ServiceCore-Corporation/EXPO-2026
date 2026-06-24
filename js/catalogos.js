async function obtenerCategorias() {
    return await peticion('/api/categorias');
}

async function crearCategoria(nombre) {
    return await peticion('/api/categorias', 'POST', { nombre });
}

async function actualizarCategoria(idCategoria, nombre) {
    return await peticion(`/api/categorias/${idCategoria}`, 'PUT', { nombre });
}

async function eliminarCategoria(idCategoria) {
    return await peticion(`/api/categorias/${idCategoria}`, 'DELETE');
}

// Llena un <select> con las categorías disponibles
async function llenarSelectCategorias(idSelect) {
    const lista   = await obtenerCategorias();
    const select  = document.getElementById(idSelect);
    if (!select) return;
    select.innerHTML = '<option value="">Seleccionar...</option>' +
        lista.map(c => `<option value="${c.id_categoria}">${c.nombre}</option>`).join('');
}

// ── PRIORIDADES ───────────────────────────────────────────────

async function obtenerPrioridades() {
    return await peticion('/api/prioridades');
}

async function crearPrioridad(nombre) {
    return await peticion('/api/prioridades', 'POST', { nombre });
}

async function actualizarPrioridad(idPrioridad, nombre) {
    return await peticion(`/api/prioridades/${idPrioridad}`, 'PUT', { nombre });
}

async function eliminarPrioridad(idPrioridad) {
    return await peticion(`/api/prioridades/${idPrioridad}`, 'DELETE');
}

// Llena un <select> con las prioridades disponibles
async function llenarSelectPrioridades(idSelect) {
    const lista  = await obtenerPrioridades();
    const select = document.getElementById(idSelect);
    if (!select) return;
    select.innerHTML = '<option value="">Seleccionar...</option>' +
        lista.map(p => `<option value="${p.id_prioridad}">${p.nombre}</option>`).join('');
}

// ── ESTADOS ───────────────────────────────────────────────────

async function obtenerEstados() {
    return await peticion('/api/estados');
}

async function crearEstado(nombre) {
    return await peticion('/api/estados', 'POST', { nombre });
}

async function actualizarEstado(idEstado, nombre) {
    return await peticion(`/api/estados/${idEstado}`, 'PUT', { nombre });
}

async function eliminarEstado(idEstado) {
    return await peticion(`/api/estados/${idEstado}`, 'DELETE');
}

// Llena un <select> con los estados disponibles
async function llenarSelectEstados(idSelect) {
    const lista  = await obtenerEstados();
    const select = document.getElementById(idSelect);
    if (!select) return;
    select.innerHTML = '<option value="">Seleccionar...</option>' +
        lista.map(e => `<option value="${e.id_estado}">${e.nombre}</option>`).join('');
}

// ── ROLES ─────────────────────────────────────────────────────

async function obtenerRoles() {
    return await peticion('/api/roles');
}

// Llena un <select> con los roles disponibles
async function llenarSelectRoles(idSelect) {
    const lista  = await obtenerRoles();
    const select = document.getElementById(idSelect);
    if (!select) return;
    select.innerHTML = '<option value="">Seleccionar...</option>' +
        lista.map(r => `<option value="${r.id_rol}">${r.nombre}</option>`).join('');
}
