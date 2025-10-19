<?php
require_once __DIR__ . '/../config/database.php';

class MedicalHistory {
    private $conn;
    private $table_name = "historial_medico";

    public $id;
    public $paciente_id;
    public $doctor_id;
    public $cita_id;
    public $diagnostico;
    public $tratamiento;
    public $medicamentos;
    public $notas_adicionales;
    public $fecha_consulta;

    // Información personal del paciente
    public $fecha_nacimiento;
    public $genero;
    public $direccion;
    public $emergencia_contacto;
    public $emergencia_telefono;
    
    // Información médica
    public $alergias;
    public $enfermedades_cronicas;
    public $medicamentos_actuales;
    public $cirugias_previas;
    public $historial_familiar;
    public $grupo_sanguineo;
    public $peso;
    public $altura;
    public $presion_arterial;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear historial médico
    public function crear() {
        // Determinar si hay una cita asociada
        $hasCita = !empty($this->cita_id) && $this->cita_id !== '';
        
        if ($hasCita) {
            // Si hay cita_id, incluirla en la consulta
            $query = "INSERT INTO " . $this->table_name . " 
                      (paciente_id, doctor_id, cita_id, diagnostico, tratamiento, medicamentos, notas_adicionales) 
                      VALUES (:paciente_id, :doctor_id, :cita_id, :diagnostico, :tratamiento, :medicamentos, :notas_adicionales)";
        } else {
            // Si no hay cita_id, usar NULL
            $query = "INSERT INTO " . $this->table_name . " 
                      (paciente_id, doctor_id, cita_id, diagnostico, tratamiento, medicamentos, notas_adicionales) 
                      VALUES (:paciente_id, :doctor_id, NULL, :diagnostico, :tratamiento, :medicamentos, :notas_adicionales)";
        }

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->paciente_id = htmlspecialchars(strip_tags($this->paciente_id));
        $this->doctor_id = htmlspecialchars(strip_tags($this->doctor_id));
        $this->diagnostico = htmlspecialchars(strip_tags($this->diagnostico));
        $this->tratamiento = htmlspecialchars(strip_tags($this->tratamiento));
        $this->medicamentos = htmlspecialchars(strip_tags($this->medicamentos));
        $this->notas_adicionales = htmlspecialchars(strip_tags($this->notas_adicionales));

        // Bind parameters
        $stmt->bindParam(':paciente_id', $this->paciente_id);
        $stmt->bindParam(':doctor_id', $this->doctor_id);
        if ($hasCita) {
            $this->cita_id = htmlspecialchars(strip_tags($this->cita_id));
            $stmt->bindParam(':cita_id', $this->cita_id);
        }
        $stmt->bindParam(':diagnostico', $this->diagnostico);
        $stmt->bindParam(':tratamiento', $this->tratamiento);
        $stmt->bindParam(':medicamentos', $this->medicamentos);
        $stmt->bindParam(':notas_adicionales', $this->notas_adicionales);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener historial médico por paciente
    public function obtenerPorPaciente($paciente_id) {
        $query = "SELECT h.id, h.paciente_id, h.doctor_id, h.cita_id, h.diagnostico, h.tratamiento, 
                         h.medicamentos, h.notas_adicionales, h.fecha_consulta,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " h
                  LEFT JOIN usuarios p ON h.paciente_id = p.id
                  LEFT JOIN usuarios d ON h.doctor_id = d.id
                  WHERE h.paciente_id = :paciente_id
                  ORDER BY h.fecha_consulta DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->execute();
        return $stmt;
    }

    // Obtener un registro específico del historial
    public function obtenerPorId($id) {
        $query = "SELECT h.id, h.paciente_id, h.doctor_id, h.cita_id, h.diagnostico, h.tratamiento, 
                         h.medicamentos, h.notas_adicionales, h.fecha_consulta,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " h
                  LEFT JOIN usuarios p ON h.paciente_id = p.id
                  LEFT JOIN usuarios d ON h.doctor_id = d.id
                  WHERE h.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->paciente_id = $row['paciente_id'];
            $this->doctor_id = $row['doctor_id'];
            $this->cita_id = $row['cita_id'];
            $this->diagnostico = $row['diagnostico'];
            $this->tratamiento = $row['tratamiento'];
            $this->medicamentos = $row['medicamentos'];
            $this->notas_adicionales = $row['notas_adicionales'];
            $this->fecha_consulta = $row['fecha_consulta'];
            return $row;
        }
        return false;
    }

    // Actualizar historial médico
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET diagnostico = :diagnostico, tratamiento = :tratamiento, 
                      medicamentos = :medicamentos, notas_adicionales = :notas_adicionales
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':diagnostico', $this->diagnostico);
        $stmt->bindParam(':tratamiento', $this->tratamiento);
        $stmt->bindParam(':medicamentos', $this->medicamentos);
        $stmt->bindParam(':notas_adicionales', $this->notas_adicionales);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar registro del historial
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener todos los registros del historial (para administrador)
    public function obtenerTodos() {
        $query = "SELECT h.id, h.paciente_id, h.doctor_id, h.cita_id, h.diagnostico, h.tratamiento, 
                         h.medicamentos, h.notas_adicionales, h.fecha_consulta,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " h
                  LEFT JOIN usuarios p ON h.paciente_id = p.id
                  LEFT JOIN usuarios d ON h.doctor_id = d.id
                  ORDER BY h.fecha_consulta DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Obtener historial por doctor
    public function obtenerPorDoctor($doctor_id) {
        $query = "SELECT h.id, h.paciente_id, h.doctor_id, h.cita_id, h.diagnostico, h.tratamiento, 
                         h.medicamentos, h.notas_adicionales, h.fecha_consulta,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido
                  FROM " . $this->table_name . " h
                  LEFT JOIN usuarios p ON h.paciente_id = p.id
                  WHERE h.doctor_id = :doctor_id
                  ORDER BY h.fecha_consulta DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        return $stmt;
    }
}
?>
