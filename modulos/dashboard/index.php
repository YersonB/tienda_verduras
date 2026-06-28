<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

function dia_es(string $fecha): string
{
    $map = ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb', 'Sun' => 'Dom'];
    return ($map[date('D', strtotime($fecha))] ?? date('D', strtotime($fecha))) . ' ' . date('d/m', strtotime($fecha));
}

try {
    // ── Resumen del día ──────────────────────────────────────────────────
    $resumen_hoy = $pdo->query(
        "SELECT COUNT(*) AS ventas_hoy, COALESCE(SUM(total), 0) AS ingresos_hoy
         FROM ventas WHERE DATE(fecha) = CURDATE()"
    )->fetch();

    // ── Total de productos registrados ───────────────────────────────────
    $total_productos = (int)$pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();

    // ── Solicitudes nuevas de clientes ───────────────────────────────────
    $solicitudes_nuevas = 0;
    try {
        $solicitudes_nuevas = (int)$pdo->query("SELECT COUNT(*) FROM solicitudes WHERE estado='nueva'")->fetchColumn();
    } catch (\PDOException $e) { /* tabla aún no creada */ }

    // ── Productos con stock bajo (umbral: 15) ────────────────────────────
    $productos_bajo_stock = $pdo->query(
        "SELECT id, nombre, categoria, stock, unidad_medida
         FROM productos WHERE stock <= 15 ORDER BY stock ASC LIMIT 10"
    )->fetchAll();
    $count_bajo_stock = count($productos_bajo_stock);

    // ── Ventas de los últimos 7 días (para el gráfico) ───────────────────
    $ventas_semana_raw = $pdo->query(
        "SELECT DATE(fecha) AS dia, COUNT(*) AS cantidad, SUM(total) AS monto
         FROM ventas
         WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(fecha)
         ORDER BY dia ASC"
    )->fetchAll();

    // Llenar los 7 días aunque no haya ventas ese día
    $dias_chart = [];
    for ($i = 6; $i >= 0; $i--) {
        $f = date('Y-m-d', strtotime("-{$i} days"));
        $dias_chart[$f] = ['label' => dia_es($f), 'monto' => 0, 'cantidad' => 0];
    }
    foreach ($ventas_semana_raw as $row) {
        if (isset($dias_chart[$row['dia']])) {
            $dias_chart[$row['dia']]['monto']    = (float)$row['monto'];
            $dias_chart[$row['dia']]['cantidad'] = (int)$row['cantidad'];
        }
    }
    $chart_labels   = json_encode(array_column(array_values($dias_chart), 'label'));
    $chart_montos   = json_encode(array_column(array_values($dias_chart), 'monto'));
    $chart_cantidades = json_encode(array_column(array_values($dias_chart), 'cantidad'));

    // ── Top 5 productos más vendidos (con margen) ────────────────────────
    $top_productos = $pdo->query(
        "SELECT p.nombre, p.unidad_medida, p.categoria,
                SUM(dv.cantidad)  AS total_vendido,
                SUM(dv.subtotal)  AS total_ingresos,
                SUM(dv.subtotal - (p.precio_compra * dv.cantidad)) AS margen
         FROM detalle_ventas dv
         JOIN productos p ON dv.producto_id = p.id
         GROUP BY dv.producto_id, p.nombre, p.unidad_medida, p.categoria, p.precio_compra
         ORDER BY total_vendido DESC
         LIMIT 5"
    )->fetchAll();

    // ── Margen de ganancia del mes actual ────────────────────────────────
    // Ingreso - costo (precio_compra * cantidad) de todo lo vendido este mes.
    $margen_mes = $pdo->query(
        "SELECT COALESCE(SUM(dv.subtotal), 0)                              AS ingresos,
                COALESCE(SUM(p.precio_compra * dv.cantidad), 0)            AS costos,
                COALESCE(SUM(dv.subtotal - (p.precio_compra * dv.cantidad)), 0) AS margen
         FROM detalle_ventas dv
         JOIN ventas v    ON dv.venta_id = v.id
         JOIN productos p ON dv.producto_id = p.id
         WHERE YEAR(v.fecha) = YEAR(CURDATE()) AND MONTH(v.fecha) = MONTH(CURDATE())"
    )->fetch();
    $pct_margen = $margen_mes['ingresos'] > 0
        ? ($margen_mes['margen'] / $margen_mes['ingresos']) * 100
        : 0;

    // ── Últimas 5 ventas ─────────────────────────────────────────────────
    $ultimas_ventas = $pdo->query(
        "SELECT v.id, v.fecha, v.total, COALESCE(u.nombre, '—') AS vendedor
         FROM ventas v
         LEFT JOIN usuarios u ON v.usuario_id = u.id
         ORDER BY v.fecha DESC
         LIMIT 5"
    )->fetchAll();

} catch (\PDOException $e) {
    morir_con_error('dashboard', $e, "Error al cargar el dashboard.");
}

