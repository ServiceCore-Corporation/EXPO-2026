<?php
/**
 * vistas/Admin/php/lib/consultas_reportes.php
 * -------------------------------------------------------------------------
 * Capa de datos del módulo de Reportes, conectada a la base de datos real
 * de ServiceCore (mysqli, mismo esquema que usa el resto del sistema).
 *
 * Tablas reales usadas:
 *   ticket(id_ticket, titulo, id_usuario_cliente, id_usuario_agente,
 *          id_categoria, id_prioridad, id_estado, fecha_creacion, fecha_cierre)
 *   estado(id_estado, nombre)      -> 'Pendiente','En proceso','Cerrado','Cancelado'
 *   prioridad(id_prioridad, nombre) -> 'Alta','Media','Baja'
 *   usuario(id_usuario, nombre, id_rol)
 *
 * Tanto el dashboard en vivo (api_datos_reporte.php) como el PDF
 * (generar_pdf.php) llaman a estas mismas funciones, así que nunca se
 * desincronizan los números entre pantalla y PDF.
 * -------------------------------------------------------------------------
 */

require_once __DIR__ . '/../../../../conexion.php'; // define $conn (mysqli)

/**
 * Traduce el nombre real de estado a la clase de badge visual usada en
 * el dashboard y el PDF (ver css/admin_reportes.css y plantilla_pdf_reporte.php).
 */
function sluggEstado(?string $estadoReal): string
{
    $mapa = [
        'Pendiente'  => 'abierto',
        'En proceso' => 'en_progreso',
        'Cerrado'    => 'resuelto',
        'Cancelado'  => 'cancelado',
    ];
    return $mapa[$estadoReal] ?? 'cancelado';
}

function sluggPrioridad(?string $prioridadReal): string
{
    $mapa = ['Alta' => 'alta', 'Media' => 'media', 'Baja' => 'baja'];
    return $mapa[$prioridadReal] ?? 'media';
}

/**
 * Normaliza y valida los filtros que llegan desde el frontend (querystring).
 * Los valores de estado/prioridad son los nombres reales tal cual están en
 * las tablas `estado` y `prioridad` (o 'todos' para no filtrar).
 */
function normalizarFiltros(array $filtrosCrudos): array
{
    return [
        'fecha_inicio' => $filtrosCrudos['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days')),
        'fecha_fin'    => $filtrosCrudos['fecha_fin']    ?? date('Y-m-d'),
        'estado'       => $filtrosCrudos['estado']       ?? 'todos',
        'prioridad'    => $filtrosCrudos['prioridad']    ?? 'todos',
        'agente_id'    => isset($filtrosCrudos['agente_id']) ? (int) $filtrosCrudos['agente_id'] : 0,
    ];
}

/**
 * Arma el fragmento "WHERE ..." + arreglo de parámetros/tipos para mysqli,
 * a partir de los filtros ya normalizados.
 */
function construirClausulaFiltros(array $filtros): array
{
    $condiciones = ['t.fecha_creacion BETWEEN ? AND ?'];
    $tipos       = 'ss';
    $valores     = [$filtros['fecha_inicio'] . ' 00:00:00', $filtros['fecha_fin'] . ' 23:59:59'];

    if ($filtros['estado'] !== 'todos') {
        $condiciones[] = 'e.nombre = ?';
        $tipos        .= 's';
        $valores[]     = $filtros['estado'];
    }

    if ($filtros['prioridad'] !== 'todos') {
        $condiciones[] = 'p.nombre = ?';
        $tipos        .= 's';
        $valores[]     = $filtros['prioridad'];
    }

    if ($filtros['agente_id'] > 0) {
        $condiciones[] = 't.id_usuario_agente = ?';
        $tipos        .= 'i';
        $valores[]     = $filtros['agente_id'];
    }

    return [
        'sql'    => 'WHERE ' . implode(' AND ', $condiciones),
        'tipos'  => $tipos,
        'valores'=> $valores,
    ];
}

/** Prepara y ejecuta $sql con los filtros ya armados por construirClausulaFiltros(). */
function ejecutarConFiltros(mysqli $conn, string $sql, array $f): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Error preparando consulta de reportes: ' . $conn->error);
    }
    if (!empty($f['valores'])) {
        $stmt->bind_param($f['tipos'], ...$f['valores']);
    }
    $stmt->execute();
    return $stmt;
}

/**
 * Métricas principales del reporte (las tarjetas superiores del dashboard).
 * Nota: el esquema real no tiene columnas de SLA ni de calificación CSAT,
 * así que "cumplimiento_sla_pct" se calcula contra un umbral interno
 * configurable (por defecto 48 horas) en vez de una fecha de vencimiento
 * guardada en la base de datos.
 */
