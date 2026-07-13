<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

require 'conexion.php';

$success = false; 

function correoExisteRealmente(string $correo): bool
{
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) return false;

    $dominio = substr(strrchr($correo, "@"), 1);

    if (!checkdnsrr($dominio, "MX")) return false;

    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre        = trim($_POST['nombre'] ?? '');
    $correo        = trim($_POST['correo'] ?? '');
    $password      = $_POST['password'] ?? '';
    $confirmarPass = $_POST['confirmar'] ?? ''; 
    $id_rol        = (int)($_POST['id_rol'] ?? 5); 

    if (empty($nombre) || empty($correo) || empty($password) || empty($confirmarPass)) {
        header("Location: registrarse.php?error=Todos+los+campos+son+obligatorios");
        exit();
    }

    if (strlen($nombre) < 3) {
        header("Location: registrarse.php?error=Nombre+muy+corto");
        exit();
    }

    if (!correoExisteRealmente($correo)) {
        header("Location: registrarse.php?error=Correo+invalido");
        exit();
    }

    if (strlen($password) < 8) {
        header("Location: registrarse.php?error=La+contrasena+debe+tener+8+caracteres");
        exit();
    }

    if (!preg_match('/[A-Z]/', $password)) {
        header("Location: registrarse.php?error=Debe+tener+una+mayuscula");
        exit();
    }

    if (!preg_match('/[0-9]/', $password)) {
        header("Location: registrarse.php?error=Debe+tener+un+numero");
        exit();
    }

    if ($password !== $confirmarPass) {
        header("Location: registrarse.php?error=Las+contrasenas+no+coinciden");
        exit();
    }

    $verificar = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
    $verificar->bind_param("s", $correo);
    $verificar->execute();
    $resultado = $verificar->get_result();

    if ($resultado->num_rows > 0) {
        $verificar->close();
        header("Location: registrarse.php?error=Correo+ya+registrado");
        exit();
    }
    $verificar->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO usuario (nombre, correo, pass, id_rol, activo, id_empresa)
         VALUES (?, ?, ?, ?, 1, 1)"
    );
    $stmt->bind_param("sssi", $nombre, $correo, $passwordHash, $id_rol);

    if (!$stmt->execute()) {
        $stmt->close();
        header("Location: registrarse.php?error=Error+al+registrar");
        exit();
    }

    $stmt->close();
    $conn->close();

    header("Location: login.php?registro=exitoso");
    exit();
}

$conn->close();

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Crear Cuenta | ServiceCore Corp.</title>
<link rel="icon" type="image/png" href="img/LogoNav.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<link rel="stylesheet" href="css/registrarse.css">
</head>

<body>

<header class="encabezado">
  <div class="encabezado-interior">
    <div class="marca">
      <img class="logo" src="img/logoSC.png" alt="logo">
      <span class="marca-nombre">ServiceCore</span>
    </div>
    <nav class="nav-encabezado">
      <a href="index.php">Página Principal</a>
      <a href="login.php">Iniciar Sesión</a>
    </nav>
  </div>
</header>

<main class="contenido">
<div class="envoltorio">
<div class="tarjeta-envuelta">
<div class="tarjeta">

<div class="tarjeta-encabezado">
  <div class="icono-tarjeta-encabezado">
    <span class="material-symbols-outlined">person_add</span>
  </div>
  <h1>Crear Cuenta</h1>
</div>

