<?php
// modulos/productos/eliminar.php

// 1. Incluir la conexión a la base de datos
require_once '../../includes/seguridad.php'; 
require_once '../../config/conexion.php';

// 2. Verificar que se haya enviado un ID válido por la URL
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $id = trim($_GET['id']);

    try {
        // 3. Preparar la consulta de eliminación
        $sql = "DELETE FROM productos WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        // 4. Ejecutar la consulta
        $resultado = $stmt->execute([':id' => $id]);

        if ($resultado) {
            // Redireccionar al inventario indicando éxito (puedes atrapar esto luego si deseas)
            header("Location: index.php?status=deleted");
            exit;
        }
    } catch (\PDOException $e) {
        // Manejo de errores de integridad referencial (llaves foráneas)
        // Si el producto ya se vendió, MySQL arrojará el código de error 23000
        if ($e->getCode() == '23000') {
            echo "<script>
                    alert('No se puede eliminar este producto porque ya tiene ventas registradas en el historial.');
                    window.location.href = 'index.php';
                  </script>";
            exit;
        } else {
            die("Error al intentar eliminar el producto: " . $e->getMessage());
        }
    }
} else {
    // Si no hay ID, regresar al inventario
    header("Location: index.php");
    exit;
}
?>