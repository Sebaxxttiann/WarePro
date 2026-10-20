-- =====================================================================
-- WarePro - Ajustes octubre 2026
--  1. Metas por operación (catálogo de sistema + metas propias)
--  2. Indicadores diarios por turno (A/B/C) con responsables
--  3. Compensación variable configurable por operación y cargo
-- Ejecutar sobre la BD `warepro`. Es idempotente en lo posible.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. METAS
-- ---------------------------------------------------------------------
-- La actividad debe ser única por operación, no global.
ALTER TABLE `metas` DROP INDEX IF EXISTS `actividad_unique`;
ALTER TABLE `metas` ADD UNIQUE KEY IF NOT EXISTS `uk_metas_operacion_actividad` (`operacion_id`, `actividad`);

ALTER TABLE `metas`
  MODIFY `disparador` decimal(10,2) NULL DEFAULT NULL,
  MODIFY `meta_minima` decimal(10,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `sentido` enum('mayor','menor') NOT NULL DEFAULT 'mayor'
      COMMENT 'mayor: más alto es mejor (cj/h). menor: más bajo es mejor (minutos)' AFTER `unidad_medida`,
  ADD COLUMN IF NOT EXISTS `modulo` varchar(50) DEFAULT NULL COMMENT 'Módulo/área que usa la meta' AFTER `sentido`,
  ADD COLUMN IF NOT EXISTS `descripcion` varchar(255) DEFAULT NULL AFTER `modulo`,
  ADD COLUMN IF NOT EXISTS `es_sistema` tinyint(1) NOT NULL DEFAULT 0
      COMMENT '1 = la busca el código de productividades; no se puede renombrar ni borrar' AFTER `descripcion`,
  ADD COLUMN IF NOT EXISTS `configurada` tinyint(1) NOT NULL DEFAULT 0
      COMMENT '1 = la operación ya revisó/ajustó el valor' AFTER `es_sistema`;

-- ---------------------------------------------------------------------
-- 2. INDICADORES POR TURNO
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ind_turno_indicadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operacion_id` int(11) NOT NULL,
  `turno` char(1) NOT NULL COMMENT 'A, B o C',
  `nombre` varchar(100) NOT NULL,
  `meta_id` int(11) DEFAULT NULL COMMENT 'Si apunta a metas, meta/disparador/sentido salen de ahí',
  `unidad` varchar(30) DEFAULT NULL,
  `sentido` enum('mayor','menor') NOT NULL DEFAULT 'mayor',
  `meta_valor` decimal(12,2) DEFAULT NULL,
  `disparador_valor` decimal(12,2) DEFAULT NULL,
  `aplica_compensacion` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ind_turno_meta` (`operacion_id`, `turno`, `meta_id`),
  KEY `idx_ind_turno_op` (`operacion_id`, `turno`, `activo`),
  CONSTRAINT `fk_ind_turno_op` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`),
  CONSTRAINT `fk_ind_turno_meta` FOREIGN KEY (`meta_id`) REFERENCES `metas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ind_turno_valores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operacion_id` int(11) NOT NULL,
  `indicador_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `turno` char(1) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `sentido_hist` enum('mayor','menor') NOT NULL DEFAULT 'mayor',
  `meta_hist` decimal(12,2) DEFAULT NULL,
  `disparador_hist` decimal(12,2) DEFAULT NULL,
  `estado` enum('cumple','no_cumple','disparador') NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ind_valor_dia` (`indicador_id`, `fecha`),
  KEY `idx_ind_valor_op_fecha` (`operacion_id`, `fecha`),
  CONSTRAINT `fk_ind_valor_ind` FOREIGN KEY (`indicador_id`) REFERENCES `ind_turno_indicadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Personas que estuvieron en el área de ese indicador ese día (puede ser más de una)
CREATE TABLE IF NOT EXISTS `ind_turno_responsables` (
  `valor_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`valor_id`, `usuario_id`),
  KEY `idx_ind_resp_usuario` (`usuario_id`),
  CONSTRAINT `fk_ind_resp_valor` FOREIGN KEY (`valor_id`) REFERENCES `ind_turno_valores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ind_resp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 3. COMPENSACIÓN VARIABLE POR OPERACIÓN Y CARGO
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `compensacion_config` (
  `operacion_id` int(11) NOT NULL,
  `cargo` varchar(30) NOT NULL COMMENT 'auxiliar, verificador, operador',
  `monto_variable` decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'Bolsa variable mensual en COP',
  `monto_base` decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'Base fija asegurada (montacarga)',
  `dias_habiles` int(11) NOT NULL DEFAULT 25,
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`operacion_id`, `cargo`),
  CONSTRAINT `fk_comp_op` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- config_opm_global era global para todas las operaciones
ALTER TABLE `config_opm_global` ADD COLUMN IF NOT EXISTS `operacion_id` int(11) NOT NULL DEFAULT 1 AFTER `mes_anio`;
ALTER TABLE `config_opm_global` DROP PRIMARY KEY, ADD PRIMARY KEY (`mes_anio`, `operacion_id`);

-- Los valores iniciales (catálogo de metas y montos) los siembra PHP
-- automáticamente por operación: core/metas.php y core/compensacion.php
