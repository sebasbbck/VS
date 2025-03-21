<?php
include 'config.php'; // Conexión a la base de datos

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
    // Obtener todas las incidencias
    $stmt = $pdo->query("
        SELECT 
            i.id_incidencia, 
            i.id_aula, 
            a.nombre AS nombre_aula,
            u.email AS usuario_email,  
            i.id_tipo, 
            t.tipo AS nombre_tipo,
            i.id_puesto, 
            p.nombre AS nombre_puesto,
            i.descripcion, 
            i.estado, 
            i.fecha_creacion, 
            i.fecha_cierre, 
            i.solucion
        FROM incidencias i
        JOIN aulas a ON i.id_aula = a.id_aula
        JOIN users u ON i.id_user = u.id_user
        JOIN tipos_incidencias t ON i.id_tipo = t.id_tipo
        JOIN puestos p ON i.id_puesto = p.id_puesto
    ");
    $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($incidencias);
} catch (PDOException $e) {
    echo json_encode(["error" => "Error al obtener incidencias: " . $e->getMessage()]);
    http_response_code(500);
}
?>