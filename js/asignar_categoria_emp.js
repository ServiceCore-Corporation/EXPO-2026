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

    /* ---------- Modal editar asignación ---------- */
    const modalAsig = $('#modalAsig');
    let filaEnEdicion = null;

    function abrirModalAsig(row) {
        filaEnEdicion = row;
        const nombre = row.dataset.nombre;
        const catsActuales = (row.dataset.cats || '').split(',').filter(Boolean);

        $('#modalAsigTitulo').textContent = `Editar Categorías — ${nombre}`;
        $('#modalAsigSub').textContent = 'Selecciona las categorías que este colaborador atenderá.';
        $all('.chk-categoria-asig').forEach(chk => {
            chk.checked = catsActuales.includes(chk.value);
        });
        modalAsig.classList.add('open');
    }
    function cerrarModalAsig() {
        modalAsig.classList.remove('open');
        filaEnEdicion = null;
    }

    $('#modalAsigCerrar').addEventListener('click', cerrarModalAsig);
    $('#btnCancelarAsig').addEventListener('click', cerrarModalAsig);
    modalAsig.addEventListener('click', (e) => { if (e.target === modalAsig) cerrarModalAsig(); });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.row-icon-btn');
        if (!btn) return;
        const row = btn.closest('.asig-row');
        if (!row) return;
        if (btn.dataset.action === 'editar-asignacion') abrirModalAsig(row);
    });

    $('#btnGuardarAsig').addEventListener('click', async function () {
        if (!filaEnEdicion) return;
        const idAgente = filaEnEdicion.dataset.id;
        const catsActuales = new Set((filaEnEdicion.dataset.cats || '').split(',').filter(Boolean));
        const catsSeleccionadas = new Set(Array.from($all('.chk-categoria-asig')).filter(c => c.checked).map(c => c.value));

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
                const res = await fetch('asignar_categoria.php?ajax=1', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.ok) throw new Error(data.mensaje || 'No se pudo asignar una categoría.');
            }
            for (const idCat of aQuitar) {
                const fd = new FormData();
                fd.append('accion', 'desasignar');
                fd.append('id_categoria', idCat);
                fd.append('id_agente', idAgente);
                const res = await fetch('asignar_categoria.php?ajax=1', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.ok) throw new Error(data.mensaje || 'No se pudo desasignar una categoría.');
            }

            // Actualiza dataset y celda visible de la fila
            const nuevasCats = [...catsSeleccionadas];
            filaEnEdicion.dataset.cats = nuevasCats.join(',');
            const celda = filaEnEdicion.querySelector('[data-cell-cats]');
            if (nuevasCats.length === 0) {
                celda.innerHTML = '<span style="color:var(--text-muted);">Todas (sin restricción)</span>';
            } else {
                celda.innerHTML = nuevasCats.map(id => {
                    const chk = document.querySelector(`.chk-categoria-asig[value="${id}"]`);
                    const nombreCat = chk ? chk.closest('label').textContent.trim() : id;
                    return `<span class="chip-cat">${nombreCat}</span>`;
                }).join('');
            }

            showToast('success', 'Asignación guardada', 'Las categorías se actualizaron correctamente.');
            cerrarModalAsig();
        } catch (error) {
            showToast('error', 'Error', error.message || 'No se pudo guardar la asignación.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        }
    });

    window.addEventListener('load', () => {
        $all('.animar').forEach((el, i) => {
            setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, i * 100);
        });
    });
})();
