-- Módulo Entrenamientos · DPO
-- Reemplaza el sistema DPO externo usando los usuarios de Ware-Pro.
--   dpo_lideres            personas habilitadas para registrar entrenamientos (las elige el administrador)
--   dpo_registros          cada entrenamiento con su evidencia (foto o video comprimido + miniatura)
--   dpo_dias_bloqueados    días sin operación para toda la operación (no cuentan en el cumplimiento)
--   dpo_celdas_bloqueadas  excepciones de un líder en un día (vacaciones, incapacidad...)

CREATE TABLE IF NOT EXISTS dpo_lideres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_dpo_lider (operacion_id, usuario_id),
    KEY idx_dpo_lider_usuario (usuario_id),
    CONSTRAINT fk_dpo_lider_operacion FOREIGN KEY (operacion_id) REFERENCES operaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_dpo_lider_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS dpo_registros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operacion_id INT NOT NULL,
    lider_id INT NOT NULL COMMENT 'usuarios.id de quien hizo el entrenamiento (sesión)',
    colaborador_id INT NOT NULL COMMENT 'usuarios.id de quien recibió el entrenamiento',
    fecha DATE NOT NULL,
    tipo ENUM('imagen','video') NOT NULL,
    archivo VARCHAR(255) NOT NULL COMMENT 'ruta relativa a la raíz del proyecto',
    miniatura VARCHAR(255) NULL COMMENT 'JPG pequeño para listados y calendario',
    peso_bytes INT UNSIGNED NOT NULL DEFAULT 0,
    duracion_seg SMALLINT UNSIGNED NULL,
    observacion VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_dpo_reg_op_fecha (operacion_id, fecha),
    KEY idx_dpo_reg_lider_fecha (lider_id, fecha),
    KEY idx_dpo_reg_colaborador (colaborador_id),
    CONSTRAINT fk_dpo_reg_operacion FOREIGN KEY (operacion_id) REFERENCES operaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_dpo_reg_lider FOREIGN KEY (lider_id) REFERENCES usuarios(id),
    CONSTRAINT fk_dpo_reg_colaborador FOREIGN KEY (colaborador_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS dpo_dias_bloqueados (
    operacion_id INT NOT NULL,
    fecha DATE NOT NULL,
    PRIMARY KEY (operacion_id, fecha),
    CONSTRAINT fk_dpo_dia_operacion FOREIGN KEY (operacion_id) REFERENCES operaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS dpo_celdas_bloqueadas (
    operacion_id INT NOT NULL,
    lider_id INT NOT NULL COMMENT 'usuarios.id',
    fecha DATE NOT NULL,
    PRIMARY KEY (operacion_id, lider_id, fecha),
    CONSTRAINT fk_dpo_celda_operacion FOREIGN KEY (operacion_id) REFERENCES operaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_dpo_celda_usuario FOREIGN KEY (lider_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
