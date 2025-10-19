# Sistema de Citas Médicas

Un sistema completo de gestión de citas médicas desarrollado en PHP, MySQL y Bootstrap.

## Características

### 🔐 Sistema de Autenticación
- **3 tipos de usuarios**: Administrador, Doctor, Paciente
- Registro de pacientes
- Login seguro con hash de contraseñas
- Redirección automática según tipo de usuario

### 👨‍⚕️ Panel del Administrador
- Dashboard con estadísticas generales
- Gestión completa de usuarios (crear, editar, eliminar)
- Gestión de citas médicas
- Visualización de reportes

### 🩺 Panel del Doctor
- Dashboard con citas del día y próximas citas
- Gestión de pacientes asignados
- Sistema de consultas médicas
- Actualización de estados de citas

### 👤 Panel del Paciente
- Dashboard personal con estadísticas
- Solicitud de nuevas citas
- Visualización de citas propias
- Historial médico

### 📅 Sistema de Citas
- Selección de doctor y especialidad
- Calendario de disponibilidad
- Horarios de 30 minutos (8:00 AM - 6:00 PM)
- Estados: Creada, Realizada, No se presentó, Cancelada

### 📧 Notificaciones por Email
- Confirmación de citas a pacientes
- Notificación a doctores de nuevas citas
- Envío de resultados de consultas
- Plantillas HTML profesionales

### 📁 Gestión de Archivos
- Subida de imágenes y documentos
- Adjuntos a consultas médicas
- Historial médico digital

## Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **JavaScript**: Vanilla JS
- **Email**: PHPMailer (opcional)
- **Servidor**: Apache/Nginx con XAMPP

## Instalación

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- XAMPP (recomendado para desarrollo)

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
   ```bash
   git clone [url-del-repositorio]
   cd doctor_app
   ```

2. **Configurar la base de datos**
   - Importar el archivo `database/schema.sql` en MySQL
   - Verificar que la base de datos `doctor_app` se creó correctamente

3. **Configurar conexión a la base de datos**
   - Editar `config/database.php`
   - Ajustar los parámetros de conexión según tu configuración

4. **Configurar correos (opcional)**
   - Editar `config/email.php`
   - Configurar credenciales SMTP para envío de emails

5. **Configurar permisos**
   - Asegurar que la carpeta `uploads/` tenga permisos de escritura
   - Verificar permisos de la carpeta del proyecto

6. **Acceder al sistema**
   - Abrir navegador en `http://localhost/doctor_app`
   - Usar las credenciales por defecto del administrador:
     - Email: `admin@doctorapp.com`
     - Contraseña: `admin123`

## Estructura del Proyecto

```
doctor_app/
├── admin/                  # Panel administrativo
│   ├── dashboard.php
│   ├── usuarios.php
│   └── citas.php
├── doctor/                 # Panel del doctor
│   ├── dashboard.php
│   ├── citas.php
│   └── consultas.php
├── patient/                # Panel del paciente
│   ├── dashboard.php
│   ├── nueva_cita.php
│   └── mis_citas.php
├── classes/                # Clases PHP
│   ├── User.php
│   └── Appointment.php
├── config/                 # Configuraciones
│   ├── database.php
│   └── email.php
├── database/               # Scripts de base de datos
│   └── schema.sql
├── uploads/                # Archivos subidos
├── index.php              # Página principal
└── logout.php             # Cierre de sesión
```

## Base de Datos

### Tablas Principales

- **usuarios**: Información de todos los usuarios del sistema
- **citas**: Registro de citas médicas
- **archivos_consulta**: Archivos adjuntos a consultas
- **historial_medico**: Historial médico de pacientes

### Usuario Administrador por Defecto
- **Email**: admin@doctorapp.com
- **Contraseña**: admin123

## Funcionalidades por Usuario

### Administrador
- ✅ Ver dashboard con estadísticas
- ✅ Gestionar usuarios (CRUD)
- ✅ Ver todas las citas
- ✅ Actualizar estados de citas
- ⏳ Generar reportes
- ⏳ Configurar sistema

### Doctor
- ✅ Ver dashboard personal
- ✅ Ver citas asignadas
- ✅ Gestionar consultas
- ✅ Actualizar estados de citas
- ⏳ Subir resultados
- ⏳ Enviar emails a pacientes

### Paciente
- ✅ Ver dashboard personal
- ✅ Solicitar nuevas citas
- ✅ Ver sus citas
- ✅ Ver historial médico
- ⏳ Cancelar citas
- ⏳ Recibir notificaciones

## Estados de Citas

- **Cita Creada**: Nueva cita solicitada
- **Cita Realizada**: Consulta completada
- **No se Presentó**: Paciente no asistió
- **Cita Cancelada**: Cita cancelada por paciente o doctor

## Configuración de Email

Para habilitar el envío de emails:

1. Instalar PHPMailer:
   ```bash
   composer require phpmailer/phpmailer
   ```

2. Configurar credenciales SMTP en `config/email.php`

3. Ajustar la función `enviarEmail()` según tu proveedor de email

## Desarrollo

### Agregar Nuevas Funcionalidades

1. **Nuevas páginas**: Crear en la carpeta correspondiente (`admin/`, `doctor/`, `patient/`)
2. **Nuevas clases**: Agregar en la carpeta `classes/`
3. **Nuevas tablas**: Actualizar `database/schema.sql`

### Personalización

- **Estilos**: Modificar CSS en las páginas o crear archivos CSS separados
- **Funcionalidades**: Extender las clases existentes o crear nuevas
- **Base de datos**: Agregar campos o tablas según necesidades

## Seguridad

- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Validación de entrada de datos
- ✅ Protección contra SQL injection con PDO
- ✅ Verificación de sesiones y permisos
- ⏳ Validación CSRF
- ⏳ Sanitización de archivos subidos

## Próximas Mejoras

- [ ] Sistema de notificaciones push
- [ ] Chat entre doctor y paciente
- [ ] Integración con calendarios
- [ ] Reportes avanzados
- [ ] API REST
- [ ] Aplicación móvil
- [ ] Sistema de pagos
- [ ] Integración con laboratorios

## Soporte

Para soporte técnico o consultas sobre el sistema, contactar al administrador del sistema.

## Licencia

Este proyecto es de uso educativo y comercial. Todos los derechos reservados.

---

**Desarrollado con ❤️ para mejorar la gestión de citas médicas**
