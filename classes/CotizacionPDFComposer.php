<?php
// Clase para generar PDFs usando DomPDF instalado via Composer
class CotizacionPDFComposer {
    
    private $cotizacion_data;
    private $productos_data;
    
    public function __construct($cotizacion_data, $productos_data) {
        $this->cotizacion_data = $cotizacion_data;
        $this->productos_data = $productos_data;
    }
    
    public function generarPDF() {
        // Cargar autoloader de Composer si existe
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        
        // Intentar usar DomPDF si está disponible
        if (class_exists('Dompdf\Dompdf')) {
            return $this->generarConDomPDF();
        }
        
        // Fallback: usar TCPDF si está disponible
        if (class_exists('TCPDF')) {
            return $this->generarConTCPDF();
        }
        
        // Fallback: usar mPDF si está disponible
        if (class_exists('Mpdf\Mpdf')) {
            return $this->generarConMPDF();
        }
        
        // Fallback final: HTML optimizado
        return $this->generarHTMLOptimizado();
    }
    
    private function generarConDomPDF() {
        try {
            $dompdf = new \Dompdf\Dompdf([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'isRemoteEnabled' => false,
                'isFontSubsettingEnabled' => true
            ]);
            
            $dompdf->loadHtml($this->generarHTML());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            return $dompdf->output();
        } catch (Exception $e) {
            // Si hay error con DomPDF, usar fallback
            return $this->generarHTMLOptimizado();
        }
    }
    
    private function generarConTCPDF() {
        try {
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
        } catch (Exception $e) {
            // Si hay error con TCPDF, usar fallback
            return $this->generarHTMLOptimizado();
        }
    }
    
    private function generarConMPDF() {
        try {
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
        } catch (Exception $e) {
            // Si hay error con mPDF, usar fallback
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
                    font-family: "DejaVu Sans", Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    font-size: 12px;
                    line-height: 1.4;
                    color: #000;
                    background: #fff;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 3px solid #000;
                    padding-bottom: 15px;
                }
                .header h1 { 
                    font-size: 28px; 
                    color: #000; 
                    margin: 0; 
                    font-weight: bold;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                .section { 
                    margin-bottom: 25px; 
                }
                .section-title { 
                    font-size: 16px; 
                    font-weight: bold; 
                    color: #000; 
                    margin-bottom: 15px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 5px;
                    text-transform: uppercase;
                }
                .info-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 25px; 
                }
                .info-table td { 
                    padding: 8px; 
                    border-bottom: 1px solid #000; 
                    vertical-align: top;
                }
                .info-table .label { 
                    font-weight: bold; 
                    width: 25%; 
                    color: #000;
                }
                .products-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 25px; 
                    border: 2px solid #000;
                }
                .products-table th, 
                .products-table td { 
                    padding: 10px; 
                    border: 1px solid #000; 
                    text-align: left; 
                }
                .products-table th { 
                    background-color: #f0f0f0; 
                    font-weight: bold; 
                    text-align: center;
                    text-transform: uppercase;
                }
                .products-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .totals { 
                    text-align: right; 
                    margin-top: 25px; 
                }
                .totals-table { 
                    border-collapse: collapse; 
                    display: inline-block;
                    min-width: 350px;
                    border: 2px solid #000;
                }
                .totals-table td { 
                    padding: 10px 20px; 
                    border: 1px solid #000; 
                }
                .totals-table .label { 
                    font-weight: bold; 
                    text-align: left;
                    background-color: #f0f0f0;
                }
                .total-row { 
                    border-top: 3px solid #000; 
                    background-color: #e0e0e0; 
                    font-weight: bold;
                    font-size: 14px;
                }
                .notes { 
                    margin-top: 25px; 
                }
                .notes-content { 
                    background-color: #f9f9f9; 
                    padding: 15px; 
                    border: 2px solid #000; 
                    border-radius: 5px;
                }
                .footer { 
                    margin-top: 40px; 
                    text-align: center; 
                    font-size: 10px; 
                    color: #666; 
                    border-top: 1px solid #000;
                    padding-top: 15px;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                .no-print {
                    display: none;
                }
                @media print {
                    body { margin: 0; padding: 0; }
                    .no-print { display: none !important; }
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
