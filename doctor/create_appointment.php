<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

if($_POST && isset($_POST['action']) && $_POST['action'] === 'create_appointment') {
    $appointment->paciente_id = $_POST['paciente_id'] ?? '';
    $appointment->doctor_id = $_POST['doctor_id'] ?? $_SESSION['user_id']; // Usar el doctor de la sesión
    $appointment->fecha_cita = $_POST['fecha_cita'] ?? '';
    $appointment->hora_cita = $_POST['hora_cita'] ?? '';
    $appointment->motivo = $_POST['motivo'] ?? '';
    $appointment->sintomas = $_POST['sintomas'] ?? '';
    
    // Validar datos requeridos
    if(empty($appointment->paciente_id) || empty($appointment->doctor_id) || 
       empty($appointment->fecha_cita) || empty($appointment->hora_cita) || 
       empty($appointment->motivo)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados']);
        exit();
    }
    
    // Verificar disponibilidad
    if(!$appointment->verificarDisponibilidad($appointment->doctor_id, $appointment->fecha_cita, $appointment->hora_cita)) {
        echo json_encode(['success' => false, 'message' => 'El horario seleccionado no está disponible']);
        exit();
    }
    
    // Crear cita
    if($appointment->crear()) {
        echo json_encode(['success' => true, 'message' => 'Cita creada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear la cita']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
