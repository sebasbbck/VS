<?php
// Backend: Validar el token de Google y obtener la información del usuario

// URL para validar el token con Google
define("GOOGLE_TOKEN_INFO_URL", "https://oauth2.googleapis.com/tokeninfo?id_token=");

// Obtener el token enviado desde el frontend
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;

if (!$token) {
    echo json_encode(["success" => false, "message" => "Token no proporcionado"]);
    exit;
}

// Validar el token con Google
$verifyUrl = GOOGLE_TOKEN_INFO_URL . $token;
$response = file_get_contents($verifyUrl);
$userInfo = json_decode($response, true);

if (isset($userInfo['error'])) {
    echo json_encode(["success" => false, "message" => "Token inválido o expirado"]);
    exit;
}

// Obtener la fecha y hora actual
$timestamp = date("Y-m-d H:i:s");

// Extraer información del usuario desde el token
$userData = [
    "name" => $userInfo['name'],
    "email" => $userInfo['email'],
    "fecha inicio:" => $timestamp ,// Agregar fecha y hora
    "picture" => $userInfo['picture']
];

// Guardar en el archivo de logs
file_put_contents('users.log', json_encode($userData) . PHP_EOL, FILE_APPEND);

// Responder al frontend con la información del usuario y el timestamp
echo json_encode(["success" => true, "user" => $userData]);
