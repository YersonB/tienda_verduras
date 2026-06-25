<?php
// Arrancar la sesión para verificar si ya está logueado
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: modulos/productos/index.php");
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : "";
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
<body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">

<div class="card shadow-lg border-0" style="width: 100%; max-width: 400px;">
    <div class="card-header bg-success text-white text-center py-4">
        <h3 class="mb-0 fw-bold"><i class="bi bi-shop me-2"></i>Sabor & Frescura</h3>
        <small>Sistema de Gestión de Ventas</small>
    </div>
    <div class="card-body p-4">
        
        <?php if ($error == "1"): ?>
            <div class="alert alert-danger text-center py-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Correo o contraseña incorrectos.
            </div>
        <?php elseif ($error == "2"): ?>
            <div class="alert alert-warning text-center py-2" role="alert">
                <i class="bi bi-lock-fill me-2"></i> Debe iniciar sesión para acceder.
            </div>
        <?php endif; ?>

        <form action="login_proceso.php" method="POST">
            <div class="mb-3">
                <label for="correo" class="form-label fw-semibold">Correo Electrónico</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@tienda.com" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-success fw-bold py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>