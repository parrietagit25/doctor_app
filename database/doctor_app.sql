-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-10-2025 a las 23:24:45
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `doctor_app`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_consulta`
--

CREATE TABLE `archivos_consulta` (
  `id` int(11) NOT NULL,
  `cita_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tipo_archivo` varchar(100) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_cita` time NOT NULL,
  `motivo` text DEFAULT NULL,
  `sintomas` text DEFAULT NULL,
  `status` enum('cita_creada','cita_realizada','no_se_presento','cita_cancelada') DEFAULT 'cita_creada',
  `observaciones_doctor` text DEFAULT NULL,
  `resultados` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `paciente_id`, `doctor_id`, `fecha_cita`, `hora_cita`, `motivo`, `sintomas`, `status`, `observaciones_doctor`, `resultados`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 2, 3, '2025-11-05', '00:00:00', NULL, NULL, 'cita_creada', NULL, NULL, '2025-10-16 22:34:48', '2025-10-19 18:55:20'),
(2, 5, 8, '2025-10-22', '11:00:00', 'colocar la prótesis ', 'dolor ', 'cita_creada', NULL, NULL, '2025-10-19 18:22:24', '2025-10-19 18:22:24'),
(3, 4, 6, '2025-10-30', '14:00:00', 'limpieza general', 'molestias al comer ', 'cita_creada', NULL, NULL, '2025-10-19 18:23:30', '2025-10-19 18:23:30'),
(4, 2, 6, '2025-10-28', '15:00:00', 'ajustar aparatos dentales ', 'molestias al masticar', 'cita_creada', NULL, NULL, '2025-10-19 18:25:39', '2025-10-19 18:25:39'),
(5, 9, 6, '2025-10-29', '14:00:00', 'placas dentales', 'dolor', 'cita_creada', NULL, NULL, '2025-10-19 20:01:00', '2025-10-19 20:01:00'),
(6, 10, 3, '2025-10-20', '14:30:00', 'dolor general', 'dolor', 'cita_creada', NULL, NULL, '2025-10-19 20:27:07', '2025-10-19 20:27:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_vencimiento` date DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `impuesto` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `estado` enum('pendiente','aprobada','rechazada','expirada') DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`id`, `paciente_id`, `doctor_id`, `fecha_creacion`, `fecha_vencimiento`, `subtotal`, `impuesto`, `total`, `estado`, `notas`, `created_at`, `updated_at`) VALUES
(1, 4, 1, '2025-10-18 23:20:46', '2025-10-30', 90.00, 16.20, 106.20, 'pendiente', 'se le manda a realizar una corona ', '2025-10-19 04:20:46', '2025-10-19 04:20:46'),
(2, 9, 3, '2025-10-19 15:11:16', '2025-10-25', 150.00, 27.00, 177.00, 'pendiente', 'se le hace una cotizacion al paciente ', '2025-10-19 20:11:16', '2025-10-19 20:11:16'),
(3, 10, 3, '2025-10-19 15:35:28', '2025-10-31', 350.00, 63.00, 413.00, 'pendiente', 'venta de productos medicos', '2025-10-19 20:35:28', '2025-10-19 20:35:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizacion_productos`
--

CREATE TABLE `cotizacion_productos` (
  `id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `producto_nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizacion_productos`
--

INSERT INTO `cotizacion_productos` (`id`, `cotizacion_id`, `producto_nombre`, `descripcion`, `cantidad`, `precio_unitario`, `subtotal`, `created_at`) VALUES
(1, 1, 'corona', 'se le instalara la corona', 1, 10.00, 10.00, '2025-10-19 04:20:46'),
(2, 1, 'limpieza', 'limpieza en general', 2, 40.00, 80.00, '2025-10-19 04:20:46'),
(3, 2, ' corona', 'las cornas se le colocaran el mismo dia en la misma consulta ', 3, 50.00, 150.00, '2025-10-19 20:11:16'),
(4, 3, 'coronas', 'se le aplicaran el mismo dia', 3, 50.00, 150.00, '2025-10-19 20:35:28'),
(5, 3, 'blaqueamiento', 'no hay descripcion', 1, 200.00, 200.00, '2025-10-19 20:35:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_medico`
--

CREATE TABLE `historial_medico` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `tratamiento` text DEFAULT NULL,
  `medicamentos` text DEFAULT NULL,
  `notas_adicionales` text DEFAULT NULL,
  `fecha_consulta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_medico`
--

INSERT INTO `historial_medico` (`id`, `paciente_id`, `doctor_id`, `cita_id`, `diagnostico`, `tratamiento`, `medicamentos`, `notas_adicionales`, `fecha_consulta`) VALUES
(3, 4, 1, NULL, 'prueba ', 'prueba ', 'prueba ', 'prueba ', '2025-10-19 03:49:05'),
(4, 4, 1, NULL, 'prueba 2', 'prueba 2', 'prueba 2', 'prueba 10', '2025-10-19 03:53:50'),
(5, 9, 3, NULL, 'el paciente hay que hacerle una placas dentales', 'con la maquina de placas se le ara el tratamiento ', 'anestesia ', 'paciente avazado de edad con malo habitos de limentacion ', '2025-10-19 20:06:11'),
(6, 9, 3, NULL, 'el paciente hay que hacerle una placas dentales', 'con la maquina de placas se le ara el tratamiento ', 'anestesia ', 'paciente avazado de edad con malo habitos de limentacion ', '2025-10-19 20:08:39'),
(7, 10, 3, NULL, 'dormir bien', 'pastillas x', 'nose ', 'Notas Adicionales', '2025-10-19 20:31:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_medico_imagenes`
--

CREATE TABLE `historial_medico_imagenes` (
  `id` int(11) NOT NULL,
  `historial_medico_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tamaño_archivo` int(11) NOT NULL,
  `tipo_mime` varchar(100) NOT NULL,
  `nota` text DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `subido_por` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_medico_imagenes`
--

INSERT INTO `historial_medico_imagenes` (`id`, `historial_medico_id`, `nombre_archivo`, `ruta_archivo`, `tamaño_archivo`, `tipo_mime`, `nota`, `fecha_subida`, `subido_por`) VALUES
(2, 3, 'test.jpg', 'uploads/medical_images/test.jpg', 1024, 'image/jpeg', 'Test de inserción', '2025-10-19 06:16:44', 1),
(3, 3, 'Screenshot 2025-07-20 183510.png', 'uploads/medical_images/historial_3_1760854683_7832.png', 23543, 'image/png', 'jbjbjbbjkb', '2025-10-19 06:18:03', 1),
(4, 3, 'Screenshot 2025-07-20 192059.png', 'uploads/medical_images/historial_3_1760854683_8278.png', 316268, 'image/png', 'jbjbjbbjkb', '2025-10-19 06:18:03', 1),
(5, 3, 'Screenshot 2025-07-20 192328.png', 'uploads/medical_images/historial_3_1760854683_8678.png', 15052, 'image/png', 'jbjbjbbjkb', '2025-10-19 06:18:03', 1),
(6, 5, 'bitewing.jpg', 'uploads/medical_images/historial_5_1760904518_2481.jpg', 1098389, 'image/jpeg', 'se muestra el problema', '2025-10-19 20:08:38', 3),
(7, 5, 'Ortopantomografia.002.jpg', 'uploads/medical_images/historial_5_1760904518_7136.jpg', 65010, 'image/jpeg', 'se muestra el problema', '2025-10-19 20:08:38', 3),
(8, 5, 'Radiografia-dental.png', 'uploads/medical_images/historial_5_1760904518_7953.png', 361425, 'image/png', 'se muestra el problema', '2025-10-19 20:08:38', 3),
(9, 7, 'bitewing.jpg', 'uploads/medical_images/historial_7_1760905973_3280.jpg', 1098389, 'image/jpeg', 'placas', '2025-10-19 20:32:53', 3),
(10, 7, 'Ortopantomografia.002.jpg', 'uploads/medical_images/historial_7_1760905973_8533.jpg', 65010, 'image/jpeg', 'placas', '2025-10-19 20:32:53', 3),
(11, 7, 'Radiografia-dental.png', 'uploads/medical_images/historial_7_1760905973_6810.png', 361425, 'image/png', 'placas', '2025-10-19 20:32:53', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informacion_medica_paciente`
--

CREATE TABLE `informacion_medica_paciente` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('masculino','femenino','otro') DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `emergencia_contacto` varchar(150) DEFAULT NULL,
  `emergencia_telefono` varchar(20) DEFAULT NULL,
  `grupo_sanguineo` varchar(10) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `altura` decimal(5,2) DEFAULT NULL,
  `presion_arterial` varchar(20) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `enfermedades_cronicas` text DEFAULT NULL,
  `medicamentos_actuales` text DEFAULT NULL,
  `cirugias_previas` text DEFAULT NULL,
  `historial_familiar` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `identificacion` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `tipo_usuario` enum('administrador','doctor','paciente') NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `identificacion`, `telefono`, `password`, `tipo_usuario`, `especialidad`, `fecha_registro`, `activo`) VALUES
(1, 'Admin', 'Sistema', 'admin@doctorapp.com', '1234', NULL, '$2y$10$NtEbfuJZktlHbn7qZukSIuJnxK5AZOdZghon8eGwWYcjwXwEdA8Pe', 'administrador', NULL, '2025-10-16 22:14:13', 1),
(2, 'Paciente', 'Paciente', 'paciente@paciente.com', '5678', '60026773', '$2y$10$kzUcaME85X75lSwnjzrQF./lpxfCRDcKwdT7XY6rUUAqzRc/H0PaG', 'paciente', '', '2025-10-16 22:17:29', 1),
(3, 'doctor', 'doctor', 'doctor@doctor.com', '4444', '20202020', '$2y$10$.0yCuYXO4LlnhOYwnJYYs.3ANDCDuEh7BBPsrQb26/fF/b6t3WPWG', 'doctor', 'Pediatra', '2025-10-16 22:26:18', 1),
(4, 'Pedro', 'Arrieta', 'pedroarrieta25@hotmail.com', '1111', '60026773', '$2y$10$cJLJwXfFZowPj0sX36Lgtu73bx6ClDBQsqyvbNf6RwgiM47FWapbO', 'paciente', NULL, '2025-10-19 03:42:57', 1),
(5, 'cecilia', 'arandias', 'cecilia.arandias@gmail.com', '147852', '61045697', '$2y$10$d4QDPQq5BELlyXI5jmnqj.4PGJ3xEwvzsm6jgWfPRzWcJNLzCjxy6', 'paciente', NULL, '2025-10-19 17:38:00', 1),
(6, 'odontologo', 'odontologo', 'odontologo@odontologo.com', '', '321321', '$2y$10$23S7JLAofrO6zpjTMEGHLeliOTn2CoBnEv1oYDjDrGctbHPVn2yuK', 'doctor', 'Odontologia', '2025-10-19 18:18:12', 1),
(8, 'instrumentista', 'instrumentista', 'instrumentista@instrumentista.com', '', '1919191', '$2y$10$w8b9iTwfyPkJm2Vug3gixuNJxWX5yIALsAdwErTT/xvhGzrcvwYpu', 'doctor', 'Instrumentista', '2025-10-19 18:21:16', 1),
(9, 'jose', 'caseco', 'jose@jose.com', '213333', '32123132', '$2y$10$2vLYP3k64.r7knVehsu/Muf9msPK6/u/lHmNXPgzUJM1ZwRnM8px6', 'paciente', NULL, '2025-10-19 20:00:18', 1),
(10, 'marcos', 'perez', 'marcos@marcos.com', '1397', '14073964400', '$2y$10$dsGMQAZV/93bkoV112kj7urkqpkbKqZsOPY0KRlEHyAqTOqaTcqtm', 'paciente', NULL, '2025-10-19 20:25:54', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos_consulta`
--
ALTER TABLE `archivos_consulta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cita_id` (`cita_id`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_citas_fecha` (`fecha_cita`),
  ADD KEY `idx_citas_paciente` (`paciente_id`),
  ADD KEY `idx_citas_doctor` (`doctor_id`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indices de la tabla `cotizacion_productos`
--
ALTER TABLE `cotizacion_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`);

--
-- Indices de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `cita_id` (`cita_id`);

--
-- Indices de la tabla `historial_medico_imagenes`
--
ALTER TABLE `historial_medico_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subido_por` (`subido_por`),
  ADD KEY `idx_historial_medico_imagenes_historial` (`historial_medico_id`),
  ADD KEY `idx_historial_medico_imagenes_fecha` (`fecha_subida`);

--
-- Indices de la tabla `informacion_medica_paciente`
--
ALTER TABLE `informacion_medica_paciente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD KEY `idx_usuarios_tipo` (`tipo_usuario`),
  ADD KEY `idx_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_identificacion` (`identificacion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos_consulta`
--
ALTER TABLE `archivos_consulta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cotizacion_productos`
--
ALTER TABLE `cotizacion_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `historial_medico_imagenes`
--
ALTER TABLE `historial_medico_imagenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `informacion_medica_paciente`
--
ALTER TABLE `informacion_medica_paciente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos_consulta`
--
ALTER TABLE `archivos_consulta`
  ADD CONSTRAINT `archivos_consulta_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizaciones_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cotizacion_productos`
--
ALTER TABLE `cotizacion_productos`
  ADD CONSTRAINT `cotizacion_productos_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD CONSTRAINT `historial_medico_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `historial_medico_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `historial_medico_ibfk_3` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`);

--
-- Filtros para la tabla `historial_medico_imagenes`
--
ALTER TABLE `historial_medico_imagenes`
  ADD CONSTRAINT `historial_medico_imagenes_ibfk_1` FOREIGN KEY (`historial_medico_id`) REFERENCES `historial_medico` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_medico_imagenes_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `informacion_medica_paciente`
--
ALTER TABLE `informacion_medica_paciente`
  ADD CONSTRAINT `informacion_medica_paciente_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
