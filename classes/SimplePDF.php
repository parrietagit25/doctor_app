<?php
// Clase simplificada para generar PDFs sin dependencias externas
// Esta es una implementación básica que genera PDFs simples

class SimplePDF {
    private $content = '';
    private $title = '';
    private $filename = '';
    
    public function __construct($title = '') {
        $this->title = $title;
        $this->content = '';
    }
    
    public function AddPage() {
        $this->content .= "\n--- PÁGINA NUEVA ---\n";
    }
    
    public function SetTitle($title) {
        $this->title = $title;
    }
    
    public function SetFont($family, $style = '', $size = 12) {
        // Simular cambio de fuente
        $this->content .= "\n[FUENTE: $family, ESTILO: $style, TAMAÑO: $size]\n";
    }
    
    public function SetTextColor($r, $g, $b) {
        // Simular cambio de color
        $this->content .= "\n[COLOR: RGB($r,$g,$b)]\n";
    }
    
    public function Cell($w, $h, $txt, $border = 0, $ln = 0, $align = 'L', $fill = false, $link = '') {
        $this->content .= str_pad($txt, $w, ' ', STR_PAD_RIGHT);
        if ($ln == 1) {
            $this->content .= "\n";
        }
    }
    
    public function MultiCell($w, $h, $txt, $border = 0, $align = 'L', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false) {
        $lines = explode("\n", wordwrap($txt, $w / 6)); // Aproximación simple
        foreach ($lines as $line) {
            $this->content .= $line . "\n";
        }
    }
    
    public function Ln($h = '') {
        $this->content .= "\n";
    }
    
    public function SetX($x) {
        // Simular posición X
    }
    
    public function SetY($y) {
        // Simular posición Y
    }
    
    public function SetXY($x, $y) {
        // Simular posición X,Y
    }
    
    public function getAliasNumPage() {
        return '1';
    }
    
    public function getAliasNbPages() {
        return '1';
    }
    
    public function Output($filename, $dest = 'D') {
        // Generar contenido HTML básico
        $html = $this->generateHTML();
        
        if ($dest == 'D') {
            // Descargar archivo
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Convertir HTML a PDF usando mPDF si está disponible, sino mostrar HTML
            if (class_exists('Mpdf\Mpdf')) {
                $mpdf = new \Mpdf\Mpdf();
                $mpdf->WriteHTML($html);
                $mpdf->Output($filename, 'D');
            } else {
                // Fallback: mostrar HTML
                header('Content-Type: text/html');
                echo $html;
            }
        }
    }
    
    private function generateHTML() {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($this->title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #333; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 5px; border-bottom: 1px solid #ddd; }
        .info-table .label { font-weight: bold; width: 30%; }
        .products-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .products-table th, .products-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .products-table th { background-color: #f5f5f5; font-weight: bold; }
        .totals { text-align: right; margin-top: 20px; }
        .total-row { font-weight: bold; font-size: 14px; padding: 5px 0; }
        .notes { margin-top: 20px; }
        .notes-title { font-weight: bold; margin-bottom: 10px; }
        .notes-content { background-color: #f9f9f9; padding: 10px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="header">COTIZACIÓN MÉDICA</div>
    
    <div class="content">
        ' . nl2br(htmlspecialchars($this->content)) . '
    </div>
</body>
</html>';
        
        return $html;
    }
}
?>
