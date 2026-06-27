<?php
// includes/seguridad.php
// Guardián de rutas: sesión, expiración por inactividad, CSRF, cabeceras y roles.

require_once __DIR__ . '/cabeceras.php';
require_once __DIR__ . '/errores.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Acceso: requiere sesión iniciada ──────────────────────────────────────────
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /tienda_verduras/index.php?error=2");
    exit;
}

// ── Expiración por inactividad (30 minutos) ──────────────────────────────────
define('SESION_MAX_INACTIVIDAD', 30 * 60);
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESION_MAX_INACTIVIDAD) {
    session_unset();
    session_destroy();
    header("Location: /tienda_verduras/index.php?error=3");
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/csrf.php';

// ── Control de roles ─────────────────────────────────────────────────────────
function usuario_rol(): string
{
    return $_SESSION['usuario_rol'] ?? 'vendedor';
}

function es_admin(): bool
{
    return usuario_rol() === 'admin';
}

/**
 * Bloquea el acceso si el usuario no tiene el rol requerido.
 * Uso: requerir_rol('admin'); al inicio de un módulo restringido.
 */
function requerir_rol(string $rol): void
{
    if (usuario_rol() !== $rol) {
        http_response_code(403);
        die("Acceso denegado. No tienes permisos para ver esta sección.");
    }
}