function obtenerMetricas(array $filtros): array
{
    global $conn;
    $f = construirClausulaFiltros($filtros);

    $slaHorasObjetivo = 48; // umbral interno de SLA (no existe columna de SLA en el esquema real)

    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(e.nombre = 'Cerrado') AS resueltos,
            SUM(e.nombre = 'Pendiente') AS abiertos,
            SUM(e.nombre = 'En proceso') AS en_progreso,
            AVG(CASE WHEN t.fecha_cierre IS NOT NULL
                     THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) END) AS tiempo_promedio_horas,
            SUM(CASE WHEN t.fecha_cierre IS NOT NULL
                          AND TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) <= {$slaHorasObjetivo}
                     THEN 1 ELSE 0 END) AS dentro_sla,
            SUM(t.fecha_cierre IS NOT NULL) AS total_cerrados
        FROM ticket t
        LEFT JOIN estado e    ON e.id_estado = t.id_estado
        LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
        {$f['sql']}
    ";

    $stmt = ejecutarConFiltros($conn, $sql, $f);
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalCerrados = (int) ($fila['total_cerrados'] ?? 0);
    $dentroSla     = (int) ($fila['dentro_sla'] ?? 0);

    return [
        'total'                 => (int)   ($fila['total'] ?? 0),
        'resueltos'             => (int)   ($fila['resueltos'] ?? 0),
        'abiertos'              => (int)   ($fila['abiertos'] ?? 0),
        'en_progreso'           => (int)   ($fila['en_progreso'] ?? 0),
        'tiempo_promedio_horas' => round((float) ($fila['tiempo_promedio_horas'] ?? 0), 1),
        'cumplimiento_sla_pct'  => $totalCerrados > 0 ? round(($dentroSla / $totalCerrados) * 100, 1) : 0.0,
    ];
}

/**
 * Datos ya agrupados y listos para las 4 gráficas del reporte:
 *  - por_estado:    dona (Pendiente / En proceso / Cerrado / Cancelado)
 *  - por_prioridad: barras (Alta / Media / Baja)
 *  - tendencia:     línea (tickets creados vs. cerrados por día)
 *  - por_agente:    barras horizontales (top agentes por tickets cerrados)
 */
