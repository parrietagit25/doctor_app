<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/MedicalHistory.php';

// Verificar autenticación y tipo de usuario
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'administrador') {
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
        case 'create_patient':
            $user->nombre = $_POST['nombre'];
            $user->apellido = $_POST['apellido'];
            $user->email = $_POST['email'];
            $user->identificacion = $_POST['identificacion'];
            $user->telefono = $_POST['telefono'];
            
            // Validar datos requeridos
            if(empty($user->nombre) || empty($user->apellido) || empty($user->identificacion)) {
                $error = 'Los campos obligatorios (Nombre, Apellido, Identificación) deben ser completados';
                break;
            }
            
            // Verificar si la identificación ya existe
            if($user->verificarIdentificacion($user->identificacion)) {
                $error = 'Esta identificación ya está registrada';
                break;
            }
            
            $patient_id = $user->crearPaciente();
            if($patient_id) {
                $message = 'Paciente agregado exitosamente al sistema.';
            } else {
                $error = 'Error al agregar el paciente.';
            }
            break;
            
        case 'create_history':
            $medicalHistory->paciente_id = $_POST['paciente_id'];
            $medicalHistory->doctor_id = $_SESSION['user_id'];
            
            // Validar cita_id si se proporciona
            $cita_id = trim($_POST['cita_id']);
            if (!empty($cita_id)) {
                // Verificar que la cita existe
                $check_cita = "SELECT id FROM citas WHERE id = :cita_id";
                $stmt_check = $db->prepare($check_cita);
                $stmt_check->bindParam(':cita_id', $cita_id);
                $stmt_check->execute();
                
                if ($stmt_check->rowCount() == 0) {
                    $error = 'El ID de cita proporcionado no existe en el sistema.';
                    break;
                }
                $medicalHistory->cita_id = $cita_id;
            } else {
                $medicalHistory->cita_id = null;
            }
            
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
            
        case 'update_history':
            $medicalHistory->id = $_POST['history_id'];
            $medicalHistory->diagnostico = $_POST['diagnostico'];
            $medicalHistory->tratamiento = $_POST['tratamiento'];
            $medicalHistory->medicamentos = $_POST['medicamentos'];
            $medicalHistory->notas_adicionales = $_POST['notas_adicionales'];
            
            if($medicalHistory->actualizar()) {
                $message = 'Historial médico actualizado exitosamente.';
            } else {
                $error = 'Error al actualizar el historial médico.';
            }
            break;
            
        case 'create_cotizacion':
            // Esta acción se maneja via AJAX en create_cotizacion.php
            break;
            
        case 'update_cotizacion':
            require_once __DIR__ . '/../classes/Cotizacion.php';
            $cotizacion = new Cotizacion($db);
            $cotizacion->id = $_POST['cotizacion_id'];
            $cotizacion->fecha_vencimiento = $_POST['fecha_vencimiento'];
            $cotizacion->estado = $_POST['estado'];
            $cotizacion->notas = $_POST['notas'];
            
            if($cotizacion->actualizar()) {
                $message = 'Cotización actualizada exitosamente.';
            } else {
                $error = 'Error al actualizar la cotización.';
            }
            break;
            
        case 'delete_history':
            $medicalHistory->id = $_POST['history_id'];
            if($medicalHistory->eliminar()) {
                $message = 'Registro del historial médico eliminado exitosamente.';
            } else {
                $error = 'Error al eliminar el registro del historial médico.';
            }
            break;
    }
}

// Obtener solo pacientes
$query = "SELECT id, nombre, apellido, email, identificacion, telefono, fecha_registro 
          FROM usuarios 
          WHERE tipo_usuario = 'paciente' AND activo = 1 
          ORDER BY fecha_registro DESC";
