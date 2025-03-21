<?php

include 'config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Google\Client;

// Configuración de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errors.log');

// Leer datos de entrada JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    die(json_encode(["error" => "Datos de entrada no válidos."]));
}

// 🔹 Autenticación con Google
$id_token = $data['token'] ?? null; 
if ($id_token) {
    try {
        $client = new Client();
        $client->setClientId($clientId); // Asegúrate de que $clientId esté definido

        // Verificar el token de Google
        $payload = $client->verifyIdToken($id_token);
        if (!$payload) {
            error_log("Error: Token de Google no verificado correctamente.");
            echo json_encode(["error" => "Token de Google inválido."]);
            exit();
        }

        // Extraer información del usuario
        $userEmail = $payload['email'] ?? null;
        $userName = $payload['name'] ?? 'Usuario';
        $userPicture = $payload['picture'] ?? '';
        $timestamp = date('Y-m-d H:i:s');

        if (!$userEmail) {
            error_log("Error: No se pudo obtener el email del usuario.");
            echo json_encode(["error" => "No se pudo obtener el email del usuario."]);
            exit();
        }
        // Guardar la información del usuario en el archivo users.log
        $log_data = [
            'name' => $userName,
            'email' => $userEmail,
            'fecha inicio:' => $timestamp,
            'picture' => $userPicture
        ];
        file_put_contents(__DIR__ . '/users.log', json_encode($log_data) . PHP_EOL, FILE_APPEND);

        // Generar token JWT para autenticación
        $jwt = JWT::encode([
            'iss' => 'tu-proyecto.com',
            'aud' => 'tu-proyecto.com',
            'iat' => time(),
            'exp' => time() + 14400,
            'email' => $userEmail,
            'name' => $userName
        ], $jwtSecret, 'HS256');

        // ✅ Asegurar que se envía un mensaje de éxito
        echo json_encode([
            'success' => true,
            'message' => "Inicio de sesión exitoso con Google",
            'token' => $jwt,
            'name' => $userName,
            'email' => $userEmail,
            'picture' => $userPicture
        ]);
    } catch (Exception $e) {
        error_log("Error en autenticación con Google: " . $e->getMessage());
        echo json_encode(["error" => "Error en la autenticación con Google."]);
    }
    exit;
}

// 🔹 Login con usuario y contraseña manual
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;
if (!$email || !$password) {
    die(json_encode(["error" => "Email o contraseña no proporcionados."]));
}

try {
    // Validar el formato del email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die(json_encode(["error" => "Formato de email no válido."]));
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $jwt = JWT::encode([
            'iss' => 'tu-proyecto.com',
            'aud' => 'tu-proyecto.com',
            'iat' => time(),
            'exp' => time() + 14400,
            'userId' => $user['id_user'],
        ], $jwtSecret, 'HS256');

        echo json_encode([
            'success' => true,
            'message' => "Inicio de sesión exitoso",
            'token' => $jwt,
            'userId' => $user['id_user'],
            'name' => $user['nombre'],
            'email' => $user['email']
        ]);
    } else {
        echo json_encode(["error" => "Credenciales incorrectas."]);
    }
} catch (PDOException $e) {
    error_log("Error en la consulta SQL: " . $e->getMessage());
    echo json_encode(["error" => "Hubo un problema con la base de datos."]);
}

?>