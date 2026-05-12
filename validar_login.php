<?php
session_start(); //Inicia o reanuda la sesion
//configuracion de errores
ini_set('display_errors', 0); // No muestra errores en pantalla (Seguridad)
ini_set('log_errors', 1); // Activa el registro de errores
ini_set('error_log', 'error.log'); // Archivo donde se guardan los errores

$conexion = new mysqli("localhost", "u936997481_dbex", "Expotec123","u936997481_EXPO"); //Conexion

//Verifica si hubo error en la conexion
if($conexion -> connect_error){
    die("Error de conexion"); // Detiene el sistema si falla
    //verifica que le formulario fue enviado por metodo POST
}

if($_SERVER["REQUEST_METHOD"]=="POST"){
    //Obtiene el usuario y trim elimina espacionn en blanco
    $username = trim($_POST['username']);
    //Obtiene la contraseña ingresada
    $password= $_POST['password'];

    //CONSULTA PREPARADA PARA BUSCAR EL USUARIO EN LA BD
    // INCLUYE EL CAMPO "ESTADO" PARA VALIDAR SI ESTA ACTIVO
    $stmt = $conexion->prepare("SELECT id, password, rol_id, estado FROM usuarios WHERE username = ?");

    //Vincula el parametro (s = string)
    $stmt->bind_param("s", $username);
    //Ejecura la consulta
    $stmt->execute();
    //Obtiene el resultado
    $resultado = $stmt->get_result();

    //Verifica si el usuario existe
    if($resultado->num_rows > 0){
        //Obtiene los datos del usuario
        $usuario = $resultado->fetch_assoc();
        //Verifica si el usuario esta desactivado
        if($usuario['estado'] != 1){

            //Cierra conexiones
            $stmt->close();
            $conexion->close();

            //Redirige con mensaje de error
            header("Location: login.php?error=Usuario+desactivado");
            exit();
        }

        //Comparacion de contraseña (actualmente texto plano)
        if($password === $usuario['password']){

            //Regenera el ID de sesion (seguridad)
            session_regenerate_id(true);

            //Guarda datos del usuario en sesion
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['rol_id'] = $usuario['rol_id'];
            $_SESSION['username'] = $username;

            //Cierra conexiones
            $stmt->close();
            $conexion->close();

            //Redireccion segun el rol del usuario
            switch($usuario['rol_id']){
                case 1:
                    //Usuario administrador
                    header("Location: dashboard_admin.php");
                    break;

                case 2:
                    //Usuario Aprovador
                    header("Location: dashboard_aprovador.php");
                    break;

                case 3:
                    //Usuario empresarial
                    header("Location: dashboard_admin_emp.php");
                    break;

                case 4:
                    //Usuario agente
                    header("Location: dashboard_agente.php");
                    break;  

                case 5:
                    //Usuario cliente
                    header("Location: dashboard_cliente.php");
                    break;

                default:
                    //Si el rol no es valido
                    header("Location: login.php?error=Acceso+denegado");
            }

            //Detiene ejecucion despues de redirigir
            exit();

        }
            
    }
        
    //Cierre seguro del statement si existe
    if(isset($stmt)){
        $stmt->close();
    }

    //Cierra conexion a la DB
    $conexion->close();
        
    //Redirige si las credenciales son incorrectas
    header("Location: login.php?error=Credenciales+incorrectas");
    exit();
}

//Cierre adicional de seguridad fuera del POST
if(isset($stmt)){
    $stmt->close();    
}
    
//Cierra conezion (por seguridad)
$conexion->close();
?>