<?php
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
    <title>Login - Tienda de Verduras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100" style="background: url('/tienda_verduras/assets/img/vegetables.jpg') no-repeat center center fixed; background-size: cover;">

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="card shadow-lg border-0 rounded-3 animate__animated animate__zoomIn" style="width: 100%; max-width: 450px;">
        <div class="card-header bg-success text-white text-center py-4 rounded-top-3">
            <h3 class="mb-1 fw-bold"><i class="bi bi-shop me-2"></i>Sabor & Frescura</h3>
            <small class="text-white-50">Sistema de Gestión de Ventas</small>
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
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@tienda.com" required aria-describedby="emailFeedback">
                        <div id="emailFeedback" class="invalid-feedback">
                            Por favor, introduce un correo electrónico válido.
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="********" required aria-describedby="passwordFeedback">
                        <div id="passwordFeedback" class="invalid-feedback">
                            Por favor, introduce tu contraseña.
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-lg fw-bold animate__animated animate__pulse animate__infinite">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<footer class="footer mt-auto py-3 bg-light border-top">
    <div class="container text-center">
        <span class="text-muted">&copy; <?= date("Y"); ?> Sabor & Frescura. Todos los derechos reservados.</span>
    </div>
</footer>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</body>
</html>
