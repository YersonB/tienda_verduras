<?php
// seguimiento.php — rastreo público del pedido por código
require_once 'includes/cabeceras.php';
require_once 'includes/config_sitio.php';
require_once 'config/conexion.php';

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
$solicitud = null;
$canastas = [];

if ($codigo !== '') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM solicitudes WHERE codigo = :c LIMIT 1");
        $stmt->execute([':c' => $codigo]);
        $solicitud = $stmt->fetch();
        if ($solicitud) {
            $c = $pdo->prepare("SELECT nombre_canasta, cantidad, subtotal FROM solicitud_canastas WHERE solicitud_id = :id");
            $c->execute([':id' => $solicitud['id']]);
            $canastas = $c->fetchAll();
        }
    } catch (\PDOException $e) {
        log_error('seguimiento.consulta', $e);
    }
}

// Mapeo de estado → paso actual (1..4)
$pasos = [
    1 => ['Pedido recibido', 'bi-clipboard-check', 'Recibimos tu pedido y lo estamos revisando.'],
    2 => ['Comprando',        'bi-basket',          'Julia está en el mercado eligiendo lo mejor.'],
    3 => ['En camino',        'bi-truck',           'Tu pedido salió hacia tu dirección. 🛵'],
    4 => ['Entregado',        'bi-house-check',     '¡Disfruta tu mercado fresco! 💚'],
];
$mapaPaso = ['nueva'=>1,'en_proceso'=>1,'comprando'=>2,'en_camino'=>3,'entregada'=>4,'cancelada'=>0];
$estado = $solicitud['estado'] ?? '';
$pasoActual = $mapaPaso[$estado] ?? 1;
$cancelada = ($estado === 'cancelada');

$primerNombre = $solicitud ? explode(' ', trim($solicitud['nombre']))[0] : '';

$titulo = 'Seguimiento de pedido — ' . NEGOCIO_NOMBRE;
require_once 'includes/publico_header.php';
?>

<style>
    .track { max-width:520px; margin:0 auto; }
    .step { display:flex; gap:16px; position:relative; padding-bottom:30px; }
    .step:not(:last-child)::before { content:''; position:absolute; left:25px; top:54px; bottom:0; width:3px; background:#e5e7eb; }
    .step.done:not(:last-child)::before { background:#16a34a; }
    .step .dot { width:52px; height:52px; border-radius:50%; flex:0 0 auto; display:flex; align-items:center;
        justify-content:center; font-size:1.4rem; background:#e5e7eb; color:#9ca3af; transition:all .3s; }
    .step.done .dot { background:#16a34a; color:#fff; }
    .step.current .dot { background:#fb923c; color:#fff; box-shadow:0 0 0 6px rgba(251,146,60,.25); animation:pulseDot 1.6s infinite; }
    @keyframes pulseDot { 0%,100%{ box-shadow:0 0 0 6px rgba(251,146,60,.25);} 50%{ box-shadow:0 0 0 12px rgba(251,146,60,.10);} }
    .step .titulo-paso { font-weight:700; }
    .step.pending .titulo-paso { color:#9ca3af; }
</style>

<section class="py-5 bg-crema" style="min-height:60vh;">
    <div class="container">

        <div class="text-center mb-4">
            <h1 class="display-6 fw-black">📍 Seguimiento de tu pedido</h1>
        </div>

        <?php if ($solicitud): ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                <div>
                                    <div class="text-muted small">Código</div>
                                    <div class="fw-black fs-4 texto-degradado"><?= htmlspecialchars($solicitud['codigo']); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">¡Hola <?= htmlspecialchars($primerNombre); ?>! 👋</div>
                                    <div class="small">Pedido del <?= date('d/m/Y', strtotime($solicitud['creado_en'])); ?></div>
                                </div>
                            </div>

                            <?php if ($cancelada): ?>
                                <div class="alert alert-danger text-center">
                                    <i class="bi bi-x-octagon-fill fs-3 d-block mb-2"></i>
                                    <strong>Pedido cancelado</strong><br>
                                    Si crees que es un error, escríbeme por WhatsApp.
                                </div>
                            <?php else: ?>
                                <div class="track">
                                    <?php foreach ($pasos as $n => $p):
                                        if ($n < $pasoActual || ($n === $pasoActual && $estado === 'entregada')) $clase = 'done';
                                        elseif ($n === $pasoActual) $clase = 'current';
                                        else $clase = 'pending';
                                    ?>
                                        <div class="step <?= $clase; ?>">
                                            <div class="dot"><i class="bi <?= $p[1]; ?>"></i></div>
                                            <div>
                                                <div class="titulo-paso"><?= $p[0]; ?>
                                                    <?php if ($clase === 'current'): ?>
                                                        <span class="badge bg-warning text-dark ms-1">Ahora</span>
                                                    <?php elseif ($clase === 'done'): ?>
                                                        <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted"><?= $p[2]; ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Resumen -->
                            <hr class="my-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-bag me-2 text-success"></i>Resumen</h6>
                            <?php if (!empty($canastas)): ?>
                                <ul class="list-unstyled mb-2">
                                    <?php foreach ($canastas as $ca): ?>
                                        <li class="d-flex justify-content-between border-bottom py-1">
                                            <span><?= (int)$ca['cantidad']; ?>× <?= htmlspecialchars($ca['nombre_canasta']); ?></span>
                                            <span class="text-muted">S/. <?= number_format($ca['subtotal'], 2); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php if (!empty($solicitud['lista_libre'])): ?>
                                <div class="small text-muted mb-2"><strong>Tu lista:</strong> <?= nl2br(htmlspecialchars($solicitud['lista_libre'])); ?></div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total estimado</span>
                                <span class="text-success">S/. <?= number_format($solicitud['total_estimado'], 2); ?></span>
                            </div>

                            <div class="d-grid mt-4">
                                <a href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener" class="btn btn-wa">
                                    <i class="bi bi-whatsapp me-2"></i>¿Dudas? Escríbeme
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <!-- Buscar por código -->
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5 text-center">
                            <?php if ($codigo !== ''): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-emoji-frown me-1"></i>No encontramos el código <strong><?= htmlspecialchars($codigo); ?></strong>.
                                    Revísalo e intenta de nuevo.
                                </div>
                            <?php endif; ?>
                            <div style="font-size:3rem;">🔎</div>
                            <p class="text-muted">Ingresa tu código de seguimiento (lo recibiste al hacer tu pedido).</p>
                            <form method="GET" action="seguimiento.php" class="input-group">
                                <input type="text" name="codigo" class="form-control text-uppercase" placeholder="Ej. MJ7KP9Q" required>
                                <button class="btn btn-success px-4"><i class="bi bi-search me-1"></i>Buscar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/publico_footer.php'; ?>
