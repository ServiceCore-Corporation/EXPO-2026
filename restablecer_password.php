<?php
require_once 'conexion.php';

$token        = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenValido  = false;
$idUsuario    = null;
$mensajeError = '';
$exito        = false;

// Validar que el token exista, no esté usado y no haya expirado
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

// Procesar el cambio de contraseña
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

        // Actualizar la contraseña del usuario
        $hashPassword = password_hash($nuevaPassword, PASSWORD_BCRYPT);
        $actualizar   = $conexion->prepare("UPDATE usuario SET pass = ? WHERE id_usuario = ?");
        $actualizar->bind_param("si", $hashPassword, $idUsuario);
        $actualizar->execute();
        $actualizar->close();

        // Marcar el token como usado para que no se reutilice
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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
  :root {
    --fondo:           #1e1858;
    --fondo-medio:     #2a2470;
    --acento:          #5750ad;
    --acento-claro:    #7773eb;
    --blanco:          #ffffff;
    --texto-principal: #ffffff;
    --texto-suave:     #c3c0e8;
    --texto-tenue:     #8f8cc4;
    --borde:           #4a44b8;
    --error:           #ff6b6b;
    --chico:   0.25rem;
    --pequeno: 0.5rem;
    --normal:  1rem;
    --grande:  1.5rem;
    --mayor:   2rem;
    --enorme:  3rem;
    --margen:  24px;
    --radio-md: 0.5rem;
    --radio-lg: 0.75rem;
    --fuente:        'Poppins', sans-serif;
    --fuente-titulo: 'Montserrat', sans-serif;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: var(--fuente);
    background-color: #1e1858;
    background-image: var(--luz-fondo, none), radial-gradient(ellipse at top left, #2a2470 0%, #1e1858 60%);
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
    max-width: 1280px;
    margin: 0 auto;
    padding: var(--normal) var(--margen);
  }
  .marca { display: flex; align-items: center; gap: var(--pequeno); }
  .marca-nombre { font-family: var(--fuente-titulo); font-size: 20px; font-weight: 700; color: var(--blanco); }

  .contenido {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--enorme) var(--margen);
  }
  .envoltorio { width: 100%; max-width: 448px; }

  .tarjeta-envuelta { position: relative; border-radius: var(--radio-lg); padding: 1px; background: var(--borde); }
  .tarjeta {
    position: relative;
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
  }
  .icono-tarjeta-encabezado {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: var(--radio-md);
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .tarjeta-encabezado h1 { color: var(--blanco); font-family: var(--fuente-titulo); font-size: 20px; font-weight: 700; }

  .tarjeta-cuerpo { padding: var(--grande); display: flex; flex-direction: column; gap: var(--grande); }
  .descripcion { font-size: 14px; line-height: 22px; color: var(--texto-suave); }

  .formulario { display: flex; flex-direction: column; gap: var(--normal); }
  .campo { display: flex; flex-direction: column; gap: var(--chico); }
  .etiqueta-campo { font-size: 12px; letter-spacing: 0.05em; font-weight: 600; color: var(--texto-suave); margin-left: 4px; }
  .caja-entrada { position: relative; }
  .icono-entrada { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--texto-tenue); pointer-events: none; }
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
  .entrada:focus { border-color: var(--acento-claro); box-shadow: 0 0 0 2px rgba(119, 115, 235, 0.25); }
  .entrada-con-boton { padding-right: 44px; }
  .boton-mostrar { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--texto-tenue); }
  .boton-mostrar:hover { color: var(--blanco); }

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
  .boton-enviar:hover { opacity: 0.92; box-shadow: 0 6px 20px rgba(119, 115, 235, 0.5); }
  .boton-enviar:active { transform: scale(0.98); }

  .enlace-volver {
    display: flex; align-items: center; justify-content: center; gap: 4px;
    font-size: 12px; letter-spacing: 0.05em; font-weight: 600;
    color: var(--acento-claro); padding-top: var(--pequeno);
  }
  .enlace-volver:hover { opacity: 0.75; text-decoration: underline; }

  .alerta-error {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,107,107,0.12); border: 1px solid rgba(255,107,107,0.4);
    border-radius: 8px; padding: 10px 14px; font-size: 13px; color: var(--error);
  }

  .estado-exito { display: flex; flex-direction: column; align-items: center; text-align: center; gap: var(--normal); padding: var(--mayor) 0; }
  .icono-exito {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 50%;
    width: 72px; height: 72px;
    display: flex; align-items: center; justify-content: center;
  }
  .icono-exito .material-symbols-outlined { font-size: 40px; color: #22c55e; font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
  .exito-titulo { font-family: var(--fuente-titulo); font-size: 20px; font-weight: 700; color: var(--blanco); }
  .exito-descripcion { font-size: 14px; line-height: 22px; color: var(--texto-suave); }

  .estado-token-invalido { display: flex; flex-direction: column; align-items: center; text-align: center; gap: var(--normal); padding: var(--mayor) 0; }
  .icono-invalido {
    background: rgba(255, 107, 107, 0.15);
    border: 1px solid rgba(255, 107, 107, 0.3);
    border-radius: 50%;
    width: 72px; height: 72px;
    display: flex; align-items: center; justify-content: center;
  }
  .icono-invalido .material-symbols-outlined { font-size: 40px; color: var(--error); }

  .pie { background: rgba(30, 24, 88, 0.85); border-top: 1px solid var(--borde); }
  .pie-interior { display: flex; flex-direction: column; gap: var(--normal); padding: var(--grande) var(--margen); max-width: 1280px; margin: 0 auto; }
  @media (min-width: 640px) { .pie-interior { flex-direction: row; justify-content: space-between; align-items: center; } }
  .pie-nombre { font-family: var(--fuente-titulo); font-size: 12px; font-weight: 700; color: var(--blanco); letter-spacing: 0.05em; }
  .pie-derechos { font-size: 11px; color: var(--texto-suave); }

  @keyframes girar { to { transform: rotate(360deg); } }
  .girando { display: inline-block; animation: girar 0.8s linear infinite; }
  @keyframes aparecer { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
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
  // Mostrar/ocultar contraseña en ambos campos
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

  // Loader visual al enviar el formulario
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
