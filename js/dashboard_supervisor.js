const botonUsuario = document.getElementById("botonUsuario");
const menuUsuario  = document.getElementById("menuUsuario");
botonUsuario.addEventListener("click", () => menuUsuario.classList.toggle("hidden"));
document.addEventListener("click", (e) => {
    if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
        menuUsuario.classList.add("hidden");
});

const tarjetas = document.querySelectorAll(".animar");
tarjetas.forEach(t => { t.style.opacity="0"; t.style.transform="translateY(30px)"; t.style.transition="transform .4s ease, opacity .4s ease"; });
window.addEventListener("load", () => { tarjetas.forEach((t,i) => setTimeout(() => { t.style.opacity="1"; t.style.transform="translateY(0)"; }, i*120)); });

function colorEstado(e) {
    const m = {'Pendiente':'bg-yellow-100 text-yellow-700','En proceso':'bg-blue-100 text-blue-700','Cerrado':'bg-green-100 text-green-700','Cancelado':'bg-red-100 text-red-700'};
    return m[e] || 'bg-gray-100 text-gray-700';
}
function colorPrioridad(p) {
    const m = {'Alta':'text-red-600','Media':'text-orange-500','Baja':'text-blue-500'};
    return m[p] || 'text-gray-600';
}

async function cargarDatos() {
    try {
        const [resTickets, resAsig] = await Promise.all([
            fetch('/api/dashboard/tickets'),
            fetch('/api/asignaciones/supervisor/<?= $idUsuario ?>')
        ]);
        const tickets = await resTickets.json();
        const asig    = await resAsig.json();

        document.getElementById('stat-pendientes').textContent = tickets.pendientes;
        document.getElementById('stat-proceso').textContent    = tickets.en_proceso;
        document.getElementById('stat-cerrados').textContent   = tickets.cerrados;
        document.getElementById('stat-total').textContent      = tickets.pendientes + tickets.en_proceso + tickets.cerrados;

        const tbody = document.getElementById('tabla-tickets');
        tbody.innerHTML = (tickets.recientes || []).map(t => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-5 font-bold">#TK-${t.id_ticket}</td>
                <td class="p-5"><p class="font-bold">${t.titulo}</p><p class="text-sm text-gray-500">${t.cliente || '—'}</p></td>
                <td class="p-5"><span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstado(t.estado)}">${t.estado}</span></td>
                <td class="p-5 font-bold ${colorPrioridad(t.prioridad)}">${t.prioridad}</td>
                <td class="p-5">
                    <button class="hover:scale-110 transition" onclick="window.ChatWidgetAprovador && window.ChatWidgetAprovador.abrir(${t.id_ticket})">
                        <span class="material-symbols-outlined text-[#5750ad]">chat</span>
                    </button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="p-5 text-center text-gray-400">Sin tickets</td></tr>';

        const listaAsig = document.getElementById('lista-asignaciones');
        listaAsig.innerHTML = (Array.isArray(asig) ? asig : []).slice(0,5).map(a => `
            <div class="flex justify-between items-center py-2 border-b last:border-0">
                <div>
                    <p class="font-medium text-sm">${a.ticket || '—'}</p>
                    <p class="text-xs text-gray-500">Agente: ${a.agente || '—'}</p>
                </div>
            </div>
        `).join('') || '<p class="text-gray-400 text-sm">Sin asignaciones</p>';
    } catch (err) {
        console.error('Error:', err);
    }
}

cargarDatos();
