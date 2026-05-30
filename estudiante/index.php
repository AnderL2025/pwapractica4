<?php
/**
 * Consulta de Calificaciones - Rol Estudiante
 * Garantiza el aislamiento de registros basados en la sesión activa.
 */
require_once '../config/database.php';
require_once '../includes/auth_validate.php';

checkAuth(2); // Capa de seguridad: Solo Estudiantes (Rol 2)

$db = new Database();
$pdo = $db->connect();

// Consulta relacional limpia para extraer las notas asignadas al estudiante en sesión
$queryNotas = "
    SELECT a.nombre AS asignatura, n.parcial, n.teoria, n.practica, (n.teoria + n.practica) AS total, n.obs
    FROM notas n
    INNER JOIN asignaturas a ON n.asignatura_id = a.id
    WHERE n.usuario_id = ?
    ORDER BY a.nombre ASC, n.parcial ASC
";
$stmt = $pdo->prepare($queryNotas);
$stmt->execute([$_SESSION['usuario_id']]);
$misNotas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Calificaciones</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    <nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shadow-xs">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Portal de Calificaciones</h1>
            <p class="text-slate-500 text-xs">Estudiante: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
        </div>
        <a href="../logout.php" class="bg-red-50 hover:bg-red-100 text-red-600 font-medium text-sm py-2 px-4 rounded-lg transition border border-red-200">
            Cerrar Sesión
        </a>
    </nav>

    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800">Mi Récord Académico Oficial</h3>
                <p class="text-slate-500 text-xs mt-0.5">Desglose de evaluaciones de teoría y práctica asentadas por sus docentes.</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 text-xs font-bold uppercase tracking-wider">
                            <th class="px-6 py-3">Asignatura</th>
                            <th class="px-6 py-3">Periodo / Parcial</th>
                            <th class="px-6 py-3 text-center">Nota Teoría</th>
                            <th class="px-6 py-3 text-center">Nota Práctica</th>
                            <th class="px-6 py-3 text-center">Calificación Total</th>
                            <th class="px-6 py-3">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                        <?php if (empty($misNotas)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                    <span class="block text-base font-semibold text-slate-500 mb-1">Sin calificaciones</span>
                                    Aún no se han publicado notas para su cuenta de usuario en este periodo.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($misNotas as $nota): ?>
                                <tr class="hover:bg-slate-50/30 transition">
                                    <td class="px-6 py-4 font-semibold text-slate-800"><?php echo htmlspecialchars($nota['asignatura']); ?></td>
                                    <td class="px-6 py-4 text-xs">
                                        <?php 
                                            if ((int)$nota['parcial'] === 1) echo "1er Parcial";
                                            if ((int)$nota['parcial'] === 2) echo "2do Parcial";
                                            if ((int)$nota['parcial'] === 3) echo "Mejoramiento";
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono"><?php echo number_format($nota['teoria'], 2); ?></td>
                                    <td class="px-6 py-4 text-center font-mono"><?php echo number_format($nota['practica'], 2); ?></td>
                                    <td class="px-6 py-4 text-center font-mono font-bold text-blue-600 bg-blue-50/30"><?php echo number_format($nota['total'], 2); ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-500 italic max-w-xs truncate">
                                        <?php echo !empty($nota['obs']) ? htmlspecialchars($nota['obs']) : '---'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>