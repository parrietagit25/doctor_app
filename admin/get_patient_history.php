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

if(!isset($_POST['patient_id']) || empty($_POST['patient_id'])) {
    echo '<div class="alert alert-warning">ID de paciente no válido.</div>';
    exit();
}

$database = new Database();
$db = $database->getConnection();
$medicalHistory = new MedicalHistory($db);
$user = new User($db);

$patient_id = intval($_POST['patient_id']);

// Obtener información del paciente
$patient_info = $user->obtenerPorId($patient_id);
if(!$patient_info) {
    echo '<div class="alert alert-warning">Paciente no encontrado.</div>';
    exit();
}

// Obtener historial médico del paciente
$stmt = $medicalHistory->obtenerPorPaciente($patient_id);
?>

<div class="container-fluid">
    <!-- Información del Paciente -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-injured me-2"></i>
                        Información del Paciente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($patient_info->nombre . ' ' . $patient_info->apellido); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_info->email); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($patient_info->telefono); ?></p>
                            <p><strong>ID del Paciente:</strong> <?php echo $patient_id; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial Médico -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-medical me-2"></i>
                        Historial Médico
                    </h5>
                    <div>
                        <button class="btn btn-light btn-sm me-2" onclick="addMedicalHistory(<?php echo $patient_id; ?>)">
                            <i class="fas fa-plus me-1"></i>Agregar Registro
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="createCotizacion(<?php echo $patient_id; ?>)">
                            <i class="fas fa-file-invoice-dollar me-1"></i>Nueva Cotización
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($stmt && $stmt->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Doctor</th>
                                        <th>Diagnóstico</th>
                                        <th>Tratamiento</th>
                                        <th>Medicamentos</th>
                                        <th>Cita ID</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($history = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($history['fecha_consulta'])); ?></strong><br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($history['fecha_consulta'])); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($history['doctor_nombre'] . ' ' . $history['doctor_apellido']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($history['especialidad']); ?></small>
                                        </td>
                                        <td>
                                            <div class="diagnosis-text">
                                                <?php echo nl2br(htmlspecialchars(substr($history['diagnostico'], 0, 100))); ?>
                                                <?php if(strlen($history['diagnostico']) > 100): ?>
                                                    <span class="text-muted">...</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="treatment-text">
                                                <?php echo nl2br(htmlspecialchars(substr($history['tratamiento'], 0, 80))); ?>
                                                <?php if(strlen($history['tratamiento']) > 80): ?>
                                                    <span class="text-muted">...</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="medications-text">
                                                <?php echo nl2br(htmlspecialchars(substr($history['medicamentos'], 0, 80))); ?>
                                                <?php if(strlen($history['medicamentos']) > 80): ?>
                                                    <span class="text-muted">...</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($history['cita_id']): ?>
                                                <span class="badge bg-info">Cita #<?php echo $history['cita_id']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin cita</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewHistoryDetails(<?php echo $history['id']; ?>)" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($_SESSION['user_type'] == 'administrador' || $_SESSION['user_id'] == $history['doctor_id']): ?>
                                            <button class="btn btn-sm btn-outline-warning" onclick="editHistory(<?php echo $history['id']; ?>)" title="Editar registro">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="uploadMedicalImages(<?php echo $history['id']; ?>)" title="Subir imágenes">
                                                <i class="fas fa-images"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-medical fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay registros médicos</h5>
                            <p class="text-muted">Este paciente aún no tiene registros en su historial médico.</p>
                            <button class="btn btn-primary" onclick="addMedicalHistory(<?php echo $patient_id; ?>)">
                                <i class="fas fa-plus me-2"></i>Agregar Primer Registro
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cotizaciones del Paciente -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        Cotizaciones del Paciente
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Obtener cotizaciones del paciente
                    require_once __DIR__ . '/../classes/Cotizacion.php';
                    $cotizacion = new Cotizacion($db);
                    $stmt_cotizaciones = $cotizacion->obtenerPorPaciente($patient_id);
                    ?>
                    
                    <?php if($stmt_cotizaciones && $stmt_cotizaciones->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Vencimiento</th>
                                        <th>Subtotal</th>
                                        <th>Impuesto</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($cot = $stmt_cotizaciones->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $cot['id']; ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($cot['fecha_creacion'])); ?></td>
                                        <td><?php echo $cot['fecha_vencimiento'] ? date('d/m/Y', strtotime($cot['fecha_vencimiento'])) : 'Sin fecha'; ?></td>
                                        <td>$<?php echo number_format($cot['subtotal'], 2); ?></td>
                                        <td>$<?php echo number_format($cot['impuesto'], 2); ?></td>
                                        <td><strong>$<?php echo number_format($cot['total'], 2); ?></strong></td>
                                        <td>
                                            <?php
                                            $estado_class = '';
                                            switch($cot['estado']) {
                                                case 'pendiente': $estado_class = 'bg-warning'; break;
                                                case 'aprobada': $estado_class = 'bg-success'; break;
                                                case 'rechazada': $estado_class = 'bg-danger'; break;
                                                case 'expirada': $estado_class = 'bg-secondary'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($cot['estado']); ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewCotizacion(<?php echo $cot['id']; ?>)" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="editCotizacion(<?php echo $cot['id']; ?>)" title="Editar cotización">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="exportCotizacionPDF(<?php echo $cot['id']; ?>)" title="Exportar PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="sendCotizacionEmail(<?php echo $cot['id']; ?>)" title="Enviar por correo">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay cotizaciones</h5>
                            <p class="text-muted">Este paciente aún no tiene cotizaciones registradas.</p>
                            <button class="btn btn-warning" onclick="createCotizacion(<?php echo $patient_id; ?>)">
                                <i class="fas fa-plus me-2"></i>Crear Primera Cotización
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.diagnosis-text, .treatment-text, .medications-text {
    max-width: 200px;
    word-wrap: break-word;
}

.table td {
    vertical-align: middle;
}
</style>

<script>
function viewHistoryDetails(historyId) {
    // Esta función se puede implementar para mostrar detalles completos
    alert('Ver detalles del registro ID: ' + historyId + '\nEsta funcionalidad se puede expandir para mostrar el registro completo.');
}

function editHistory(historyId) {
    // Esta función se puede implementar para editar registros
    alert('Editar registro ID: ' + historyId + '\nEsta funcionalidad se puede implementar para editar registros existentes.');
}

function createCotizacion(patientId) {
    // Abrir modal para crear nueva cotización
    document.getElementById('newCotizacionPatientId').value = patientId;
    var cotizacionModal = new bootstrap.Modal(document.getElementById('newCotizacionModal'));
    cotizacionModal.show();
}

function viewCotizacion(cotizacionId) {
    // Cargar detalles de la cotización
    fetch('get_cotizacion_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'cotizacion_id=' + cotizacionId
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('cotizacionDetailsContent').innerHTML = data;
        var modal = new bootstrap.Modal(document.getElementById('cotizacionDetailsModal'));
        modal.show();
    })
    .catch(error => {
        alert('Error al cargar los detalles de la cotización.');
    });
}

function editCotizacion(cotizacionId) {
    // Cargar datos de la cotización para editar
    fetch('get_cotizacion_for_edit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'cotizacion_id=' + cotizacionId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('edit_cotizacion_id').value = cotizacionId;
            document.getElementById('edit_fecha_vencimiento').value = data.fecha_vencimiento || '';
            document.getElementById('edit_notas').value = data.notas || '';
            
            var editModal = new bootstrap.Modal(document.getElementById('editCotizacionModal'));
            editModal.show();
        } else {
            alert('Error al cargar los datos de la cotización.');
        }
    })
    .catch(error => {
        alert('Error al cargar los datos de la cotización.');
    });
}

