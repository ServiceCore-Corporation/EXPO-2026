<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {

    switch ((int)$_SESSION['id_rol']) {
        case 1: header("Location: vistas/Admin/dashboard_admin.php"); break;
        case 2: header("Location: vistas/Admin_Empresa/dashboard_admin_emp.php"); break;
        case 3: header(" Location: vistas/Agente/dashboard_agente.php"); break;
        case 4: header("Location: vistas/Supervisor/dashboard_aprovador.php"); break;
        case 5: header("Location: vistas/Cliente/dashboard_cliente.php"); break;
        default:
            session_destroy();
            header("Location: login.php");
            break;
    }
    exit();
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
  
</head>
<body>

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
          <div class="stat-num" id="s1">98<sub>%</sub></div>
          <div class="stat-label">Satisfacción del usuario</div>
        </div>
        <div class="stat-box">
          <div class="stat-num">&lt;4<sub>min</sub></div>
          <div class="stat-label">Primera respuesta</div>
        </div>
        <div class="stat-box">
          <div class="stat-num">3<sub>x</sub></div>
          <div class="stat-label">Más resolución en primer contacto</div>
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
            <div class="fcard-big">24 abiertos</div>
            <div class="chips">
              <span class="chip chip-r">Alta — 8</span>
              <span class="chip chip-y">Media — 11</span>
              <span class="chip chip-v">Baja — 5</span>
            </div>
          </div>
        </div>

        <!-- Card 2: Cola activa -->
        <div class="card-wrap cw2" id="cw2">
          <div class="fcard" id="fc2">
            <div class="fcard-tag">Cola de soporte</div>
            <div class="fcard-big">7 en curso</div>
            <div class="bar-track">
              <div class="bar-fill" id="barfill"></div>
            </div>
            <div class="fcard-sub">65% de capacidad del equipo</div>
          </div>
        </div>

        <!-- Card 3: Resueltos -->
        <div class="card-wrap cw3" id="cw3">
          <div class="fcard" id="fc3">
            <div class="fcard-tag">Resueltos hoy</div>
            <div class="grid2">
              <div class="g2-item">
                <strong>95%</strong>
                <p>Índice de resolución</p>
              </div>
              <div class="g2-item">
                <strong>3.8<span style="font-size:1.1rem">min</span></strong>
                <p>Respuesta media</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Badges laterales -->
        <div class="side-badges" id="sbadges">
          <div class="sbadge">
            <strong>+52%</strong>
            <span>Resolutividad</span>
          </div>
          <div class="sbadge">
            <strong>18</strong>
            <span>Agentes online</span>
          </div>
          <div class="sbadge">
            <strong>4.9★</strong>
            <span>Valoración</span>
          </div>
        </div>

      </div>
    </div>

  </section>

  <div class="divider"></div>
<!--Consulta para traer las imagenes de la bd al carrusel-->
  <?php
    require_once 'conexion.php'; 

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
</body>
</html>
