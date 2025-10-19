<?php
class Cotizacion {
    private $conn;
    private $table_name = "cotizaciones";

    // Propiedades de la cotización
    public $id;
    public $paciente_id;
    public $doctor_id;
    public $fecha_creacion;
    public $fecha_vencimiento;
    public $subtotal;
    public $impuesto;
    public $total;
    public $estado;
    public $notas;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nueva cotización
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (paciente_id, doctor_id, fecha_vencimiento, subtotal, impuesto, total, estado, notas) 
                  VALUES (:paciente_id, :doctor_id, :fecha_vencimiento, :subtotal, :impuesto, :total, :estado, :notas)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->paciente_id = htmlspecialchars(strip_tags($this->paciente_id));
        $this->doctor_id = htmlspecialchars(strip_tags($this->doctor_id));
        $this->fecha_vencimiento = htmlspecialchars(strip_tags($this->fecha_vencimiento));
        $this->subtotal = floatval($this->subtotal);
        $this->impuesto = floatval($this->impuesto);
        $this->total = floatval($this->total);
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->notas = htmlspecialchars(strip_tags($this->notas));

        // Bind parameters
        $stmt->bindParam(':paciente_id', $this->paciente_id);
        $stmt->bindParam(':doctor_id', $this->doctor_id);
        $stmt->bindParam(':fecha_vencimiento', $this->fecha_vencimiento);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $stmt->bindParam(':impuesto', $this->impuesto);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':notas', $this->notas);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtener cotizaciones por paciente
    public function obtenerPorPaciente($paciente_id) {
        $query = "SELECT c.id, c.paciente_id, c.doctor_id, c.fecha_creacion, c.fecha_vencimiento, 
                         c.subtotal, c.impuesto, c.total, c.estado, c.notas,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios p ON c.paciente_id = p.id
                  LEFT JOIN usuarios d ON c.doctor_id = d.id
                  WHERE c.paciente_id = :paciente_id
                  ORDER BY c.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->execute();

        return $stmt;
    }

    // Obtener cotización por ID
    public function obtenerPorId($cotizacion_id) {
        $query = "SELECT c.id, c.paciente_id, c.doctor_id, c.fecha_creacion, c.fecha_vencimiento, 
                         c.subtotal, c.impuesto, c.total, c.estado, c.notas,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios p ON c.paciente_id = p.id
                  LEFT JOIN usuarios d ON c.doctor_id = d.id
                  WHERE c.id = :cotizacion_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cotizacion_id', $cotizacion_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Actualizar cotización
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET fecha_vencimiento = :fecha_vencimiento, subtotal = :subtotal, 
                      impuesto = :impuesto, total = :total, estado = :estado, notas = :notas
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->fecha_vencimiento = htmlspecialchars(strip_tags($this->fecha_vencimiento));
        $this->subtotal = floatval($this->subtotal);
        $this->impuesto = floatval($this->impuesto);
        $this->total = floatval($this->total);
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->notas = htmlspecialchars(strip_tags($this->notas));

        // Bind parameters
        $stmt->bindParam(':fecha_vencimiento', $this->fecha_vencimiento);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $stmt->bindParam(':impuesto', $this->impuesto);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':notas', $this->notas);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar cotización
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Calcular totales de la cotización
    public function calcularTotales($cotizacion_id) {
        // Obtener productos de la cotización
        $query = "SELECT SUM(subtotal) as subtotal FROM cotizacion_productos WHERE cotizacion_id = :cotizacion_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cotizacion_id', $cotizacion_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $subtotal = $result['subtotal'] ?? 0;
        
        // Calcular impuesto (18% por defecto)
        $impuesto = $subtotal * 0.18;
        $total = $subtotal + $impuesto;
        
        // Actualizar cotización
        $update_query = "UPDATE " . $this->table_name . " 
                         SET subtotal = :subtotal, impuesto = :impuesto, total = :total 
                         WHERE id = :cotizacion_id";
        
        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bindParam(':subtotal', $subtotal);
        $update_stmt->bindParam(':impuesto', $impuesto);
        $update_stmt->bindParam(':total', $total);
        $update_stmt->bindParam(':cotizacion_id', $cotizacion_id);
        
        return $update_stmt->execute();
    }

    // Obtener todas las cotizaciones (para administradores)
    public function obtenerTodas() {
        $query = "SELECT c.id, c.paciente_id, c.doctor_id, c.fecha_creacion, c.fecha_vencimiento, 
                         c.subtotal, c.impuesto, c.total, c.estado, c.notas,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios p ON c.paciente_id = p.id
                  LEFT JOIN usuarios d ON c.doctor_id = d.id
                  ORDER BY c.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
}
?>