function exportCotizacionPDF(cotizacionId) {
    // Crear enlace para descargar PDF
    const link = document.createElement('a');
    link.href = 'export_cotizacion_pdf.php?cotizacion_id=' + cotizacionId;
    link.download = 'cotizacion_' + cotizacionId + '.pdf';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function sendCotizacionEmail(cotizacionId) {
    // Mostrar modal para enviar correo
    document.getElementById('email_cotizacion_id').value = cotizacionId;
    var emailModal = new bootstrap.Modal(document.getElementById('sendEmailModal'));
    emailModal.show();
}
</script>

<!-- Modal para enviar cotización por correo -->
<div class="modal fade" id="sendEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="sendEmailForm">
                <input type="hidden" name="action" value="send_cotizacion_email">
                <input type="hidden" name="cotizacion_id" id="email_cotizacion_id">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-envelope me-2"></i>Enviar Cotización por Correo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="email_destinatario" class="form-label">Correo Destinatario</label>
                        <input type="email" class="form-control" id="email_destinatario" name="email_destinatario" required placeholder="ejemplo@correo.com">
                        <div class="form-text">Ingresa el correo electrónico donde se enviará la cotización</div>
                    </div>
                    <div class="mb-3">
                        <label for="email_asunto" class="form-label">Asunto del Correo</label>
                        <input type="text" class="form-control" id="email_asunto" name="email_asunto" value="Cotización Médica" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_mensaje" class="form-label">Mensaje Adicional</label>
                        <textarea class="form-control" id="email_mensaje" name="email_mensaje" rows="4" placeholder="Mensaje opcional que se incluirá en el correo..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> La cotización se enviará como archivo PDF adjunto al correo electrónico.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Correo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