require_once '../../includes/header.php';
?>

<!-- ── Banner de bienvenida ──────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4 text-white overflow-hidden position-relative"
     style="background:radial-gradient(700px 220px at 90% -40%, rgba(253,224,71,.30), transparent 60%), linear-gradient(120deg,#16a34a 0%, #15803d 55%, #14532d 100%);">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 py-4 px-4">
        <div>
            <h2 class="text-white mb-1">¡Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']); ?>! 👋</h2>
            <span class="text-white-50">
                <i class="bi bi-calendar3 me-1"></i><?= ucfirst(fecha_larga()); ?>
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="../solicitudes/index.php" class="btn btn-light fw-semibold">
                <i class="bi bi-inboxes me-1"></i>Solicitudes
            </a>
            <a href="../ventas/nueva_venta.php" class="btn btn-amarillo fw-semibold" style="background:#fde047;color:#713f12;border:none;">
                <i class="bi bi-cart-plus me-1"></i>Nueva Venta
            </a>
        </div>
    </div>
</div>

<?php if ($solicitudes_nuevas > 0): ?>
<div class="alert d-flex justify-content-between align-items-center shadow-sm border-0" role="alert"
     style="background:rgba(22,163,74,.1);">
    <div class="text-success">
        <i class="bi bi-inboxes-fill me-2"></i>
        Tienes <strong><?= $solicitudes_nuevas; ?></strong> solicitud(es) nueva(s) de clientes esperando atención.
    </div>
    <a href="../solicitudes/index.php?estado=nueva" class="btn btn-sm btn-success">Ver solicitudes</a>
</div>
<?php endif; ?>

<!-- ── Tarjetas de resumen ───────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Ventas hoy', $resumen_hoy['ventas_hoy'], 'boleta(s)', 'bi-receipt', '#16a34a', 'rgba(22,163,74,.12)'],
        ['Ingresos hoy', 'S/. ' . number_format($resumen_hoy['ingresos_hoy'], 2), 'recaudados', 'bi-cash-coin', '#f97316', 'rgba(249,115,22,.12)'],
        ['Productos', $total_productos, 'en inventario', 'bi-box-seam', '#0ea5e9', 'rgba(14,165,233,.12)'],
        ['Stock bajo', $count_bajo_stock, 'producto(s)', 'bi-exclamation-triangle', $count_bajo_stock > 0 ? '#ef4444' : '#94a3b8', $count_bajo_stock > 0 ? 'rgba(239,68,68,.12)' : 'rgba(148,163,184,.14)'],
    ];
    foreach ($cards as $c): ?>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-tile" style="background:<?= $c[5]; ?>;color:<?= $c[4]; ?>;">
                        <i class="bi <?= $c[3]; ?>"></i>
                    </span>
                    <div>
                        <div class="text-muted" style="font-size:.78rem;"><?= $c[0]; ?></div>
                        <div class="fw-bold lh-1" style="font-size:1.55rem;color:<?= $c[4]; ?>;"><?= $c[1]; ?></div>
                        <div class="text-muted" style="font-size:.72rem;"><?= $c[2]; ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ── Margen de ganancia del mes ────────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center g-3 text-center text-md-start">
            <div class="col-md-3">
                <h5 class="section-title mb-1"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Margen del mes</h5>
                <small class="text-muted"><?= ucfirst(mes_anio()); ?></small>
            </div>
            <div class="col-md-3 border-start">
                <div class="text-muted small">Ingresos</div>
                <div class="fs-5 fw-bold" style="color:#f97316;">S/. <?= number_format($margen_mes['ingresos'], 2); ?></div>
            </div>
            <div class="col-md-3 border-start">
                <div class="text-muted small">Costo de mercadería</div>
                <div class="fs-5 fw-bold text-secondary">S/. <?= number_format($margen_mes['costos'], 2); ?></div>
            </div>
            <div class="col-md-3 border-start">
                <div class="text-muted small">Ganancia estimada</div>
                <div class="fs-4 fw-bold text-success">
                    S/. <?= number_format($margen_mes['margen'], 2); ?>
                    <span class="badge bg-success bg-opacity-25 text-success align-middle" style="font-size:.7rem;">
                        <?= number_format($pct_margen, 1); ?>%
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Gráfico + Últimas ventas ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Gráfico de ventas 7 días -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <h6 class="section-title mb-0"><i class="bi bi-bar-chart-line me-2 text-success"></i>Ventas — últimos 7 días</h6>
            </div>
            <div class="card-body pt-2">
                <canvas id="chartVentas" height="110"></canvas>
            </div>
        </div>
    </div>

    <!-- Últimas ventas -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <h6 class="section-title mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Últimas ventas</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($ultimas_ventas)): ?>
                        <li class="list-group-item text-center text-muted py-4">Sin ventas registradas.</li>
                    <?php else: ?>
                        <?php foreach ($ultimas_ventas as $v): ?>
                            <li class="list-group-item px-3 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold" style="font-size:.85rem;">
                                        <a href="../ventas/detalle_venta.php?id=<?= $v['id']; ?>" class="text-decoration-none text-dark">
                                            #<?= str_pad($v['id'], 4, '0', STR_PAD_LEFT); ?>
                                        </a>
                                    </div>
                                    <div class="text-muted" style="font-size:.72rem;">
                                        <?= date('d/m H:i', strtotime($v['fecha'])); ?> · <?= htmlspecialchars($v['vendedor']); ?>
                                    </div>
                                </div>
                                <span class="fw-bold text-success" style="font-size:.9rem;">
                                    S/. <?= number_format($v['total'], 2); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <div class="p-2 text-center border-top">
                    <a href="../ventas/historial.php" class="btn btn-sm btn-outline-primary w-100">
                        Ver historial completo <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ── Top productos + Stock bajo ───────────────────────────────────────── -->
