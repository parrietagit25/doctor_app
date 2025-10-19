-- Tabla para almacenar imágenes del historial médico
CREATE TABLE IF NOT EXISTS historial_medico_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historial_medico_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tamaño_archivo INT NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    nota TEXT,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subido_por INT NOT NULL,
    FOREIGN KEY (historial_medico_id) REFERENCES historial_medico(id) ON DELETE CASCADE,
    FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices para optimizar consultas
CREATE INDEX idx_historial_medico_imagenes_historial ON historial_medico_imagenes(historial_medico_id);
CREATE INDEX idx_historial_medico_imagenes_fecha ON historial_medico_imagenes(fecha_subida);
