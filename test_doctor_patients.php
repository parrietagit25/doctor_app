<?php
// Script de prueba para verificar el módulo de pacientes del doctor
require_once __DIR__ . '/config/database.php';

echo "<h2>🔍 Prueba del Módulo de Pacientes del Doctor</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "✅ Conexión a la base de datos: EXITOSA<br>";
        
        // Verificar doctores
        echo "<h3>👨‍⚕️ Doctores en el sistema:</h3>";
        $query = "SELECT id, nombre, apellido, especialidad FROM usuarios WHERE tipo_usuario = 'doctor' AND activo = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Especialidad</th><th>Pacientes</th></tr>";
            
            while($doctor = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Contar pacientes para este doctor
                $count_query = "SELECT COUNT(DISTINCT paciente_id) as total_pacientes FROM citas WHERE doctor_id = :doctor_id";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->bindParam(':doctor_id', $doctor['id']);
                $count_stmt->execute();
                $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<tr>";
                echo "<td>" . $doctor['id'] . "</td>";
                echo "<td>" . $doctor['nombre'] . "</td>";
                echo "<td>" . $doctor['apellido'] . "</td>";
                echo "<td>" . $doctor['especialidad'] . "</td>";
                echo "<td>" . $count_result['total_pacientes'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠️ No hay doctores registrados<br>";
        }
        
        // Verificar citas y relaciones doctor-paciente
        echo "<h3>📅 Relaciones Doctor-Paciente:</h3>";
        $query = "SELECT c.doctor_id, c.paciente_id, d.nombre as doctor_nombre, d.apellido as doctor_apellido, 
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                         COUNT(c.id) as total_citas
                  FROM citas c
                  LEFT JOIN usuarios d ON c.doctor_id = d.id
                  LEFT JOIN usuarios p ON c.paciente_id = p.id
                  GROUP BY c.doctor_id, c.paciente_id
                  ORDER BY c.doctor_id, total_citas DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Doctor</th><th>Paciente</th><th>Total Citas</th></tr>";
            
            while($relation = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $relation['doctor_nombre'] . " " . $relation['doctor_apellido'] . "</td>";
                echo "<td>" . $relation['paciente_nombre'] . " " . $relation['paciente_apellido'] . "</td>";
                echo "<td>" . $relation['total_citas'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠️ No hay citas registradas<br>";
        }
        
        // Verificar información médica de pacientes
        echo "<h3>🏥 Información Médica de Pacientes:</h3>";
        $query = "SELECT COUNT(*) as total FROM informacion_medica_paciente";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de pacientes con información médica: " . $result['total'] . "<br>";
        
        // Verificar historial médico
        echo "<h3>📋 Historial Médico:</h3>";
        $query = "SELECT COUNT(*) as total FROM historial_medico";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de registros en historial médico: " . $result['total'] . "<br>";
        
        // Mostrar estadísticas generales
        echo "<h3>📊 Estadísticas Generales:</h3>";
        $stats = [
            'usuarios' => $db->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn(),
            'doctores' => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'doctor' AND activo = 1")->fetchColumn(),
            'pacientes' => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'paciente' AND activo = 1")->fetchColumn(),
            'citas' => $db->query("SELECT COUNT(*) FROM citas")->fetchColumn()
        ];
        
        foreach($stats as $key => $value) {
            echo "📈 " . ucfirst($key) . ": " . $value . "<br>";
        }
        
    } else {
        echo "❌ Error: No se pudo conectar a la base de datos<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>🔗 Enlaces de Prueba:</h3>";
echo "<a href='index.php' style='margin-right: 10px;'>🏠 Sistema Principal</a>";
echo "<a href='admin/pacientes.php' style='margin-right: 10px;'>👥 Pacientes (Admin)</a>";
echo "<a href='doctor/mis_pacientes.php' style='margin-right: 10px;'>👨‍⚕️ Mis Pacientes (Doctor)</a>";
?>
