-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-09-2026 a las 07:06:35
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `deltahub`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos`
--

CREATE TABLE `archivos` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `version` varchar(20) DEFAULT NULL,
  `url_archivo` varchar(255) NOT NULL,
  `tamano_mb` decimal(6,2) DEFAULT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `archivos`
--

INSERT INTO `archivos` (`id`, `item_id`, `version`, `url_archivo`, `tamano_mb`, `fecha_subida`) VALUES
(1, 1, '0.1', 'uploads/items/1_test.zip', 0.00, '2026-09-05 02:48:19'),
(2, 2, '67.67', 'uploads/items/2_test_2.zip', 0.00, '2026-09-05 03:12:11'),
(3, 16, 'lorem ipsum', 'uploads/items/16_test_2.zip', 0.00, '2026-09-29 02:00:48'),
(4, 17, 'eduardo lorem', 'uploads/items/17_test_2.zip', 0.00, '2026-09-29 02:01:24'),
(5, 18, 'la mismisima pagina', 'uploads/items/18_test_2.zip', 0.00, '2026-09-29 02:05:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(7, 'Mods'),
(8, 'Sprites'),
(9, 'Saves'),
(10, 'Música'),
(11, 'Herramientas'),
(12, 'Traducciones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `puntuacion` tinyint(4) DEFAULT NULL CHECK (`puntuacion` between 1 and 5),
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentarios`
--

INSERT INTO `comentarios` (`id`, `item_id`, `usuario_id`, `contenido`, `puntuacion`, `fecha`) VALUES
(1, 1, 9, 'Golem que pasó', 5, '2026-09-05 03:09:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `capitulo` tinyint(3) unsigned DEFAULT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen_portada` varchar(255) DEFAULT NULL,
  `fecha_publicacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL,
  `descargas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `items`
--

INSERT INTO `items` (`id`, `usuario_id`, `categoria_id`, `capitulo`, `titulo`, `descripcion`, `imagen_portada`, `fecha_publicacion`, `fecha_actualizacion`, `descargas`) VALUES
(1, 9, 7, NULL, 'skibidi', 'hola soy el primer upload jeje', 'uploads/portadas/1_082081e43486700302fb1915f7f3bfaf.jpg', '2026-09-05 02:48:19', NULL, 2),
(2, 9, 7, NULL, 'El mod del Phonk', 'Mod que reemplaza el roaring knight por osam phonky god', 'uploads/portadas/2_file_0000000044c8720eaf2b64f99361fe57.png', '2026-09-05 03:12:11', NULL, 0),
(16, 9, 10, NULL, 'lorem ipsum', 'lorem ipsum', 'uploads/portadas/16_yo_con_mi_cuadro_de_valencia.png', '2026-09-29 02:00:48', NULL, 0),
(17, 9, 12, NULL, 'eduardo lorem', 'eduardo lorem', 'uploads/portadas/17_bana.jpg', '2026-09-29 02:01:24', NULL, 0),
(18, 9, 8, NULL, 'la mismisima pagina es basura', 'la mismisima pagina es basura', 'uploads/portadas/18_1780702325121-019e9a1f-ecbc-7e28-9ba7-c7ecaab2e020.png', '2026-09-29 02:05:11', NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `rol` enum('usuario','moderador','admin') DEFAULT 'usuario',
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_usuario`, `email`, `password_hash`, `avatar_url`, `banner_url`, `fecha_registro`, `rol`, `bio`) VALUES
(7, 'Nataliasigma', 'natytorres@gmail.com', '$2y$10$Zz788dc1Wnuip2.DkCIu9.WqLbkDE8oQK9vbpoZhwtf6QyBPOV5cW', NULL, NULL, '2026-09-02 01:57:10', 'usuario', NULL),
(8, 'arseniatrola', 'arseniaimbecil@gmail.com', '$2y$10$/0pBlUqJHGTEENmcw3HBeO8.UoD8UG.Vs4ckrF.w3TqWAwWDEejTi', 'uploads/avatars/avatar_8_1788574972.jpg', NULL, '2026-09-04 23:11:20', 'usuario', NULL),
(9, 'supersigmasanti', 'santinitot@gmail.com', '$2y$10$cUrsoZZVUepk892Noqayx.KQ.lWc.YOvZDnBvykkhiauqqmyvJOn.', 'uploads/avatars/avatar_9_1788673813.png', NULL, '2026-09-04 23:33:41', 'usuario', 'Hola pijes jejeje\r\nbs. as.\r\naguante boca!!');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos`
--
ALTER TABLE `archivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD CONSTRAINT `archivos_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `items_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
