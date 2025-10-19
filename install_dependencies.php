<?php
// Script para instalar Composer y DomPDF
echo "Instalando dependencias para generación de PDFs...\n";

// Verificar si Composer está instalado
$composer_installed = false;
if (file_exists('composer.phar')) {
    $composer_installed = true;
    echo "Composer ya está instalado.\n";
} else {
    echo "Descargando Composer...\n";
    
    // Descargar Composer
    $composer_installer = file_get_contents('https://getcomposer.org/installer');
    if ($composer_installer === false) {
        die("Error: No se pudo descargar el instalador de Composer\n");
    }
    
    // Ejecutar el instalador
    $installer_file = tempnam(sys_get_temp_dir(), 'composer_installer') . '.php';
    file_put_contents($installer_file, $composer_installer);
    
    // Ejecutar el instalador
    $output = [];
    $return_var = 0;
    exec("php $installer_file", $output, $return_var);
    
    if ($return_var === 0 && file_exists('composer.phar')) {
        $composer_installed = true;
        echo "Composer instalado exitosamente.\n";
    } else {
        die("Error: No se pudo instalar Composer\n");
    }
    
    // Limpiar archivo temporal
    unlink($installer_file);
}

// Instalar dependencias
if ($composer_installed) {
    echo "Instalando DomPDF...\n";
    
    $output = [];
    $return_var = 0;
    exec("php composer.phar install --no-dev --optimize-autoloader", $output, $return_var);
    
    if ($return_var === 0) {
        echo "DomPDF instalado exitosamente!\n";
        echo "Las dependencias están listas para usar.\n";
        echo "\nPuedes eliminar este archivo install_dependencies.php\n";
    } else {
        echo "Error al instalar dependencias:\n";
        foreach ($output as $line) {
            echo $line . "\n";
        }
    }
} else {
    die("No se pudo instalar Composer\n");
}

echo "\nInstalación completada!\n";
?>
