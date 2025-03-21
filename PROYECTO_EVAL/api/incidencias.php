<?php
include 'config.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/SMTP.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Obtener el token desde el encabezado de autorización
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$jwt = str_replace('Bearer ', '', $authHeader); // Eliminar el prefijo "Bearer"

// Validar si hay token
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

function enviarCorreoIncidencia($emails, $aula, $puesto, $descripcion, $fecha_creacion, $email_creador)
{
    // Crear instancia de PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP (NO MODIFICAR)
        $mail->isSMTP();
        $mail->Host       = 'smtp01.educa.madrid.org';
        $mail->Username   = 'sebastian.coello@educa.madrid.org'; // Tu correo
        $mail->Password   = '95AA B0B3 7B67 3E69 EA51 CC3A 907A F7C5 5075 B03A'; // Contraseña o clave de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587; 
        
        // Configuración del correo
        $mail->setFrom('sebastian.coello@educa.madrid.org', 'Admin');

        // Añadir todos los destinatarios
        foreach ($emails as $email) {
            $mail->addAddress($email); // Destinatario (usuarios asignados a la clase)
        }

        $mail->Subject = 'Nueva Incidencia Creada'; // Asunto del correo

        // Cuerpo del correo con los detalles de la incidencia
        $mail->Body = "
            <h1>Nueva Incidencia Creada</h1>
            <p>Se ha creado una nueva incidencia con los siguientes detalles:</p>
            <ul>
                <li><strong>Aula:</strong> $aula</li>
                <li><strong>Puesto:</strong> $puesto</li>
                <li><strong>Descripcion:</strong> $descripcion</li>
                <li><strong>Fecha y Hora:</strong> $fecha_creacion</li>
                <li><strong>Creada por:</strong> $email_creador</li>
            </ul>
            <p>Por favor, revisa la incidencia en el sistema.</p>
        ";
        $mail->isHTML(true); // Habilitar formato HTML

        // Enviar correo
        $mail->send();
        error_log("Correo enviado correctamente a los usuarios asignados a la clase.");
    } catch (Exception $e) {
        error_log("No se pudo enviar el correo. Error: " . $mail->ErrorInfo);
    }
}

// Obtener todas las incidencias o filtrarlas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Inicializar la consulta base
    $sql = "SELECT 
                incidencias.id_incidencia,
                incidencias.id_aula,
                incidencias.id_tipo, 
                aulas.nombre AS nombre_aula, 
                tipos_incidencias.tipo AS nombre_tipo, 
                puestos.nombre AS nombre_puesto, 
                incidencias.descripcion, 
                incidencias.estado, 
                incidencias.fecha_creacion, 
                incidencias.fecha_cierre, 
                incidencias.solucion,
                users.email AS usuario_email 
            FROM incidencias 
            JOIN aulas ON incidencias.id_aula = aulas.id_aula 
            JOIN tipos_incidencias ON incidencias.id_tipo = tipos_incidencias.id_tipo 
            JOIN puestos ON incidencias.id_puesto = puestos.id_puesto
            JOIN users ON incidencias.id_user = users.id_user 
            WHERE 1=1"; // Esto permite agregar condiciones dinámicamente

    // Inicializar un array para los parámetros
    $params = [];

    // Aplicar filtros si están presentes
    if (isset($_GET['estado']) && $_GET['estado'] !== '') {
        $sql .= " AND incidencias.estado = ?";
        $params[] = $_GET['estado'];
    }

    if (isset($_GET['tipo']) && $_GET['tipo'] !== '') {
        $sql .= " AND tipos_incidencias.id_tipo = ?";
        $params[] = $_GET['tipo'];
    }

    if (isset($_GET['aula']) && $_GET['aula'] !== '') {
        $sql .= " AND incidencias.id_aula = ?";
        $params[] = $_GET['aula'];
    }

    if (isset($_GET['fechaDesde']) && $_GET['fechaDesde'] !== '') {
        $sql .= " AND incidencias.fecha_creacion >= ?";
        $params[] = $_GET['fechaDesde'];
    }

    if (isset($_GET['fechaHasta']) && $_GET['fechaHasta'] !== '') {
        $sql .= " AND incidencias.fecha_creacion <= ?";
        $params[] = $_GET['fechaHasta'];
    }

    // Ordenar por fecha de creación
    $sql .= " ORDER BY 
            CASE 
                WHEN incidencias.estado = 'Pendiente' THEN 1
                WHEN incidencias.estado = 'En proceso' THEN 2
                WHEN incidencias.estado = 'Resuelto' THEN 3
                WHEN incidencias.estado = 'Cerrado' THEN 4
                WHEN incidencias.estado = 'Cancelado' THEN 5
            END, 
            incidencias.fecha_creacion DESC";

    // Registrar la consulta SQL y los parámetros
    error_log("Consulta SQL: " . $sql);
    error_log("Parámetros: " . json_encode($params));

    // Preparar y ejecutar la consulta
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Obtener los resultados
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asegúrate de que el resultado sea un array
    if ($result === false) {
        echo json_encode(["error" => "No se encontraron incidencias."]);
        exit;
    }

    // Devolver el resultado como JSON
    echo json_encode($result);
    exit;
}

