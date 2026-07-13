<?php
// vistas/perfil.php ya no renderiza el perfil directamente: cada rol tiene
// su propio archivo (Admin/perfil.php, Admin_Empresa/perfil.php,
// Agente/perfil.php, Supervisor/perfil.php, Cliente/perfil.php).
// Este archivo solo redirige a la version correcta segun el rol de la
// sesion activa, para que los enlaces antiguos ("perfil.php") sigan
// funcionando.

define('ROLES_PERMITIDOS', [1, 2, 3, 4, 5]);
require_once __DIR__ . '/../seguridad.php';

switch ((int)($_SESSION['id_rol'] ?? 0)) {
    case 1:
        header('Location: Admin/perfil.php');
        break;
    case 2:
        header('Location: Admin_Empresa/perfil.php');
        break;
    case 3:
        header('Location: Agente/perfil.php');
        break;
    case 4:
        header('Location: Supervisor/perfil.php');
        break;
    case 5:
        header('Location: Cliente/perfil.php');
        break;
    default:
        header('Location: ../login.php?error=Rol+invalido');
        break;
}
exit();
