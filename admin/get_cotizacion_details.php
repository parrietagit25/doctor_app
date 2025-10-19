<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Cotizacion.php';
require_once __DIR__ . '/../classes/CotizacionProducto.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['administrador', 'doctor'])) {
    echo '<div class="alert alert-danger">No autorizado.</div>';
    exit();
}

if(!isset($_POST['cotizacion_id']) || empty($_POST['cotizacion_id'])) {
    echo '<div class="alert alert-warning">ID de cotización no válido.</div>';
    exit();
}

$database = new Database();
$db = $database->getConnection();
$cotizacion = new Cotizacion($db);
$cotizacionProducto = new CotizacionProducto($db);

$cotizacion_id = intval($_POST['cotizacion_id']);

// Obtener detalles de la cotización
$cotizacion_data = $cotizacion->obtenerPorId($cotizacion_id);
if(!$cotizacion_data) {
    echo '<div class="alert alert-warning">Cotización no encontrada.</div>';
    exit();
}

// Obtener productos de la cotización
$stmt_productos = $cotizacionProducto->obtenerPorCotizacion($cotizacion_id);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        Detalles de la Cotización #<?php echo $cotizacion_data['id']; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Información General -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-info-circle me-2"></i>Información General
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Paciente:</strong></td>
                                    <td><?php echo htmlspecialchars($cotizacion_data['paciente_nombre'] . ' ' . $cotizacion_data['paciente_apellido']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Doctor:</strong></td>
                                    <td><?php echo htmlspecialchars($cotizacion_data['doctor_nombre'] . ' ' . $cotizacion_data['doctor_apellido']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha de Creación:</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($cotizacion_data['fecha_creacion'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha de Vencimiento:</strong></td>
                                    <td>
                                        <?php if($cotizacion_data['fecha_vencimiento']): ?>
                                            <?php echo date('d/m/Y', strtotime($cotizacion_data['fecha_vencimiento'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin fecha</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success mb-3">
                                <i class="fas fa-calculator me-2"></i>Totales
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td>$<?php echo number_format($cotizacion_data['subtotal'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Impuesto (18%):</strong></td>
                                    <td>$<?php echo number_format($cotizacion_data['impuesto'], 2); ?></td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>Total:</strong></td>
                                    <td><strong>$<?php echo number_format($cotizacion_data['total'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <?php
                                        $estado_class = '';
                                        switch($cotizacion_data['estado']) {
                                            case 'pendiente': $estado_class = 'bg-warning'; break;
                                            case 'aprobada': $estado_class = 'bg-success'; break;
                                            case 'rechazada': $estado_class = 'bg-danger'; break;
                                            case 'expirada': $estado_class = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($cotizacion_data['estado']); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Notas -->
                    <?php if(!empty($cotizacion_data['notas'])): ?>
                    <div class="mb-4">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-sticky-note me-2"></i>Notas
                        </h6>
                        <div class="alert alert-light border-start border-info border-4">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($cotizacion_data['notas'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Productos/Servicios -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-shopping-cart me-2"></i>Productos/Servicios
                        </h6>
                        <?php if($stmt_productos && $stmt_productos->rowCount() > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto/Servicio</th>
                                            <th>Descripción</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unitario</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($producto = $stmt_productos->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($producto['producto_nombre']); ?></strong></td>
                                            <td><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></td>
                                            <td><?php echo $producto['cantidad']; ?></td>
                                            <td>$<?php echo number_format($producto['precio_unitario'], 2); ?></td>
                                            <td><strong>$<?php echo number_format($producto['subtotal'], 2); ?></strong></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>No hay productos registrados en esta cotización.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
