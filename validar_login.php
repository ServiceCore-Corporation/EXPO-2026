<?php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($correo) || empty($password)) {
        $conn->close();
        header("Location: login.php?error=Completa+todos+los+campos");
        exit();
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $conn->close();
        header("Location: login.php?error=Correo+invalido");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id_usuario, nombre, correo, pass, id_rol, activo
        FROM usuario
        WHERE correo = ?
    ");

    if (!$stmt) {
        $conn->close();
        header("Location: login.php?error=Error+del+servidor");
        exit();
    }

    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        $stmt->close();
        $conn->close();
        header("Location: login.php?error=Credenciales+incorrectas");
        exit();
    }

    $usuario = $resultado->fetch_assoc();
    $stmt->close();

    if ((int)$usuario['activo'] !== 1) {
        $conn->close();
        header("Location: login.php?error=Usuario+desactivado");
        exit();
    }

    if (!password_verify($password, $usuario['pass'])) {
        $conn->close();
        header("Location: login.php?error=Credenciales+incorrectas");
        exit();
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id']  = (int)$usuario['id_usuario'];
    $_SESSION['nombre']      = $usuario['nombre'];
    $_SESSION['correo']      = $usuario['correo'];
    $_SESSION['id_rol']      = (int)$usuario['id_rol'];
    $_SESSION['autenticado'] = true;

    $conn->close();

    switch ((int)$usuario['id_rol']) {
        case 1:
            header("Location: vistas/Admin/dashboard_admin.php");
            break;
        case 2:
            header("Location: vistas/Admin_Empresa/dashboard_admin_emp.php");
            break;
        case 3:
            header("Location: vistas/Agente/dashboard_agente.php");
            break;
        case 4:
            header("Location: vistas/Supervisor/dashboard_aprovador.php");
            break;
        case 5:
            header("Location: vistas/Cliente/dashboard_cliente.php");
            break;
        default:
            header("Location: login.php?error=Rol+invalido");
            break;
    }
    exit();
}

$conn->close();
