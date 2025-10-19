-- Agregar campo de identificación a la tabla usuarios
ALTER TABLE usuarios ADD COLUMN identificacion VARCHAR(20) UNIQUE AFTER email;

-- Crear índice para mejorar el rendimiento en búsquedas por identificación
CREATE INDEX idx_usuarios_identificacion ON usuarios(identificacion);

-- Actualizar el campo email para que no sea obligatorio (quitar NOT NULL)
ALTER TABLE usuarios MODIFY COLUMN email VARCHAR(150) UNIQUE;
