<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$success = false; // Variable necesaria para el HTML

// Cargar variables .env
function cargarEnv(string $ruta): void
{
    if (!file_exists($ruta)) return;

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {

        $linea = trim($linea);

        if ($linea === '' || str_starts_with($linea, '#')) continue;
        if (!str_contains($linea, '=')) continue;

        [$clave, $valor] = explode('=', $linea, 2);
        $_ENV[trim($clave)] = trim($valor);
    }
}

cargarEnv(__DIR__ . '/.env');

// Conexión
$conexion = new mysqli("localhost", "u936997481_ServiCore", "ServiceCore_2026", "u936997481_ServiceCore");

if ($conexion->connect_error) {
    die("Error de conexión");
}

// Validar correo real
function correoExisteRealmente(string $correo): bool
{
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) return false;

    $dominio = substr(strrchr($correo, "@"), 1);

    if (!checkdnsrr($dominio, "MX")) return false;

    return true;
}

// Enviar código de verificación
function enviarCodigoVerificacion(string $correoUsuario, string $nombreUsuario, string $codigo): bool
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $_ENV['MAIL_PORT'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($correoUsuario, $nombreUsuario);

        $mail->isHTML(true);
        $mail->Subject = 'Código de verificación - ServiceCore';

        $mail->Body = "
            <div style='font-family:Segoe UI,sans-serif;max-width:480px;margin:auto;
                        background:#0f172a;color:#f1f5f9;border-radius:12px;padding:40px'>
                <h2 style='margin:0 0 8px;font-size:22px'>Verificación de cuenta</h2>
                <p style='color:#94a3b8;margin:0 0 28px'>
                    Hola <strong>$nombreUsuario</strong>, este es tu código de verificación:
                </p>
                <div style='background:#1e293b;border:1px solid #334155;border-radius:10px;
                            padding:24px;text-align:center;margin-bottom:24px'>
                    <span style='font-size:38px;font-weight:700;letter-spacing:10px;color:#3b82f6'>
                        $codigo
                    </span>
                </div>
                <p style='color:#64748b;font-size:13px;margin:0'>
                    Expira en <strong style='color:#f1f5f9'>10 minutos</strong>.<br>
                    Si no solicitaste esto, ignora este correo.
                </p>
            </div>
        ";

        $mail->AltBody = "Tu código ServiceCore: $codigo";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error enviando correo: " . $mail->ErrorInfo);
        return false;
    }
}

// Procesar registro
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Solo nombre, sin apellido
    $nombre        = trim($_POST['nombre'] ?? '');
    $correo        = trim($_POST['correo'] ?? '');
    $password      = $_POST['password'] ?? '';
    $confirmarPass = $_POST['confirmar'] ?? ''; // ← nombre correcto del campo
    $id_rol        = (int)($_POST['id_rol'] ?? 5); // ← tomar del formulario

    // Campos vacíos
    if (empty($nombre) || empty($correo) || empty($password) || empty($confirmarPass)) {
        header("Location: registrarse.php?error=Todos+los+campos+son+obligatorios");
        exit();
    }

    // Nombre muy corto
    if (strlen($nombre) < 3) {
        header("Location: registrarse.php?error=Nombre+muy+corto");
        exit();
    }

    // Correo válido
    if (!correoExisteRealmente($correo)) {
        header("Location: registrarse.php?error=Correo+invalido");
        exit();
    }

    // Contraseña mínima
    if (strlen($password) < 8) {
        header("Location: registrarse.php?error=La+contrasena+debe+tener+8+caracteres");
        exit();
    }

    // Mayúscula
    if (!preg_match('/[A-Z]/', $password)) {
        header("Location: registrarse.php?error=Debe+tener+una+mayuscula");
        exit();
    }

    // Número
    if (!preg_match('/[0-9]/', $password)) {
        header("Location: registrarse.php?error=Debe+tener+un+numero");
        exit();
    }

    // Contraseñas iguales
    if ($password !== $confirmarPass) {
        header("Location: registrarse.php?error=Las+contrasenas+no+coinciden");
        exit();
    }

    // Verificar correo duplicado
    $verificar = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
    $verificar->bind_param("s", $correo);
    $verificar->execute();
    $resultado = $verificar->get_result();

    if ($resultado->num_rows > 0) {
        $verificar->close();
        header("Location: registrarse.php?error=Correo+ya+registrado");
        exit();
    }
    $verificar->close();

    // Hash contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario — id_empresa = 1 por defecto
    $stmt = $conexion->prepare(
        "INSERT INTO usuario (nombre, correo, pass, id_rol, activo, id_empresa)
         VALUES (?, ?, ?, ?, 1, 1)"
    );
    $stmt->bind_param("sssi", $nombre, $correo, $passwordHash, $id_rol);

    if (!$stmt->execute()) {
        $stmt->close();
        header("Location: registrarse.php?error=Error+al+registrar");
        exit();
    }

    $id_usuario = $stmt->insert_id;
    $stmt->close();

    // Generar código 2FA
    $codigo     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Guardar código
    $guardarCodigo = $conexion->prepare(
        "INSERT INTO usuarios_2fa (id_usuario, tipo, secreto, codigo_temporal, expiracion_codigo, activo)
         VALUES (?, 'email', '', ?, ?, 1)"
    );
    $guardarCodigo->bind_param("iss", $id_usuario, $codigo, $expiracion);
    $guardarCodigo->execute();
    $guardarCodigo->close();

    // Enviar correo
    $enviado = enviarCodigoVerificacion($correo, $nombre, $codigo);

    if (!$enviado) {
        // Eliminar usuario si no se pudo enviar el correo
        $eliminar = $conexion->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $eliminar->bind_param("i", $id_usuario);
        $eliminar->execute();
        $eliminar->close();

        header("Location: registrarse.php?error=No+se+pudo+enviar+el+correo");
        exit();
    }

    // Sesión temporal para 2FA
    session_regenerate_id(true);
    $_SESSION['2fa_id_usuario'] = $id_usuario;
    $_SESSION['2fa_correo']     = $correo;

    $conexion->close();

    header("Location: verificar_2fa.php");
    exit();
}

