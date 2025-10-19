<?php
class MedicalImage {
    private $conn;
    private $table_name = "historial_medico_imagenes";

    public $id;
    public $historial_medico_id;
    public $nombre_archivo;
    public $ruta_archivo;
    public $tamaño_archivo;
    public $tipo_mime;
    public $nota;
    public $fecha_subida;
    public $subido_por;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nueva imagen
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (historial_medico_id, nombre_archivo, ruta_archivo, tamaño_archivo, tipo_mime, nota, subido_por) 
                  VALUES (:historial_medico_id, :nombre_archivo, :ruta_archivo, :tamano_archivo, :tipo_mime, :nota, :subido_por)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->historial_medico_id = htmlspecialchars(strip_tags($this->historial_medico_id));
        $this->nombre_archivo = htmlspecialchars(strip_tags($this->nombre_archivo));
        $this->ruta_archivo = htmlspecialchars(strip_tags($this->ruta_archivo));
        $this->tamaño_archivo = htmlspecialchars(strip_tags($this->tamaño_archivo));
        $this->tipo_mime = htmlspecialchars(strip_tags($this->tipo_mime));
        $this->nota = htmlspecialchars(strip_tags($this->nota));
        $this->subido_por = htmlspecialchars(strip_tags($this->subido_por));

        // Usar array de parámetros en lugar de bindParam
        $params = [
            ':historial_medico_id' => $this->historial_medico_id,
            ':nombre_archivo' => $this->nombre_archivo,
            ':ruta_archivo' => $this->ruta_archivo,
            ':tamano_archivo' => $this->tamaño_archivo,
            ':tipo_mime' => $this->tipo_mime,
            ':nota' => $this->nota,
            ':subido_por' => $this->subido_por
        ];

        if ($stmt->execute($params)) {
            return true;
        }
        return false;
    }

    // Obtener imágenes por historial médico
    public function obtenerPorHistorial($historial_id) {
        $query = "SELECT hmi.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
                  FROM " . $this->table_name . " hmi
                  LEFT JOIN usuarios u ON hmi.subido_por = u.id
                  WHERE hmi.historial_medico_id = :historial_id
                  ORDER BY hmi.fecha_subida DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":historial_id", $historial_id);
        $stmt->execute();

        return $stmt;
    }

    // Obtener imagen por ID
    public function obtenerPorId($id) {
        $query = "SELECT hmi.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
                  FROM " . $this->table_name . " hmi
                  LEFT JOIN usuarios u ON hmi.subido_por = u.id
                  WHERE hmi.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->historial_medico_id = $row['historial_medico_id'];
            $this->nombre_archivo = $row['nombre_archivo'];
            $this->ruta_archivo = $row['ruta_archivo'];
            $this->tamaño_archivo = $row['tamaño_archivo'];
            $this->tipo_mime = $row['tipo_mime'];
            $this->nota = $row['nota'];
            $this->fecha_subida = $row['fecha_subida'];
            $this->subido_por = $row['subido_por'];
            return $row;
        }
        return false;
    }

    // Eliminar imagen
    public function eliminar() {
        // Primero obtener la información de la imagen para eliminar el archivo
        if ($this->obtenerPorId($this->id)) {
            // Eliminar archivo físico
            if (file_exists($this->ruta_archivo)) {
                unlink($this->ruta_archivo);
            }

            // Eliminar registro de la base de datos
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);

            if ($stmt->execute()) {
                return true;
            }
        }
        return false;
    }

    // Validar archivo de imagen
    public static function validarImagen($archivo) {
        $errores = [];

        // Verificar si se subió un archivo
        if (!isset($archivo['tmp_name']) || empty($archivo['tmp_name'])) {
            $errores[] = "No se ha seleccionado ningún archivo";
            return $errores;
        }

        // Verificar si hay errores en la subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores[] = "Error en la subida del archivo: " . $archivo['error'];
            return $errores;
        }

        // Verificar tamaño del archivo (máximo 5MB)
        $tamaño_maximo = 5 * 1024 * 1024; // 5MB
        if ($archivo['size'] > $tamaño_maximo) {
            $errores[] = "El archivo es demasiado grande. Máximo 5MB";
        }

        // Verificar tipo de archivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $tipo_mime = mime_content_type($archivo['tmp_name']);
        if (!in_array($tipo_mime, $tipos_permitidos)) {
            $errores[] = "Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WEBP";
        }

        // Verificar si es realmente una imagen
        $imagen_info = getimagesize($archivo['tmp_name']);
        if ($imagen_info === false) {
            $errores[] = "El archivo no es una imagen válida";
        }

        return $errores;
    }

    // Generar nombre único para el archivo
    public static function generarNombreArchivo($nombre_original, $historial_id) {
        $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
        $nombre_base = pathinfo($nombre_original, PATHINFO_FILENAME);
        
        // Limpiar nombre base
        $nombre_base = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre_base);
        
        // Generar nombre único
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        
        return "historial_{$historial_id}_{$timestamp}_{$random}.{$extension}";
    }

    // Formatear tamaño de archivo
    public static function formatearTamaño($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>
