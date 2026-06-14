<?php
// Redirige si ya está autenticado
session_start();

// Bloquear caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    switch ($_SESSION['id_rol']) {
        case 1: header("Location: dashboard_admin.php"); break;
        case 2: header("Location: dashboard_admin_emp.php"); break;
        case 3: header("Location: dashboard_aprovador.php"); break;
        case 4: header("Location: dashboard_agente.php"); break;
        case 5: header("Location: dashboard_cliente.php"); break;
    }
    exit();
}

// Captura el mensaje de error si viene en la URL
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Iniciar Sesión | ServiceCore Corp.</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
  :root {
    --fondo:           #1e1858;
    --fondo-medio:     #2a2470;
    --fondo-claro:     #322b88;
    --acento:          #5750ad;
    --acento-claro:    #7773eb;
    --blanco:          #ffffff;
    --celeste:         #dfe7f4;
    --texto-principal: #ffffff;
    --texto-suave:     #c3c0e8;
    --texto-tenue:     #8f8cc4;
    --borde:           #4a44b8;
    --borde-claro:     #4f4ab0;
    --error:           #ff6b6b;
    --chico:   0.25rem;
    --pequeno: 0.5rem;
    --normal:  1rem;
    --grande:  1.5rem;
    --mayor:   2rem;
    --enorme:  3rem;
    --margen:  24px;
    --radio:    0.25rem;
    --radio-md: 0.5rem;
    --radio-lg: 0.75rem;
    --fuente:        'Poppins', sans-serif;
    --fuente-titulo: 'Montserrat', sans-serif;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: var(--fuente);
    background-color: #1e1858;
    background-image: var(--luz-fondo, none),
      radial-gradient(ellipse at top left, #2a2470 0%, #1e1858 60%);
    color: var(--texto-principal);
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
  }
  a { text-decoration: none; color: inherit; }
  button { cursor: pointer; font-family: inherit; border: none; background: none; }
  input { font-family: inherit; }
  .logo { height: 35px; width: 45px; }
  .titulo-grande { font-family: var(--fuente-titulo); font-size: 28px; line-height: 36px; letter-spacing: -0.01em; font-weight: 700; }
  .titulo-medio  { font-family: var(--fuente-titulo); font-size: 20px; line-height: 28px; font-weight: 700; }

  /* ── Encabezado ── */
  .encabezado { display: flex; align-items: center; justify-content: space-between; padding: 16px var(--margen); border-bottom: 1px solid rgba(74,68,184,0.35); backdrop-filter: blur(8px); position: sticky; top: 0; z-index: 100; background: rgba(30,24,88,0.7); }
  .marca { display: flex; align-items: center; gap: 10px; }
  .marca-nombre { font-family: var(--fuente-titulo); font-size: 18px; font-weight: 700; color: var(--blanco); }
  .marca-nombre span { color: var(--acento-claro); }
  .nav-enlaces { display: flex; gap: 24px; list-style: none; }
  .nav-enlaces a { font-size: 14px; color: var(--texto-suave); transition: color 0.2s; }
  .nav-enlaces a:hover { color: var(--blanco); }

  /* ── Main ── */
  main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px var(--margen); }
  .contenedor-central { display: flex; align-items: center; justify-content: center; gap: 60px; width: 100%; max-width: 960px; }

  /* ── Lado izquierdo ── */
  .lado-info { flex: 1; max-width: 380px; display: flex; flex-direction: column; gap: 20px; }
  .lado-info .chip { display: inline-flex; align-items: center; gap: 6px; background: rgba(87,80,173,0.25); border: 1px solid rgba(119,115,235,0.4); border-radius: 20px; padding: 5px 14px; font-size: 12px; color: var(--acento-claro); width: fit-content; }
  .lado-info h1 { font-family: var(--fuente-titulo); font-size: 32px; font-weight: 700; line-height: 1.25; }
  .lado-info h1 span { color: var(--acento-claro); }
  .lado-info p { font-size: 14px; color: var(--texto-suave); line-height: 1.65; }
  .beneficios { display: flex; flex-direction: column; gap: 10px; margin-top: 4px; }
  .beneficio  { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--texto-suave); }
  .beneficio .material-symbols-outlined { font-size: 18px; color: var(--acento-claro); }

  /* ── Tarjeta ── */
  .tarjeta-envuelta { flex-shrink: 0; width: 100%; max-width: 400px; }
  .tarjeta {
    background: linear-gradient(145deg, rgba(50,43,136,0.55), rgba(42,36,112,0.45));
    border: 1px solid rgba(74,68,184,0.5);
    border-radius: 16px;
    padding: 36px 32px;
    backdrop-filter: blur(12px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
  }
  .tarjeta-cabecera { display: flex; flex-direction: column; align-items: center; gap: 10px; margin-bottom: 28px; text-align: center; }
  .icono-escudo { width: 52px; height: 52px; background: linear-gradient(135deg, #5750ad, #7773eb); border-radius: 14px; display: flex; align-items: center; justify-content: center; }
  .icono-escudo .material-symbols-outlined { font-size: 28px; color: #fff; }

  /* ── Alerta de error ── */
  .alerta-error {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,107,107,0.12); border: 1px solid rgba(255,107,107,0.4);
    border-radius: 8px; padding: 10px 14px;
    font-size: 13px; color: var(--error);
    margin-bottom: 18px;
  }
  .alerta-error .material-symbols-outlined { font-size: 18px; flex-shrink: 0; }

  /* ── Formulario ── */
  .formulario { display: flex; flex-direction: column; gap: var(--normal); }
  .campo { display: flex; flex-direction: column; gap: 6px; }
  .etiqueta-campo { font-size: 13px; font-weight: 600; color: var(--texto-suave); }
  .fila-campo { display: flex; align-items: center; justify-content: space-between; }
  .enlace-olvide { font-size: 12px; color: var(--acento-claro); transition: opacity 0.2s; }
  .enlace-olvide:hover { opacity: 0.75; }
  .caja-entrada { position: relative; }
  .icono-entrada { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 20px; color: var(--texto-tenue); pointer-events: none; }
  .entrada {
    width: 100%; background: rgba(15,12,54,0.6); border: 1px solid var(--borde);
    border-radius: var(--radio-md); color: var(--blanco);
    padding: 11px 14px 11px 40px; font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s; outline: none;
  }
  .entrada::placeholder { color: var(--texto-tenue); }
  .entrada:focus { border-color: var(--acento-claro); box-shadow: 0 0 0 3px rgba(119,115,235,0.2); }
  .entrada-con-boton { padding-right: 44px; }
  .boton-mostrar { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--texto-tenue); display: flex; align-items: center; transition: color 0.2s; }
  .boton-mostrar:hover { color: var(--blanco); }
  .boton-ingresar {
    width: 100%; padding: 13px;
    background: linear-gradient(135deg, #5750ad, #7773eb);
    color: #fff; border-radius: 8px;
    font-family: var(--fuente-titulo); font-weight: 700; font-size: 15px;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: opacity 0.2s, transform 0.1s, box-shadow 0.2s;
    box-shadow: 0 4px 15px rgba(87,80,173,0.4);
    margin-top: 4px;
  }
  .boton-ingresar:hover { opacity: 0.88; box-shadow: 0 6px 20px rgba(87,80,173,0.5); }
  .boton-ingresar:active { transform: scale(0.98); }
  @keyframes girar { to { transform: rotate(360deg); } }
  .girando .material-symbols-outlined { animation: girar 0.8s linear infinite; display: inline-block; }
  .separador { display: flex; align-items: center; gap: 12px; margin: 4px 0; }
  .separador-linea { flex: 1; height: 1px; background: rgba(74,68,184,0.4); }
  .texto-registro { font-size: 13px; color: var(--texto-tenue); text-align: center; }
  .texto-registro a { color: var(--acento-claro); font-weight: 600; }
  .texto-registro a:hover { text-decoration: underline; }

  /* ── Footer ── */
  .pie { border-top: 1px solid rgba(74,68,184,0.35); padding: 18px var(--margen); }
  .pie-interior { max-width: 960px; margin: auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
  .pie-marca { display: flex; flex-direction: column; gap: 2px; }
  .pie-nombre { font-family: var(--fuente-titulo); font-size: 13px; font-weight: 700; color: var(--acento-claro); }
  .pie-derechos { font-size: 11px; color: var(--texto-tenue); }
  .pie-nav { display: flex; gap: 20px; }
  .pie-nav a { font-size: 12px; color: var(--texto-tenue); transition: color 0.2s; }
  .pie-nav a:hover { color: var(--blanco); }

  @media (max-width: 700px) {
    .contenedor-central { flex-direction: column; gap: 32px; }
    .lado-info { display: none; }
    .tarjeta-envuelta { max-width: 100%; }
  }
</style>
</head>
<body>

<header class="encabezado">
  <div class="marca">
    <img src="img/logoSC.png" alt="Logo" class="logo"/>
    <span class="marca-nombre">Service<span>Core</span></span>
  </div>
  <nav>
    <ul class="nav-enlaces">
      <li><a href="index.php">Inicio</a></li>
      <li><a href="index.php#features">Funciones</a></li>
    </ul>
  </nav>
</header>

<main>
  <div class="contenedor-central">

    <!-- Info lateral -->
    <div class="lado-info">
      <span class="chip">
        <span class="material-symbols-outlined" style="font-size:14px">verified_user</span>
        Acceso Seguro
      </span>
      <h1>Bienvenido a <span>ServiceCore</span></h1>
      <p>Plataforma empresarial de gestión de tickets y soporte técnico.</p>
      <div class="beneficios">
        <div class="beneficio"><span class="material-symbols-outlined">task_alt</span>Gestión de tickets en tiempo real</div>
        <div class="beneficio"><span class="material-symbols-outlined">group</span>Roles de acceso diferenciados</div>
        <div class="beneficio"><span class="material-symbols-outlined">lock</span>Verificación en dos pasos (2FA)</div>
      </div>
    </div>

    <!-- Tarjeta de login -->
    <div class="tarjeta-envuelta" id="tarjetaEnvuelta">
      <div class="tarjeta" id="tarjeta">
        <div class="tarjeta-cabecera">
          <div class="icono-escudo">
            <span class="material-symbols-outlined">shield_person</span>
          </div>
          <h2 class="titulo-medio">Iniciar Sesión</h2>
          <p style="font-size:13px;color:var(--texto-tenue)">Ingresa tus credenciales corporativas</p>
        </div>

        <?php if ($error): ?>
        <div class="alerta-error">
          <span class="material-symbols-outlined">error</span>
          <?= $error ?>
        </div>
        <?php endif; ?>

        <form id="formularioLogin" class="formulario" action="validar_login.php" method="POST" novalidate>

          <div class="campo">
            <label class="etiqueta-campo" for="correo">Correo electrónico</label>
            <div class="caja-entrada">
              <span class="material-symbols-outlined icono-entrada">mail</span>
              <input class="entrada" id="correo" name="correo"
                     type="email" placeholder="usuario@empresa.com" required/>
            </div>
          </div>

          <div class="campo">
            <div class="fila-campo">
              <label class="etiqueta-campo" for="password">Contraseña</label>
              <a class="enlace-olvide" href="#">Olvidé mi contraseña</a>
            </div>
            <div class="caja-entrada">
              <span class="material-symbols-outlined icono-entrada">lock</span>
              <input class="entrada entrada-con-boton" id="password" name="password"
                     type="password" placeholder="••••••••" required/>
              <button class="boton-mostrar" type="button" id="verContrasena" aria-label="Mostrar contraseña">
                <span class="material-symbols-outlined" style="font-size:20px">visibility</span>
              </button>
            </div>
          </div>

          <button class="boton-ingresar" type="submit" id="botonEntrar">
            <span class="material-symbols-outlined" style="font-size:20px">login</span>
            Iniciar Sesión
          </button>
        </form>

        <div class="separador"><div class="separador-linea"></div></div>
        <p class="texto-registro">¿No tienes cuenta? <a href="registrarse.php">Registrarse</a></p>
      </div>
    </div>

  </div>
</main>

<footer class="pie">
  <div class="pie-interior">
    <div class="pie-marca">
      <span class="pie-nombre">ServiceCore Corporation</span>
      <span class="pie-derechos">© 2026. Secure Enterprise Environment.</span>
    </div>
    <nav class="pie-nav">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
      <a href="#">Contact Support</a>
    </nav>
  </div>
</footer>
<script src="js/block.js"></script> 

<script>
  // ── Spotlight ────────────────────────────────────────────
  const cuerpo = document.body;
  const encabezado = document.querySelector('.encabezado');
  const piePagina  = document.querySelector('.pie');
  const envuelta   = document.getElementById('tarjetaEnvuelta');
  let luzX=-999,luzY=-999,destX=-999,destY=-999,brillo=0,brilloDest=0;
  function estaEnZonaLibre(cx,cy){
    function cubre(el){const r=el.getBoundingClientRect();return cx>=r.left&&cx<=r.right&&cy>=r.top&&cy<=r.bottom;}
    return !cubre(encabezado)&&!cubre(piePagina)&&!cubre(envuelta);
  }
  document.addEventListener('mousemove',(e)=>{destX=e.clientX;destY=e.clientY;brilloDest=estaEnZonaLibre(e.clientX,e.clientY)?1:0;});
  document.addEventListener('mouseleave',()=>{brilloDest=0;});
  (function animar(){
    luzX+=(destX-luzX)*0.1; luzY+=(destY-luzY)*0.1;
    const v=brilloDest>brillo?0.08:0.05; brillo+=(brilloDest-brillo)*v;
    if(brillo>0.01){
      cuerpo.style.setProperty('--luz-fondo',`radial-gradient(500px circle at ${luzX}px ${luzY}px, rgba(141,48,212,${0.25*brillo}) 0%, rgba(97,47,131,${0.15*brillo}) 35%, transparent 70%)`);
    } else { cuerpo.style.setProperty('--luz-fondo','none'); }
    requestAnimationFrame(animar);
  })();

  // ── Envío del formulario ─────────────────────────────────
  const formulario  = document.getElementById('formularioLogin');
  const botonEntrar = document.getElementById('botonEntrar');

  formulario.addEventListener('submit', (e) => {
    botonEntrar.innerHTML = '<span class="girando"><span class="material-symbols-outlined" style="font-size:20px">sync</span></span> Autenticando...';
    botonEntrar.style.opacity = '0.8';
    botonEntrar.disabled = true;
    // El formulario continúa hacia validar_login.php
  });

  // ── Mostrar / ocultar contraseña ─────────────────────────
  const verContrasena = document.getElementById('verContrasena');
  const campoPassword = document.getElementById('password');
  verContrasena.addEventListener('click', () => {
    const esPassword = campoPassword.type === 'password';
    campoPassword.type = esPassword ? 'text' : 'password';
    verContrasena.querySelector('.material-symbols-outlined').textContent =
      esPassword ? 'visibility_off' : 'visibility';
  });
</script>
</body>
</html>
