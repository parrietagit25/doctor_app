<?php
// Script de instalación del Sistema de Citas Médicas
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Configuración de la base de datos
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'doctor_app'
];

if($_POST && $step == 2) {
    // Procesar configuración de base de datos
    $db_config['host'] = $_POST['host'];
    $db_config['username'] = $_POST['username'];
    $db_config['password'] = $_POST['password'];
    $db_config['database'] = $_POST['database'];
    
    try {
        // Probar conexión
        $pdo = new PDO("mysql:host={$db_config['host']}", $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db_config['database']}");
        $pdo->exec("USE {$db_config['database']}");
        
        // Leer y ejecutar schema
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        $statements = explode(';', $schema);
        
        foreach($statements as $statement) {
            $statement = trim($statement);
            if(!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Actualizar archivo de configuración
        $config_content = "<?php
// Configuración de la base de datos
class Database {
    private \$host = '{$db_config['host']}';
    private \$db_name = '{$db_config['database']}';
    private \$username = '{$db_config['username']}';
    private \$password = '{$db_config['password']}';
    private \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name,
                \$this->username,
                \$this->password
            );
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$this->conn->exec(\"set names utf8\");
        } catch(PDOException \$exception) {
            echo \"Error de conexión: \" . \$exception->getMessage();
        }
        
        return \$this->conn;
    }
}
?>";
        
        file_put_contents(__DIR__ . '/config/database.php', $config_content);
        
        $success = 'Base de datos configurada exitosamente.';
        $step = 3;
        
    } catch(PDOException $e) {
        $error = 'Error de conexión: ' . $e->getMessage();
    }
}

if($_POST && $step == 3) {
    // Crear carpeta de uploads
    if(!file_exists(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0755, true);
    }
    
    $success = 'Instalación completada exitosamente.';
    $step = 4;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Citas Médicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .install-container {
            max-width: 800px;
            margin: 50px auto;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step-line {
            width: 50px;
            height: 2px;
            background: #e9ecef;
            margin: auto 0;
        }
        .step-line.completed {
            background: #28a745;
        }
    </style>
</head>
<body class="bg-light">
    <div class="install-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-user-md me-2"></i>Sistema de Citas Médicas</h1>
            <p class="text-muted">Instalación del Sistema</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
            <div class="step-line <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
            <div class="step-line <?php echo $step > 2 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">3</div>
            <div class="step-line <?php echo $step > 3 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if($step == 1): ?>
                    <!-- Step 1: Welcome -->
                    <h3>Bienvenido a la Instalación</h3>
                    <p>Este asistente le ayudará a configurar el Sistema de Citas Médicas.</p>
                    
                    <h5>Requisitos del Sistema:</h5>
                    <ul>
                        <li>PHP 7.4 o superior</li>
                        <li>MySQL 5.7 o superior</li>
                        <li>Servidor web (Apache/Nginx)</li>
                        <li>XAMPP (recomendado para desarrollo)</li>
                    </ul>

                    <h5>Funcionalidades:</h5>
                    <ul>
                        <li>Gestión de usuarios (Administrador, Doctor, Paciente)</li>
                        <li>Sistema de citas médicas</li>
                        <li>Notificaciones por email</li>
                        <li>Historial médico digital</li>
                        <li>Panel administrativo completo</li>
                    </ul>

                    <div class="text-end">
                        <a href="?step=2" class="btn btn-primary">
                            Continuar <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>

                <?php elseif($step == 2): ?>
                    <!-- Step 2: Database Configuration -->
                    <h3>Configuración de Base de Datos</h3>
                    <p>Configure la conexión a su base de datos MySQL.</p>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="host" class="form-label">Host</label>
                                <input type="text" class="form-control" id="host" name="host" value="<?php echo $db_config['host']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="database" class="form-label">Nombre de la Base de Datos</label>
                                <input type="text" class="form-control" id="database" name="database" value="<?php echo $db_config['database']; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo $db_config['username']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" value="<?php echo $db_config['password']; ?>">
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> La base de datos se creará automáticamente si no existe.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="?step=1" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Anterior
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continuar <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>

                <?php elseif($step == 3): ?>
                    <!-- Step 3: Final Setup -->
                    <h3>Configuración Final</h3>
                    <p>Se realizará la configuración final del sistema.</p>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Importante:</strong> Asegúrese de que el servidor web tenga permisos de escritura en la carpeta del proyecto.
                    </div>

                    <form method="POST">
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-cog me-2"></i>Completar Instalación
                            </button>
                        </div>
                    </form>

                <?php elseif($step == 4): ?>
                    <!-- Step 4: Installation Complete -->
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h3 class="mt-3">¡Instalación Completada!</h3>
                        <p class="text-muted">El Sistema de Citas Médicas se ha instalado exitosamente.</p>
                    </div>

                    <div class="alert alert-info">
                        <h5><i class="fas fa-key me-2"></i>Credenciales del Administrador</h5>
                        <p><strong>Email:</strong> admin@doctorapp.com</p>
                        <p><strong>Contraseña:</strong> admin123</p>
                        <p class="mb-0"><small class="text-muted">Por favor, cambie estas credenciales después del primer acceso.</small></p>
                    </div>

                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Importante</h5>
                        <ul class="mb-0">
                            <li>Elimine este archivo <code>install.php</code> por seguridad</li>
                            <li>Configure el sistema de emails en <code>config/email.php</code></li>
                            <li>Revise los permisos de la carpeta <code>uploads/</code></li>
                        </ul>
                    </div>

                    <div class="text-center">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Acceder al Sistema
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
