<?php
require_once __DIR__ . '/../config/database.php';

class PatientMedicalInfo {
    private $conn;
    private $table_name = "informacion_medica_paciente";

    public $id;
    public $paciente_id;
    public $fecha_nacimiento;
    public $genero;
    public $direccion;
    public $emergencia_contacto;
    public $emergencia_telefono;
    public $grupo_sanguineo;
    public $peso;
    public $altura;
    public $presion_arterial;
    public $alergias;
    public $enfermedades_cronicas;
    public $medicamentos_actuales;
    public $cirugias_previas;
    public $historial_familiar;
    public $fecha_creacion;
    public $fecha_actualizacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear información médica del paciente
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (paciente_id, fecha_nacimiento, genero, direccion, emergencia_contacto, emergencia_telefono, 
                   grupo_sanguineo, peso, altura, presion_arterial, alergias, enfermedades_cronicas, 
                   medicamentos_actuales, cirugias_previas, historial_familiar) 
                  VALUES (:paciente_id, :fecha_nacimiento, :genero, :direccion, :emergencia_contacto, :emergencia_telefono, 
                          :grupo_sanguineo, :peso, :altura, :presion_arterial, :alergias, :enfermedades_cronicas, 
                          :medicamentos_actuales, :cirugias_previas, :historial_familiar)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->paciente_id = htmlspecialchars(strip_tags($this->paciente_id));
        $this->fecha_nacimiento = htmlspecialchars(strip_tags($this->fecha_nacimiento));
        $this->genero = htmlspecialchars(strip_tags($this->genero));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->emergencia_contacto = htmlspecialchars(strip_tags($this->emergencia_contacto));
        $this->emergencia_telefono = htmlspecialchars(strip_tags($this->emergencia_telefono));
        $this->grupo_sanguineo = htmlspecialchars(strip_tags($this->grupo_sanguineo));
        $this->peso = htmlspecialchars(strip_tags($this->peso));
        $this->altura = htmlspecialchars(strip_tags($this->altura));
        $this->presion_arterial = htmlspecialchars(strip_tags($this->presion_arterial));
        $this->alergias = htmlspecialchars(strip_tags($this->alergias));
        $this->enfermedades_cronicas = htmlspecialchars(strip_tags($this->enfermedades_cronicas));
        $this->medicamentos_actuales = htmlspecialchars(strip_tags($this->medicamentos_actuales));
        $this->cirugias_previas = htmlspecialchars(strip_tags($this->cirugias_previas));
        $this->historial_familiar = htmlspecialchars(strip_tags($this->historial_familiar));

