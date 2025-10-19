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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAppointmentModal">
                        <i class="fas fa-plus me-2"></i>Nueva Cita
                    </button>
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

    <!-- Create Appointment Modal -->
    <div class="modal fade" id="createAppointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="createAppointmentForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Nueva Cita</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="paciente_id" class="form-label">Paciente *</label>
                                <div class="input-group">
                                    <select class="form-control" id="paciente_id" name="paciente_id" required>
                                        <option value="">Seleccionar paciente...</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#registerPatientModal">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_cita" class="form-label">Fecha de la Cita *</label>
                                <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="hora_cita" class="form-label">Hora de la Cita *</label>
                                <select class="form-control" id="hora_cita" name="hora_cita" required>
                                    <option value="">Seleccionar hora...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="motivo" class="form-label">Motivo de la Consulta *</label>
                                <input type="text" class="form-control" id="motivo" name="motivo" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="sintomas" class="form-label">Síntomas (Opcional)</label>
                            <textarea class="form-control" id="sintomas" name="sintomas" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Cita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Register Patient Modal -->
    <div class="modal fade" id="registerPatientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="registerPatientForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Nuevo Paciente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_paciente" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre_paciente" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="apellido_paciente" class="form-label">Apellido *</label>
                                <input type="text" class="form-control" id="apellido_paciente" name="apellido" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="identificacion_paciente" class="form-label">Identificación *</label>
                            <input type="text" class="form-control" id="identificacion_paciente" name="identificacion" required>
                            <div id="identificacion_error" class="invalid-feedback" style="display: none;">
                                Esta identificación ya está registrada
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email_paciente" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email_paciente" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="telefono_paciente" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono_paciente" name="telefono">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Registrar Paciente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar datos al inicializar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadPatients();
            setupDateRestrictions();
            setupIdentificationValidation();
        });

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

        // Configurar validación de identificación
        function setupIdentificationValidation() {
            const identificacionInput = document.getElementById('identificacion_paciente');
            const errorDiv = document.getElementById('identificacion_error');
            let validationTimeout;

            identificacionInput.addEventListener('input', function() {
                clearTimeout(validationTimeout);
                const identificacion = this.value.trim();
                
                if (identificacion.length >= 3) { // Validar solo si tiene al menos 3 caracteres
                    validationTimeout = setTimeout(() => {
                        validateIdentification(identificacion);
                    }, 500); // Esperar 500ms después del último input
                } else {
                    hideIdentificationError();
                }
            });
        }

        // Validar identificación
        function validateIdentification(identificacion) {
            fetch(`validate_identification.php?identificacion=${encodeURIComponent(identificacion)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showIdentificationError();
                    } else {
                        hideIdentificationError();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideIdentificationError();
                });
        }

        // Mostrar error de identificación
        function showIdentificationError() {
            const identificacionInput = document.getElementById('identificacion_paciente');
            const errorDiv = document.getElementById('identificacion_error');
            
            identificacionInput.classList.add('is-invalid');
            errorDiv.style.display = 'block';
        }

        // Ocultar error de identificación
        function hideIdentificationError() {
            const identificacionInput = document.getElementById('identificacion_paciente');
            const errorDiv = document.getElementById('identificacion_error');
            
            identificacionInput.classList.remove('is-invalid');
            errorDiv.style.display = 'none';
        }

        // Cargar pacientes
        function loadPatients() {
            fetch('get_patients.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('paciente_id');
                    select.innerHTML = '<option value="">Seleccionar paciente...</option>';
                    data.forEach(patient => {
                        const option = document.createElement('option');
                        option.value = patient.id;
                        const displayText = patient.identificacion ? 
                            `${patient.nombre} ${patient.apellido} - ${patient.identificacion}` :
                            `${patient.nombre} ${patient.apellido} - ${patient.email}`;
                        option.textContent = displayText;
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Configurar restricciones de fecha
        function setupDateRestrictions() {
            const fechaInput = document.getElementById('fecha_cita');
            const today = new Date().toISOString().split('T')[0];
            fechaInput.min = today;
            
            // Cargar horarios disponibles cuando cambie la fecha
            fechaInput.addEventListener('change', loadAvailableHours);
        }

        // Cargar horarios disponibles
        function loadAvailableHours() {
            const doctorId = <?php echo $_SESSION['user_id']; ?>;
            const fecha = document.getElementById('fecha_cita').value;
            
            if (!fecha) return;
            
            fetch(`get_available_hours.php?doctor_id=${doctorId}&fecha=${fecha}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('hora_cita');
                    select.innerHTML = '<option value="">Seleccionar hora...</option>';
                    data.forEach(hour => {
                        const option = document.createElement('option');
                        option.value = hour;
                        option.textContent = hour;
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Crear cita
        document.getElementById('createAppointmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_appointment');
            formData.append('doctor_id', <?php echo $_SESSION['user_id']; ?>);
            
            fetch('create_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cita creada exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear la cita');
            });
        });

        // Registrar paciente
        document.getElementById('registerPatientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Verificar si hay error de identificación
            const identificacionInput = document.getElementById('identificacion_paciente');
            if (identificacionInput.classList.contains('is-invalid')) {
                alert('La identificación ya está registrada. Por favor, use una identificación diferente.');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'register_patient');
            
            fetch('register_patient.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Paciente registrado exitosamente');
                    loadPatients(); // Recargar lista de pacientes
                    document.getElementById('paciente_id').value = data.patient_id;
                    bootstrap.Modal.getInstance(document.getElementById('registerPatientModal')).hide();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al registrar el paciente');
            });
        });
    </script>
</body>
</html>
