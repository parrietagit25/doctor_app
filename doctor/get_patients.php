<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Obtener todos los pacientes
$stmt = $user->obtenerTodos();
$patients = [];

if($stmt) {
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if($row['tipo_usuario'] === 'paciente') {
            $patients[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'apellido' => $row['apellido'],
                'email' => $row['email'],
                'identificacion' => $row['identificacion'],
                'telefono' => $row['telefono']
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($patients);
?>
