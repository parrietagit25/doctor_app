<?php
require_once __DIR__ . '/../tcpdf/tcpdf.php';

class CotizacionPDF extends TCPDF {
    
    private $cotizacion_data;
    private $productos_data;
    
    public function __construct($cotizacion_data, $productos_data) {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $this->cotizacion_data = $cotizacion_data;
        $this->productos_data = $productos_data;
        
        // Configuración del documento
        $this->SetCreator('Sistema de Gestión Médica');
        $this->SetAuthor('Sistema de Gestión Médica');
        $this->SetTitle('Cotización #' . $cotizacion_data['id']);
        $this->SetSubject('Cotización Médica');
        
        // Configuración de márgenes
        $this->SetMargins(15, 20, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(10);
        
        // Configuración de fuente
        $this->SetFont('helvetica', '', 10);
        
        // Auto page breaks
        $this->SetAutoPageBreak(TRUE, 25);
        
        // Agregar página
        $this->AddPage();
    }
    
    // Header personalizado
    public function Header() {
        // Logo (si existe)
        $logo_path = __DIR__ . '/../assets/logo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Título
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 15, 'COTIZACIÓN MÉDICA', 0, 1, 'C');
        
        // Línea separadora
        $this->SetDrawColor(0, 0, 0);
        $this->Line(15, 35, 195, 35);
        
        $this->Ln(10);
    }
    
    // Footer personalizado
    public function Footer() {
        // Posición a 15 mm del final
        $this->SetY(-15);
        
        // Fuente
        $this->SetFont('helvetica', 'I', 8);
        
        // Número de página
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
    }
    
    // Generar el contenido del PDF
    public function generarPDF() {
        $this->generarInformacionGeneral();
        $this->generarProductos();
        $this->generarTotales();
        $this->generarNotas();
    }
    
    private function generarInformacionGeneral() {
        // Información general
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 8, 'INFORMACIÓN GENERAL', 0, 1, 'L');
        $this->Ln(2);
        
        $this->SetFont('helvetica', '', 10);
        
        // Datos en dos columnas
        $col1_width = 60;
        $col2_width = 60;
        
        // Columna izquierda
        $this->Cell($col1_width, 6, 'Número de Cotización:', 0, 0, 'L');
        $this->Cell($col2_width, 6, '# ' . $this->cotizacion_data['id'], 0, 1, 'L');
        
        $this->Cell($col1_width, 6, 'Paciente:', 0, 0, 'L');
        $this->Cell($col2_width, 6, $this->cotizacion_data['paciente_nombre'] . ' ' . $this->cotizacion_data['paciente_apellido'], 0, 1, 'L');
        
        $this->Cell($col1_width, 6, 'Doctor:', 0, 0, 'L');
        $this->Cell($col2_width, 6, $this->cotizacion_data['doctor_nombre'] . ' ' . $this->cotizacion_data['doctor_apellido'], 0, 1, 'L');
        
        // Columna derecha
        $this->SetXY(120, $this->GetY() - 18);
        
        $this->Cell($col1_width, 6, 'Fecha de Creación:', 0, 0, 'L');
        $this->Cell($col2_width, 6, date('d/m/Y H:i', strtotime($this->cotizacion_data['fecha_creacion'])), 0, 1, 'L');
        
        $this->Cell($col1_width, 6, 'Fecha de Vencimiento:', 0, 0, 'L');
        $fecha_vencimiento = $this->cotizacion_data['fecha_vencimiento'] ? 
            date('d/m/Y', strtotime($this->cotizacion_data['fecha_vencimiento'])) : 'Sin fecha';
        $this->Cell($col2_width, 6, $fecha_vencimiento, 0, 1, 'L');
        
        $this->Cell($col1_width, 6, 'Estado:', 0, 0, 'L');
        $this->Cell($col2_width, 6, ucfirst($this->cotizacion_data['estado']), 0, 1, 'L');
        
        $this->Ln(10);
    }
    
    private function generarProductos() {
        // Encabezado de productos
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 8, 'PRODUCTOS/SERVICIOS', 0, 1, 'L');
        $this->Ln(2);
        
        // Encabezados de tabla
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(240, 240, 240);
        
        $this->Cell(80, 8, 'Producto/Servicio', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Cant.', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Precio Unit.', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Subtotal', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Descripción', 1, 1, 'C', true);
        
        // Datos de productos
        $this->SetFont('helvetica', '', 9);
        $this->SetFillColor(255, 255, 255);
        
        foreach ($this->productos_data as $producto) {
            // Calcular altura necesaria para la descripción
            $desc_height = $this->getStringHeight(40, $producto['descripcion'], false, true, '', 1);
            $cell_height = max(8, $desc_height + 2);
            
            $this->Cell(80, $cell_height, $producto['producto_nombre'], 1, 0, 'L', true);
            $this->Cell(15, $cell_height, $producto['cantidad'], 1, 0, 'C', true);
            $this->Cell(25, $cell_height, '$' . number_format($producto['precio_unitario'], 2), 1, 0, 'R', true);
            $this->Cell(25, $cell_height, '$' . number_format($producto['subtotal'], 2), 1, 0, 'R', true);
            $this->MultiCell(40, $cell_height, $producto['descripcion'], 1, 'L', true, 0, '', '', true, 0, false, true, $cell_height, 'M');
        }
        
        $this->Ln(5);
    }
    
    private function generarTotales() {
        // Totales
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        
        // Alinear a la derecha
        $this->SetX(120);
        
        $this->Cell(30, 8, 'Subtotal:', 0, 0, 'L');
        $this->Cell(25, 8, '$' . number_format($this->cotizacion_data['subtotal'], 2), 0, 1, 'R');
        
        $this->SetX(120);
        $this->Cell(30, 8, 'Impuesto (18%):', 0, 0, 'L');
        $this->Cell(25, 8, '$' . number_format($this->cotizacion_data['impuesto'], 2), 0, 1, 'R');
        
        $this->SetX(120);
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(30, 8, 'TOTAL:', 0, 0, 'L');
        $this->Cell(25, 8, '$' . number_format($this->cotizacion_data['total'], 2), 0, 1, 'R');
        
        $this->Ln(10);
    }
    
    private function generarNotas() {
        if (!empty($this->cotizacion_data['notas'])) {
            $this->SetFont('helvetica', 'B', 12);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 8, 'NOTAS', 0, 1, 'L');
            $this->Ln(2);
            
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell(0, 6, $this->cotizacion_data['notas'], 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
            $this->Ln(5);
        }
    }
}
?>
