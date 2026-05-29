<?php
/**
 * Controlador de Autenticación
 * Validación segura con sentencias preparadas y desvío por roles.
 */
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $contrasena = $_POST['contrasena'] ?? '';

    if (!$email || empty($contrasena)) {
        header("Location: index.php?error=invalid");
        exit();
    }

    $db = new Database();
    $pdo = $db->connect();

    // Consulta con parámetros posicionales estándar
    $stmt = $pdo->prepare("SELECT id, nombre, email, rol, contrasena FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verificación segura del hash almacenado en la Base de Datos
    if ($user && password_verify($contrasena, $user['contrasena'])) {
        // Regenerar ID de sesión para prevenir ataques de fijación de sesión
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_rol'] = (int)$user['rol'];

        // Enrutamiento modular por Roles (1: Docente, 2: Estudiante)
        if ($_SESSION['usuario_rol'] === 1) {
            header("Location: ../docente/index.php");
        } else {
            header("Location: ../estudiante/index.php");
        }
        exit();
    } else {
        header("Location: index.php?error=invalid");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}