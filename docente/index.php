<?php
/**
 * Vista de Administración - Rol Docente
 * Gestión de alumnos y asignaciones académicas.
 */
require_once '../config/database.php';
require_once '../includes/auth_validate.php';

// Forzar protección de capa: Solo rol 1 (Docente) puede pisar este archivo
checkAuth(1);

$db = new Database();
$pdo = $db->connect();
$mensaje = '';

// Procesar el registro del Estudiante (Requerimiento 1 del docente)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_estudiante'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    // Password por defecto para los alumnos creados
    $password_default = password_hash('estudiante123', PASSWORD_BCRYPT); 

    if (!empty($nombre) && $email) {
        try {
            // Sentencia preparada con inserción de auditoría explícita
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, rol, contrasena, usuario_id_creacion, fecha_creacion, hora_creacion) VALUES (?, ?, 2, ?, ?, NOW(), CURTIME())");
            $stmt->execute([$nombre, $email, $password_default, $_SESSION['usuario_id']]);
            $mensaje = "<div class='bg-green-50 border border-green-200 text-green-600 px-4 py-2 rounded-lg text-sm mb-4'>Estudiante registrado exitosamente (Clave provisional: estudiante123).</div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm mb-4'>El correo ya se encuentra registrado.</div>";
        }
    } else {
        $mensaje = "<div class='bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm mb-4'>Por favor, llene todos los campos correctamente.</div>";
    }
}

// Obtener lista completa de estudiantes (Rol 2)
$stmtEstudiantes = $pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol = 2 ORDER BY id DESC");
$estudiantes = $stmtEstudiantes->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Docente</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    <nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shadow-xs">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Panel Académico (Docente)</h1>
            <p class="text-slate-500 text-xs">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
        </div>
        <a href="../logout.php" class="bg-red-50 hover:bg-red-100 text-red-600 font-medium text-sm py-2 px-4 rounded-lg transition border border-red-200">
            Cerrar Sesión
        </a>
    </nav>

    <div class="max-w-6xl mx-auto p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white p-6 rounded-xl shadow-xs border border-slate-200 h-fit">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Registrar Nuevo Estudiante</h3>
            
            <?php echo $mensaje; ?>

            <form action="index.php" method="POST" class="space-y-4">
                <input type="hidden" name="registrar_estudiante" value="1">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Apellidos y Nombres Completos</label>
                    <input type="text" name="nombre" required placeholder="Ej. Cevallos Melanie"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Correo Electrónico</label>
                    <input type="email" name="email" required placeholder="Ej. estudiante@uleam.edu.ec"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                    Guardar Registro
                </button>
            </form>
        </div>

        <div class="md:col-span-2 space-y-6">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="asignar_lugar.php" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:border-blue-500 transition block">
                    <span class="block font-bold text-slate-800">Asignar a Materia/Lugar</span>
                    <span class="text-slate-500 text-xs">Vincular alumnos a las sedes y asignaturas.</span>
                </a>
                <a href="gestionar_notas.php" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:border-green-500 transition block">
                    <span class="block font-bold text-slate-800">Gestionar Calificaciones</span>
                    <span class="text-slate-500 text-xs">Ingresar notas de teoría, práctica y parciales.</span>
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-800">Nómina Global de Estudiantes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Estudiante</th>
                                <th class="px-6 py-3">Email</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                            <?php if (empty($estudiantes)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-slate-400">No hay estudiantes registrados en el sistema.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($estudiantes as $est): ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 font-medium text-slate-800">#<?php echo $est['id']; ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($est['nombre']); ?></td>
                                        <td class="px-6 py-4 text-slate-500"><?php echo htmlspecialchars($est['email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</body>
</html>