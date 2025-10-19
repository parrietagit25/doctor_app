<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verificar autenticación y tipo de usuario
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'paciente') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

// Obtener citas del paciente
$stmt = $appointment->obtenerPorPaciente($_SESSION['user_id']);
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
                        <h4 class="text-white"><i class="fas fa-user me-2"></i>Paciente</h4>
                        <p class="text-white-50">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="nueva_cita.php">
                                <i class="fas fa-plus-circle me-2"></i>Nueva Cita
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="mis_citas.php">
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
                    <h1 class="h2">Mis Citas Médicas</h1>
                    <a href="nueva_cita.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nueva Cita
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Doctor</th>
                                        <th>Especialidad</th>
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
                                            <strong><?php echo $row['doctor_nombre'] . ' ' . $row['doctor_apellido']; ?></strong>
                                        </td>
                                        <td><?php echo $row['especialidad']; ?></td>
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
                                            <?php if($row['status'] == 'cita_creada'): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="cancelAppointment(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
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

        function cancelAppointment(appointmentId) {
            if(confirm('¿Está seguro de que desea cancelar esta cita?')) {
                // This would typically send a request to cancel the appointment
                alert('Funcionalidad de cancelación será implementada en la siguiente iteración.');
            }
        }
    </script>
</body>
</html>
