<?php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Verificar flujo 2FA
if (!isset($_SESSION['2fa_id_usuario'])) {
    header("Location: login.php?error=Sesion+invalida");
    exit();
}

// Conexión
$conexion = new mysqli("localhost", "u936997481_ServiCore", "ServiceCore_2026", "u936997481_ServiceCore");

if ($conexion->connect_error) {
    die("Error de conexion");
}

$errorMsg = '';

// Procesar verificación
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $codigo_ingresado = trim($_POST['codigo'] ?? '');
    $id_usuario = (int) $_SESSION['2fa_id_usuario'];

    // Validar código
    if (empty($codigo_ingresado)) {

        $errorMsg = "Ingresa el código de verificación.";

    } elseif (!ctype_digit($codigo_ingresado) || strlen($codigo_ingresado) != 6) {

        $errorMsg = "El código debe tener 6 dígitos.";

    } else {

        // Buscar código activo
        $stmt = $conexion->prepare("
            SELECT id_2fa, codigo_temporal, expiracion_codigo
            FROM usuarios_2fa
            WHERE id_usuario = ?
            AND activo = 1
            ORDER BY id_2fa DESC
            LIMIT 1
        ");

        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows <= 0) {

            $errorMsg = "No se encontró un código activo.";

        } else {

            $codigoDB = $resultado->fetch_assoc();

            $fechaActual = new DateTime();
            $fechaExpira = new DateTime($codigoDB['expiracion_codigo']);

            // Verificar expiración
            if ($fechaActual > $fechaExpira) {

                $del = $conexion->prepare("
                    DELETE FROM usuarios_2fa
                    WHERE id_2fa = ?
                ");

                $del->bind_param("i", $codigoDB['id_2fa']);
                $del->execute();
                $del->close();

                $errorMsg = "El código expiró. Inicia sesión nuevamente.";

            } 
            // Verificar código incorrecto
            elseif ($codigo_ingresado !== $codigoDB['codigo_temporal']) {

                $errorMsg = "Código incorrecto.";

            } else {

                // Eliminar código usado
                $del = $conexion->prepare("
                    DELETE FROM usuarios_2fa
                    WHERE id_2fa = ?
                ");

                $del->bind_param("i", $codigoDB['id_2fa']);
                $del->execute();
                $del->close();

                // Obtener datos del usuario
                $stmtUsuario = $conexion->prepare("
                    SELECT nombre, correo, id_rol
                    FROM usuario
                    WHERE id_usuario = ?
                    LIMIT 1
                ");

                $stmtUsuario->bind_param("i", $id_usuario);
                $stmtUsuario->execute();

                $resUsuario = $stmtUsuario->get_result();

                if ($resUsuario->num_rows <= 0) {

                    session_destroy();

                    header("Location: login.php?error=Usuario+no+encontrado");
                    exit();
                }

                $usuario = $resUsuario->fetch_assoc();

                $stmtUsuario->close();

                // Regenerar sesión
                session_regenerate_id(true);

                // Variables de sesión
                $_SESSION['usuario_id'] = $id_usuario;
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['correo'] = $usuario['correo'];
                $_SESSION['id_rol'] = $usuario['id_rol'];
                $_SESSION['autenticado'] = true;
                $_SESSION['token'] = bin2hex(random_bytes(32));

                // Limpiar temporales 2FA
                unset($_SESSION['2fa_id_usuario']);
                unset($_SESSION['2fa_id_rol']);
                unset($_SESSION['2fa_correo']);

                // Redirección por rol
                switch ((int)$usuario['id_rol']) {

                    case 1:
                        header("Location: dashboard_admin.php");
                        break;

                    case 2:
                        header("Location: dashboard_admin_emp.php");
                        break;

                    case 3:
                        header("Location: dashboard_agente.php");
                        break;

                    case 4:
                        header("Location: dashboard_aprovador.php");
                        break;

                    case 5:
                        header("Location: dashboard_cliente.php");
                        break;

                    default:
                        session_destroy();
                        header("Location: login.php?error=Rol+invalido");
                        break;
                }

                $conexion->close();
                exit();
            }
        }

        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
    }
}

// Ocultar parte del correo
$correo = $_SESSION['2fa_correo'] ?? '';

$correoMask = '';

if (!empty($correo) && strpos($correo, '@') !== false) {

    $partes = explode('@', $correo);

    $usuarioCorreo = $partes[0];
    $dominioCorreo = $partes[1];

    $visible = substr($usuarioCorreo, 0, 1);

    $ocultos = str_repeat('*', max(strlen($usuarioCorreo) - 1, 3));

    $correoMask = $visible . $ocultos . '@' . $dominioCorreo;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<title>Verificar Identidad | ServiceCore</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet"/>

<style>

:root{
    --fondo:#1e1858;
    --fondo2:#2a2470;
    --acento:#7773eb;
    --texto:#ffffff;
    --texto2:#b8b5e1;
    --error:#ff6b6b;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Poppins',sans-serif;
    background:linear-gradient(135deg,var(--fondo),var(--fondo2));
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--texto);
    padding:20px;
}

.tarjeta{
    width:100%;
    max-width:420px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:18px;
    padding:35px;
    backdrop-filter:blur(10px);
}

.logo{
    width:70px;
    display:block;
    margin:auto;
    margin-bottom:15px;
}

h1{
    text-align:center;
    font-size:24px;
    margin-bottom:10px;
}

.texto{
    text-align:center;
    color:var(--texto2);
    font-size:14px;
    margin-bottom:20px;
}

.correo{
    text-align:center;
    margin-bottom:25px;
    color:var(--acento);
    font-weight:600;
}

.alerta{
    background:rgba(255,107,107,0.15);
    border:1px solid rgba(255,107,107,0.4);
    color:var(--error);
    padding:12px;
    border-radius:10px;
    margin-bottom:20px;
    text-align:center;
    font-size:14px;
}

.otp-grupo{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-bottom:25px;
}

.otp{
    width:50px;
    height:58px;
    border:none;
    border-radius:12px;
    background:rgba(255,255,255,0.08);
    color:white;
    font-size:24px;
    text-align:center;
    outline:none;
    border:2px solid transparent;
}

.otp:focus{
    border-color:var(--acento);
}

button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:linear-gradient(135deg,#5750ad,#7773eb);
    color:white;
    font-size:15px;
    font-weight:700;
    cursor:pointer;
    transition:0.2s;
}

button:hover{
    opacity:0.9;
}

.volver{
    display:block;
    text-align:center;
    margin-top:18px;
    color:var(--texto2);
    text-decoration:none;
    font-size:13px;
}

.volver:hover{
    color:white;
}

</style>
</head>

<body>

<div class="tarjeta">

    <img src="img/logoSC.png" class="logo">

    <h1>Verifica tu identidad</h1>

    <p class="texto">
        Ingresa el código enviado a:
    </p>

    <div class="correo">
        <?= htmlspecialchars($correoMask) ?>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="alerta">
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="formOTP">

        <div class="otp-grupo">
            <input type="text" maxlength="1" class="otp">
            <input type="text" maxlength="1" class="otp">
            <input type="text" maxlength="1" class="otp">
            <input type="text" maxlength="1" class="otp">
            <input type="text" maxlength="1" class="otp">
            <input type="text" maxlength="1" class="otp">
        </div>

        <input type="hidden" name="codigo" id="codigo">

        <button type="submit">
            Verificar código
        </button>

    </form>

    <a href="login.php" class="volver">
        Volver al login
    </a>

</div>

<script>

const inputs = document.querySelectorAll('.otp');
const hidden = document.getElementById('codigo');

inputs.forEach((input, index) => {

    input.addEventListener('input', () => {

        input.value = input.value.replace(/\D/g, '');

        if (input.value && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });

    input.addEventListener('keydown', (e) => {

        if (e.key === 'Backspace' && !input.value && index > 0) {
            inputs[index - 1].focus();
        }
    });
});

document.getElementById('formOTP').addEventListener('submit', function(e){

    const codigo = [...inputs].map(input => input.value).join('');

    if (codigo.length !== 6) {

        e.preventDefault();

        alert('Completa el código de 6 dígitos.');
        return;
    }

    hidden.value = codigo;
});

</script>

</body>
</html>