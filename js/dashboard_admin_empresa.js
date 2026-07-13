const botonUsuario = document.getElementById("botonUsuario");
const menuUsuario  = document.getElementById("menuUsuario");
botonUsuario.addEventListener("click", () => menuUsuario.classList.toggle("hidden"));
document.addEventListener("click", (e) => {
    if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
        menuUsuario.classList.add("hidden");
});

const tarjetas = document.querySelectorAll(".animar");
tarjetas.forEach(t => { t.style.opacity = "0"; t.style.transform = "translateY(30px)"; t.style.transition = "transform .4s ease, opacity .4s ease"; });
window.addEventListener("load", () => { tarjetas.forEach((t, i) => setTimeout(() => { t.style.opacity="1"; t.style.transform="translateY(0)"; }, i*120)); });

function colorEstado(e) {
    const m = { 'Pendiente':'bg-yellow-100 text-yellow-700','En proceso':'bg-blue-100 text-blue-700','Cerrado':'bg-green-100 text-green-700','Cancelado':'bg-red-100 text-red-700' };
    return m[e] || 'bg-gray-100 text-gray-700';
}
function colorPrioridad(p) {
    const m = { 'Alta':'text-red-600','Media':'text-orange-500','Baja':'text-blue-500' };
    return m[p] || 'text-gray-600';
}

async function cargarDatos() {
    try {
        const [resTickets, resUsuarios] = await Promise.all([
            fetch('/api/dashboard/tickets'),
            fetch('/api/dashboard/usuarios')
        ]);
        const tickets  = await resTickets.json();
        const usuarios = await resUsuarios.json();

        document.getElementById('stat-total').textContent     = tickets.pendientes + tickets.en_proceso + tickets.cerrados;
        document.getElementById('stat-cerrados').textContent  = tickets.cerrados;
        document.getElementById('stat-pendientes').textContent = tickets.pendientes;

        const tbody = document.getElementById('tabla-tickets');
        tbody.innerHTML = (tickets.recientes || []).map(t => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 font-bold">#TK-${t.id_ticket}</td>
                <td class="p-4 font-medium">${t.titulo}</td>
                <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstado(t.estado)}">${t.estado}</span></td>
                <td class="p-4 font-bold ${colorPrioridad(t.prioridad)}">${t.prioridad}</td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="p-4 text-center text-gray-400">Sin tickets</td></tr>';

        const listaUsuarios = document.getElementById('lista-usuarios');
        listaUsuarios.innerHTML = (usuarios.por_rol || []).map(r => `
            <div class="flex justify-between items-center py-2 border-b last:border-0">
                <span class="text-sm font-medium">${r.rol}</span>
                <span class="bg-[#5750ad]/10 text-[#5750ad] px-3 py-1 rounded-full text-xs font-bold">${r.total}</span>
            </div>
        `).join('');
    } catch (err) {
        console.error('Error cargando datos:', err);
    }
}

cargarDatos();