<?php
// modulos/productos/actualizar_precio.php
// API para buscar productos y actualizar el precio de venta (usado por la página de voz).
header('Content-Type: application/json');
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

$accion = $_GET['accion'] ?? '';

// ── Buscar productos por nombre (GET) ───────────────────────────────────────
if ($accion === 'buscar') {
    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        echo json_encode(['ok' => true, 'productos' => []]);
        exit;
    }
    try {
        $stmt = $pdo->prepare(
            "SELECT id, nombre, precio_venta, unidad_medida
             FROM productos WHERE nombre LIKE :q ORDER BY nombre ASC LIMIT 8"
        );
        $stmt->execute([':q' => '%' . $q . '%']);
        echo json_encode(['ok' => true, 'productos' => $stmt->fetchAll()]);
    } catch (\PDOException $e) {
        morir_con_error_json('precio.buscar', $e);
    }
    exit;
}

// ── Actualizar precio(s) (POST JSON, con CSRF) ──────────────────────────────
csrf_verify_json();

$in = json_decode(file_get_contents('php://input'), true);

// Normalizar a una lista de cambios (acepta uno solo o un lote)
$cambios = [];
if (isset($in['cambios']) && is_array($in['cambios'])) {
    $cambios = $in['cambios'];
} elseif (isset($in['id'])) {
    $cambios = [['id' => $in['id'], 'precio' => $in['precio'] ?? -1]];
}

if (empty($cambios)) {
    echo json_encode(['ok' => false, 'msg' => 'No hay cambios para guardar.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $upd = $pdo->prepare("UPDATE productos SET precio_venta = :p WHERE id = :id");
    $sel = $pdo->prepare("SELECT id, nombre, precio_venta, unidad_medida FROM productos WHERE id = :id");

    $resultados = [];
    foreach ($cambios as $c) {
        $id     = (int)($c['id'] ?? 0);
        $precio = isset($c['precio']) ? (float)$c['precio'] : -1;
        if ($id <= 0 || $precio < 0) {
            continue; // ignora entradas inválidas
        }
        $upd->execute([':p' => round($precio, 2), ':id' => $id]);
        $sel->execute([':id' => $id]);
        if ($p = $sel->fetch()) {
            $resultados[] = $p;
        }
    }
    $pdo->commit();

    echo json_encode(['ok' => true, 'actualizados' => count($resultados), 'productos' => $resultados]);
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    morir_con_error_json('precio.actualizar', $e);
}
