<?php
header('Content-Type: application/json');
require_once 'config.php'; // Usa config.php para centralizar la carga de .env

echo json_encode(['clientId' => $clientId]); // Retorna el clientId obtenido en config.php
?>
