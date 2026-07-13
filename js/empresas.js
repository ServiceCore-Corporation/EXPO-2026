async function obtenerEmpresas() {
    return await peticion('/api/empresas');
}

async function obtenerEmpresa(idEmpresa) {
    return await peticion(`/api/empresas/${idEmpresa}`);
}

async function crearEmpresa(datosEmpresa) {
    return await peticion('/api/empresas', 'POST', datosEmpresa);
}

async function actualizarEmpresa(idEmpresa, datosEmpresa) {
    return await peticion(`/api/empresas/${idEmpresa}`, 'PUT', datosEmpresa);
}

async function eliminarEmpresa(idEmpresa) {
    return await peticion(`/api/empresas/${idEmpresa}`, 'DELETE');
}

// Obtiene los usuarios de una empresa
async function obtenerUsuariosDeEmpresa(idEmpresa) {
    return await peticion(`/api/empresas/${idEmpresa}/usuarios`);
}

// Obtiene los pagos de una empresa
async function obtenerPagosDeEmpresa(idEmpresa) {
    return await peticion(`/api/empresas/${idEmpresa}/pagos`);
}


function renderizarTablaEmpresas(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="5" class="sin-datos">Sin empresas</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(empresa => `
        <tr>
            <td>${empresa.id_empresa}</td>
            <td>${empresa.nombre}</td>
            <td>${empresa.correo_contacto}</td>
            <td>${empresa.telefono}</td>
            <td class="acciones">
                <button onclick="editarEmpresa(${empresa.id_empresa})">Editar</button>
                <button class="peligro" onclick="confirmarEliminarEmpresa(${empresa.id_empresa})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

async function cargarTablaEmpresas(idTbody = 'cuerpo-empresas') {
    try {
        const lista = await obtenerEmpresas();
        renderizarTablaEmpresas(lista, idTbody);
    } catch (error) {
        console.error('Error cargando empresas:', error);
    }
}

async function editarEmpresa(idEmpresa) {
    try {
        const empresa = await obtenerEmpresa(idEmpresa);
        document.getElementById('empresa-id').value      = empresa.id_empresa;
        document.getElementById('empresa-nombre').value  = empresa.nombre;
        document.getElementById('empresa-correo').value  = empresa.correo_contacto;
        document.getElementById('empresa-telefono').value = empresa.telefono;
        document.getElementById('empresa-estado').value  = empresa.estado;
        document.getElementById('titulo-formulario-empresa').textContent = 'Editar Empresa';
    } catch (error) {
        mostrarMensaje('msg-empresas', 'Error al cargar empresa.', true);
    }
}

async function confirmarEliminarEmpresa(idEmpresa) {
    if (!confirm('¿Eliminar esta empresa?')) return;
    try {
        await eliminarEmpresa(idEmpresa);
        mostrarMensaje('msg-empresas', 'Empresa eliminada.');
        cargarTablaEmpresas();
    } catch (error) {
        mostrarMensaje('msg-empresas', error.message, true);
    }
}

const formularioEmpresa = document.getElementById('formulario-empresa');
if (formularioEmpresa) {
    formularioEmpresa.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idEmpresa    = document.getElementById('empresa-id').value;
        const datosEmpresa = {
            nombre:           document.getElementById('empresa-nombre').value.trim(),
            correo_contacto:  document.getElementById('empresa-correo').value.trim(),
            telefono:         document.getElementById('empresa-telefono').value.trim(),
            estado:           parseInt(document.getElementById('empresa-estado').value)
        };

        try {
            if (idEmpresa) {
                await actualizarEmpresa(idEmpresa, datosEmpresa);
                mostrarMensaje('msg-empresas', 'Empresa actualizada.');
            } else {
                await crearEmpresa(datosEmpresa);
                mostrarMensaje('msg-empresas', 'Empresa creada.');
            }
            limpiarFormulario('formulario-empresa');
            document.getElementById('empresa-id').value = '';
            document.getElementById('titulo-formulario-empresa').textContent = 'Nueva Empresa';
            cargarTablaEmpresas();
        } catch (error) {
            mostrarMensaje('msg-empresas', error.message, true);
        }
    });
}
