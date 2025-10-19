<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MedicalImage.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['administrador', 'doctor'])) {
    die('No autorizado');
}

if (!isset($_GET['historial_id']) || empty($_GET['historial_id'])) {
    die('ID de historial médico requerido');
}

$historial_id = intval($_GET['historial_id']);

try {
    $database = new Database();
    $db = $database->getConnection();
    $medicalImage = new MedicalImage($db);
    
    $stmt = $medicalImage->obtenerPorHistorial($historial_id);
    
    if ($stmt->rowCount() > 0) {
        echo '<div class="row g-3">';
        while ($image = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<div class="col-md-4 col-sm-6">';
            echo '<div class="card">';
            echo '<div class="position-relative">';
            echo '<img src="../' . htmlspecialchars($image['ruta_archivo']) . '" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Imagen médica">';
            echo '<div class="position-absolute top-0 end-0 m-2">';
            echo '<button class="btn btn-sm btn-danger" onclick="deleteMedicalImage(' . $image['id'] . ')" title="Eliminar imagen">';
            echo '<i class="fas fa-trash"></i>';
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '<div class="card-body p-2">';
            echo '<h6 class="card-title text-truncate" title="' . htmlspecialchars($image['nombre_archivo']) . '">';
            echo htmlspecialchars($image['nombre_archivo']);
            echo '</h6>';
            echo '<p class="card-text small text-muted">';
            echo '<strong>Tamaño:</strong> ' . MedicalImage::formatearTamaño($image['tamaño_archivo']) . '<br>';
            echo '<strong>Subido:</strong> ' . date('d/m/Y H:i', strtotime($image['fecha_subida'])) . '<br>';
            echo '<strong>Por:</strong> ' . htmlspecialchars($image['usuario_nombre'] . ' ' . $image['usuario_apellido']);
            if (!empty($image['nota'])) {
                echo '<br><strong>Nota:</strong> ' . htmlspecialchars($image['nota']);
            }
            echo '</p>';
            echo '</div>';
            echo '<div class="card-footer p-2">';
            echo '<button class="btn btn-sm btn-primary" onclick="viewMedicalImage(\'../' . htmlspecialchars($image['ruta_archivo']) . '\', \'' . htmlspecialchars($image['nombre_archivo']) . '\')" title="Ver imagen completa">';
            echo '<i class="fas fa-eye me-1"></i>Ver';
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-4">';
        echo '<i class="fas fa-images fa-3x text-muted mb-3"></i>';
        echo '<h5 class="text-muted">No hay imágenes</h5>';
        echo '<p class="text-muted">Este registro no tiene imágenes asociadas.</p>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
