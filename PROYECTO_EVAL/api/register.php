<?php
include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = $data['nombre'];
$email = $data['email'];
$apellidos = $data['apellidos'];
$password = password_hash($data['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO users (nombre, apellidos, email, password) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
try {
    $stmt->execute([$name, $apellidos, $email, $password]);
    echo json_encode(["message" => "Usuario registrado con éxito"]);
} catch (Exception $e) {
    echo json_encode(["error" => "Error al registrar el usuario: " . $e->getMessage()]);
}
?>
