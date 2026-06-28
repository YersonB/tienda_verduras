<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
requerir_rol('admin');

try {
    $usuarios = $pdo->query(
        "SELECT id, nombre, correo, rol FROM usuarios ORDER BY nombre ASC"
    )->fetchAll();
} catch (\PDOException $e) {
    morir_con_error('usuarios.listar', $e);
}

$status = $_GET['status'] ?? '';
require_once '../../includes/header.php';
?>

<?php if ($status === 'creado'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Usuario creado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($status === 'editado'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Usuario actualizado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($status === 'eliminado'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Usuario eliminado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($status === 'error_fk'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>No se puede eliminar: el usuario tiene ventas registradas. Considera cambiar su rol en lugar de eliminarlo.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($status === 'error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i>No se pudo completar la operación.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php panel_header('Gestión de Usuarios', 'bi-people-fill', 'Administra el personal del sistema',
    '<a href="crear.php" class="btn fw-semibold" style="background:#fde047;color:#713f12;border:none;"><i class="bi bi-person-plus me-1"></i>Nuevo Usuario</a>'
); ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th class="text-center">Rol</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="ps-3 text-muted fw-bold"><?= $u['id']; ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($u['nombre']); ?></td>
                            <td class="text-muted"><?= htmlspecialchars($u['correo']); ?></td>
                            <td class="text-center">
                                <?php if ($u['rol'] === 'admin'): ?>
                                    <span class="badge bg-danger"><i class="bi bi-shield-lock me-1"></i>Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Vendedor</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="editar.php?id=<?= $u['id']; ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                        <form method="POST" action="eliminar.php" class="d-inline"
                                              data-confirm="¿Eliminar al usuario «<?= htmlspecialchars($u['nombre'], ENT_QUOTES); ?>»?"
                                              data-confirm-btn="Sí, eliminar">
                                            <input type="hidden" name="id" value="<?= $u['id']; ?>">
                                            <?= csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="No puedes eliminarte a ti mismo">
                                            <i class="bi bi-person-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
