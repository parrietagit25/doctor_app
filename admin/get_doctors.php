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

// Obtener todos los doctores
$stmt = $user->obtenerDoctores();
$doctors = [];

if($stmt) {
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $doctors[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'apellido' => $row['apellido'],
            'especialidad' => $row['especialidad']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($doctors);
?>
