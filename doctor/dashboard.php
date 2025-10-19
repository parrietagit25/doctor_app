<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verificar autenticación y tipo de usuario
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$appointment = new Appointment($db);

// Obtener citas del doctor
$stmt_appointments = $appointment->obtenerPorDoctor($_SESSION['user_id']);
$appointments = [];
if($stmt_appointments) {
    while($row = $stmt_appointments->fetch(PDO::FETCH_ASSOC)) {
        $appointments[] = $row;
    }
}

// Obtener información del doctor
$doctor_info = $user->obtenerPorId($_SESSION['user_id']);

// Calcular estadísticas del doctor
$total_citas = count($appointments);
$citas_realizadas = 0;
$citas_pendientes = 0;
$citas_canceladas = 0;
$no_se_presento = 0;

foreach($appointments as $appointment) {
    switch($appointment['status']) {
        case 'cita_realizada': $citas_realizadas++; break;
        case 'cita_creada': $citas_pendientes++; break;
        case 'cita_cancelada': $citas_canceladas++; break;
        case 'no_se_presento': $no_se_presento++; break;
    }
}

// Citas de este mes
$mes_actual = date('Y-m');
$citas_este_mes = 0;
foreach($appointments as $appointment) {
    if(date('Y-m', strtotime($appointment['fecha_cita'])) === $mes_actual) {
        $citas_este_mes++;
    }
}

// Citas de esta semana
$inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fin_semana = date('Y-m-d', strtotime('sunday this week'));
$citas_esta_semana = 0;
foreach($appointments as $appointment) {
    if($appointment['fecha_cita'] >= $inicio_semana && $appointment['fecha_cita'] <= $fin_semana) {
        $citas_esta_semana++;
    }
}

