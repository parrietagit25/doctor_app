<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';

// Si el usuario ya está logueado, redirigir según su tipo
if(isset($_SESSION['user_id'])) {
    switch($_SESSION['user_type']) {
        case 'administrador':
            header('Location: admin/dashboard.php');
            break;
        case 'doctor':
            header('Location: doctor/dashboard.php');
            break;
        case 'paciente':
            header('Location: patient/dashboard.php');
            break;
    }
    exit();
}

$error = '';
$success = '';

// Procesar login
if($_POST && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if(!empty($email) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        
        if($user->login($email, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->nombre . ' ' . $user->apellido;
            $_SESSION['user_type'] = $user->tipo_usuario;
            $_SESSION['user_email'] = $user->email;
            
            // Redirigir según tipo de usuario
            switch($user->tipo_usuario) {
                case 'administrador':
                    header('Location: admin/dashboard.php');
                    break;
                case 'doctor':
                    header('Location: doctor/dashboard.php');
                    break;
                case 'paciente':
                    header('Location: patient/dashboard.php');
                    break;
            }
            exit();
        } else {
            $error = 'Credenciales incorrectas.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}

// Procesar registro
if($_POST && isset($_POST['register'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $tipo_usuario = 'paciente'; // Por defecto se registran como pacientes
    
    if(!empty($nombre) && !empty($apellido) && !empty($email) && !empty($password)) {
        if($password === $confirm_password) {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);
            
            $user->nombre = $nombre;
            $user->apellido = $apellido;
            $user->email = $email;
            $user->telefono = $telefono;
            $user->password = $password;
            $user->tipo_usuario = $tipo_usuario;
            
            if($user->crear()) {
                $success = 'Usuario registrado exitosamente. Puede iniciar sesión.';
            } else {
                $error = 'Error al registrar usuario. El email podría estar en uso.';
            }
        } else {
            $error = 'Las contraseñas no coinciden.';
        }
    } else {
        $error = 'Por favor complete todos los campos obligatorios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Citas Médicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .card {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Sistema de Citas Médicas</h1>
                    <p class="lead mb-4">Gestiona tus citas médicas de manera fácil y eficiente. Conecta pacientes con doctores especializados.</p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Reserva citas online</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Historial médico digital</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Notificaciones por email</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Gestión completa de consultas</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body p-4" style="color: black;">
                            <ul class="nav nav-tabs" id="authTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                                        Iniciar Sesión
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                                        Registrarse
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content mt-4" id="authTabsContent">
                                <!-- Login Form -->
                                <div class="tab-pane fade show active" id="login" role="tabpanel">
                                    <?php if($error): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>
                                    <?php if($success): ?>
                                        <div class="alert alert-success"><?php echo $success; ?></div>
                                    <?php endif; ?>
                                    
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Contraseña</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        <button type="submit" name="login" class="btn btn-primary w-100">Iniciar Sesión</button>
                                    </form>
                                </div>
                                
                                <!-- Register Form -->
                                <div class="tab-pane fade" id="register" role="tabpanel">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nombre" class="form-label">Nombre</label>
                                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="apellido" class="form-label">Apellido</label>
                                                <input type="text" class="form-control" id="apellido" name="apellido" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email_reg" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email_reg" name="email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono">
                                        </div>
                                        <div class="mb-3">
                                            <label for="password_reg" class="form-label">Contraseña</label>
                                            <input type="password" class="form-control" id="password_reg" name="password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <button type="submit" name="register" class="btn btn-success w-100">Registrarse</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-user-md feature-icon mb-3"></i>
                            <h5 class="card-title">Doctores Especializados</h5>
                            <p class="card-text">Accede a una red de médicos profesionales y especialistas en diferentes áreas de la salud.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-calendar-check feature-icon mb-3"></i>
                            <h5 class="card-title">Reserva Fácil</h5>
                            <p class="card-text">Reserva tu cita médica de manera sencilla, seleccionando el doctor, fecha y hora que mejor te convenga.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-file-medical feature-icon mb-3"></i>
                            <h5 class="card-title">Historial Digital</h5>
                            <p class="card-text">Mantén un registro completo de tus consultas, diagnósticos y tratamientos en formato digital.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-light">
        <div class="container text-center">
            <h2 class="mb-4">¿Listo para empezar?</h2>
            <p class="lead mb-4">Regístrate como paciente y comienza a gestionar tus citas médicas de manera digital.</p>
            <button class="btn btn-primary btn-lg" data-bs-toggle="tab" data-bs-target="#register">Registrarse Ahora</button>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cambiar a pestaña de registro si hay mensaje de éxito
        <?php if($success): ?>
            document.getElementById('register-tab').click();
        <?php endif; ?>
    </script>
</body>
</html>
