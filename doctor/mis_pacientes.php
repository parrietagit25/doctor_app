<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/MedicalHistory.php';

// Verificar autenticación y tipo de usuario
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$medicalHistory = new MedicalHistory($db);

$message = '';
$error = '';

// Procesar acciones
if($_POST && isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'create_history':
            $medicalHistory->paciente_id = $_POST['paciente_id'];
            $medicalHistory->doctor_id = $_SESSION['user_id'];
            $medicalHistory->cita_id = $_POST['cita_id'];
            $medicalHistory->diagnostico = $_POST['diagnostico'];
            $medicalHistory->tratamiento = $_POST['tratamiento'];
            $medicalHistory->medicamentos = $_POST['medicamentos'];
            $medicalHistory->notas_adicionales = $_POST['notas_adicionales'];
            
            if($medicalHistory->crear()) {
                $message = 'Historial médico registrado exitosamente.';
            } else {
                $error = 'Error al registrar el historial médico.';
            }
            break;
    }
}

// Obtener solo pacientes que han tenido citas con este doctor
$query = "SELECT DISTINCT u.id, u.nombre, u.apellido, u.email, u.telefono, 
                 COUNT(c.id) as total_citas,
                 MAX(c.fecha_cita) as ultima_cita
          FROM usuarios u
          INNER JOIN citas c ON u.id = c.paciente_id
          WHERE u.tipo_usuario = 'paciente' 
          AND u.activo = 1 
          AND c.doctor_id = :doctor_id
          GROUP BY u.id, u.nombre, u.apellido, u.email, u.telefono
          ORDER BY ultima_cita DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':doctor_id', $_SESSION['user_id']);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pacientes - Sistema de Citas Médicas</title>
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
        .patient-card {
            transition: transform 0.2s;
        }
        .patient-card:hover {
            transform: translateY(-2px);
        }
        .stats-badge {
            font-size: 0.8rem;
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
                        <h4 class="text-white"><i class="fas fa-user-md me-2"></i>Doctor</h4>
                        <p class="text-white-50">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="citas.php">
                                <i class="fas fa-calendar-check me-2"></i>Mis Citas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="mis_pacientes.php">
                                <i class="fas fa-user-injured me-2"></i>Mis Pacientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="consultas.php">
                                <i class="fas fa-stethoscope me-2"></i>Consultas
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
                    <h1 class="h2">Mis Pacientes</h1>
                    <div class="text-muted">
                        <small>Pacientes que han tenido citas contigo</small>
                    </div>
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

                <!-- Patients Grid -->
                <div class="row">
                    <?php while($patient = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card patient-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-injured me-2"></i>
                                    <?php echo htmlspecialchars($patient['nombre'] . ' ' . $patient['apellido']); ?>
                                </h5>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark stats-badge">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        <?php echo $patient['total_citas']; ?> citas
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text">
                                    <i class="fas fa-envelope me-2"></i>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?><br>
                                    <i class="fas fa-phone me-2"></i>
                                    <strong>Teléfono:</strong> <?php echo htmlspecialchars($patient['telefono']); ?><br>
                                    <i class="fas fa-calendar me-2"></i>
                                    <strong>Última cita:</strong> <?php echo date('d/m/Y', strtotime($patient['ultima_cita'])); ?>
                                </p>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100" role="group">
                                    <button class="btn btn-outline-primary btn-sm" onclick="viewPatientHistory(<?php echo $patient['id']; ?>)">
                                        <i class="fas fa-file-medical me-1"></i>Historial
                                    </button>
                                    <button class="btn btn-outline-success btn-sm" onclick="addMedicalHistory(<?php echo $patient['id']; ?>)">
                                        <i class="fas fa-plus me-1"></i>Agregar
                                    </button>
                                    <a href="patient_medical_info.php?id=<?php echo $patient['id']; ?>" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-eye me-1"></i>Ver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <?php if($stmt->rowCount() == 0): ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-injured fa-5x text-muted mb-4"></i>
                    <h3 class="text-muted">No tienes pacientes asignados</h3>
                    <p class="text-muted">Los pacientes aparecerán aquí una vez que tengan citas contigo.</p>
                    <a href="citas.php" class="btn btn-primary">
                        <i class="fas fa-calendar-check me-2"></i>Ver Mis Citas
                    </a>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Patient History Modal -->
    <div class="modal fade" id="patientHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Historial Médico del Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="patientHistoryContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Medical History Modal -->
    <div class="modal fade" id="addHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="create_history">
                    <input type="hidden" name="paciente_id" id="add_paciente_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Registro al Historial Médico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cita_id" class="form-label">ID de Cita (Opcional)</label>
                            <input type="number" class="form-control" id="cita_id" name="cita_id" placeholder="Dejar vacío si no está asociado a una cita">
                        </div>
                        <div class="mb-3">
                            <label for="diagnostico" class="form-label">Diagnóstico</label>
                            <textarea class="form-control" id="diagnostico" name="diagnostico" rows="3" required placeholder="Describe el diagnóstico..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="tratamiento" class="form-label">Tratamiento</label>
                            <textarea class="form-control" id="tratamiento" name="tratamiento" rows="3" placeholder="Describe el tratamiento prescrito..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="medicamentos" class="form-label">Medicamentos</label>
                            <textarea class="form-control" id="medicamentos" name="medicamentos" rows="2" placeholder="Lista de medicamentos prescritos..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="notas_adicionales" class="form-label">Notas Adicionales</label>
                            <textarea class="form-control" id="notas_adicionales" name="notas_adicionales" rows="3" placeholder="Observaciones adicionales..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewPatientHistory(patientId) {
            // This would typically load patient history via AJAX
            document.getElementById('patientHistoryContent').innerHTML = '<p>Cargando historial médico...</p>';
            
            var patientHistoryModal = new bootstrap.Modal(document.getElementById('patientHistoryModal'));
            patientHistoryModal.show();
            
            // For now, show a placeholder
            setTimeout(function() {
                document.getElementById('patientHistoryContent').innerHTML = 
                    '<p><strong>Historial médico del paciente ID:</strong> ' + patientId + '</p>' +
                    '<p>Esta funcionalidad se completará en la siguiente iteración.</p>' +
                    '<p>Aquí se mostrará el historial completo de consultas, diagnósticos y tratamientos.</p>';
            }, 500);
        }

        function addMedicalHistory(patientId) {
            document.getElementById('add_paciente_id').value = patientId;
            
            var addHistoryModal = new bootstrap.Modal(document.getElementById('addHistoryModal'));
            addHistoryModal.show();
        }
    </script>
</body>
</html>
