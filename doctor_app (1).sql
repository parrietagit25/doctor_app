-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-10-2025 a las 05:08:09
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
(1, 2, 3, '0000-00-00', '00:00:00', NULL, NULL, 'cita_creada', NULL, NULL, '2025-10-16 22:34:48', '2025-10-16 23:14:26');

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
  `email` varchar(150) NOT NULL,
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

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `telefono`, `password`, `tipo_usuario`, `especialidad`, `fecha_registro`, `activo`) VALUES
(1, 'Admin', 'Sistema', 'admin@doctorapp.com', NULL, '$2y$10$NtEbfuJZktlHbn7qZukSIuJnxK5AZOdZghon8eGwWYcjwXwEdA8Pe', 'administrador', NULL, '2025-10-16 22:14:13', 1),
(2, 'Paciente', 'Paciente', 'paciente@paciente.com', '60026773', '$2y$10$kzUcaME85X75lSwnjzrQF./lpxfCRDcKwdT7XY6rUUAqzRc/H0PaG', 'paciente', '', '2025-10-16 22:17:29', 1),
(3, 'doctor', 'doctor', 'doctor@doctor.com', '20202020', '$2y$10$.0yCuYXO4LlnhOYwnJYYs.3ANDCDuEh7BBPsrQb26/fF/b6t3WPWG', 'doctor', 'Pediatra', '2025-10-16 22:26:18', 1);

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
-- Indices de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `cita_id` (`cita_id`);

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
  ADD KEY `idx_usuarios_tipo` (`tipo_usuario`),
  ADD KEY `idx_usuarios_email` (`email`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `informacion_medica_paciente`
--
ALTER TABLE `informacion_medica_paciente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Filtros para la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD CONSTRAINT `historial_medico_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `historial_medico_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `historial_medico_ibfk_3` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`);

--
-- Filtros para la tabla `informacion_medica_paciente`
--
ALTER TABLE `informacion_medica_paciente`
  ADD CONSTRAINT `informacion_medica_paciente_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
