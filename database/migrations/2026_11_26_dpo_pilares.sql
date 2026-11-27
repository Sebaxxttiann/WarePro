-- Entrenamientos · DPO: pilar(es) que impacta cada entrenamiento (selección múltiple).
-- La API también agrega la columna si falta (dpoAsegurarPilares), pero conviene correr esto al desplegar.
ALTER TABLE dpo_registros
    ADD COLUMN IF NOT EXISTS pilares SET('almacen','seguridad','flota','people','gestion') NULL
    COMMENT 'pilares impactados por el entrenamiento' AFTER fecha;
