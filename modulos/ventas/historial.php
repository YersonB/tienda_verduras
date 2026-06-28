<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

// ── Filtros ──────────────────────────────────────────────────────────────────
$fecha_inicio = isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] !== '' ? trim($_GET['fecha_inicio']) : '';
$fecha_fin    = isset($_GET['fecha_fin'])    && $_GET['fecha_fin']    !== '' ? trim($_GET['fecha_fin'])    : '';

// ── Paginación ────────────────────────────────────────────────────────────────
$por_pagina   = 15;
$pagina_actual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset       = ($pagina_actual - 1) * $por_pagina;

// Construir cláusula WHERE reutilizable
$where  = "WHERE 1=1";
$params = [];
if ($fecha_inicio !== '') {
    $where .= " AND DATE(v.fecha) >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
}
if ($fecha_fin !== '') {
    $where .= " AND DATE(v.fecha) <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}

try {
    // Total de registros (para calcular páginas)
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM ventas v $where");
    $stmtCount->execute($params);
    $total_registros = (int)$stmtCount->fetchColumn();
    $total_paginas   = max(1, (int)ceil($total_registros / $por_pagina));
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

    // Listado de ventas
    $sql = "SELECT v.id,
                   v.fecha,
                   v.total,
                   v.estado,
                   COALESCE(u.nombre, 'Usuario eliminado') AS vendedor,
                   COUNT(dv.id)                            AS num_items
            FROM ventas v
            LEFT JOIN usuarios      u  ON v.usuario_id = u.id
            LEFT JOIN detalle_ventas dv ON v.id         = dv.venta_id
            $where
            GROUP BY v.id, v.fecha, v.total, v.estado, u.nombre
            ORDER BY v.fecha DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
    $stmt->execute();
    $ventas = $stmt->fetchAll();

    // Resumen del período filtrado
    // Los montos consideran solo ventas no anuladas
    $stmtRes = $pdo->prepare(
        "SELECT COUNT(*) AS total_ventas,
                COALESCE(SUM(CASE WHEN estado <> 'anulada' THEN total ELSE 0 END), 0) AS monto_total,
                COALESCE(AVG(CASE WHEN estado <> 'anulada' THEN total END), 0)         AS ticket_promedio
         FROM ventas v $where"
    );
    $stmtRes->execute($params);
    $resumen = $stmtRes->fetch();

} catch (\PDOException $e) {
    die("Error al cargar el historial.");
}

// Helper: construir URL de paginación conservando los filtros actuales
function url_pagina(int $pagina): string {
    $q = $_GET;
    $q['pagina'] = $pagina;
    return '?' . http_build_query($q);
}

require_once '../../includes/header.php';
?>

<?php panel_header('Historial de Ventas', 'bi-clock-history', 'Todas las boletas emitidas',
    '<button class="btn btn-light fw-semibold" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir / PDF</button>'
); ?>

<!-- ── Tarjetas de resumen ──────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-success bg-opacity-10">
                    <i class="bi bi-receipt fs-3 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Boletas emitidas</div>
                    <div class="fs-3 fw-bold"><?= number_format($resumen['total_ventas']); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-primary bg-opacity-10">
                    <i class="bi bi-cash-stack fs-3 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Total recaudado</div>
                    <div class="fs-3 fw-bold text-primary">S/. <?= number_format($resumen['monto_total'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-warning bg-opacity-10">
                    <i class="bi bi-graph-up fs-3 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Ticket promedio</div>
                    <div class="fs-3 fw-bold text-warning">S/. <?= number_format($resumen['ticket_promedio'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Filtro por fechas ────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="historial.php" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold mb-1 small">Desde</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($fecha_inicio); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold mb-1 small">Hasta</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($fecha_fin); ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm px-3">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="historial.php" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-x-circle me-1"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── Tabla de ventas ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3" style="width:80px;"># Boleta</th>
                        <th>Fecha y Hora</th>
                        <th>Vendedor</th>
                        <th class="text-center">Productos</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Total</th>
                        <th class="text-center" style="width:100px;">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($ventas) > 0): ?>
                        <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted">#<?= str_pad($v['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <span class="fw-semibold"><?= date('d/m/Y', strtotime($v['fecha'])); ?></span>
                                    <br><small class="text-muted"><?= date('H:i', strtotime($v['fecha'])); ?> hrs</small>
                                </td>
                                <td>
                                    <i class="bi bi-person-circle text-muted me-1"></i>
                                    <?= htmlspecialchars($v['vendedor']); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill"><?= $v['num_items']; ?> ítem(s)</span>
                                </td>
                                <td class="text-center">
                                    <?php if (($v['estado'] ?? 'completada') === 'anulada'): ?>
                                        <span class="badge bg-danger">Anulada</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Completada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold fs-6 <?= ($v['estado'] ?? '') === 'anulada' ? 'text-muted text-decoration-line-through' : 'text-success'; ?>">
                                    S/. <?= number_format($v['total'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <a href="detalle_venta.php?id=<?= $v['id']; ?>"
                                       class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No se encontraron ventas para el período seleccionado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Pie de tabla: contador + paginación ─────────────────────────── -->
    <?php if ($total_registros > 0): ?>
    <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
        <small class="text-muted">
            Mostrando <?= min($offset + 1, $total_registros); ?>–<?= min($offset + $por_pagina, $total_registros); ?>
            de <strong><?= $total_registros; ?></strong> venta(s)
        </small>
        <?php if ($total_paginas > 1): ?>
        <nav aria-label="Paginación">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?= url_pagina($pagina_actual - 1); ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                    <?php
                    // Mostrar solo páginas cercanas a la actual para no saturar
                    if ($p === 1 || $p === $total_paginas || abs($p - $pagina_actual) <= 2) :
                    ?>
                    <li class="page-item <?= $p === $pagina_actual ? 'active' : ''; ?>">
                        <a class="page-link" href="<?= url_pagina($p); ?>"><?= $p; ?></a>
                    </li>
                    <?php elseif (abs($p - $pagina_actual) === 3): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?= url_pagina($pagina_actual + 1); ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .navbar, .btn, .card-footer, footer, form { display: none !important; }
    .container { max-width: 100% !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; page-break-inside: avoid; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
