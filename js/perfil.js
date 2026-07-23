(function () {
    'use strict';

    /* ---------- Utilidades ---------- */
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
            <div>
                <strong>${title}</strong>
                <p>${message}</p>
            </div>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 200);
        }, 4000);
    }

    /* ---------- Modal de confirmación reutilizable ---------- */
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
    modalConfirmar.addEventListener('click', function (e) {
        if (e.target === modalConfirmar) closeConfirm();
    });

    /* ---------- Sidebar móvil ---------- */
    const sidebar = $('#sidebar');
    const btnSidebar = $('#btnSidebar');
    if (btnSidebar) {
        btnSidebar.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    /* ---------- Menú de usuario en topbar ---------- */
    const profileBtn = $('#profileBtn');
    const userMenu = $('#userMenu');

    if (profileBtn) {
        profileBtn.addEventListener('click', function (event) {
            const isMenuLink = event.target.closest('.user-option');
            if (isMenuLink) return;
            this.classList.toggle('open');
        });
    }

    document.addEventListener('click', function (event) {
        if (!profileBtn || !userMenu) return;
        if (!profileBtn.contains(event.target)) {
            profileBtn.classList.remove('open');
        }
    });

    if (userMenu) {
        userMenu.querySelectorAll('.user-option').forEach((link) => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                profileBtn.classList.remove('open');

                const action = this.dataset.action;
                if (action === 'logout') {
                    openConfirm(
                        'Cerrar sesión',
                        '¿Estás seguro de que deseas cerrar tu sesión actual?',
                        () => {
                            showToast('info', 'Cerrando sesión', 'Tu sesión se está cerrando de forma segura...');
                        }
                    );
                    return;
                }

                let targetSection = null;
                if (action === 'settings') {
                    targetSection = document.getElementById('tarjetaPreferencias');
                } else if (action === 'perfil') {
                    targetSection = document.getElementById('tarjetaInformacionPersonal');
                }

                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    showToast('success', 'Navegando', `Abriendo ${this.textContent.trim()}...`);
                } else {
                    showToast('info', 'Funcionalidad', 'Opción seleccionada.');
                }
            });
        });
    }

    /* ---------- Vista previa de fotografía ---------- */
    const fotoInput = $('#fotoInput');
    const fotoPreview = $('#fotoPreview');
    const fotoPlaceholder = $('#fotoPlaceholder');
    const btnCambiarFoto = $('#btnCambiarFoto');
    const resumenAvatar = $('#resumenAvatar');

    btnCambiarFoto.addEventListener('click', () => fotoInput.click());

    fotoInput.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;

        if (!file.type.match(/^image\/(png|jpeg|webp)$/)) {
            showToast('error', 'Formato no válido', 'Selecciona una imagen en formato JPG, PNG o WEBP.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            showToast('error', 'Archivo muy grande', 'El tamaño máximo permitido es 2 MB.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            fotoPreview.src = e.target.result;
            fotoPreview.style.display = 'block';
            fotoPlaceholder.style.display = 'none';
            if (resumenAvatar) {
                resumenAvatar.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
            }
            const botonUsuario = document.getElementById('botonUsuario');
            if (botonUsuario) {
                botonUsuario.innerHTML = `<img src="${e.target.result}" alt="Foto de perfil" class="w-full h-full object-cover">`;
            }
        };
        reader.readAsDataURL(file);

        subirFoto(file);
    });

    async function subirFoto(file) {
        const formData = new FormData();
        formData.append('foto', file);
        try {
            const res = await fetch('/api/perfil/foto', {
                method: 'POST',
                credentials: 'include',
                body: formData,
            });
            const datos = await res.json();
            if (!res.ok) throw new Error(datos.error || 'No se pudo subir la imagen');
            showToast('success', 'Foto actualizada', 'Tu fotografía de perfil se guardó correctamente.');
        } catch (error) {
            console.error('[perfil.js] Error subiendo foto:', error);
            showToast('error', 'No se pudo subir la foto', error.message || 'Ocurrió un error al subir la imagen.');
        }
    }

    /* ---------- Mostrar / ocultar contraseña ---------- */
    $all('.toggle-pass').forEach((btn) => {
        btn.addEventListener('click', function () {
            const target = document.getElementById(this.dataset.target);
            const icon = this.querySelector('.material-symbols-outlined');
            if (target.type === 'password') {
                target.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                target.type = 'password';
                icon.textContent = 'visibility';
            }
        });
    });

    /* ---------- Validaciones genéricas ---------- */
    const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const regexTelefono = /^[0-9+\s()-]{7,20}$/;

    function marcarCampo(input, errorEl, esValido, mensaje) {
        const wrapper = input.closest('.input-icon');
        wrapper.classList.remove('valid', 'invalid');
        wrapper.classList.add(esValido ? 'valid' : 'invalid');
        if (errorEl) errorEl.textContent = esValido ? '' : mensaje;
        return esValido;
    }

    /* ---------- Formulario: Información Personal ---------- */
    const formPerfil = $('#formPerfil');

    function validarFormPerfil() {
        let valido = true;

        const campos = [
            ['campoNombre', 'errorNombre', 'El nombre es obligatorio.'],
            ['campoApellidos', 'errorApellidos', 'Los apellidos son obligatorios.'],
            ['campoEmpresa', 'errorEmpresa', 'La empresa es obligatoria.'],
            ['campoDepartamento', 'errorDepartamento', 'El departamento es obligatorio.'],
            ['campoCargo', 'errorCargo', 'El cargo es obligatorio.'],
        ];

        campos.forEach(([id, errId, msg]) => {
            const input = $('#' + id);
            const ok = input.value.trim().length > 0;
            if (!marcarCampo(input, $('#' + errId), ok, msg)) valido = false;
        });

        const correo = $('#campoCorreo');
        const correoOk = regexCorreo.test(correo.value.trim());
        if (!marcarCampo(correo, $('#errorCorreo'), correoOk, 'Ingresa un correo electrónico válido.')) valido = false;

        const telefono = $('#campoTelefono');
        const telOk = regexTelefono.test(telefono.value.trim());
        if (!marcarCampo(telefono, $('#errorTelefono'), telOk, 'Ingresa un número de teléfono válido.')) valido = false;

        return valido;
    }

    formPerfil.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!validarFormPerfil()) {
            showToast('error', 'Revisa el formulario', 'Algunos campos de tu información personal no son válidos.');
            return;
        }
        openConfirm(
            'Guardar cambios',
            '¿Deseas guardar los cambios realizados en tu información personal?',
            guardarPerfil
        );
    });

    async function guardarPerfil() {
        const btn = $('#btnGuardarCambios');
        const payload = {
            nombre: $('#campoNombre').value.trim(),
            apellidos: $('#campoApellidos').value.trim(),
            correo: $('#campoCorreo').value.trim(),
            telefono: $('#campoTelefono').value.trim(),
            departamento: $('#campoDepartamento').value.trim(),
            cargo: $('#campoCargo').value.trim(),
            direccion: $('#campoDireccion').value.trim(),
        };

        if (btn) btn.disabled = true;
        try {
            await peticion('/api/perfil', 'PUT', payload);

            const nombreCompleto = `${payload.nombre} ${payload.apellidos}`.trim();
            const resumenNombre = $('#resumenNombre');
            if (resumenNombre) resumenNombre.textContent = nombreCompleto;

            showToast('success', 'Cambios guardados', 'Tu información personal se actualizó correctamente.');
        } catch (error) {
            console.error('[perfil.js] Error guardando perfil:', error);
            showToast('error', 'No se pudo guardar', error.message || 'Ocurrió un error al guardar tus datos.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    /* ---------- Formulario: Seguridad de la Cuenta ---------- */
    const passActual = $('#passActual');
    const passNueva = $('#passNueva');
    const passConfirmar = $('#passConfirmar');
    const strengthFill = $('#strengthFill');
    const strengthLabel = $('#strengthLabel');
    const passChecklist = $('#passChecklist');

    function evaluarReglas(valor) {
        return {
            length: valor.length >= 8,
            mayus: /[A-Z]/.test(valor),
            minus: /[a-z]/.test(valor),
            numero: /[0-9]/.test(valor),
            especial: /[^A-Za-z0-9]/.test(valor),
        };
    }

    function actualizarChecklist(reglas) {
        Object.keys(reglas).forEach((regla) => {
            const li = passChecklist.querySelector(`[data-rule="${regla}"]`);
            const icon = li.querySelector('.material-symbols-outlined');
            if (reglas[regla]) {
                li.classList.add('met');
                icon.textContent = 'check_circle';
            } else {
                li.classList.remove('met');
                icon.textContent = 'radio_button_unchecked';
            }
        });
    }

    function actualizarFortaleza(valor) {
        const reglas = evaluarReglas(valor);
        actualizarChecklist(reglas);

        const cumplidas = Object.values(reglas).filter(Boolean).length;
        let porcentaje = 0;
        let color = 'var(--red)';
        let texto = 'Seguridad de la contraseña';

        if (valor.length === 0) {
            porcentaje = 0;
            texto = 'Seguridad de la contraseña';
        } else if (cumplidas <= 2) {
            porcentaje = 25; color = '#dc2626'; texto = 'Débil';
        } else if (cumplidas === 3) {
            porcentaje = 50; color = '#d97706'; texto = 'Media';
        } else if (cumplidas === 4) {
            porcentaje = 75; color = '#2563eb'; texto = 'Buena';
        } else {
            porcentaje = 100; color = '#16a34a'; texto = 'Muy segura';
        }

        strengthFill.style.width = porcentaje + '%';
        strengthFill.style.background = color;
        strengthLabel.textContent = texto;

        return reglas;
    }

    passNueva.addEventListener('input', function () {
        actualizarFortaleza(this.value);
        validarCoincidencia();
    });

    function validarCoincidencia() {
        if (!passConfirmar.value) return null;
        const coincide = passNueva.value === passConfirmar.value;
        marcarCampo(
            passConfirmar,
            $('#errorPassConfirmar'),
            coincide,
            'Las contraseñas no coinciden.'
        );
        return coincide;
    }

    passConfirmar.addEventListener('input', validarCoincidencia);

    const formSeguridad = $('#formSeguridad');

    formSeguridad.addEventListener('submit', function (e) {
        e.preventDefault();
        let valido = true;

        const actualOk = passActual.value.trim().length > 0;
        if (!marcarCampo(passActual, $('#errorPassActual'), actualOk, 'Ingresa tu contraseña actual.')) valido = false;

        const reglas = actualizarFortaleza(passNueva.value);
        const reglasOk = Object.values(reglas).every(Boolean);
        marcarCampo(passNueva, null, reglasOk, '');
        if (!reglasOk) valido = false;

        const coincide = validarCoincidencia();
        if (!passConfirmar.value.trim()) {
            marcarCampo(passConfirmar, $('#errorPassConfirmar'), false, 'Confirma tu nueva contraseña.');
            valido = false;
        } else if (!coincide) {
            valido = false;
        }

        if (!valido) {
            showToast('error', 'Revisa la contraseña', 'Verifica los requisitos de seguridad y que las contraseñas coincidan.');
            return;
        }

        openConfirm(
            'Actualizar contraseña',
            '¿Confirmas que deseas actualizar la contraseña de tu cuenta?',
            actualizarPassword
        );
    });

    async function actualizarPassword() {
        const btn = $('#btnActualizarPassword');
        if (btn) btn.disabled = true;
        try {
            await peticion('/api/perfil/password', 'PATCH', {
                passActual: passActual.value,
                passNueva: passNueva.value,
            });

            formSeguridad.reset();
            strengthFill.style.width = '0%';
            strengthLabel.textContent = 'Seguridad de la contraseña';
            actualizarChecklist({ length: false, mayus: false, minus: false, numero: false, especial: false });
            $all('.input-icon').forEach((w) => w.classList.remove('valid', 'invalid'));
            showToast('success', 'Contraseña actualizada', 'Tu contraseña se actualizó correctamente.');
        } catch (error) {
            console.error('[perfil.js] Error actualizando contraseña:', error);
            marcarCampo(passActual, $('#errorPassActual'), false, error.message || 'No se pudo actualizar la contraseña.');
            showToast('error', 'No se pudo actualizar', error.message || 'Verifica tu contraseña actual e intenta de nuevo.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    /* ---------- Preferencias del Usuario ---------- */
    let temaSeleccionado = 'claro';

    $all('.theme-option').forEach((btn) => {
        btn.addEventListener('click', function () {
            $all('.theme-option').forEach((b) => b.classList.remove('active'));
            this.classList.add('active');
            temaSeleccionado = this.dataset.theme;
        });
    });

    const formPreferencias = $('#formPreferencias');

    formPreferencias.addEventListener('submit', function (e) {
        e.preventDefault();
        showToast('success', 'Preferencias guardadas', `Idioma, tema (${temaSeleccionado}) y notificaciones actualizados.`);
    });

    /* ---------- Cerrar sesión desde el menú de usuario ---------- */
    $all('#btnTopCerrarSesion').forEach((btn) => {
        if (!btn) return;
        btn.addEventListener('click', () => {
            openConfirm(
                'Cerrar sesión',
                '¿Estás seguro de que deseas cerrar tu sesión actual?',
                () => {
                    showToast('info', 'Cerrando sesión', 'Tu sesión se está cerrando de forma segura...');
                }
            );
        });
    });

})();