<div class="tarjeta-cuerpo">

  <!-- PASOS -->
  <div class="pasos" id="pasos">
    <div class="paso activo" id="paso-1">
      <div class="paso-circulo">1</div>
      <span>Datos</span>
    </div>
    <div class="conector"></div>
    <div class="paso" id="paso-2">
      <div class="paso-circulo">2</div>
      <span>Seguridad</span>
    </div>
    <div class="conector"></div>
    <div class="paso" id="paso-3">
      <div class="paso-circulo">3</div>
      <span>Rol</span>
    </div>
  </div>

  <!-- PANEL ÉXITO -->
  <div class="panel-exito <?= $success ? 'visible' : '' ?>" id="panelExito">
    <div class="circulo-exito">
      <span class="material-symbols-outlined" style="font-size:36px;color:white;">check_circle</span>
    </div>
    <h2>¡Cuenta Creada!</h2>
    <p>Tu cuenta fue registrada correctamente.</p>
    <a href="login.php" class="boton-principal" style="text-align:center;display:block;padding:13px;">
      Ir al Login
    </a>
  </div>

  <!-- FORMULARIO -->
  <form method="POST" action="registrarse.php" id="formRegistro">

    <?php if (!empty($error)): ?>
    <div class="alerta-servidor">
      <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- PASO 1: DATOS -->
    <div class="panel-paso visible" id="panel-1">

      <div class="campo">
        <label class="etiqueta-campo">Nombre completo</label>
        <div class="caja-entrada">
          <span class="material-symbols-outlined icono-entrada">person</span>
          <input class="entrada" type="text" id="nombre" name="nombre" required placeholder="Ej: Juan García">
        </div>
        <span class="msg-error" id="err-nombre">Ingresa tu nombre</span>
      </div>

      <div class="campo">
        <label class="etiqueta-campo">Correo electrónico</label>
        <div class="caja-entrada">
          <span class="material-symbols-outlined icono-entrada">mail</span>
          <input class="entrada" type="email" id="email" name="correo" required placeholder="usuario@empresa.com">
        </div>
        <span class="msg-error" id="err-email">Correo inválido</span>
      </div>

      <div class="acciones">
        <button class="boton-principal" type="button" id="btnPaso1">Siguiente</button>
      </div>

    </div>

    <!-- PASO 2: SEGURIDAD -->
    <div class="panel-paso" id="panel-2">

      <div class="campo">
        <label class="etiqueta-campo">Contraseña</label>
        <div class="caja-entrada">
          <span class="material-symbols-outlined icono-entrada">lock</span>
          <input class="entrada" type="password" id="password" name="password" required>
        </div>
        <span class="msg-error" id="err-password">Mínimo 8 caracteres, una mayúscula y un número</span>
      </div>

      <div class="campo">
        <label class="etiqueta-campo">Confirmar Contraseña</label>
        <div class="caja-entrada">
          <span class="material-symbols-outlined icono-entrada">lock_reset</span>
          <!-- ← nombre corregido a "confirmar" -->
          <input class="entrada" type="password" id="confirmar" name="confirmar" required>
        </div>
        <span class="msg-error" id="err-confirmar">Las contraseñas no coinciden</span>
      </div>

      <div class="acciones">
        <button class="boton-secundario" type="button" id="btnAtras2">Atrás</button>
        <button class="boton-principal" type="button" id="btnPaso2">Siguiente</button>
      </div>

    </div>

    <!-- PASO 3: ROL -->
    <div class="panel-paso" id="panel-3">

      <div class="campo">
        <label class="etiqueta-campo">Selecciona tu rol</label>
        <div class="roles-grid">

          <label>
            <input class="rol-opcion" type="radio" name="id_rol" value="5" checked>
            <div class="rol-tarjeta">Cliente</div>
          </label>

          <label>
            <input class="rol-opcion" type="radio" name="id_rol" value="4">
            <div class="rol-tarjeta">Agente</div>
          </label>

          <label>
            <input class="rol-opcion" type="radio" name="id_rol" value="3">
            <div class="rol-tarjeta">Supervisor</div>
          </label>

          <label>
            <input class="rol-opcion" type="radio" name="id_rol" value="2">
            <div class="rol-tarjeta">Admin</div>
          </label>

        </div>
      </div>

      <label class="terminos">
        <input type="checkbox" id="terminos">
        <span>Acepto los términos y condiciones</span>
      </label>

      <span class="msg-error" id="err-terminos">Debes aceptar los términos</span>

      <div class="acciones">
        <button class="boton-secundario" type="button" id="btnAtras3">Atrás</button>
        <button class="boton-principal" type="submit" id="btnRegistrar">Crear Cuenta</button>
      </div>

    </div>

  </form>

  <p class="texto-login">
    ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
  </p>

</div>
</div>
</div>
</div>
</main>

