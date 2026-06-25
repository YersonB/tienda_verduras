<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/tienda_verduras/modulos/productos/index.php">
            <i class="bi bi-shop me-2"></i>Tienda Verduras
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="/tienda_verduras/modulos/productos/index.php">
                        <i class="bi bi-box-seam me-1"></i>Inventario
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/tienda_verduras/modulos/ventas/nueva_venta.php">
                        <i class="bi bi-cart-plus me-1"></i>Nueva Venta
                    </a>
                </li>
                
                <?php if (isset($_SESSION['usuario_nombre'])): ?>
                    <li class="nav-item ms-lg-3">
                        <span class="badge bg-dark text-white p-2">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['usuario_nombre']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-bold" href="/tienda_verduras/logout.php" onclick="return confirm('¿Desea cerrar sesión?');">
                            <i class="bi bi-box-arrow-right ms-1"></i> Salir
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container">