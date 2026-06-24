// Función base para todas las peticiones al API
async function peticion(url, metodo = 'GET', cuerpo = null) {
    const opciones = {
        method: metodo,
        headers: { 'Content-Type': 'application/json' }
    };

    // Agregar cuerpo solo si hay datos
    if (cuerpo) opciones.body = JSON.stringify(cuerpo);

    const respuesta = await fetch(url, opciones);
    const datos     = await respuesta.json();

    // Si el servidor responde con error lanzar excepción
    if (!respuesta.ok) throw new Error(datos.error || 'Error en el servidor');

    return datos;
}

// Muestra un mensaje temporal en el elemento indicado
function mostrarMensaje(idElemento, texto, esError = false) {
    const elemento = document.getElementById(idElemento);
    if (!elemento) return;
    elemento.textContent = texto;
    elemento.className   = esError ? 'mensaje error' : 'mensaje exito';
    setTimeout(() => { elemento.textContent = ''; elemento.className = 'mensaje'; }, 3500);
}

// Limpia todos los inputs de un formulario
function limpiarFormulario(idFormulario) {
    document.getElementById(idFormulario).reset();
}
