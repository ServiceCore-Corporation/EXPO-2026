<?php
require_once 'conexion.php';
require_once 'correo.php';

$mensajeExito = '';
$correoEnviado = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = trim($_POST['email'] ?? '');

    if (!empty($correo) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        // Buscar si el correo existe en la base de datos
        $consulta = $conexion->prepare("SELECT id_usuario, nombre FROM usuario WHERE correo = ? LIMIT 1");
        $consulta->bind_param("s", $correo);
        $consulta->execute();
        $resultado = $consulta->get_result();
        $usuario   = $resultado->fetch_assoc();
        $consulta->close();

        // Solo generar token si el usuario existe (sin revelar si existe o no)
        if ($usuario) {

            // Generar token único y seguro
            $token       = bin2hex(random_bytes(32));
            $expiracion  = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $fechaActual = date('Y-m-d H:i:s');

            // Invalidar tokens anteriores del mismo usuario
            $invalidar = $conexion->prepare("UPDATE recuperacion_password SET usado = 1 WHERE id_usuario = ? AND usado = 0");
            $invalidar->bind_param("i", $usuario['id_usuario']);
            $invalidar->execute();
            $invalidar->close();

            // Insertar el nuevo token
            $insertar = $conexion->prepare("
                INSERT INTO recuperacion_password (id_usuario, token, expiracion, usado, fecha_creacion)
                VALUES (?, ?, ?, 0, ?)
            ");
            $insertar->bind_param("isss", $usuario['id_usuario'], $token, $expiracion, $fechaActual);
            $insertar->execute();
            $insertar->close();

            // Construir el enlace de restablecimiento
            $protocolo   = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $dominio     = $_SERVER['HTTP_HOST'];
            $enlaceReset = "{$protocolo}{$dominio}/restablecer_password.php?token={$token}";

            // Construir y enviar el correo con la plantilla de diseño
            $contenidoCorreo = "
                <p>Hola <strong>" . htmlspecialchars($usuario['nombre']) . "</strong>,</p>
                <br/>
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en ServiceCore.</p>
                <p>Haz clic en el siguiente botón para crear una nueva contraseña. Este enlace expirará en 30 minutos.</p>
                <br/>
                <p style='font-size:12px; color:#888888;'>Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña actual seguirá siendo válida.</p>
            ";

            $cuerpoHtml = generarPlantillaCorreo(
                '🔒',
                'Recuperar Contraseña',
                $contenidoCorreo,
                'Restablecer Contraseña',
                $enlaceReset
            );

            enviarCorreo($correo, 'Recupera tu contraseña - ServiceCore', $cuerpoHtml);
        }

        // Siempre mostrar el mismo mensaje exista o no el correo (seguridad)
        $mensajeExito  = '1';
        $correoEnviado = htmlspecialchars($correo);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Recuperar Contraseña | ServiceCore Corp.</title>
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
    background-image:
      var(--luz-fondo, none),
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

  .material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    font-size: 24px;
    line-height: 1;
    display: inline-block;
    vertical-align: middle;
  }
  .icono-md { font-size: 20px; }
  .icono-sm { font-size: 16px; }

  .encabezado {
    background: rgba(30, 24, 88, 0.85);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--borde);
    position: sticky;
    top: 0;
    z-index: 50;
  }
  .encabezado-interior {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    padding: var(--normal) var(--margen);
  }
  .marca { display: flex; align-items: center; gap: var(--pequeno); }
  .marca-nombre {
    font-family: var(--fuente-titulo);
    font-size: 20px;
    font-weight: 700;
    color: var(--blanco);
  }
  .nav-encabezado { display: flex; gap: var(--grande); }
  .nav-encabezado a {
    font-size: 12px;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: var(--texto-suave);
    transition: color 0.2s, background-color 0.2s, box-shadow 0.2s;
    padding: 6px 14px;
    border-radius: 10px;
  }
  .nav-encabezado a:hover {
    background-color: #c3c0e8;
    box-shadow: 0 6px 20px rgba(119, 115, 235, 0.5);
    color: #1e1858;
  }
  @media (max-width: 640px) { .nav-encabezado { display: none; } }

  .contenido {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--enorme) var(--margen);
  }
  .envoltorio { width: 100%; max-width: 448px; }

  .tarjeta-envuelta {
    position: relative;
    border-radius: var(--radio-lg);
    padding: 1px;
    background: var(--borde);
  }

  .tarjeta {
    position: relative;
    z-index: 1;
    background: rgba(30, 24, 88, 0.88);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px rgba(113, 86, 131, 0.5);
    border-radius: calc(var(--radio-lg) - 1px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .tarjeta-encabezado {
    position: relative;
    height: 120px;
    background: linear-gradient(135deg, var(--acento) 0%, var(--fondo-medio) 100%);
    display: flex;
    align-items: flex-end;
    padding: var(--normal) var(--grande);
    gap: var(--normal);
    overflow: hidden;
  }
  .icono-tarjeta-encabezado {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: var(--radio-md);
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .tarjeta-encabezado h1 {
    color: var(--blanco);
    font-family: var(--fuente-titulo);
    font-size: 20px;
    font-weight: 700;
  }

  .tarjeta-cuerpo {
    padding: var(--grande);
    display: flex;
    flex-direction: column;
    gap: var(--grande);
  }

  .descripcion {
    font-size: 14px;
    line-height: 22px;
    color: var(--texto-suave);
  }

  .formulario { display: flex; flex-direction: column; gap: var(--normal); }
  .campo { display: flex; flex-direction: column; gap: var(--chico); }
  .etiqueta-campo {
    font-size: 12px;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: var(--texto-suave);
    margin-left: 4px;
  }
  .caja-entrada { position: relative; }
  .icono-entrada {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--texto-tenue);
    pointer-events: none;
  }
  .entrada {
    width: 100%;
    padding: 12px 16px 12px 40px;
    border-radius: var(--radio-md);
    border: 1px solid var(--borde);
    background: rgba(30, 24, 88, 0.6);
    color: var(--blanco);
    outline: none;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .entrada::placeholder { color: var(--texto-tenue); }
  .entrada:focus {
    border-color: var(--acento-claro);
    box-shadow: 0 0 0 2px rgba(119, 115, 235, 0.25);
  }

  .boton-enviar {
    width: 100%;
    background: linear-gradient(135deg, var(--acento) 0%, var(--acento-claro) 100%);
    color: var(--blanco);
    padding: 13px;
    border-radius: var(--radio-md);
    font-family: var(--fuente-titulo);
    font-size: 16px;
    font-weight: 700;
    box-shadow: 0 4px 16px rgba(119, 115, 235, 0.35);
    transition: opacity 0.2s, transform 0.1s, box-shadow 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  .boton-enviar:hover {
    opacity: 0.92;
    box-shadow: 0 6px 20px rgba(119, 115, 235, 0.5);
  }
  .boton-enviar:active { transform: scale(0.98); }

  .enlace-volver {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    font-size: 12px;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: var(--acento-claro);
    transition: opacity 0.2s;
    padding-top: var(--pequeno);
  }
  .enlace-volver:hover { opacity: 0.75; text-decoration: underline; }

  .estado-exito {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: var(--normal);
    padding: var(--mayor) 0;
  }
  .icono-exito {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 50%;
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .icono-exito .material-symbols-outlined {
    font-size: 40px;
    color: #22c55e;
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  }
  .exito-titulo {
    font-family: var(--fuente-titulo);
    font-size: 20px;
    font-weight: 700;
    color: var(--blanco);
  }
  .exito-descripcion {
    font-size: 14px;
    line-height: 22px;
    color: var(--texto-suave);
  }
  .exito-descripcion strong { color: var(--acento-claro); }
  .boton-reintentar {
    font-size: 12px;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: var(--acento-claro);
    margin-top: var(--pequeno);
  }
  .boton-reintentar:hover { text-decoration: underline; }

  .texto-ayuda {
    margin-top: var(--grande);
    text-align: center;
    font-size: 11px;
    color: var(--texto-tenue);
  }
  .texto-ayuda a { color: var(--acento-claro); font-weight: 600; }
  .texto-ayuda a:hover { text-decoration: underline; }

  .pie {
    background: rgba(30, 24, 88, 0.85);
    border-top: 1px solid var(--borde);
  }
  .pie-interior {
    display: flex;
    flex-direction: column;
    gap: var(--normal);
    padding: var(--grande) var(--margen);
    max-width: 1280px;
    margin: 0 auto;
  }
  @media (min-width: 640px) {
    .pie-interior { flex-direction: row; justify-content: space-between; align-items: center; }
  }
  .pie-marca { display: flex; align-items: center; gap: 8px; }
  .pie-nombre {
    font-family: var(--fuente-titulo);
    font-size: 12px;
    font-weight: 700;
    color: var(--blanco);
    letter-spacing: 0.05em;
  }
  .pie-derechos { font-size: 11px; color: var(--texto-suave); }
  .pie-nav { display: flex; gap: var(--grande); }
  .pie-nav a {
    font-size: 12px;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: var(--texto-tenue);
    transition: color 0.2s;
  }
  .pie-nav a:hover { color: var(--acento-claro); }

  @keyframes girar { to { transform: rotate(360deg); } }
  .girando { display: inline-block; animation: girar 0.8s linear infinite; }

  @keyframes aparecer {
    from { opacity: 0; transform: scale(0.95); }
    to   { opacity: 1; transform: scale(1); }
  }
  .aparecer { animation: aparecer 0.4s ease forwards; }
</style>
</head>
<body>

<header class="encabezado">
  <div class="encabezado-interior">
    <div class="marca">
      <span><img class="logo" src="img/logoSC.png" alt="SERVICECORE LOGO"></span>
      <span class="marca-nombre">ServiceCore</span>
    </div>
    <nav class="nav-encabezado">
      <a href="index.php">Página Principal</a>
    </nav>
  </div>
</header>

<main class="contenido">
  <div class="envoltorio">

    <div class="tarjeta-envuelta" id="tarjetaEnvuelta">
      <div class="tarjeta" id="tarjeta">

        <div class="tarjeta-encabezado">
          <div class="icono-tarjeta-encabezado">
            <span class="material-symbols-outlined" style="font-size:28px; color:#ffffff;">lock_reset</span>
          </div>
          <h1>Recuperar Contraseña</h1>
        </div>

        <div class="tarjeta-cuerpo" id="cuerpotarjeta">

          <?php if ($mensajeExito === '1'): ?>
            <!-- Estado de éxito (renderizado por PHP tras enviar) -->
            <div class="estado-exito aparecer">
              <div class="icono-exito">
                <span class="material-symbols-outlined">check_circle</span>
              </div>
              <p class="exito-titulo">¡Correo Enviado!</p>
              <p class="exito-descripcion">
                Si existe una cuenta asociada a <strong><?= $correoEnviado ?></strong>,
                recibirás un correo con instrucciones en unos momentos.
              </p>
              <button class="boton-reintentar" onclick="location.reload()">
                Intentar con otro correo
              </button>
            </div>
          <?php else: ?>

            <p class="descripcion">
              Ingresa tu correo para recibir las instrucciones de recuperación. Te enviaremos un enlace seguro para restablecer tu acceso.
            </p>

            <form id="formularioRecuperar" class="formulario" method="POST" action="recuperarpass.php" novalidate>
              <div class="campo">
                <label class="etiqueta-campo" for="email">Email</label>
                <div class="caja-entrada">
                  <span class="material-symbols-outlined icono-entrada icono-md">mail</span>
                  <input class="entrada" id="email" name="email"
                         type="email" placeholder="nombre@empresa.com" required/>
                </div>
              </div>

              <button class="boton-enviar" type="submit" id="botonEnviar">
                <span>Enviar Instrucciones</span>
                <span class="material-symbols-outlined icono-md">send</span>
              </button>
            </form>

            <a class="enlace-volver" href="login.php">
              <span class="material-symbols-outlined icono-sm">arrow_back</span>
              Volver al Login
            </a>

          <?php endif; ?>

        </div>
      </div>
    </div>

    <p class="texto-ayuda">
      ¿Tienes problemas para acceder?
      <a href="#">Contactar a soporte técnico</a>
    </p>

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

<script>
  // Spotlight en el fondo (efecto visual decorativo)
  const cuerpo     = document.body;
  const encabezado = document.querySelector('.encabezado');
  const piePagina  = document.querySelector('.pie');
  const envuelta   = document.getElementById('tarjetaEnvuelta');

  let luzX = -999, luzY = -999;
  let destX = -999, destY = -999;
  let brillo = 0, brilloDest = 0;

  function estaEnZonaLibre(cx, cy) {
    function cubre(el) {
      const r = el.getBoundingClientRect();
      return cx >= r.left && cx <= r.right && cy >= r.top && cy <= r.bottom;
    }
    return !cubre(encabezado) && !cubre(piePagina) && !cubre(envuelta);
  }

  document.addEventListener('mousemove', (e) => {
    destX = e.clientX;
    destY = e.clientY;
    brilloDest = estaEnZonaLibre(e.clientX, e.clientY) ? 1 : 0;
  });
  document.addEventListener('mouseleave', () => { brilloDest = 0; });

  function animar() {
    luzX += (destX - luzX) * 0.1;
    luzY += (destY - luzY) * 0.1;
    const velocidad = brilloDest > brillo ? 0.08 : 0.05;
    brillo += (brilloDest - brillo) * velocidad;

    if (brillo > 0.01) {
      cuerpo.style.setProperty('--luz-fondo',
        `radial-gradient(500px circle at ${luzX}px ${luzY}px,
          rgba(141,48,212, ${0.25 * brillo}) 0%,
          rgba(97,47,131,  ${0.15 * brillo}) 35%,
          transparent 70%)`
      );
    } else {
      cuerpo.style.setProperty('--luz-fondo', 'none');
    }
    requestAnimationFrame(animar);
  }
  requestAnimationFrame(animar);

  // Loader visual al enviar el formulario (el envío real lo hace PHP)
  const formulario  = document.getElementById('formularioRecuperar');
  const botonEnviar = document.getElementById('botonEnviar');

  if (formulario) {
    formulario.addEventListener('submit', () => {
      botonEnviar.disabled = true;
      botonEnviar.innerHTML = `
        <span class="girando"><span class="material-symbols-outlined" style="font-size:20px">sync</span></span>
        Procesando...
      `;
      botonEnviar.style.opacity = '0.8';
      // El formulario continúa su envío normal hacia PHP
    });
  }
</script>
</body>
</html>
