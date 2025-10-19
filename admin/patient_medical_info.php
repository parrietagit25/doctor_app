<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/PatientMedicalInfo.php';
require_once __DIR__ . '/../classes/MedicalHistory.php';

// Verificar autenticación y tipo de usuario
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ../index.php');
    exit();
}

$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($patient_id <= 0) {
    header('Location: pacientes.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$patientInfo = new PatientMedicalInfo($db);
$medicalHistory = new MedicalHistory($db);

$message = '';
$error = '';

// Obtener información del paciente
$patient_data = $user->obtenerPorId($patient_id);
if(!$patient_data || !is_object($patient_data)) {
    header('Location: pacientes.php');
    exit();
}

// Verificar que es un paciente
if($patient_data->tipo_usuario !== 'paciente') {
    header('Location: pacientes.php');
    exit();
}

// Obtener información médica del paciente
$medical_data = $patientInfo->obtenerPorPaciente($patient_id);

// Obtener historial médico del paciente
$history_stmt = $medicalHistory->obtenerPorPaciente($patient_id);
$history_records = [];
if($history_stmt) {
    while($row = $history_stmt->fetch(PDO::FETCH_ASSOC)) {
        $history_records[] = $row;
    }
}

// Procesar formularios
if($_POST) {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'save_medical_info':
                $patientInfo->paciente_id = $patient_id;
                $patientInfo->fecha_nacimiento = $_POST['fecha_nacimiento'];
                $patientInfo->genero = $_POST['genero'];
                $patientInfo->direccion = $_POST['direccion'];
                $patientInfo->emergencia_contacto = $_POST['emergencia_contacto'];
                $patientInfo->emergencia_telefono = $_POST['emergencia_telefono'];
                $patientInfo->grupo_sanguineo = $_POST['grupo_sanguineo'];
                $patientInfo->peso = $_POST['peso'];
                $patientInfo->altura = $_POST['altura'];
                $patientInfo->presion_arterial = $_POST['presion_arterial'];
                $patientInfo->alergias = $_POST['alergias'];
                $patientInfo->enfermedades_cronicas = $_POST['enfermedades_cronicas'];
                $patientInfo->medicamentos_actuales = $_POST['medicamentos_actuales'];
                $patientInfo->cirugias_previas = $_POST['cirugias_previas'];
                $patientInfo->historial_familiar = $_POST['historial_familiar'];

                if($patientInfo->existeParaPaciente($patient_id)) {
                    if($patientInfo->actualizar()) {
                        $message = 'Información médica actualizada exitosamente.';
                    } else {
                        $error = 'Error al actualizar la información médica.';
                    }
                } else {
                    if($patientInfo->crear()) {
                        $message = 'Información médica registrada exitosamente.';
                    } else {
                        $error = 'Error al registrar la información médica.';
                    }
                }

                // Recargar datos
                $medical_data = $patientInfo->obtenerPorPaciente($patient_id);
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información Médica - <?php echo htmlspecialchars($patient_data->nombre . ' ' . $patient_data->apellido); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            margin: 5px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
        }
        .info-card {
            border-left: 4px solid #007bff;
        }
        .medical-card {
            border-left: 4px solid #28a745;
        }
        .history-card {
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white"><i class="fas fa-user-md me-2"></i>Admin Panel</h4>
                        <p class="text-white-50">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios.php">
                                <i class="fas fa-users me-2"></i>Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pacientes.php">
                                <i class="fas fa-user-injured me-2"></i>Pacientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="citas.php">
                                <i class="fas fa-calendar-check me-2"></i>Citas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reportes.php">
                                <i class="fas fa-chart-bar me-2"></i>Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Información Médica del Paciente</h1>
                    <a href="pacientes.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Pacientes
                    </a>
                </div>

                <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Patient Basic Info -->
                <div class="card info-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            Información Personal
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($patient_data->nombre . ' ' . $patient_data->apellido); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_data->email); ?></p>
                                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($patient_data->telefono); ?></p>
                            </div>
                            <div class="col-md-6">
                                <?php if($medical_data): ?>
                                <p><strong>Edad:</strong> <?php echo $patientInfo->calcularEdad($medical_data['fecha_nacimiento']); ?> años</p>
                                <p><strong>Género:</strong> <?php echo ucfirst($medical_data['genero']); ?></p>
                                <p><strong>Fecha de Nacimiento:</strong> <?php echo date('d/m/Y', strtotime($medical_data['fecha_nacimiento'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medical Information Form -->
                <div class="card medical-card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-file-medical me-2"></i>
                            Información Médica
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_medical_info">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                           value="<?php echo $medical_data ? $medical_data['fecha_nacimiento'] : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="genero" class="form-label">Género</label>
                                    <select class="form-control" id="genero" name="genero">
                                        <option value="">Seleccionar...</option>
                                        <option value="masculino" <?php echo ($medical_data && $medical_data['genero'] == 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="femenino" <?php echo ($medical_data && $medical_data['genero'] == 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                                        <option value="otro" <?php echo ($medical_data && $medical_data['genero'] == 'otro') ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo $medical_data ? htmlspecialchars($medical_data['direccion']) : ''; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="emergencia_contacto" class="form-label">Contacto de Emergencia</label>
                                    <input type="text" class="form-control" id="emergencia_contacto" name="emergencia_contacto" 
                                           value="<?php echo $medical_data ? htmlspecialchars($medical_data['emergencia_contacto']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="emergencia_telefono" class="form-label">Teléfono de Emergencia</label>
                                    <input type="tel" class="form-control" id="emergencia_telefono" name="emergencia_telefono" 
                                           value="<?php echo $medical_data ? htmlspecialchars($medical_data['emergencia_telefono']) : ''; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="grupo_sanguineo" class="form-label">Grupo Sanguíneo</label>
                                    <select class="form-control" id="grupo_sanguineo" name="grupo_sanguineo">
                                        <option value="">Seleccionar...</option>
                                        <option value="A+" <?php echo ($medical_data && $medical_data['grupo_sanguineo'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo ($medical_data && $medical_data['grupo_sanguineo'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo ($medical_data && $medical_data['grupo_sanguineo'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo ($medical_data && $medical_data['grupo_sanguineo'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo ($medical_data && $medical_data['grupo_sanguineo'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo ($medical_data && $medical_data['grupo_sanguineo'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo ($medical_data && $medical_data['grupo_sanguineo'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo ($medical_data && $medical_data['grupo_sanguineo'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="peso" class="form-label">Peso (kg)</label>
                                    <input type="number" step="0.1" class="form-control" id="peso" name="peso" 
                                           value="<?php echo $medical_data ? $medical_data['peso'] : ''; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="altura" class="form-label">Altura (cm)</label>
                                    <input type="number" class="form-control" id="altura" name="altura" 
                                           value="<?php echo $medical_data ? $medical_data['altura'] : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="presion_arterial" class="form-label">Presión Arterial</label>
                                <input type="text" class="form-control" id="presion_arterial" name="presion_arterial" 
                                       placeholder="Ej: 120/80" value="<?php echo $medical_data ? htmlspecialchars($medical_data['presion_arterial']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="alergias" class="form-label">Alergias</label>
                                <textarea class="form-control" id="alergias" name="alergias" rows="3" 
                                          placeholder="Liste todas las alergias conocidas..."><?php echo $medical_data ? htmlspecialchars($medical_data['alergias']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="enfermedades_cronicas" class="form-label">Enfermedades Crónicas</label>
                                <textarea class="form-control" id="enfermedades_cronicas" name="enfermedades_cronicas" rows="3" 
                                          placeholder="Diabetes, hipertensión, etc..."><?php echo $medical_data ? htmlspecialchars($medical_data['enfermedades_cronicas']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="medicamentos_actuales" class="form-label">Medicamentos Actuales</label>
                                <textarea class="form-control" id="medicamentos_actuales" name="medicamentos_actuales" rows="3" 
                                          placeholder="Medicamentos que toma actualmente..."><?php echo $medical_data ? htmlspecialchars($medical_data['medicamentos_actuales']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="cirugias_previas" class="form-label">Cirugías Previas</label>
                                <textarea class="form-control" id="cirugias_previas" name="cirugias_previas" rows="3" 
                                          placeholder="Historial de cirugías..."><?php echo $medical_data ? htmlspecialchars($medical_data['cirugias_previas']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="historial_familiar" class="form-label">Historial Familiar</label>
                                <textarea class="form-control" id="historial_familiar" name="historial_familiar" rows="3" 
                                          placeholder="Enfermedades hereditarias, antecedentes familiares..."><?php echo $medical_data ? htmlspecialchars($medical_data['historial_familiar']) : ''; ?></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Guardar Información
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Medical History -->
                <div class="card history-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Historial de Consultas
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($history_records)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay registros en el historial médico.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Doctor</th>
                                            <th>Diagnóstico</th>
                                            <th>Tratamiento</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($history_records as $record): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($record['fecha_consulta'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($record['doctor_nombre'] . ' ' . $record['doctor_apellido']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($record['especialidad']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($record['diagnostico'], 0, 50) . '...'); ?></td>
                                            <td><?php echo htmlspecialchars(substr($record['tratamiento'], 0, 50) . '...'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewHistoryRecord(<?php echo $record['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- History Record Modal -->
    <div class="modal fade" id="historyRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Registro Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="historyRecordContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewHistoryRecord(recordId) {
            // This would typically load history record details via AJAX
            document.getElementById('historyRecordContent').innerHTML = '<p>Cargando detalles del registro...</p>';
            
            var historyRecordModal = new bootstrap.Modal(document.getElementById('historyRecordModal'));
            historyRecordModal.show();
            
            // For now, show a placeholder
            setTimeout(function() {
                document.getElementById('historyRecordContent').innerHTML = 
                    '<p><strong>Registro médico ID:</strong> ' + recordId + '</p>' +
                    '<p>Esta funcionalidad se completará en la siguiente iteración.</p>' +
                    '<p>Aquí se mostrará el diagnóstico completo, tratamiento y medicamentos.</p>';
            }, 500);
        }

        // Auto-calculate BMI when weight or height changes
        document.getElementById('peso').addEventListener('input', calculateBMI);
        document.getElementById('altura').addEventListener('input', calculateBMI);

        function calculateBMI() {
            const peso = parseFloat(document.getElementById('peso').value);
            const altura = parseFloat(document.getElementById('altura').value);
            
            if(peso && altura && altura > 0) {
                const alturaM = altura / 100;
                const imc = peso / (alturaM * alturaM);
                console.log('IMC:', imc.toFixed(2));
            }
        }
    </script>
</body>
</html>
