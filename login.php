<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    switch ((int)$_SESSION['id_rol']) {
        case 1: header("Location: vistas/Admin/dashboard_admin.php"); break;
        case 2: header("Location: vistas/Admin_Empresa/dashboard_admin_emp.php"); break;
        case 3: header("Location: vistas/Agente/dashboard_agente.php"); break;
        case 4: header("Location: vistas/Supervisor/dashboard_supervisor.php"); break;
        case 5: header("Location: vistas/Cliente/dashboard_cliente.php"); break;
        default:
            session_destroy();
            header("Location: login.php?error=Rol+invalido");
            break;
    }
    exit();
}

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$registroExitoso = isset($_GET['registro']) && $_GET['registro'] === 'exitoso';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Iniciar Sesión | ServiceCore Corp.</title>
<link rel="icon" type="image/png" href="img/LogoNav.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/login.css">
</head>
<body>

<header class="encabezado">
  <div class="marca">
    <img src="img/logoSC.png" alt="Logo" class="logo"/>
    <span class="marca-nombre">Service<span>Core</span></span>
  </div>
  <nav>
    <ul class="nav-enlaces">
      <li><a href="index.php">Inicio</a></li>
      <li><a href="index.php#features">Funciones</a></li>
    </ul>
  </nav>
</header>

<main>
  <div class="contenedor-central">

    <!-- Info lateral -->
    <div class="lado-info">
      <span class="chip">
        <span class="material-symbols-outlined" style="font-size:14px">verified_user</span>
        Acceso Seguro
      </span>
      <h1>Bienvenido a <span>ServiceCore</span></h1>
      <p>Plataforma empresarial de gestión de tickets y soporte técnico.</p>
      <div class="beneficios">
        <div class="beneficio"><span class="material-symbols-outlined">task_alt</span>Gestión de tickets en tiempo real</div>
        <div class="beneficio"><span class="material-symbols-outlined">group</span>Roles de acceso diferenciados</div>
        <div class="beneficio"><span class="material-symbols-outlined">lock</span>Verificación en dos pasos (2FA)</div>
      </div>
    </div>

    <!-- Tarjeta de login -->
    <div class="tarjeta-envuelta" id="tarjetaEnvuelta">
      <div class="tarjeta" id="tarjeta">
        <div class="tarjeta-cabecera">
          <div class="icono-escudo">
            <span class="material-symbols-outlined">shield_person</span>
          </div>
          <h2 class="titulo-medio">Iniciar Sesión</h2>
          <p style="font-size:13px;color:var(--texto-tenue)">Ingresa tus credenciales corporativas</p>
        </div>

        <?php if ($registroExitoso): ?>
        <div class="alerta-error" style="background:rgba(34,197,94,0.12);border-color:rgba(34,197,94,0.4);color:#22c55e;">
          <span class="material-symbols-outlined">check_circle</span>
          Cuenta creada correctamente. Ya puedes iniciar sesión.
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alerta-error">
          <span class="material-symbols-outlined">error</span>
          <?= $error ?>
        </div>
        <?php endif; ?>

        <form id="formularioLogin" class="formulario" action="validar_login.php" method="POST" novalidate>

          <div class="campo">
            <label class="etiqueta-campo" for="correo">Correo electrónico</label>
            <div class="caja-entrada">
              <span class="material-symbols-outlined icono-entrada">mail</span>
              <input class="entrada" id="correo" name="correo"
                     type="email" placeholder="usuario@empresa.com" required/>
            </div>
          </div>

          <div class="campo">
            <div class="fila-campo">
              <label class="etiqueta-campo" for="password">Contraseña</label>
              <a class="enlace-olvide" href="#">Olvidé mi contraseña</a>
            </div>
            <div class="caja-entrada">
              <span class="material-symbols-outlined icono-entrada">lock</span>
              <input class="entrada entrada-con-boton" id="password" name="password"
                     type="password" placeholder="••••••••" required/>
              <button class="boton-mostrar" type="button" id="verContrasena" aria-label="Mostrar contraseña">
                <span class="material-symbols-outlined" style="font-size:20px">visibility</span>
              </button>
            </div>
          </div>

          <button class="boton-ingresar" type="submit" id="botonEntrar">
            <span class="material-symbols-outlined" style="font-size:20px">login</span>
            Iniciar Sesión
          </button>
        </form>

        <div class="separador"><div class="separador-linea"></div></div>
        <p class="texto-registro">¿No tienes cuenta? <a href="registrarse.php">Registrarse</a></p>
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
    <nav class="pie-nav">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
      <a href="#">Contact Support</a>
    </nav>
  </div>
</footer>
<script src="js/block.js"></script> 

<script>
  const cuerpo = document.body;
  const encabezado = document.querySelector('.encabezado');
  const piePagina  = document.querySelector('.pie');
  const envuelta   = document.getElementById('tarjetaEnvuelta');
  let luzX=-999,luzY=-999,destX=-999,destY=-999,brillo=0,brilloDest=0;
  function estaEnZonaLibre(cx,cy){
    function cubre(el){const r=el.getBoundingClientRect();return cx>=r.left&&cx<=r.right&&cy>=r.top&&cy<=r.bottom;}
    return !cubre(encabezado)&&!cubre(piePagina)&&!cubre(envuelta);
  }
  document.addEventListener('mousemove',(e)=>{destX=e.clientX;destY=e.clientY;brilloDest=estaEnZonaLibre(e.clientX,e.clientY)?1:0;});
  document.addEventListener('mouseleave',()=>{brilloDest=0;});
  (function animar(){
    luzX+=(destX-luzX)*0.1; luzY+=(destY-luzY)*0.1;
    const v=brilloDest>brillo?0.08:0.05; brillo+=(brilloDest-brillo)*v;
    if(brillo>0.01){
      cuerpo.style.setProperty('--luz-fondo',`radial-gradient(500px circle at ${luzX}px ${luzY}px, rgba(141,48,212,${0.25*brillo}) 0%, rgba(97,47,131,${0.15*brillo}) 35%, transparent 70%)`);
    } else { cuerpo.style.setProperty('--luz-fondo','none'); }
    requestAnimationFrame(animar);
  })();

  const formulario  = document.getElementById('formularioLogin');
  const botonEntrar = document.getElementById('botonEntrar');

  formulario.addEventListener('submit', (e) => {
    botonEntrar.innerHTML = '<span class="girando"><span class="material-symbols-outlined" style="font-size:20px">sync</span></span> Autenticando...';
    botonEntrar.style.opacity = '0.8';
    botonEntrar.disabled = true;
  });

  const verContrasena = document.getElementById('verContrasena');
  const campoPassword = document.getElementById('password');
  verContrasena.addEventListener('click', () => {
    const esPassword = campoPassword.type === 'password';
    campoPassword.type = esPassword ? 'text' : 'password';
    verContrasena.querySelector('.material-symbols-outlined').textContent =
      esPassword ? 'visibility_off' : 'visibility';
  });
</script>
</body>
</html>
