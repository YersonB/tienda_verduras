<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

$mensaje = "";
$tipo_alerta = "";
$id = (int)$_SESSION['usuario_id'];

// Datos actuales del usuario
try {
    $stmt = $pdo->prepare("SELECT nombre, correo, rol FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch();
} catch (\PDOException $e) {
    morir_con_error('perfil.cargar', $e);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $actual  = $_POST['password_actual'] ?? '';
    $nueva   = $_POST['password_nueva'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($actual === '' || $nueva === '' || $confirm === '') {
        $mensaje = "Complete todos los campos."; $tipo_alerta = "danger";
    } elseif (strlen($nueva) < 6) {
        $mensaje = "La nueva contraseña debe tener al menos 6 caracteres."; $tipo_alerta = "danger";
    } elseif ($nueva !== $confirm) {
        $mensaje = "La confirmación no coincide con la nueva contraseña."; $tipo_alerta = "danger";
    } else {
        try {
            // Verificar la contraseña actual antes de permitir el cambio
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $fila = $stmt->fetch();

            if (!$fila || !password_verify($actual, $fila['password'])) {
                $mensaje = "La contraseña actual es incorrecta."; $tipo_alerta = "danger";
            } else {
                $hash = password_hash($nueva, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE usuarios SET password = :p WHERE id = :id");
                $upd->execute([':p' => $hash, ':id' => $id]);
                $mensaje = "¡Contraseña actualizada correctamente!"; $tipo_alerta = "success";
            }
        } catch (\PDOException $e) {
            morir_con_error('perfil.cambiar_password', $e);
        }
    }
}

require_once '../../includes/header.php';
?>

<?php panel_header('Mi Perfil', 'bi-person-circle', 'Tus datos y seguridad'); ?>

<div class="row justify-content-center">
    <div class="col-md-7">

        <!-- Datos del usuario -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-muted">Nombre</div>
                    <div class="col-md-8 fw-semibold"><?= htmlspecialchars($usuario['nombre']); ?></div>
                </div>
                <hr class="my-2">
                <div class="row">
                    <div class="col-md-4 text-muted">Correo</div>
                    <div class="col-md-8 fw-semibold"><?= htmlspecialchars($usuario['correo']); ?></div>
                </div>
                <hr class="my-2">
                <div class="row">
                    <div class="col-md-4 text-muted">Rol</div>
                    <div class="col-md-8">
                        <span class="badge <?= $usuario['rol'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                            <?= htmlspecialchars(ucfirst($usuario['rol'])); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cambio de contraseña -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-key me-2"></i>Cambiar Contraseña</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($mensaje !== ''): ?>
                    <div class="alert alert-<?= $tipo_alerta; ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php">
                    <?= csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contraseña actual <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password_actual" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nueva contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password_nueva" minlength="6" required>
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirmar nueva contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password_confirm" minlength="6" required>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-save me-2"></i>Actualizar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
