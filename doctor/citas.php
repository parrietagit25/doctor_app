<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verificar autenticación y tipo de usuario
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

$message = '';
$error = '';

// Procesar acciones
if($_POST && isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'update_status':
            $appointment->id = $_POST['appointment_id'];
            $appointment->status = $_POST['status'];
            
            if($appointment->actualizar()) {
                $message = 'Estado de la cita actualizado exitosamente.';
            } else {
                $error = 'Error al actualizar el estado de la cita.';
            }
            break;
    }
}

// Obtener citas del doctor
$stmt = $appointment->obtenerPorDoctor($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - Sistema de Citas Médicas</title>
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
                            <a class="nav-link active" href="citas.php">
                                <i class="fas fa-calendar-check me-2"></i>Mis Citas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="mis_pacientes.php">
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
                    <h1 class="h2">Mis Citas Médicas</h1>
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

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Paciente</th>
                                        <th>Email</th>
                                        <th>Motivo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['fecha_cita'])); ?></td>
                                        <td><?php echo $row['hora_cita']; ?></td>
                                        <td>
                                            <strong><?php echo $row['paciente_nombre'] . ' ' . $row['paciente_apellido']; ?></strong>
                                        </td>
                                        <td><?php echo $row['paciente_email']; ?></td>
                                        <td><?php echo substr($row['motivo'], 0, 50) . '...'; ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch($row['status']) {
                                                case 'cita_creada': $status_class = 'bg-warning'; break;
                                                case 'cita_realizada': $status_class = 'bg-success'; break;
                                                case 'no_se_presento': $status_class = 'bg-danger'; break;
                                                case 'cita_cancelada': $status_class = 'bg-secondary'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewAppointment(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="updateStatus(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="startConsultation(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-stethoscope"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="appointment_id" id="update_appointment_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Actualizar Estado de la Cita</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Nuevo Estado</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="cita_creada">Cita Creada</option>
                                <option value="cita_realizada">Cita Realizada</option>
                                <option value="no_se_presento">No se Presentó</option>
                                <option value="cita_cancelada">Cita Cancelada</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewAppointment(appointmentId) {
            // This would typically load appointment details via AJAX
            alert('Ver detalles de la cita ID: ' + appointmentId + '\nEsta funcionalidad se completará en la siguiente iteración.');
        }

        function updateStatus(appointmentId, currentStatus) {
            document.getElementById('update_appointment_id').value = appointmentId;
            document.getElementById('status').value = currentStatus;
            
            var updateModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            updateModal.show();
        }

        function startConsultation(appointmentId) {
            // This would typically load consultation form via AJAX
            alert('Iniciar consulta para cita ID: ' + appointmentId + '\nEsta funcionalidad se completará en la siguiente iteración.');
        }
    </script>
</body>
</html>
