<?php
// Script para configurar la base de datos
require_once __DIR__ . '/config/database.php';

try {
    // Intentar conectar
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "✅ Conexión a la base de datos exitosa.\n";
        
        // Verificar si las tablas existen
        $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
        if($stmt->rowCount() > 0) {
            echo "✅ Tabla 'usuarios' existe.\n";
            
            // Verificar si hay datos
            $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Total de usuarios: " . $result['total'] . "\n";
            
            if($result['total'] == 0) {
                echo "⚠️  No hay usuarios en la base de datos.\n";
                echo "💡 Ejecuta el script de instalación o importa el schema.sql manualmente.\n";
            }
        } else {
            echo "❌ Tabla 'usuarios' no existe.\n";
            echo "💡 Necesitas ejecutar el schema.sql para crear las tablas.\n";
        }
        
    } else {
        echo "❌ Error: No se pudo conectar a la base de datos.\n";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Verifica la configuración en config/database.php\n";
}
?>
