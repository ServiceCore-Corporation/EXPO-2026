<?php
require_once 'conexion.php';

$token        = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenValido  = false;
$idUsuario    = null;
$mensajeError = '';
$exito        = false;

if (!empty($token)) {

    $consulta = $conexion->prepare("
        SELECT id_usuario, expiracion, usado
        FROM recuperacion_password
        WHERE token = ?
        LIMIT 1
    ");
    $consulta->bind_param("s", $token);
    $consulta->execute();
    $resultado = $consulta->get_result();
    $fila      = $resultado->fetch_assoc();
    $consulta->close();

    if (!$fila) {
        $mensajeError = "El enlace no es válido.";
    } elseif ((int)$fila['usado'] === 1) {
        $mensajeError = "Este enlace ya fue utilizado.";
    } elseif (strtotime($fila['expiracion']) < time()) {
        $mensajeError = "El enlace ha expirado. Solicita uno nuevo.";
    } else {
        $tokenValido = true;
        $idUsuario   = $fila['id_usuario'];
    }
} else {
    $mensajeError = "Enlace incompleto o inválido.";
}

if ($tokenValido && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $nuevaPassword     = trim($_POST['password'] ?? '');
    $confirmarPassword = trim($_POST['confirmar_password'] ?? '');

    if (empty($nuevaPassword) || empty($confirmarPassword)) {
        $mensajeError = "Completa ambos campos.";
    } elseif ($nuevaPassword !== $confirmarPassword) {
        $mensajeError = "Las contraseñas no coinciden.";
    } elseif (strlen($nuevaPassword) < 8) {
        $mensajeError = "La contraseña debe tener al menos 8 caracteres.";
    } else {

        $hashPassword = password_hash($nuevaPassword, PASSWORD_BCRYPT);
        $actualizar   = $conexion->prepare("UPDATE usuario SET pass = ? WHERE id_usuario = ?");
        $actualizar->bind_param("si", $hashPassword, $idUsuario);
        $actualizar->execute();
        $actualizar->close();

        $marcarUsado = $conexion->prepare("UPDATE recuperacion_password SET usado = 1 WHERE token = ?");
        $marcarUsado->bind_param("s", $token);
        $marcarUsado->execute();
        $marcarUsado->close();

        $exito = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Restablecer Contraseña | ServiceCore Corp.</title>
<link rel="icon" type="image/png" href="img/LogoNav.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/restablecer_password.css">
</head>
<body>

<header class="encabezado">
  <div class="encabezado-interior">
    <div class="marca">
      <span><img class="logo" src="img/logoSC.png" alt="SERVICECORE LOGO"></span>
      <span class="marca-nombre">ServiceCore</span>
    </div>
  </div>
</header>

<main class="contenido">
  <div class="envoltorio">

    <div class="tarjeta-envuelta">
      <div class="tarjeta">

        <div class="tarjeta-encabezado">
          <div class="icono-tarjeta-encabezado">
            <span class="material-symbols-outlined" style="font-size:28px; color:#ffffff;">password</span>
          </div>
          <h1>Nueva Contraseña</h1>
        </div>

        <div class="tarjeta-cuerpo">

          <?php if ($exito): ?>
            <!-- Contraseña cambiada correctamente -->
            <div class="estado-exito aparecer">
              <div class="icono-exito">
                <span class="material-symbols-outlined">check_circle</span>
              </div>
              <p class="exito-titulo">¡Contraseña Actualizada!</p>
              <p class="exito-descripcion">Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión con tu nueva contraseña.</p>
              <a href="login.php" class="boton-enviar" style="margin-top:10px; text-decoration:none;">
                Ir al Login
              </a>
            </div>

          <?php elseif (!$tokenValido): ?>
            <!-- Token inválido, usado o expirado -->
            <div class="estado-token-invalido aparecer">
              <div class="icono-invalido">
                <span class="material-symbols-outlined">error</span>
              </div>
              <p class="exito-titulo"><?= htmlspecialchars($mensajeError) ?></p>
              <p class="exito-descripcion">Solicita un nuevo enlace de recuperación para continuar.</p>
              <a href="recuperarpass.php" class="boton-enviar" style="margin-top:10px; text-decoration:none;">
                Solicitar Nuevo Enlace
              </a>
            </div>

          <?php else: ?>
            <!-- Formulario para nueva contraseña -->
            <p class="descripcion">Crea una nueva contraseña segura para tu cuenta. Debe tener al menos 8 caracteres.</p>

            <?php if (!empty($mensajeError)): ?>
              <div class="alerta-error">
                <span class="material-symbols-outlined">error</span>
                <?= htmlspecialchars($mensajeError) ?>
              </div>
            <?php endif; ?>

            <form class="formulario" method="POST" action="restablecer_password.php?token=<?= htmlspecialchars($token) ?>">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

              <div class="campo">
                <label class="etiqueta-campo" for="password">Nueva Contraseña</label>
                <div class="caja-entrada">
                  <span class="material-symbols-outlined icono-entrada icono-md">lock</span>
                  <input class="entrada entrada-con-boton" id="password" name="password" type="password" placeholder="••••••••" required minlength="8"/>
                  <button class="boton-mostrar" type="button" id="verPassword1" aria-label="Mostrar contraseña">
                    <span class="material-symbols-outlined" style="font-size:20px">visibility</span>
                  </button>
                </div>
              </div>

              <div class="campo">
                <label class="etiqueta-campo" for="confirmar_password">Confirmar Contraseña</label>
                <div class="caja-entrada">
                  <span class="material-symbols-outlined icono-entrada icono-md">lock</span>
                  <input class="entrada entrada-con-boton" id="confirmar_password" name="confirmar_password" type="password" placeholder="••••••••" required minlength="8"/>
                  <button class="boton-mostrar" type="button" id="verPassword2" aria-label="Mostrar contraseña">
                    <span class="material-symbols-outlined" style="font-size:20px">visibility</span>
                  </button>
                </div>
              </div>

              <button class="boton-enviar" type="submit" id="botonGuardar">
                <span>Guardar Nueva Contraseña</span>
                <span class="material-symbols-outlined icono-md">check</span>
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

  </div>
</main>

<footer class="pie">
  <div class="pie-interior">
    <div class="pie-marca">
      <span class="pie-nombre">ServiceCore Corporation</span>
      <span class="pie-derechos">© 2026. Secure Enterprise Environment.</span>
    </div>
  </div>
</footer>

<script>
  function configurarToggle(idBoton, idCampo) {
    const boton = document.getElementById(idBoton);
    const campo = document.getElementById(idCampo);
    if (!boton || !campo) return;
    boton.addEventListener('click', () => {
      const esPassword = campo.type === 'password';
      campo.type = esPassword ? 'text' : 'password';
      boton.querySelector('.material-symbols-outlined').textContent = esPassword ? 'visibility_off' : 'visibility';
    });
  }
  configurarToggle('verPassword1', 'password');
  configurarToggle('verPassword2', 'confirmar_password');

  const formulario   = document.querySelector('.formulario');
  const botonGuardar = document.getElementById('botonGuardar');
  if (formulario) {
    formulario.addEventListener('submit', () => {
      botonGuardar.disabled = true;
      botonGuardar.innerHTML = `
        <span class="girando"><span class="material-symbols-outlined" style="font-size:20px">sync</span></span>
        Guardando...
      `;
      botonGuardar.style.opacity = '0.8';
    });
  }
</script>
</body>
</html>
