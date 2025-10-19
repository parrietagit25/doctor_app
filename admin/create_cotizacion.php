<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Cotizacion.php';
require_once __DIR__ . '/../classes/CotizacionProducto.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['administrador', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Verificar datos requeridos
if(!isset($_POST['paciente_id']) || !isset($_POST['productos'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Crear cotización
    $cotizacion = new Cotizacion($db);
    $cotizacion->paciente_id = $_POST['paciente_id'];
    $cotizacion->doctor_id = $_SESSION['user_id'];
    $cotizacion->fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
    $cotizacion->estado = $_POST['estado'] ?? 'pendiente';
    $cotizacion->notas = $_POST['notas'] ?? '';

    // Procesar productos
    $productos = json_decode($_POST['productos'], true);
    if(!$productos || empty($productos)) {
        throw new Exception('Debe agregar al menos un producto');
    }

    // Calcular totales
    $subtotal = 0;
    foreach($productos as $producto) {
        $subtotal += floatval($producto['subtotal']);
    }
    $impuesto = $subtotal * 0.18; // 18% de impuesto
    $total = $subtotal + $impuesto;

    $cotizacion->subtotal = $subtotal;
    $cotizacion->impuesto = $impuesto;
    $cotizacion->total = $total;

    // Crear cotización
    $cotizacion_id = $cotizacion->crear();
    if(!$cotizacion_id) {
        throw new Exception('Error al crear la cotización');
    }

    // Crear productos
    $cotizacionProducto = new CotizacionProducto($db);
    foreach($productos as $producto) {
        $cotizacionProducto->cotizacion_id = $cotizacion_id;
        $cotizacionProducto->producto_nombre = $producto['nombre'];
        $cotizacionProducto->descripcion = $producto['descripcion'] ?? '';
        $cotizacionProducto->cantidad = intval($producto['cantidad']);
        $cotizacionProducto->precio_unitario = floatval($producto['precio']);
        $cotizacionProducto->subtotal = floatval($producto['subtotal']);

        if(!$cotizacionProducto->crear()) {
            throw new Exception('Error al crear los productos de la cotización');
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Cotización creada exitosamente', 'cotizacion_id' => $cotizacion_id]);

} catch(Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
