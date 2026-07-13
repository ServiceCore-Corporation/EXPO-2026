const botonUsuario = document.getElementById("botonUsuario");
const menuUsuario  = document.getElementById("menuUsuario");
botonUsuario.addEventListener("click", () => menuUsuario.classList.toggle("hidden"));
document.addEventListener("click", (e) => {
    if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
        menuUsuario.classList.add("hidden");
});

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

function colorEstado(estado) {
    const mapa = {
        'Pendiente':   'bg-yellow-100 text-yellow-700',
        'En proceso':  'bg-blue-100 text-blue-700',
        'Cerrado':     'bg-green-100 text-green-700',
        'Cancelado':   'bg-red-100 text-red-700',
    };
    return mapa[estado] || 'bg-gray-100 text-gray-700';
}

function colorPrioridad(prioridad) {
    const mapa = { 'Alta': 'text-red-600', 'Media': 'text-orange-500', 'Baja': 'text-blue-500' };
    return mapa[prioridad] || 'text-gray-600';
}

async function cargarDashboard() {
    try {
        const res  = await fetch('/api/dashboard');
        const data = await res.json();

        document.getElementById('stat-tickets').textContent  = data.resumen.tickets;
        document.getElementById('stat-usuarios').textContent = data.resumen.usuarios;
        document.getElementById('stat-empresas').textContent = data.resumen.empresas;
        document.getElementById('stat-ingresos').textContent = '$' + parseFloat(data.resumen.ingresos).toLocaleString();

        const contenedorEstados = document.getElementById('estado-tickets');
        contenedorEstados.innerHTML = data.tickets_por_estado.map(e => `
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium">${e.estado}</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstado(e.estado)}">${e.total}</span>
            </div>
        `).join('');
    } catch (err) {
        console.error('Error cargando dashboard:', err);
    }
}

async function cargarTickets() {
    try {
        const res     = await fetch('/api/dashboard/tickets');
        const data    = await res.json();
        const tbody   = document.getElementById('tabla-tickets');

        if (!data.recientes || data.recientes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-5 text-center text-gray-400">Sin tickets</td></tr>';
            return;
        }

        tbody.innerHTML = data.recientes.map(t => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-5 font-bold">#TK-${t.id_ticket}</td>
                <td class="p-5">
                    <p class="font-bold">${t.titulo}</p>
                    <p class="text-sm text-gray-500">${t.cliente || '—'}</p>
                </td>
                <td class="p-5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstado(t.estado)}">${t.estado}</span>
                </td>
                <td class="p-5 font-bold ${colorPrioridad(t.prioridad)}">${t.prioridad}</td>
            </tr>
        `).join('');
    } catch (err) {
        console.error('Error cargando tickets:', err);
    }
}

cargarDashboard();
cargarTickets();