        // Bind parameters
        $stmt->bindParam(':paciente_id', $this->paciente_id);
        $stmt->bindParam(':fecha_nacimiento', $this->fecha_nacimiento);
        $stmt->bindParam(':genero', $this->genero);
        $stmt->bindParam(':direccion', $this->direccion);
        $stmt->bindParam(':emergencia_contacto', $this->emergencia_contacto);
        $stmt->bindParam(':emergencia_telefono', $this->emergencia_telefono);
        $stmt->bindParam(':grupo_sanguineo', $this->grupo_sanguineo);
        $stmt->bindParam(':peso', $this->peso);
        $stmt->bindParam(':altura', $this->altura);
        $stmt->bindParam(':presion_arterial', $this->presion_arterial);
        $stmt->bindParam(':alergias', $this->alergias);
        $stmt->bindParam(':enfermedades_cronicas', $this->enfermedades_cronicas);
        $stmt->bindParam(':medicamentos_actuales', $this->medicamentos_actuales);
        $stmt->bindParam(':cirugias_previas', $this->cirugias_previas);
        $stmt->bindParam(':historial_familiar', $this->historial_familiar);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener información médica por paciente
    public function obtenerPorPaciente($paciente_id) {
        $query = "SELECT i.*, u.nombre, u.apellido, u.email, u.telefono 
                  FROM " . $this->table_name . " i
                  LEFT JOIN usuarios u ON i.paciente_id = u.id
                  WHERE i.paciente_id = :paciente_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->paciente_id = $row['paciente_id'];
            $this->fecha_nacimiento = $row['fecha_nacimiento'];
            $this->genero = $row['genero'];
            $this->direccion = $row['direccion'];
            $this->emergencia_contacto = $row['emergencia_contacto'];
            $this->emergencia_telefono = $row['emergencia_telefono'];
            $this->grupo_sanguineo = $row['grupo_sanguineo'];
            $this->peso = $row['peso'];
            $this->altura = $row['altura'];
            $this->presion_arterial = $row['presion_arterial'];
            $this->alergias = $row['alergias'];
            $this->enfermedades_cronicas = $row['enfermedades_cronicas'];
            $this->medicamentos_actuales = $row['medicamentos_actuales'];
            $this->cirugias_previas = $row['cirugias_previas'];
            $this->historial_familiar = $row['historial_familiar'];
            return $row;
        }
        return false;
    }

    // Actualizar información médica
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET fecha_nacimiento = :fecha_nacimiento, genero = :genero, direccion = :direccion, 
                      emergencia_contacto = :emergencia_contacto, emergencia_telefono = :emergencia_telefono,
                      grupo_sanguineo = :grupo_sanguineo, peso = :peso, altura = :altura, 
                      presion_arterial = :presion_arterial, alergias = :alergias, 
                      enfermedades_cronicas = :enfermedades_cronicas, medicamentos_actuales = :medicamentos_actuales,
                      cirugias_previas = :cirugias_previas, historial_familiar = :historial_familiar
                  WHERE paciente_id = :paciente_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':fecha_nacimiento', $this->fecha_nacimiento);
        $stmt->bindParam(':genero', $this->genero);
        $stmt->bindParam(':direccion', $this->direccion);
        $stmt->bindParam(':emergencia_contacto', $this->emergencia_contacto);
        $stmt->bindParam(':emergencia_telefono', $this->emergencia_telefono);
        $stmt->bindParam(':grupo_sanguineo', $this->grupo_sanguineo);
        $stmt->bindParam(':peso', $this->peso);
        $stmt->bindParam(':altura', $this->altura);
        $stmt->bindParam(':presion_arterial', $this->presion_arterial);
        $stmt->bindParam(':alergias', $this->alergias);
        $stmt->bindParam(':enfermedades_cronicas', $this->enfermedades_cronicas);
        $stmt->bindParam(':medicamentos_actuales', $this->medicamentos_actuales);
        $stmt->bindParam(':cirugias_previas', $this->cirugias_previas);
        $stmt->bindParam(':historial_familiar', $this->historial_familiar);
        $stmt->bindParam(':paciente_id', $this->paciente_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar si existe información médica para un paciente
    public function existeParaPaciente($paciente_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE paciente_id = :paciente_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Obtener todos los pacientes con información médica
    public function obtenerTodosConInfo() {
        $query = "SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.fecha_registro,
                         i.fecha_nacimiento, i.genero, i.grupo_sanguineo, i.alergias, i.enfermedades_cronicas
                  FROM usuarios u
                  LEFT JOIN " . $this->table_name . " i ON u.id = i.paciente_id
                  WHERE u.tipo_usuario = 'paciente' AND u.activo = 1
                  ORDER BY u.fecha_registro DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Calcular edad del paciente
    public function calcularEdad($fecha_nacimiento) {
        if(empty($fecha_nacimiento)) {
            return null;
        }
        
        $fecha_nac = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nac);
        return $edad->y;
    }

    // Calcular IMC (Índice de Masa Corporal)
    public function calcularIMC($peso, $altura) {
        if(empty($peso) || empty($altura) || $altura == 0) {
            return null;
        }
        
        $altura_m = $altura / 100; // Convertir cm a metros
        $imc = $peso / ($altura_m * $altura_m);
        return round($imc, 2);
    }

    // Obtener clasificación del IMC
    public function clasificarIMC($imc) {
        if($imc < 18.5) return "Bajo peso";
        if($imc < 25) return "Peso normal";
        if($imc < 30) return "Sobrepeso";
        if($imc < 35) return "Obesidad grado I";
        if($imc < 40) return "Obesidad grado II";
        return "Obesidad grado III";
    }
}
?>
