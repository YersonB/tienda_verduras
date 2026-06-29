<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

// Activar/desactivar rápido (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle'])) {
    csrf_verify();
    $id = (int)($_POST['toggle'] ?? 0);
    try {
        $pdo->prepare("UPDATE canastas SET activo = 1 - activo WHERE id = :id")->execute([':id' => $id]);
    } catch (\PDOException $e) { morir_con_error('canastas.toggle', $e); }
    header("Location: index.php?status=ok");
    exit;
}

try {
    $canastas = $pdo->query("SELECT * FROM canastas ORDER BY activo DESC, precio ASC")->fetchAll();
} catch (\PDOException $e) {
    morir_con_error('canastas.listar', $e);
}

$status = $_GET['status'] ?? '';
require_once '../../includes/header.php';
?>

<?php if ($status === 'creada' || $status === 'editada' || $status === 'eliminada' || $status === 'ok'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Canastas actualizadas correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php panel_header('Canastas', 'bi-basket2-fill', 'Administra los combos que ofreces en la web',
    '<a href="crear.php" class="btn fw-semibold" style="background:#fde047;color:#713f12;border:none;"><i class="bi bi-plus-circle me-1"></i>Nueva Canasta</a>'
); ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Nombre</th>
                        <th>Contenido</th>
                        <th class="text-center">Etiqueta</th>
                        <th class="text-end">Precio</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($canastas)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-basket fs-3 d-block mb-2"></i>Aún no hay canastas. ¡Crea la primera!
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($canastas as $c): ?>
                            <tr class="<?= $c['activo'] ? '' : 'opacity-50'; ?>">
                                <td class="ps-3 fw-semibold"><?= htmlspecialchars($c['nombre']); ?></td>
                                <td class="text-muted small" style="max-width:320px;"><?= htmlspecialchars($c['contenido'] ?: $c['descripcion']); ?></td>
                                <td class="text-center">
                                    <?php if ($c['etiqueta']): ?><span class="badge bg-warning text-dark"><?= htmlspecialchars($c['etiqueta']); ?></span><?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-success">S/. <?= number_format($c['precio'], 2); ?></td>
                                <td class="text-center">
                                    <?php if ($c['activo']): ?>
                                        <span class="badge bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Oculta</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="editar.php?id=<?= $c['id']; ?>" class="btn btn-sm btn-outline-warning" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        <form method="POST" action="index.php" class="d-inline">
                                            <input type="hidden" name="toggle" value="<?= $c['id']; ?>">
                                            <?= csrf_field(); ?>
                                            <button class="btn btn-sm btn-outline-secondary" title="<?= $c['activo'] ? 'Ocultar' : 'Activar'; ?>">
                                                <i class="bi bi-<?= $c['activo'] ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="eliminar.php" class="d-inline"
                                              data-confirm="¿Eliminar la canasta «<?= htmlspecialchars($c['nombre'], ENT_QUOTES); ?>»?" data-confirm-btn="Sí, eliminar">
                                            <input type="hidden" name="id" value="<?= $c['id']; ?>">
                                            <?= csrf_field(); ?>
                                            <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
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
