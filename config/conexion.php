<?php
// config/conexion.php

// 1. Definición de las credenciales de la base de datos
$host    = "localhost";
$db      = "tienda_verduras";
$user    = "root";
$pass    = ""; // Por defecto en XAMPP la contraseña de root está vacía
$charset = "utf8mb4"; // Permite ñ, acentos y caracteres especiales

// 2. Definición del DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// 3. Opciones de configuración para PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones si hay errores de SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve los datos como arreglos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactiva la emulación para mayor seguridad real
];

try {
    // 4. Intento de conexión
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // NOTA: Para producción debes borrar o comentar la siguiente línea.
    // Solo la usaremos ahora para verificar que funcione.
    // echo "Conexión exitosa a la base de datos."; 

} catch (\PDOException $e) {
    // 5. Captura de errores en caso falle la conexión
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
?>