<footer class="pie">
  <div class="pie-interior">
    <div>© 2026 ServiceCore</div>
    <div>Sistema Empresarial</div>
  </div>
</footer>

<script>

function mostrarPaso(n) {
  [1, 2, 3].forEach(i => {
    document.getElementById(`panel-${i}`).classList.toggle('visible', i === n);
    document.getElementById(`paso-${i}`).classList.toggle('activo', i === n);
  });
}

function setError(inputId, errorId, condition, mensaje) {
  const input = document.getElementById(inputId);
  const span  = document.getElementById(errorId);
  if (condition) {
    input.classList.add('entrada-error');
    span.textContent = mensaje;
    span.classList.add('visible');
  } else {
    input.classList.remove('entrada-error');
    span.classList.remove('visible');
  }
  return !condition;
}

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

document.getElementById('nombre').addEventListener('blur', function () {
  setError('nombre', 'err-nombre', this.value.trim().length < 2, 'Ingresa tu nombre (mínimo 2 caracteres)');
});
document.getElementById('nombre').addEventListener('input', function () {
  if (this.value.trim()) setError('nombre', 'err-nombre', false, '');
});

document.getElementById('email').addEventListener('blur', function () {
  setError('email', 'err-email', !emailRegex.test(this.value.trim()), 'Correo inválido');
});
document.getElementById('email').addEventListener('input', function () {
  if (emailRegex.test(this.value.trim())) setError('email', 'err-email', false, '');
});

document.getElementById('password').addEventListener('input', function () {
  const pass = this.value;
  let msg = '';
  if (pass.length === 0)       msg = 'La contraseña es obligatoria';
  else if (pass.length < 8)    msg = 'Mínimo 8 caracteres';
  else if (!/[A-Z]/.test(pass)) msg = 'Debe tener al menos una mayúscula';
  else if (!/[0-9]/.test(pass)) msg = 'Debe tener al menos un número';
  setError('password', 'err-password', msg !== '', msg);

  const conf = document.getElementById('confirmar').value;
  if (conf) {
    setError('confirmar', 'err-confirmar', pass !== conf, 'Las contraseñas no coinciden');
  }
});

document.getElementById('confirmar').addEventListener('input', function () {
  const pass = document.getElementById('password').value;
  setError('confirmar', 'err-confirmar', this.value !== pass, 'Las contraseñas no coinciden');
});

document.getElementById('btnPaso1').addEventListener('click', () => {

  const nombre = document.getElementById('nombre').value.trim();
  const email  = document.getElementById('email').value.trim();

  const v1 = setError('nombre', 'err-nombre', nombre.length < 2, 'Ingresa tu nombre completo');
  const v2 = setError('email',  'err-email',  !emailRegex.test(email), 'Correo electrónico inválido');

  if (v1 && v2) mostrarPaso(2);
});

document.getElementById('btnPaso2').addEventListener('click', () => {

  const pass = document.getElementById('password').value;
  const conf = document.getElementById('confirmar').value;

  let msgPass = '';
  if (pass.length === 0)        msgPass = 'La contraseña es obligatoria';
  else if (pass.length < 8)     msgPass = 'Mínimo 8 caracteres';
  else if (!/[A-Z]/.test(pass)) msgPass = 'Debe tener al menos una mayúscula';
  else if (!/[0-9]/.test(pass)) msgPass = 'Debe tener al menos un número';

  const v1 = setError('password',  'err-password',  msgPass !== '',   msgPass);
  const v2 = setError('confirmar', 'err-confirmar', pass !== conf,    'Las contraseñas no coinciden');

  if (v1 && v2) mostrarPaso(3);
});

document.getElementById('btnAtras2').addEventListener('click', () => mostrarPaso(1));
document.getElementById('btnAtras3').addEventListener('click', () => mostrarPaso(2));

document.getElementById('btnRegistrar').addEventListener('click', (e) => {
  const terms = document.getElementById('terminos').checked;
  const span  = document.getElementById('err-terminos');

  if (!terms) {
    span.textContent = 'Debes aceptar los términos y condiciones';
    span.classList.add('visible');
    e.preventDefault();
  } else {
    span.classList.remove('visible');
  }
});

</script>

</body>
</html>