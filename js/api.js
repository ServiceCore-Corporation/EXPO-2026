async function peticion(url, metodo = 'GET', cuerpo = null) {

    const opciones = {
        method: metodo,
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (cuerpo) {
        opciones.body = JSON.stringify(cuerpo);
    }

    const respuesta = await fetch(url, opciones);

    const tipo = respuesta.headers.get('content-type') || '';

    if (!tipo.includes('application/json')) {
        const texto = await respuesta.text();

        throw new Error(
            respuesta.status === 404
                ? 'Endpoint no encontrado'
                : texto.substring(0, 200)
        );
    }

    const datos = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(datos.error || 'Error del servidor');
    }

    return datos;
}