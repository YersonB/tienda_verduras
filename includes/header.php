<?php
require_once __DIR__ . '/../config/entorno.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uri_actual = $_SERVER['REQUEST_URI'];
function nav_active(string $segmento): string {
    global $uri_actual;
    return str_contains($uri_actual, $segmento) ? 'active" aria-current="page' : '';
}

/**
 * Banner de encabezado de página, consistente en todo el panel.
 * $acciones: HTML ya armado (botones) que se muestran a la derecha.
 */
function panel_header(string $titulo, string $icono = 'bi-grid-1x2', string $subtitulo = '', string $acciones = ''): void {
    echo '<div class="card border-0 shadow-sm mb-4 text-white overflow-hidden" '
       . 'style="background:radial-gradient(600px 200px at 92% -50%, rgba(253,224,71,.28), transparent 60%), '
       . 'linear-gradient(120deg,#16a34a 0%,#15803d 55%,#14532d 100%);">';
    echo '<div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 px-4">';
    echo '<div><h2 class="text-white mb-0 h3"><i class="bi ' . htmlspecialchars($icono) . ' me-2"></i>'
       . htmlspecialchars($titulo) . '</h2>';
    if ($subtitulo !== '') {
        echo '<small class="text-white-50">' . htmlspecialchars($subtitulo) . '</small>';
    }
    echo '</div>';
    if ($acciones !== '') {
        echo '<div class="d-flex gap-2 flex-wrap">' . $acciones . '</div>';
    }
    echo '</div></div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El mercadito de Julia - Panel de Gestión</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20100%20100'%3E%3Crect%20width='100'%20height='100'%20rx='24'%20fill='%2316a34a'/%3E%3Ctext%20x='50'%20y='56'%20font-size='60'%20text-anchor='middle'%20dominant-baseline='central'%3E%F0%9F%A7%BA%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --jv-green:#16a34a; --jv-green-d:#15803d; --jv-ink:#1f2937; --jv-bg:#f3f6f4; }
        body { font-family:'Plus Jakarta Sans',system-ui,sans-serif; background:var(--jv-bg) !important; color:var(--jv-ink); }
        h1,h2,h3,h4,h5,.display-font { font-family:'Fraunces',Georgia,serif; letter-spacing:-.3px; }
        .navbar-brand { font-family:'Fraunces',serif; font-weight:900 !important; font-size:1.25rem; }

        /* Navbar */
        .navbar.sticky-top { backdrop-filter:saturate(1.1); }
        .navbar .nav-link { font-weight:600; border-radius:9px; padding-left:.8rem; padding-right:.8rem; transition:all .15s ease; }
        .navbar .nav-link:hover { background:rgba(22,163,74,.08); color:var(--jv-green-d) !important; }
        .navbar .nav-link.active { background:rgba(22,163,74,.12); color:var(--jv-green-d) !important; font-weight:700; }

        /* Tarjetas */
        .card { border:none; border-radius:18px; }
        .shadow-sm { box-shadow:0 8px 22px -14px rgba(16,40,30,.22) !important; }
        .card-hover { transition:transform .2s ease, box-shadow .2s ease; }
        .card-hover:hover { transform:translateY(-4px); box-shadow:0 16px 32px -18px rgba(16,40,30,.35) !important; }

        /* Botones y tablas con color de marca */
        .btn { border-radius:11px; font-weight:600; }
        .btn-success { background:var(--jv-green); border-color:var(--jv-green); }
        .btn-success:hover { background:var(--jv-green-d); border-color:var(--jv-green-d); }
        .table-dark { --bs-table-bg:#15402c; }
        .table > :not(caption) > * > * { padding-top:.85rem; padding-bottom:.85rem; }

        /* Tile de iconos para tarjetas de stat */
        .stat-tile { width:56px; height:56px; border-radius:16px; display:inline-flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0; }
        .section-title { font-family:'Fraunces',serif; font-weight:700; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="<?= BASE_URL ?>/modulos/dashboard/index.php">
            <i class="bi bi-basket2-fill me-2"></i>El mercadito de Julia
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('dashboard'); ?>" href="<?= BASE_URL ?>/modulos/dashboard/index.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('productos'); ?>" href="<?= BASE_URL ?>/modulos/productos/index.php">
                        <i class="bi bi-box-seam me-1"></i>Inventario
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('canastas'); ?>" href="<?= BASE_URL ?>/modulos/canastas/index.php">
                        <i class="bi bi-basket2 me-1"></i>Canastas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('nueva_venta'); ?>" href="<?= BASE_URL ?>/modulos/ventas/nueva_venta.php">
                        <i class="bi bi-cart-plus me-1"></i>Nueva Venta
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('solicitudes'); ?>" href="<?= BASE_URL ?>/modulos/solicitudes/index.php">
                        <i class="bi bi-inboxes me-1"></i>Solicitudes
                        <?php
                        if (!isset($GLOBALS['__solicitudes_nuevas'])) {
                            try {
                                require_once __DIR__ . '/../config/conexion.php';
                                $GLOBALS['__solicitudes_nuevas'] = (int)$pdo->query("SELECT COUNT(*) FROM solicitudes WHERE estado='nueva'")->fetchColumn();
                            } catch (\Throwable $e) { $GLOBALS['__solicitudes_nuevas'] = 0; }
                        }
                        $__nuevas = (int)$GLOBALS['__solicitudes_nuevas'];
                        ?>
                        <span id="badge-solicitudes" class="badge rounded-pill bg-danger <?= $__nuevas > 0 ? '' : 'd-none'; ?>"><?= $__nuevas; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('historial'); ?>" href="<?= BASE_URL ?>/modulos/ventas/historial.php">
                        <i class="bi bi-clock-history me-1"></i>Historial
                    </a>
                </li>
                <?php if (($_SESSION['usuario_rol'] ?? '') === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('usuarios'); ?>" href="<?= BASE_URL ?>/modulos/usuarios/index.php">
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
                                <a class="dropdown-item" href="<?= BASE_URL ?>/modulos/perfil/index.php">
                                    <i class="bi bi-person-fill me-1"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"
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
