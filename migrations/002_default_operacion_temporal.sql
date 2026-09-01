-- 002_default_operacion_temporal.sql
-- Red de seguridad temporal: mientras se actualizan los INSERT de cada
-- archivo para que pasen operacion_id explicitamente (fase 4 del trabajo
-- multi-operacion), se le da un DEFAULT (Cucuta) a la columna en las 58
-- tablas operativas para que ningun INSERT existente falle con error de
-- SQL mientras tanto. Cuando la fase 4 termine, este DEFAULT puede
-- quitarse (ALTER TABLE ... ALTER COLUMN operacion_id DROP DEFAULT) si se
-- quiere forzar que cada INSERT sea explicito.

SET @op_cucuta = (SELECT id FROM operaciones WHERE nombre = 'Cucuta');

ALTER TABLE `auditoria_ejecuciones` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `auditoria_ejecucion_respuestas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `auditoria_zonas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `cargar_informativo` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `checklist_wip` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `check_herramientas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `control_trampas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `descansos` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `descargue` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `devoluciones` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `error_armado` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `error_verificacion` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `fefo_areas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `fefo_registros` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `fefo_subareas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `insumos` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `inventario_if` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `kpi_indicadores` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `kpi_valores` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `metas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `ows_cargue` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `ows_reempaque` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `ows_revision` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `ows_vertimiento` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `pasajes` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `personal_activo` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `picking_posiciones` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `picking_registros` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `pi_despachados` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `pi_reabastecimiento` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `placas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `planeacion_semanal` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `ptl_areas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `ptl_subareas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `recargue_t2` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `recursos_almacen` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `recursos_tv` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `reempaque1` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `revision` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `roturas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `sectores_almacen` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `sectores_tv` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `sider_certificados` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `sortiing` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `sorting` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `tableros` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `tablero_inventario` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `tablero_verificaciones` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `temperaturas` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `temperatura_au` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `turnoa_registros` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `turnob_registros` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `turnoc_registros` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `ubicaciones` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `vertimiento` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `productividades` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `tiempos_atencion` ALTER COLUMN `operacion_id` SET DEFAULT 1;
ALTER TABLE `config_bloqueo` ALTER COLUMN `operacion_id` SET DEFAULT 1;