// Citas de hoy
$hoy = date('Y-m-d');
$citas_hoy = 0;
foreach($appointments as $appointment) {
    if($appointment['fecha_cita'] === $hoy) {
        $citas_hoy++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Doctor - Sistema de Citas Médicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <h4 class="text-white"><i class="fas fa-user-md me-2"></i>Doctor</h4>
                        <p class="text-white-50">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                        <?php if($doctor_info && isset($doctor_info->especialidad) && !empty($doctor_info->especialidad)): ?>
                        <small class="text-white-50"><?php echo htmlspecialchars($doctor_info->especialidad); ?></small>
                        <?php else: ?>
                        <small class="text-white-50">Especialidad no definida</small>
                        <?php endif; ?>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="citas.php">
                                <i class="fas fa-calendar-check me-2"></i>Mis Citas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="calendario.php">
                                <i class="fas fa-calendar-alt me-2"></i>Calendario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="mis_pacientes.php">
                                <i class="fas fa-user-injured me-2"></i>Pacientes
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
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Citas</h6>
                                        <h3 class="mb-0"><?php echo $total_citas; ?></h3>
                                        <small class="text-white-50">Historial completo</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Citas Realizadas</h6>
                                        <h3 class="mb-0"><?php echo $citas_realizadas; ?></h3>
                                        <small class="text-white-50">Completadas</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Citas Pendientes</h6>
                                        <h3 class="mb-0"><?php echo $citas_pendientes; ?></h3>
                                        <small class="text-white-50">Por realizar</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Citas Canceladas</h6>
                                        <h3 class="mb-0"><?php echo $citas_canceladas; ?></h3>
                                        <small class="text-white-50">No realizadas</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Citas Hoy</h6>
                                        <h3 class="mb-0"><?php echo $citas_hoy; ?></h3>
                                        <small class="text-white-50">Programadas</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-day fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Esta Semana</h6>
                                        <h3 class="mb-0"><?php echo $citas_esta_semana; ?></h3>
                                        <small class="text-white-50">Citas programadas</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-week fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Este Mes</h6>
                                        <h3 class="mb-0"><?php echo $citas_este_mes; ?></h3>
                                        <small class="text-white-50">Citas programadas</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">No se Presentó</h6>
                                        <h3 class="mb-0"><?php echo $no_se_presento; ?></h3>
                                        <small class="text-dark-50">Ausencias</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-times fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie me-2"></i>Estado de Mis Citas</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="myAppointmentsStatusChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>Mis Citas por Mes</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="myMonthlyAppointmentsChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Today's Appointments -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-day me-2"></i>Citas de Hoy</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $today = date('Y-m-d');
                                $today_appointments = [];
                                if(is_array($appointments)) {
                                    $today_appointments = array_filter($appointments, function($a) use ($today) { 
                                        return isset($a['fecha_cita']) && isset($a['status']) && $a['fecha_cita'] == $today && $a['status'] != 'cita_cancelada'; 
                                    });
                                }
                                ?>
                                <?php if(empty($today_appointments)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-calendar-day fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No tienes citas programadas para hoy.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Hora</th>
                                                    <th>Paciente</th>
                                                    <th>Motivo</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                // Ordenar por hora
                                                usort($today_appointments, function($a, $b) {
                                                    return strcmp($a['hora_cita'], $b['hora_cita']);
                                                });
                                                
                                                foreach($today_appointments as $appointment_item): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $appointment_item['hora_cita']; ?></strong>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo $appointment_item['paciente_nombre'] . ' ' . $appointment_item['paciente_apellido']; ?></strong>
                                                        <br><small class="text-muted"><?php echo $appointment_item['paciente_email']; ?></small>
                                                    </td>
                                                    <td><?php echo substr($appointment_item['motivo'], 0, 50) . '...'; ?></td>
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
                                                        <button class="btn btn-sm btn-outline-success" onclick="startConsultation(<?php echo $appointment_item['id']; ?>)">
                                                            <i class="fas fa-stethoscope"></i>
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
                    </div>

                    <!-- Quick Actions & Upcoming -->
                    <div class="col-md-4 mb-4">
                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="citas.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-check me-2"></i>Ver Todas las Citas
                                    </a>
                                    <a href="mis_pacientes.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-injured me-2"></i>Mis Pacientes
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Appointments -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-plus me-2"></i>Próximas Citas</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $upcoming = [];
                                if(is_array($appointments)) {
                                    $upcoming = array_filter($appointments, function($a) use ($today) { 
                                        return isset($a['fecha_cita']) && isset($a['status']) && $a['fecha_cita'] > $today && $a['status'] == 'cita_creada'; 
                                    });
                                    
                                    // Ordenar por fecha y hora
                                    usort($upcoming, function($a, $b) {
                                        $dateA = strtotime($a['fecha_cita'] . ' ' . $a['hora_cita']);
                                        $dateB = strtotime($b['fecha_cita'] . ' ' . $b['hora_cita']);
                                        return $dateA - $dateB;
                                    });
                                    
                                    $upcoming = array_slice($upcoming, 0, 3);
                                }
                                ?>
                                <?php if(empty($upcoming)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay citas próximas.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($upcoming as $upcoming_appointment): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <strong><?php echo htmlspecialchars($upcoming_appointment['paciente_nombre'] . ' ' . $upcoming_appointment['paciente_apellido']); ?></strong>
                                            <br><small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($upcoming_appointment['fecha_cita'])); ?> 
                                                <?php echo $upcoming_appointment['hora_cita']; ?>
                                            </small>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewAppointment(<?php echo $upcoming_appointment['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
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

    <!-- Consultation Modal -->
    <div class="modal fade" id="consultationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Consultar Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="consultationContent">
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

        function startConsultation(appointmentId) {
            // This would typically load consultation form via AJAX
            document.getElementById('consultationContent').innerHTML = '<p>Cargando formulario de consulta...</p>';
            
            var consultationModal = new bootstrap.Modal(document.getElementById('consultationModal'));
            consultationModal.show();
            
            // For now, show a placeholder
            setTimeout(function() {
                document.getElementById('consultationContent').innerHTML = 
                    '<p><strong>Formulario de consulta:</strong></p>' +
                    '<p>Cita ID: ' + appointmentId + '</p>' +
                    '<p>Esta funcionalidad se completará en la siguiente iteración.</p>';
            }, 500);
        }

        // Gráfica de estado de citas del doctor (Pie Chart)
        const myAppointmentsStatusCtx = document.getElementById('myAppointmentsStatusChart').getContext('2d');
        new Chart(myAppointmentsStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Realizadas', 'Pendientes', 'Canceladas', 'No se presentó'],
                datasets: [{
                    data: [<?php echo $citas_realizadas; ?>, <?php echo $citas_pendientes; ?>, <?php echo $citas_canceladas; ?>, <?php echo $no_se_presento; ?>],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6c757d'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfica de citas por mes del doctor (Bar Chart)
        const myMonthlyAppointmentsCtx = document.getElementById('myMonthlyAppointmentsChart').getContext('2d');
        
        // Obtener datos de los últimos 6 meses
        const months = [];
        const myAppointmentsData = [];
        
        for(let i = 5; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            months.push(date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' }));
            
            // Simular datos (en una implementación real, obtendrías estos datos del servidor)
            myAppointmentsData.push(Math.floor(Math.random() * 15) + 2);
        }
        
        new Chart(myMonthlyAppointmentsCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Mis Citas',
                    data: myAppointmentsData,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
