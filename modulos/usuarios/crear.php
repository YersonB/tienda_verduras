<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
requerir_rol('admin');

$mensaje = "";
$tipo_alerta = "";
$nombre = $correo = $rol = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol      = $_POST['rol'] ?? '';

    if ($nombre === '' || $correo === '' || $password === '' || $rol === '') {
        $mensaje = "Complete todos los campos."; $tipo_alerta = "danger";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo no es válido."; $tipo_alerta = "danger";
    } elseif (strlen($password) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres."; $tipo_alerta = "danger";
    } elseif (!in_array($rol, ['admin', 'vendedor'], true)) {
        $mensaje = "Rol inválido."; $tipo_alerta = "danger";
    } else {
        try {
            // Verificar que el correo no esté ya registrado
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo LIMIT 1");
            $check->execute([':correo' => $correo]);
            if ($check->fetch()) {
                $mensaje = "Ya existe un usuario con ese correo."; $tipo_alerta = "warning";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    "INSERT INTO usuarios (nombre, correo, password, rol) VALUES (:nombre, :correo, :password, :rol)"
                );
                $stmt->execute([':nombre' => $nombre, ':correo' => $correo, ':password' => $hash, ':rol' => $rol]);
                header("Location: index.php?status=creado");
                exit;
            }
        } catch (\PDOException $e) {
            morir_con_error('usuarios.crear', $e);
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="mb-3">
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver a Usuarios
            </a>
        </div>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-<?= $tipo_alerta; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white py-3">
                <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2"></i>Nuevo Usuario</h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="crear.php">
                    <?= csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($nombre); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="correo" value="<?= htmlspecialchars($correo); ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" minlength="6" required>
                            <div class="form-text">Mínimo 6 caracteres.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" name="rol" required>
                                <option value="" disabled <?= $rol === '' ? 'selected' : ''; ?>>Seleccione...</option>
                                <option value="vendedor" <?= $rol === 'vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                <option value="admin" <?= $rol === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-light border me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-save me-2"></i>Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
