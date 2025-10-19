<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if($_POST && isset($_POST['action']) && $_POST['action'] === 'register_patient') {
    $user->nombre = $_POST['nombre'] ?? '';
    $user->apellido = $_POST['apellido'] ?? '';
    $user->email = $_POST['email'] ?? '';
    $user->identificacion = $_POST['identificacion'] ?? '';
    $user->telefono = $_POST['telefono'] ?? '';
    $user->tipo_usuario = 'paciente';
    $user->especialidad = null;
    
    // Validar datos requeridos
    if(empty($user->nombre) || empty($user->apellido) || empty($user->identificacion)) {
        echo json_encode(['success' => false, 'message' => 'Los campos obligatorios (Nombre, Apellido, Identificación) deben ser completados']);
        exit();
    }
    
    // Verificar si la identificación ya existe
    if($user->verificarIdentificacion($user->identificacion)) {
        echo json_encode(['success' => false, 'message' => 'Esta identificación ya está registrada']);
        exit();
    }
    
    // Crear paciente
    $patient_id = $user->crearPaciente();
    
    if($patient_id) {
        $patient_name = $user->nombre . ' ' . $user->apellido . ' - ' . ($user->identificacion ?: $user->email);
        echo json_encode([
            'success' => true, 
            'patient_id' => $patient_id,
            'patient_name' => $patient_name,
            'message' => 'Paciente registrado exitosamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar el paciente. El email podría estar en uso.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