<div class="row g-3">

    <!-- Top 5 productos más vendidos -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <h6 class="section-title mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Top 5 productos más vendidos</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($top_productos)): ?>
                    <p class="text-center text-muted py-4">Sin datos de ventas aún.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width:28px;">#</th>
                                    <th>Producto</th>
                                    <th class="text-end">Vendido</th>
                                    <th class="text-end">Ingreso</th>
                                    <th class="text-end pe-3">Margen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_productos as $i => $p): ?>
                                    <tr>
                                        <td class="ps-3 text-muted fw-bold"><?= $i + 1; ?></td>
                                        <td>
                                            <span class="fw-semibold"><?= htmlspecialchars($p['nombre']); ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($p['categoria']); ?></small>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format($p['total_vendido'], 2); ?>
                                            <small class="text-muted"><?= $p['unidad_medida']; ?></small>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            S/. <?= number_format($p['total_ingresos'], 2); ?>
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-success">
                                            S/. <?= number_format($p['margen'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Productos con stock bajo -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="section-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2 text-danger"></i>Productos con stock bajo
                </h6>
                <?php if ($count_bajo_stock > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $count_bajo_stock; ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($productos_bajo_stock)): ?>
                    <p class="text-center text-muted py-4">
                        <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                        ¡Todo el stock está bien!
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Producto</th>
                                    <th class="text-center">Stock actual</th>
                                    <th class="text-center pe-3">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_bajo_stock as $p): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-semibold"><?= htmlspecialchars($p['nombre']); ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($p['categoria']); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php $critico = $p['stock'] <= 5; ?>
                                            <span class="badge <?= $critico ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                                <?= number_format($p['stock'], 3); ?> <?= $p['unidad_medida']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <a href="../productos/editar.php?id=<?= $p['id']; ?>"
                                               class="btn btn-sm btn-outline-warning py-0 px-2" title="Reponer stock">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($count_bajo_stock > 0): ?>
            <div class="card-footer bg-white border-top text-center py-2">
                <a href="../productos/index.php" class="btn btn-sm btn-outline-secondary w-100">
                    Ir al inventario <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── Chart.js ──────────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels    = <?= $chart_labels; ?>;
    const montos    = <?= $chart_montos; ?>;
    const cantidades = <?= $chart_cantidades; ?>;

    new Chart(document.getElementById('chartVentas'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ingreso (S/.)',
                    data: montos,
                    backgroundColor: 'rgba(25, 135, 84, 0.75)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    yAxisID: 'yMonto',
                },
                {
                    label: 'Boletas',
                    data: cantidades,
                    type: 'line',
                    borderColor: 'rgba(13, 110, 253, 0.8)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'yCant',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            if (ctx.dataset.yAxisID === 'yMonto')
                                return ` S/. ${ctx.parsed.y.toFixed(2)}`;
                            return ` ${ctx.parsed.y} boleta(s)`;
                        }
                    }
                }
            },
            scales: {
                yMonto: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: (v) => 'S/. ' + v.toFixed(0),
                        font: { size: 11 }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                yCant: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    },
                    grid: { drawOnChartArea: false }
                },
                x: {
                    ticks: { font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
})();
</script>

<?php require_once '../../includes/footer.php'; ?>
