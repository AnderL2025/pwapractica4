<?php
/**
 * Gestión y Registro de Calificaciones - Rol Docente
 * Sigue principios estrictos de código limpio y validación por capas.
 */
require_once '../config/database.php';
require_once '../includes/auth_validate.php';

checkAuth(1); // Capa de seguridad: Solo Docentes

$db = new Database();
$pdo = $db->connect();
$mensaje = '';

// Procesar el registro o actualización de notas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asignatura_id = (int)($_POST['asignatura_id'] ?? 0);
    $usuario_id = (int)($_POST['usuario_id'] ?? 0); // Estudiante
    $parcial = (int)($_POST['parcial'] ?? 0);
    $teoria = filter_input(INPUT_POST, 'teoria', FILTER_VALIDATE_FLOAT);
    $practica = filter_input(INPUT_POST, 'practica', FILTER_VALIDATE_FLOAT);
    $obs = trim($_POST['obs'] ?? '');

    if ($asignatura_id > 0 && $usuario_id > 0 && $parcial > 0 && $teoria !== false && $practica !== false) {
        try {
            // Verificar si ya existe nota registrada para ese parcial y materia (Evita redundancia)
            $check = $pdo->prepare("SELECT id FROM notas WHERE asignatura_id = ? AND usuario_id = ? AND parcial = ?");
            $check->execute([$asignatura_id, $usuario_id, $parcial]);
            $notaExistente = $check->fetch();

            if ($notaExistente) {
                // UPDATE con auditoría de actualización
                $sqlUpd = "UPDATE notas SET teoria = ?, practica = ?, obs = ?, usuario_id_actualizacion = ?, fecha_actualizacion = NOW(), hora_actualizacion = CURTIME() WHERE id = ?";
                $stmt = $pdo->prepare($sqlUpd);
                $stmt->execute([$teoria, $practica, $obs, $_SESSION['usuario_id'], $notaExistente['id']]);
                $mensaje = "<div class='bg-green-50 border border-green-200 text-green-600 px-4 py-2 rounded-lg text-sm mb-4'>Calificación actualizada con éxito.</div>";
            } else {
                // INSERT con auditoría de creación
                $sqlIns = "INSERT INTO notas (asignatura_id, usuario_id, parcial, teoria, practica, obs, usuario_id_creacion, fecha_creacion, hora_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), CURTIME())";
                $stmt = $pdo->prepare($sqlIns);
                $stmt->execute([$asignatura_id, $usuario_id, $parcial, $teoria, $practica, $obs, $_SESSION['usuario_id']]);
                $mensaje = "<div class='bg-green-50 border border-green-200 text-green-600 px-4 py-2 rounded-lg text-sm mb-4'>Calificación asentada con éxito.</div>";
            }
        } catch (PDOException $e) {
            error_log("Error en gestión de notas: " . $e->getMessage());
            $mensaje = "<div class='bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm mb-4'>Error al procesar las calificaciones.</div>";
        }
    } else {
        $mensaje = "<div class='bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm mb-4'>Por favor, ingrese valores numéricos válidos para las notas.</div>";
    }
}

// Cargar catálogo de estudiantes vinculados a materias para el formulario de notas
$querySelects = "
    SELECT DISTINCT u.id, u.nombre 
    FROM usuarios u
    INNER JOIN asignaturas_estudiante ae ON u.id = ae.usuario_id
    WHERE u.rol = 2 ORDER BY u.nombre ASC
";
$estudiantes = $pdo->query($querySelects)->fetchAll();
$asignaturas = $pdo->query("SELECT id, nombre FROM asignaturas ORDER BY nombre ASC")->fetchAll();

// Obtener el reporte actual de calificaciones asentadas con INNER JOINs relacionales
$queryReporte = "
    SELECT n.id, u.nombre AS estudiante, a.nombre AS asignatura, n.parcial, n.teoria, n.practica, (n.teoria + n.practica) AS total, n.obs
    FROM notas n
    INNER JOIN usuarios u ON n.usuario_id = u.id
    INNER JOIN asignaturas a ON n.asignatura_id = a.id
    ORDER BY n.id DESC
";
$reporteNotas = $pdo->query($queryReporte)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Calificaciones</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50 min-h-screen p-6">

    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Registro Oficial de Calificaciones</h2>
                <p class="text-slate-500 text-xs">Asiente los parámetros de evaluación teórica y práctica por parcial.</p>
            </div>
            <a href="index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm py-2 px-4 rounded-lg transition border border-slate-300">
                Volver al Panel
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs h-fit">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Evaluar Estudiante</h3>
                
                <?php echo $mensaje; ?>

                <form action="gestionar_notas.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Estudiante</label>
                        <select name="usuario_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Seleccione el alumno --</option>
                            <?php foreach ($estudiantes as $est): ?>
                                <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Materia</label>
                        <select name="asignatura_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Seleccione la asignatura --</option>
                            <?php foreach ($asignaturas as $asig): ?>
                                <option value="<?php echo $asig['id']; ?>"><?php echo htmlspecialchars($asig['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Periodo / Parcial</label>
                        <select name="parcial" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Seleccione Parcial --</option>
                            <option value="1">Primer Parcial</option>
                            <option value="2">Segundo Parcial</option>
                            <option value="3">Mejoramiento</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nota Teoría</label>
                            <input type="number" step="0.01" min="0" max="10" name="teoria" required placeholder="0.00"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nota Práctica</label>
                            <input type="number" step="0.01" min="0" max="10" name="practica" required placeholder="0.00"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Observación (Opcional)</label>
                        <textarea name="obs" rows="2" placeholder="Ej. Cumplió con el entregable..." 
                                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                        Guardar Calificación
                    </button>
                </form>
            </div>

            <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-800">Sábana de Notas Registradas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="px-6 py-3">Estudiante</th>
                                <th class="px-6 py-3">Asignatura</th>
                                <th class="px-6 py-3">Parcial</th>
                                <th class="px-6 py-3 text-center">Teor.</th>
                                <th class="px-6 py-3 text-center">Prác.</th>
                                <th class="px-6 py-3 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                            <?php if (empty($reporteNotas)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-slate-400">No se registran calificaciones en este período.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reporteNotas as $not): ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 font-medium text-slate-800"><?php echo htmlspecialchars($not['estudiante']); ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-500"><?php echo htmlspecialchars($not['asignatura']); ?></td>
                                        <td class="px-6 py-4 text-xs">
                                            <?php 
                                                if ((int)$not['parcial'] === 1) echo "1er Parcial";
                                                if ((int)$not['parcial'] === 2) echo "2do Parcial";
                                                if ((int)$not['parcial'] === 3) echo "Mejoramiento";
                                            ?>
                                        </td>
                                        <td class="px-6 py-3 text-center font-mono"><?php echo number_format($not['teoria'], 2); ?></td>
                                        <td class="px-6 py-3 text-center font-mono"><?php echo number_format($not['practica'], 2); ?></td>
                                        <td class="px-6 py-3 text-center font-mono font-bold text-blue-600"><?php echo number_format($not['total'], 2); ?></td>
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