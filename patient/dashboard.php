<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verificar autenticación y tipo de usuario
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'paciente') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$appointment = new Appointment($db);

// Obtener citas del paciente
$stmt_appointments = $appointment->obtenerPorPaciente($_SESSION['user_id']);
$appointments = [];
if($stmt_appointments) {
    while($row = $stmt_appointments->fetch(PDO::FETCH_ASSOC)) {
        $appointments[] = $row;
    }
}

// Obtener doctores disponibles
$stmt_doctors = $user->obtenerDoctores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Paciente - Sistema de Citas Médicas</title>
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
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
                        <h4 class="text-white"><i class="fas fa-user me-2"></i>Paciente</h4>
                        <p class="text-white-50">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="nueva_cita.php">
                                <i class="fas fa-plus-circle me-2"></i>Nueva Cita
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="mis_citas.php">
                                <i class="fas fa-calendar-check me-2"></i>Mis Citas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="historial.php">
                                <i class="fas fa-file-medical me-2"></i>Historial
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
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Citas</h5>
                                        <h2 class="mb-0"><?php echo is_array($appointments) ? count($appointments) : 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-check fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Citas Pendientes</h5>
                                        <h2 class="mb-0"><?php echo is_array($appointments) ? count(array_filter($appointments, function($a) { return isset($a['status']) && $a['status'] == 'cita_creada'; })) : 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Citas Realizadas</h5>
                                        <h2 class="mb-0"><?php echo is_array($appointments) ? count(array_filter($appointments, function($a) { return isset($a['status']) && $a['status'] == 'cita_realizada'; })) : 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Appointments -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-check me-2"></i>Mis Citas Recientes</h5>
                            </div>
                            <div class="card-body">
                                <?php if(empty($appointments)): ?>
                                    <p class="text-muted">No tienes citas registradas.</p>
                                    <a href="nueva_cita.php" class="btn btn-primary">Solicitar Primera Cita</a>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Doctor</th>
                                                    <th>Fecha</th>
                                                    <th>Hora</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach(array_slice($appointments, 0, 5) as $appointment_item): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $appointment_item['doctor_nombre'] . ' ' . $appointment_item['doctor_apellido']; ?></strong>
                                                        <br><small class="text-muted"><?php echo $appointment_item['especialidad']; ?></small>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($appointment_item['fecha_cita'])); ?></td>
                                                    <td><?php echo $appointment_item['hora_cita']; ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        switch($appointment_item['status']) {
                                                            case 'cita_creada': $status_class = 'bg-warning'; break;
                                                            case 'cita_realizada': $status_class = 'bg-success'; break;
                                                            case 'no_se_presento': $status_class = 'bg-danger'; break;
                                                            case 'cita_cancelada': $status_class = 'bg-secondary'; break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $appointment_item['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewAppointment(<?php echo $appointment_item['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center">
                                        <a href="mis_citas.php" class="btn btn-outline-primary">Ver Todas las Citas</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="nueva_cita.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-2"></i>Solicitar Nueva Cita
                                    </a>
                                    <a href="mis_citas.php" class="btn btn-outline-primary">
                                        <i class="fas fa-calendar-check me-2"></i>Ver Mis Citas
                                    </a>
                                    <a href="historial.php" class="btn btn-outline-info">
                                        <i class="fas fa-file-medical me-2"></i>Ver Historial
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Available Doctors -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fas fa-user-md me-2"></i>Doctores Disponibles</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $doctors = [];
                                while($row = $stmt_doctors->fetch(PDO::FETCH_ASSOC)) {
                                    $doctors[] = $row;
                                }
                                ?>
                                <?php if(empty($doctors)): ?>
                                    <p class="text-muted">No hay doctores disponibles.</p>
                                <?php else: ?>
                                    <?php foreach(array_slice($doctors, 0, 3) as $doctor): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?php echo $doctor['nombre'] . ' ' . $doctor['apellido']; ?></strong>
                                            <br><small class="text-muted"><?php echo $doctor['especialidad']; ?></small>
                                        </div>
                                        <a href="nueva_cita.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-calendar-plus"></i>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="nueva_cita.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="appointmentDetails">
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
        function viewAppointment(appointmentId) {
            // This would typically load appointment details via AJAX
            document.getElementById('appointmentDetails').innerHTML = '<p>Cargando detalles de la cita...</p>';
            
            var appointmentModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
            appointmentModal.show();
            
            // For now, show a placeholder
            setTimeout(function() {
                document.getElementById('appointmentDetails').innerHTML = 
                    '<p><strong>Detalles de la cita:</strong></p>' +
                    '<p>ID: ' + appointmentId + '</p>' +
                    '<p>Esta funcionalidad se completará en la siguiente iteración.</p>';
            }, 500);
        }
    </script>
</body>
</html>
