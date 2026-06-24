// Obtiene todos los usuarios
async function obtenerUsuarios() {
    return await peticion('/api/usuarios');
}

// Obtiene un usuario por id
async function obtenerUsuario(idUsuario) {
    return await peticion(`/api/usuarios/${idUsuario}`);
}

// Crea un nuevo usuario
async function crearUsuario(datosUsuario) {
    return await peticion('/api/usuarios', 'POST', datosUsuario);
}

// Actualiza un usuario
async function actualizarUsuario(idUsuario, datosUsuario) {
    return await peticion(`/api/usuarios/${idUsuario}`, 'PUT', datosUsuario);
}

// Elimina un usuario
async function eliminarUsuario(idUsuario) {
    return await peticion(`/api/usuarios/${idUsuario}`, 'DELETE');
}

// Activa o desactiva un usuario (activo: 1 o 0)
async function cambiarEstadoUsuario(idUsuario, activo) {
    return await peticion(`/api/usuarios/${idUsuario}/estado`, 'PATCH', { activo });
}

// Obtiene usuarios de una empresa
async function obtenerUsuariosPorEmpresa(idEmpresa) {
    return await peticion(`/api/usuarios/empresa/${idEmpresa}`);
}

// Obtiene usuarios por rol
async function obtenerUsuariosPorRol(idRol) {
    return await peticion(`/api/usuarios/rol/${idRol}`);
}

// ── Renderizado ───────────────────────────────────────────────

// Pinta la lista de usuarios en el tbody indicado
function renderizarTablaUsuarios(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="6" class="sin-datos">Sin usuarios</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(usuario => `
        <tr>
            <td>${usuario.id_usuario}</td>
            <td>${usuario.nombre}</td>
            <td>${usuario.correo}</td>
            <td>${usuario.rol}</td>
            <td><span class="badge ${usuario.activo == 1 ? 'activo' : 'inactivo'}">${usuario.activo == 1 ? 'Activo' : 'Inactivo'}</span></td>
            <td class="acciones">
                <button onclick="editarUsuario(${usuario.id_usuario})">Editar</button>
                <button onclick="cambiarEstadoUsuario(${usuario.id_usuario}, ${usuario.activo == 1 ? 0 : 1}).then(() => cargarTablaUsuarios())">
                    ${usuario.activo == 1 ? 'Desactivar' : 'Activar'}
                </button>
                <button class="peligro" onclick="confirmarEliminarUsuario(${usuario.id_usuario})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

// Carga y pinta los usuarios
async function cargarTablaUsuarios(idTbody = 'cuerpo-usuarios') {
    try {
        const lista = await obtenerUsuarios();
        renderizarTablaUsuarios(lista, idTbody);
    } catch (error) {
        console.error('Error cargando usuarios:', error);
    }
}

// Carga los datos de un usuario en el formulario para editar
async function editarUsuario(idUsuario) {
    try {
        const usuario = await obtenerUsuario(idUsuario);
        document.getElementById('usuario-id').value      = usuario.id_usuario;
        document.getElementById('usuario-nombre').value  = usuario.nombre;
        document.getElementById('usuario-correo').value  = usuario.correo;
        document.getElementById('usuario-rol').value     = usuario.id_rol;
        document.getElementById('usuario-empresa').value = usuario.id_empresa;
        document.getElementById('titulo-formulario-usuario').textContent = 'Editar Usuario';
    } catch (error) {
        mostrarMensaje('msg-usuarios', 'Error al cargar usuario.', true);
    }
}

// Confirma y elimina un usuario
async function confirmarEliminarUsuario(idUsuario) {
    if (!confirm('¿Eliminar este usuario?')) return;
    try {
        await eliminarUsuario(idUsuario);
        mostrarMensaje('msg-usuarios', 'Usuario eliminado.');
        cargarTablaUsuarios();
    } catch (error) {
        mostrarMensaje('msg-usuarios', error.message, true);
    }
}

// Maneja el envío del formulario de usuario
const formularioUsuario = document.getElementById('formulario-usuario');
if (formularioUsuario) {
    formularioUsuario.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idUsuario    = document.getElementById('usuario-id').value;
        const datosUsuario = {
            nombre:     document.getElementById('usuario-nombre').value.trim(),
            correo:     document.getElementById('usuario-correo').value.trim(),
            password:   document.getElementById('usuario-password')?.value.trim(),
            id_rol:     parseInt(document.getElementById('usuario-rol').value),
            id_empresa: parseInt(document.getElementById('usuario-empresa').value)
        };

        try {
            if (idUsuario) {
                await actualizarUsuario(idUsuario, datosUsuario);
                mostrarMensaje('msg-usuarios', 'Usuario actualizado.');
            } else {
                await crearUsuario(datosUsuario);
                mostrarMensaje('msg-usuarios', 'Usuario creado.');
            }
            limpiarFormulario('formulario-usuario');
            document.getElementById('usuario-id').value = '';
            document.getElementById('titulo-formulario-usuario').textContent = 'Nuevo Usuario';
            cargarTablaUsuarios();
        } catch (error) {
            mostrarMensaje('msg-usuarios', error.message, true);
        }
    });
}
