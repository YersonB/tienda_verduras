<?php
// login.php — acceso del personal (staff)
require_once 'config/entorno.php';
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: modulos/dashboard/index.php");
    exit;
}
require_once 'includes/cabeceras.php';
require_once 'includes/csrf.php';

$error = isset($_GET['error']) ? $_GET['error'] : "";
$min   = isset($_GET['min']) && is_numeric($_GET['min']) ? (int)$_GET['min'] : 15;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso del Personal - El mercadito de Julia</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20100%20100'%3E%3Crect%20width='100'%20height='100'%20rx='24'%20fill='%2316a34a'/%3E%3Ctext%20x='50'%20y='56'%20font-size='60'%20text-anchor='middle'%20dominant-baseline='central'%3E%F0%9F%A7%BA%3C/text%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="d-flex flex-column min-vh-100" style="background: url('<?= BASE_URL ?>/assets/img/vegetables.jpg') no-repeat center center fixed; background-size: cover;">

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="card shadow-lg border-0 rounded-3 animate__animated animate__zoomIn" style="width: 100%; max-width: 450px;">
        <div class="card-header bg-success text-white text-center py-4 rounded-top-3">
            <h3 class="mb-1 fw-bold"><i class="bi bi-person-badge me-2"></i>Acceso del Personal</h3>
            <small class="text-white-50">Panel de gestión interno</small>
        </div>
        <div class="card-body p-5">
            <?php if ($error == "1"): ?>
                <div class="alert alert-danger text-center py-2 animate__animated animate__shakeX" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Correo o contraseña incorrectos.
                </div>
            <?php elseif ($error == "2"): ?>
                <div class="alert alert-warning text-center py-2 animate__animated animate__shakeX" role="alert">
                    <i class="bi bi-lock-fill me-2"></i> Debe iniciar sesión para acceder.
                </div>
            <?php elseif ($error == "3"): ?>
                <div class="alert alert-info text-center py-2 animate__animated animate__shakeX" role="alert">
                    <i class="bi bi-clock-history me-2"></i> Su sesión expiró por inactividad. Ingrese de nuevo.
                </div>
            <?php elseif ($error == "4"): ?>
                <div class="alert alert-danger text-center py-2 animate__animated animate__shakeX" role="alert">
                    <i class="bi bi-shield-lock-fill me-2"></i> Demasiados intentos fallidos. Espere <?= htmlspecialchars($min); ?> min e intente otra vez.
                </div>
            <?php endif; ?>

            <form action="login_proceso.php" method="POST" class="mt-4">
                <?= csrf_field(); ?>
                <div class="mb-3">
                    <label for="correo" class="form-label fw-semibold">Correo Electrónico</label>
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@tienda.com" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-lg fw-bold">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
                    </button>
                </div>
            </form>

            <div class="text-center mt-4">
                <a href="index.php" class="text-muted text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i>Volver al sitio
                </a>
            </div>
        </div>
    </div>
</main>

<footer class="footer mt-auto py-3" style="background: rgba(255,255,255,.85);">
    <div class="container text-center">
        <span class="text-muted">&copy; <?= date("Y"); ?> El mercadito de Julia. Todos los derechos reservados.</span>
    </div>
</footer>

</body>
</html>
