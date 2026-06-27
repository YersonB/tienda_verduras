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

<!-- ── Saludo ────────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-0">
            <i class="bi bi-speedometer2 me-2 text-success"></i>Panel Principal
        </h2>
        <small class="text-muted">
            <?= ucfirst(strftime('%A %d de %B de %Y') ?: date('d/m/Y')); ?>
            &nbsp;·&nbsp;
            Bienvenido, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>
        </small>
    </div>
    <a href="../ventas/nueva_venta.php" class="btn btn-success">
        <i class="bi bi-cart-plus me-1"></i>Nueva Venta
    </a>
</div>

<!-- ── Tarjetas de resumen ───────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-success bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-receipt fs-3 text-success"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.78rem;">Ventas hoy</div>
                    <div class="fs-2 fw-bold lh-1"><?= $resumen_hoy['ventas_hoy']; ?></div>
                    <div class="text-muted" style="font-size:.72rem;">boleta(s)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-primary bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-cash-coin fs-3 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.78rem;">Ingresos hoy</div>
                    <div class="fs-4 fw-bold lh-1 text-primary">S/. <?= number_format($resumen_hoy['ingresos_hoy'], 2); ?></div>
                    <div class="text-muted" style="font-size:.72rem;">recaudados</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-info bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-box-seam fs-3 text-info"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.78rem;">Productos</div>
                    <div class="fs-2 fw-bold lh-1"><?= $total_productos; ?></div>
                    <div class="text-muted" style="font-size:.72rem;">en inventario</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 <?= $count_bajo_stock > 0 ? 'border-warning border-2' : ''; ?>">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 <?= $count_bajo_stock > 0 ? 'bg-warning bg-opacity-20' : 'bg-secondary bg-opacity-10'; ?> flex-shrink-0">
                    <i class="bi bi-exclamation-triangle fs-3 <?= $count_bajo_stock > 0 ? 'text-warning' : 'text-secondary'; ?>"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.78rem;">Stock bajo</div>
                    <div class="fs-2 fw-bold lh-1 <?= $count_bajo_stock > 0 ? 'text-warning' : ''; ?>"><?= $count_bajo_stock; ?></div>
                    <div class="text-muted" style="font-size:.72rem;">producto(s)</div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ── Margen de ganancia del mes ────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-md-3">
                <h6 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Margen del mes</h6>
                <small class="text-muted"><?= ucfirst(strftime('%B %Y') ?: date('m/Y')); ?></small>
            </div>
            <div class="col-md-3 text-center border-start">
                <div class="text-muted small">Ingresos</div>
                <div class="fs-5 fw-bold text-primary">S/. <?= number_format($margen_mes['ingresos'], 2); ?></div>
            </div>
            <div class="col-md-3 text-center border-start">
                <div class="text-muted small">Costo de mercadería</div>
                <div class="fs-5 fw-bold text-secondary">S/. <?= number_format($margen_mes['costos'], 2); ?></div>
            </div>
            <div class="col-md-3 text-center border-start">
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
                <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2 text-success"></i>Ventas — últimos 7 días</h6>
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
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Últimas ventas</h6>
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
                <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Top 5 productos más vendidos</h6>
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
                <h6 class="fw-bold mb-0">
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
