<?php
// Clase para generar PDFs simples sin dependencias externas
class CotizacionPDFSimple {
    
    private $cotizacion_data;
    private $productos_data;
    
    public function __construct($cotizacion_data, $productos_data) {
        $this->cotizacion_data = $cotizacion_data;
        $this->productos_data = $productos_data;
    }
    
    public function generarPDF() {
        // Intentar usar mPDF si está disponible
        if (class_exists('Mpdf\Mpdf')) {
            return $this->generarConMPDF();
        }
        
        // Intentar usar TCPDF si está disponible
        if (class_exists('TCPDF')) {
            return $this->generarConTCPDF();
        }
        
        // Fallback: usar wkhtmltopdf si está disponible
        if ($this->wkhtmltopdfDisponible()) {
            return $this->generarConWkhtmltopdf();
        }
        
        // Fallback final: generar HTML optimizado para impresión
        return $this->generarHTMLOptimizado();
    }
    
    private function generarConMPDF() {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9
        ]);
        
        $html = $this->generarHTML();
        $mpdf->WriteHTML($html);
        
        return $mpdf;
    }
    
    private function generarConTCPDF() {
        require_once __DIR__ . '/../tcpdf/tcpdf.php';
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Gestión Médica');
        $pdf->SetAuthor('Sistema de Gestión Médica');
        $pdf->SetTitle('Cotización #' . $this->cotizacion_data['id']);
        $pdf->SetSubject('Cotización Médica');
        
        // Configuración de márgenes
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 25);
        
        // Agregar página
        $pdf->AddPage();
        
        // Escribir HTML
        $html = $this->generarHTML();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf;
    }
    
    private function wkhtmltopdfDisponible() {
        $output = [];
        $return_var = 0;
        exec('which wkhtmltopdf', $output, $return_var);
        return $return_var === 0;
    }
    
    private function generarConWkhtmltopdf() {
        // Crear archivo temporal HTML
        $temp_html = tempnam(sys_get_temp_dir(), 'cotizacion_') . '.html';
        file_put_contents($temp_html, $this->generarHTML());
        
        // Crear archivo temporal PDF
        $temp_pdf = tempnam(sys_get_temp_dir(), 'cotizacion_') . '.pdf';
        
        // Ejecutar wkhtmltopdf
        $command = "wkhtmltopdf --page-size A4 --margin-top 20mm --margin-bottom 20mm --margin-left 15mm --margin-right 15mm '$temp_html' '$temp_pdf'";
        exec($command, $output, $return_var);
        
        if ($return_var === 0 && file_exists($temp_pdf)) {
            // Leer y retornar contenido del PDF
            $pdf_content = file_get_contents($temp_pdf);
            
            // Limpiar archivos temporales
            unlink($temp_html);
            unlink($temp_pdf);
            
            return $pdf_content;
        } else {
            // Fallback: retornar HTML
            unlink($temp_html);
            return $this->generarHTMLOptimizado();
        }
    }
    
    private function generarHTMLOptimizado() {
        // Generar HTML que se puede imprimir como PDF desde el navegador
        return $this->generarHTML();
    }
    
    private function generarHTML() {
        $fecha_creacion = date('d/m/Y H:i', strtotime($this->cotizacion_data['fecha_creacion']));
        $fecha_vencimiento = $this->cotizacion_data['fecha_vencimiento'] ? 
            date('d/m/Y', strtotime($this->cotizacion_data['fecha_vencimiento'])) : 'Sin fecha';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Cotización #' . $this->cotizacion_data['id'] . '</title>
            <style>
                @page {
                    size: A4;
                    margin: 20mm;
                }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 0; 
                    font-size: 12px;
                    line-height: 1.4;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 15px;
                }
                .header h1 { 
                    font-size: 24px; 
                    color: #333; 
                    margin: 0; 
                    font-weight: bold;
                }
                .section { 
                    margin-bottom: 20px; 
                }
                .section-title { 
                    font-size: 16px; 
                    font-weight: bold; 
                    color: #333; 
                    margin-bottom: 10px;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
                }
                .info-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 20px; 
                }
                .info-table td { 
                    padding: 5px; 
                    border-bottom: 1px solid #ddd; 
                    vertical-align: top;
                }
                .info-table .label { 
                    font-weight: bold; 
                    width: 25%; 
                    color: #555;
                }
                .products-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 20px; 
                }
                .products-table th, 
                .products-table td { 
                    padding: 8px; 
                    border: 1px solid #ddd; 
                    text-align: left; 
                }
                .products-table th { 
                    background-color: #f5f5f5; 
                    font-weight: bold; 
                    text-align: center;
                }
                .products-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .totals { 
                    text-align: right; 
                    margin-top: 20px; 
                }
                .totals-table { 
                    border-collapse: collapse; 
                    display: inline-block;
                    min-width: 300px;
                }
                .totals-table td { 
                    padding: 8px 15px; 
                    border-bottom: 1px solid #ddd; 
                }
                .totals-table .label { 
                    font-weight: bold; 
                    text-align: left;
                }
                .total-row { 
                    border-top: 2px solid #007bff; 
                    background-color: #f8f9fa; 
                    font-weight: bold;
                }
                .notes { 
                    margin-top: 20px; 
                }
                .notes-content { 
                    background-color: #f8f9fa; 
                    padding: 15px; 
                    border-left: 4px solid #007bff; 
                    border-radius: 4px;
                }
                .footer { 
                    margin-top: 30px; 
                    text-align: center; 
                    font-size: 10px; 
                    color: #666; 
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>COTIZACIÓN MÉDICA</h1>
            </div>
            
            <div class="section">
                <div class="section-title">INFORMACIÓN GENERAL</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Número de Cotización:</td>
                        <td><strong># ' . htmlspecialchars($this->cotizacion_data['id']) . '</strong></td>
                        <td class="label">Fecha de Creación:</td>
                        <td>' . htmlspecialchars($fecha_creacion) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Paciente:</td>
                        <td>' . htmlspecialchars($this->cotizacion_data['paciente_nombre'] . ' ' . $this->cotizacion_data['paciente_apellido']) . '</td>
                        <td class="label">Fecha de Vencimiento:</td>
                        <td>' . htmlspecialchars($fecha_vencimiento) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Doctor:</td>
                        <td>' . htmlspecialchars($this->cotizacion_data['doctor_nombre'] . ' ' . $this->cotizacion_data['doctor_apellido']) . '</td>
                        <td class="label">Estado:</td>
                        <td>' . ucfirst($this->cotizacion_data['estado']) . '</td>
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
        
        foreach ($this->productos_data as $producto) {
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
                            <td class="text-right">$' . number_format($this->cotizacion_data['subtotal'], 2) . '</td>
                        </tr>
                        <tr>
                            <td class="label">Impuesto (18%):</td>
                            <td class="text-right">$' . number_format($this->cotizacion_data['impuesto'], 2) . '</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label">TOTAL:</td>
                            <td class="text-right">$' . number_format($this->cotizacion_data['total'], 2) . '</td>
                        </tr>
                    </table>
                </div>
            </div>';
        
        if (!empty($this->cotizacion_data['notas'])) {
            $html .= '
            <div class="section">
                <div class="section-title">NOTAS</div>
                <div class="notes-content">
                    ' . nl2br(htmlspecialchars($this->cotizacion_data['notas'])) . '
                </div>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                <p>Este documento fue generado automáticamente por el Sistema de Gestión Médica</p>
                <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
?>
