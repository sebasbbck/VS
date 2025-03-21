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
    $user_id = $decoded->userId ?? null; // Extraer el user_id del token
} catch (Exception $e) {
    echo json_encode(["error" => "Token inválido o expirado: " . $e->getMessage()]);
    http_response_code(401);
    exit;
}

// Obtener ID de incidencia si se proporciona en la solicitud
$id_incidencia = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    // Si el usuario tiene userId, obtener su información
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT email, nombre FROM users WHERE id_user = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["error" => "Usuario no encontrado."]);
            http_response_code(404);
            exit;
        }
    } else {
        // Si no tiene userId, asumir que es un usuario de Google y usar un email genérico
        $user = [
            'email' => 'google_user@educa.madrid.org',
            'nombre' => 'Usuario de Google'
        ];
    }

    // Obtener aulas
    $stmt = $pdo->prepare("
    SELECT a.id_aula, a.nombre, 
        CASE WHEN ap.id_aula IS NOT NULL THEN 1 ELSE 0 END AS is_selected
    FROM aulas a
    LEFT JOIN aulas_profes ap ON a.id_aula = ap.id_aula AND ap.id_user = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener puestos
    $stmt = $pdo->query("SELECT id_puesto, nombre FROM puestos ORDER BY id_puesto ASC");
    $puestos = $stmt->fetchAll();

    // Obtener tipos de incidencias
    $stmt = $pdo->query("SELECT id_tipo, tipo FROM tipos_incidencias ORDER BY id_tipo ASC");
    $tipos = $stmt->fetchAll();

    // Obtener datos de la incidencia si se envió un ID válido
    $incidencia = null;
    if ($id_incidencia) {
        $stmt = $pdo->prepare("SELECT id_incidencia, id_aula, usuario, id_tipo, id_puesto, descripcion, estado, solucion 
                               FROM incidencias 
                               WHERE id_incidencia = ?");
        $stmt->execute([$id_incidencia]);
        $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$incidencia) {
            echo json_encode(["error" => "Incidencia no encontrada"]);
            http_response_code(404);
            exit;
        }
    }

    // Devolver todos los datos en un solo JSON
    echo json_encode([
        "aulas" => $aulas,
        "puestos" => $puestos,
        "tipos_incidencias" => $tipos,
        "user_id" => $user_id,
        "email" => $user['email'],
        "nombre" => $user['nombre'],
        "incidencia" => $incidencia // Agregar la incidencia si existe
    ]);
    
} catch (PDOException $e) {
    echo json_encode(["error" => "Error al obtener datos: " . $e->getMessage()]);
    http_response_code(500);
}
?>