$stmt = $db->prepare($query);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pacientes - Sistema de Citas Médicas</title>
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
        
        /* Timeline Styles */
        .timeline-container {
            max-width: 100%;
            margin: 0 auto;
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #007bff, #28a745);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 80px;
        }
        
        .timeline-marker {
            position: absolute;
            left: 32px;
            top: 10px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            z-index: 2;
        }
        
        .timeline-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .timeline-content:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .timeline-item:nth-child(odd) .timeline-content {
            border-left: 4px solid #007bff;
        }
        
        .timeline-item:nth-child(even) .timeline-content {
            border-left: 4px solid #28a745;
        }
        
        @media (max-width: 768px) {
            .timeline::before {
                left: 30px;
            }
            
            .timeline-item {
                padding-left: 60px;
            }
            
            .timeline-marker {
                left: 22px;
                width: 30px;
                height: 30px;
            }
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios.php">
                                <i class="fas fa-users me-2"></i>Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="pacientes.php">
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
                    <h1 class="h2">Gestión de Pacientes</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                        <i class="fas fa-plus me-2"></i>Agregar Paciente
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
                            </div>
                            <div class="card-body">
                                <p class="card-text">
                                    <i class="fas fa-id-card me-2"></i>
                                    <strong>Identificación:</strong> <?php echo htmlspecialchars($patient['identificacion']); ?><br>
                                    <i class="fas fa-envelope me-2"></i>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?><br>
                                    <i class="fas fa-phone me-2"></i>
                                    <strong>Teléfono:</strong> <?php echo htmlspecialchars($patient['telefono']); ?><br>
                                    <i class="fas fa-calendar me-2"></i>
                                    <strong>Registro:</strong> <?php echo date('d/m/Y', strtotime($patient['fecha_registro'])); ?>
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
                                    <button class="btn btn-outline-warning btn-sm" onclick="viewPatientTimeline(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['nombre'] . ' ' . $patient['apellido']); ?>')">
                                        <i class="fas fa-clock me-1"></i>Timeline
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
                    <h3 class="text-muted">No hay pacientes registrados</h3>
                    <p class="text-muted">Puedes agregar pacientes directamente desde este módulo o los pacientes pueden registrarse automáticamente.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                        <i class="fas fa-plus me-2"></i>Agregar Primer Paciente
                    </button>
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

    <!-- Patient Timeline Modal -->
    <div class="modal fade" id="patientTimelineModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clock me-2"></i>Timeline Médico del Paciente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="patientTimelineContent">
                    <div class="text-center">
                        <div class="spinner-border text-info" role="status">
                            <span class="visually-hidden">Cargando timeline...</span>
                        </div>
                        <p class="mt-2">Cargando línea de tiempo médica...</p>
                    </div>
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
                            <div class="form-text">Solo ingrese un ID si este registro está asociado a una cita específica</div>
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

    <!-- History Details Modal -->
    <div class="modal fade" id="historyDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Registro Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="historyDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit History Modal -->
    <div class="modal fade" id="editHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editHistoryForm">
                    <input type="hidden" name="action" value="update_history">
                    <input type="hidden" name="history_id" id="edit_history_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Registro Médico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_diagnostico" class="form-label">Diagnóstico</label>
                            <textarea class="form-control" id="edit_diagnostico" name="diagnostico" rows="3" required placeholder="Describe el diagnóstico..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tratamiento" class="form-label">Tratamiento</label>
                            <textarea class="form-control" id="edit_tratamiento" name="tratamiento" rows="3" placeholder="Describe el tratamiento prescrito..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_medicamentos" class="form-label">Medicamentos</label>
                            <textarea class="form-control" id="edit_medicamentos" name="medicamentos" rows="2" placeholder="Lista de medicamentos prescritos..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_notas_adicionales" class="form-label">Notas Adicionales</label>
                            <textarea class="form-control" id="edit_notas_adicionales" name="notas_adicionales" rows="3" placeholder="Observaciones adicionales..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New Cotizacion Modal -->
    <div class="modal fade" id="newCotizacionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="newCotizacionForm">
                    <input type="hidden" name="action" value="create_cotizacion">
                    <input type="hidden" name="paciente_id" id="newCotizacionPatientId">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Nueva Cotización
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="aprobada">Aprobada</option>
                                    <option value="rechazada">Rechazada</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3" placeholder="Notas adicionales sobre la cotización..."></textarea>
                        </div>
                        
                        <!-- Productos Section -->
                        <div class="border-top pt-3">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-shopping-cart me-2"></i>Productos/Servicios
                            </h6>
                            <div id="productosContainer">
                                <div class="producto-item border p-3 mb-3 rounded">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Producto/Servicio</label>
                                            <input type="text" class="form-control producto-nombre" placeholder="Nombre del producto/servicio" required>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Cantidad</label>
                                            <input type="number" class="form-control producto-cantidad" min="1" value="1" required>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Precio Unitario</label>
                                            <input type="number" class="form-control producto-precio" step="0.01" min="0" placeholder="0.00" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Subtotal</label>
                                            <input type="text" class="form-control producto-subtotal" readonly>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                            <label class="form-label">Descripción</label>
                                            <textarea class="form-control producto-descripcion" rows="2" placeholder="Descripción del producto/servicio"></textarea>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger remove-producto">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-success" id="addProducto">
                                <i class="fas fa-plus"></i> Agregar Producto
                            </button>
                        </div>
                        
                        <!-- Totales Section -->
                        <div class="border-top pt-3 mt-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Subtotal</label>
                                    <input type="text" class="form-control" id="totalSubtotal" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Impuesto (18%)</label>
                                    <input type="text" class="form-control" id="totalImpuesto" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Total</label>
                                    <input type="text" class="form-control" id="totalFinal" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Crear Cotización
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cotizacion Details Modal -->
    <div class="modal fade" id="cotizacionDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la Cotización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="cotizacionDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Cotizacion Modal -->
    <div class="modal fade" id="editCotizacionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editCotizacionForm">
                    <input type="hidden" name="action" value="update_cotizacion">
                    <input type="hidden" name="cotizacion_id" id="edit_cotizacion_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Cotización</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="edit_fecha_vencimiento" name="fecha_vencimiento">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_estado" class="form-label">Estado</label>
                                <select class="form-control" id="edit_estado" name="estado">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="aprobada">Aprobada</option>
                                    <option value="rechazada">Rechazada</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="edit_notas" name="notas" rows="3" placeholder="Notas adicionales sobre la cotización..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Cotización</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div class="modal fade" id="addPatientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="create_patient">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Nuevo Paciente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> Este paciente será agregado al sistema pero no tendrá acceso de usuario hasta que se registre.
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="apellido" name="apellido" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="identificacion" class="form-label">Identificación <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="identificacion" name="identificacion" required>
                            <div id="identificacion_error" class="invalid-feedback" style="display: none;">
                                Esta identificación ya está registrada
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Agregar Paciente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewPatientHistory(patientId) {
            document.getElementById('patientHistoryContent').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2">Cargando historial médico...</p></div>';
            
            var patientHistoryModal = new bootstrap.Modal(document.getElementById('patientHistoryModal'));
            patientHistoryModal.show();
            
            // Cargar historial médico via AJAX
            fetch('get_patient_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'patient_id=' + patientId
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('patientHistoryContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('patientHistoryContent').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar el historial médico. Intente nuevamente.</div>';
            });
        }

        function viewPatientTimeline(patientId, patientName) {
            // Actualizar el título del modal con el nombre del paciente
            document.querySelector('#patientTimelineModal .modal-title').innerHTML = 
                '<i class="fas fa-clock me-2"></i>Timeline Médico - ' + patientName;
            
            // Mostrar modal
            var patientTimelineModal = new bootstrap.Modal(document.getElementById('patientTimelineModal'));
            patientTimelineModal.show();
            
            // Cargar timeline via AJAX
            fetch('get_patient_timeline.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'patient_id=' + patientId
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('patientTimelineContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('patientTimelineContent').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar el timeline médico. Intente nuevamente.</div>';
            });
        }

        function addMedicalHistory(patientId) {
            document.getElementById('add_paciente_id').value = patientId;
            
            var addHistoryModal = new bootstrap.Modal(document.getElementById('addHistoryModal'));
            addHistoryModal.show();
        }

        function viewHistoryDetails(historyId) {
            document.getElementById('historyDetailsContent').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2">Cargando detalles...</p></div>';
            
            var historyDetailsModal = new bootstrap.Modal(document.getElementById('historyDetailsModal'));
            historyDetailsModal.show();
            
            // Cargar detalles del registro via AJAX
            fetch('get_history_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'history_id=' + historyId
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('historyDetailsContent').innerHTML = data;
                // Cargar imágenes después de cargar los detalles
                loadMedicalImages(historyId);
            })
            .catch(error => {
                document.getElementById('historyDetailsContent').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar los detalles del registro.</div>';
            });
        }

        function editHistory(historyId) {
            // Cargar datos del registro para editar
            fetch('get_history_for_edit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'history_id=' + historyId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('edit_history_id').value = historyId;
                    document.getElementById('edit_diagnostico').value = data.diagnostico || '';
                    document.getElementById('edit_tratamiento').value = data.tratamiento || '';
                    document.getElementById('edit_medicamentos').value = data.medicamentos || '';
                    document.getElementById('edit_notas_adicionales').value = data.notas_adicionales || '';
                    
                    var editModal = new bootstrap.Modal(document.getElementById('editHistoryModal'));
                    editModal.show();
                } else {
                    alert('Error al cargar los datos del registro para editar.');
                }
            })
            .catch(error => {
                alert('Error al cargar los datos del registro.');
            });
        }

        function createCotizacion(patientId) {
            // Abrir modal para crear nueva cotización
            document.getElementById('newCotizacionPatientId').value = patientId;
            var cotizacionModal = new bootstrap.Modal(document.getElementById('newCotizacionModal'));
            cotizacionModal.show();
        }

        function viewCotizacion(cotizacionId) {
            // Cargar detalles de la cotización
            fetch('get_cotizacion_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'cotizacion_id=' + cotizacionId
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('cotizacionDetailsContent').innerHTML = data;
                var modal = new bootstrap.Modal(document.getElementById('cotizacionDetailsModal'));
                modal.show();
            })
            .catch(error => {
                alert('Error al cargar los detalles de la cotización.');
            });
        }

        function editCotizacion(cotizacionId) {
            // Cargar datos de la cotización para editar
            fetch('get_cotizacion_for_edit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'cotizacion_id=' + cotizacionId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('edit_cotizacion_id').value = cotizacionId;
                    document.getElementById('edit_fecha_vencimiento').value = data.fecha_vencimiento || '';
                    document.getElementById('edit_notas').value = data.notas || '';
                    
                    var editModal = new bootstrap.Modal(document.getElementById('editCotizacionModal'));
                    editModal.show();
                } else {
                    alert('Error al cargar los datos de la cotización.');
                }
            })
            .catch(error => {
                alert('Error al cargar los datos de la cotización.');
            });
        }

        function exportCotizacionPDF(cotizacionId) {
            // Mostrar indicador de carga
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            button.disabled = true;
            
            // Usar fetch para obtener el PDF con mPDF (más confiable)
            fetch('export_cotizacion_mpdf.php?cotizacion_id=' + cotizacionId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Crear enlace para descargar
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'cotizacion_' + cotizacionId + '.pdf';
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    
                    // Restaurar botón
                    button.innerHTML = originalText;
                    button.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    button.innerHTML = originalText;
                    button.disabled = false;
                    alert('Error al generar el PDF: ' + error.message);
                });
        }

        function sendCotizacionEmail(cotizacionId) {
            // Mostrar modal para enviar correo
            document.getElementById('email_cotizacion_id').value = cotizacionId;
            var emailModal = new bootstrap.Modal(document.getElementById('sendEmailModal'));
            emailModal.show();
        }

        function uploadMedicalImages(historialId) {
            // Mostrar modal para subir imágenes
            document.getElementById('upload_historial_id').value = historialId;
            document.getElementById('uploadImagesForm').reset();
            document.getElementById('upload_preview').innerHTML = '';
            var uploadModal = new bootstrap.Modal(document.getElementById('uploadImagesModal'));
            uploadModal.show();
        }

        // Funciones para manejar productos en cotizaciones
        function addProducto() {
            const container = document.getElementById('productosContainer');
            const productoHtml = `
                <div class="producto-item border p-3 mb-3 rounded">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Producto/Servicio</label>
                            <input type="text" class="form-control producto-nombre" placeholder="Nombre del producto/servicio" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Cantidad</label>
                            <input type="number" class="form-control producto-cantidad" min="1" value="1" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Precio Unitario</label>
                            <input type="number" class="form-control producto-precio" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Subtotal</label>
                            <input type="text" class="form-control producto-subtotal" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control producto-descripcion" rows="2" placeholder="Descripción del producto/servicio"></textarea>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger remove-producto">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', productoHtml);
            attachProductoEvents();
        }

        function attachProductoEvents() {
            // Event listeners para cantidad y precio
            document.querySelectorAll('.producto-cantidad, .producto-precio').forEach(input => {
                input.addEventListener('input', calculateProductoSubtotal);
            });

            // Event listeners para eliminar productos
            document.querySelectorAll('.remove-producto').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.producto-item').remove();
                    calculateTotals();
                });
            });
        }

        function calculateProductoSubtotal(event) {
            const productoItem = event.target.closest('.producto-item');
            const cantidad = parseFloat(productoItem.querySelector('.producto-cantidad').value) || 0;
            const precio = parseFloat(productoItem.querySelector('.producto-precio').value) || 0;
            const subtotal = cantidad * precio;
            
            productoItem.querySelector('.producto-subtotal').value = subtotal.toFixed(2);
            calculateTotals();
        }

        function calculateTotals() {
            let totalSubtotal = 0;
            
            document.querySelectorAll('.producto-subtotal').forEach(input => {
                totalSubtotal += parseFloat(input.value) || 0;
            });

            const impuesto = totalSubtotal * 0.18; // 18% de impuesto
            const total = totalSubtotal + impuesto;

            document.getElementById('totalSubtotal').value = totalSubtotal.toFixed(2);
            document.getElementById('totalImpuesto').value = impuesto.toFixed(2);
            document.getElementById('totalFinal').value = total.toFixed(2);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar producto
            document.getElementById('addProducto').addEventListener('click', addProducto);
            
            // Attach events to existing productos
            attachProductoEvents();
            
            // Form submission para cotización
            document.getElementById('newCotizacionForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Recolectar datos de productos
                const productos = [];
                document.querySelectorAll('.producto-item').forEach(item => {
                    const nombre = item.querySelector('.producto-nombre').value;
                    const cantidad = item.querySelector('.producto-cantidad').value;
                    const precio = item.querySelector('.producto-precio').value;
                    const descripcion = item.querySelector('.producto-descripcion').value;
                    
                    if (nombre && cantidad && precio) {
                        productos.push({
                            nombre: nombre,
                            cantidad: cantidad,
                            precio: precio,
                            descripcion: descripcion,
                            subtotal: (parseFloat(cantidad) * parseFloat(precio)).toFixed(2)
                        });
                    }
                });

                if (productos.length === 0) {
                    alert('Debe agregar al menos un producto/servicio.');
                    return;
                }

                // Crear FormData
                const formData = new FormData(this);
                formData.append('productos', JSON.stringify(productos));

                // Enviar datos
                fetch('create_cotizacion.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Cotización creada exitosamente.');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al crear la cotización.');
                });
            });

            // Manejar vista previa de imágenes
            document.getElementById('medical_images').addEventListener('change', function(e) {
                const files = e.target.files;
                const preview = document.getElementById('upload_preview');
                preview.innerHTML = '';

                if (files.length > 0) {
                    preview.innerHTML = '<div class="col-12"><h6>Vista previa de imágenes:</h6></div>';
                    
                    Array.from(files).forEach((file, index) => {
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const imgDiv = document.createElement('div');
                                imgDiv.className = 'col-md-3 col-sm-4 col-6 mb-2';
                                imgDiv.innerHTML = `
                                    <div class="card">
                                        <img src="${e.target.result}" class="card-img-top" style="height: 100px; object-fit: cover;">
                                        <div class="card-body p-2">
                                            <small class="text-muted">${file.name}</small>
                                            <br><small class="text-muted">${formatFileSize(file.size)}</small>
                                        </div>
                                    </div>
                                `;
                                preview.appendChild(imgDiv);
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            });

            // Manejar envío de imágenes
            document.getElementById('uploadImagesForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subiendo...';
                submitBtn.disabled = true;

                fetch('../external/upload_medical_images.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Imágenes subidas exitosamente.');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al subir las imágenes.');
                    console.error('Error:', error);
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        });

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function loadMedicalImages(historialId) {
            fetch('get_medical_images.php?historial_id=' + historialId)
                .then(response => response.text())
                .then(data => {
                    const container = document.getElementById('medical_images_container');
                    if (container) {
                        container.innerHTML = data;
                    }
                })
                .catch(error => {
                    console.error('Error loading medical images:', error);
                    const container = document.getElementById('medical_images_container');
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger">Error al cargar las imágenes</div>';
                    }
                });
        }

        function viewMedicalImage(imagePath, imageName) {
            // Crear modal para ver imagen completa
            const modalHtml = `
                <div class="modal fade" id="imageModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-image me-2"></i>${imageName}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img src="${imagePath}" class="img-fluid" alt="${imageName}">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <a href="${imagePath}" download="${imageName}" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Descargar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal existente si existe
            const existingModal = document.getElementById('imageModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Agregar nuevo modal
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Mostrar modal
            const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
            
            // Limpiar modal cuando se cierre
            document.getElementById('imageModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }

        function deleteMedicalImage(imageId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
                fetch('delete_medical_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'image_id=' + imageId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Imagen eliminada exitosamente.');
                        // Recargar las imágenes
                        const historialId = document.getElementById('upload_historial_id').value;
                        if (historialId) {
                            loadMedicalImages(historialId);
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al eliminar la imagen.');
                    console.error('Error:', error);
                });
            }
        }

    </script>

    <!-- Modal para enviar cotización por correo -->
    <div class="modal fade" id="sendEmailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="sendEmailForm">
                    <input type="hidden" name="action" value="send_cotizacion_email">
                    <input type="hidden" name="cotizacion_id" id="email_cotizacion_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-envelope me-2"></i>Enviar Cotización por Correo
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="email_destinatario" class="form-label">Correo Destinatario</label>
                            <input type="email" class="form-control" id="email_destinatario" name="email_destinatario" required placeholder="ejemplo@correo.com">
                            <div class="form-text">Ingresa el correo electrónico donde se enviará la cotización</div>
                        </div>
                        <div class="mb-3">
                            <label for="email_asunto" class="form-label">Asunto del Correo</label>
                            <input type="text" class="form-control" id="email_asunto" name="email_asunto" value="Cotización Médica" required>
                        </div>
                        <div class="mb-3">
                            <label for="email_mensaje" class="form-label">Mensaje Adicional</label>
                            <textarea class="form-control" id="email_mensaje" name="email_mensaje" rows="4" placeholder="Mensaje opcional que se incluirá en el correo..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> La cotización se enviará como archivo PDF adjunto al correo electrónico.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Correo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para subir imágenes médicas -->
    <div class="modal fade" id="uploadImagesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="uploadImagesForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_medical_images">
                    <input type="hidden" name="historial_id" id="upload_historial_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-images me-2"></i>Subir Imágenes Médicas
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="medical_images" class="form-label">Seleccionar Imágenes</label>
                            <input type="file" class="form-control" id="medical_images" name="medical_images[]" multiple accept="image/*" required>
                            <div class="form-text">
                                Puedes seleccionar múltiples imágenes. Formatos permitidos: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB por imagen.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="image_note" class="form-label">Nota (Opcional)</label>
                            <textarea class="form-control" id="image_note" name="image_note" rows="3" placeholder="Agrega una nota descriptiva para las imágenes..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información:</strong> Las imágenes se asociarán con este registro del historial médico.
                        </div>
                        <div id="upload_preview" class="row g-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload me-2"></i>Subir Imágenes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Timeline Details Modal -->
    <div class="modal fade" id="timelineDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Detalles del Evento
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="timelineDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTimelineDetails(eventId, eventType) {
            // Mostrar modal
            var timelineDetailsModal = new bootstrap.Modal(document.getElementById('timelineDetailsModal'));
            timelineDetailsModal.show();
            
            // Cargar detalles via AJAX
            fetch('get_timeline_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'event_id=' + eventId + '&event_type=' + eventType
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('timelineDetailsContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('timelineDetailsContent').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar los detalles. Intente nuevamente.</div>';
            });
        }

        // Configurar validación de identificación
        function setupIdentificationValidation() {
            const identificacionInput = document.getElementById('identificacion');
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
            const identificacionInput = document.getElementById('identificacion');
            const errorDiv = document.getElementById('identificacion_error');
            
            identificacionInput.classList.add('is-invalid');
            errorDiv.style.display = 'block';
        }

        // Ocultar error de identificación
        function hideIdentificationError() {
            const identificacionInput = document.getElementById('identificacion');
            const errorDiv = document.getElementById('identificacion_error');
            
            identificacionInput.classList.remove('is-invalid');
            errorDiv.style.display = 'none';
        }

        // Inicializar validación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            setupIdentificationValidation();
        });
    </script>
</body>
</html>
