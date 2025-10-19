<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Cotizacion.php';
require_once __DIR__ . '/../classes/CotizacionProducto.php';

// Cargar mPDF
require_once __DIR__ . '/../vendor/autoload.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['administrador', 'doctor'])) {
    die('No autorizado');
}

if(!isset($_GET['cotizacion_id']) || empty($_GET['cotizacion_id'])) {
    die('ID de cotización no válido');
}

$database = new Database();
$db = $database->getConnection();
$cotizacion = new Cotizacion($db);
$cotizacionProducto = new CotizacionProducto($db);

$cotizacion_id = intval($_GET['cotizacion_id']);

try {
    // Obtener datos de la cotización
    $cotizacion_data = $cotizacion->obtenerPorId($cotizacion_id);
    if(!$cotizacion_data) {
        die('Cotización no encontrada');
    }

    // Verificar permisos (doctores solo pueden exportar sus propias cotizaciones)
    if($_SESSION['user_type'] == 'doctor' && $_SESSION['user_id'] != $cotizacion_data['doctor_id']) {
        die('No tienes permisos para exportar esta cotización');
    }

    // Obtener productos de la cotización
    $stmt_productos = $cotizacionProducto->obtenerPorCotizacion($cotizacion_id);
    $productos_data = [];
    while($producto = $stmt_productos->fetch(PDO::FETCH_ASSOC)) {
        $productos_data[] = $producto;
    }

    // Crear instancia de mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'P',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 16,
        'margin_bottom' => 16
    ]);

    // Preparar fechas
    $fecha_creacion = date('d/m/Y H:i', strtotime($cotizacion_data['fecha_creacion']));
    $fecha_vencimiento = $cotizacion_data['fecha_vencimiento'] ? 
        date('d/m/Y', strtotime($cotizacion_data['fecha_vencimiento'])) : 'Sin fecha';
    
    // HTML para el PDF
    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #000; padding-bottom: 15px; }
        .header h1 { font-size: 24px; color: #000; margin: 0; font-weight: bold; text-transform: uppercase; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: bold; color: #000; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 6px; border-bottom: 1px solid #000; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 25%; }
        .products-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 2px solid #000; }
        .products-table th, .products-table td { padding: 8px; border: 1px solid #000; text-align: left; }
        .products-table th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        .totals { text-align: right; margin-top: 20px; }
        .totals-table { border-collapse: collapse; display: inline-block; min-width: 300px; border: 2px solid #000; }
        .totals-table td { padding: 8px 15px; border: 1px solid #000; }
        .totals-table .label { font-weight: bold; text-align: left; background-color: #f0f0f0; }
        .total-row { border-top: 3px solid #000; background-color: #e0e0e0; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
    
    <div class="header">
        <h1>COTIZACIÓN MÉDICA</h1>
    </div>
    
    <div class="section">
        <div class="section-title">INFORMACIÓN GENERAL</div>
        <table class="info-table">
            <tr>
                <td class="label">Número de Cotización:</td>
                <td><strong># ' . htmlspecialchars($cotizacion_data['id']) . '</strong></td>
                <td class="label">Fecha de Creación:</td>
                <td>' . htmlspecialchars($fecha_creacion) . '</td>
            </tr>
            <tr>
                <td class="label">Paciente:</td>
                <td>' . htmlspecialchars($cotizacion_data['paciente_nombre'] . ' ' . $cotizacion_data['paciente_apellido']) . '</td>
                <td class="label">Fecha de Vencimiento:</td>
                <td>' . htmlspecialchars($fecha_vencimiento) . '</td>
            </tr>
            <tr>
                <td class="label">Doctor:</td>
                <td>' . htmlspecialchars($cotizacion_data['doctor_nombre'] . ' ' . $cotizacion_data['doctor_apellido']) . '</td>
                <td class="label">Estado:</td>
                <td>' . ucfirst($cotizacion_data['estado']) . '</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">PRODUCTOS/SERVICIOS</div>
        <table class="products-table">
            <thead>
                <tr>
                    <th>Producto/Servicio</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($productos_data as $producto) {
        $html .= '
                <tr>
                    <td><strong>' . htmlspecialchars($producto['producto_nombre']) . '</strong></td>
                    <td class="text-center">' . $producto['cantidad'] . '</td>
                    <td class="text-right">$' . number_format($producto['precio_unitario'], 2) . '</td>
                    <td class="text-right"><strong>$' . number_format($producto['subtotal'], 2) . '</strong></td>
                    <td>' . htmlspecialchars($producto['descripcion']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </div>
    
    <div class="section">
        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="text-right">$' . number_format($cotizacion_data['subtotal'], 2) . '</td>
                </tr>
                <tr>
                    <td class="label">Impuesto (18%):</td>
                    <td class="text-right">$' . number_format($cotizacion_data['impuesto'], 2) . '</td>
                </tr>
                <tr class="total-row">
                    <td class="label">TOTAL:</td>
                    <td class="text-right">$' . number_format($cotizacion_data['total'], 2) . '</td>
                </tr>
            </table>
        </div>
    </div>';
    
    if (!empty($cotizacion_data['notas'])) {
        $html .= '
        <div class="section">
            <div class="section-title">NOTAS</div>
            <div style="background-color: #f9f9f9; padding: 15px; border: 2px solid #000; border-radius: 5px;">
                ' . nl2br(htmlspecialchars($cotizacion_data['notas'])) . '
            </div>
        </div>';
    }
    
    $html .= '
    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #000; padding-top: 15px;">
        <p>Este documento fue generado automáticamente por el Sistema de Gestión Médica</p>
        <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
    </div>';

    // Escribir HTML en mPDF
    $mpdf->WriteHTML($html);
    
    // Nombre del archivo
    $filename = 'cotizacion_' . $cotizacion_data['id'] . '_' . 
                $cotizacion_data['paciente_nombre'] . '_' . 
                $cotizacion_data['paciente_apellido'] . '_' . 
                date('Y-m-d') . '.pdf';

    // Limpiar caracteres especiales del nombre de archivo
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

    // Limpiar buffer de salida
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Enviar PDF para descarga
    $mpdf->Output($filename, 'D'); // 'D' = download

} catch(Exception $e) {
    die('Error al generar el PDF: ' . $e->getMessage());
}
?>
