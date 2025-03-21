<?php
include 'config.php'; // Conexión a la base de datos
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

// Obtener el token desde el encabezado de autorización
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$jwt = str_replace('Bearer ', '', $authHeader); // Eliminar el prefijo "Bearer"

if (!$jwt) {
    echo json_encode(["error" => "Token no proporcionado"]);
    http_response_code(401);
    exit;
}

try {
    // Decodificar el token JWT
    $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
    $user_id = $decoded->userId; // Extraer el user_id del token
} catch (Exception $e) {
    echo json_encode(["error" => "Token inválido o expirado: " . $e->getMessage()]);
    http_response_code(401);
    exit;
}

// Obtener el cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);
$aulasIds = $data['aulasIds'] ?? []; // Obtener los IDs de las aulas del cuerpo de la solicitud

if (empty($aulasIds)) {
    echo json_encode(["error" => "No se proporcionaron IDs de aulas"]);
    http_response_code(400);
    exit;
}

try {
    // Eliminar las relaciones existentes en aulas_profes para el usuario
    $deleteStmt = $pdo->prepare("DELETE FROM aulas_profes WHERE id_user = :user_id");
    $deleteStmt->execute(['user_id' => $user_id]);

    // Insertar en la tabla aulas_profes
    $insertStmt = $pdo->prepare("INSERT INTO aulas_profes (id_user, id_aula) VALUES (:user_id, :aula_id)");

    foreach ($aulasIds as $aulaId) {
        $insertStmt->execute(['user_id' => $user_id, 'aula_id' => $aulaId]);
    }

    echo json_encode(["message" => "Aulas guardadas exitosamente."]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Error al guardar las aulas: " . $e->getMessage()]);
    http_response_code(500);
}
?>