$conexion->close();

// Capturar error de URL
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Crear Cuenta | ServiceCore Corp.</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<style>
:root {
  --fondo:#1e1858;
  --fondo-medio:#2a2470;
  --acento:#5750ad;
  --acento-claro:#7773eb;
  --blanco:#fff;
  --texto-suave:#c3c0e8;
  --texto-tenue:#8f8cc4;
  --borde:#4a44b8;
  --error:#ff6b6b;
  --radio-md:0.5rem;
  --radio-lg:0.75rem;
  --fuente:'Poppins',sans-serif;
  --fuente-titulo:'Montserrat',sans-serif;
}

*{margin:0;padding:0;box-sizing:border-box;}

body{
  font-family:var(--fuente);
  background:#1e1858;
  color:#fff;
  min-height:100vh;
  display:flex;
  flex-direction:column;
}

a{text-decoration:none;color:inherit;}
button{border:none;cursor:pointer;}

/* ENCABEZADO */
.encabezado{
  background:rgba(30,24,88,0.85);
  border-bottom:1px solid var(--borde);
}

.encabezado-interior{
  max-width:1280px;
  margin:auto;
  padding:1rem 24px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.marca{display:flex;align-items:center;gap:10px;}

.logo{width:45px;height:35px;}

.marca-nombre{
  font-family:var(--fuente-titulo);
  font-weight:700;
  font-size:20px;
}

.nav-encabezado{display:flex;gap:20px;}
.nav-encabezado a{font-size:13px;color:var(--texto-suave);}

/* CONTENIDO */
.contenido{
  flex:1;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:40px 24px;
}

.envoltorio{width:100%;max-width:560px;}

.tarjeta-envuelta{
  border-radius:var(--radio-lg);
  padding:1px;
  background:var(--borde);
}

.tarjeta{
  background:rgba(30,24,88,0.95);
  border-radius:var(--radio-lg);
  overflow:hidden;
}

.tarjeta-encabezado{
  background:linear-gradient(135deg,var(--acento),var(--fondo-medio));
  padding:20px 24px;
  display:flex;
  align-items:center;
  gap:12px;
}

.icono-tarjeta-encabezado{
  background:rgba(255,255,255,0.15);
  padding:8px;
  border-radius:8px;
}

.tarjeta-cuerpo{
  padding:32px;
  display:flex;
  flex-direction:column;
  gap:24px;
}

/* PASOS */
.pasos{
  display:flex;
  align-items:center;
  justify-content:center;
}

.paso{display:flex;flex-direction:column;align-items:center;gap:5px;}

.paso-circulo{
  width:32px;
  height:32px;
  border-radius:50%;
  border:2px solid var(--borde);
  display:flex;
  justify-content:center;
  align-items:center;
  font-size:12px;
}

.paso.activo .paso-circulo{
  background:linear-gradient(135deg,var(--acento),var(--acento-claro));
  border-color:var(--acento-claro);
}

.conector{
  flex:1;
  height:2px;
  background:var(--borde);
  margin:0 5px 18px;
}

/* PANELES */
.panel-paso{display:none;flex-direction:column;gap:16px;}
.panel-paso.visible{display:flex;}

/* GRID */
.fila-doble{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
}

@media(max-width:480px){
  .fila-doble{grid-template-columns:1fr;}
}

/* CAMPOS */
.campo{display:flex;flex-direction:column;gap:5px;}

.etiqueta-campo{font-size:12px;color:var(--texto-suave);}

.caja-entrada{position:relative;}

.icono-entrada{
  position:absolute;
  top:50%;
  left:12px;
  transform:translateY(-50%);
  color:var(--texto-tenue);
  font-size:20px;
}

.entrada{
  width:100%;
  padding:12px 16px 12px 42px;
  border-radius:8px;
  border:1px solid var(--borde);
  background:rgba(30,24,88,0.6);
  color:#fff;
  outline:none;
  font-family:var(--fuente);
  font-size:14px;
}

.entrada:focus{border-color:var(--acento-claro);}

.entrada-error{border-color:var(--error)!important;}

.msg-error{display:none;color:var(--error);font-size:11px;}
.msg-error.visible{display:block;}

/* ACCIONES */
.acciones{display:flex;gap:16px;}

.boton-principal{
  flex:1;
  background:linear-gradient(135deg,var(--acento),var(--acento-claro));
  color:#fff;
  padding:13px;
  border-radius:8px;
  font-weight:700;
  font-family:var(--fuente);
  font-size:14px;
}

.boton-secundario{
  padding:13px 20px;
  border:1px solid var(--borde);
  border-radius:8px;
  color:var(--texto-suave);
  background:transparent;
  font-family:var(--fuente);
  font-size:14px;
}

/* ROLES */
.roles-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:8px;
}

