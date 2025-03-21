<?php
include 'config.php'; // Conexión a la base de datos
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

// Obtener el ID de la incidencia desde la solicitud
$id_incidencia = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$id_incidencia) {
    echo json_encode(["error" => "ID de incidencia no proporcionado"]);
    http_response_code(400);
    exit;
}

// Verificar si la solicitud es para obtener los datos de la incidencia
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Obtener los datos de la incidencia seleccionada
        $stmt = $pdo->prepare("
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
        WHERE i.id_incidencia = ?
        ");
        $stmt->execute([$id_incidencia]);
        $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$incidencia) {
            echo json_encode(["error" => "Incidencia no encontrada"]);
            http_response_code(404);
            exit;
        }

        // Enviar los datos de la incidencia en el JSON
        echo json_encode([
            "incidencia" => $incidencia, // Agregar la incidencia si existe
        ]);
        exit; // Asegúrate de salir después de enviar la respuesta
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error al obtener datos: " . $e->getMessage()]);
        http_response_code(500);
        exit; // Asegúrate de salir después de enviar la respuesta
    }
}

// Verificar si la solicitud es para actualizar la incidencia
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Obtener los datos enviados en la solicitud PUT
    $input = json_decode(file_get_contents('php://input'), true);

    // Verificar que se hayan proporcionado los datos necesarios
    if (!isset($input['estado']) || !isset($input['solucion'])) {
        echo json_encode(["error" => "Datos incompletos para actualizar la incidencia"]);
        http_response_code(400);
        exit;
    }

    try {
        // Verificar si el usuario actual es el creador o el asignado a la incidencia
        $stmt = $pdo->prepare("SELECT id_user, id_aula, id_tipo, id_puesto, descripcion, fecha_creacion, fecha_cierre FROM incidencias WHERE id_incidencia = ?");
        $stmt->execute([$id_incidencia]);
        $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$incidencia) {
            echo json_encode(["error" => "Incidencia no encontrada"]);
            http_response_code(404);
            exit;
        }

        if ($incidencia['id_user'] !== $user_id) {
            echo json_encode(["error" => "Debes tener asignada la incidencia para poder modificarla"]);
            http_response_code(403);
            exit;
        }

        // Determinar la fecha de cierre
        $fecha_cierre = null;
        if (in_array($input['estado'], ['Resuelto', 'Cerrado', 'Cancelado'])) {
            $fecha_cierre = date('Y-m-d H:i:s');
        }

        // Actualizar la incidencia
        $stmt = $pdo->prepare("
            UPDATE incidencias 
            SET estado = ?, solucion = ?, fecha_cierre = ? 
            WHERE id_incidencia = ?
        ");
        $stmt->execute([
            $input['estado'], 
            $input['solucion'], 
            $fecha_cierre,
            $id_incidencia
        ]);

        // Obtener el nombre del aula
        $stmt = $pdo->prepare("SELECT nombre FROM aulas WHERE id_aula = ?");
        $stmt->execute([$incidencia['id_aula']]);
        $aula = $stmt->fetchColumn();

        // Obtener el tipo de incidencia
        $stmt = $pdo->prepare("SELECT tipo FROM tipos_incidencias WHERE id_tipo = ?");
        $stmt->execute([$incidencia['id_tipo']]);
        $tipo_incidencia = $stmt->fetchColumn();

        // Obtener el nombre del puesto
        $stmt = $pdo->prepare("SELECT nombre FROM puestos WHERE id_puesto = ?");
        $stmt->execute([$incidencia['id_puesto']]);
        $puesto = $stmt->fetchColumn();

        // Obtener el email del usuario que creó la incidencia
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id_user = ?");
        $stmt->execute([$incidencia['id_user']]);
        $email_usuario = $stmt->fetchColumn();

        // Obtener todos los correos de los usuarios asignados a la clase
        $stmt = $pdo->prepare("
            SELECT u.email 
            FROM aulas_profes ap
            JOIN users u ON ap.id_user = u.id_user
            WHERE ap.id_aula = ?
        ");
        $stmt->execute([$incidencia['id_aula']]);
        $emails_asignados = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Enviar correo con los detalles de la incidencia modificada a todos los usuarios asignados
        enviarCorreoModificacionIncidencia($emails_asignados, $aula, $puesto, $incidencia['descripcion'], $incidencia['fecha_creacion'], $fecha_cierre, $email_usuario, $input['solucion'], $input['estado']);

        echo json_encode(["message" => "Incidencia actualizada correctamente y correos enviados a los usuarios asignados."]);
        http_response_code(200);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error al actualizar la incidencia: " . $e->getMessage()]);
        http_response_code(500);
    }
}

function enviarCorreoModificacionIncidencia($emails, $aula, $puesto, $descripcion, $fecha_creacion, $fecha_cierre, $email_creador, $solucion = null, $estado = null)
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

        $mail->Subject = 'Modificacion del Estado de Incidencia'; // Asunto del correo

        // Cuerpo del correo con los detalles de la incidencia
        $mail->Body = "
            <h1>Modificacion del Estado de Incidencia</h1>
            <p>Se ha modificado una incidencia con los siguientes detalles:</p>
            <ul>
                <li><strong>Aula:</strong> $aula</li>
                <li><strong>Puesto:</strong> $puesto</li>
                <li><strong>Descripcion:</strong> $descripcion</li>
                <li><strong>Fecha y Hora de Creacion:</strong> $fecha_creacion</li>
                <li><strong>Fecha de Cierre:</strong> " . ($fecha_cierre ? $fecha_cierre : "N/A") . "</li>
                <li><strong>Creada por:</strong> $email_creador</li>
                <li><strong>Estado:</strong> $estado</li>";
        
        if ($solucion) {
            $mail->Body .= "<li><strong>Solucion:</strong> $solucion</li>";
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
?>