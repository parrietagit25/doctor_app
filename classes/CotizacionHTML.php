<?php
class CotizacionHTML {
    
    private $cotizacion_data;
    private $productos_data;
    
    public function __construct($cotizacion_data, $productos_data) {
        $this->cotizacion_data = $cotizacion_data;
        $this->productos_data = $productos_data;
    }
    
    public function generarHTML() {
        $html = $this->getHeader();
        $html .= $this->getInformacionGeneral();
        $html .= $this->getProductos();
        $html .= $this->getTotales();
        $html .= $this->getNotas();
        $html .= $this->getFooter();
        
        return $this->wrapInDocument($html);
    }
    
    private function getHeader() {
        return '
        <div class="header">
            <h1>COTIZACIÓN MÉDICA</h1>
            <div class="header-line"></div>
        </div>';
    }
    
    private function getInformacionGeneral() {
        $fecha_creacion = date('d/m/Y H:i', strtotime($this->cotizacion_data['fecha_creacion']));
        $fecha_vencimiento = $this->cotizacion_data['fecha_vencimiento'] ? 
            date('d/m/Y', strtotime($this->cotizacion_data['fecha_vencimiento'])) : 'Sin fecha';
        
        return '
        <div class="section">
            <h2 class="section-title">INFORMACIÓN GENERAL</h2>
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
                    <td><span class="estado-badge estado-' . $this->cotizacion_data['estado'] . '">' . ucfirst($this->cotizacion_data['estado']) . '</span></td>
                </tr>
            </table>
        </div>';
    }
    
    private function getProductos() {
        if (empty($this->productos_data)) {
            return '
            <div class="section">
                <h2 class="section-title">PRODUCTOS/SERVICIOS</h2>
                <p class="no-products">No hay productos registrados en esta cotización.</p>
            </div>';
        }
        
        $html = '
        <div class="section">
            <h2 class="section-title">PRODUCTOS/SERVICIOS</h2>
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
                        <td>' . nl2br(htmlspecialchars($producto['descripcion'])) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
        
        return $html;
    }
    
    private function getTotales() {
        return '
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
    }
    
    private function getNotas() {
        if (empty($this->cotizacion_data['notas'])) {
            return '';
        }
        
        return '
        <div class="section">
            <h2 class="section-title">NOTAS</h2>
            <div class="notes-content">
                ' . nl2br(htmlspecialchars($this->cotizacion_data['notas'])) . '
            </div>
        </div>';
    }
    
    private function getFooter() {
        return '
        <div class="footer">
            <p>Este documento fue generado automáticamente por el Sistema de Gestión Médica</p>
            <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
        </div>';
    }
    
    private function wrapInDocument($content) {
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización #' . $this->cotizacion_data['id'] . '</title>
    <style>
        ' . $this->getCSS() . '
    </style>
</head>
<body>
    ' . $content . '
</body>
</html>';
    }
    
    private function getCSS() {
        return '
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #fff;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header-line {
            width: 100px;
            height: 3px;
            background-color: #3498db;
            margin: 0 auto;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .info-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: top;
        }
        
        .info-table .label {
            font-weight: bold;
            color: #34495e;
            width: 25%;
        }
        
        .estado-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .estado-pendiente {
            background-color: #f39c12;
            color: white;
        }
        
        .estado-aprobada {
            background-color: #27ae60;
            color: white;
        }
        
        .estado-rechazada {
            background-color: #e74c3c;
            color: white;
        }
        
        .estado-expirada {
            background-color: #95a5a6;
            color: white;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .products-table th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        .products-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .products-table tr:hover {
            background-color: #e8f4f8;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .totals {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .totals-table {
            border-collapse: collapse;
            min-width: 300px;
        }
        
        .totals-table td {
            padding: 8px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .totals-table .label {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .total-row {
            border-top: 2px solid #3498db;
            background-color: #f8f9fa;
        }
        
        .total-row td {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .notes-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #3498db;
            border-radius: 4px;
        }
        
        .no-products {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 20px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .header {
                margin-bottom: 20px;
            }
            
            .section {
                margin-bottom: 20px;
            }
        }
        ';
    }
}
?>