function obtenerDatosGraficas(array $filtros): array
{
    global $conn;
    $f = construirClausulaFiltros($filtros);

    // --- Por estado ---
    $sqlEstado = "
        SELECT e.nombre AS etiqueta, COUNT(*) AS total
        FROM ticket t
        LEFT JOIN estado e    ON e.id_estado = t.id_estado
        LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
        {$f['sql']}
        GROUP BY e.nombre
    ";
    $stmt = ejecutarConFiltros($conn, $sqlEstado, $f);
    $crudoEstado = [];
    foreach ($stmt->get_result() as $row) {
        $crudoEstado[$row['etiqueta'] ?? 'Sin estado'] = (int) $row['total'];
    }
    $stmt->close();
    // Orden fijo para que el color de cada estado no cambie entre recargas
    $ordenEstado = ['Pendiente', 'En proceso', 'Cerrado', 'Cancelado'];
    $porEstado = [];
    foreach ($ordenEstado as $nombreEstado) {
        if (isset($crudoEstado[$nombreEstado])) {
            $porEstado[$nombreEstado] = $crudoEstado[$nombreEstado];
        }
    }
    // Cualquier estado inesperado que no esté en la lista fija se agrega al final
    foreach ($crudoEstado as $nombre => $total) {
        if (!isset($porEstado[$nombre])) {
            $porEstado[$nombre] = $total;
        }
    }

    // --- Por prioridad ---
    $sqlPrioridad = "
        SELECT p.nombre AS etiqueta, COUNT(*) AS total
        FROM ticket t
        LEFT JOIN estado e    ON e.id_estado = t.id_estado
        LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
        {$f['sql']}
        GROUP BY p.nombre
    ";
    $stmt = ejecutarConFiltros($conn, $sqlPrioridad, $f);
    $crudoPrioridad = [];
    foreach ($stmt->get_result() as $row) {
        $crudoPrioridad[$row['etiqueta'] ?? 'Sin prioridad'] = (int) $row['total'];
    }
    $stmt->close();
    $ordenPrioridad = ['Alta', 'Media', 'Baja'];
    $porPrioridad = [];
    foreach ($ordenPrioridad as $nombrePrioridad) {
        if (isset($crudoPrioridad[$nombrePrioridad])) {
            $porPrioridad[$nombrePrioridad] = $crudoPrioridad[$nombrePrioridad];
        }
    }
    foreach ($crudoPrioridad as $nombre => $total) {
        if (!isset($porPrioridad[$nombre])) {
            $porPrioridad[$nombre] = $total;
        }
    }

    // --- Tendencia por día (creados vs. cerrados) ---
    $sqlTendencia = "
        SELECT DATE(t.fecha_creacion) AS dia,
               COUNT(*) AS creados,
               SUM(t.fecha_cierre IS NOT NULL AND DATE(t.fecha_cierre) = DATE(t.fecha_creacion)) AS cerrados_mismo_dia
        FROM ticket t
        LEFT JOIN estado e    ON e.id_estado = t.id_estado
        LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
        {$f['sql']}
        GROUP BY DATE(t.fecha_creacion)
        ORDER BY dia ASC
    ";
    $stmt = ejecutarConFiltros($conn, $sqlTendencia, $f);
    $tendenciaFilas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Cerrados por día (basado en fecha_cierre real, más útil que "mismo día")
    $sqlCerradosPorDia = "
        SELECT DATE(t.fecha_cierre) AS dia, COUNT(*) AS total
        FROM ticket t
        LEFT JOIN estado e    ON e.id_estado = t.id_estado
        LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
        {$f['sql']}
          AND t.fecha_cierre IS NOT NULL
        GROUP BY DATE(t.fecha_cierre)
    ";
    $stmt = ejecutarConFiltros($conn, $sqlCerradosPorDia, $f);
    $cerradosPorDia = [];
    foreach ($stmt->get_result() as $row) {
        $cerradosPorDia[$row['dia']] = (int) $row['total'];
    }
    $stmt->close();

    $dias      = array_column($tendenciaFilas, 'dia');
    $creados   = array_map('intval', array_column($tendenciaFilas, 'creados'));
    $resueltos = array_map(fn($dia) => $cerradosPorDia[$dia] ?? 0, $dias);

    // --- Top agentes por tickets cerrados ---
    $sqlAgentes = "
        SELECT u.nombre AS agente, COUNT(*) AS total
        FROM ticket t
        LEFT JOIN estado e    ON e.id_estado = t.id_estado
        LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
        JOIN usuario u ON u.id_usuario = t.id_usuario_agente
        {$f['sql']}
          AND e.nombre = 'Cerrado'
        GROUP BY u.id_usuario, u.nombre
        ORDER BY total DESC
        LIMIT 6
    ";
    $stmt = ejecutarConFiltros($conn, $sqlAgentes, $f);
    $porAgente = [];
    foreach ($stmt->get_result() as $row) {
        $porAgente[$row['agente']] = (int) $row['total'];
    }
    $stmt->close();

    return [
        'por_estado'    => $porEstado,
        'por_prioridad' => $porPrioridad,
        'tendencia'     => [
            'dias'      => $dias,
            'creados'   => $creados,
            'resueltos' => $resueltos,
        ],
        'por_agente'    => $porAgente,
    ];
}

/**
 * Listado de tickets para la tabla resumen (dashboard y PDF).
 * $limite acota cuántas filas se traen (el PDF usa un límite más alto
 * que la vista previa en pantalla).
 */
function obtenerListadoTickets(array $filtros, int $limite = 10): array
{
    global $conn;
    $f = construirClausulaFiltros($filtros);

    $sql = "
        SELECT t.id_ticket, t.titulo,
               cli.nombre AS cliente_nombre,
               ag.nombre  AS agente,
               p.nombre   AS prioridad,
               e.nombre   AS estado,
               t.fecha_creacion
        FROM ticket t
        LEFT JOIN estado e    ON e.id_estado = t.id_estado
        LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
        LEFT JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
        LEFT JOIN usuario ag  ON ag.id_usuario  = t.id_usuario_agente
        {$f['sql']}
        ORDER BY t.fecha_creacion DESC
        LIMIT ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Error preparando listado de tickets: ' . $conn->error);
    }
    $tipos   = $f['tipos'] . 'i';
    $valores = array_merge($f['valores'], [$limite]);
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(function ($t) {
        return [
            'folio'           => 'TK-' . str_pad((string) $t['id_ticket'], 5, '0', STR_PAD_LEFT),
            'asunto'          => $t['titulo'],
            'cliente_nombre'  => $t['cliente_nombre'] ?? 'Sin cliente',
            'agente'          => $t['agente'],
            'prioridad'       => $t['prioridad'] ?? 'Media',
            'prioridad_slug'  => sluggPrioridad($t['prioridad']),
            'estado'          => $t['estado'] ?? 'Pendiente',
            'estado_slug'     => sluggEstado($t['estado']),
            'fecha_creacion'  => $t['fecha_creacion'],
        ];
    }, $filas);
}

/** Lista de agentes (rol 3) para el selector de filtro por agente. */
function obtenerAgentesParaFiltro(): array
{
    global $conn;
    $rows = $conn->query("SELECT id_usuario, nombre FROM usuario WHERE id_rol = 3 ORDER BY nombre")
        ->fetch_all(MYSQLI_ASSOC);
    return $rows;
}
