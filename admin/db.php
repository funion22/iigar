<?php
try {
    $pdo = new PDO(
        "mysql:host=" . $_SERVER['DB_HOST'] . ";dbname=" . $_SERVER['DB_DATABASE'] . ";charset=utf8mb4",
        $_SERVER['DB_USERNAME'],
        $_SERVER['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("<h1 style='color:red;font-family:Arial'>Error de conexión a la base de datos</h1>"
        . "<p style='font-family:Arial'>" . $e->getMessage() . "</p>");
}
