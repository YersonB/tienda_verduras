<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php"); exit; }
csrf_verify();

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) { header("Location: index.php"); exit; }
$id = (int)$_POST['id'];

try {
    // Si la canasta fue pedida en alguna solicitud, la FK la pone en NULL (ON DELETE SET NULL)
    $pdo->prepare("DELETE FROM canastas WHERE id = :id")->execute([':id' => $id]);
    header("Location: index.php?status=eliminada");
    exit;
} catch (\PDOException $e) {
    morir_con_error('canastas.eliminar', $e);
}
