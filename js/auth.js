// Inicia sesión con usuario y contraseña
async function iniciarSesion(correo, contrasena) {
    const datos = await peticion('/api/auth/login', 'POST', { correo, password: contrasena });
    return datos;
}

// Cierra sesión del usuario actual
async function cerrarSesion() {
    await peticion('/api/auth/logout', 'POST');
    window.location.href = '/login.php';
}

// Obtiene los datos del usuario autenticado
async function obtenerUsuarioActual() {
    const datos = await peticion('/api/auth/me');
    return datos;
}

// Enlaza el botón de cerrar sesión si existe en la página
const botonCerrarSesion = document.getElementById('btn-cerrar-sesion');
if (botonCerrarSesion) {
    botonCerrarSesion.addEventListener('click', () => cerrarSesion());
}
