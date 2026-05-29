<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Si ya está logueado, redirige automáticamente según su rol
if (isset($_SESSION['usuario_rol'])) {
    if ((int)$_SESSION['usuario_rol'] === 1) header("Location: ../docente/index.php");
    if ((int)$_SESSION['usuario_rol'] === 2) header("Location: ../estudiante/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Calificaciones - Login</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md border border-slate-100">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Sistema de Gestión Académica</h2>
            <p class="text-slate-500 text-sm mt-1">Ingrese sus credenciales de acceso</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm mb-4">
                <?php 
                    if ($_GET['error'] === 'invalid') echo "Credenciales incorrectas.";
                    if ($_GET['error'] === 'unauthorized') echo "No tiene permisos para acceder a esta sección.";
                ?>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Correo Institucional</label>
                <input type="email" name="email" required autocomplete="email"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                <input type="password" name="contrasena" required autocomplete="current-password"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                Iniciar Sesión
            </button>
        </form>
    </div>

</body>
</html>