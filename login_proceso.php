<?php
// login_proceso.php
session_start();
require_once 'config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);

    if (empty($correo) || empty($password)) {
        header("Location: index.php?error=1");
        exit;
    }

    try {
        // Buscar al usuario por correo electrónico
        $sql = "SELECT id, nombre, password, rol FROM usuarios WHERE correo = :correo LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        // Verificar si el usuario existe y comprobar la contraseña encriptada
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Generar variables de sesión seguras
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            // Redirigir al inventario principal
            header("Location: modulos/productos/index.php");
            exit;
        } else {
            // Credenciales incorrectas
            header("Location: index.php?error=1");
            exit;
        }
    } catch (\PDOException $e) {
        die("Error en el sistema: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}