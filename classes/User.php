<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nombre;
    public $apellido;
    public $email;
    public $identificacion;
    public $telefono;
    public $password;
    public $tipo_usuario;
    public $especialidad;
    public $fecha_registro;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nuevo usuario
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, apellido, email, identificacion, telefono, password, tipo_usuario, especialidad) 
                  VALUES (:nombre, :apellido, :email, :identificacion, :telefono, :password, :tipo_usuario, :especialidad)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->identificacion = htmlspecialchars(strip_tags($this->identificacion));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->tipo_usuario = htmlspecialchars(strip_tags($this->tipo_usuario));
        $this->especialidad = htmlspecialchars(strip_tags($this->especialidad));

        // Bind parameters
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':apellido', $this->apellido);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':identificacion', $this->identificacion);
        $stmt->bindParam(':telefono', $this->telefono);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':tipo_usuario', $this->tipo_usuario);
        $stmt->bindParam(':especialidad', $this->especialidad);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Crear paciente sin autenticación (para doctores y administradores)
    public function crearPaciente() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, apellido, email, identificacion, telefono, password, tipo_usuario, especialidad) 
                  VALUES (:nombre, :apellido, :email, :identificacion, :telefono, :password, :tipo_usuario, :especialidad)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->identificacion = htmlspecialchars(strip_tags($this->identificacion));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        // Generar password temporal para pacientes creados por doctores/admin
        $this->password = password_hash('temp_password_' . time(), PASSWORD_DEFAULT);
        $this->tipo_usuario = 'paciente';
        $this->especialidad = null;

        // Bind parameters
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':apellido', $this->apellido);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':identificacion', $this->identificacion);
        $stmt->bindParam(':telefono', $this->telefono);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':tipo_usuario', $this->tipo_usuario);
        $stmt->bindParam(':especialidad', $this->especialidad);

        if($stmt->execute()) {
            return $this->conn->lastInsertId(); // Retornar el ID del paciente creado
        }
        return false;
    }

    // Autenticar usuario
    public function login($email, $password) {
        $query = "SELECT id, nombre, apellido, email, password, tipo_usuario, especialidad 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND activo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->nombre = $row['nombre'];
                $this->apellido = $row['apellido'];
                $this->email = $row['email'];
                $this->tipo_usuario = $row['tipo_usuario'];
                $this->especialidad = $row['especialidad'];
                return true;
            }
        }
        return false;
    }

    // Obtener todos los usuarios
    public function obtenerTodos() {
        $query = "SELECT id, nombre, apellido, email, identificacion, telefono, tipo_usuario, especialidad, fecha_registro 
                  FROM " . $this->table_name . " 
                  WHERE activo = 1 
                  ORDER BY fecha_registro DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Obtener doctores
    public function obtenerDoctores() {
        $query = "SELECT id, nombre, apellido, email, identificacion, especialidad 
                  FROM " . $this->table_name . " 
                  WHERE tipo_usuario = 'doctor' AND activo = 1 
                  ORDER BY nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Obtener un usuario por ID
    public function obtenerPorId($id) {
        $query = "SELECT id, nombre, apellido, email, identificacion, telefono, tipo_usuario, especialidad 
                  FROM " . $this->table_name . " 
                  WHERE id = :id AND activo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->email = $row['email'];
            $this->identificacion = $row['identificacion'];
            $this->telefono = $row['telefono'];
            $this->tipo_usuario = $row['tipo_usuario'];
            $this->especialidad = $row['especialidad'];
            return $this; // Devolver el objeto en lugar de true
        }
        return false;
    }

    // Actualizar usuario
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre = :nombre, apellido = :apellido, email = :email, 
                      telefono = :telefono, tipo_usuario = :tipo_usuario, especialidad = :especialidad 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':apellido', $this->apellido);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':telefono', $this->telefono);
        $stmt->bindParam(':tipo_usuario', $this->tipo_usuario);
        $stmt->bindParam(':especialidad', $this->especialidad);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar usuario (soft delete)
    public function eliminar() {
        $query = "UPDATE " . $this->table_name . " SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar si la identificación ya existe
    public function verificarIdentificacion($identificacion) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE identificacion = :identificacion AND activo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':identificacion', $identificacion);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Obtener usuario por identificación
    public function obtenerPorIdentificacion($identificacion) {
        $query = "SELECT id, nombre, apellido, email, identificacion, telefono, tipo_usuario, especialidad 
                  FROM " . $this->table_name . " 
                  WHERE identificacion = :identificacion AND activo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':identificacion', $identificacion);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->email = $row['email'];
            $this->identificacion = $row['identificacion'];
            $this->telefono = $row['telefono'];
            $this->tipo_usuario = $row['tipo_usuario'];
            $this->especialidad = $row['especialidad'];
            return $this;
        }
        return false;
    }
}
?>
