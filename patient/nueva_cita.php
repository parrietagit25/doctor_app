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

$message = '';
$error = '';

// Obtener doctores disponibles
$stmt_doctors = $user->obtenerDoctores();
$doctors = [];
while($row = $stmt_doctors->fetch(PDO::FETCH_ASSOC)) {
    $doctors[] = $row;
}

// Procesar formulario de nueva cita
if($_POST && isset($_POST['crear_cita'])) {
    $doctor_id = $_POST['doctor_id'];
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];
    $motivo = $_POST['motivo'];
    $sintomas = $_POST['sintomas'];
    
    if(!empty($doctor_id) && !empty($fecha_cita) && !empty($hora_cita) && !empty($motivo)) {
        // Verificar disponibilidad
        if($appointment->verificarDisponibilidad($doctor_id, $fecha_cita, $hora_cita)) {
            $appointment->paciente_id = $_SESSION['user_id'];
            $appointment->doctor_id = $doctor_id;
            $appointment->fecha_cita = $fecha_cita;
            $appointment->hora_cita = $hora_cita;
            $appointment->motivo = $motivo;
            $appointment->sintomas = $sintomas;
            
            if($appointment->crear()) {
                $message = 'Cita creada exitosamente. Recibirá una confirmación por correo electrónico.';
                
                // TODO: Enviar email al doctor
                
                // Limpiar formulario
                $_POST = array();
            } else {
                $error = 'Error al crear la cita. Intente nuevamente.';
            }
        } else {
            $error = 'El horario seleccionado no está disponible. Por favor, seleccione otro horario.';
        }
    } else {
        $error = 'Por favor complete todos los campos obligatorios.';
    }
}

// Obtener horarios disponibles si se seleccionó doctor y fecha
$horarios_disponibles = [];
if(isset($_GET['doctor_id']) && isset($_GET['fecha'])) {
    $horarios_disponibles = $appointment->obtenerHorariosDisponibles($_GET['doctor_id'], $_GET['fecha']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Cita - Sistema de Citas Médicas</title>
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
        .form-card {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="nueva_cita.php">
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
                    <h1 class="h2">Solicitar Nueva Cita</h1>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
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

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card form-card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-plus me-2"></i>Información de la Cita</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="citaForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="doctor_id" class="form-label">Seleccionar Doctor <span class="text-danger">*</span></label>
                                            <select class="form-control" id="doctor_id" name="doctor_id" required onchange="cargarHorarios()">
                                                <option value="">Seleccionar doctor...</option>
                                                <?php foreach($doctors as $doctor): ?>
                                                <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $doctor['nombre'] . ' ' . $doctor['apellido'] . ' - ' . $doctor['especialidad']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="fecha_cita" class="form-label">Fecha de la Cita <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" required onchange="cargarHorarios()" 
                                                   min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['fecha_cita']) ? $_POST['fecha_cita'] : ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="hora_cita" class="form-label">Hora de la Cita <span class="text-danger">*</span></label>
                                        <select class="form-control" id="hora_cita" name="hora_cita" required>
                                            <option value="">Seleccione fecha y doctor primero...</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="motivo" class="form-label">Motivo de la Consulta <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="motivo" name="motivo" rows="3" required placeholder="Describa el motivo de su consulta..."><?php echo isset($_POST['motivo']) ? $_POST['motivo'] : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sintomas" class="form-label">Síntomas (Opcional)</label>
                                        <textarea class="form-control" id="sintomas" name="sintomas" rows="3" placeholder="Describa los síntomas que está experimentando..."><?php echo isset($_POST['sintomas']) ? $_POST['sintomas'] : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="dashboard.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                        <button type="submit" name="crear_cita" class="btn btn-primary">
                                            <i class="fas fa-calendar-plus me-2"></i>Solicitar Cita
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Doctor Info Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle me-2"></i>Información</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-clock me-2"></i>Horarios de Atención</h6>
                                    <p class="mb-1"><strong>Lunes a Viernes:</strong> 8:00 AM - 6:00 PM</p>
                                    <p class="mb-0"><strong>Sábados:</strong> 8:00 AM - 12:00 PM</p>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Importante</h6>
                                    <ul class="mb-0">
                                        <li>Las citas se programan cada 30 minutos</li>
                                        <li>Llegue 15 minutos antes de su cita</li>
                                        <li>Puede cancelar hasta 24 horas antes</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Available Doctors -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fas fa-user-md me-2"></i>Doctores Disponibles</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach(array_slice($doctors, 0, 3) as $doctor): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo $doctor['nombre'] . ' ' . $doctor['apellido']; ?></strong>
                                        <br><small class="text-muted"><?php echo $doctor['especialidad']; ?></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarDoctor(<?php echo $doctor['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function seleccionarDoctor(doctorId) {
            document.getElementById('doctor_id').value = doctorId;
            cargarHorarios();
        }

        function cargarHorarios() {
            const doctorId = document.getElementById('doctor_id').value;
            const fecha = document.getElementById('fecha_cita').value;
            const horaSelect = document.getElementById('hora_cita');
            
            horaSelect.innerHTML = '<option value="">Cargando horarios...</option>';
            
            if(doctorId && fecha) {
                // Generar horarios disponibles (8:00 AM a 6:00 PM, cada 30 minutos)
                const horarios = [];
                const horaInicio = 8; // 8:00 AM
                const horaFin = 18; // 6:00 PM
                
                for(let hora = horaInicio; hora < horaFin; hora++) {
                    for(let minuto = 0; minuto < 60; minuto += 30) {
                        const horaFormato = String(hora).padStart(2, '0') + ':' + String(minuto).padStart(2, '0');
                        horarios.push(horaFormato);
                    }
                }
                
                horaSelect.innerHTML = '<option value="">Seleccionar hora...</option>';
                horarios.forEach(function(horario) {
                    const option = document.createElement('option');
                    option.value = horario;
                    option.textContent = horario;
                    horaSelect.appendChild(option);
                });
            } else {
                horaSelect.innerHTML = '<option value="">Seleccione fecha y doctor primero...</option>';
            }
        }

        // Cargar horarios si hay datos del POST
        <?php if(isset($_POST['doctor_id']) && isset($_POST['fecha_cita'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            cargarHorarios();
            <?php if(isset($_POST['hora_cita'])): ?>
            document.getElementById('hora_cita').value = '<?php echo $_POST['hora_cita']; ?>';
            <?php endif; ?>
        });
        <?php endif; ?>
    </script>
</body>
</html>
