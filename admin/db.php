<?php

$host = 'localhost';
$dbname = 'pagifier';
$user = 'root';
$pass = ''; // En XAMPP la contraseña por defecto es vacía

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("<h1 style='color:red;font-family:Arial'>Error de conexión a la base de datos</h1>"
        . "<p style='font-family:Arial'>" . $e->getMessage() . "</p>"
        . "<p style='font-family:Arial'>Asegúrate de que MySQL está iniciado en XAMPP y que la base de datos 'pagifier' existe.</p>");
}
