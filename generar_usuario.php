<?php
// generar_usuario.php
require_once 'config/conexion.php';

// La contraseña que queremos usar en texto plano
$password_plana = "admin123";

// Encriptar la contraseña usando el algoritmo de tu servidor PHP
$password_encriptada = password_hash($password_plana, PASSWORD_BCRYPT);

try {
    // 1. Primero verificamos si el usuario ya existe
    $checkSql = "SELECT id FROM usuarios WHERE correo = 'admin@tienda.com' LIMIT 1";
    $stmtCheck = $pdo->query($checkSql);
    $usuarioExiste = $stmtCheck->fetch();

    if ($usuarioExiste) {
        // 2. Si ya existe, SOLO actualizamos su contraseña encriptada (Evita el error de la llave foránea)
        $sql = "UPDATE usuarios SET password = :password WHERE correo = 'admin@tienda.com'";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([':password' => $password_encriptada]);
        $accion = "actualizado (se actualizó la contraseña de forma segura)";
    } else {
        // 3. Si por alguna razón no existía, lo insertamos normalmente
        $sql = "INSERT INTO usuarios (nombre, correo, password, rol) VALUES (:nombre, :correo, :password, :rol)";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            ':nombre'   => 'Administrador',
            ':correo'   => 'admin@tienda.com',
            ':password' => $password_encriptada,
            ':rol'      => 'admin'
        ]);
        $accion = "creado desde cero";
    }

    if ($resultado) {
        echo "<h3>¡Usuario {$accion} con éxito en tu base de datos!</h3>";
        echo "<b>Correo:</b> admin@tienda.com<br>";
        echo "<b>Contraseña válida:</b> admin123<br><br>";
        echo "<i>Ya puedes regresar al login (index.php) e intentar ingresar.</i>";
    }
} catch (\PDOException $e) {
    echo "Error al procesar el usuario: " . $e->getMessage();
}
?>