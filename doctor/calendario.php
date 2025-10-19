<?php
session_start();

// Verificar si el usuario está logueado y es doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);
$user = new User($db);

// Obtener el mes y año actual o de la URL
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validar mes y año
if ($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = date('n');
}
if ($currentYear < 2020 || $currentYear > 2030) {
    $currentYear = date('Y');
}

// Calcular el primer día del mes y el número de días
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$lastDay = mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear);
$daysInMonth = date('t', $firstDay);
$startDayOfWeek = date('w', $firstDay);

// Obtener citas del doctor para el mes
$appointments = $appointment->obtenerCitasPorMesDoctor($_SESSION['user_id'], $currentMonth, $currentYear);

// Nombres de los meses en español
$monthNames = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Nombres de los días de la semana
$dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$dayNamesShort = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Calendario - Sistema de Citas Médicas</title>
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
            background-color: rgba(255,255,255,0.1);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .calendar-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .calendar-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .calendar-nav button {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .calendar-nav button:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .calendar-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: #e9ecef;
        }
        .calendar-day-header {
            background-color: #f8f9fa;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        .calendar-day {
            background-color: white;
            min-height: 120px;
            padding: 10px;
            border: 1px solid #e9ecef;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .calendar-day:hover {
            background-color: #f8f9fa;
        }
        .calendar-day.other-month {
            background-color: #f8f9fa;
            color: #adb5bd;
        }
        .calendar-day.today {
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
        }
        .day-number {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .appointment-item {
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            margin: 1px 0;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .appointment-item:hover {
            background-color: #0056b3;
            transform: scale(1.02);
        }
        .appointment-item.urgent {
            background-color: #dc3545;
        }
        .appointment-item.urgent:hover {
            background-color: #c82333;
        }
        .appointment-item.completed {
            background-color: #28a745;
        }
        .appointment-item.completed:hover {
            background-color: #218838;
        }
        .appointment-item.cancelled {
            background-color: #6c757d;
        }
        .appointment-item.cancelled:hover {
            background-color: #5a6268;
        }
        .appointment-item.no-show {
            background-color: #ffc107;
            color: #000;
        }
        .appointment-item.no-show:hover {
            background-color: #e0a800;
        }
        .appointment-item.normal {
            background-color: #007bff;
        }
        .appointment-item.normal:hover {
            background-color: #0056b3;
        }
        .appointment-count {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        .legend-color.blue { background-color: #007bff; }
        .legend-color.green { background-color: #28a745; }
        .legend-color.grey { background-color: #6c757d; }
        .legend-color.yellow { background-color: #ffc107; }
        
        @media (max-width: 768px) {
            .calendar-day {
                min-height: 80px;
                padding: 5px;
            }
            .appointment-item {
                font-size: 0.65rem;
                padding: 1px 4px;
            }
            .calendar-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h4 class="text-white mb-4">
                            <i class="fas fa-calendar-alt me-2"></i>Mi Calendario
                        </h4>
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
                                <a class="nav-link active" href="calendario.php">
                                    <i class="fas fa-calendar-alt me-2"></i>Calendario
                                </a>
                            </li>
                            <li class="nav-item">
                            <a class="nav-link" href="mis_pacientes.php">
                                <i class="fas fa-user-injured me-2"></i>Pacientes
                            </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a class="nav-link" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <div class="calendar-container">
                        <!-- Calendar Header -->
                        <div class="calendar-header">
                            <div class="calendar-nav">
                                <a href="?month=<?php echo $currentMonth == 1 ? 12 : $currentMonth - 1; ?>&year=<?php echo $currentMonth == 1 ? $currentYear - 1 : $currentYear; ?>" 
                                   class="btn">
                                    <i class="fas fa-chevron-left me-1"></i>Mes Anterior
                                </a>
                                <h1 class="calendar-title">
                                    <?php echo $monthNames[$currentMonth] . ' ' . $currentYear; ?>
                                </h1>
                                <a href="?month=<?php echo $currentMonth == 12 ? 1 : $currentMonth + 1; ?>&year=<?php echo $currentMonth == 12 ? $currentYear + 1 : $currentYear; ?>" 
                                   class="btn">
                                    Mes Siguiente<i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            </div>
                            
                            <!-- Quick Navigation -->
                            <div class="text-center">
                                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                                   class="btn btn-outline-light btn-sm me-2">
                                    <i class="fas fa-home me-1"></i>Mes Actual
                                </a>
                                <span class="text-white-50">|</span>
                                <a href="citas.php" class="btn btn-outline-light btn-sm ms-2">
                                    <i class="fas fa-plus me-1"></i>Nueva Cita
                                </a>
                            </div>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="calendar-grid">
                            <!-- Day Headers -->
                            <?php foreach ($dayNamesShort as $day): ?>
                                <div class="calendar-day-header"><?php echo $day; ?></div>
                            <?php endforeach; ?>

                            <!-- Calendar Days -->
                            <?php
                            $today = date('Y-m-d');
                            $dayCounter = 1;
                            
                            // Días del mes anterior
                            for ($i = 0; $i < $startDayOfWeek; $i++): 
                                $prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
                                $prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
                                $prevMonthDays = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));
                                $dayNumber = $prevMonthDays - $startDayOfWeek + $i + 1;
                            ?>
                                <div class="calendar-day other-month">
                                    <div class="day-number"><?php echo $dayNumber; ?></div>
                                </div>
                            <?php endfor; ?>

                            <!-- Días del mes actual -->
                            <?php for ($day = 1; $day <= $daysInMonth; $day++): 
                                $currentDate = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                $isToday = $currentDate === $today;
                                $dayAppointments = isset($appointments[$day]) ? $appointments[$day] : [];
                            ?>
                                <div class="calendar-day <?php echo $isToday ? 'today' : ''; ?>" 
                                     data-date="<?php echo $currentDate; ?>">
                                    <div class="day-number"><?php echo $day; ?></div>
                                    
                                    <?php if (count($dayAppointments) > 0): ?>
                                        <div class="appointment-count"><?php echo count($dayAppointments); ?></div>
                                        
                                        <?php foreach ($dayAppointments as $appointment): 
                                            $appointmentClass = '';
                                            $status = isset($appointment['status']) ? $appointment['status'] : 'cita_creada';
                                            
                                            // Mapear status a clases CSS
                                            switch($status) {
                                                case 'cita_realizada':
                                                    $appointmentClass = 'completed';
                                                    break;
                                                case 'cita_cancelada':
                                                    $appointmentClass = 'cancelled';
                                                    break;
                                                case 'no_se_presento':
                                                    $appointmentClass = 'no-show';
                                                    break;
                                                default:
                                                    $appointmentClass = 'normal';
                                                    break;
                                            }
                                        ?>
                                            <div class="appointment-item <?php echo $appointmentClass; ?>" 
                                                 data-appointment-id="<?php echo $appointment['id']; ?>"
                                                 title="<?php echo htmlspecialchars($appointment['paciente_nombre'] . ' - ' . $appointment['hora_cita']); ?>">
                                                <?php echo htmlspecialchars($appointment['hora_cita'] . ' - ' . $appointment['paciente_nombre']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>

                            <!-- Días del mes siguiente -->
                            <?php 
                            $remainingDays = 42 - ($startDayOfWeek + $daysInMonth);
                            for ($day = 1; $day <= $remainingDays; $day++): 
                            ?>
                                <div class="calendar-day other-month">
                                    <div class="day-number"><?php echo $day; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <!-- Legend -->
                        <div class="p-3">
                            <div class="legend">
                                <div class="legend-item">
                                    <div class="legend-color blue"></div>
                                    <span>Cita Programada</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color green"></div>
                                    <span>Cita Realizada</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color grey"></div>
                                    <span>Cita Cancelada</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color yellow"></div>
                                    <span>No se Presentó</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles de cita -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="appointmentDetails">
                    <!-- Los detalles se cargarán aquí via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="editAppointmentBtn" style="display: none;">
                        <i class="fas fa-edit me-1"></i>Editar Cita
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Event listener para clics en citas
        document.addEventListener('click', function(e) {
            const appointmentItem = e.target.closest('.appointment-item');
            if (appointmentItem) {
                const appointmentId = appointmentItem.dataset.appointmentId;
                showAppointmentDetails(appointmentId);
            }
        });

        // Función para mostrar detalles de la cita
        function showAppointmentDetails(appointmentId) {
            fetch(`test_appointment_simple.php?id=${appointmentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayAppointmentDetails(data.appointment);
                    } else {
                        alert('Error al cargar los detalles de la cita: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los detalles de la cita: ' + error.message);
                });
        }

        // Función para mostrar los detalles en el modal
        function displayAppointmentDetails(appointment) {
            const modalBody = document.getElementById('appointmentDetails');
            const editBtn = document.getElementById('editAppointmentBtn');
            
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2"></i>Paciente</h6>
                        <p class="mb-3">${appointment.paciente_nombre} ${appointment.paciente_apellido}</p>
                        
                        <h6><i class="fas fa-phone me-2"></i>Teléfono</h6>
                        <p class="mb-3">${appointment.paciente_telefono || 'No disponible'}</p>
                        
                        <h6><i class="fas fa-calendar me-2"></i>Fecha y Hora</h6>
                        <p class="mb-3">${appointment.fecha_cita} a las ${appointment.hora_cita}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle me-2"></i>Estado</h6>
                        <p class="mb-3">
                            <span class="badge ${getStatusBadgeClass(appointment.status)}">${appointment.estado}</span>
                        </p>
                        
                        <h6><i class="fas fa-stethoscope me-2"></i>Motivo</h6>
                        <p class="mb-3">${appointment.motivo || 'No especificado'}</p>
                        
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Síntomas</h6>
                        <p class="mb-3">${appointment.sintomas || 'No especificados'}</p>
                    </div>
                </div>
            `;
            
            // Mostrar botón de editar si es necesario
            editBtn.style.display = 'inline-block';
            editBtn.onclick = () => {
                // Aquí se puede implementar la funcionalidad de edición
                alert('Funcionalidad de edición próximamente');
            };
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
            modal.show();
        }

        // Función para obtener la clase del badge según el estado
        function getStatusBadgeClass(status) {
            switch(status) {
                case 'cita_creada': return 'bg-primary';
                case 'cita_realizada': return 'bg-success';
                case 'cita_cancelada': return 'bg-danger';
                case 'no_se_presento': return 'bg-warning';
                default: return 'bg-secondary';
            }
        }
    </script>
</body>
</html>
