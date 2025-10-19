<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

$doctor_id = $_GET['doctor_id'] ?? null;
$fecha = $_GET['fecha'] ?? null;

if(!$doctor_id || !$fecha) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros requeridos: doctor_id y fecha']);
    exit();
}

// Obtener horarios disponibles
$horarios = $appointment->obtenerHorariosDisponibles($doctor_id, $fecha);

header('Content-Type: application/json');
echo json_encode($horarios);
?>
