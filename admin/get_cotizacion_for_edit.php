<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Cotizacion.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['administrador', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if(!isset($_POST['cotizacion_id']) || empty($_POST['cotizacion_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de cotización no válido']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$cotizacion = new Cotizacion($db);

$cotizacion_id = intval($_POST['cotizacion_id']);

// Obtener detalles de la cotización
$cotizacion_data = $cotizacion->obtenerPorId($cotizacion_id);
if(!$cotizacion_data) {
    echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
    exit();
}

// Verificar permisos (solo administradores o el doctor que creó la cotización)
if($_SESSION['user_type'] != 'administrador' && $_SESSION['user_id'] != $cotizacion_data['doctor_id']) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar esta cotización']);
    exit();
}

// Retornar datos en formato JSON
echo json_encode([
    'success' => true,
    'fecha_vencimiento' => $cotizacion_data['fecha_vencimiento'],
    'estado' => $cotizacion_data['estado'],
    'notas' => $cotizacion_data['notas']
]);
?>
