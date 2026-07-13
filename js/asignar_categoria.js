document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('supervisorGrid');
    const cards = () => Array.from(grid.querySelectorAll('.supervisor-card'));
    const emptyState = document.getElementById('emptyState');
    const buscador = document.getElementById('buscadorSupervisores');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const filtroTag = document.getElementById('filtroActivoTag');
    const kpiCards = document.querySelectorAll('.kpi-clickable');

    let filtroActual = 'todos';
    let terminoBusqueda = '';

    const etiquetasFiltro = {
        'todos': 'Todos',
        'categorias': 'Todos',
        'asignados': 'Asignados',
        'sin-asignar': 'Sin asignar',
    };

    function aplicarFiltros() {
        let visibles = 0;

        cards().forEach(card => {
            const totalCats = parseInt(card.dataset.totalCategorias, 10) || 0;
            const nombre = (card.dataset.nombre || '').toLowerCase();
            const correo = (card.dataset.correo || '').toLowerCase();

            let coincideFiltro = true;
            if (filtroActual === 'asignados') coincideFiltro = totalCats > 0;
            if (filtroActual === 'sin-asignar') coincideFiltro = totalCats === 0;

            const coincideBusqueda = !terminoBusqueda ||
                nombre.includes(terminoBusqueda) ||
                correo.includes(terminoBusqueda);

            const mostrar = coincideFiltro && coincideBusqueda;
            card.classList.toggle('card-hidden', !mostrar);
            if (mostrar) visibles++;
        });

        emptyState.style.display = visibles === 0 ? 'block' : 'none';
        filtroTag.innerHTML = `Mostrando: <strong>${etiquetasFiltro[filtroActual] || 'Todos'}</strong>`;
    }

    kpiCards.forEach(card => {
        card.addEventListener('click', () => {
            kpiCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            filtroActual = card.dataset.filter || 'todos';
            aplicarFiltros();
        });
    });

    buscador.addEventListener('input', () => {
        terminoBusqueda = buscador.value.trim().toLowerCase();
        aplicarFiltros();
    });

    btnLimpiar.addEventListener('click', () => {
        buscador.value = '';
        terminoBusqueda = '';
        filtroActual = 'todos';
        kpiCards.forEach(c => c.classList.remove('active'));
        document.querySelector('.kpi-clickable[data-filter="todos"]').classList.add('active');
        aplicarFiltros();
    });

    /* ---------------------------------------------------------
       Modal: Asignar Categoría
    --------------------------------------------------------- */
    const modal = document.getElementById('modalAsignarCategoria');
    const btnCerrar = document.getElementById('modalAsignarCerrar');
    const btnCancelar = document.getElementById('btnCancelarAsignar');
    const form = document.getElementById('formAsignarCategoria');
    const inputIdUsuario = document.getElementById('asignarIdUsuario');
    const avatarEl = document.getElementById('modalSupervisorAvatar');
    const nombreEl = document.getElementById('modalSupervisorNombre');
    const correoEl = document.getElementById('modalSupervisorCorreo');
    const checklist = document.getElementById('categoryChecklist');
    const errorEl = document.getElementById('errorAsignarCategoria');

    let cardActiva = null;

    function abrirModal(card) {
        cardActiva = card;
        errorEl.textContent = '';

        const nombre = card.dataset.nombre;
        const correo = card.dataset.correo;
        const idUsuario = card.dataset.id;
        const categoriasAsignadas = (card.dataset.categorias || '')
            .split(',')
            .filter(Boolean)
            .map(Number);

        inputIdUsuario.value = idUsuario;
        nombreEl.textContent = nombre;
        correoEl.textContent = correo;
        avatarEl.textContent = nombre
            .split(' ')
            .slice(0, 2)
            .map(p => p.charAt(0))
            .join('')
            .toUpperCase();

        checklist.querySelectorAll('input[type="checkbox"]').forEach(chk => {
            chk.checked = categoriasAsignadas.includes(Number(chk.value));
        });

        modal.classList.add('open');
    }

    function cerrarModal() {
        modal.classList.remove('open');
        cardActiva = null;
        form.reset();
    }

    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="asignar-categoria"]');
        if (!btn) return;
        const card = btn.closest('.supervisor-card');
        abrirModal(card);
    });

    btnCerrar.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) cerrarModal();
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        errorEl.textContent = '';

        const idUsuario = inputIdUsuario.value;
        const seleccionadas = Array.from(
            checklist.querySelectorAll('input[type="checkbox"]:checked')
        ).map(chk => chk.value);

        const btnGuardar = document.getElementById('btnGuardarAsignar');
        btnGuardar.disabled = true;

        // TODO: conecta aquí tu API — por ejemplo:
        // fetch(`/api/supervisores/${idUsuario}/categorias`, {
        //     method: 'PUT',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({ categorias: seleccionadas })
        // })
        //     .then(resp => resp.json())
        //     .then(data => { /* usa data para confirmar y luego llama actualizarCardTrasGuardar(...) */ })
        //     .catch(() => { errorEl.textContent = 'No se pudo guardar la asignación.'; })
        //     .finally(() => { btnGuardar.disabled = false; });

        actualizarCardTrasGuardar(cardActiva, seleccionadas, seleccionadas.length);
        mostrarToast('success', 'Categorías asignadas', 'La asignación se guardó correctamente.');
        cerrarModal();
        recalcularKPIs();
        aplicarFiltros();
        btnGuardar.disabled = false;
    });

    function actualizarCardTrasGuardar(card, categoriasIds, total) {
        if (!card) return;
        card.dataset.categorias = categoriasIds.join(',');
        card.dataset.totalCategorias = String(total);

        const badge = card.querySelector('[data-badge-categorias]');
        badge.textContent = `${total} categoría${total === 1 ? '' : 's'}`;
        badge.classList.remove('badge-blue', 'badge-gray');
        badge.classList.add(total > 0 ? 'badge-blue' : 'badge-gray');
    }

    function recalcularKPIs() {
        const todas = cards();
        const total = todas.length;
        const asignados = todas.filter(c => (parseInt(c.dataset.totalCategorias, 10) || 0) > 0).length;
        document.querySelector('[data-kpi="total"]').textContent = total;
        document.querySelector('[data-kpi="asignados"]').textContent = asignados;
        document.querySelector('[data-kpi="sin-asignar"]').textContent = total - asignados;
    }

    /* ---------------------------------------------------------
       Toasts
    --------------------------------------------------------- */
    function mostrarToast(tipo, titulo, mensaje) {
        const contenedor = document.getElementById('toastContainer');
        const iconos = { success: 'check_circle', error: 'error', info: 'info' };

        const toast = document.createElement('div');
        toast.className = `toast ${tipo}`;
        toast.innerHTML = `
            <span class="material-symbols-outlined">${iconos[tipo] || 'info'}</span>
            <div>
                <strong>${titulo}</strong>
                <p>${mensaje}</p>
            </div>
        `;
        contenedor.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 200);
        }, 3200);
    }

    aplicarFiltros();
});
