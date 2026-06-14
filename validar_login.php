<?php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Cargar variables del .env
function cargarEnv(string $ruta): void
{
    if (!file_exists($ruta)) {
        return;
    }

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {

        $linea = trim($linea);

        // Ignorar comentarios
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }

        // Verificar que tenga =
        if (!str_contains($linea, '=')) {
            continue;
        }

        [$clave, $valor] = explode('=', $linea, 2);

        $_ENV[trim($clave)] = trim($valor);
    }
}

cargarEnv(__DIR__ . '/.env');

// Enviar código 2FA
function enviarCodigo2FA(string $correoUsuario, string $nombreUsuario, string $codigo): bool
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

        // Remitente
        $mail->setFrom(
            $_ENV['MAIL_USERNAME'],
            $_ENV['MAIL_FROM_NAME']
        );

        // Destinatario
        $mail->addAddress($correoUsuario, $nombreUsuario);

        $mail->isHTML(true);

        $mail->Subject = 'Tu código de verificación - ServiceCore';

        $mail->Body = "
            <div style='font-family:Segoe UI,sans-serif;max-width:480px;margin:auto;
                        background:#0f172a;color:#f1f5f9;border-radius:12px;padding:40px'>

                <h2 style='margin:0 0 8px;font-size:22px'>
                    Verificación en dos pasos
                </h2>

                <p style='color:#94a3b8;margin:0 0 28px'>
                    Hola <strong>$nombreUsuario</strong>,
                    usa este código para ingresar:
                </p>

                <div style='background:#1e293b;border:1px solid #334155;
                            border-radius:10px;padding:24px;text-align:center;
                            margin-bottom:24px'>

                    <span style='font-size:38px;font-weight:700;
                                 letter-spacing:10px;color:#3b82f6'>
                        $codigo
                    </span>
                </div>

                <p style='color:#64748b;font-size:13px;margin:0'>
                    Expira en <strong style='color:#f1f5f9'>10 minutos</strong>.
                    <br>
                    Si no solicitaste esto, ignora este correo.
                </p>
            </div>
        ";

        $mail->AltBody = "Tu código ServiceCore: $codigo";

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log("Error enviando correo 2FA: " . $mail->ErrorInfo);

        return false;
    }
}

// Conexión
$conexion = new mysqli("localhost", "u936997481_ServiCore", "ServiceCore_2026", "u936997481_ServiceCore");

// Verificar conexión
if ($conexion->connect_error) {

    die("Error de conexión: " . $conexion->connect_error);
}

// Solo POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obtener datos
    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Verificar campos vacíos
    if (empty($correo) || empty($password)) {

        $conexion->close();

        header("Location: login.php?error=Completa+todos+los+campos");
        exit();
    }

    // Validar formato correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $conexion->close();

        header("Location: login.php?error=Correo+invalido");
        exit();
    }

    // Buscar usuario
    $stmt = $conexion->prepare("
        SELECT 
            id_usuario,
            nombre,
            correo,
            pass,
            id_rol,
            activo
        FROM usuario
        WHERE correo = ?
    ");

    // Verificar prepare
    if (!$stmt) {

        $conexion->close();

        header("Location: login.php?error=Error+del+servidor");
        exit();
    }

    $stmt->bind_param("s", $correo);

    $stmt->execute();

    $resultado = $stmt->get_result();

    // Usuario no encontrado
    if ($resultado->num_rows === 0) {

        $stmt->close();
        $conexion->close();

        header("Location: login.php?error=Credenciales+incorrectas");
        exit();
    }

    // Datos usuario
    $usuario = $resultado->fetch_assoc();

    $stmt->close();

    // Usuario desactivado
    if ((int)$usuario['activo'] !== 1) {

        $conexion->close();

        header("Location: login.php?error=Usuario+desactivado");
        exit();
    }

    // Verificar contraseña
    if (!password_verify($password, $usuario['pass'])) {

        $conexion->close();

        header("Location: login.php?error=Credenciales+incorrectas");
        exit();
    }

    // Generar código 2FA
    $codigo = str_pad(
        random_int(0, 999999),
        6,
        '0',
        STR_PAD_LEFT
    );

    $expiracion = date(
        'Y-m-d H:i:s',
        strtotime('+10 minutes')
    );

    $id_usuario = (int)$usuario['id_usuario'];

    // Eliminar códigos anteriores
    $del = $conexion->prepare("
        DELETE FROM usuarios_2fa
        WHERE id_usuario = ?
    ");

    if ($del) {

        $del->bind_param("i", $id_usuario);
        $del->execute();
        $del->close();
    }

    // Guardar nuevo código
    $ins = $conexion->prepare("
        INSERT INTO usuarios_2fa
        (
            id_usuario,
            tipo,
            secreto,
            codigo_temporal,
            expiracion_codigo,
            activo
        )
        VALUES
        (
            ?,
            'email',
            '',
            ?,
            ?,
            1
        )
    ");

    if (!$ins) {

        $conexion->close();

        header("Location: login.php?error=Error+guardando+codigo");
        exit();
    }

    $ins->bind_param(
        "iss",
        $id_usuario,
        $codigo,
        $expiracion
    );

    $ins->execute();

    $ins->close();

    // Enviar correo
    $enviado = enviarCodigo2FA(
        $usuario['correo'],
        $usuario['nombre'],
        $codigo
    );

    // Error enviando correo
    if (!$enviado) {

        $del2 = $conexion->prepare("
            DELETE FROM usuarios_2fa
            WHERE id_usuario = ?
        ");

        if ($del2) {

            $del2->bind_param("i", $id_usuario);
            $del2->execute();
            $del2->close();
        }

        $conexion->close();

        header("Location: login.php?error=Error+al+enviar+codigo");
        exit();
    }

    // Seguridad sesión
    session_regenerate_id(true);

    // Variables temporales
    $_SESSION['2fa_id_usuario'] = $id_usuario;
    $_SESSION['2fa_id_rol']     = $usuario['id_rol'];
    $_SESSION['2fa_correo']     = $usuario['correo'];

    $conexion->close();

    // Ir a verificar código
    header("Location: verificar_2fa.php");
    exit();
}

// Cerrar conexión
$conexion->close();
?>  