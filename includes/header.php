<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uri_actual = $_SERVER['REQUEST_URI'];
function nav_active(string $segmento): string {
    global $uri_actual;
    return str_contains($uri_actual, $segmento) ? 'active" aria-current="page' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabor & Frescura - Tienda de Verduras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="/tienda_verduras/modulos/dashboard/index.php">
            <i class="bi bi-shop me-2"></i>Sabor & Frescura
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('dashboard'); ?>" href="/tienda_verduras/modulos/dashboard/index.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('productos'); ?>" href="/tienda_verduras/modulos/productos/index.php">
                        <i class="bi bi-box-seam me-1"></i>Inventario
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('nueva_venta'); ?>" href="/tienda_verduras/modulos/ventas/nueva_venta.php">
                        <i class="bi bi-cart-plus me-1"></i>Nueva Venta
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('historial'); ?>" href="/tienda_verduras/modulos/ventas/historial.php">
                        <i class="bi bi-clock-history me-1"></i>Historial
                    </a>
                </li>
                <?php if (($_SESSION['usuario_rol'] ?? '') === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('usuarios'); ?>" href="/tienda_verduras/modulos/usuarios/index.php">
                        <i class="bi bi-people me-1"></i>Usuarios
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if (isset($_SESSION['usuario_nombre'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['usuario_nombre']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <span class="dropdown-item-text text-muted" style="font-size:0.8rem;">
                                    Rol: <?= htmlspecialchars(ucfirst($_SESSION['usuario_rol'] ?? 'usuario')); ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/tienda_verduras/modulos/perfil/index.php">
                                    <i class="bi bi-person-fill me-1"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="/tienda_verduras/logout.php"
                                   onclick="return confirm('¿Desea cerrar sesión?');">
                                    <i class="bi bi-box-arrow-right me-1"></i> Salir
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-2">
