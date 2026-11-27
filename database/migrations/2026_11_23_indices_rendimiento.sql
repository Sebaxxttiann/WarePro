-- Índices para los tableros (workstations) y consultas diarias por operación.
-- Las consultas filtran por operacion_id + rango de fecha; sin índice compuesto MySQL recorre la tabla completa.
-- MariaDB: IF NOT EXISTS permite correr el script más de una vez sin error.

ALTER TABLE ows_cargue      ADD INDEX IF NOT EXISTS idx_op_fecha (operacion_id, fecha);
ALTER TABLE ows_reempaque   ADD INDEX IF NOT EXISTS idx_op_act_fecha (operacion_id, actividad, fecha);
ALTER TABLE ows_vertimiento ADD INDEX IF NOT EXISTS idx_op_act_fecha (operacion_id, actividad, fecha);
ALTER TABLE ows_revision    ADD INDEX IF NOT EXISTS idx_op_fecha (operacion_id, fecha);
ALTER TABLE descargue       ADD INDEX IF NOT EXISTS idx_op_fin (operacion_id, fecha_hora_fin);
ALTER TABLE descargue       ADD INDEX IF NOT EXISTS idx_op_inicio (operacion_id, fecha_hora_inicio);
ALTER TABLE recargue_t2     ADD INDEX IF NOT EXISTS idx_op_fecha (operacion_id, fecha);
ALTER TABLE sortiing        ADD INDEX IF NOT EXISTS idx_op_fecha_sorting (operacion_id, fecha_sorting);
ALTER TABLE sortiing        ADD INDEX IF NOT EXISTS idx_op_fecha_ingreso (operacion_id, fecha_ingreso);

-- Notificaciones del header (se consulta en CADA página que se abre)
ALTER TABLE reempaque1      ADD INDEX IF NOT EXISTS idx_notif (auxiliar_id, operacion_id, cumple_meta, estado_ciclo, fecha);
