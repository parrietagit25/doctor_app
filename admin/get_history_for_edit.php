<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MedicalHistory.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['administrador', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if(!isset($_POST['history_id']) || empty($_POST['history_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de registro no válido']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$medicalHistory = new MedicalHistory($db);

$history_id = intval($_POST['history_id']);

// Obtener detalles del registro
$history = $medicalHistory->obtenerPorId($history_id);
if(!$history) {
    echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
    exit();
}

// Verificar permisos (solo administradores o el doctor que creó el registro)
if($_SESSION['user_type'] != 'administrador' && $_SESSION['user_id'] != $history['doctor_id']) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este registro']);
    exit();
}

// Retornar datos en formato JSON
echo json_encode([
    'success' => true,
    'diagnostico' => $history['diagnostico'],
    'tratamiento' => $history['tratamiento'],
    'medicamentos' => $history['medicamentos'],
    'notas_adicionales' => $history['notas_adicionales']
]);
?>
