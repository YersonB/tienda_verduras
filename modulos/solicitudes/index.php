<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

$estados = ['nueva','en_proceso','comprando','en_camino','entregada','cancelada'];
$filtro  = $_GET['estado'] ?? '';

$where = "WHERE 1=1";
$params = [];
if (in_array($filtro, $estados, true)) {
    $where .= " AND s.estado = :estado";
    $params[':estado'] = $filtro;
}

try {
    $sql = "SELECT s.id, s.nombre, s.telefono, s.distrito, s.fecha_entrega, s.frecuencia,
                   s.total_estimado, s.estado, s.creado_en,
                   (SELECT COUNT(*) FROM solicitud_canastas sc WHERE sc.solicitud_id = s.id) AS num_canastas
            FROM solicitudes s
            $where
            ORDER BY s.creado_en DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitudes = $stmt->fetchAll();

    // Conteo por estado para las pestañas
    $conteos = ['nueva'=>0];
    $rows = $pdo->query("SELECT estado, COUNT(*) c FROM solicitudes GROUP BY estado")->fetchAll();
    foreach ($rows as $r) { $conteos[$r['estado']] = (int)$r['c']; }
} catch (\PDOException $e) {
    morir_con_error('solicitudes.listar', $e);
}

$badge = [
    'nueva'      => 'bg-primary',
    'en_proceso' => 'bg-info text-dark',
    'comprando'  => 'bg-warning text-dark',
    'en_camino'  => 'bg-warning text-dark',
    'entregada'  => 'bg-success',
    'cancelada'  => 'bg-secondary',
];
$etiqueta_estado = [
    'nueva'=>'Nueva', 'en_proceso'=>'En proceso', 'comprando'=>'Comprando',
    'en_camino'=>'En camino', 'entregada'=>'Entregada', 'cancelada'=>'Cancelada',
];

$status = $_GET['status'] ?? '';
require_once '../../includes/header.php';
?>

<?php if ($status === 'actualizada'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Solicitud actualizada.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php panel_header('Solicitudes de clientes', 'bi-inboxes', 'Pedidos recibidos desde la web'); ?>

<!-- Filtro por estado -->
<ul class="nav nav-pills mb-3 flex-wrap gap-1">
    <li class="nav-item">
        <a class="nav-link <?= $filtro === '' ? 'active' : ''; ?>" href="index.php">Todas</a>
    </li>
    <?php foreach ($estados as $e): ?>
        <li class="nav-item">
            <a class="nav-link <?= $filtro === $e ? 'active' : ''; ?>" href="?estado=<?= $e; ?>">
                <?= $etiqueta_estado[$e]; ?>
                <?php if (!empty($conteos[$e])): ?>
                    <span class="badge rounded-pill bg-light text-dark ms-1"><?= $conteos[$e]; ?></span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Entrega</th>
                        <th class="text-center">Canastas</th>
                        <th class="text-end">Estimado</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Ver</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudes)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>No hay solicitudes en este estado.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes as $s): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted">#<?= str_pad($s['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <span class="fw-semibold"><?= htmlspecialchars($s['nombre']); ?></span>
                                    <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($s['creado_en'])); ?></small>
                                </td>
                                <td>
                                    <i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($s['telefono']); ?>
                                    <?php if ($s['distrito']): ?><br><small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['distrito']); ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <?= $s['fecha_entrega'] ? date('d/m/Y', strtotime($s['fecha_entrega'])) : '<span class="text-muted">—</span>'; ?>
                                    <br><small class="text-muted text-capitalize"><?= htmlspecialchars($s['frecuencia']); ?></small>
                                </td>
                                <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $s['num_canastas']; ?></span></td>
                                <td class="text-end fw-bold text-success">S/. <?= number_format($s['total_estimado'], 2); ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $badge[$s['estado']] ?? 'bg-secondary'; ?>"><?= $etiqueta_estado[$s['estado']] ?? $s['estado']; ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="detalle.php?id=<?= $s['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
