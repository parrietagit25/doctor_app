<?php
// Script de prueba para verificar la información del paciente
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/PatientMedicalInfo.php';

echo "<h2>🔍 Prueba de Información del Paciente</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "✅ Conexión a la base de datos: EXITOSA<br>";
        
        $user = new User($db);
        $patientInfo = new PatientMedicalInfo($db);
        
        // Buscar un paciente
        $query = "SELECT id FROM usuarios WHERE tipo_usuario = 'paciente' AND activo = 1 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $patient_id = $result['id'];
            
            echo "<h3>Probando con paciente ID: $patient_id</h3>";
            
            // Probar obtenerPorId
            $patient_data = $user->obtenerPorId($patient_id);
            if($patient_data && is_object($patient_data)) {
                echo "✅ obtenerPorId(): EXITOSO<br>";
                echo "&nbsp;&nbsp;Nombre: " . $patient_data->nombre . " " . $patient_data->apellido . "<br>";
                echo "&nbsp;&nbsp;Email: " . $patient_data->email . "<br>";
                echo "&nbsp;&nbsp;Tipo: " . $patient_data->tipo_usuario . "<br>";
            } else {
                echo "❌ obtenerPorId(): FALLÓ<br>";
            }
            
            // Probar obtenerPorPaciente
            $medical_data = $patientInfo->obtenerPorPaciente($patient_id);
            if($medical_data && is_array($medical_data)) {
                echo "✅ obtenerPorPaciente(): EXITOSO<br>";
                echo "&nbsp;&nbsp;Fecha nacimiento: " . ($medical_data['fecha_nacimiento'] ?: 'No registrada') . "<br>";
                echo "&nbsp;&nbsp;Género: " . ($medical_data['genero'] ?: 'No registrado') . "<br>";
            } else {
                echo "ℹ️ obtenerPorPaciente(): Sin información médica registrada<br>";
            }
            
        } else {
            echo "⚠️ No hay pacientes registrados en la base de datos<br>";
            echo "💡 Registra un paciente primero desde el sistema<br>";
        }
        
    } else {
        echo "❌ Error: No se pudo conectar a la base de datos<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<a href='index.php'>🏠 Ir al Sistema</a>";
?>
