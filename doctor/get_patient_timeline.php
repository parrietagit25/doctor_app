<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MedicalHistory.php';
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/MedicalImage.php';
require_once __DIR__ . '/../classes/Cotizacion.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    die('No autorizado');
}

if (!isset($_POST['patient_id']) || empty($_POST['patient_id'])) {
    die('ID de paciente requerido');
}

$patient_id = intval($_POST['patient_id']);
$doctor_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener información del paciente
    $stmt_patient = $db->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ? AND tipo_usuario = 'paciente'");
    $stmt_patient->execute([$patient_id]);
    $patient = $stmt_patient->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        echo '<div class="alert alert-danger">Paciente no encontrado.</div>';
        exit;
    }
    
    // Obtener historial médico (solo del doctor actual)
    $medicalHistory = new MedicalHistory($db);
    $stmt_history = $medicalHistory->obtenerPorPacienteYDoctor($patient_id, $doctor_id);
    $historial_medico = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener citas médicas (solo del doctor actual)
    $appointment = new Appointment($db);
    $stmt_appointments = $appointment->obtenerPorPacienteYDoctor($patient_id, $doctor_id);
    $citas = $stmt_appointments->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener imágenes médicas (solo del doctor actual)
    $stmt_images = $db->prepare("
        SELECT hmi.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido,
               hm.fecha_consulta, p.nombre as paciente_nombre, p.apellido as paciente_apellido
        FROM historial_medico_imagenes hmi
        LEFT JOIN usuarios u ON hmi.subido_por = u.id
        LEFT JOIN historial_medico hm ON hmi.historial_medico_id = hm.id
        LEFT JOIN usuarios p ON hm.paciente_id = p.id
        WHERE hm.paciente_id = :paciente_id AND hm.doctor_id = :doctor_id
        ORDER BY hmi.fecha_subida DESC
    ");
    $stmt_images->bindParam(':paciente_id', $patient_id);
    $stmt_images->bindParam(':doctor_id', $doctor_id);
    $stmt_images->execute();
    $imagenes = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener cotizaciones (solo del doctor actual)
    $stmt_cotizaciones = $db->prepare("
        SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido,
               d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
        FROM cotizaciones c
        LEFT JOIN usuarios p ON c.paciente_id = p.id
        LEFT JOIN usuarios d ON c.doctor_id = d.id
        WHERE c.paciente_id = :paciente_id AND c.doctor_id = :doctor_id
        ORDER BY c.fecha_creacion DESC
    ");
    $stmt_cotizaciones->bindParam(':paciente_id', $patient_id);
    $stmt_cotizaciones->bindParam(':doctor_id', $doctor_id);
    $stmt_cotizaciones->execute();
    $cotizaciones = $stmt_cotizaciones->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar todos los eventos en un array
    $eventos = [];
    
    // Agregar registros del historial médico
    foreach ($historial_medico as $registro) {
        $eventos[] = [
            'fecha' => $registro['fecha_consulta'],
            'tipo' => 'historial',
            'titulo' => 'Registro Médico',
            'descripcion' => $registro['diagnostico'],
            'doctor' => $registro['doctor_nombre'] . ' ' . $registro['doctor_apellido'],
            'id' => $registro['id'],
            'detalles' => [
                'Síntomas' => $registro['sintomas'] ?? 'No especificado',
                'Diagnóstico' => $registro['diagnostico'] ?? 'No especificado',
                'Tratamiento' => $registro['tratamiento'] ?? 'No especificado',
                'Observaciones' => $registro['observaciones'] ?? 'Sin observaciones'
            ]
        ];
    }
    
    // Agregar citas médicas
    foreach ($citas as $cita) {
        $estado_class = '';
        $estado_text = '';
        switch ($cita['status'] ?? '') {
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
                $estado_text = ucfirst($cita['status'] ?? 'Sin estado');
        }
        
        $eventos[] = [
            'fecha' => $cita['fecha_cita'] . ' ' . $cita['hora_cita'],
            'tipo' => 'cita',
            'titulo' => 'Cita Médica',
            'descripcion' => $cita['motivo'],
            'doctor' => $cita['doctor_nombre'] . ' ' . $cita['doctor_apellido'],
            'id' => $cita['id'],
            'estado' => $estado_text,
            'estado_class' => $estado_class,
            'detalles' => [
                'Motivo' => $cita['motivo'],
                'Estado' => $estado_text,
                'Fecha' => date('d/m/Y', strtotime($cita['fecha_cita'])),
                'Hora' => $cita['hora_cita']
            ]
        ];
    }
    
    // Agregar eventos de imágenes médicas
    foreach ($imagenes as $imagen) {
        $eventos[] = [
            'fecha' => $imagen['fecha_subida'],
            'tipo' => 'imagen',
            'titulo' => 'Imagen Médica',
            'descripcion' => $imagen['nombre_archivo'],
            'doctor' => $imagen['usuario_nombre'] . ' ' . $imagen['usuario_apellido'],
            'id' => $imagen['id'],
            'historial_id' => $imagen['historial_medico_id'],
            'detalles' => [
                'Archivo' => $imagen['nombre_archivo'],
                'Tamaño' => MedicalImage::formatearTamaño($imagen['tamaño_archivo']),
                'Tipo' => $imagen['tipo_mime'],
                'Nota' => $imagen['nota'] ?? 'Sin nota',
                'Fecha de Consulta' => date('d/m/Y H:i', strtotime($imagen['fecha_consulta']))
            ]
        ];
    }
    
    // Agregar eventos de cotizaciones
    foreach ($cotizaciones as $cotizacion_data) {
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
        
        $eventos[] = [
            'fecha' => $cotizacion_data['fecha_creacion'],
            'tipo' => 'cotizacion',
            'titulo' => 'Cotización Médica',
            'descripcion' => 'Cotización por $' . number_format($cotizacion_data['total'], 2),
            'doctor' => $cotizacion_data['doctor_nombre'] . ' ' . $cotizacion_data['doctor_apellido'],
            'id' => $cotizacion_data['id'],
            'estado' => $estado_text,
            'estado_class' => $estado_class,
            'detalles' => [
                'Subtotal' => '$' . number_format($cotizacion_data['subtotal'], 2),
                'Impuesto' => '$' . number_format($cotizacion_data['impuesto'], 2),
                'Total' => '$' . number_format($cotizacion_data['total'], 2),
                'Estado' => $estado_text,
                'Fecha de Creación' => date('d/m/Y H:i', strtotime($cotizacion_data['fecha_creacion'])),
                'Fecha de Vencimiento' => date('d/m/Y', strtotime($cotizacion_data['fecha_vencimiento'])),
                'Notas' => $cotizacion_data['notas'] ?? 'Sin notas'
            ]
        ];
    }
    
    // Ordenar eventos por fecha (más reciente primero)
    usort($eventos, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    if (empty($eventos)) {
        echo '<div class="text-center py-5">';
        echo '<i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>';
        echo '<h5 class="text-muted">Sin registros médicos</h5>';
        echo '<p class="text-muted">Este paciente no tiene registros médicos o citas programadas con usted.</p>';
        echo '</div>';
        exit;
    }
    
    // Mostrar timeline
    echo '<div class="timeline-container">';
    echo '<div class="row mb-4">';
    echo '<div class="col-12">';
    echo '<div class="card border-info">';
    echo '<div class="card-header bg-info text-white">';
    echo '<h6 class="mb-0"><i class="fas fa-user me-2"></i>Paciente: ' . htmlspecialchars($patient['nombre'] . ' ' . $patient['apellido']) . '</h6>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<p class="text-muted mb-0">Timeline cronológico de todos los registros médicos y citas (solo sus registros).</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="timeline">';
    
    foreach ($eventos as $index => $evento) {
        $fecha_formateada = date('d/m/Y H:i', strtotime($evento['fecha']));
        
        // Definir iconos, colores y badges según el tipo de evento
        switch ($evento['tipo']) {
            case 'historial':
                $icono = 'fa-file-medical';
                $color = 'success';
                $badge_class = 'bg-success';
                $badge_text = 'HISTORIAL';
                break;
            case 'cita':
                $icono = 'fa-calendar-check';
                $color = 'primary';
                $badge_class = 'bg-primary';
                $badge_text = 'CITA';
                break;
            case 'imagen':
                $icono = 'fa-images';
                $color = 'info';
                $badge_class = 'bg-info';
                $badge_text = 'IMAGEN';
                break;
            case 'cotizacion':
                $icono = 'fa-file-invoice-dollar';
                $color = 'warning';
                $badge_class = 'bg-warning';
                $badge_text = 'COTIZACIÓN';
                break;
            default:
                $icono = 'fa-circle';
                $color = 'secondary';
                $badge_class = 'bg-secondary';
                $badge_text = 'EVENTO';
        }
        
        echo '<div class="timeline-item">';
        echo '<div class="timeline-marker bg-' . $color . '">';
        echo '<i class="fas ' . $icono . ' text-white"></i>';
        echo '</div>';
        echo '<div class="timeline-content">';
        echo '<div class="card border-' . $color . '">';
        echo '<div class="card-header bg-light d-flex justify-content-between align-items-center">';
        echo '<div class="d-flex align-items-center">';
        echo '<span class="badge ' . $badge_class . ' me-2">' . $badge_text . '</span>';
        echo '<h6 class="mb-0">';
        echo '<i class="fas ' . $icono . ' me-2 text-' . $color . '"></i>';
        echo htmlspecialchars($evento['titulo']);
        echo '</h6>';
        echo '</div>';
        echo '<small class="text-muted fw-bold">' . $fecha_formateada . '</small>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<h6 class="card-title text-dark">' . htmlspecialchars($evento['descripcion']) . '</h6>';
        echo '<p class="card-text"><strong><i class="fas fa-user-md me-1"></i>Doctor:</strong> ' . htmlspecialchars($evento['doctor']) . '</p>';
        
        if (isset($evento['estado'])) {
            echo '<p class="card-text"><strong><i class="fas fa-info-circle me-1"></i>Estado:</strong> <span class="' . $evento['estado_class'] . ' fw-bold">' . htmlspecialchars($evento['estado']) . '</span></p>';
        }
        
        echo '<button class="btn btn-sm btn-' . $color . '" onclick="showTimelineDetails(' . $evento['id'] . ', \'' . $evento['tipo'] . '\')">';
        echo '<i class="fas fa-eye me-1"></i>Ver Detalles';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
