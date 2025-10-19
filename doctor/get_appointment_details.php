<?php
// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Deshabilitar errores de PHP para evitar HTML en la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Verificar si el usuario está logueado y es doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Appointment.php';

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de cita inválido']);
    exit();
}

$appointmentId = (int)$_GET['id'];

try {
    $appointmentData = $appointment->obtenerPorId($appointmentId);
    
    if (!$appointmentData) {
        echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
        exit();
    }

    // Verificar que la cita pertenece al doctor logueado
    if ($appointmentData['doctor_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver esta cita']);
        exit();
    }

    // Obtener información del paciente
    require_once __DIR__ . '/../classes/User.php';
    $user = new User($db);
    $paciente = $user->obtenerPorId($appointmentData['paciente_id']);

    if (!$paciente) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener información del paciente']);
        exit();
    }

    $response = [
        'success' => true,
        'appointment' => [
            'id' => $appointmentData['id'],
            'fecha_cita' => date('d/m/Y', strtotime($appointmentData['fecha_cita'])),
            'hora_cita' => $appointmentData['hora_cita'],
            'motivo' => $appointmentData['motivo'] ?? 'No especificado',
            'sintomas' => $appointmentData['sintomas'] ?? 'No especificados',
            'estado' => ucfirst(str_replace('_', ' ', $appointmentData['status'] ?? 'cita_creada')),
            'status' => $appointmentData['status'] ?? 'cita_creada',
            'fecha_creacion' => date('d/m/Y H:i', strtotime($appointmentData['fecha_creacion'])),
            'paciente_nombre' => $paciente['nombre'] ?? 'No disponible',
            'paciente_apellido' => $paciente['apellido'] ?? 'No disponible',
            'paciente_telefono' => $paciente['telefono'] ?? 'No disponible',
            'paciente_email' => $paciente['email'] ?? 'No disponible',
            'paciente_identificacion' => 'No disponible'
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error al obtener detalles de cita: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
