<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header("Location: index.php"); exit; }
$id = (int)$_GET['id'];
$mensaje = ""; $tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $contenido   = trim($_POST['contenido'] ?? '');
    $etiqueta    = trim($_POST['etiqueta'] ?? '');
    $precio      = trim($_POST['precio'] ?? '');
    $activo      = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '' || $precio === '' || !is_numeric($precio)) {
        $mensaje = "Completa el nombre y un precio válido."; $tipo = "danger";
    } else {
        try {
            $stmt = $pdo->prepare(
                "UPDATE canastas SET nombre=:n, descripcion=:d, contenido=:c, precio=:p, etiqueta=:e, activo=:a WHERE id=:id"
            );
            $stmt->execute([
                ':n'=>$nombre, ':d'=>$descripcion !== '' ? $descripcion : null,
                ':c'=>$contenido !== '' ? $contenido : null, ':p'=>(float)$precio,
                ':e'=>$etiqueta !== '' ? $etiqueta : null, ':a'=>$activo, ':id'=>$id,
            ]);
            header("Location: index.php?status=editada");
            exit;
        } catch (\PDOException $e) { morir_con_error('canastas.editar', $e); }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM canastas WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $c = $stmt->fetch();
    if (!$c) { header("Location: index.php"); exit; }
} catch (\PDOException $e) { morir_con_error('canastas.editar.cargar', $e); }

require_once '../../includes/header.php';
?>

<?php panel_header('Editar Canasta', 'bi-basket2-fill', htmlspecialchars($c['nombre']),
    '<a href="index.php" class="btn btn-light fw-semibold"><i class="bi bi-arrow-left me-1"></i>Volver</a>'
); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if ($mensaje): ?><div class="alert alert-<?= $tipo; ?>"><?= htmlspecialchars($mensaje); ?></div><?php endif; ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="editar.php?id=<?= $id; ?>">
                    <?= csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($c['nombre']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Precio (S/.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="precio" class="form-control" value="<?= htmlspecialchars($c['precio']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Etiqueta</label>
                            <input type="text" name="etiqueta" class="form-control" value="<?= htmlspecialchars($c['etiqueta'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Descripción corta</label>
                            <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($c['descripcion'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Contenido</label>
                            <textarea name="contenido" class="form-control" rows="3"><?= htmlspecialchars($c['contenido'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" <?= $c['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">Visible en la web</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="index.php" class="btn btn-light border me-md-2">Cancelar</a>
                        <button class="btn btn-warning px-4 text-dark fw-bold"><i class="bi bi-arrow-clockwise me-2"></i>Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
