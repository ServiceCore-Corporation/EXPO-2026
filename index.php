<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ServiceCore — Service Desk</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css">
  
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="wrap">

  <!-- NAV -->
  <nav id="nav">
    <a href="./img/Logo-ServiceCore.png" class="logo">Service<span>Core</span></a>
    <ul class="nav-links">
      <li><a href="#">Inicio</a></li>
      <li><a href="#features">Funciones</a></li>
      <li><a href="#">Desarrolladores</a></li>
      <li><a href="#">Sobre Nosotros</a></li>
    </ul>
    <a href="login.php" class="nav-cta">Iniciar sesión</a>
  </nav>

  <!-- HERO -->
  <section class="hero">

    <!-- Texto -->
    <div class="hero-left">
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
        <a href="#" class="btn-ghost">Empezar gratis</a>
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

  <!-- FEATURES -->
  <section class="features-section" id="features">
    <div class="section-head" id="feathead">
      <span class="section-tag">Funcionalidades clave</span>
      <h2>Todo el flujo de soporte, en un solo lugar.</h2>
      <p>Desde la notificación hasta el cierre del ticket, tu equipo tiene visibilidad total con una experiencia que prioriza velocidad y claridad.</p>
    </div>
    <div class="features-grid">
      <article class="feat-card feat-item">
        <div class="feat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
            <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
          </svg>
        </div>
        <h3>Panel unificado</h3>
        <p>Todos los tickets, agentes y colas visibles desde una sola interfaz. Sin saltar entre apps ni perder contexto.</p>
      </article>
      <article class="feat-card feat-item">
        <div class="feat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
          </svg>
        </div>
        <h3>Control de Usuarios</h3>
        <p>Creacion y asignacion de tareas y permisos para cada tipo de usuarios dentro de tu empresa. Autenticación y 2FA para mayor seguridad.</p>
      </article>
      <article class="feat-card feat-item">
        <div class="feat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <h3>Priodidades de tickets</h3>
        <p>Alertas automáticas antes de que venza un acuerdo de nivel de servicio. Nunca más un ticket olvidado.</p>
      </article>
      <article class="feat-card feat-item">
        <div class="feat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <h3>Resolucion de problemas</h3>
        <p>Permite a los clientes generar tickets en diferentes categoria que cuenta con sus respectivos empleados especializados.</p>
      </article>
      <article class="feat-card feat-item">
        <div class="feat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6" y1="20" x2="6" y2="14"/>
          </svg>
        </div>
        <h3>Reportes avanzados</h3>
        <p>Implementacion de un sistema de seguimiento y control de tickets mediante historiales y estadísticas detalladas en Dasboards.</p>
      </article>
      <article class="feat-card feat-item">
        <div class="feat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
          </svg>
        </div>
        <h3>Documentacion y evidencias</h3>
        <p>Facilitar la adjunción de documentación, evidencias y comentarios relacionados con cada ticket.</p>
      </article>
    </div>
  </section>

  <footer>
    <a href="#" class="logo">Service<span>Core</span></a>
    <p>© 2026 ServiceCore Corporation— Mesa de ayuda empresarial</p>
    <p>Hecho para equipos de soporte</p>
  </footer>

  
</div>
<script src="/js/script.js"></script>   
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
</body>
</html>
