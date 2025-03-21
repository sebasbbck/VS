<?php

require __DIR__ . '/../vendor/autoload.php'; // Carga el autoload de Composer

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Credenciales de la base de datos
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_DATABASE'] ?? 'prueba';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? 'abc';

// Claves de autenticación
$jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_default_secret_key';
$clientId = $_ENV['OAUTH_ID'] ?? null;
$secretClient = $_ENV['SECRET_CLIENT'] ?? null;
$redirectUri = $_ENV['REDIRECT_URI'] ?? null;

if (!$clientId || !$secretClient || !$redirectUri) {
    error_log("Error: OAUTH_ID o SECRET_CLIENT o REDIRECT_URI no están configurados en el archivo .env");
}

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die(json_encode(["error" => "No se pudo conectar a la base de datos."]));
}

?>
