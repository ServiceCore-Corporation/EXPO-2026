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
    <title>Gestion de Empresas | ServiceCore</title>
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
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Usuarios</h2>
            <p class="text-gray-500 mt-2">Administra los usuarios registrados en el sistema.</p>
        </div>

        <a href="crear_empresa.php"
            class="bg-[#5750ad] hover:bg-[#463d99] text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition duration-300 hover:scale-105 flex items-center gap-2 w-fit">
            <span class="material-symbols-outlined">person_add</span>
            Crear Usuario
        </a>
    </div>
</section>

<!-- TABLA DE USUARIOS -->
<section class="tarjeta animar overflow-hidden">

    <div class="flex justify-between items-center border-b p-5 bg-gray-50">
        <h3 class="text-xl font-bold text-[#1e1858]">
            Lista de Usuarios
        </h3>

        <div class="relative">
            <input
                type="text"
                id="buscarUsuario"
                placeholder="Buscar usuario..."
                class="border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#5750ad]">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#5750ad] text-white">
                <tr>
                    <th class="p-4 text-left">No.</th>
                    <th class="p-4 text-left">Nombre</th>
                    <th class="p-4 text-left">Correo</th>
                    <th class="p-4 text-left">Teléfono</th>
                    <th class="p-4 text-center">Estado</th>
                    <th class="p-4 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody id="tablaUsuarios" class="divide-y divide-gray-200">

                <tr class="hover:bg-gray-50 transition duration-300">
                    <td class="p-4 font-semibold">1</td>
                    <td class="p-4">Juan Pérez</td>
                    <td class="p-4">juan@email.com</td>
                    <td class="p-4">5555-5555</td>

                    <td class="p-4 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                            Activo
                        </span>
                    </td>

                    <td class="p-4">
                        <div class="flex justify-center gap-2">

                            <button
                                class="btnActualizar bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center gap-1 transition hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                Actualizar
                            </button>

                            <button
                                class="btnDesactivar bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg flex items-center gap-1 transition hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]">block</span>
                                Desactivar
                            </button>

                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

</section>

        <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
        </footer>
    </main>

    <script>
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

        // Buscador en tiempo real
        document.getElementById("buscarUsuario").addEventListener("keyup", function () {

            let filtro = this.value.toLowerCase();

            document.querySelectorAll("#tablaUsuarios tr").forEach(fila => {

                let texto = fila.textContent.toLowerCase();

                fila.style.display =
                    texto.includes(filtro)
                    ? ""
                    : "none";
            });

        });

        // Efecto botones actualizar
        document.addEventListener("click", function(e){

        const boton = e.target.closest(".btnActualizar");

            if(boton){

                const fila = boton.closest("tr");

                const usuario = {

                    nombre: fila.children[1].textContent.trim(),
                    correo: fila.children[2].textContent.trim(),
                    telefono: fila.children[3].textContent.trim()

                };

                localStorage.setItem(
                    "usuarioEditar",
                    JSON.stringify(usuario)
                );

                window.location.href = "editar_empresa.php";
            }

        });

        // Confirmación desactivar
        document.addEventListener("click", function(e){

            if(e.target.closest(".btnDesactivar")){

                if(confirm("¿Desea desactivar este usuario?")){

                    const fila = e.target.closest("tr");

                    fila.querySelector("td:nth-child(5)").innerHTML =
                    `<span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                        Inactivo
                    </span>`;

                }

            }

        });

        // Botón crear usuario
        document.getElementById("btnCrearUsuario").addEventListener("click", () => {
            alert("Abrir modal de creación de usuario");
        });

    </script>
</body>
</html>