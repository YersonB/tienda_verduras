<?php
// includes/seguridad.php

// Asegurar que la sesión esté iniciada en la página actual
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si la variable de sesión obligatoria no existe, bloquear el acceso
if (!isset($_SESSION['usuario_id'])) {
    // Redirigir al login con un código de advertencia
    header("Location: /tienda_verduras/index.php?error=2");
    exit;
}
?>