<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$identificacion = $_GET['identificacion'] ?? '';

if(empty($identificacion)) {
    echo json_encode(['exists' => false]);
    exit();
}

// Verificar si la identificación ya existe
$exists = $user->verificarIdentificacion($identificacion);

header('Content-Type: application/json');
echo json_encode(['exists' => $exists]);
?>
