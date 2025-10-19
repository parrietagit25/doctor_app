<?php
require_once __DIR__ . '/config/database.php';

echo "Actualizando base de datos para agregar campo de identificación...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Leer el archivo SQL
    $sql = file_get_contents(__DIR__ . '/database/add_identification_field.sql');
    
    // Ejecutar las consultas
    $db->exec($sql);
    
    echo "✅ Base de datos actualizada exitosamente!\n";
    echo "✅ Campo 'identificacion' agregado a la tabla usuarios\n";
    echo "✅ Campo 'email' ya no es obligatorio\n";
    echo "✅ Índice creado para mejorar rendimiento\n";
    
} catch (Exception $e) {
    echo "❌ Error al actualizar la base de datos: " . $e->getMessage() . "\n";
    echo "Por favor, ejecuta manualmente el archivo: database/add_identification_field.sql\n";
}
?>
