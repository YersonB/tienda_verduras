<?php
// login_proceso.php
session_start();
require_once 'includes/cabeceras.php';
require_once 'includes/errores.php';
require_once 'includes/csrf.php';
require_once 'includes/login_throttle.php';
require_once 'config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

csrf_verify();

// ── Anti fuerza bruta ────────────────────────────────────────────────────────
$bloqueo = throttle_bloqueo_restante();
if ($bloqueo > 0) {
    $minutos = ceil($bloqueo / 60);
    header("Location: index.php?error=4&min=" . $minutos);
    exit;
}

$correo   = trim($_POST['correo'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($correo) || empty($password)) {
    header("Location: index.php?error=1");
    exit;
}

try {
    $sql  = "SELECT id, nombre, password, rol FROM usuarios WHERE correo = :correo LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Login correcto: limpiar intentos y blindar la sesión contra fixation
        throttle_limpiar();
        session_regenerate_id(true);

        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol']    = $usuario['rol'];
        $_SESSION['last_activity']  = time();

        header("Location: modulos/dashboard/index.php");
        exit;
    }

    // Credenciales incorrectas: registrar el intento fallido
    throttle_registrar_fallo();
    header("Location: index.php?error=1");
    exit;

} catch (\PDOException $e) {
    morir_con_error('login', $e, "Error en el sistema. Intenta nuevamente más tarde.");
}