// Crear una nueva incidencia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar que los campos obligatorios están presentes
    if (empty($data['id_aula']) || empty($data['id_tipo']) || empty($data['id_puesto']) || empty($data['descripcion'])) {
        echo json_encode(["error" => "Todos los campos son obligatorios"]);
        http_response_code(400);
        exit;
    }

    // Insertar la incidencia en la base de datos
    $sql = "INSERT INTO incidencias (id_aula, id_user, id_tipo, id_puesto, descripcion) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['id_aula'], $user_id, $data['id_tipo'], $data['id_puesto'], $data['descripcion']]);

    // Obtener los detalles de la incidencia recién creada
    $id_incidencia = $pdo->lastInsertId(); // Obtener el ID de la incidencia creada
    $fecha_creacion = date('Y-m-d H:i:s'); // Fecha y hora actual

    // Obtener el nombre del aula
    $stmt = $pdo->prepare("SELECT nombre FROM aulas WHERE id_aula = ?");
    $stmt->execute([$data['id_aula']]);
    $aula = $stmt->fetchColumn();

    // Obtener el tipo de incidencia
    $stmt = $pdo->prepare("SELECT tipo FROM tipos_incidencias WHERE id_tipo = ?");
    $stmt->execute([$data['id_tipo']]);
    $tipo_incidencia = $stmt->fetchColumn();

    // Obtener el nombre del puesto
    $stmt = $pdo->prepare("SELECT nombre FROM puestos WHERE id_puesto = ?");
    $stmt->execute([$data['id_puesto']]);
    $puesto = $stmt->fetchColumn();

    // Obtener el email del usuario que creó la incidencia
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $email_usuario = $stmt->fetchColumn();

    // Obtener todos los correos de los usuarios asignados a la clase
    $stmt = $pdo->prepare("
        SELECT u.email 
        FROM aulas_profes ap
        JOIN users u ON ap.id_user = u.id_user
        WHERE ap.id_aula = ?
        ");
    $stmt->execute([$data['id_aula']]);
    $emails_asignados = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // Enviar correo con los detalles de la incidencia a todos los usuarios asignados
    enviarCorreoIncidencia($emails_asignados, $aula, $puesto, $data['descripcion'], $fecha_creacion, $email_usuario);

    // Respuesta de éxito
    echo json_encode(["message" => "Incidencia creada con éxito y correos enviados a los usuarios asignados."]);
    exit;
}

