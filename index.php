<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {

    switch ((int)$_SESSION['id_rol']) {
        case 1: header("Location: vistas/Admin/dashboard_admin.php"); break;
        case 2: header("Location: vistas/Admin_Empresa/dashboard_admin_emp.php"); break;
        case 3: header("Location: vistas/Agente/dashboard_agente.php"); break;
        case 4: header("Location: vistas/Supervisor/dashboard_supervisor.php"); break;
        case 5: header("Location: vistas/Cliente/dashboard_cliente.php"); break;
        default:
            session_destroy();
            header("Location: login.php");
            break;
    }
    exit();
}

?>

<?php
require_once 'conexion.php';

// Valores por defecto seguros: si alguna consulta falla (por ejemplo si
// todavía falta correr una migración reciente), la página pública sigue
// funcionando en vez de caerse con un error 500 para todos los visitantes.
$prioridadesActivas = ['Alta' => 0, 'Media' => 0, 'Baja' => 0];
$ticketsActivosTotal = 0;
$capacidadPct = 0;
$indiceResolucion = 0;
$respuestaMediaTexto = '—';
$resolutividadTexto = '+0%';
$agentesOnline = 0;
$empresasActivas = 0;

try {
    $resPrioridad = $conn->query("
        SELECT p.nombre AS prioridad, COUNT(*) AS total
        FROM ticket t
        JOIN estado e ON e.id_estado = t.id_estado
        JOIN prioridad p ON p.id_prioridad = t.id_prioridad
        WHERE e.nombre NOT IN ('Cerrado', 'Cancelado')
        GROUP BY p.nombre
    ");
    if ($resPrioridad) {
        while ($row = $resPrioridad->fetch_assoc()) {
            if (isset($prioridadesActivas[$row['prioridad']])) {
                $prioridadesActivas[$row['prioridad']] = (int)$row['total'];
            }
        }
    }
    $ticketsActivosTotal = array_sum($prioridadesActivas);

    $enProceso = (int)($conn->query("
        SELECT COUNT(*) AS c FROM ticket t
        JOIN estado e ON e.id_estado = t.id_estado
        WHERE e.nombre = 'En proceso'
    ")->fetch_assoc()['c'] ?? 0);

    $agentesActivos = (int)($conn->query("
        SELECT COUNT(*) AS c FROM usuario WHERE id_rol = 3 AND activo = 1
    ")->fetch_assoc()['c'] ?? 0);

    $capacidadPorAgente = 5;
    $capacidadTotal = max(1, $agentesActivos * $capacidadPorAgente);
    $capacidadPct = min(100, (int)round(($enProceso / $capacidadTotal) * 100));

    $totalTickets = (int)($conn->query("SELECT COUNT(*) AS c FROM ticket")->fetch_assoc()['c'] ?? 0);
    $totalCerrados = (int)($conn->query("
        SELECT COUNT(*) AS c FROM ticket t
        JOIN estado e ON e.id_estado = t.id_estado
        WHERE e.nombre = 'Cerrado'
    ")->fetch_assoc()['c'] ?? 0);
    $indiceResolucion = $totalTickets > 0 ? (int)round(($totalCerrados / $totalTickets) * 100) : 0;

    $respuestaMediaMin = $conn->query("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, t.fecha_creacion, primera.fecha_envio)) AS prom
        FROM ticket t
        JOIN (
            SELECT m.id_ticket, MIN(m.fecha_envio) AS fecha_envio
            FROM mensaje m
            JOIN usuario u ON u.id_usuario = m.id_usuario
            WHERE u.id_rol IN (2, 3, 4)
            GROUP BY m.id_ticket
        ) AS primera ON primera.id_ticket = t.id_ticket
    ")->fetch_assoc()['prom'] ?? null;
    $respuestaMediaTexto = $respuestaMediaMin !== null ? number_format((float)$respuestaMediaMin, 1) : '—';

    $cerradosEsteMes = (int)($conn->query("
        SELECT COUNT(*) AS c FROM ticket
        WHERE id_estado IN (SELECT id_estado FROM estado WHERE nombre = 'Cerrado')
        AND fecha_cierre >= DATE_FORMAT(NOW(), '%Y-%m-01')
    ")->fetch_assoc()['c'] ?? 0);
    $cerradosMesPasado = (int)($conn->query("
        SELECT COUNT(*) AS c FROM ticket
        WHERE id_estado IN (SELECT id_estado FROM estado WHERE nombre = 'Cerrado')
        AND fecha_cierre >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
        AND fecha_cierre < DATE_FORMAT(NOW(), '%Y-%m-01')
    ")->fetch_assoc()['c'] ?? 0);
    if ($cerradosMesPasado > 0) {
        $resolutividadDelta = (int)round((($cerradosEsteMes - $cerradosMesPasado) / $cerradosMesPasado) * 100);
    } else {
        $resolutividadDelta = $cerradosEsteMes > 0 ? 100 : 0;
    }
    $resolutividadTexto = ($resolutividadDelta >= 0 ? '+' : '') . $resolutividadDelta . '%';

    // Requiere la columna usuario.ultimo_acceso (ver migracion_perfil.sql)
    $agentesOnline = (int)($conn->query("
        SELECT COUNT(*) AS c FROM usuario
        WHERE id_rol = 3 AND ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ")->fetch_assoc()['c'] ?? 0);

    $empresasActivas = (int)($conn->query("SELECT COUNT(*) AS c FROM empresa WHERE estado = 1")->fetch_assoc()['c'] ?? 0);
} catch (\Throwable $e) {
    // Se mantienen los valores por defecto definidos arriba; la página
    // pública sigue funcionando aunque falte alguna columna/tabla.
    error_log('[index.php] estadisticas publicas fallaron: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ServiceCore — Service Desk</title>
  <link rel="icon" type="image/png" href="img/LogoNav.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&family=Unbounded:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/transition.css">
  
</head>
<body>

<div class="page-transition-overlay" id="pageTransition">
  <div class="pt-panel pt-panel--l"></div>
  <div class="pt-panel pt-panel--c">
    <div class="pt-logo">
      <img src="img/bar1.png" alt="">
      <img src="img/bar2.png" alt="">
      <img src="img/bar3.png" alt="">
    </div>
    <p class="pt-wordmark">ServiceCore<span>Corporation</span></p>
  </div>
  <div class="pt-panel pt-panel--r"></div>
</div>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="wrap">

  <!-- NAV -->
  <nav id="nav">
    <a href="index.php" class="logo">
      <img src="./img/Logo-ServiceCore.png" alt="ServiceCore" class="logo-img">
      Service<span>Core</span>
    </a>
    <ul class="nav-links">
      <li><a href="#inicio">Inicio</a></li>
      <li><a href="#features">Funciones</a></li>
      <li><a href="#planes">Precios</a></li>
      <li><a href="about.html">Sobre Nosotros</a></li>
    </ul>
    <a href="login.php" class="nav-cta">Iniciar sesión</a>
  </nav>

  <!-- HERO -->
  <section class="hero">


    <div class="hero-left" id="inicio">
      <div class="badge" id="badge">
        <span class="badge-dot"></span>
        Plataforma de soporte empresarial
      </div>

      <h1 id="h1">
        Soporte que<br><em>resuelve</em><br>más rápido.
      </h1>

      <p class="hero-desc" id="hdesc">
        Mesa de ayuda para equipos modernos. Gestiona tickets, automatiza prioridades y mejora la resolución de problemas dentro de una organización.
      </p>

      <div class="hero-btns" id="hbtns">
        <a href="login.php" class="btn-ghost">Empezar gratis</a>
      </div>

      <div class="stats-row" id="srow">
        <div class="stat-box">
          <div class="stat-num" id="s1" data-count="<?php echo $indiceResolucion; ?>"><?php echo $indiceResolucion; ?><sub>%</sub></div>
          <div class="stat-label">Índice de resolución</div>
        </div>
        <div class="stat-box">
          <?php if ($respuestaMediaMin !== null): ?>
          <div class="stat-num">&lt;<?php echo $respuestaMediaTexto; ?><sub>min</sub></div>
          <?php else: ?>
          <div class="stat-num">—</div>
          <?php endif; ?>
          <div class="stat-label">Primera respuesta</div>
        </div>
        <div class="stat-box">
          <div class="stat-num"><?php echo $agentesActivos; ?><sub>+</sub></div>
          <div class="stat-label">Agentes en tu equipo</div>
        </div>
      </div>
    </div>

    <!-- Escena 3D -->
    <div class="hero-right">
      <div class="scene" id="scene">
        <div class="scene-bg"></div>
        <div class="scene-glow"></div>
        <div class="sphere sph-a"></div>
        <div class="sphere sph-b"></div>

        <!-- Card 1: Tickets activos -->
        <div class="card-wrap cw1" id="cw1">
          <div class="fcard" id="fc1">
            <div class="fcard-tag">Tickets activos</div>
            <div class="fcard-big"><?php echo $ticketsActivosTotal; ?> abiertos</div>
            <div class="chips">
              <span class="chip chip-r">Alta — <?php echo $prioridadesActivas['Alta']; ?></span>
              <span class="chip chip-y">Media — <?php echo $prioridadesActivas['Media']; ?></span>
              <span class="chip chip-v">Baja — <?php echo $prioridadesActivas['Baja']; ?></span>
            </div>
          </div>
        </div>

        <!-- Card 2: Cola activa -->
        <div class="card-wrap cw2" id="cw2">
          <div class="fcard" id="fc2">
            <div class="fcard-tag">Cola de soporte</div>
            <div class="fcard-big"><?php echo $enProceso; ?> en curso</div>
            <div class="bar-track">
              <div class="bar-fill" id="barfill" data-pct="<?php echo $capacidadPct; ?>"></div>
            </div>
            <div class="fcard-sub"><?php echo $capacidadPct; ?>% de capacidad del equipo</div>
          </div>
        </div>

        <!-- Card 3: Resueltos -->
        <div class="card-wrap cw3" id="cw3">
          <div class="fcard" id="fc3">
            <div class="fcard-tag">Resueltos hoy</div>
            <div class="grid2">
              <div class="g2-item">
                <strong><?php echo $indiceResolucion; ?>%</strong>
                <p>Índice de resolución</p>
              </div>
              <div class="g2-item">
                <strong><?php echo $respuestaMediaMin !== null ? $respuestaMediaTexto : '—'; ?><span style="font-size:1.1rem">min</span></strong>
                <p>Respuesta media</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Badges laterales -->
        <div class="side-badges" id="sbadges">
          <div class="sbadge">
            <strong><?php echo $resolutividadTexto; ?></strong>
            <span>Resolutividad</span>
          </div>
          <div class="sbadge">
            <strong><?php echo $agentesOnline; ?></strong>
            <span>Agentes online</span>
          </div>
          <div class="sbadge">
            <strong><?php echo $empresasActivas; ?></strong>
            <span>Empresas activas</span>
          </div>
        </div>

      </div>
    </div>

  </section>

  <div class="divider"></div>
<!--Consulta para traer las imagenes de la bd al carrusel-->
  <?php
    $carrusel = [];
    $sql = "SELECT id_carrusel, imagen_url, descripcion, fecha_subida 
            FROM carrusel 
            ORDER BY fecha_subida DESC";

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $carrusel[] = $row;
        }
    }
  ?>

<!-- CARRUSEL -->
  <section class="carousel-section" id="carousel-section">

    <div class="sphere sph-a"></div>

    <div class="carousel-wrap" id="carouselWrap">
      <button class="car-arrow car-prev" id="carPrev" aria-label="Anterior">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
      </button>
      <div class="sphere sph-b"></div>

      <div class="car-track" id="carTrack">
        <?php foreach ($carrusel as $i => $item): ?>
          <div class="car-slide" data-index="<?php echo $i; ?>">
            <div class="car-card">
              <img src="<?php echo htmlspecialchars($item['imagen_url']); ?>" alt="<?php echo htmlspecialchars($item['descripcion']); ?>">
              <div class="car-overlay">
                <p><?php echo htmlspecialchars($item['descripcion']); ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <button class="car-arrow car-next" id="carNext" aria-label="Siguiente">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>

    </div>

    <div class="car-dots" id="carDots">
      <?php foreach ($carrusel as $i => $item): ?>
        <span class="car-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="divider"></div>
  <?php
    // Traemos las imágenes de la galería para las features
    $galeria = [];
    $sqlGaleria = "SELECT id_galeria, imagen_url, descripcion, fecha_subida 
                  FROM galeria 
                  ORDER BY id_galeria ASC";
    $resultGaleria = $conn->query($sqlGaleria);
    if ($resultGaleria && $resultGaleria->num_rows > 0) {
        while ($row = $resultGaleria->fetch_assoc()) {
            $galeria[$row['id_galeria']] = $row; // indexado por id_galeria
        }
    }

    // Prueba de cards para galeria
    $features = [
        [
            'id_galeria' => 1,
            'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'titulo' => 'Panel unificado',
            'texto' => 'Todos los tickets, agentes y colas visibles desde una sola interfaz. Sin saltar entre apps ni perder contexto.'
        ],
        [
            'id_galeria' => 2,
            'icon' => '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>',
            'titulo' => 'Control de Usuarios',
            'texto' => 'Creación y asignación de tareas y permisos para cada tipo de usuarios dentro de tu empresa. Autenticación y 2FA para mayor seguridad.'
        ],
        [
            'id_galeria' => 3,
            'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'titulo' => 'Prioridades de tickets',
            'texto' => 'Alertas automáticas antes de que venza un acuerdo de nivel de servicio. Nunca más un ticket olvidado.'
        ],
        [
            'id_galeria' => 4,
            'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'titulo' => 'Resolución de problemas',
            'texto' => 'Permite a los clientes generar tickets en diferentes categorías que cuentan con sus respectivos empleados especializados.'
        ],
        [
            'id_galeria' => 5,
            'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'titulo' => 'Reportes avanzados',
            'texto' => 'Implementación de un sistema de seguimiento y control de tickets mediante historiales y estadísticas detalladas en dashboards.'
        ],
        [
            'id_galeria' => 6,
            'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>',
            'titulo' => 'Documentación y evidencias',
            'texto' => 'Facilitar la adjunción de documentación, evidencias y comentarios relacionados con cada ticket.'
        ],
    ];
  ?>

  <?php
    $planes = [];
    $sqlPlanes = "SELECT id_plan, nombre, precio, limite_usuarios, limite_tickets
                  FROM plan
                  WHERE activo = 1
                  ORDER BY precio ASC";
    $resultPlanes = $conn->query($sqlPlanes);
    if ($resultPlanes && $resultPlanes->num_rows > 0) {
        while ($row = $resultPlanes->fetch_assoc()) {
            $planes[] = $row;
        }
    }

    function formatoQuetzalesIndex($valor) {
        $valor = (float) $valor;
        return floor($valor) == $valor ? 'Q' . number_format($valor, 0) : 'Q' . number_format($valor, 2);
    }
  ?>
  <!-- GALERIA -->
  <section class="features-section" id="features">
    <div class="section-head" id="feathead">
      <span class="section-tag">Funcionalidades clave</span>
      <h2>Todo el flujo de soporte, en un solo lugar.</h2>
      <p>Desde la notificación hasta el cierre del ticket, tu equipo tiene visibilidad total con una experiencia que prioriza velocidad y claridad.</p>
    </div>

    <div class="features-grid">
      <?php foreach ($features as $f): 
        $img = $galeria[$f['id_galeria']]['imagen_url'] ?? 'img/placeholder-feature.jpg';
      ?>
        <article class="feat-card feat-item">

          <div class="feat-img-wrap">
            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($f['titulo']); ?>" class="feat-img">

            <div class="feat-icon-circle">
              <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <?php echo $f['icon']; ?>
              </svg>
            </div>
          </div>

          <div class="feat-body">
            <h3><?php echo htmlspecialchars($f['titulo']); ?></h3>
            <p><?php echo htmlspecialchars($f['texto']); ?></p>
          </div>

        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="divider"></div>

  <section class="pricing-section" id="planes">
    <div class="section-head">
      <span class="section-tag">Planes</span>
      <h2>Un plan para cada tamaño de equipo.</h2>
      <p>Empieza con el que se ajuste a tu empresa hoy y crece de plan cuando lo necesites, sin perder historial ni configuración.</p>
    </div>

    <?php if (!empty($planes)): ?>
    <div class="price-grid">
      <?php foreach ($planes as $i => $p):
        $featured = count($planes) > 2 && $i === intdiv(count($planes), 2);
      ?>
        <article class="price-card<?php echo $featured ? ' featured' : ''; ?>">
          <?php if ($featured): ?><span class="price-badge">Más elegido</span><?php endif; ?>
          <h3 class="price-name"><?php echo htmlspecialchars($p['nombre']); ?></h3>
          <div class="price-amount">
            <?php echo formatoQuetzalesIndex($p['precio']); ?><span>/mes</span>
          </div>
          <ul class="price-features">
            <li><?php echo number_format((int)$p['limite_usuarios']); ?> usuarios</li>
            <li><?php echo number_format((int)$p['limite_tickets']); ?> tickets al mes</li>
            <li>Soporte por correo y chat</li>
          </ul>
          <a href="registrarse.php" class="price-cta"><?php echo $featured ? 'Empezar ahora' : 'Elegir plan'; ?></a>
        </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="price-empty">Muy pronto vas a poder ver aquí los planes disponibles.</p>
    <?php endif; ?>
  </section>

  <div class="divider"></div>
  <!--FOOTER-->
  <footer class="site-footer">
    <div class="footer-top">

      <div class="footer-brand">
        <a href="index.php" class="logo">
          <img src="./img/Logo-ServiceCore.png" alt="ServiceCore" class="logo-img">
          Service<span>Core</span>
        </a>
        <p class="footer-tagline">Mesa de ayuda empresarial para equipos que resuelven más rápido.</p>

        <div class="footer-social">
          <a href="hostingexpo26@gmail.com" aria-label="Correo" class="social-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/>
              <path d="M2 7l10 6 10-6"/>
            </svg>
          </a>
          <a href="https://github.com/ServiceCore-Corporation" aria-label="GitHub" class="social-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>
            </svg>
          </a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Legal</h4>
        <ul>
          <li><a href="#">Términos de servicio</a></li>
          <li><a href="#">Política de privacidad</a></li>
          <li><a href="#">Seguridad</a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p>© 2026 ServiceCore Corporation — Todos los derechos reservados.</p>
      <p class="footer-made">Hecho para equipos de soporte</p>
    </div>
  </footer>

  
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="js/script.js"></script>
<script src="js/block.js"></script>
<script src="js/carrusel.js"></script>
<script src="js/nav_scroll.js"></script>
<script src="js/page_transition.js"></script>
</body>
</html>
