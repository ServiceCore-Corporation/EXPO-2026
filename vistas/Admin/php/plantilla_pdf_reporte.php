<?php
/**
 * php/plantilla_pdf_reporte.php
 * -------------------------------------------------------------------------
 * Plantilla HTML que se convierte en el PDF final.
 *
 * IMPORTANTE: esta plantilla es INDEPENDIENTE de reportes.php.
 * No usa Tailwind ni CSS moderno (flexbox/grid) porque el motor de
 * DomPDF solo entiende un subconjunto de CSS 2.1 + un poco de CSS3.
 * Por eso el layout de aquí está armado con <table>, que es lo que
 * DomPDF renderiza de forma más predecible.
 *
 * Variables que este archivo espera recibir ya definidas por quien
 * lo incluye (php/generar_pdf.php):
 *   $metricas         array  (ver obtenerMetricas())
 *   $tickets          array  (ver obtenerListadoTickets())
 *   $filtros          array  (ver normalizarFiltros())
 *   $urlGraficaEstado, $urlGraficaPrioridad, $urlGraficaTendencia, $urlGraficaAgentes  string
 *   $fechaGeneracion  string
 * -------------------------------------------------------------------------
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    /* ---- Tipografía y colores de marca ServiceCore ---- */
    body{
        font-family: 'DejaVu Sans', sans-serif; /* DomPDF trae esta fuente por defecto con soporte UTF-8 */
        color:#1f2937;
        font-size:11px;
    }
    .marca-ink{ color:#1e1858; }
    .marca-royal{ color:#5750ad; }

    /* ---- Encabezado del documento ---- */
    .encabezado{
        width:100%;
        border-bottom:2px solid #1e1858;
        padding-bottom:10px;
        margin-bottom:16px;
    }
    .encabezado td{ vertical-align:middle; }
    .encabezado .titulo{
        font-size:20px;
        font-weight:bold;
        color:#1e1858;
        margin:0;
    }
    .encabezado .subtitulo{
        font-size:11px;
        color:#6b7280;
        margin:2px 0 0;
    }
    .encabezado .meta{
        text-align:right;
        font-size:10px;
        color:#6b7280;
    }

    /* ---- Tarjetas de métricas (usamos <table> como grid de 4 columnas) ---- */
    table.metricas{
        width:100%;
        border-collapse:separate;
        border-spacing:6px;
        margin-bottom:14px;
    }
    table.metricas td{
        width:16.6%;
        background:#f5f7ff;
        border:1px solid #dfe7f4;
        border-radius:6px;
        padding:8px 10px;
        text-align:left;
    }
    table.metricas .valor{
        font-size:17px;
        font-weight:bold;
        color:#1e1858;
        display:block;
    }
    table.metricas .etiqueta{
        font-size:9px;
        color:#6b7280;
        text-transform:uppercase;
        letter-spacing:0.03em;
    }

    /* ---- Sección de gráficas (2 columnas) ---- */
    .titulo-seccion{
        font-size:13px;
        font-weight:bold;
        color:#1e1858;
        margin:18px 0 8px;
        border-left:3px solid #7773eb;
        padding-left:8px;
    }
    table.graficas{ width:100%; border-collapse:collapse; }
    table.graficas td{
        width:50%;
        padding:6px;
        text-align:center;
        vertical-align:top;
    }
    table.graficas img{ width:100%; max-width:320px; }
    table.graficas .pie-grafica{
        font-size:9px;
        color:#9ca3af;
        margin-top:2px;
    }

    /* ---- Tabla de tickets ---- */
    table.tickets{
        width:100%;
        border-collapse:collapse;
        margin-top:8px;
    }
    table.tickets th{
        background:#1e1858;
        color:#ffffff;
        font-size:9px;
        text-transform:uppercase;
        padding:6px 8px;
        text-align:left;
    }
    table.tickets td{
        font-size:9.5px;
        padding:5px 8px;
        border-bottom:1px solid #eef0f7;
    }
    table.tickets tr:nth-child(even) td{ background:#fafafe; }

    /* ---- Badges de estado/prioridad (DomPDF soporta border-radius e inline-block) ---- */
    .badge{
        display:inline-block;
        padding:2px 7px;
        border-radius:8px;
        font-size:8.5px;
        font-weight:bold;
    }
    .badge-resuelto{ background:#e6f7ee; color:#16a34a; }
    .badge-abierto{ background:#fdecec; color:#dc2626; }
    .badge-en_progreso{ background:#fdf3e2; color:#b8760f; }
    .badge-cerrado{ background:#f1f2f6; color:#6b7280; }
    .badge-cancelado{ background:#f1f2f6; color:#6b7280; }

    .badge-critica{ background:#fdecec; color:#dc2626; }
    .badge-alta{ background:#fdf3e2; color:#b8760f; }
    .badge-media{ background:#eceafd; color:#5750ad; }
    .badge-baja{ background:#f1f2f6; color:#6b7280; }

    /* ---- Pie de página ---- */
    .pie-pagina{
        margin-top:16px;
        font-size:8.5px;
        color:#9ca3af;
        text-align:center;
    }
</style>
</head>
<body>

    <!-- ================= ENCABEZADO ================= -->
    <table class="encabezado">
        <tr>
            <td style="width:60%;">
                <p class="titulo">ServiceCore — Reporte de Mesa de Servicio</p>
                <p class="subtitulo">
                    Periodo: <?= htmlspecialchars($filtros['fecha_inicio']) ?> al <?= htmlspecialchars($filtros['fecha_fin']) ?>
                    <?php if ($filtros['estado'] !== 'todos'): ?>
                        &middot; Estado: <?= htmlspecialchars($filtros['estado']) ?>
                    <?php endif; ?>
                    <?php if ($filtros['prioridad'] !== 'todos'): ?>
                        &middot; Prioridad: <?= htmlspecialchars($filtros['prioridad']) ?>
                    <?php endif; ?>
                </p>
            </td>
            <td class="meta" style="width:40%;">
                Generado el <?= htmlspecialchars($fechaGeneracion) ?><br>
                Documento generado automáticamente
            </td>
        </tr>
    </table>

    <!-- ================= MÉTRICAS PRINCIPALES ================= -->
    <table class="metricas">
        <tr>
            <td>
                <span class="valor"><?= (int) $metricas['total'] ?></span>
                <span class="etiqueta">Tickets totales</span>
            </td>
            <td>
                <span class="valor"><?= (int) $metricas['resueltos'] ?></span>
                <span class="etiqueta">Resueltos</span>
            </td>
            <td>
                <span class="valor"><?= (int) $metricas['abiertos'] ?></span>
                <span class="etiqueta">Abiertos</span>
            </td>
            <td>
                <span class="valor"><?= (int) $metricas['en_progreso'] ?></span>
                <span class="etiqueta">En progreso</span>
            </td>
            <td>
                <span class="valor"><?= number_format($metricas['tiempo_promedio_horas'], 1) ?>h</span>
                <span class="etiqueta">Tiempo prom.</span>
            </td>
            <td>
                <span class="valor"><?= number_format($metricas['cumplimiento_sla_pct'], 1) ?>%</span>
                <span class="etiqueta">SLA cumplido</span>
            </td>
        </tr>
    </table>

    <!-- ================= GRÁFICAS ================= -->
    <p class="titulo-seccion">Resumen visual</p>
    <table class="graficas">
        <tr>
            <td>
                <img src="<?= htmlspecialchars($urlGraficaEstado) ?>" alt="Tickets por estado">
                <p class="pie-grafica">Tickets por estado</p>
            </td>
            <td>
                <img src="<?= htmlspecialchars($urlGraficaPrioridad) ?>" alt="Tickets por prioridad">
                <p class="pie-grafica">Tickets por prioridad</p>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <img src="<?= htmlspecialchars($urlGraficaTendencia) ?>" alt="Tendencia de tickets" style="max-width:600px;">
                <p class="pie-grafica">Tendencia: creados vs. resueltos</p>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <img src="<?= htmlspecialchars($urlGraficaAgentes) ?>" alt="Tickets por agente" style="max-width:600px;">
                <p class="pie-grafica">Tickets resueltos por agente</p>
            </td>
        </tr>
    </table>

    <!-- ================= LISTADO DE TICKETS ================= -->
    <p class="titulo-seccion">Detalle de tickets (<?= count($tickets) ?> más recientes del periodo)</p>
    <table class="tickets">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Asunto</th>
                <th>Cliente</th>
                <th>Agente</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th>Creado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['folio']) ?></td>
                <td><?= htmlspecialchars($t['asunto']) ?></td>
                <td><?= htmlspecialchars($t['cliente_nombre']) ?></td>
                <td><?= htmlspecialchars($t['agente'] ?? '—') ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($t['prioridad_slug']) ?>"><?= htmlspecialchars($t['prioridad']) ?></span></td>
                <td><span class="badge badge-<?= htmlspecialchars($t['estado_slug']) ?>"><?= htmlspecialchars($t['estado']) ?></span></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($t['fecha_creacion']))) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="pie-pagina">ServiceCore Corporation &middot; Reporte confidencial de uso interno</p>

    <!-- El número de página real se agrega después, desde generar_pdf.php,
         usando $dompdf->getCanvas()->page_text(...) -->

</body>
</html>
