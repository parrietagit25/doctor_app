-- Tabla para almacenar las cotizaciones
CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    doctor_id INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    impuesto DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) DEFAULT 0.00,
    estado ENUM('pendiente', 'aprobada', 'rechazada', 'expirada') DEFAULT 'pendiente',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla para almacenar los productos/servicios de cada cotización
CREATE TABLE IF NOT EXISTS cotizacion_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    producto_nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE
);

-- Índices para mejorar el rendimiento
CREATE INDEX idx_cotizaciones_paciente ON cotizaciones(paciente_id);
CREATE INDEX idx_cotizaciones_doctor ON cotizaciones(doctor_id);
CREATE INDEX idx_cotizaciones_estado ON cotizaciones(estado);
CREATE INDEX idx_cotizacion_productos_cotizacion ON cotizacion_productos(cotizacion_id);
