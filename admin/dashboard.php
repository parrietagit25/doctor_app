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
                    <div class="col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Usuarios</h5>
                                        <h2 class="mb-0"><?php echo $total_users; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Citas</h5>
                                        <h2 class="mb-0"><?php echo $total_appointments; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-check fa-3x"></i>
                                    </div>
                                </div>
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
</body>
</html>
