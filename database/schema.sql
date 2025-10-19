-- Base de datos para Sistema de Citas Médicas
CREATE DATABASE IF NOT EXISTS doctor_app;
USE doctor_app;

-- Tabla de usuarios (administradores, doctores, pacientes)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('administrador', 'doctor', 'paciente') NOT NULL,
    especialidad VARCHAR(100), -- Solo para doctores
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de información médica de pacientes
CREATE TABLE informacion_medica_paciente (
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

-- Tabla de citas médicas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    doctor_id INT NOT NULL,
    fecha_cita DATE NOT NULL,
    hora_cita TIME NOT NULL,
    motivo TEXT,
    sintomas TEXT,
    status ENUM('cita_creada', 'cita_realizada', 'no_se_presento', 'cita_cancelada') DEFAULT 'cita_creada',
    observaciones_doctor TEXT,
    resultados TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id),
    FOREIGN KEY (doctor_id) REFERENCES usuarios(id)
);

-- Tabla para archivos adjuntos de consultas
CREATE TABLE archivos_consulta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_archivo VARCHAR(100),
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE
);

-- Tabla de historial médico
CREATE TABLE historial_medico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    doctor_id INT NOT NULL,
    cita_id INT,
    diagnostico TEXT,
    tratamiento TEXT,
    medicamentos TEXT,
    notas_adicionales TEXT,
    fecha_consulta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id),
    FOREIGN KEY (doctor_id) REFERENCES usuarios(id),
    FOREIGN KEY (cita_id) REFERENCES citas(id)
);

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, apellido, email, password, tipo_usuario) 
VALUES ('Admin', 'Sistema', 'admin@doctorapp.com', MD5('admin123'), 'administrador');

-- Crear índices para mejorar el rendimiento
CREATE INDEX idx_citas_fecha ON citas(fecha_cita);
CREATE INDEX idx_citas_paciente ON citas(paciente_id);
CREATE INDEX idx_citas_doctor ON citas(doctor_id);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);
CREATE INDEX idx_usuarios_email ON usuarios(email);