.rol-opcion{display:none;}

.rol-tarjeta{
  border:1px solid var(--borde);
  border-radius:8px;
  padding:12px;
  text-align:center;
  cursor:pointer;
  font-size:14px;
  transition:0.2s;
}

.rol-opcion:checked + .rol-tarjeta{
  border-color:var(--acento-claro);
  background:rgba(119,115,235,0.2);
}

/* TÉRMINOS */
.terminos{
  display:flex;
  gap:10px;
  padding:12px;
  border:1px solid var(--borde);
  border-radius:8px;
  align-items:center;
  font-size:14px;
}

.texto-login{
  text-align:center;
  color:var(--texto-suave);
  font-size:14px;
}

.texto-login a{color:#ff6b6b;}

/* ALERTA SERVIDOR */
.alerta-servidor{
  background:rgba(255,107,107,0.15);
  border:1px solid #ff6b6b;
  color:#ff6b6b;
  padding:12px;
  border-radius:8px;
  font-size:13px;
  text-align:center;
}

/* PANEL ÉXITO */
.panel-exito{
  display:none;
  flex-direction:column;
  align-items:center;
  text-align:center;
  gap:16px;
}

.panel-exito.visible{display:flex;}

.circulo-exito{
  width:72px;
  height:72px;
  border-radius:50%;
  background:linear-gradient(135deg,#16a34a,#22c55e);
  display:flex;
  justify-content:center;
  align-items:center;
}

/* PIE */
.pie{
  background:rgba(30,24,88,0.85);
  border-top:1px solid var(--borde);
}

.pie-interior{
  max-width:1280px;
  margin:auto;
  padding:24px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  font-size:13px;
  color:var(--texto-suave);
}

/* SELECT */
select.entrada option{
  background:#1e1858;
  color:#fff;
}
</style>
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

<?php if ($success): ?>
document.getElementById('pasos').style.display = 'none';
document.getElementById('formRegistro').style.display = 'none';
<?php endif; ?>

// ── Helpers ──────────────────────────────────────────────
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

// ── Validaciones en tiempo real (blur) ───────────────────
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

  // Revalidar confirmación si ya tiene algo escrito
  const conf = document.getElementById('confirmar').value;
  if (conf) {
    setError('confirmar', 'err-confirmar', pass !== conf, 'Las contraseñas no coinciden');
  }
});

document.getElementById('confirmar').addEventListener('input', function () {
  const pass = document.getElementById('password').value;
  setError('confirmar', 'err-confirmar', this.value !== pass, 'Las contraseñas no coinciden');
});

// ── PASO 1: Siguiente ────────────────────────────────────
document.getElementById('btnPaso1').addEventListener('click', () => {

  const nombre = document.getElementById('nombre').value.trim();
  const email  = document.getElementById('email').value.trim();

  const v1 = setError('nombre', 'err-nombre', nombre.length < 2, 'Ingresa tu nombre completo');
  const v2 = setError('email',  'err-email',  !emailRegex.test(email), 'Correo electrónico inválido');

  if (v1 && v2) mostrarPaso(2);
});

// ── PASO 2: Siguiente ────────────────────────────────────
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

// ── Navegación atrás ─────────────────────────────────────
document.getElementById('btnAtras2').addEventListener('click', () => mostrarPaso(1));
document.getElementById('btnAtras3').addEventListener('click', () => mostrarPaso(2));

// ── PASO 3: Submit ───────────────────────────────────────
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