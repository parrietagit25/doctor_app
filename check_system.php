<?php
// Script de verificación del sistema
echo "<h2>🔍 Verificación del Sistema de Citas Médicas</h2>";

// Verificar configuración de base de datos
echo "<h3>📊 Base de Datos</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "✅ Conexión a la base de datos: <span style='color: green;'>EXITOSA</span><br>";
        
        // Verificar tablas
        $tables = ['usuarios', 'citas', 'archivos_consulta', 'historial_medico'];
        foreach($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if($stmt->rowCount() > 0) {
                echo "✅ Tabla '$table': <span style='color: green;'>EXISTE</span><br>";
                
                // Contar registros
                $count_stmt = $db->query("SELECT COUNT(*) as total FROM $table");
                $count = $count_stmt->fetch(PDO::FETCH_ASSOC);
                echo "&nbsp;&nbsp;&nbsp;📈 Registros: " . $count['total'] . "<br>";
            } else {
                echo "❌ Tabla '$table': <span style='color: red;'>NO EXISTE</span><br>";
            }
        }
        
        // Verificar usuario administrador
        $stmt = $db->query("SELECT * FROM usuarios WHERE tipo_usuario = 'administrador'");
        if($stmt->rowCount() > 0) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✅ Usuario administrador: <span style='color: green;'>CONFIGURADO</span><br>";
            echo "&nbsp;&nbsp;&nbsp;📧 Email: " . $admin['email'] . "<br>";
        } else {
            echo "❌ Usuario administrador: <span style='color: red;'>NO CONFIGURADO</span><br>";
        }
        
    } else {
        echo "❌ Conexión a la base de datos: <span style='color: red;'>FALLIDA</span><br>";
    }
} catch(Exception $e) {
    echo "❌ Error de base de datos: <span style='color: red;'>" . $e->getMessage() . "</span><br>";
}

// Verificar archivos del sistema
echo "<h3>📁 Archivos del Sistema</h3>";
$required_files = [
    'config/database.php',
    'classes/User.php',
    'classes/Appointment.php',
    'admin/dashboard.php',
    'patient/dashboard.php',
    'doctor/dashboard.php'
];

foreach($required_files as $file) {
    if(file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file: <span style='color: green;'>EXISTE</span><br>";
    } else {
        echo "❌ $file: <span style='color: red;'>NO EXISTE</span><br>";
    }
}

// Verificar permisos
echo "<h3>🔐 Permisos</h3>";
$directories = ['uploads'];
foreach($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    if(is_dir($path)) {
        if(is_writable($path)) {
            echo "✅ $dir/: <span style='color: green;'>ESCRITURA PERMITIDA</span><br>";
        } else {
            echo "⚠️ $dir/: <span style='color: orange;'>SIN PERMISOS DE ESCRITURA</span><br>";
        }
    } else {
        echo "❌ $dir/: <span style='color: red;'>NO EXISTE</span><br>";
    }
}

echo "<hr>";
echo "<h3>🛠️ Acciones Recomendadas</h3>";

// Verificar si necesita configuración
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if(!$db) {
        echo "1. ⚠️ <strong>Configurar base de datos:</strong> Edita config/database.php con tus credenciales<br>";
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
    if($stmt->rowCount() == 0) {
        echo "2. ⚠️ <strong>Importar esquema:</strong> Ejecuta database/schema.sql en MySQL<br>";
    }
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    if($count['total'] == 0) {
        echo "3. ⚠️ <strong>Crear usuario administrador:</strong> Ejecuta el script de instalación<br>";
    }
    
} catch(Exception $e) {
    echo "1. ⚠️ <strong>Configurar sistema:</strong> Ejecuta install.php para configuración inicial<br>";
}

echo "<br><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Ir al Sistema</a>";
?>
