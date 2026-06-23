/* =========================================================
   usuarios_roles.js — ServiceCore Corporation
   Vista: Gestión de Usuarios por Rol (tablero de cards)
   ========================================================= */

(function () {
    'use strict';

    const $ = (sel) => document.querySelector(sel);
    const $all = (sel) => document.querySelectorAll(sel);

    const ACCENTS = {
        'Administrador': 'purple',
        'Supervisor': 'blue',
        'Agente': 'green',
        'Cliente': 'gray',
    };
    const ICONS = {
        'Administrador': 'shield_person',
        'Supervisor': 'supervisor_account',
        'Agente': 'support_agent',
        'Cliente': 'person',
    };
    const BADGE_ROL = {
        'Administrador': 'badge badge-purple',
        'Supervisor': 'badge badge-blue',
        'Agente': 'badge badge-green',
        'Cliente': 'badge badge-gray',
    };

    let nextIdSeq = 100;

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
    const modalConfirmar = $('#modalConfirmar');
    let confirmCallback = null;
    function openConfirm(titulo, mensaje, onConfirm) {
        $('#modalConfirmarTitulo').textContent = titulo;
        $('#modalConfirmarMensaje').textContent = mensaje;
        confirmCallback = onConfirm;
        modalConfirmar.classList.add('open');
    }
    function closeConfirm() {
        modalConfirmar.classList.remove('open');
        confirmCallback = null;
    }
    $('#modalConfirmarCerrar').addEventListener('click', closeConfirm);
    $('#modalConfirmarCancelar').addEventListener('click', closeConfirm);
    $('#modalConfirmarAceptar').addEventListener('click', function () {
        const cb = confirmCallback;
        closeConfirm();
        if (typeof cb === 'function') cb();
    });
    modalConfirmar.addEventListener('click', (e) => { if (e.target === modalConfirmar) closeConfirm(); });

    /* ---------- Filtro por rol vía KPIs ---------- */
    const kpiGrid = $('#kpiGrid');
    let rolActivo = '';

    function aplicarFiltroRolYBusqueda() {
        const texto = $('#buscadorUsuarios').value.trim().toLowerCase();
        const estado = $('#filterEstado').value;

        $all('.role-column').forEach((col) => {
            const rolColumna = col.dataset.roleColumn;
            const colVisible = !rolActivo || rolActivo === rolColumna;
            col.classList.toggle('column-hidden', !colVisible);
            if (!colVisible) return;

            let visiblesEnColumna = 0;
            col.querySelectorAll('.user-card').forEach((card) => {
                const matchTexto = !texto || (card.dataset.nombre + ' ' + card.dataset.correo).toLowerCase().includes(texto);
                const matchEstado = !estado || card.dataset.estado === estado;
                const mostrar = matchTexto && matchEstado;
                card.classList.toggle('card-hidden', !mostrar);
                if (mostrar) visiblesEnColumna++;
            });

            const empty = col.querySelector('[data-empty-column]');
            if (empty) empty.style.display = visiblesEnColumna === 0 ? 'block' : 'none';
        });
    }

    kpiGrid.addEventListener('click', function (e) {
        const kpi = e.target.closest('.kpi-clickable');
        if (!kpi) return;

        rolActivo = kpi.dataset.filterRol || '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        kpi.classList.add('active');

        aplicarFiltroRolYBusqueda();
    });

    $('#buscadorUsuarios').addEventListener('input', aplicarFiltroRolYBusqueda);
    $('#filterEstado').addEventListener('change', aplicarFiltroRolYBusqueda);

    $('#btnLimpiarFiltros').addEventListener('click', function () {
        rolActivo = '';
        $('#filterEstado').value = '';
        $('#buscadorUsuarios').value = '';
        $all('.kpi-clickable').forEach((k) => k.classList.remove('active'));
        $('.kpi-clickable[data-filter-rol=""]').classList.add('active');
        aplicarFiltroRolYBusqueda();
    });

    /* ---------- KPIs / contadores ---------- */
    function recalcularContadores() {
        let total = 0, activos = 0;
        const porRol = { 'Administrador': 0, 'Supervisor': 0, 'Agente': 0, 'Cliente': 0 };

        $all('.user-card').forEach((card) => {
            total++;
            if (card.dataset.estado === 'Activo') activos++;
            porRol[card.dataset.rol] = (porRol[card.dataset.rol] || 0) + 1;
        });

        const kpis = $all('.kpi h3');
        if (kpis[0]) kpis[0].textContent = total;
        const subEl = kpiGrid.querySelector('.kpi.primary span');
        if (subEl) subEl.textContent = `${activos} activos · ${total - activos} inactivos`;

        Object.keys(porRol).forEach((rol, idx) => {
            if (kpis[idx + 1]) kpis[idx + 1].textContent = porRol[rol];
            const countBadge = document.querySelector(`[data-role-count="${rol}"]`);
            if (countBadge) countBadge.textContent = porRol[rol];
        });
    }

    /* ---------- Modal de usuario (crear / editar) ---------- */
    const modalUsuario = $('#modalUsuario');
    const formUsuario = $('#formUsuario');
    let modoEdicion = false;
    let cardEnEdicion = null;

    function limpiarErroresUsuario() {
        $('#errorUsuarioNombre').textContent = '';
        $('#errorUsuarioCorreo').textContent = '';
        $('#usuarioNombre').closest('.input-icon').classList.remove('invalid');
        $('#usuarioCorreo').closest('.input-icon').classList.remove('invalid');
    }

    function abrirModalNuevo() {
        modoEdicion = false;
        cardEnEdicion = null;
        $('#modalUsuarioTitulo').textContent = 'Nuevo Usuario';
        $('#modalUsuarioSub').textContent = 'Completa los datos para registrar un nuevo usuario en el sistema.';
        $('#btnGuardarUsuario').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Usuario';
        formUsuario.reset();
        $('#usuarioId').value = '';
        limpiarErroresUsuario();
        modalUsuario.classList.add('open');
    }

    function abrirModalEditar(card) {
        modoEdicion = true;
        cardEnEdicion = card;
        $('#modalUsuarioTitulo').textContent = 'Editar Usuario';
        $('#modalUsuarioSub').textContent = 'Actualiza los datos del usuario. Si cambias el rol, la tarjeta se moverá a la columna correspondiente.';
        $('#btnGuardarUsuario').innerHTML = '<span class="material-symbols-outlined">save</span> Guardar Cambios';

        $('#usuarioId').value = card.dataset.id;
        $('#usuarioNombre').value = card.dataset.nombre;
        $('#usuarioCorreo').value = card.dataset.correo;
        $('#usuarioDetalle').value = card.dataset.detalle;
        $('#usuarioRol').value = card.dataset.rol;
        $('#usuarioEstado').value = card.dataset.estado;

        limpiarErroresUsuario();
        modalUsuario.classList.add('open');
    }

    function cerrarModalUsuario() {
        modalUsuario.classList.remove('open');
        cardEnEdicion = null;
    }

    $('#btnNuevoUsuario').addEventListener('click', abrirModalNuevo);
    $('#modalUsuarioCerrar').addEventListener('click', cerrarModalUsuario);
    $('#btnCancelarUsuario').addEventListener('click', cerrarModalUsuario);
    modalUsuario.addEventListener('click', (e) => { if (e.target === modalUsuario) cerrarModalUsuario(); });

    function inicialesDe(nombre) {
        return nombre.trim().split(' ').slice(0, 2).map((p) => p[0] || '').join('').toUpperCase();
    }

    function crearElementoCard(data) {
        const accent = ACCENTS[data.rol] || 'gray';
        const article = document.createElement('article');
        article.className = 'user-card';
        article.dataset.id = data.id;
        article.dataset.nombre = data.nombre;
        article.dataset.correo = data.correo;
        article.dataset.rol = data.rol;
        article.dataset.estado = data.estado;
        article.dataset.detalle = data.detalle;

        article.innerHTML = `
            <div class="user-card-top">
                <div class="avatar small accent-${accent}">${inicialesDe(data.nombre)}</div>
                <div class="user-card-id">
                    <strong data-card-nombre>${data.nombre}</strong>
                    <span data-card-correo>${data.correo}</span>
                </div>
            </div>
            <p class="user-card-detalle" data-card-detalle>${data.detalle || '—'}</p>
            <div class="user-card-badges">
                <span class="${BADGE_ROL[data.rol]}" data-card-estado-badge>${data.estado}</span>
                <small data-card-fecha>Desde hoy</small>
            </div>
            <div class="user-card-actions">
                <button class="card-icon-btn" data-action="editar" title="Editar usuario">
                    <span class="material-symbols-outlined">edit</span>
                </button>
                <button class="card-icon-btn" data-action="toggle-estado" title="Activar / desactivar">
                    <span class="material-symbols-outlined">toggle_on</span>
                </button>
                <button class="card-icon-btn danger" data-action="eliminar" title="Eliminar usuario">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        `;
        return article;
    }

    function actualizarBadgeEstadoCard(card) {
        const badge = card.querySelector('[data-card-estado-badge]');
        badge.textContent = card.dataset.estado;
        badge.className = card.dataset.estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray';
    }

    formUsuario.addEventListener('submit', function (e) {
        e.preventDefault();
        limpiarErroresUsuario();

        const nombre = $('#usuarioNombre').value.trim();
        const correo = $('#usuarioCorreo').value.trim();
        const detalle = $('#usuarioDetalle').value.trim();
        const rol = $('#usuarioRol').value;
        const estado = $('#usuarioEstado').value;

        let valido = true;
        if (!nombre) {
            $('#usuarioNombre').closest('.input-icon').classList.add('invalid');
            $('#errorUsuarioNombre').textContent = 'El nombre es obligatorio.';
            valido = false;
        }
        const correoOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
        if (!correoOk) {
            $('#usuarioCorreo').closest('.input-icon').classList.add('invalid');
            $('#errorUsuarioCorreo').textContent = 'Ingresa un correo electrónico válido.';
            valido = false;
        }
        if (!valido) return;

        if (modoEdicion && cardEnEdicion) {
            const rolAnterior = cardEnEdicion.dataset.rol;

            cardEnEdicion.dataset.nombre = nombre;
            cardEnEdicion.dataset.correo = correo;
            cardEnEdicion.dataset.detalle = detalle;
            cardEnEdicion.dataset.rol = rol;
            cardEnEdicion.dataset.estado = estado;

            cardEnEdicion.querySelector('[data-card-nombre]').textContent = nombre;
            cardEnEdicion.querySelector('[data-card-correo]').textContent = correo;
            cardEnEdicion.querySelector('[data-card-detalle]').textContent = detalle || '—';
            actualizarBadgeEstadoCard(cardEnEdicion);

            const avatar = cardEnEdicion.querySelector('.avatar');
            avatar.textContent = inicialesDe(nombre);
            avatar.className = `avatar small accent-${ACCENTS[rol] || 'gray'}`;

            if (rol !== rolAnterior) {
                const nuevoBody = document.querySelector(`[data-role-body="${rol}"]`);
                nuevoBody.insertBefore(cardEnEdicion, nuevoBody.querySelector('[data-empty-column]'));
                showToast('success', 'Rol actualizado', `${nombre} se movió de ${rolAnterior} a ${rol}.`);
            } else {
                showToast('success', 'Usuario actualizado', `Los datos de ${nombre} se guardaron correctamente.`);
            }
        } else {
            nextIdSeq++;
            const nuevoId = `USR-${String(nextIdSeq).padStart(3, '0')}`;
            const nuevaCard = crearElementoCard({ id: nuevoId, nombre, correo, detalle, rol, estado });

            const body = document.querySelector(`[data-role-body="${rol}"]`);
            body.insertBefore(nuevaCard, body.querySelector('[data-empty-column]'));

            showToast('success', 'Usuario creado', `${nombre} se agregó como ${rol}.`);
        }

        recalcularContadores();
        aplicarFiltroRolYBusqueda();
        cerrarModalUsuario();
    });

    /* ---------- Acciones sobre cada card (editar / toggle estado / eliminar) ---------- */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.card-icon-btn');
        if (!btn) return;

        const card = btn.closest('.user-card');
        const accion = btn.dataset.action;
        const nombre = card.dataset.nombre;

        if (accion === 'editar') {
            abrirModalEditar(card);
        }

        if (accion === 'toggle-estado') {
            const nuevoEstado = card.dataset.estado === 'Activo' ? 'Inactivo' : 'Activo';
            card.dataset.estado = nuevoEstado;
            actualizarBadgeEstadoCard(card);
            recalcularContadores();
            aplicarFiltroRolYBusqueda();
            showToast('info', 'Estado actualizado', `${nombre} ahora está ${nuevoEstado.toLowerCase()}.`);
        }

        if (accion === 'eliminar') {
            openConfirm(
                'Eliminar usuario',
                `¿Seguro que deseas eliminar a ${nombre} del sistema? Esta acción no se puede deshacer.`,
                () => {
                    card.remove();
                    recalcularContadores();
                    aplicarFiltroRolYBusqueda();
                    showToast('success', 'Usuario eliminado', `${nombre} fue eliminado correctamente.`);
                }
            );
        }
    });

    /* ---------- Estado inicial ---------- */
    recalcularContadores();
    aplicarFiltroRolYBusqueda();

})();
