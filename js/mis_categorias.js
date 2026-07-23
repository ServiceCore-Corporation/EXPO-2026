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

    /* ---------- Búsqueda ---------- */
    function aplicarFiltroCat() {
        const texto = $('#buscadorCat').value.trim().toLowerCase();
        let visibles = 0;
        $all('.cat-row').forEach((row) => {
            const mostrar = !texto || row.dataset.nombre.toLowerCase().includes(texto);
            row.classList.toggle('row-hidden', !mostrar);
            if (mostrar) visibles++;
        });
        $('#emptyCat').style.display = visibles === 0 ? 'flex' : 'none';
    }
    $('#buscadorCat').addEventListener('input', aplicarFiltroCat);

    /* ---------- Modal crear / editar categoría ---------- */
    const modalCat = $('#modalCat');
    const formCat = $('#formCat');
    let modoEdicionCat = false;
    let filaCatEnEdicion = null;

    function abrirModalNuevaCat() {
        modoEdicionCat = false;
        filaCatEnEdicion = null;
        $('#modalCatTitulo').textContent = 'Nueva Categoría';
        $('#modalCatSub').textContent = 'Completa el nombre de la nueva categoría.';
        $('#btnGuardarCat').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Categoría';
        formCat.reset();
        $('#catId').value = '';
        $('#errorCatNombre').textContent = '';
        $('#catNombre').closest('.input-icon').classList.remove('invalid');
        modalCat.classList.add('open');
        $('#catNombre').focus();
    }

    function abrirModalEditarCat(row) {
        modoEdicionCat = true;
        filaCatEnEdicion = row;
        $('#modalCatTitulo').textContent = 'Editar Categoría';
        $('#modalCatSub').textContent = 'Actualiza el nombre de esta categoría.';
        $('#btnGuardarCat').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Cambios';
        $('#catId').value = row.dataset.id;
        $('#catNombre').value = row.dataset.nombre;
        $('#errorCatNombre').textContent = '';
        $('#catNombre').closest('.input-icon').classList.remove('invalid');
        modalCat.classList.add('open');
        $('#catNombre').focus();
    }

    function cerrarModalCat() {
        modalCat.classList.remove('open');
        filaCatEnEdicion = null;
    }

    $('#btnNuevaCat').addEventListener('click', abrirModalNuevaCat);
    $('#modalCatCerrar').addEventListener('click', cerrarModalCat);
    $('#btnCancelarCat').addEventListener('click', cerrarModalCat);
    modalCat.addEventListener('click', (e) => { if (e.target === modalCat) cerrarModalCat(); });

    function existeNombreCatDuplicado(nombre, idExcluir) {
        return Array.from($all('.cat-row')).some(
            (row) => row.dataset.nombre.toLowerCase() === nombre.toLowerCase() && row.dataset.id !== idExcluir
        );
    }

    function crearElementoFilaCat(data) {
        const tr = document.createElement('tr');
        tr.className = 'cat-row';
        tr.dataset.id = data.id;
        tr.dataset.nombre = data.nombre;
        tr.dataset.tickets = '0';
        tr.innerHTML = `
            <td>
                <span class="chip-cat" style="background:var(--primary-soft);">
                    <span class="material-symbols-outlined" style="font-size:16px;">sell</span>
                    <strong data-row-nombre>${data.nombre}</strong>
                </span>
            </td>
            <td><span data-row-tickets>0</span> ticket(s)</td>
            <td><span data-row-agentes>0</span> agente(s)</td>
            <td>
                <div class="row-actions">
                    <button class="row-icon-btn" data-action="editar" title="Editar categoría">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="row-icon-btn danger" data-action="eliminar" title="Eliminar categoría">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </td>
        `;
        return tr;
    }

    function actualizarKpisCat() {
        const total = $all('.cat-row').length;
        const totalTickets = Array.from($all('.cat-row')).reduce((s, r) => s + parseInt(r.dataset.tickets || '0', 10), 0);
        const sinAgente = Array.from($all('.cat-row')).filter(r => parseInt(r.querySelector('[data-row-agentes]')?.textContent || '0', 10) === 0).length;
        const conActividad = Array.from($all('.cat-row')).filter(r => parseInt(r.dataset.tickets || '0', 10) > 0).length;
        const elTotal = $('[data-kpi-cat="total"]');
        const elTickets = $('[data-kpi-cat="tickets"]');
        const elSinAgente = $('[data-kpi-cat="sinagente"]');
        const elActivas = $('[data-kpi-cat="activas"]');
        if (elTotal) elTotal.textContent = total;
        if (elTickets) elTickets.textContent = totalTickets;
        if (elSinAgente) elSinAgente.textContent = sinAgente;
        if (elActivas) elActivas.textContent = conActividad;
    }

    formCat.addEventListener('submit', async function (e) {
        e.preventDefault();
        const id = $('#catId').value;
        const nombre = $('#catNombre').value.trim();
        $('#errorCatNombre').textContent = '';
        $('#catNombre').closest('.input-icon').classList.remove('invalid');

        if (!nombre) {
            $('#catNombre').closest('.input-icon').classList.add('invalid');
            $('#errorCatNombre').textContent = 'El nombre de la categoría es obligatorio.';
            return;
        }
        if (existeNombreCatDuplicado(nombre, id)) {
            $('#catNombre').closest('.input-icon').classList.add('invalid');
            $('#errorCatNombre').textContent = 'Ya existe una categoría con este nombre.';
            return;
        }

        const btnGuardar = $('#btnGuardarCat');
        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.disabled = true;

        try {
            const fd = new FormData();
            fd.append('accion', modoEdicionCat ? 'editar' : 'crear');
            if (modoEdicionCat) fd.append('id', id);
            fd.append('nombre', nombre);

            const res = await fetch('mis_categorias.php?ajax=1', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo guardar la categoría.');

            if (modoEdicionCat && filaCatEnEdicion) {
                filaCatEnEdicion.dataset.nombre = nombre;
                filaCatEnEdicion.querySelector('[data-row-nombre]').textContent = nombre;
                showToast('success', 'Categoría actualizada', `"${nombre}" se guardó correctamente.`);
            } else {
                const nuevaFila = crearElementoFilaCat({ id: data.id, nombre });
                $('#tablaCat').prepend(nuevaFila);
                showToast('success', 'Categoría creada', `"${nombre}" fue registrada correctamente.`);
            }

            aplicarFiltroCat();
            actualizarKpisCat();
            cerrarModalCat();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo guardar la categoría.');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
        }
    });

    /* ---------- Confirmación / eliminar ---------- */
    const modalConfirmarCat = $('#modalConfirmarCat');
    let confirmCallbackCat = null;
    function openConfirmCat(titulo, mensaje, onConfirm) {
        $('#modalConfirmarCatTitulo').textContent = titulo;
        $('#modalConfirmarCatMensaje').textContent = mensaje;
        confirmCallbackCat = onConfirm;
        modalConfirmarCat.classList.add('open');
    }
    function closeConfirmCat() {
        modalConfirmarCat.classList.remove('open');
        confirmCallbackCat = null;
    }
    $('#modalConfirmarCatCerrar').addEventListener('click', closeConfirmCat);
    $('#modalConfirmarCatCancelar').addEventListener('click', closeConfirmCat);
    $('#modalConfirmarCatAceptar').addEventListener('click', function () {
        const cb = confirmCallbackCat;
        closeConfirmCat();
        if (typeof cb === 'function') cb();
    });
    modalConfirmarCat.addEventListener('click', (e) => { if (e.target === modalConfirmarCat) closeConfirmCat(); });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.row-icon-btn');
        if (!btn) return;
        const row = btn.closest('.cat-row');
        if (!row) return;
        const accion = btn.dataset.action;
        const nombre = row.dataset.nombre;

        if (accion === 'editar') abrirModalEditarCat(row);

        if (accion === 'eliminar') {
            const tickets = parseInt(row.dataset.tickets || '0', 10);
            let advertencia = `¿Seguro que deseas eliminar la categoría "${nombre}"?`;
            if (tickets > 0) advertencia = `No se puede eliminar "${nombre}" porque tiene ${tickets} ticket(s) asociados. Desasígnalos primero.`;

            openConfirmCat('Eliminar Categoría', advertencia, async () => {
                if (tickets > 0) {
                    showToast('error', 'No se puede eliminar', `"${nombre}" tiene tickets asociados.`);
                    return;
                }
                try {
                    const fd = new FormData();
                    fd.append('accion', 'eliminar');
                    fd.append('id', row.dataset.id);
                    const res = await fetch('mis_categorias.php?ajax=1', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.mensaje || 'No se pudo eliminar la categoría.');

                    row.remove();
                    aplicarFiltroCat();
                    actualizarKpisCat();
                    showToast('success', 'Categoría eliminada', `"${nombre}" fue eliminada correctamente.`);
                } catch (error) {
                    showToast('error', 'Error', error.message || 'No se pudo eliminar la categoría.');
                }
            });
        }
    });

    /* ---------- Modal: asignar categoría(s) a agente ---------- */
    const modalAsignarCat = $('#modalAsignarCat');
    function abrirModalAsignar() {
        $('#selectAgenteCat').value = '';
        $all('.chk-categoria').forEach(chk => chk.checked = false);
        $('#errorAsignarCat').textContent = '';
        modalAsignarCat.classList.add('open');
    }
    function cerrarModalAsignar() { modalAsignarCat.classList.remove('open'); }

    $('#btnAsignarCat').addEventListener('click', abrirModalAsignar);
    $('#modalAsignarCatCerrar').addEventListener('click', cerrarModalAsignar);
    $('#btnCancelarAsignarCat').addEventListener('click', cerrarModalAsignar);
    modalAsignarCat.addEventListener('click', (e) => { if (e.target === modalAsignarCat) cerrarModalAsignar(); });

    $('#selectAgenteCat').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const catsActuales = (opt?.dataset.cats || '').split(',').filter(Boolean);
        $all('.chk-categoria').forEach(chk => {
            chk.checked = catsActuales.includes(chk.value);
        });
    });

    $('#btnGuardarAsignarCat').addEventListener('click', async function () {
        const idAgente = $('#selectAgenteCat').value;
        $('#errorAsignarCat').textContent = '';
        if (!idAgente) {
            $('#errorAsignarCat').textContent = 'Selecciona un agente.';
            return;
        }

        const opt = $('#selectAgenteCat').options[$('#selectAgenteCat').selectedIndex];
        const catsActuales = new Set((opt?.dataset.cats || '').split(',').filter(Boolean));
        const catsSeleccionadas = new Set(Array.from($all('.chk-categoria')).filter(c => c.checked).map(c => c.value));

        const aAsignar = [...catsSeleccionadas].filter(c => !catsActuales.has(c));
        const aQuitar = [...catsActuales].filter(c => !catsSeleccionadas.has(c));

        if (aAsignar.length === 0 && aQuitar.length === 0) {
            showToast('info', 'Sin cambios', 'No hay categorías nuevas para asignar o quitar.');
            return;
        }

        const btn = this;
        const textoOriginal = btn.innerHTML;
        btn.disabled = true;

        try {
            for (const idCat of aAsignar) {
                const fd = new FormData();
                fd.append('accion', 'asignar');
                fd.append('id_categoria', idCat);
                fd.append('id_agente', idAgente);
                const res = await fetch('mis_categorias.php?ajax=1', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.ok) throw new Error(data.mensaje || 'No se pudo asignar una categoría.');
            }
            for (const idCat of aQuitar) {
                const fd = new FormData();
                fd.append('accion', 'desasignar');
                fd.append('id_categoria', idCat);
                fd.append('id_agente', idAgente);
                const res = await fetch('mis_categorias.php?ajax=1', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.ok) throw new Error(data.mensaje || 'No se pudo desasignar una categoría.');
            }

            // Actualiza el contador de agentes visibles en la tabla de categorías
            aAsignar.forEach(idCat => {
                const row = document.querySelector(`.cat-row[data-id="${idCat}"]`);
                const el = row?.querySelector('[data-row-agentes]');
                if (el) el.textContent = String(parseInt(el.textContent, 10) + 1);
            });
            aQuitar.forEach(idCat => {
                const row = document.querySelector(`.cat-row[data-id="${idCat}"]`);
                const el = row?.querySelector('[data-row-agentes]');
                if (el) el.textContent = String(Math.max(0, parseInt(el.textContent, 10) - 1));
            });
            actualizarKpisCat();

            showToast('success', 'Asignación guardada', 'Las categorías del agente se actualizaron correctamente.');
            cerrarModalAsignar();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo guardar la asignación.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        }
    });

    aplicarFiltroCat();

    window.addEventListener('load', () => {
        $all('.animar').forEach((el, i) => {
            setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, i * 100);
        });
    });
})();
