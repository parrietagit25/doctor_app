<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MedicalImage.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['administrador', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se haya enviado el image_id
if (!isset($_POST['image_id']) || empty($_POST['image_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de imagen requerido']);
    exit;
}

$image_id = intval($_POST['image_id']);

try {
    $database = new Database();
    $db = $database->getConnection();
    $medicalImage = new MedicalImage($db);
    
    // Verificar que la imagen existe
    if (!$medicalImage->obtenerPorId($image_id)) {
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
        exit;
    }
    
    // Verificar permisos (doctores solo pueden eliminar sus propias imágenes)
    if ($_SESSION['user_type'] == 'doctor' && $_SESSION['user_id'] != $medicalImage->subido_por) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar esta imagen']);
        exit;
    }
    
    // Eliminar imagen
    if ($medicalImage->eliminar()) {
        echo json_encode(['success' => true, 'message' => 'Imagen eliminada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la imagen']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
