<?php
// Script simple para instalar TCPDF
// Ejecutar este archivo una vez para descargar TCPDF

echo "Instalando TCPDF...\n";

// Crear directorio tcpdf si no existe
if (!file_exists('tcpdf')) {
    mkdir('tcpdf', 0777, true);
}

// URL de TCPDF
$tcpdf_url = 'https://github.com/tecnickcom/TCPDF/archive/main.zip';
$zip_file = 'tcpdf.zip';

// Descargar TCPDF
echo "Descargando TCPDF desde GitHub...\n";
$zip_content = file_get_contents($tcpdf_url);

if ($zip_content === false) {
    die("Error: No se pudo descargar TCPDF\n");
}

// Guardar archivo ZIP
file_put_contents($zip_file, $zip_content);
echo "TCPDF descargado exitosamente.\n";

// Extraer ZIP
$zip = new ZipArchive();
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo('tcpdf');
    $zip->close();
    echo "TCPDF extraído exitosamente.\n";
    
    // Eliminar archivo ZIP
    unlink($zip_file);
    
    // Mover archivos al directorio correcto
    $extracted_dir = 'tcpdf/TCPDF-main';
    if (file_exists($extracted_dir)) {
        // Mover archivos
        $files = scandir($extracted_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                rename($extracted_dir . '/' . $file, 'tcpdf/' . $file);
            }
        }
        
        // Eliminar directorio vacío
        rmdir($extracted_dir);
        echo "Archivos organizados correctamente.\n";
    }
    
    echo "Instalación completada exitosamente!\n";
    echo "Puedes eliminar este archivo install_tcpdf.php\n";
} else {
    die("Error: No se pudo extraer TCPDF\n");
}
?>
