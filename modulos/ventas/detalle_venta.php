<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: historial.php");
    exit;
}

$venta_id = (int)$_GET['id'];

try {
    // Cabecera de la venta
    $stmt = $pdo->prepare(
        "SELECT v.*, COALESCE(u.nombre, 'Usuario eliminado') AS vendedor
         FROM ventas v
         LEFT JOIN usuarios u ON v.usuario_id = u.id
         WHERE v.id = :id"
    );
    $stmt->execute([':id' => $venta_id]);
    $venta = $stmt->fetch();

    if (!$venta) {
        header("Location: historial.php");
        exit;
    }

    // Detalle de productos
    $stmtDet = $pdo->prepare(
        "SELECT dv.cantidad,
                dv.precio_unitario,
                dv.subtotal,
                COALESCE(p.nombre, '[Producto eliminado]') AS nombre,
                COALESCE(p.categoria, '—')                 AS categoria,
                COALESCE(p.unidad_medida, 'unid')          AS unidad_medida
         FROM detalle_ventas dv
         LEFT JOIN productos p ON dv.producto_id = p.id
         WHERE dv.venta_id = :venta_id
         ORDER BY nombre ASC"
    );
    $stmtDet->execute([':venta_id' => $venta_id]);
    $detalles = $stmtDet->fetchAll();

} catch (\PDOException $e) {
    morir_con_error('ventas.detalle', $e, "Error al cargar el detalle de la venta.");
}

$anulada = ($venta['estado'] ?? 'completada') === 'anulada';
$status  = $_GET['status'] ?? '';
$autoprint = isset($_GET['print']) && $_GET['print'] === '1';

require_once '../../includes/header.php';
?>

<?php if ($status === 'anulada'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>La venta fue anulada y el stock devuelto al inventario.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($status === 'ya_anulada'): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>Esta venta ya estaba anulada.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Encabezado de página ─────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="historial.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Volver al Historial
        </a>
        <span class="text-muted fw-semibold">
            Boleta #<?= str_pad($venta['id'], 4, '0', STR_PAD_LEFT); ?>
        </span>
        <?php if ($anulada): ?>
            <span class="badge bg-danger ms-2"><i class="bi bi-x-octagon me-1"></i>ANULADA</span>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir / PDF
        </button>
        <?php if (es_admin() && !$anulada): ?>
            <form method="POST" action="anular_venta.php"
                  data-confirm="¿Anular esta venta? Se devolverá el stock al inventario. Esta acción no se puede deshacer."
                  data-confirm-btn="Sí, anular">
                <input type="hidden" name="id" value="<?= $venta['id']; ?>">
                <?= csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-octagon me-1"></i>Anular Venta
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- ── Datos de la boleta ───────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-hash me-1"></i>Número de boleta</div>
                <div class="fs-4 fw-bold text-dark">#<?= str_pad($venta['id'], 4, '0', STR_PAD_LEFT); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-calendar-event me-1"></i>Fecha y hora</div>
                <div class="fw-bold"><?= date('d/m/Y', strtotime($venta['fecha'])); ?></div>
                <div class="text-muted small"><?= date('H:i:s', strtotime($venta['fecha'])); ?> hrs</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-person-circle me-1"></i>Atendido por</div>
                <div class="fw-bold"><?= htmlspecialchars($venta['vendedor']); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Tabla de productos ───────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Detalle de productos vendidos</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Producto</th>
                        <th>Categoría</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Precio Unitario</th>
                        <th class="text-end pe-3">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $item): ?>
                        <tr>
                            <td class="ps-3 fw-semibold"><?= htmlspecialchars($item['nombre']); ?></td>
                            <td>
                                <span class="badge bg-secondary text-uppercase" style="font-size:0.7rem;">
                                    <?= htmlspecialchars($item['categoria']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?= number_format($item['cantidad'], 3); ?>
                                <small class="text-muted"><?= htmlspecialchars($item['unidad_medida']); ?></small>
                            </td>
                            <td class="text-end text-muted">S/. <?= number_format($item['precio_unitario'], 2); ?></td>
                            <td class="text-end pe-3 fw-bold text-success">S/. <?= number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="4" class="text-end fw-bold pe-3 py-3 fs-6">TOTAL COBRADO:</td>
                        <td class="text-end pe-3 fw-bold text-success fs-5">
                            S/. <?= number_format($venta['total'], 2); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- ── Estilos solo para impresión ─────────────────────────────────────── -->
<style>
@media print {
    .navbar, .btn, footer, .alert { display: none !important; }
    .container { max-width: 100% !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<?php if ($autoprint): ?>
<script>
    // Boleta recién generada: abrir el diálogo de impresión automáticamente
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