// Actualizar el estado de una incidencia
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar que se han proporcionado los datos necesarios
    if (empty($data['id_incidencia']) || empty($data['estado'])) {
        echo json_encode(["error" => "ID de incidencia y estado son obligatorios"]);
        http_response_code(400);
        exit;
    }

    // Validar si el estado es uno de los permitidos
    $validEstados = ['Pendiente', 'En proceso', 'Resuelto', 'Cerrado', 'Cancelado'];
    if (!in_array($data['estado'], $validEstados)) {
        echo json_encode(["error" => "Estado no válido"]);
        http_response_code(400);
        exit;
    }

    // Verificar si el usuario tiene permisos para modificar la incidencia
    $sql = "SELECT id_user FROM incidencias WHERE id_incidencia = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['id_incidencia']]);
    $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$incidencia) {
        echo json_encode(["error" => "Incidencia no encontrada"]);
        http_response_code(404);
        exit;
    }

    if ($incidencia['id_user'] !== $user_id) {
        echo json_encode(["error" => "No tienes permisos para modificar esta incidencia"]);
        http_response_code(403);
        exit;
    }

    // Determinar la fecha de cierre
    $fecha_cierre = null;
    if (in_array($data['estado'], ['Resuelto', 'Cerrado', 'Cancelado'])) {
        $fecha_cierre = date('Y-m-d H:i:s');
    }

    // Actualizar estado y fecha de cierre de la incidencia en la base de datos
    $sql = "UPDATE incidencias SET estado = ?, fecha_cierre = ? WHERE id_incidencia = ? AND id_user = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['estado'], $fecha_cierre, $data['id_incidencia'], $user_id]);

    echo json_encode(["message" => "Estado de la incidencia actualizado"]);
    exit;
}

function enviarCorreoModificacionIncidencia($emails, $aula, $puesto, $descripcion, $fecha_creacion, $email_creador, $solucion = null, $nuevo_usuario = null)
{
    // Crear instancia de PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP (NO MODIFICAR)
        $mail->isSMTP();
        $mail->Host       = 'smtp01.educa.madrid.org';
        $mail->Username   = 'sebastian.coello@educa.madrid.org'; // Tu correo
        $mail->Password   = '95AA B0B3 7B67 3E69 EA51 CC3A 907A F7C5 5075 B03A'; // Contraseña o clave de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587; 
        
        // Configuración del correo
        $mail->setFrom('sebastian.coello@educa.madrid.org', 'Admin');

        // Añadir todos los destinatarios
        foreach ($emails as $email) {
            $mail->addAddress($email); // Destinatario (usuarios asignados a la clase)
        }

        $mail->Subject = 'Modificación de Incidencia'; // Asunto del correo

        // Cuerpo del correo con los detalles de la incidencia
        $mail->Body = "
            <h1>Modificación de Incidencia</h1>
            <p>Se ha modificado una incidencia con los siguientes detalles:</p>
            <ul>
                <li><strong>Aula:</strong> $aula</li>
                <li><strong>Puesto:</strong> $puesto</li>
                <li><strong>Descripcion:</strong> $descripcion</li>
                <li><strong>Fecha y Hora:</strong> $fecha_creacion</li>
                <li><strong>Creada por:</strong> $email_creador</li>";
        
        if ($solucion) {
            $mail->Body .= "<li><strong>Solución:</strong> $solucion</li>";
        }

        if ($nuevo_usuario) {
            $mail->Body .= "<li><strong>Nuevo Usuario:</strong> $nuevo_usuario</li>";
        }

        $mail->Body .= "</ul>
            <p>Por favor, revisa la incidencia en el sistema.</p>
        ";
        $mail->isHTML(true); // Habilitar formato HTML

        // Enviar correo
        $mail->send();
        error_log("Correo de modificación enviado correctamente a los usuarios asignados a la clase.");
    } catch (Exception $e) {
        error_log("No se pudo enviar el correo de modificación. Error: " . $mail->ErrorInfo);
    }
}

// Eliminar una incidencia
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['id_incidencia'])) {
        echo json_encode(["error" => "ID de incidencia requerido"]);
        http_response_code(400);
        exit;
    }

    $sql = "DELETE FROM incidencias WHERE id_incidencia = ? AND id_user = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['id_incidencia'], $user_id]);

    echo json_encode(["message" => "Incidencia eliminada"]);
    exit;
}

// Obtener aulas
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aulas'])) {
    try {
        $stmt = $pdo->query("SELECT id_aula, nombre FROM aulas ORDER BY nombre ASC");
        $aula = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($aula);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error al obtener aulas: " . $e->getMessage()]);
        http_response_code(500);
    }
}
?>