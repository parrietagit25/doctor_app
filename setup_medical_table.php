<?php
// Script para configurar la tabla de información médica
require_once __DIR__ . '/config/database.php';

echo "<h2>🔧 Configuración de Tabla de Información Médica</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "✅ Conexión a la base de datos: EXITOSA<br>";
        
        // Verificar si la tabla existe
        $stmt = $db->query("SHOW TABLES LIKE 'informacion_medica_paciente'");
        if($stmt->rowCount() > 0) {
            echo "✅ Tabla 'informacion_medica_paciente': EXISTE<br>";
            
            // Mostrar estructura
            echo "<h3>Estructura de la tabla:</h3>";
            $stmt = $db->query("DESCRIBE informacion_medica_paciente");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>";
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "❌ Tabla 'informacion_medica_paciente': NO EXISTE<br>";
            echo "🔧 Creando tabla...<br>";
            
            // Crear la tabla
            $create_table = "
            CREATE TABLE informacion_medica_paciente (
                id INT AUTO_INCREMENT PRIMARY KEY,
                paciente_id INT NOT NULL,
                fecha_nacimiento DATE,
                genero ENUM('masculino', 'femenino', 'otro'),
                direccion TEXT,
                emergencia_contacto VARCHAR(150),
                emergencia_telefono VARCHAR(20),
                grupo_sanguineo VARCHAR(10),
                peso DECIMAL(5,2),
                altura DECIMAL(5,2),
                presion_arterial VARCHAR(20),
                alergias TEXT,
                enfermedades_cronicas TEXT,
                medicamentos_actuales TEXT,
                cirugias_previas TEXT,
                historial_familiar TEXT,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (paciente_id) REFERENCES usuarios(id) ON DELETE CASCADE
            )";
            
            $db->exec($create_table);
            echo "✅ Tabla creada exitosamente<br>";
        }
        
        // Verificar pacientes
        echo "<h3>Pacientes en el sistema:</h3>";
        $stmt = $db->query("SELECT id, nombre, apellido, email FROM usuarios WHERE tipo_usuario = 'paciente' AND activo = 1");
        if($stmt->rowCount() > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Email</th></tr>";
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['nombre'] . "</td>";
                echo "<td>" . $row['apellido'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠️ No hay pacientes registrados<br>";
        }
        
    } else {
        echo "❌ Error: No se pudo conectar a la base de datos<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<a href='index.php'>🏠 Ir al Sistema</a> | ";
echo "<a href='admin/pacientes.php'>👥 Ver Pacientes</a>";
?>
