<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}
csrf_verify();

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_POST['id'];

// Un usuario no puede eliminarse a sí mismo
if ($id === (int)$_SESSION['usuario_id']) {
    header("Location: index.php?status=error");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: index.php?status=eliminado");
    exit;
} catch (\PDOException $e) {
    // Si el usuario tiene ventas asociadas (ON DELETE RESTRICT), avisar
    if ($e->getCode() === '23000') {
        header("Location: index.php?status=error_fk");
    } else {
        morir_con_error('usuarios.eliminar', $e);
    }
    exit;
}
