<?php
require_once '../../includes/seguridad.php';
require_once '../../includes/config_sitio.php';
require_once '../../config/conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

$estados = ['nueva','en_proceso','comprando','en_camino','entregada','cancelada'];

// Cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nuevo = $_POST['estado'] ?? '';
    if (in_array($nuevo, $estados, true)) {
        try {
            $upd = $pdo->prepare("UPDATE solicitudes SET estado = :estado WHERE id = :id");
            $upd->execute([':estado' => $nuevo, ':id' => $id]);
            header("Location: detalle.php?id={$id}&status=actualizada");
            exit;
        } catch (\PDOException $e) {
            morir_con_error('solicitudes.actualizar', $e);
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM solicitudes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $s = $stmt->fetch();
    if (!$s) { header("Location: index.php"); exit; }

    $canastas = $pdo->prepare("SELECT * FROM solicitud_canastas WHERE solicitud_id = :id");
    $canastas->execute([':id' => $id]);
    $items = $canastas->fetchAll();
} catch (\PDOException $e) {
    morir_con_error('solicitudes.detalle', $e);
}

$etiqueta_estado = [
    'nueva'=>'Nueva', 'en_proceso'=>'En proceso', 'comprando'=>'Comprando',
    'en_camino'=>'En camino', 'entregada'=>'Entregada', 'cancelada'=>'Cancelada',
];
$badge = [
    'nueva'=>'bg-primary','en_proceso'=>'bg-info text-dark','comprando'=>'bg-warning text-dark',
    'en_camino'=>'bg-warning text-dark','entregada'=>'bg-success','cancelada'=>'bg-secondary',
];

$status = $_GET['status'] ?? '';
require_once '../../includes/header.php';
?>

<?php if ($status === 'actualizada'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Estado actualizado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
$wa = preg_replace('/\D/', '', $s['telefono']);
$primerNombre = explode(' ', trim($s['nombre']))[0];
$estadoLbl = $etiqueta_estado[$s['estado']] ?? $s['estado'];
// Mensaje de seguimiento listo para enviar al cliente
$msgSeg = "¡Hola {$primerNombre}! 🛒 Tu pedido en " . NEGOCIO_NOMBRE . " está: *{$estadoLbl}*.";
if (!empty($s['codigo'])) {
    $msgSeg .= "\nSíguelo en vivo aquí: " . url_seguimiento($s['codigo']);
}
$linkSeg = 'https://wa.me/' . $wa . '?text=' . rawurlencode($msgSeg);
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="index.php" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-arrow-left me-1"></i>Volver</a>
        <span class="fw-semibold text-muted">Solicitud #<?= str_pad($s['id'], 4, '0', STR_PAD_LEFT); ?></span>
        <?php if (!empty($s['codigo'])): ?>
            <span class="badge bg-light text-dark border ms-1"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['codigo']); ?></span>
        <?php endif; ?>
        <span class="badge <?= $badge[$s['estado']] ?? 'bg-secondary'; ?> ms-1"><?= htmlspecialchars($estadoLbl); ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="repartir.php?id=<?= $s['id']; ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-truck me-1"></i>Iniciar reparto (mapa)
        </a>
        <a href="https://wa.me/<?= htmlspecialchars($wa); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
            <i class="bi bi-chat-dots me-1"></i>Escribir
        </a>
        <a href="<?= htmlspecialchars($linkSeg); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success">
            <i class="bi bi-whatsapp me-1"></i>Enviar seguimiento
        </a>
    </div>
</div>

<?php if (!empty($s['venta_id'])): ?>
<div class="alert alert-success d-flex justify-content-between align-items-center">
    <span><i class="bi bi-check-circle-fill me-2"></i>Pedido registrado como <strong>Venta #<?= str_pad($s['venta_id'], 4, '0', STR_PAD_LEFT); ?></strong>.</span>
    <a href="../ventas/detalle_venta.php?id=<?= (int)$s['venta_id']; ?>" class="btn btn-sm btn-success">Ver boleta</a>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Datos del cliente -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="bi bi-person me-2 text-success"></i>Cliente y entrega</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Nombre</dt><dd class="col-7 fw-semibold"><?= htmlspecialchars($s['nombre']); ?></dd>
                    <dt class="col-5 text-muted">Teléfono</dt><dd class="col-7"><?= htmlspecialchars($s['telefono']); ?></dd>
                    <dt class="col-5 text-muted">Correo</dt><dd class="col-7"><?= htmlspecialchars($s['email'] ?: '—'); ?></dd>
                    <dt class="col-5 text-muted">Dirección</dt><dd class="col-7"><?= htmlspecialchars($s['direccion']); ?></dd>
                    <dt class="col-5 text-muted">Referencia</dt><dd class="col-7"><?= htmlspecialchars($s['referencia'] ?: '—'); ?></dd>
                    <dt class="col-5 text-muted">Distrito</dt><dd class="col-7"><?= htmlspecialchars($s['distrito'] ?: '—'); ?></dd>
                    <dt class="col-5 text-muted">Fecha deseada</dt><dd class="col-7"><?= $s['fecha_entrega'] ? date('d/m/Y', strtotime($s['fecha_entrega'])) : '—'; ?></dd>
                    <dt class="col-5 text-muted">Frecuencia</dt><dd class="col-7 text-capitalize"><?= htmlspecialchars($s['frecuencia']); ?></dd>
                    <dt class="col-5 text-muted">Recibida</dt><dd class="col-7"><?= date('d/m/Y H:i', strtotime($s['creado_en'])); ?></dd>
                </dl>
            </div>
        </div>

        <!-- Cambiar estado -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-arrow-repeat me-2 text-success"></i>Actualizar estado</h6></div>
            <div class="card-body">
                <form method="POST" action="detalle.php?id=<?= $id; ?>" class="d-flex gap-2">
                    <?= csrf_field(); ?>
                    <select name="estado" class="form-select">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e; ?>" <?= $s['estado']===$e?'selected':''; ?>><?= $etiqueta_estado[$e]; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-success px-3"><i class="bi bi-save"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Pedido -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white py-3"><h5 class="mb-0 fw-bold"><i class="bi bi-basket2 me-2"></i>Canastas pedidas</h5></div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th class="ps-3">Canasta</th><th class="text-center">Cant.</th><th class="text-end">P. Unit.</th><th class="text-end pe-3">Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Sin canastas (solo lista libre).</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= htmlspecialchars($it['nombre_canasta']); ?></td>
                                    <td class="text-center"><?= $it['cantidad']; ?></td>
                                    <td class="text-end">S/. <?= number_format($it['precio_unitario'], 2); ?></td>
                                    <td class="text-end pe-3 fw-bold text-success">S/. <?= number_format($it['subtotal'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr><td colspan="3" class="text-end fw-bold pe-3">Total estimado:</td>
                            <td class="text-end pe-3 fw-bold text-success fs-6">S/. <?= number_format($s['total_estimado'], 2); ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if (!empty($s['lista_libre'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-card-list me-2 text-success"></i>Lista libre del cliente</h6>
                <a href="../ventas/cotizar.php?solicitud=<?= $s['id']; ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-magic me-1"></i>Cotizar automáticamente
                </a>
            </div>
            <div class="card-body"><pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= htmlspecialchars($s['lista_libre']); ?></pre></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($s['notas'])): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-chat-left-text me-2 text-success"></i>Notas</h6></div>
            <div class="card-body"><p class="mb-0"><?= htmlspecialchars($s['notas']); ?></p></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
