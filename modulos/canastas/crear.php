<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

$mensaje = ""; $tipo = "";
$nombre = $descripcion = $contenido = $etiqueta = ""; $precio = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $contenido   = trim($_POST['contenido'] ?? '');
    $etiqueta    = trim($_POST['etiqueta'] ?? '');
    $precio      = trim($_POST['precio'] ?? '');

    if ($nombre === '' || $precio === '' || !is_numeric($precio)) {
        $mensaje = "Completa al menos el nombre y un precio válido."; $tipo = "danger";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO canastas (nombre, descripcion, contenido, precio, etiqueta, activo)
                 VALUES (:n, :d, :c, :p, :e, 1)"
            );
            $stmt->execute([
                ':n' => $nombre, ':d' => $descripcion !== '' ? $descripcion : null,
                ':c' => $contenido !== '' ? $contenido : null, ':p' => (float)$precio,
                ':e' => $etiqueta !== '' ? $etiqueta : null,
            ]);
            header("Location: index.php?status=creada");
            exit;
        } catch (\PDOException $e) { morir_con_error('canastas.crear', $e); }
    }
}

require_once '../../includes/header.php';
?>

<?php panel_header('Nueva Canasta', 'bi-basket2-fill', 'Crea un combo para la web',
    '<a href="index.php" class="btn btn-light fw-semibold"><i class="bi bi-arrow-left me-1"></i>Volver</a>'
); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo; ?>"><?= htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="crear.php">
                    <?= csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($nombre); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Precio (S/.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="precio" class="form-control" value="<?= htmlspecialchars($precio); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Etiqueta</label>
                            <input type="text" name="etiqueta" class="form-control" placeholder="Ej. Familiar, Parrilla" value="<?= htmlspecialchars($etiqueta); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Descripción corta</label>
                            <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($descripcion); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Contenido (qué incluye)</label>
                            <textarea name="contenido" class="form-control" rows="3"><?= htmlspecialchars($contenido); ?></textarea>
                        </div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="index.php" class="btn btn-light border me-md-2">Cancelar</a>
                        <button class="btn btn-success px-4"><i class="bi bi-save me-2"></i>Crear Canasta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
