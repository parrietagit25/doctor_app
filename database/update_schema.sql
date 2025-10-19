-- Script para actualizar la base de datos existente con la nueva tabla de información médica
USE doctor_app;

-- Agregar la nueva tabla de información médica de pacientes
CREATE TABLE IF NOT EXISTS informacion_medica_paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    fecha_nacimiento DATE,
    genero ENUM('masculino', 'femenino', 'otro'),
    direccion TEXT,
    emergencia_contacto VARCHAR(150),
    emergencia_telefono VARCHAR(20),
    grupo_sanguineo VARCHAR(10),
    peso DECIMAL(5,2),
    altura DECIMAL(5,2),
    presion_arterial VARCHAR(20),
    alergias TEXT,
    enfermedades_cronicas TEXT,
    medicamentos_actuales TEXT,
    cirugias_previas TEXT,
    historial_familiar TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Verificar que la tabla se creó correctamente
SHOW TABLES LIKE 'informacion_medica_paciente';

-- Mostrar la estructura de la nueva tabla
DESCRIBE informacion_medica_paciente;
