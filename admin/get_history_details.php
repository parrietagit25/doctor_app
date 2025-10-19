<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MedicalHistory.php';
require_once __DIR__ . '/../classes/User.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['administrador', 'doctor'])) {
    echo '<div class="alert alert-danger">No autorizado.</div>';
    exit();
}

if(!isset($_POST['history_id']) || empty($_POST['history_id'])) {
    echo '<div class="alert alert-warning">ID de registro no válido.</div>';
    exit();
}

$database = new Database();
$db = $database->getConnection();
$medicalHistory = new MedicalHistory($db);

$history_id = intval($_POST['history_id']);

// Obtener detalles del registro
$history = $medicalHistory->obtenerPorId($history_id);
if(!$history) {
    echo '<div class="alert alert-warning">Registro no encontrado.</div>';
    exit();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-medical-alt me-2"></i>
                        Detalles del Registro Médico
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Información General -->
                        <div class="col-md-6 mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-info-circle me-2"></i>Información General
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Fecha de Consulta:</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($history['fecha_consulta'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Doctor:</strong></td>
                                    <td><?php echo htmlspecialchars($history['doctor_nombre'] . ' ' . $history['doctor_apellido']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Especialidad:</strong></td>
                                    <td><?php echo htmlspecialchars($history['especialidad']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Paciente:</strong></td>
                                    <td><?php echo htmlspecialchars($history['paciente_nombre'] . ' ' . $history['paciente_apellido']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Cita Asociada:</strong></td>
                                    <td>
                                        <?php if($history['cita_id']): ?>
                                            <span class="badge bg-info">Cita #<?php echo $history['cita_id']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Sin cita asociada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Información Médica -->
                        <div class="col-md-6 mb-4">
                            <h6 class="text-success mb-3">
                                <i class="fas fa-stethoscope me-2"></i>Información Médica
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>ID del Registro:</strong></td>
                                    <td><?php echo $history['id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha de Creación:</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($history['fecha_consulta'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- Diagnóstico -->
                    <div class="mb-4">
                        <h6 class="text-danger mb-3">
                            <i class="fas fa-diagnoses me-2"></i>Diagnóstico
                        </h6>
                        <div class="alert alert-light border-start border-danger border-4">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($history['diagnostico'])); ?></p>
                        </div>
                    </div>

                    <!-- Tratamiento -->
                    <div class="mb-4">
                        <h6 class="text-warning mb-3">
                            <i class="fas fa-prescription-bottle-alt me-2"></i>Tratamiento
                        </h6>
                        <div class="alert alert-light border-start border-warning border-4">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($history['tratamiento'])); ?></p>
                        </div>
                    </div>

                    <!-- Medicamentos -->
                    <div class="mb-4">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-pills me-2"></i>Medicamentos
                        </h6>
                        <div class="alert alert-light border-start border-info border-4">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($history['medicamentos'])); ?></p>
                        </div>
                    </div>

                    <!-- Imágenes Médicas -->
                    <div class="mb-4">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-images me-2"></i>Imágenes Médicas
                        </h6>
                        <div id="medical_images_container">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Cargando imágenes...
                            </div>
                        </div>
                    </div>

                    <!-- Notas Adicionales -->
                    <?php if(!empty($history['notas_adicionales'])): ?>
                    <div class="mb-4">
                        <h6 class="text-secondary mb-3">
                            <i class="fas fa-sticky-note me-2"></i>Notas Adicionales
                        </h6>
                        <div class="alert alert-light border-start border-secondary border-4">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($history['notas_adicionales'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cerrar
                        </button>
                        <?php if($_SESSION['user_type'] == 'administrador' || $_SESSION['user_id'] == $history['doctor_id']): ?>
                        <button class="btn btn-warning" onclick="editHistory(<?php echo $history['id']; ?>)">
                            <i class="fas fa-edit me-2"></i>Editar Registro
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
