<?php
require_once __DIR__ . '/../config/database.php';

class Appointment {
    private $conn;
    private $table_name = "citas";

    public $id;
    public $paciente_id;
    public $doctor_id;
    public $fecha_cita;
    public $hora_cita;
    public $motivo;
    public $sintomas;
    public $status;
    public $observaciones_doctor;
    public $resultados;
    public $fecha_creacion;
    public $fecha_actualizacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nueva cita
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (paciente_id, doctor_id, fecha_cita, hora_cita, motivo, sintomas, status) 
                  VALUES (:paciente_id, :doctor_id, :fecha_cita, :hora_cita, :motivo, :sintomas, :status)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->paciente_id = htmlspecialchars(strip_tags($this->paciente_id));
        $this->doctor_id = htmlspecialchars(strip_tags($this->doctor_id));
        $this->fecha_cita = htmlspecialchars(strip_tags($this->fecha_cita));
        $this->hora_cita = htmlspecialchars(strip_tags($this->hora_cita));
        $this->motivo = htmlspecialchars(strip_tags($this->motivo));
        $this->sintomas = htmlspecialchars(strip_tags($this->sintomas));
        $this->status = 'cita_creada';

        // Bind parameters
        $stmt->bindParam(':paciente_id', $this->paciente_id);
        $stmt->bindParam(':doctor_id', $this->doctor_id);
        $stmt->bindParam(':fecha_cita', $this->fecha_cita);
        $stmt->bindParam(':hora_cita', $this->hora_cita);
        $stmt->bindParam(':motivo', $this->motivo);
        $stmt->bindParam(':sintomas', $this->sintomas);
        $stmt->bindParam(':status', $this->status);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener todas las citas
    public function obtenerTodas() {
        $query = "SELECT c.id, c.fecha_cita, c.hora_cita, c.motivo, c.sintomas, c.status, 
                         c.observaciones_doctor, c.resultados, c.fecha_creacion,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.email as paciente_email,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios p ON c.paciente_id = p.id
                  LEFT JOIN usuarios d ON c.doctor_id = d.id
                  ORDER BY c.fecha_cita DESC, c.hora_cita DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Obtener citas por paciente
    public function obtenerPorPaciente($paciente_id) {
        $query = "SELECT c.id, c.fecha_cita, c.hora_cita, c.motivo, c.sintomas, c.status, 
                         c.observaciones_doctor, c.resultados, c.fecha_creacion,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios d ON c.doctor_id = d.id
                  WHERE c.paciente_id = :paciente_id
                  ORDER BY c.fecha_cita DESC, c.hora_cita DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->execute();
        return $stmt;
    }

    // Obtener citas por doctor
    public function obtenerPorDoctor($doctor_id) {
        $query = "SELECT c.id, c.fecha_cita, c.hora_cita, c.motivo, c.sintomas, c.status, 
                         c.observaciones_doctor, c.resultados, c.fecha_creacion,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.email as paciente_email
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios p ON c.paciente_id = p.id
                  WHERE c.doctor_id = :doctor_id
                  ORDER BY c.fecha_cita DESC, c.hora_cita DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        return $stmt;
    }

    // Obtener una cita por ID
    public function obtenerPorId($id) {
        $query = "SELECT c.id, c.paciente_id, c.doctor_id, c.fecha_cita, c.hora_cita, 
                         c.motivo, c.sintomas, c.status, c.observaciones_doctor, c.resultados,
                         p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.email as paciente_email,
                         d.nombre as doctor_nombre, d.apellido as doctor_apellido, d.especialidad
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios p ON c.paciente_id = p.id
                  LEFT JOIN usuarios d ON c.doctor_id = d.id
                  WHERE c.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->paciente_id = $row['paciente_id'];
            $this->doctor_id = $row['doctor_id'];
            $this->fecha_cita = $row['fecha_cita'];
            $this->hora_cita = $row['hora_cita'];
            $this->motivo = $row['motivo'];
            $this->sintomas = $row['sintomas'];
            $this->status = $row['status'];
            $this->observaciones_doctor = $row['observaciones_doctor'];
            $this->resultados = $row['resultados'];
            return $row;
        }
        return false;
    }

    // Actualizar cita
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET fecha_cita = :fecha_cita, hora_cita = :hora_cita, motivo = :motivo, 
                      sintomas = :sintomas, status = :status, observaciones_doctor = :observaciones_doctor,
                      resultados = :resultados
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':fecha_cita', $this->fecha_cita);
        $stmt->bindParam(':hora_cita', $this->hora_cita);
        $stmt->bindParam(':motivo', $this->motivo);
        $stmt->bindParam(':sintomas', $this->sintomas);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':observaciones_doctor', $this->observaciones_doctor);
        $stmt->bindParam(':resultados', $this->resultados);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar disponibilidad de horario
    public function verificarDisponibilidad($doctor_id, $fecha, $hora) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE doctor_id = :doctor_id AND fecha_cita = :fecha AND hora_cita = :hora 
                  AND status != 'cita_cancelada'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':hora', $hora);
        $stmt->execute();

        return $stmt->rowCount() == 0;
    }

    // Obtener horarios disponibles para un doctor en una fecha específica
    public function obtenerHorariosDisponibles($doctor_id, $fecha) {
        $horarios = [];
        $horariosOcupados = [];

        // Obtener horarios ocupados
        $query = "SELECT hora_cita FROM " . $this->table_name . " 
                  WHERE doctor_id = :doctor_id AND fecha_cita = :fecha 
                  AND status != 'cita_cancelada'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $horariosOcupados[] = $row['hora_cita'];
        }

        // Horarios disponibles (8:00 AM a 6:00 PM, cada 30 minutos)
        $horaInicio = strtotime('08:00');
        $horaFin = strtotime('18:00');
        
        for($hora = $horaInicio; $hora <= $horaFin; $hora += 1800) { // 1800 segundos = 30 minutos
            $horaFormato = date('H:i', $hora);
            if(!in_array($horaFormato, $horariosOcupados)) {
                $horarios[] = $horaFormato;
            }
        }

        return $horarios;
    }
}
?>
