-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-09-2026 a las 22:51:58
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
-- Base de datos: `u806400645_warepro`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_ol`
--

CREATE TABLE `actividades_ol` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) DEFAULT 1 COMMENT '1 = Activo, 0 = Inactivo',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `es_productiva` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditorias`
--

CREATE TABLE `auditorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `criterios` text NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_checklists`
--

CREATE TABLE `auditoria_checklists` (
  `id` int(10) UNSIGNED NOT NULL,
  `auditoria_id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(255) DEFAULT 'Checklist de Auditoría',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_checklist_preguntas`
--

CREATE TABLE `auditoria_checklist_preguntas` (
  `id` int(10) UNSIGNED NOT NULL,
  `checklist_id` int(10) UNSIGNED NOT NULL,
  `pregunta` text NOT NULL,
  `tipo_respuesta` enum('si_no','comentario') NOT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_ejecuciones`
--

CREATE TABLE `auditoria_ejecuciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `checklist_id` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(255) DEFAULT NULL,
  `fecha_ejecucion` timestamp NULL DEFAULT current_timestamp(),
  `zona_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_ejecucion_respuestas`
--

CREATE TABLE `auditoria_ejecucion_respuestas` (
  `id` int(10) UNSIGNED NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `ejecucion_id` int(10) UNSIGNED NOT NULL,
  `pregunta_id` int(10) UNSIGNED NOT NULL,
  `respuesta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_media`
--

CREATE TABLE `auditoria_media` (
  `id` int(10) UNSIGNED NOT NULL,
  `auditoria_id` int(10) UNSIGNED NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `tipo` enum('imagen','pdf','link') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_zonas`
--

CREATE TABLE `auditoria_zonas` (
  `id` int(10) UNSIGNED NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargar_informativo`
--

CREATE TABLE `cargar_informativo` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `texto` text NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_wip`
--

CREATE TABLE `checklist_wip` (
  `id` int(10) UNSIGNED NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre_usuario` varchar(150) NOT NULL,
  `fecha_registro` date NOT NULL,
  `golpes_fisuras` enum('si','no') NOT NULL,
  `obs_golpes_fisuras` text DEFAULT NULL,
  `botones_funcionan` enum('si','no') NOT NULL,
  `obs_botones_funcionan` text DEFAULT NULL,
  `camara_limpia` enum('si','no') NOT NULL,
  `obs_camara_limpia` text DEFAULT NULL,
  `conectividad` enum('si','no') NOT NULL,
  `obs_conectividad` text DEFAULT NULL,
  `forro_completo` enum('si','no') NOT NULL,
  `obs_forro_completo` text DEFAULT NULL,
  `condiciones_seguras` enum('si','no') NOT NULL,
  `obs_condiciones_seguras` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `check_herramientas`
--

CREATE TABLE `check_herramientas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `usuario_id` int(11) NOT NULL,
  `herramienta` varchar(100) NOT NULL,
  `marca_temporal` datetime NOT NULL,
  `estado_fisico` enum('SI','NO') NOT NULL COMMENT 'La pistola de calor se encuentra en buen estado físico',
  `enchuf_conectores` enum('SI','NO') NOT NULL COMMENT 'El enchufe y los conectores están en buen estado y limpios',
  `epp_operador` enum('SI','NO') NOT NULL COMMENT 'El operador está utilizando los Elementos de Protección Personal necesarios',
  `almacenamiento` enum('SI','NO') NOT NULL COMMENT 'Se almacenó la pistola en un lugar seguro y seco después del uso',
  `capacitacion` enum('SI','NO') NOT NULL COMMENT 'El operador recibió capacitación sobre el uso correcto de la pistola de calor',
  `resultado` enum('APROBADO','RECHAZADO') NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_bloqueo`
--

CREATE TABLE `config_bloqueo` (
  `mes_anio` varchar(7) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `cumple` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_opm_global`
--

CREATE TABLE `config_opm_global` (
  `mes_anio` varchar(7) NOT NULL,
  `cumple_adherencia` tinyint(1) DEFAULT 1,
  `penalidad_cargue` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `control_trampas`
--

CREATE TABLE `control_trampas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `usuario_id` int(11) NOT NULL,
  `numero_trampa` int(11) NOT NULL,
  `area` varchar(100) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `descansos`
--

CREATE TABLE `descansos` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `usuario_id` int(11) NOT NULL,
  `hora_inicio` datetime NOT NULL,
  `hora_fin` datetime DEFAULT NULL,
  `duracion_minutos` int(11) DEFAULT NULL,
  `estado` enum('activo','finalizado') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `descargue`
--

CREATE TABLE `descargue` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `id_sortiing` int(11) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `usuario_descargue` varchar(100) NOT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime DEFAULT NULL,
  `tiene_novedad` enum('si','no') DEFAULT 'no',
  `novedades` text DEFAULT NULL,
  `estado` enum('en_proceso','finalizado') NOT NULL DEFAULT 'en_proceso'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones`
--

CREATE TABLE `devoluciones` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `canal` enum('KA','MM','T4','TAT') NOT NULL,
  `operador` enum('logisticos','surtilicores','t4') NOT NULL,
  `sku` varchar(50) NOT NULL,
  `dt` varchar(100) NOT NULL,
  `unidades` int(11) NOT NULL,
  `casual` enum('Rotas','faltantes','bajo nivel','humedo','desfondada','averiada') NOT NULL,
  `verificador` varchar(100) NOT NULL,
  `facturador` enum('julian pavon','david omeara','Alvaro Madrigal') NOT NULL,
  `status` enum('check in','cambio mano a mano') NOT NULL,
  `placa` varchar(20) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `error_armado`
--

CREATE TABLE `error_armado` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `turno` enum('Turno A','Turno B','Turno C') NOT NULL,
  `cc` varchar(100) DEFAULT NULL,
  `verificador_reporta` varchar(255) DEFAULT NULL,
  `colaborador_error_1` varchar(255) DEFAULT NULL,
  `cantidad_1` varchar(100) DEFAULT NULL,
  `descripcion_producto_1` text DEFAULT NULL,
  `tipo_error_1` enum('Sobrante','Faltante','Trocado','Otro') DEFAULT NULL,
  `placa_1` varchar(50) DEFAULT NULL,
  `colaborador_error_2` varchar(255) DEFAULT NULL,
  `cantidad_2` varchar(100) DEFAULT NULL,
  `descripcion_producto_2` text DEFAULT NULL,
  `tipo_error_2` enum('Sobrante','Faltante','Trocado','Otro') DEFAULT NULL,
  `placa_2` varchar(50) DEFAULT NULL,
  `colaborador_error_3` varchar(255) DEFAULT NULL,
  `cantidad_3` varchar(100) DEFAULT NULL,
  `descripcion_producto_3` text DEFAULT NULL,
  `tipo_error_3` enum('Sobrante','Faltante','Trocado','Otro') DEFAULT NULL,
  `placa_3` varchar(50) DEFAULT NULL,
  `colaborador_error_4` varchar(255) DEFAULT NULL,
  `cantidad_4` varchar(100) DEFAULT NULL,
  `descripcion_producto_4` text DEFAULT NULL,
  `tipo_error_4` enum('Sobrante','Faltante','Trocado','Otro') DEFAULT NULL,
  `placa_4` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `error_verificacion`
--

CREATE TABLE `error_verificacion` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `marca_temporal` datetime NOT NULL,
  `reportado_por` varchar(100) NOT NULL,
  `nombre_persona_reporta` varchar(100) NOT NULL,
  `tipo_novedad` varchar(100) NOT NULL,
  `dt_con_novedad` varchar(50) NOT NULL,
  `placa_completa` varchar(20) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `cantidad_unidad_presentacion` int(11) NOT NULL,
  `verificador_responsable` varchar(100) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `turno` enum('turno a','turno b','turno c') NOT NULL,
  `novedad_genero_rechazo` enum('N','Y') NOT NULL,
  `auxiliar_responsable` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fefo_areas`
--

CREATE TABLE `fefo_areas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(100) NOT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fefo_registros`
--

CREATE TABLE `fefo_registros` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `subarea_id` int(11) NOT NULL,
  `fecha_dia` date NOT NULL,
  `fecha_rotulo` date DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_evaluacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fefo_subareas`
--

CREATE TABLE `fefo_subareas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `area_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_rotulo` date DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_evaluacion` datetime DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `supervisor` varchar(100) NOT NULL,
  `vinipel_rollos` int(11) DEFAULT 0,
  `termoencogido_rollos` int(11) DEFAULT 0,
  `carton_laminas` int(11) DEFAULT 0,
  `isotanques` int(11) DEFAULT 0,
  `iso_llenos` int(11) DEFAULT 0,
  `iso_bueno` int(11) DEFAULT 0,
  `iso_malo` int(11) DEFAULT 0,
  `estibas_tipo_a` varchar(100) DEFAULT NULL,
  `estibas_tipo_b` varchar(100) DEFAULT NULL,
  `estibas_tipo_c` varchar(100) DEFAULT NULL,
  `estibas_ara` varchar(100) DEFAULT NULL,
  `estibas_d1` varchar(100) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_if`
--

CREATE TABLE `inventario_if` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha_analisis` date DEFAULT NULL COMMENT 'Fecha Análisis',
  `familia` varchar(100) DEFAULT NULL COMMENT 'Familia',
  `contenido` int(11) DEFAULT NULL COMMENT 'Contenido',
  `und_caja` int(11) DEFAULT NULL COMMENT 'Und/Caja',
  `cod_sku` varchar(50) NOT NULL COMMENT 'Cod.',
  `descripcion_pt` varchar(255) DEFAULT NULL COMMENT 'Descripción PT',
  `cantidad_unidades` decimal(10,2) DEFAULT NULL COMMENT 'CANTIDAD EN UNIDADES',
  `hl` decimal(10,2) DEFAULT NULL COMMENT 'HL',
  `cantidad_estibas` decimal(10,2) DEFAULT NULL COMMENT 'CANTIDAD EN ESTIBAS',
  `fecha_vencimiento` date DEFAULT NULL COMMENT 'Fecha Venc.',
  `mes` varchar(20) DEFAULT NULL COMMENT 'Mes',
  `dias_faltantes` int(11) DEFAULT NULL COMMENT 'Días Faltantes',
  `estado` varchar(50) DEFAULT NULL COMMENT 'Estado',
  `dias_producido` int(11) DEFAULT NULL COMMENT 'Dias de producido',
  `menor_10_dias` decimal(10,2) DEFAULT NULL COMMENT '< 10 Dias',
  `mayor_10_dias` decimal(10,2) DEFAULT NULL COMMENT '> 10 Dias',
  `menor_30_dias` decimal(10,2) DEFAULT NULL COMMENT '< 30 Dias',
  `mayor_30_dias` decimal(10,2) DEFAULT NULL COMMENT '> 30 Dias',
  `valor_unitario` decimal(15,2) DEFAULT NULL COMMENT 'VALOR UNITARIO',
  `valor_total` decimal(15,2) DEFAULT NULL COMMENT 'VALOR TOTAL',
  `cajas_totales` decimal(10,2) DEFAULT NULL COMMENT 'CAJAS TOTALES',
  `canal` varchar(50) DEFAULT NULL COMMENT 'CANAL',
  `ubicacion` varchar(150) DEFAULT NULL COMMENT 'Columna AA (Ubicación)',
  `fecha_carga` timestamp NULL DEFAULT current_timestamp() COMMENT 'Momento en que se subió al sistema'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kpi_indicadores`
--

CREATE TABLE `kpi_indicadores` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('KPI','PI') NOT NULL,
  `temporalidad` enum('Diario','Semanal','Mensual') NOT NULL,
  `unidad_medida` enum('Porcentaje','Numero','Cantidad') NOT NULL,
  `unidad_especifica` varchar(50) DEFAULT NULL,
  `meta_operador` enum('<=','>=','==') NOT NULL,
  `meta_valor` decimal(10,2) NOT NULL,
  `disparador_operador` enum('<=','>=','==') NOT NULL,
  `disparador_valor` decimal(10,2) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `meta_operador_2` varchar(10) DEFAULT NULL,
  `meta_valor_2` decimal(10,2) DEFAULT NULL,
  `disparador_operador_2` varchar(10) DEFAULT NULL,
  `disparador_valor_2` decimal(10,2) DEFAULT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kpi_valores`
--

CREATE TABLE `kpi_valores` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `indicador_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `meta_operador_hist` enum('<=','>=','==') NOT NULL,
  `meta_valor_hist` decimal(10,2) NOT NULL,
  `disparador_operador_hist` enum('<=','>=','==') NOT NULL,
  `disparador_valor_hist` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metas`
--

CREATE TABLE `metas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `actividad` varchar(50) NOT NULL,
  `meta_minima` decimal(10,2) NOT NULL,
  `disparador` decimal(10,2) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operaciones`
--

CREATE TABLE `operaciones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ows_cargue`
--

CREATE TABLE `ows_cargue` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `vehiculos_planeados` int(11) NOT NULL DEFAULT 0,
  `vehiculos_cargados` int(11) NOT NULL DEFAULT 0,
  `franja` int(11) NOT NULL DEFAULT 0,
  `usuario_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ows_reempaque`
--

CREATE TABLE `ows_reempaque` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `actividad` varchar(100) NOT NULL DEFAULT 'REEMPAQUE',
  `unidades` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ows_revision`
--

CREATE TABLE `ows_revision` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `actividad` varchar(100) NOT NULL DEFAULT 'REVISION',
  `unidades` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ows_vertimiento`
--

CREATE TABLE `ows_vertimiento` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `actividad` varchar(100) NOT NULL DEFAULT 'VERTIMIENTO',
  `unidades` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pasajes`
--

CREATE TABLE `pasajes` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `marca_temporal` datetime NOT NULL,
  `placa_sider` varchar(20) NOT NULL,
  `origen` varchar(100) NOT NULL,
  `verificador` varchar(100) NOT NULL,
  `descripcion_material` varchar(100) NOT NULL,
  `cantidad_cajas` int(11) NOT NULL DEFAULT 0,
  `peso_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` varchar(50) NOT NULL DEFAULT 'NO OK',
  `observaciones2` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_activo`
--

CREATE TABLE `personal_activo` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(255) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `picking_posiciones`
--

CREATE TABLE `picking_posiciones` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(100) NOT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `picking_registros`
--

CREATE TABLE `picking_registros` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `posicion_id` int(11) NOT NULL,
  `fecha_dia` date NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `fecha_producto` date DEFAULT NULL,
  `fecha_archivo` date DEFAULT NULL,
  `cumple` tinyint(1) DEFAULT 0,
  `evaluado_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `picking_skus_archivo`
--

CREATE TABLE `picking_skus_archivo` (
  `sku` varchar(50) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pi_despachados`
--

CREATE TABLE `pi_despachados` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `cd` varchar(100) NOT NULL,
  `verificador` varchar(100) NOT NULL,
  `distribuidor` enum('Surti','Logisticos') NOT NULL,
  `placa` varchar(20) NOT NULL,
  `cajas_recibidas` int(11) DEFAULT 0,
  `envases_recibidos` int(11) DEFAULT 0,
  `cajas_resividas` int(11) DEFAULT 0,
  `envases_resividos` int(11) DEFAULT 0,
  `descripcion_envase` text DEFAULT NULL,
  `unidades_rotas` varchar(100) DEFAULT NULL,
  `unidades_faltantes` varchar(100) DEFAULT NULL,
  `unidades_otras_companias` int(11) DEFAULT 0,
  `unidades_antiguo_formato` int(11) DEFAULT 0,
  `unidades_nr` int(11) DEFAULT 0,
  `unidades_mal_estado` int(11) DEFAULT 0,
  `unidades_mal_clasificadas` int(11) DEFAULT 0,
  `plasticos_mal_estado` int(11) DEFAULT 0,
  `unidades_cuerpo_extrano` int(11) DEFAULT 0,
  `envases_sucios_recuperables` int(11) DEFAULT 0,
  `estibas_mal_estado` int(11) DEFAULT 0,
  `estibas_buen_estado` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pi_reabastecimiento`
--

CREATE TABLE `pi_reabastecimiento` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `marca_temporal` date NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion_material` varchar(500) NOT NULL,
  `cantidad_estibas` int(11) NOT NULL,
  `tipo_picking` enum('no retornable','retornable') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `placas`
--

CREATE TABLE `placas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `placa` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planeacion_semanal`
--

CREATE TABLE `planeacion_semanal` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `identificador` varchar(20) NOT NULL COMMENT 'ID del empleado que viene de CAB',
  `semana` varchar(10) NOT NULL COMMENT 'Formato ISO, Ej: 2023-W45',
  `turno` varchar(20) DEFAULT 'Sin Turno' COMMENT 'A, B, C, o Sin Turno',
  `actividad` varchar(255) DEFAULT NULL,
  `lunes` varchar(50) DEFAULT NULL,
  `martes` varchar(50) DEFAULT NULL,
  `miercoles` varchar(50) DEFAULT NULL,
  `jueves` varchar(50) DEFAULT NULL,
  `viernes` varchar(50) DEFAULT NULL,
  `sabado` varchar(50) DEFAULT NULL,
  `domingo` varchar(50) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'activo' COMMENT 'activo, descartado, etc',
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `orden` int(11) DEFAULT 9999
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `precios`
--

CREATE TABLE `precios` (
  `Codigo` int(11) DEFAULT NULL,
  `MATERIAL` varchar(255) DEFAULT NULL,
  `ENVASE` decimal(10,2) DEFAULT NULL,
  `COSTO LIQUIDO` decimal(10,2) DEFAULT NULL,
  `TOTAL` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productividades`
--

CREATE TABLE `productividades` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `actividad` varchar(50) NOT NULL,
  `auxiliar` varchar(100) NOT NULL,
  `turno` varchar(10) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `unid_opt` int(11) DEFAULT 0,
  `unid_mal` int(11) DEFAULT 0,
  `unid_total` int(11) DEFAULT 0,
  `hr_inicio` decimal(5,2) DEFAULT NULL,
  `hr_fin` decimal(5,2) DEFAULT NULL,
  `hrs_totales` decimal(5,2) DEFAULT NULL,
  `bandejas` int(11) DEFAULT 0,
  `cumplimiento` decimal(8,2) DEFAULT NULL,
  `cm` varchar(50) DEFAULT NULL,
  `hl` decimal(10,4) DEFAULT NULL,
  `meta` decimal(8,2) DEFAULT NULL,
  `disparador` decimal(8,2) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `id_material` varchar(20) NOT NULL,
  `material` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ptl_areas`
--

CREATE TABLE `ptl_areas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(100) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `rotulacion` tinyint(1) DEFAULT 0,
  `staking` tinyint(1) DEFAULT 0,
  `nivel_almacenamiento` tinyint(1) DEFAULT 0,
  `adherencia_abc` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ptl_subareas`
--

CREATE TABLE `ptl_subareas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `area_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rotulacion` tinyint(1) DEFAULT 0,
  `staking` tinyint(1) DEFAULT 0,
  `nivel_almacenamiento` tinyint(1) DEFAULT 0,
  `adherencia_abc` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recargue_t2`
--

CREATE TABLE `recargue_t2` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `verificador` varchar(100) NOT NULL,
  `turno` varchar(50) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `hora_entrada_bahia` time NOT NULL,
  `hora_inicio_cargue` time NOT NULL,
  `hora_final_cargue` time NOT NULL,
  `hora_salida_bahia` time NOT NULL,
  `opm1` varchar(100) NOT NULL,
  `novedades_salidas_bahia` enum('NO','SI') NOT NULL DEFAULT 'NO',
  `descripcion_novedad` text DEFAULT NULL,
  `tiempo` time NOT NULL,
  `estatus` varchar(100) NOT NULL,
  `canal` varchar(100) NOT NULL,
  `conteo_vehiculo` varchar(50) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recursos_almacen`
--

CREATE TABLE `recursos_almacen` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `sector_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icono` varchar(50) NOT NULL DEFAULT 'link',
  `orden` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recursos_tv`
--

CREATE TABLE `recursos_tv` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `sector_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icono` varchar(50) NOT NULL DEFAULT 'link',
  `refresh_minutos` int(11) NOT NULL DEFAULT 0,
  `orden` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `video_url` varchar(255) DEFAULT '',
  `video_intervalo` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reempaque1`
--

CREATE TABLE `reempaque1` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `auxiliar_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `actividad` varchar(50) NOT NULL,
  `turno` char(1) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `sku` varchar(20) NOT NULL,
  `producto_nombre` varchar(255) NOT NULL,
  `unidades` int(11) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `horas_trabajadas` decimal(4,2) NOT NULL,
  `cumplimiento_individual` decimal(8,2) NOT NULL,
  `cumplimiento_general` decimal(8,2) NOT NULL,
  `cumple_meta` tinyint(1) NOT NULL DEFAULT 0,
  `estado_ciclo` enum('pendiente','completo') DEFAULT 'pendiente',
  `evidencia_5_porque` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `grupo_registro` varchar(50) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revision`
--

CREATE TABLE `revision` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `auxiliar_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `actividad` varchar(100) NOT NULL,
  `turno` varchar(10) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `producto_nombre` varchar(255) NOT NULL,
  `unidades` int(11) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `horas_trabajadas` decimal(10,2) NOT NULL,
  `cumplimiento_individual` decimal(10,2) NOT NULL,
  `cumplimiento_general` decimal(10,2) NOT NULL,
  `cumple_meta` tinyint(1) NOT NULL DEFAULT 0,
  `estado_ciclo` enum('completo','pendiente') NOT NULL DEFAULT 'pendiente',
  `evidencia_5_porque` varchar(500) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `grupo_registro` varchar(50) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roturas`
--

CREATE TABLE `roturas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `supervisor_turno` varchar(100) NOT NULL,
  `turno` varchar(10) NOT NULL,
  `persona_rotura` varchar(100) NOT NULL,
  `placa_montacarga` varchar(50) DEFAULT NULL,
  `placa_camion` varchar(6) DEFAULT NULL,
  `canal` varchar(10) DEFAULT NULL,
  `cargo_persona` varchar(100) NOT NULL,
  `tipo_producto` varchar(20) NOT NULL,
  `codigo_producto` varchar(20) DEFAULT NULL,
  `descripcion_material` text NOT NULL,
  `unidades` int(11) NOT NULL,
  `zona` varchar(100) NOT NULL,
  `casual` text NOT NULL,
  `precio_rotura` decimal(10,2) DEFAULT 0.00,
  `registro_fotografico` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `primer_porque` text NOT NULL,
  `segundo_porque` text NOT NULL,
  `tercer_porque` text NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sectores_almacen`
--

CREATE TABLE `sectores_almacen` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(100) NOT NULL,
  `icono` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#3b82f6',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sectores_tv`
--

CREATE TABLE `sectores_tv` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(100) NOT NULL,
  `icono` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#eab308',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sider_certificados`
--

CREATE TABLE `sider_certificados` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `placa` varchar(10) NOT NULL,
  `planta_destino` varchar(50) DEFAULT 'Bucaramanga',
  `cantidad_estibas` int(11) NOT NULL,
  `tipo_envase` enum('Marron 1000','Marron 750','Flint 1000') NOT NULL,
  `cantidad_estibas_2` int(11) DEFAULT 0,
  `tipo_envase_2` enum('Marron 1000','Marron 750','Flint 1000') DEFAULT NULL,
  `factura` int(11) NOT NULL,
  `supervisor` varchar(50) NOT NULL,
  `facturador` enum('David Omeara','Alvaro Madrigal','Juliana Pabon') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sortiing`
--

CREATE TABLE `sortiing` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `placa` varchar(20) NOT NULL,
  `usuario_porteria` varchar(100) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `hora_ingreso` time NOT NULL,
  `usuario_sorting` varchar(100) DEFAULT NULL,
  `fecha_sorting` date DEFAULT NULL,
  `hora_sorting` time DEFAULT NULL,
  `sku` int(11) DEFAULT NULL,
  `material` varchar(255) DEFAULT NULL,
  `envase` varchar(255) NOT NULL,
  `cajas_sorting` int(11) DEFAULT NULL,
  `estado` enum('pendiente','completado') NOT NULL DEFAULT 'pendiente',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sorting`
--

CREATE TABLE `sorting` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `colaborador_id` int(11) NOT NULL,
  `turno` enum('A','B','C') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tableros`
--

CREATE TABLE `tableros` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(100) NOT NULL,
  `codigo_qr` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tablero_inventario`
--

CREATE TABLE `tablero_inventario` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `tablero_id` int(11) NOT NULL,
  `id_material` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `ultima_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tablero_verificaciones`
--

CREATE TABLE `tablero_verificaciones` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `tablero_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ruta_foto` varchar(255) NOT NULL,
  `fecha_verificacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temperaturas`
--

CREATE TABLE `temperaturas` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `hora` varchar(10) NOT NULL,
  `lugar` varchar(255) NOT NULL,
  `temperatura` decimal(5,2) NOT NULL,
  `nombre_persona` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temperatura_au`
--

CREATE TABLE `temperatura_au` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `hora` varchar(20) NOT NULL,
  `lugar` varchar(50) NOT NULL,
  `temperatura` decimal(5,2) NOT NULL,
  `persona` varchar(100) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiempos_atencion`
--

CREATE TABLE `tiempos_atencion` (
  `fecha` date NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `t1` int(11) DEFAULT 0,
  `t2` int(11) DEFAULT 0,
  `t4` int(11) DEFAULT 0,
  `cumple` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnoa_registros`
--

CREATE TABLE `turnoa_registros` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL,
  `supervisor` varchar(100) NOT NULL,
  `proyeccion_turno` enum('Turno A','Turno B','Turno C') NOT NULL,
  `manejo_handling` enum('Sí','No') NOT NULL,
  `vh_t1` decimal(10,2) DEFAULT 0.00,
  `tiempos_t1` decimal(10,2) DEFAULT 0.00,
  `vh_t2` decimal(10,2) DEFAULT 0.00,
  `tiempos_t2` decimal(10,2) DEFAULT 0.00,
  `vh_t4` decimal(10,2) DEFAULT 0.00,
  `tiempos_t4` decimal(10,2) DEFAULT 0.00,
  `vh_mkp` decimal(10,2) DEFAULT 0.00,
  `horas_reempaque` decimal(10,2) DEFAULT 0.00,
  `cajas_reempacadas` int(11) DEFAULT 0,
  `horas_limpieza_clasificacion` decimal(10,2) DEFAULT 0.00,
  `cajas_clasificadas` int(11) DEFAULT 0,
  `horas_lavado_unidades` decimal(10,2) DEFAULT 0.00,
  `cajas_lavadas` int(11) DEFAULT 0,
  `horas_vertimiento` decimal(10,2) DEFAULT 0.00,
  `cajas_vertidas` int(11) DEFAULT 0,
  `horas_revision_rn` decimal(10,2) DEFAULT 0.00,
  `cajas_rn` int(11) DEFAULT 0,
  `horas_revision_nr` decimal(10,2) DEFAULT 0.00,
  `cajas_nr` int(11) DEFAULT 0,
  `toma_temperatura` enum('Sí','No') NOT NULL,
  `surtido_picking` enum('Sí','No') NOT NULL,
  `estibas_sider_certificados` varchar(255) DEFAULT NULL,
  `video_dpo` enum('Sí','No') NOT NULL,
  `auxiliar_entrevistado` varchar(100) DEFAULT NULL,
  `imagen_lavado_unidades` varchar(255) DEFAULT NULL,
  `imagen_reempaque` varchar(255) DEFAULT NULL,
  `imagen_staying` varchar(255) DEFAULT NULL,
  `imagen_sendero` varchar(255) DEFAULT NULL,
  `imagen_pns` varchar(255) DEFAULT NULL,
  `imagen_vertimiento` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnob_registros`
--

CREATE TABLE `turnob_registros` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL,
  `supervisor` varchar(255) NOT NULL,
  `proyeccion_turno` varchar(255) NOT NULL,
  `cumplimiento_handling` enum('Sí','No') NOT NULL,
  `vh_t1` decimal(10,2) DEFAULT 0.00,
  `tiempos_t1` decimal(10,2) DEFAULT 0.00,
  `vh_t2` decimal(10,2) DEFAULT 0.00,
  `tiempos_t2` decimal(10,2) DEFAULT 0.00,
  `vh_descargados_t2` decimal(10,2) DEFAULT 0.00,
  `vh_t4` decimal(10,2) DEFAULT 0.00,
  `tiempos_t4` decimal(10,2) DEFAULT 0.00,
  `vh_mkp` decimal(10,2) DEFAULT 0.00,
  `reempaque_horas` varchar(255) DEFAULT NULL,
  `cajas_reempacadas` varchar(255) DEFAULT NULL,
  `limpieza_clasificacion_horas` varchar(255) DEFAULT NULL,
  `cajas_clasificadas` int(11) DEFAULT 0,
  `lavado_unidades_horas` varchar(255) DEFAULT NULL,
  `cajas_lavadas` int(11) DEFAULT 0,
  `vertimiento_horas` varchar(255) DEFAULT NULL,
  `cajas_vertidas` int(11) DEFAULT 0,
  `revision_rn_horas` varchar(255) DEFAULT NULL,
  `cajas_rn` int(11) DEFAULT 0,
  `revision_nr_horas` varchar(255) DEFAULT NULL,
  `cajas_nr` int(11) DEFAULT 0,
  `sorting_horas` varchar(255) DEFAULT NULL,
  `cajas_sorting` int(11) DEFAULT 0,
  `toma_temperatura` enum('Sí','No') NOT NULL,
  `surtido_picking` enum('Sí','No') NOT NULL,
  `estibas_sider_certificados` int(11) DEFAULT 0,
  `placas_certificados` varchar(255) DEFAULT NULL,
  `video_dpo` enum('Sí','No') NOT NULL,
  `auxiliar_entrevistado` varchar(255) DEFAULT NULL,
  `sider_certificados` enum('Sí','No') NOT NULL,
  `imagen_lavado_unidades` varchar(255) DEFAULT NULL,
  `imagen_reempaque` varchar(255) DEFAULT NULL,
  `imagen_staying` varchar(255) DEFAULT NULL,
  `imagen_pnc` varchar(255) DEFAULT NULL,
  `imagen_jaula_pfn1` varchar(255) DEFAULT NULL,
  `imagen_jaula_pfn2` varchar(255) DEFAULT NULL,
  `imagen_vertimiento` varchar(255) DEFAULT NULL,
  `imagen_sorting` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnoc_registros`
--

CREATE TABLE `turnoc_registros` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `marca_temporal` datetime NOT NULL,
  `supervisor` varchar(100) NOT NULL,
  `proyeccion_turno` enum('Si','No') NOT NULL,
  `cumplimiento_handling` enum('Si','No') NOT NULL,
  `vh_t1` int(11) DEFAULT 0,
  `tiempos_t1` int(11) DEFAULT 0,
  `vh_t2_plan` int(11) DEFAULT 0,
  `vh_t2_cargado` int(11) DEFAULT 0,
  `vh_cargado_xhr` int(11) DEFAULT 0,
  `hr_cargado_xhr` int(11) DEFAULT 0,
  `hr_inicio_armado_ka` time DEFAULT NULL,
  `hr_fin_armado_ka` time DEFAULT NULL,
  `productividad_cajas` int(11) DEFAULT 0,
  `hr_inicio_armado` time DEFAULT NULL,
  `hr_fin_armado` time DEFAULT NULL,
  `cajas_total` int(11) DEFAULT 0,
  `cajas_picking` int(11) DEFAULT 0,
  `porcentaje_picking` decimal(5,2) DEFAULT 0.00,
  `aux_rn` int(11) DEFAULT 0,
  `cajas_rn` int(11) DEFAULT 0,
  `aux_nr` int(11) DEFAULT 0,
  `cajas_nr` int(11) DEFAULT 0,
  `cajas_mkp` int(11) DEFAULT 0,
  `productividad_mkp` int(11) DEFAULT 0,
  `errores_auxiliares` int(11) DEFAULT 0,
  `pi_reabastecimiento` int(11) DEFAULT 0,
  `actividad_adicional_1` text DEFAULT NULL,
  `hrs_1` varchar(50) DEFAULT NULL,
  `productividad_1` int(11) DEFAULT 0,
  `actividad_adicional_2` text DEFAULT NULL,
  `hrs_2` varchar(50) DEFAULT NULL,
  `productividad_2` int(11) DEFAULT 0,
  `actividad_adicional_3` text DEFAULT NULL,
  `productividad_3` varchar(50) DEFAULT NULL,
  `hrs_3` varchar(50) DEFAULT NULL,
  `auxiliar_entrevistado` text DEFAULT NULL,
  `picking_img` varchar(255) DEFAULT NULL,
  `pnc_img` varchar(255) DEFAULT NULL,
  `stayin_img` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicaciones`
--

CREATE TABLE `ubicaciones` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `usuario_id` varchar(50) NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `ultima_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `cargo` enum('admin','supervisor','verificador','auxiliar','operador','lider','coplas','super_admin') NOT NULL,
  `operacion_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `huella` longtext DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vertimiento`
--

CREATE TABLE `vertimiento` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `auxiliar_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `actividad` varchar(100) NOT NULL,
  `turno` varchar(10) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `producto_nombre` varchar(255) NOT NULL,
  `unidades` int(11) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `horas_trabajadas` decimal(10,2) NOT NULL,
  `cumplimiento_individual` decimal(10,2) NOT NULL,
  `cumplimiento_general` decimal(10,2) NOT NULL,
  `cumple_meta` tinyint(1) NOT NULL DEFAULT 0,
  `estado_ciclo` enum('completo','pendiente') NOT NULL DEFAULT 'pendiente',
  `evidencia_5_porque` varchar(500) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `grupo_registro` varchar(50) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades_ol`
--
ALTER TABLE `actividades_ol`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `auditorias`
--
ALTER TABLE `auditorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indices de la tabla `auditoria_checklists`
--
ALTER TABLE `auditoria_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_id` (`auditoria_id`);

--
-- Indices de la tabla `auditoria_checklist_preguntas`
--
ALTER TABLE `auditoria_checklist_preguntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_id` (`checklist_id`);

--
-- Indices de la tabla `auditoria_ejecuciones`
--
ALTER TABLE `auditoria_ejecuciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_id` (`checklist_id`),
  ADD KEY `fk_auditoria_ejecuciones_operacion` (`operacion_id`);

--
-- Indices de la tabla `auditoria_ejecucion_respuestas`
--
ALTER TABLE `auditoria_ejecucion_respuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ejecucion_id` (`ejecucion_id`),
  ADD KEY `pregunta_id` (`pregunta_id`),
  ADD KEY `fk_auditoria_ejecucion_respuestas_operacion` (`operacion_id`);

--
-- Indices de la tabla `auditoria_media`
--
ALTER TABLE `auditoria_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_id` (`auditoria_id`);

--
-- Indices de la tabla `auditoria_zonas`
--
ALTER TABLE `auditoria_zonas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_auditoria_zonas_operacion` (`operacion_id`);

--
-- Indices de la tabla `cargar_informativo`
--
ALTER TABLE `cargar_informativo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuario_informativo` (`usuario_id`),
  ADD KEY `fk_cargar_informativo_operacion` (`operacion_id`);

--
-- Indices de la tabla `checklist_wip`
--
ALTER TABLE `checklist_wip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha_registro`),
  ADD KEY `idx_usuario` (`nombre_usuario`),
  ADD KEY `fk_checklist_wip_operacion` (`operacion_id`);

--
-- Indices de la tabla `check_herramientas`
--
ALTER TABLE `check_herramientas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_check_herramientas_operacion` (`operacion_id`);

--
-- Indices de la tabla `config_bloqueo`
--
ALTER TABLE `config_bloqueo`
  ADD PRIMARY KEY (`mes_anio`,`operacion_id`),
  ADD KEY `fk_config_bloqueo_operacion` (`operacion_id`);

--
-- Indices de la tabla `config_opm_global`
--
ALTER TABLE `config_opm_global`
  ADD PRIMARY KEY (`mes_anio`);

--
-- Indices de la tabla `control_trampas`
--
ALTER TABLE `control_trampas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_control_trampas_operacion` (`operacion_id`);

--
-- Indices de la tabla `descansos`
--
ALTER TABLE `descansos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_descansos_operacion` (`operacion_id`);

--
-- Indices de la tabla `descargue`
--
ALTER TABLE `descargue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_sortiing` (`id_sortiing`),
  ADD KEY `fk_descargue_operacion` (`operacion_id`);

--
-- Indices de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_devoluciones_operacion` (`operacion_id`);

--
-- Indices de la tabla `error_armado`
--
ALTER TABLE `error_armado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_error_armado_operacion` (`operacion_id`);

--
-- Indices de la tabla `error_verificacion`
--
ALTER TABLE `error_verificacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_error_verificacion_operacion` (`operacion_id`);

--
-- Indices de la tabla `fefo_areas`
--
ALTER TABLE `fefo_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fefo_areas_operacion` (`operacion_id`);

--
-- Indices de la tabla `fefo_registros`
--
ALTER TABLE `fefo_registros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registro_diario` (`subarea_id`,`fecha_dia`),
  ADD KEY `fk_fefo_registros_operacion` (`operacion_id`);

--
-- Indices de la tabla `fefo_subareas`
--
ALTER TABLE `fefo_subareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `fk_fefo_subareas_operacion` (`operacion_id`);

--
-- Indices de la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_insumos_operacion` (`operacion_id`);

--
-- Indices de la tabla `inventario_if`
--
ALTER TABLE `inventario_if`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cod_sku` (`cod_sku`),
  ADD KEY `idx_fecha_analisis` (`fecha_analisis`),
  ADD KEY `idx_canal` (`canal`),
  ADD KEY `fk_inventario_if_operacion` (`operacion_id`);

--
-- Indices de la tabla `kpi_indicadores`
--
ALTER TABLE `kpi_indicadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kpi_indicadores_operacion` (`operacion_id`);

--
-- Indices de la tabla `kpi_valores`
--
ALTER TABLE `kpi_valores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `indicador_id` (`indicador_id`,`fecha`),
  ADD KEY `fk_kpi_valores_operacion` (`operacion_id`);

--
-- Indices de la tabla `metas`
--
ALTER TABLE `metas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `actividad_unique` (`actividad`),
  ADD KEY `fk_metas_operacion` (`operacion_id`);

--
-- Indices de la tabla `operaciones`
--
ALTER TABLE `operaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `ows_cargue`
--
ALTER TABLE `ows_cargue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha_hora` (`fecha`,`hora`),
  ADD KEY `fk_ows_cargue_operacion` (`operacion_id`);

--
-- Indices de la tabla `ows_reempaque`
--
ALTER TABLE `ows_reempaque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_fecha` (`usuario_id`,`fecha`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `fk_ows_reempaque_operacion` (`operacion_id`);

--
-- Indices de la tabla `ows_revision`
--
ALTER TABLE `ows_revision`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_fecha` (`usuario_id`,`fecha`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `fk_ows_revision_operacion` (`operacion_id`);

--
-- Indices de la tabla `ows_vertimiento`
--
ALTER TABLE `ows_vertimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_fecha` (`usuario_id`,`fecha`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `fk_ows_vertimiento_operacion` (`operacion_id`);

--
-- Indices de la tabla `pasajes`
--
ALTER TABLE `pasajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pasajes_operacion` (`operacion_id`);

--
-- Indices de la tabla `personal_activo`
--
ALTER TABLE `personal_activo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nombre` (`nombre`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_cargo` (`cargo`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `fk_personal_activo_operacion` (`operacion_id`);

--
-- Indices de la tabla `picking_posiciones`
--
ALTER TABLE `picking_posiciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_picking_posiciones_operacion` (`operacion_id`);

--
-- Indices de la tabla `picking_registros`
--
ALTER TABLE `picking_registros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registro_diario` (`posicion_id`,`fecha_dia`),
  ADD KEY `fk_picking_registros_operacion` (`operacion_id`);

--
-- Indices de la tabla `picking_skus_archivo`
--
ALTER TABLE `picking_skus_archivo`
  ADD PRIMARY KEY (`sku`);

--
-- Indices de la tabla `pi_despachados`
--
ALTER TABLE `pi_despachados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pi_despachados_operacion` (`operacion_id`);

--
-- Indices de la tabla `pi_reabastecimiento`
--
ALTER TABLE `pi_reabastecimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pi_reabastecimiento_operacion` (`operacion_id`);

--
-- Indices de la tabla `placas`
--
ALTER TABLE `placas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_placas_operacion` (`operacion_id`);

--
-- Indices de la tabla `planeacion_semanal`
--
ALTER TABLE `planeacion_semanal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_planeacion` (`identificador`,`semana`),
  ADD KEY `fk_planeacion_semanal_operacion` (`operacion_id`);

--
-- Indices de la tabla `productividades`
--
ALTER TABLE `productividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_productividades_operacion` (`operacion_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_id_material` (`id_material`),
  ADD KEY `idx_id_material` (`id_material`),
  ADD KEY `idx_material` (`material`);

--
-- Indices de la tabla `ptl_areas`
--
ALTER TABLE `ptl_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ptl_areas_operacion` (`operacion_id`);

--
-- Indices de la tabla `ptl_subareas`
--
ALTER TABLE `ptl_subareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `fk_ptl_subareas_operacion` (`operacion_id`);

--
-- Indices de la tabla `recargue_t2`
--
ALTER TABLE `recargue_t2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recargue_t_operacion` (`operacion_id`);

--
-- Indices de la tabla `recursos_almacen`
--
ALTER TABLE `recursos_almacen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `fk_recursos_almacen_operacion` (`operacion_id`);

--
-- Indices de la tabla `recursos_tv`
--
ALTER TABLE `recursos_tv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `fk_recursos_tv_operacion` (`operacion_id`);

--
-- Indices de la tabla `reempaque1`
--
ALTER TABLE `reempaque1`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_auxiliar` (`auxiliar_id`),
  ADD KEY `fk_producto` (`producto_id`),
  ADD KEY `fk_reempaque_operacion` (`operacion_id`);

--
-- Indices de la tabla `revision`
--
ALTER TABLE `revision`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auxiliar` (`auxiliar_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_grupo_registro` (`grupo_registro`),
  ADD KEY `idx_estado_ciclo` (`estado_ciclo`),
  ADD KEY `fk_revision_operacion` (`operacion_id`);

--
-- Indices de la tabla `roturas`
--
ALTER TABLE `roturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_codigo_producto` (`codigo_producto`),
  ADD KEY `fk_roturas_operacion` (`operacion_id`);

--
-- Indices de la tabla `sectores_almacen`
--
ALTER TABLE `sectores_almacen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sectores_almacen_operacion` (`operacion_id`);

--
-- Indices de la tabla `sectores_tv`
--
ALTER TABLE `sectores_tv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sectores_tv_operacion` (`operacion_id`);

--
-- Indices de la tabla `sider_certificados`
--
ALTER TABLE `sider_certificados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sider_certificados_operacion` (`operacion_id`);

--
-- Indices de la tabla `sortiing`
--
ALTER TABLE `sortiing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sortiing_operacion` (`operacion_id`);

--
-- Indices de la tabla `sorting`
--
ALTER TABLE `sorting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_colaborador` (`colaborador_id`),
  ADD KEY `fk_sorting_operacion` (`operacion_id`);

--
-- Indices de la tabla `tableros`
--
ALTER TABLE `tableros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tableros_operacion` (`operacion_id`);

--
-- Indices de la tabla `tablero_inventario`
--
ALTER TABLE `tablero_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tablero` (`tablero_id`),
  ADD KEY `idx_material` (`id_material`),
  ADD KEY `fk_tablero_inventario_operacion` (`operacion_id`);

--
-- Indices de la tabla `tablero_verificaciones`
--
ALTER TABLE `tablero_verificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tablero_verificaciones_operacion` (`operacion_id`);

--
-- Indices de la tabla `temperaturas`
--
ALTER TABLE `temperaturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_temperaturas_operacion` (`operacion_id`);

--
-- Indices de la tabla `temperatura_au`
--
ALTER TABLE `temperatura_au`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_temperatura_au_operacion` (`operacion_id`);

--
-- Indices de la tabla `tiempos_atencion`
--
ALTER TABLE `tiempos_atencion`
  ADD PRIMARY KEY (`fecha`,`operacion_id`),
  ADD KEY `fk_tiempos_atencion_operacion` (`operacion_id`);

--
-- Indices de la tabla `turnoa_registros`
--
ALTER TABLE `turnoa_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_turnoa_registros_operacion` (`operacion_id`);

--
-- Indices de la tabla `turnob_registros`
--
ALTER TABLE `turnob_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_turnob_registros_operacion` (`operacion_id`);

--
-- Indices de la tabla `turnoc_registros`
--
ALTER TABLE `turnoc_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_turnoc_registros_operacion` (`operacion_id`);

--
-- Indices de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_ubicaciones_operacion` (`operacion_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `fk_usuarios_operacion` (`operacion_id`);

--
-- Indices de la tabla `vertimiento`
--
ALTER TABLE `vertimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auxiliar` (`auxiliar_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_grupo_registro` (`grupo_registro`),
  ADD KEY `idx_estado_ciclo` (`estado_ciclo`),
  ADD KEY `fk_vertimiento_operacion` (`operacion_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades_ol`
--
ALTER TABLE `actividades_ol`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditorias`
--
ALTER TABLE `auditorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria_checklists`
--
ALTER TABLE `auditoria_checklists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria_checklist_preguntas`
--
ALTER TABLE `auditoria_checklist_preguntas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria_ejecuciones`
--
ALTER TABLE `auditoria_ejecuciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria_ejecucion_respuestas`
--
ALTER TABLE `auditoria_ejecucion_respuestas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria_media`
--
ALTER TABLE `auditoria_media`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria_zonas`
--
ALTER TABLE `auditoria_zonas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cargar_informativo`
--
ALTER TABLE `cargar_informativo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checklist_wip`
--
ALTER TABLE `checklist_wip`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `check_herramientas`
--
ALTER TABLE `check_herramientas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `control_trampas`
--
ALTER TABLE `control_trampas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `descansos`
--
ALTER TABLE `descansos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `descargue`
--
ALTER TABLE `descargue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `error_armado`
--
ALTER TABLE `error_armado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `error_verificacion`
--
ALTER TABLE `error_verificacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fefo_areas`
--
ALTER TABLE `fefo_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fefo_registros`
--
ALTER TABLE `fefo_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fefo_subareas`
--
ALTER TABLE `fefo_subareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_if`
--
ALTER TABLE `inventario_if`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `kpi_indicadores`
--
ALTER TABLE `kpi_indicadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `kpi_valores`
--
ALTER TABLE `kpi_valores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metas`
--
ALTER TABLE `metas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `operaciones`
--
ALTER TABLE `operaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ows_cargue`
--
ALTER TABLE `ows_cargue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ows_reempaque`
--
ALTER TABLE `ows_reempaque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ows_revision`
--
ALTER TABLE `ows_revision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ows_vertimiento`
--
ALTER TABLE `ows_vertimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pasajes`
--
ALTER TABLE `pasajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personal_activo`
--
ALTER TABLE `personal_activo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `picking_posiciones`
--
ALTER TABLE `picking_posiciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `picking_registros`
--
ALTER TABLE `picking_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pi_despachados`
--
ALTER TABLE `pi_despachados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pi_reabastecimiento`
--
ALTER TABLE `pi_reabastecimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `placas`
--
ALTER TABLE `placas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `planeacion_semanal`
--
ALTER TABLE `planeacion_semanal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productividades`
--
ALTER TABLE `productividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ptl_areas`
--
ALTER TABLE `ptl_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ptl_subareas`
--
ALTER TABLE `ptl_subareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recargue_t2`
--
ALTER TABLE `recargue_t2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recursos_almacen`
--
ALTER TABLE `recursos_almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recursos_tv`
--
ALTER TABLE `recursos_tv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reempaque1`
--
ALTER TABLE `reempaque1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `revision`
--
ALTER TABLE `revision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roturas`
--
ALTER TABLE `roturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sectores_almacen`
--
ALTER TABLE `sectores_almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sectores_tv`
--
ALTER TABLE `sectores_tv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sider_certificados`
--
ALTER TABLE `sider_certificados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sortiing`
--
ALTER TABLE `sortiing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sorting`
--
ALTER TABLE `sorting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tableros`
--
ALTER TABLE `tableros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tablero_inventario`
--
ALTER TABLE `tablero_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tablero_verificaciones`
--
ALTER TABLE `tablero_verificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temperaturas`
--
ALTER TABLE `temperaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temperatura_au`
--
ALTER TABLE `temperatura_au`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turnoa_registros`
--
ALTER TABLE `turnoa_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turnob_registros`
--
ALTER TABLE `turnob_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turnoc_registros`
--
ALTER TABLE `turnoc_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vertimiento`
--
ALTER TABLE `vertimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria_checklists`
--
ALTER TABLE `auditoria_checklists`
  ADD CONSTRAINT `auditoria_checklists_ibfk_1` FOREIGN KEY (`auditoria_id`) REFERENCES `auditorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `auditoria_checklist_preguntas`
--
ALTER TABLE `auditoria_checklist_preguntas`
  ADD CONSTRAINT `auditoria_checklist_preguntas_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `auditoria_checklists` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `auditoria_ejecuciones`
--
ALTER TABLE `auditoria_ejecuciones`
  ADD CONSTRAINT `auditoria_ejecuciones_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `auditoria_checklists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_auditoria_ejecuciones_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `auditoria_ejecucion_respuestas`
--
ALTER TABLE `auditoria_ejecucion_respuestas`
  ADD CONSTRAINT `auditoria_ejecucion_respuestas_ibfk_1` FOREIGN KEY (`ejecucion_id`) REFERENCES `auditoria_ejecuciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auditoria_ejecucion_respuestas_ibfk_2` FOREIGN KEY (`pregunta_id`) REFERENCES `auditoria_checklist_preguntas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_auditoria_ejecucion_respuestas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `auditoria_media`
--
ALTER TABLE `auditoria_media`
  ADD CONSTRAINT `auditoria_media_ibfk_1` FOREIGN KEY (`auditoria_id`) REFERENCES `auditorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `auditoria_zonas`
--
ALTER TABLE `auditoria_zonas`
  ADD CONSTRAINT `fk_auditoria_zonas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `cargar_informativo`
--
ALTER TABLE `cargar_informativo`
  ADD CONSTRAINT `fk_cargar_informativo_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`),
  ADD CONSTRAINT `fk_usuario_informativo` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `checklist_wip`
--
ALTER TABLE `checklist_wip`
  ADD CONSTRAINT `fk_checklist_wip_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `check_herramientas`
--
ALTER TABLE `check_herramientas`
  ADD CONSTRAINT `check_herramientas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_check_herramientas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `config_bloqueo`
--
ALTER TABLE `config_bloqueo`
  ADD CONSTRAINT `fk_config_bloqueo_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `control_trampas`
--
ALTER TABLE `control_trampas`
  ADD CONSTRAINT `fk_control_trampas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `descansos`
--
ALTER TABLE `descansos`
  ADD CONSTRAINT `fk_descansos_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `descargue`
--
ALTER TABLE `descargue`
  ADD CONSTRAINT `fk_descargue_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD CONSTRAINT `fk_devoluciones_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `error_armado`
--
ALTER TABLE `error_armado`
  ADD CONSTRAINT `fk_error_armado_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `error_verificacion`
--
ALTER TABLE `error_verificacion`
  ADD CONSTRAINT `fk_error_verificacion_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `fefo_areas`
--
ALTER TABLE `fefo_areas`
  ADD CONSTRAINT `fk_fefo_areas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `fefo_registros`
--
ALTER TABLE `fefo_registros`
  ADD CONSTRAINT `fefo_registros_ibfk_1` FOREIGN KEY (`subarea_id`) REFERENCES `fefo_subareas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fefo_registros_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `fefo_subareas`
--
ALTER TABLE `fefo_subareas`
  ADD CONSTRAINT `fk_fefo_subareas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD CONSTRAINT `fk_insumos_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `inventario_if`
--
ALTER TABLE `inventario_if`
  ADD CONSTRAINT `fk_inventario_if_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `kpi_indicadores`
--
ALTER TABLE `kpi_indicadores`
  ADD CONSTRAINT `fk_kpi_indicadores_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `kpi_valores`
--
ALTER TABLE `kpi_valores`
  ADD CONSTRAINT `fk_kpi_valores_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `metas`
--
ALTER TABLE `metas`
  ADD CONSTRAINT `fk_metas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `ows_cargue`
--
ALTER TABLE `ows_cargue`
  ADD CONSTRAINT `fk_ows_cargue_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `ows_reempaque`
--
ALTER TABLE `ows_reempaque`
  ADD CONSTRAINT `fk_ows_reempaque_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `ows_revision`
--
ALTER TABLE `ows_revision`
  ADD CONSTRAINT `fk_ows_revision_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `ows_vertimiento`
--
ALTER TABLE `ows_vertimiento`
  ADD CONSTRAINT `fk_ows_vertimiento_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `pasajes`
--
ALTER TABLE `pasajes`
  ADD CONSTRAINT `fk_pasajes_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `personal_activo`
--
ALTER TABLE `personal_activo`
  ADD CONSTRAINT `fk_personal_activo_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `picking_posiciones`
--
ALTER TABLE `picking_posiciones`
  ADD CONSTRAINT `fk_picking_posiciones_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `picking_registros`
--
ALTER TABLE `picking_registros`
  ADD CONSTRAINT `fk_picking_registros_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `pi_despachados`
--
ALTER TABLE `pi_despachados`
  ADD CONSTRAINT `fk_pi_despachados_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `pi_reabastecimiento`
--
ALTER TABLE `pi_reabastecimiento`
  ADD CONSTRAINT `fk_pi_reabastecimiento_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `placas`
--
ALTER TABLE `placas`
  ADD CONSTRAINT `fk_placas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `planeacion_semanal`
--
ALTER TABLE `planeacion_semanal`
  ADD CONSTRAINT `fk_planeacion_semanal_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `productividades`
--
ALTER TABLE `productividades`
  ADD CONSTRAINT `fk_productividades_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `ptl_areas`
--
ALTER TABLE `ptl_areas`
  ADD CONSTRAINT `fk_ptl_areas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `ptl_subareas`
--
ALTER TABLE `ptl_subareas`
  ADD CONSTRAINT `fk_ptl_subareas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `recargue_t2`
--
ALTER TABLE `recargue_t2`
  ADD CONSTRAINT `fk_recargue_t_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `recursos_almacen`
--
ALTER TABLE `recursos_almacen`
  ADD CONSTRAINT `fk_recursos_almacen_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `recursos_tv`
--
ALTER TABLE `recursos_tv`
  ADD CONSTRAINT `fk_recursos_tv_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `reempaque1`
--
ALTER TABLE `reempaque1`
  ADD CONSTRAINT `fk_reempaque_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `revision`
--
ALTER TABLE `revision`
  ADD CONSTRAINT `fk_revision_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `roturas`
--
ALTER TABLE `roturas`
  ADD CONSTRAINT `fk_roturas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `sectores_almacen`
--
ALTER TABLE `sectores_almacen`
  ADD CONSTRAINT `fk_sectores_almacen_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `sectores_tv`
--
ALTER TABLE `sectores_tv`
  ADD CONSTRAINT `fk_sectores_tv_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `sider_certificados`
--
ALTER TABLE `sider_certificados`
  ADD CONSTRAINT `fk_sider_certificados_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `sortiing`
--
ALTER TABLE `sortiing`
  ADD CONSTRAINT `fk_sortiing_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `sorting`
--
ALTER TABLE `sorting`
  ADD CONSTRAINT `fk_sorting_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `tableros`
--
ALTER TABLE `tableros`
  ADD CONSTRAINT `fk_tableros_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `tablero_inventario`
--
ALTER TABLE `tablero_inventario`
  ADD CONSTRAINT `fk_tablero_inventario_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `tablero_verificaciones`
--
ALTER TABLE `tablero_verificaciones`
  ADD CONSTRAINT `fk_tablero_verificaciones_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `temperaturas`
--
ALTER TABLE `temperaturas`
  ADD CONSTRAINT `fk_temperaturas_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `temperatura_au`
--
ALTER TABLE `temperatura_au`
  ADD CONSTRAINT `fk_temperatura_au_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `tiempos_atencion`
--
ALTER TABLE `tiempos_atencion`
  ADD CONSTRAINT `fk_tiempos_atencion_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `turnoa_registros`
--
ALTER TABLE `turnoa_registros`
  ADD CONSTRAINT `fk_turnoa_registros_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `turnob_registros`
--
ALTER TABLE `turnob_registros`
  ADD CONSTRAINT `fk_turnob_registros_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `turnoc_registros`
--
ALTER TABLE `turnoc_registros`
  ADD CONSTRAINT `fk_turnoc_registros_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD CONSTRAINT `fk_ubicaciones_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);

--
-- Filtros para la tabla `vertimiento`
--
ALTER TABLE `vertimiento`
  ADD CONSTRAINT `fk_vertimiento_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
