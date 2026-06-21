<?php
define('ROL_REQUERIDO', 1);
require_once 'seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Empresa | ServiceCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f5f7ff; }
        .tarjeta { background: white; border-radius: 16px; padding: 24px; border: 1px solid #dfe7fa; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: .3s; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #c7c9ff; transition: .3s; }
        .menu-item:hover { background: rgba(255,255,255,.08); color: white; transform: translateX(5px); }
        .menu-item.activo { background: #5750ad; color: white; }
    </style>
</head>
<body>

    <!-- ENCABEZADO -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Panel Administrador</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Administrador</p>
            </div>
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold">
                <?= mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1)) ?>
            </div>
            <div id="menuUsuario" class="hidden absolute right-0 top-14 w-52 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50">
                <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">settings</span>Configuración
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">person</span>Perfil
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 text-red-600 transition">
                    <span class="material-symbols-outlined">logout</span>Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- MENÚ LATERAL -->
    <aside class="fixed left-0 top-0 w-64 h-full bg-[#1e1858] text-white p-6">
        <div class="flex flex-col items-center mb-8">
            <img src="img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="space-y-2">
            <a href="dashboard_admin.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_empresas.php" class="menu-item activo">
                <span class="material-symbols-outlined">business</span> Gestion de Empresas
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">workspace_premium</span>Planes
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">payments</span>Pagos
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="ml-64 pt-24 px-8 pb-10">
        <!-- CONTENIDO PRINCIPAL -->
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Editar Usuario</h2>
            <p class="text-gray-500 mt-2">
                Edite la información para actualizar el usuario.
            </p>
        </section>

        <section class="max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animar">

                <!-- Encabezado -->
                <div class="bg-gradient-to-r from-[#5750ad] to-[#6f67d8] p-6">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined">person_add</span>
                        Nuevo Usuario
                    </h3>
                    <p class="text-indigo-100 mt-1">
                        Ingrese los datos del usuario a registrar.
                    </p>
                </div>

                <!-- Formulario -->
                <form class="p-8 space-y-6" id="formUsuario">

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nombre Completo
                        </label>

                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-3 text-gray-400">
                                person
                            </span>

                            <input
                                type="text"
                                id="nombre"
                                class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#5750ad] focus:border-[#5750ad] outline-none transition"
                                placeholder="Ingrese el nombre completo"
                            >
                        </div>
                    </div>

                    <!-- Correo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Correo Electrónico
                        </label>

                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-3 text-gray-400">
                                mail
                            </span>

                            <input
                                type="email"
                                id="correo"
                                class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#5750ad] focus:border-[#5750ad] outline-none transition"
                                placeholder="usuario@correo.com"
                            >
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Teléfono
                        </label>

                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-3 text-gray-400">
                                call
                            </span>

                            <input
                                type="tel"
                                id="telefono"
                                class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#5750ad] focus:border-[#5750ad] outline-none transition"
                                placeholder="0000-0000"
                            >
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-4 pt-4 border-t">

                        <button
                            type="submit"
                            class="bg-[#5750ad] text-white px-6 py-3 rounded-xl">
                            Actualizar Usuario
                        </button>

                    </div>

                </form>

            </div>
        </section>



        <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
        </footer>
    </main>

    <script>
        window.addEventListener("DOMContentLoaded", () => {

            const usuario = JSON.parse(
                localStorage.getItem("usuarioEditar")
            );

            console.log(usuario);

            if(usuario){

                document.getElementById("nombre").value =
                    usuario.nombre;

                document.getElementById("correo").value =
                    usuario.correo;

                document.getElementById("telefono").value =
                    usuario.telefono;

            }

        });
        
        // Menú usuario
        const botonUsuario = document.getElementById("botonUsuario");
        const menuUsuario  = document.getElementById("menuUsuario");
        botonUsuario.addEventListener("click", () => menuUsuario.classList.toggle("hidden"));
        document.addEventListener("click", (e) => {
            if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
                menuUsuario.classList.add("hidden");
        });

        // Colores de estado
        function colorEstado(estado) {
            const mapa = {
                'Pendiente':   'bg-yellow-100 text-yellow-700',
                'En proceso':  'bg-blue-100 text-blue-700',
                'Cerrado':     'bg-green-100 text-green-700',
                'Cancelado':   'bg-red-100 text-red-700',
            };
            return mapa[estado] || 'bg-gray-100 text-gray-700';
        }

        function colorPrioridad(prioridad) {
            const mapa = { 'Alta': 'text-red-600', 'Media': 'text-orange-500', 'Baja': 'text-blue-500' };
            return mapa[prioridad] || 'text-gray-600';
        }

        // Animación de entrada
        const tarjetas = document.querySelectorAll(".animar");

        tarjetas.forEach(t => {
            t.style.opacity = "0";
            t.style.transform = "translateY(25px)";
            t.style.transition = "all .5s ease";
        });

        window.addEventListener("load", () => {
            tarjetas.forEach((t, i) => {
                setTimeout(() => {
                    t.style.opacity = "1";
                    t.style.transform = "translateY(0)";
                }, i * 150);
            });
        });


    </script>
</body>
</html>