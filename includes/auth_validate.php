<?php
/**
 * Middleware de Control de Accesos por Roles
 * Asegura las vistas contra accesos no autorizados.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAuth($allowedRole) {
    // Si no hay sesión activa, redirige al login unificado
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol'])) {
        header("Location: ../login/index.php");
        exit();
    }

    // Si el rol no coincide con el permitido, bloquea el acceso
    if ((int)$_SESSION['usuario_rol'] !== (int)$allowedRole) {
        header("Location: ../login/index.php?error=unauthorized");
        exit();
    }
}