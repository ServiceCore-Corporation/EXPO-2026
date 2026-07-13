<?php
require_once 'conexion.php';
require_once 'correo.php';

$mensajeExito = '';
$correoEnviado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = trim($_POST['email'] ?? '');

    if (!empty($correo) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $consulta = $conexion->prepare("SELECT id_usuario, nombre FROM usuario WHERE correo = ? LIMIT 1");
        $consulta->bind_param("s", $correo);
        $consulta->execute();
        $resultado = $consulta->get_result();
        $usuario   = $resultado->fetch_assoc();
        $consulta->close();

        if ($usuario) {

            $token       = bin2hex(random_bytes(32));
            $expiracion  = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $fechaActual = date('Y-m-d H:i:s');

            $invalidar = $conexion->prepare("UPDATE recuperacion_password SET usado = 1 WHERE id_usuario = ? AND usado = 0");
            $invalidar->bind_param("i", $usuario['id_usuario']);
            $invalidar->execute();
            $invalidar->close();

            $insertar = $conexion->prepare("
                INSERT INTO recuperacion_password (id_usuario, token, expiracion, usado, fecha_creacion)
                VALUES (?, ?, ?, 0, ?)
            ");
            $insertar->bind_param("isss", $usuario['id_usuario'], $token, $expiracion, $fechaActual);
            $insertar->execute();
            $insertar->close();

            $protocolo   = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $dominio     = $_SERVER['HTTP_HOST'];
            $enlaceReset = "{$protocolo}{$dominio}/restablecer_password.php?token={$token}";

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
<link rel="icon" type="image/png" href="img/LogoNav.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/recuperarpass.css">
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
            <!-- Estado de éxito -->
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
    });
  }
</script>
</body>
</html>
