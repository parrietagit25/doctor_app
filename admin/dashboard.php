<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verificar autenticación y tipo de usuario
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$appointment = new Appointment($db);

// Obtener estadísticas
$stmt_users = $user->obtenerTodos();
$total_users = $stmt_users ? $stmt_users->rowCount() : 0;

$stmt_appointments = $appointment->obtenerTodas();
$total_appointments = $stmt_appointments ? $stmt_appointments->rowCount() : 0;

// Obtener estadísticas adicionales
$total_doctores = $user->obtenerDoctores() ? $user->obtenerDoctores()->rowCount() : 0;
$total_pacientes = $user->obtenerTodos() ? $user->obtenerTodos()->rowCount() : 0;

// Citas por estado
$citas_creadas = 0;
$citas_realizadas = 0;
$citas_canceladas = 0;
$no_se_presento = 0;

if($stmt_appointments) {
    $appointments_data = $stmt_appointments->fetchAll(PDO::FETCH_ASSOC);
    foreach($appointments_data as $app) {
        switch($app['status']) {
            case 'cita_creada': $citas_creadas++; break;
            case 'cita_realizada': $citas_realizadas++; break;
            case 'cita_cancelada': $citas_canceladas++; break;
            case 'no_se_presento': $no_se_presento++; break;
        }
    }
}

// Citas de este mes
$mes_actual = date('Y-m');
$stmt_citas_mes = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE DATE_FORMAT(fecha_cita, '%Y-%m') = ?");
$stmt_citas_mes->execute([$mes_actual]);
$citas_este_mes = $stmt_citas_mes->fetch(PDO::FETCH_ASSOC)['total'];

// Citas de la semana
$inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fin_semana = date('Y-m-d', strtotime('sunday this week'));
$stmt_citas_semana = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE fecha_cita BETWEEN ? AND ?");
$stmt_citas_semana->execute([$inicio_semana, $fin_semana]);
$citas_esta_semana = $stmt_citas_semana->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener usuarios recientes
$users_recent = [];
if($stmt_users) {
    $count = 0;
    while(($row = $stmt_users->fetch(PDO::FETCH_ASSOC)) !== false && $count < 5) {
        $users_recent[] = $row;
        $count++;
    }
}

// Obtener citas recientes
$appointments_recent = [];
if($stmt_appointments) {
    $count = 0;
    while(($row = $stmt_appointments->fetch(PDO::FETCH_ASSOC)) !== false && $count < 5) {
        $appointments_recent[] = $row;
        $count++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Sistema de Citas Médicas</title>
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
                        <h4 class="text-white"><i class="fas fa-user-md me-2"></i>Admin Panel</h4>
                        <p class="text-white-50">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="calendario.php">
                                <i class="fas fa-calendar-alt me-2"></i>Calendario
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
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Usuarios</h6>
                                        <h3 class="mb-0"><?php echo $total_users; ?></h3>
                                        <small class="text-white-50">Sistema completo</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
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
                                        <h6 class="card-title">Doctores</h6>
                                        <h3 class="mb-0"><?php echo $total_doctores; ?></h3>
                                        <small class="text-white-50">Activos</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-md fa-2x"></i>
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
                                        <h6 class="card-title">Pacientes</h6>
                                        <h3 class="mb-0"><?php echo $total_pacientes; ?></h3>
                                        <small class="text-white-50">Registrados</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-injured fa-2x"></i>
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
                                        <h6 class="card-title">Total Citas</h6>
                                        <h3 class="mb-0"><?php echo $total_appointments; ?></h3>
                                        <small class="text-white-50">Historial completo</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-check fa-2x"></i>
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
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Citas Pendientes</h6>
                                        <h3 class="mb-0"><?php echo $citas_creadas; ?></h3>
                                        <small class="text-dark-50">Por realizar</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
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
                        <div class="card bg-primary text-white">
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
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie me-2"></i>Estado de Citas</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="appointmentsStatusChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>Citas por Mes (Últimos 6 meses)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyAppointmentsChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-users me-2"></i>Usuarios Recientes</h5>
                            </div>
                            <div class="card-body">
                                <?php if(empty($users_recent)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay usuarios registrados.</p>
                                        <a href="usuarios.php" class="btn btn-outline-primary btn-sm">Gestionar Usuarios</a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Email</th>
                                                    <th>Tipo</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($users_recent as $user_recent): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user_recent['nombre'] . ' ' . $user_recent['apellido']); ?></td>
                                                    <td><?php echo htmlspecialchars($user_recent['email']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user_recent['tipo_usuario'] == 'administrador' ? 'danger' : ($user_recent['tipo_usuario'] == 'doctor' ? 'primary' : 'success'); ?>">
                                                            <?php echo ucfirst($user_recent['tipo_usuario']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($user_recent['fecha_registro'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center">
                                        <a href="usuarios.php" class="btn btn-outline-primary btn-sm">Ver Todos</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Appointments -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-check me-2"></i>Citas Recientes</h5>
                            </div>
                            <div class="card-body">
                                <?php if(empty($appointments_recent)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay citas registradas.</p>
                                        <a href="citas.php" class="btn btn-outline-primary btn-sm">Gestionar Citas</a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Paciente</th>
                                                    <th>Doctor</th>
                                                    <th>Fecha</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($appointments_recent as $appointment_recent): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($appointment_recent['paciente_nombre'] . ' ' . $appointment_recent['paciente_apellido']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment_recent['doctor_nombre'] . ' ' . $appointment_recent['doctor_apellido']); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($appointment_recent['fecha_cita'])) . ' ' . $appointment_recent['hora_cita']; ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        switch($appointment_recent['status']) {
                                                            case 'cita_creada': $status_class = 'bg-warning'; break;
                                                            case 'cita_realizada': $status_class = 'bg-success'; break;
                                                            case 'no_se_presento': $status_class = 'bg-danger'; break;
                                                            case 'cita_cancelada': $status_class = 'bg-secondary'; break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $appointment_recent['status'])); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center">
                                        <a href="citas.php" class="btn btn-outline-primary btn-sm">Ver Todas</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Gráfica de estado de citas (Pie Chart)
        const appointmentsStatusCtx = document.getElementById('appointmentsStatusChart').getContext('2d');
        new Chart(appointmentsStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Realizadas', 'Pendientes', 'Canceladas', 'No se presentó'],
                datasets: [{
                    data: [<?php echo $citas_realizadas; ?>, <?php echo $citas_creadas; ?>, <?php echo $citas_canceladas; ?>, <?php echo $no_se_presento; ?>],
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

        // Gráfica de citas por mes (Bar Chart)
        const monthlyAppointmentsCtx = document.getElementById('monthlyAppointmentsChart').getContext('2d');
        
        // Obtener datos de los últimos 6 meses
        const months = [];
        const appointmentsData = [];
        
        for(let i = 5; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            const monthStr = date.toISOString().substr(0, 7);
            months.push(date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' }));
            
            // Simular datos (en una implementación real, obtendrías estos datos del servidor)
            appointmentsData.push(Math.floor(Math.random() * 20) + 5);
        }
        
        new Chart(monthlyAppointmentsCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Citas',
                    data: appointmentsData,
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
