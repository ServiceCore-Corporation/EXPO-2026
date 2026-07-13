/**
 * js/admin_reportes.js
 * -------------------------------------------------------------------------
 * Controla el dashboard de reportes.php:
 *   1. Lee los filtros del formulario.
 *   2. Pide los datos a php/api_datos_reporte.php (fetch/AJAX).
 *   3. Pinta las tarjetas de métricas, las 4 gráficas (Chart.js) y la tabla.
 *   4. Arma el enlace de descarga hacia php/generar_pdf.php con esos
 *      mismos filtros, para que el PDF coincida exactamente con lo que
 *      el usuario está viendo en pantalla.
 *
 * IMPORTANTE sobre rutas: este archivo asume que vive en /js/ al mismo
 * nivel que /php/ (ver estructura de carpetas entregada). Ajusta
 * RUTA_API y RUTA_PDF si tu proyecto organiza las carpetas distinto.
 * -------------------------------------------------------------------------
 */

(function () {
    'use strict';

    // ---- Rutas de los endpoints PHP (relativas a vistas/Admin/reportes.php) ----
    const RUTA_API = 'php/api_datos_reporte.php';
    const RUTA_PDF = 'php/generar_pdf.php';

    // ---- Referencias a instancias de Chart.js, para poder destruirlas
    //      antes de redibujar cuando cambian los filtros (si no, Chart.js
    //      va apilando gráficas fantasma encima de la anterior) ----
    let instanciaGraficaEstado    = null;
    let instanciaGraficaPrioridad = null;
    let instanciaGraficaTendencia = null;
    let instanciaGraficaAgentes   = null;

    // Paleta de marca ServiceCore, reutilizada en todas las gráficas
    const PALETA = {
        ink:        '#1e1858',
        royal:      '#5750ad',
        periwinkle: '#7773eb',
        mist:       '#dfe7f4',
        exito:      '#16a34a',
        alerta:     '#e9a23b',
        peligro:    '#dc2626',
        neutro:     '#9ca3af',
    };

    // ---------- Utilidades ----------

    /** Lee el estado actual de los filtros del formulario. */
    function leerFiltros() {
        return {
            fecha_inicio: document.getElementById('filtroFechaInicio').value,
            fecha_fin:    document.getElementById('filtroFechaFin').value,
            estado:       document.getElementById('filtroEstado').value,
            prioridad:    document.getElementById('filtroPrioridad').value,
        };
    }

    /** Convierte un objeto de filtros en un query string, ej. "?a=1&b=2". */
    function construirQueryString(filtros) {
        const params = new URLSearchParams();
        Object.entries(filtros).forEach(([clave, valor]) => {
            if (valor !== '' && valor !== null && valor !== undefined) {
                params.append(clave, valor);
            }
        });
        return params.toString();
    }

    function formatearFecha(fechaISO) {
        const f = new Date(fechaISO.replace(' ', 'T'));
        if (isNaN(f)) return fechaISO;
        return f.toLocaleDateString('es-GT', { day: '2-digit', month: '2-digit', year: 'numeric' })
            + ' ' + f.toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' });
    }

    // ---------- Carga de datos ----------

    /**
     * Pide los datos al backend con los filtros actuales y repinta
     * toda la vista (métricas + gráficas + tabla).
     */
    async function cargarDatosReporte() {
        const filtros = leerFiltros();
        const qs = construirQueryString(filtros);

        try {
            const respuesta = await fetch(`${RUTA_API}?${qs}`, { credentials: 'include' });
            const datos = await respuesta.json();
            if (!respuesta.ok) {
                throw new Error(datos.error || 'Error al pedir el reporte');
            }

            renderMetricas(datos.metricas);
            renderGraficas(datos.graficas);
            renderTablaTickets(datos.tickets);
        } catch (error) {
            console.error('No se pudo cargar el reporte:', error);
            alert('No se pudo cargar el reporte: ' + error.message);
        }
    }

    // ---------- Render: tarjetas de métricas ----------

    function renderMetricas(metricas) {
        // Cada tarjeta tiene data-metrica="clave" que coincide con las
        // claves que devuelve obtenerMetricas() en el backend.
        document.querySelectorAll('[data-metrica]').forEach((el) => {
            const clave = el.dataset.metrica;
            const valor = metricas[clave];

            if (clave === 'cumplimiento_sla_pct') {
                el.textContent = `${Number(valor).toFixed(1)}%`;
            } else if (clave === 'tiempo_promedio_horas') {
                el.textContent = `${Number(valor).toFixed(1)}h`;
            } else {
                el.textContent = valor;
            }
        });
    }

    // ---------- Render: las 4 gráficas ----------

    function renderGraficas(graficas) {
        renderGraficaEstado(graficas.por_estado);
        renderGraficaPrioridad(graficas.por_prioridad);
        renderGraficaTendencia(graficas.tendencia);
        renderGraficaAgentes(graficas.por_agente);
    }

    function renderGraficaEstado(porEstado) {
        const ctx = document.getElementById('graficaEstado');
        const etiquetas = Object.keys(porEstado).map(e => e.charAt(0).toUpperCase() + e.slice(1));
        const valores = Object.values(porEstado);

        if (instanciaGraficaEstado) instanciaGraficaEstado.destroy();
        instanciaGraficaEstado = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: etiquetas,
                datasets: [{
                    data: valores,
                    backgroundColor: [PALETA.peligro, PALETA.alerta, PALETA.exito, PALETA.neutro],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
            },
        });
    }

    function renderGraficaPrioridad(porPrioridad) {
        const ctx = document.getElementById('graficaPrioridad');
        const etiquetas = Object.keys(porPrioridad).map(p => p.charAt(0).toUpperCase() + p.slice(1));
        const valores = Object.values(porPrioridad);

        if (instanciaGraficaPrioridad) instanciaGraficaPrioridad.destroy();
        instanciaGraficaPrioridad = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: etiquetas,
                datasets: [{
                    data: valores,
                    backgroundColor: PALETA.royal,
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    function renderGraficaTendencia(tendencia) {
        const ctx = document.getElementById('graficaTendencia');

        if (instanciaGraficaTendencia) instanciaGraficaTendencia.destroy();
        instanciaGraficaTendencia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: tendencia.dias,
                datasets: [
                    {
                        label: 'Creados',
                        data: tendencia.creados,
                        borderColor: PALETA.royal,
                        backgroundColor: PALETA.royal,
                        tension: 0.3,
                        fill: false,
                    },
                    {
                        label: 'Resueltos',
                        data: tendencia.resueltos,
                        borderColor: PALETA.periwinkle,
                        backgroundColor: PALETA.periwinkle,
                        tension: 0.3,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    function renderGraficaAgentes(porAgente) {
        const ctx = document.getElementById('graficaAgentes');
        const etiquetas = Object.keys(porAgente);
        const valores = Object.values(porAgente);

        if (instanciaGraficaAgentes) instanciaGraficaAgentes.destroy();
        instanciaGraficaAgentes = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: etiquetas,
                datasets: [{
                    data: valores,
                    backgroundColor: PALETA.periwinkle,
                    borderRadius: 6,
                }],
            },
            options: {
                indexAxis: 'y', // barras horizontales, mismo criterio que el PDF
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    // ---------- Render: tabla de tickets ----------

    function renderTablaTickets(tickets) {
        const cuerpo = document.getElementById('cuerpoTablaTickets');
        cuerpo.innerHTML = '';

        tickets.forEach((t) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="font-semibold text-[#1e1858]">${t.folio}</td>
                <td>${t.asunto}</td>
                <td>${t.cliente_nombre}</td>
                <td>${t.agente ?? '—'}</td>
                <td><span class="badge-prioridad-ticket badge-prioridad-ticket--${t.prioridad_slug}">${t.prioridad}</span></td>
                <td><span class="badge-estado-ticket badge-estado-ticket--${t.estado_slug}">${t.estado}</span></td>
                <td class="text-gray-500">${formatearFecha(t.fecha_creacion)}</td>
            `;
            cuerpo.appendChild(tr);
        });
    }

    // ---------- Descarga de PDF ----------

    /**
     * Arma la URL de php/generar_pdf.php con los filtros actuales y
     * navega hacia ella. Como generar_pdf.php responde con
     * Content-Disposition: attachment, el navegador descarga el
     * archivo directamente sin salir de la página actual.
     */
    function descargarPDF() {
        const filtros = leerFiltros();
        const qs = construirQueryString(filtros);
        const boton = document.getElementById('btnDescargarPDF');

        // pequeño feedback visual mientras el servidor arma el PDF
        boton.classList.add('generando');
        boton.querySelector('.material-symbols-outlined').textContent = 'hourglass_top';

        window.location.href = `${RUTA_PDF}?${qs}`;

        // el PDF se descarga en segundo plano; regresamos el botón a su
        // estado normal después de un momento (no hay evento de "descarga
        // terminada" confiable en el navegador para este patrón).
        setTimeout(() => {
            boton.classList.remove('generando');
            boton.querySelector('.material-symbols-outlined').textContent = 'picture_as_pdf';
        }, 2500);
    }

    // ---------- Eventos ----------

    document.getElementById('formFiltros').addEventListener('submit', (e) => {
        e.preventDefault();
        cargarDatosReporte();
    });

    document.getElementById('btnDescargarPDF').addEventListener('click', descargarPDF);

    // Menú de usuario del header (mismo patrón que el resto del panel)
    const botonUsuario = document.getElementById('botonUsuario');
    const menuUsuario = document.getElementById('menuUsuario');
    if (botonUsuario && menuUsuario) {
        botonUsuario.addEventListener('click', (e) => {
            e.stopPropagation();
            menuUsuario.classList.toggle('hidden');
        });
        document.addEventListener('click', () => menuUsuario.classList.add('hidden'));
    }

    // ---------- Carga inicial ----------

    // Por defecto mostramos los últimos 30 días (mismo criterio que el backend
    // en normalizarFiltros(), para que los inputs reflejen lo que se está viendo).
    document.getElementById('filtroFechaFin').value = new Date().toISOString().slice(0, 10);
    document.getElementById('filtroFechaInicio').value = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
        .toISOString().slice(0, 10);

    cargarDatosReporte();
})();
