<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
requerir_rol('admin');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

$mensaje = "";
$tipo_alerta = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';   // opcional: solo si se cambia
    $rol      = $_POST['rol'] ?? '';

    if ($nombre === '' || $correo === '' || $rol === '') {
        $mensaje = "Complete los campos obligatorios."; $tipo_alerta = "danger";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo no es válido."; $tipo_alerta = "danger";
    } elseif ($password !== '' && strlen($password) < 6) {
        $mensaje = "La nueva contraseña debe tener al menos 6 caracteres."; $tipo_alerta = "danger";
    } elseif (!in_array($rol, ['admin', 'vendedor'], true)) {
        $mensaje = "Rol inválido."; $tipo_alerta = "danger";
    } else {
        try {
            // Evitar correos duplicados en OTRO usuario
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo AND id <> :id LIMIT 1");
            $check->execute([':correo' => $correo, ':id' => $id]);
            if ($check->fetch()) {
                $mensaje = "Ese correo ya pertenece a otro usuario."; $tipo_alerta = "warning";
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare(
                        "UPDATE usuarios SET nombre=:nombre, correo=:correo, password=:password, rol=:rol WHERE id=:id"
                    );
                    $stmt->execute([':nombre'=>$nombre, ':correo'=>$correo, ':password'=>$hash, ':rol'=>$rol, ':id'=>$id]);
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE usuarios SET nombre=:nombre, correo=:correo, rol=:rol WHERE id=:id"
                    );
                    $stmt->execute([':nombre'=>$nombre, ':correo'=>$correo, ':rol'=>$rol, ':id'=>$id]);
                }

                // Si el admin editó su propio nombre/rol, refrescar la sesión
                if ($id == $_SESSION['usuario_id']) {
                    $_SESSION['usuario_nombre'] = $nombre;
                    $_SESSION['usuario_rol']    = $rol;
                }
                header("Location: index.php?status=editado");
                exit;
            }
        } catch (\PDOException $e) {
            morir_con_error('usuarios.editar', $e);
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch();
    if (!$usuario) {
        header("Location: index.php");
        exit;
    }
} catch (\PDOException $e) {
    morir_con_error('usuarios.editar.cargar', $e);
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
            <div class="card-header bg-warning text-dark py-3">
                <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar: <?= htmlspecialchars($usuario['nombre']); ?></h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="editar.php?id=<?= $id; ?>">
                    <?= csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="correo" value="<?= htmlspecialchars($usuario['correo']); ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nueva contraseña</label>
                            <input type="password" class="form-control" name="password" minlength="6" placeholder="Dejar vacío para no cambiar">
                            <div class="form-text">Solo escríbela si deseas cambiarla.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" name="rol" required>
                                <option value="vendedor" <?= $usuario['rol'] === 'vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-light border me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-warning px-4 text-dark fw-bold">
                            <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
