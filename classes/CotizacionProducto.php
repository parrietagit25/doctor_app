<?php
class CotizacionProducto {
    private $conn;
    private $table_name = "cotizacion_productos";

    // Propiedades del producto
    public $id;
    public $cotizacion_id;
    public $producto_nombre;
    public $descripcion;
    public $cantidad;
    public $precio_unitario;
    public $subtotal;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Agregar producto a cotización
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (cotizacion_id, producto_nombre, descripcion, cantidad, precio_unitario, subtotal) 
                  VALUES (:cotizacion_id, :producto_nombre, :descripcion, :cantidad, :precio_unitario, :subtotal)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->cotizacion_id = htmlspecialchars(strip_tags($this->cotizacion_id));
        $this->producto_nombre = htmlspecialchars(strip_tags($this->producto_nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->cantidad = intval($this->cantidad);
        $this->precio_unitario = floatval($this->precio_unitario);
        $this->subtotal = floatval($this->subtotal);

        // Bind parameters
        $stmt->bindParam(':cotizacion_id', $this->cotizacion_id);
        $stmt->bindParam(':producto_nombre', $this->producto_nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':cantidad', $this->cantidad);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);
        $stmt->bindParam(':subtotal', $this->subtotal);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtener productos de una cotización
    public function obtenerPorCotizacion($cotizacion_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE cotizacion_id = :cotizacion_id 
                  ORDER BY id ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cotizacion_id', $cotizacion_id);
        $stmt->execute();

        return $stmt;
    }

    // Obtener producto por ID
    public function obtenerPorId($producto_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :producto_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Actualizar producto
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET producto_nombre = :producto_nombre, descripcion = :descripcion, 
                      cantidad = :cantidad, precio_unitario = :precio_unitario, subtotal = :subtotal
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->producto_nombre = htmlspecialchars(strip_tags($this->producto_nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->cantidad = intval($this->cantidad);
        $this->precio_unitario = floatval($this->precio_unitario);
        $this->subtotal = floatval($this->subtotal);

        // Bind parameters
        $stmt->bindParam(':producto_nombre', $this->producto_nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':cantidad', $this->cantidad);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar producto
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar todos los productos de una cotización
    public function eliminarPorCotizacion($cotizacion_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE cotizacion_id = :cotizacion_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cotizacion_id', $cotizacion_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Calcular subtotal de un producto
    public function calcularSubtotal($cantidad, $precio_unitario) {
        return floatval($cantidad) * floatval($precio_unitario);
    }
}
?>
