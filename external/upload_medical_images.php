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

// Verificar que se haya enviado el historial_id
if (!isset($_POST['historial_id']) || empty($_POST['historial_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de historial médico requerido']);
    exit;
}

$historial_id = intval($_POST['historial_id']);
$nota = isset($_POST['image_note']) ? trim($_POST['image_note']) : '';

// Verificar que se hayan enviado archivos
if (!isset($_FILES['medical_images']) || empty($_FILES['medical_images']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No se han seleccionado archivos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $medicalImage = new MedicalImage($db);
    
    // Directorio de subida
    $upload_dir = __DIR__ . '/../uploads/medical_images/';
    
    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded_files = [];
    $errors = [];
    
    // Procesar cada archivo
    $files = $_FILES['medical_images'];
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        // Crear array de archivo individual
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        
        // Validar archivo
        $validation_errors = MedicalImage::validarImagen($file);
        if (!empty($validation_errors)) {
            $errors[] = $file['name'] . ': ' . implode(', ', $validation_errors);
            continue;
        }
        
        // Generar nombre único
        $nombre_archivo = MedicalImage::generarNombreArchivo($file['name'], $historial_id);
        $ruta_archivo = $upload_dir . $nombre_archivo;
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $ruta_archivo)) {
            // Crear nueva instancia para cada archivo
            $medicalImageNew = new MedicalImage($db);
            
            // Guardar en base de datos
            $medicalImageNew->historial_medico_id = $historial_id;
            $medicalImageNew->nombre_archivo = $file['name'];
            $medicalImageNew->ruta_archivo = 'uploads/medical_images/' . $nombre_archivo;
            $medicalImageNew->tamaño_archivo = $file['size'];
            $medicalImageNew->tipo_mime = $file['type'];
            $medicalImageNew->nota = $nota;
            $medicalImageNew->subido_por = $_SESSION['user_id'];
            
            if ($medicalImageNew->crear()) {
                $uploaded_files[] = [
                    'nombre' => $file['name'],
                    'tamaño' => MedicalImage::formatearTamaño($file['size'])
                ];
            } else {
                $errors[] = $file['name'] . ': Error al guardar en base de datos';
                // Eliminar archivo si falló la BD
                if (file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
            }
        } else {
            $errors[] = $file['name'] . ': Error al mover el archivo';
        }
    }
    
    // Preparar respuesta
    if (!empty($uploaded_files)) {
        $message = 'Se subieron ' . count($uploaded_files) . ' imagen(es) exitosamente';
        if (!empty($errors)) {
            $message .= '. Errores: ' . implode('; ', $errors);
        }
        echo json_encode([
            'success' => true,
            'message' => $message,
            'uploaded_files' => $uploaded_files
        ]);
    } else {
        $message = 'No se pudieron subir las imágenes. Errores: ' . implode('; ', $errors);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
