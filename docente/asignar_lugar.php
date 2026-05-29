<?php
/**
 * Asignación de Estudiantes a Lugares y Asignaturas (Muchos a Muchos)
 * Sigue principios de arquitectura modular y seguridad por capas.
 */
require_once '../config/database.php';
require_once '../includes/auth_validate.php';

checkAuth(1); // Capa de seguridad: Solo Docentes

$db = new Database();
$pdo = $db->connect();
$mensaje = '';

// Procesar el formulario de asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['usuario_id'] ?? 0);
    $lugar_id = (int)($_POST['lugar_id'] ?? 0);
    $asignatura_id = (int)($_POST['asignatura_id'] ?? 0);

    if ($student_id > 0 && $lugar_id > 0 && $asignatura_id > 0) {
        try {
            // Verificar si la asignación ya existe para evitar duplicados redundantes (DRY)
            $check = $pdo->prepare("SELECT id FROM asignaturas_estudiante WHERE lugar_id = ? AND asignatura_id = ? AND usuario_id = ?");
            $check->execute([$lugar_id, $asignatura_id, $student_id]);
            
            if ($check->fetch()) {
                $mensaje = "<div class='bg-amber-50 border border-amber-200 text-amber-600 px-4 py-2 rounded-lg text-sm mb-4'>Este estudiante ya está asignado a esa materia en ese lugar.</div>";
            } else {
                // Inserción limpia con marcas de tiempo explícitas
                $stmt = $pdo->prepare("INSERT INTO asignaturas_estudiante (lugar_id, asignatura_id, usuario_id, usuario_id_creacion, fecha_creacion, hora_creacion) VALUES (?, ?, ?, ?, NOW(), CURTIME())");
                $stmt->execute([$lugar_id, $asignatura_id, $student_id, $_SESSION['usuario_id']]);
                $mensaje = "<div class='bg-green-50 border border-green-200 text-green-600 px-4 py-2 rounded-lg text-sm mb-4'>Asignación académica registrada correctamente.</div>";
            }
        } catch (PDOException $e) {
            error_log("Error en asignación: " . $e->getMessage());
            $mensaje = "<div class='bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm mb-4'>Ocurrió un error en el servidor.</div>";
        }
    } else {
        $mensaje = "<div class='bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm mb-4'>Por favor, seleccione todos los campos obligatorios.</div>";
    }
}

// Cargar catálogos relacionales usando Queries directas
$estudiantes = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 2 ORDER BY nombre ASC")->fetchAll();
$lugares = $pdo->query("SELECT id, nombre FROM lugares ORDER BY nombre ASC")->fetchAll();
$asignaturas = $pdo->query("SELECT id, nombre FROM asignaturas ORDER BY nombre ASC")->fetchAll();

// Obtener las asignaciones actuales con INNER JOIN para mostrar datos limpios en la vista
$queryAsignaciones = "
    SELECT ae.id, u.nombre AS estudiante, l.nombre AS lugar, a.nombre AS asignatura 
    FROM asignaturas_estudiante ae
    INNER JOIN usuarios u ON ae.usuario_id = u.id
    INNER JOIN lugares l ON ae.lugar_id = l.id
    INNER JOIN asignaturas a ON ae.asignatura_id = a.id
    ORDER BY ae.id DESC
";
$asignaciones = $pdo->query($queryAsignaciones)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación Académica</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50 min-h-screen p-6">

    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Carga Académica y Distribución</h2>
                <p class="text-slate-500 text-xs">Vincule estudiantes con instituciones y sus respectivas cátedras.</p>
            </div>
            <a href="index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm py-2 px-4 rounded-lg transition border border-slate-300">
                Volver al Panel
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs h-fit">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Nueva Asignación</h3>
                
                <?php echo $mensaje; ?>

                <form action="asignar_lugar.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Seleccionar Estudiante</label>
                        <select name="usuario_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Seleccione un alumno --</option>
                            <?php foreach ($estudiantes as $est): ?>
                                <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Lugar / Sede Educativa</label>
                        <select name="lugar_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Seleccione la institución --</option>
                            <?php foreach ($lugares as $lug): ?>
                                <option value="<?php echo $lug['id']; ?>"><?php echo htmlspecialchars($lug['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Asignatura</label>
                        <select name="asignatura_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Seleccione la materia --</option>
                            <?php foreach ($asignaturas as $asig): ?>
                                <option value="<?php echo $asig['id']; ?>"><?php echo htmlspecialchars($asig['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-blue-6