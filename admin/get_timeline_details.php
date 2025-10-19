<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MedicalHistory.php';
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/MedicalImage.php';
require_once __DIR__ . '/../classes/Cotizacion.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'administrador') {
    die('No autorizado');
}

if (!isset($_POST['event_id']) || !isset($_POST['event_type'])) {
    die('Parámetros requeridos');
}

$event_id = intval($_POST['event_id']);
$event_type = $_POST['event_type'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($event_type === 'historial') {
        // Obtener detalles del historial médico
        $medicalHistory = new MedicalHistory($db);
        $historial = $medicalHistory->obtenerPorId($event_id);
        
        if (!$historial) {
            echo '<div class="alert alert-danger">Registro de historial médico no encontrado.</div>';
            exit;
        }
        
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="card border-success">';
        echo '<div class="card-header bg-success text-white">';
        echo '<h5 class="mb-0"><i class="fas fa-file-medical me-2"></i>Detalles del Registro Médico</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<h6 class="text-primary"><i class="fas fa-calendar me-2"></i>Información General</h6>';
        echo '<p><strong>Fecha de Consulta:</strong> ' . date('d/m/Y H:i', strtotime($historial['fecha_consulta'])) . '</p>';
        echo '<p><strong>Doctor:</strong> ' . htmlspecialchars($historial['doctor_nombre'] . ' ' . $historial['doctor_apellido']) . '</p>';
        echo '<p><strong>Paciente:</strong> ' . htmlspecialchars($historial['paciente_nombre'] . ' ' . $historial['paciente_apellido']) . '</p>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<h6 class="text-success"><i class="fas fa-user-md me-2"></i>Información Médica</h6>';
        echo '<p><strong>Diagnóstico:</strong> ' . htmlspecialchars($historial['diagnostico'] ?? 'No especificado') . '</p>';
        echo '<p><strong>Tratamiento:</strong> ' . htmlspecialchars($historial['tratamiento'] ?? 'No especificado') . '</p>';
        echo '<p><strong>Medicamentos:</strong> ' . htmlspecialchars($historial['medicamentos'] ?? 'No especificado') . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<hr>';
        
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<h6 class="text-info"><i class="fas fa-clipboard-list me-2"></i>Detalles Clínicos</h6>';
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<div class="card bg-light">';
        echo '<div class="card-body">';
        echo '<h6 class="card-title text-warning"><i class="fas fa-pills me-2"></i>Medicamentos</h6>';
        echo '<p class="card-text">' . htmlspecialchars($historial['medicamentos'] ?? 'No especificado') . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<div class="card bg-light">';
        echo '<div class="card-body">';
        echo '<h6 class="card-title text-secondary"><i class="fas fa-sticky-note me-2"></i>Notas Adicionales</h6>';
        echo '<p class="card-text">' . htmlspecialchars($historial['notas_adicionales'] ?? 'Sin notas adicionales') . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
    } elseif ($event_type === 'cita') {
        // Obtener detalles de la cita
        $appointment = new Appointment($db);
        $cita = $appointment->obtenerPorId($event_id);
        
        if (!$cita) {
            echo '<div class="alert alert-danger">Cita médica no encontrada.</div>';
            exit;
        }
        
        $estado_class = '';
        $estado_text = '';
        switch ($cita['status']) {
            case 'programada':
                $estado_class = 'text-primary';
                $estado_text = 'Programada';
                break;
            case 'completada':
                $estado_class = 'text-success';
                $estado_text = 'Completada';
                break;
            case 'cancelada':
                $estado_class = 'text-danger';
                $estado_text = 'Cancelada';
                break;
            default:
                $estado_class = 'text-secondary';
                $estado_text = ucfirst($cita['status']);
        }
        
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="card border-primary">';
        echo '<div class="card-header bg-primary text-white">';
        echo '<h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Detalles de la Cita Médica</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<h6 class="text-primary"><i class="fas fa-calendar me-2"></i>Información de la Cita</h6>';
        echo '<p><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($cita['fecha_cita'])) . '</p>';
        echo '<p><strong>Hora:</strong> ' . htmlspecialchars($cita['hora_cita']) . '</p>';
        echo '<p><strong>Estado:</strong> <span class="' . $estado_class . ' fw-bold">' . $estado_text . '</span></p>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<h6 class="text-success"><i class="fas fa-user-md me-2"></i>Personal Médico</h6>';
        echo '<p><strong>Doctor:</strong> ' . htmlspecialchars($cita['doctor_nombre'] . ' ' . $cita['doctor_apellido']) . '</p>';
        echo '<p><strong>Paciente:</strong> ' . htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']) . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<hr>';
        
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<h6 class="text-info"><i class="fas fa-clipboard-list me-2"></i>Detalles de la Cita</h6>';
        echo '<div class="card bg-light">';
        echo '<div class="card-body">';
        echo '<h6 class="card-title text-primary"><i class="fas fa-comment me-2"></i>Motivo de la Cita</h6>';
        echo '<p class="card-text">' . htmlspecialchars($cita['motivo']) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
    } elseif ($event_type === 'imagen') {
        // Obtener detalles de la imagen
        $medicalImage = new MedicalImage($db);
        $imagen = $medicalImage->obtenerPorId($event_id);
        
        if (!$imagen) {
            echo '<div class="alert alert-danger">Imagen médica no encontrada.</div>';
            exit;
        }
        
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="card border-info">';
        echo '<div class="card-header bg-info text-white">';
        echo '<h5 class="mb-0"><i class="fas fa-images me-2"></i>Detalles de la Imagen Médica</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<h6 class="text-primary"><i class="fas fa-file me-2"></i>Información del Archivo</h6>';
        echo '<p><strong>Nombre:</strong> ' . htmlspecialchars($imagen['nombre_archivo']) . '</p>';
        echo '<p><strong>Tamaño:</strong> ' . MedicalImage::formatearTamaño($imagen['tamaño_archivo']) . '</p>';
        echo '<p><strong>Tipo:</strong> ' . htmlspecialchars($imagen['tipo_mime']) . '</p>';
        echo '<p><strong>Fecha de Subida:</strong> ' . date('d/m/Y H:i', strtotime($imagen['fecha_subida'])) . '</p>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<h6 class="text-success"><i class="fas fa-user-md me-2"></i>Información Médica</h6>';
        echo '<p><strong>Subido por:</strong> ' . htmlspecialchars($imagen['usuario_nombre'] . ' ' . $imagen['usuario_apellido']) . '</p>';
        echo '<p><strong>Historial ID:</strong> ' . $imagen['historial_medico_id'] . '</p>';
        if (!empty($imagen['nota'])) {
            echo '<p><strong>Nota:</strong> ' . htmlspecialchars($imagen['nota']) . '</p>';
        }
        echo '</div>';
        echo '</div>';
        
        echo '<hr>';
        
        echo '<div class="row">';
        echo '<div class="col-12 text-center">';
        echo '<h6 class="text-info"><i class="fas fa-image me-2"></i>Vista Previa</h6>';
        echo '<img src="../' . htmlspecialchars($imagen['ruta_archivo']) . '" class="img-fluid rounded shadow" style="max-height: 400px;" alt="Imagen médica">';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
    } elseif ($event_type === 'cotizacion') {
        // Obtener detalles de la cotización
        $cotizacion = new Cotizacion($db);
        $cotizacion_data = $cotizacion->obtenerPorId($event_id);
        
        if (!$cotizacion_data) {
            echo '<div class="alert alert-danger">Cotización no encontrada.</div>';
            exit;
        }
        
        $estado_class = '';
        $estado_text = '';
        switch ($cotizacion_data['estado']) {
            case 'pendiente':
                $estado_class = 'text-warning';
                $estado_text = 'Pendiente';
                break;
            case 'aprobada':
                $estado_class = 'text-success';
                $estado_text = 'Aprobada';
                break;
            case 'rechazada':
                $estado_class = 'text-danger';
                $estado_text = 'Rechazada';
                break;
            case 'vencida':
                $estado_class = 'text-secondary';
                $estado_text = 'Vencida';
                break;
            default:
                $estado_class = 'text-info';
                $estado_text = ucfirst($cotizacion_data['estado']);
        }
        
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="card border-warning">';
        echo '<div class="card-header bg-warning text-dark">';
        echo '<h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Detalles de la Cotización</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<h6 class="text-primary"><i class="fas fa-calendar me-2"></i>Información General</h6>';
        echo '<p><strong>Fecha de Creación:</strong> ' . date('d/m/Y H:i', strtotime($cotizacion_data['fecha_creacion'])) . '</p>';
        echo '<p><strong>Fecha de Vencimiento:</strong> ' . date('d/m/Y', strtotime($cotizacion_data['fecha_vencimiento'])) . '</p>';
        echo '<p><strong>Estado:</strong> <span class="' . $estado_class . ' fw-bold">' . $estado_text . '</span></p>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<h6 class="text-success"><i class="fas fa-user-md me-2"></i>Información Médica</h6>';
        echo '<p><strong>Doctor:</strong> ' . htmlspecialchars($cotizacion_data['doctor_nombre'] . ' ' . $cotizacion_data['doctor_apellido']) . '</p>';
        echo '<p><strong>Paciente:</strong> ' . htmlspecialchars($cotizacion_data['paciente_nombre'] . ' ' . $cotizacion_data['paciente_apellido']) . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<hr>';
        
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<h6 class="text-info"><i class="fas fa-calculator me-2"></i>Detalles Financieros</h6>';
        echo '<div class="row">';
        echo '<div class="col-md-4">';
        echo '<div class="card bg-light">';
        echo '<div class="card-body text-center">';
        echo '<h6 class="card-title text-primary">Subtotal</h6>';
        echo '<h4 class="text-primary">$' . number_format($cotizacion_data['subtotal'], 2) . '</h4>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-4">';
        echo '<div class="card bg-light">';
        echo '<div class="card-body text-center">';
        echo '<h6 class="card-title text-warning">Impuesto</h6>';
        echo '<h4 class="text-warning">$' . number_format($cotizacion_data['impuesto'], 2) . '</h4>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-4">';
        echo '<div class="card bg-success text-white">';
        echo '<div class="card-body text-center">';
        echo '<h6 class="card-title">Total</h6>';
        echo '<h4>$' . number_format($cotizacion_data['total'], 2) . '</h4>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        if (!empty($cotizacion_data['notas'])) {
            echo '<hr>';
            echo '<div class="row">';
            echo '<div class="col-12">';
            echo '<h6 class="text-secondary"><i class="fas fa-sticky-note me-2"></i>Notas</h6>';
            echo '<div class="card bg-light">';
            echo '<div class="card-body">';
            echo '<p class="card-text">' . htmlspecialchars($cotizacion_data['notas']) . '</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
    } else {
        echo '<div class="alert alert-warning">Tipo de evento no válido.</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
