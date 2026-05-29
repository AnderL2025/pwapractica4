<?php
/**
 * Enrutador Principal del Sistema
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirección directa al login centralizado
header("Location: login/index.php");
exit();