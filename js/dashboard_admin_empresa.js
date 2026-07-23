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
// Los datos del dashboard (tickets y usuarios de la empresa) ahora se
// renderizan directamente desde PHP en dashboard_admin_emp.php, ya que
// el endpoint global /api/dashboard no está disponible para este rol
// y mostraba datos de todas las empresas en lugar de solo la propia.