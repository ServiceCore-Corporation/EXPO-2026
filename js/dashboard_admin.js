const botonUsuario = document.getElementById("botonUsuario");
const menuUsuario  = document.getElementById("menuUsuario");
if (botonUsuario && menuUsuario) {
    botonUsuario.addEventListener("click", () => menuUsuario.classList.toggle("hidden"));
    document.addEventListener("click", (e) => {
        if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
            menuUsuario.classList.add("hidden");
    });
}

const tarjetas = document.querySelectorAll(".animar");
tarjetas.forEach(t => {
    t.style.opacity = "0";
    t.style.transform = "translateY(30px)";
    t.style.transition = "transform .4s ease, opacity .4s ease";
});
window.addEventListener("load", () => {
    tarjetas.forEach((t, i) => {
        setTimeout(() => { t.style.opacity = "1"; t.style.transform = "translateY(0)"; }, i * 120);
    });
});

function colorEstadoEmpresa(estado) {
    return estado === 'Activo' || estado === 1 || estado === '1'
        ? 'bg-green-100 text-green-700'
        : 'bg-gray-200 text-gray-600';
}

function colorRol(rol) {
    const mapa = {
        'Admin ServiceCore': 'bg-purple-100 text-purple-700',
        'Admin Empresa':     'bg-blue-100 text-blue-700',
        'Supervisor':        'bg-orange-100 text-orange-700',
        'Agente':            'bg-teal-100 text-teal-700',
        'Cliente':           'bg-yellow-100 text-yellow-700',
    };
    return mapa[rol] || 'bg-gray-100 text-gray-700';
}

async function cargarDashboardEmpresas() {
    // Tarjetas de resumen de empresas y usuarios (solo en dashboard_admin.php).
    if (!document.getElementById('stat-empresas')) return;
    try {
        const [resEmp, resUsu] = await Promise.all([
            fetch('/api/dashboard/empresas'),
            fetch('/api/dashboard/usuarios'),
        ]);
        const empresas = await resEmp.json();
        const usuarios = await resUsu.json();

        document.getElementById('stat-empresas').textContent = empresas.total ?? 0;
        document.getElementById('stat-empresas-activas').textContent = empresas.activas ?? 0;
        document.getElementById('stat-usuarios').textContent = usuarios.total ?? 0;
        document.getElementById('stat-usuarios-activos').textContent = usuarios.activos ?? 0;

        const tbody = document.getElementById('tabla-empresas');
        if (tbody) {
            if (!empresas.recientes || empresas.recientes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="p-5 text-center text-gray-400">Sin empresas registradas</td></tr>';
            } else {
                tbody.innerHTML = empresas.recientes.map(e => `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-5 font-bold">${e.nombre}</td>
                        <td class="p-5 text-gray-600">${e.correo_contacto || '—'}</td>
                        <td class="p-5">${e.usuarios ?? 0}</td>
                        <td class="p-5">
                            <span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstadoEmpresa(e.estado)}">
                                ${(e.estado == 1) ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                    </tr>
                `).join('');
            }
        }

        const contenedorRoles = document.getElementById('usuarios-por-rol');
        if (contenedorRoles) {
            contenedorRoles.innerHTML = (usuarios.por_rol || []).map(r => `
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium">${r.rol}</span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold ${colorRol(r.rol)}">${r.total}</span>
                </div>
            `).join('');
        }
    } catch (err) {
        console.error('Error cargando dashboard de empresas:', err);
    }
}

cargarDashboardEmpresas();