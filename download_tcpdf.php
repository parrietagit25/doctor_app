<?php
// Script para descargar TCPDF
// Este archivo se puede ejecutar una vez para descargar TCPDF
// Luego se puede eliminar

$tcpdf_url = 'https://github.com/tecnickcom/TCPDF/archive/main.zip';
$zip_file = 'tcpdf.zip';
$extract_dir = 'tcpdf';

// Descargar TCPDF
echo "Descargando TCPDF...\n";
$zip_content = file_get_contents($tcpdf_url);

if ($zip_content === false) {
    die("Error: No se pudo descargar TCPDF\n");
}

// Guardar archivo ZIP
file_put_contents($zip_file, $zip_content);

// Crear directorio de extracción
if (!file_exists($extract_dir)) {
    mkdir($extract_dir, 0777, true);
}

// Extraer ZIP
$zip = new ZipArchive();
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo($extract_dir);
    $zip->close();
    echo "TCPDF extraído exitosamente en la carpeta: " . $extract_dir . "\n";
    
    // Eliminar archivo ZIP
    unlink($zip_file);
    
    echo "Instalación completada. Puede eliminar este archivo download_tcpdf.php\n";
} else {
    die("Error: No se pudo extraer TCPDF\n");
}
?>
