<?php
/**
 * Catálogo de metas del sistema y utilidades para evaluarlas.
 *
 * Las metas "de sistema" son las que buscan los módulos de productividad por
 * nombre exacto (columna metas.actividad). Se siembran automáticamente en cada
 * operación con valores por defecto; cada CD ajusta meta y disparador en
 * modules/reportes/metas.php.
 *
 * sentido = 'mayor' -> cumple si valor >= meta; disparador si valor <= disparador
 * sentido = 'menor' -> cumple si valor <= meta; disparador si valor >= disparador
 */

const METAS_CATALOGO = [
    // actividad (nombre exacto que usa el código), modulo, unidad, sentido, meta por defecto, disparador por defecto, descripción
    ['Reempaque',          'Reempaque',    'cj/h', 'mayor', 20,  null, 'Cajas reempacadas por hora (reempaque.php, workstation reempaque)'],
    ['Clasificación',      'Reempaque',    'cj/h', 'mayor', 18,  null, 'Cajas clasificadas por hora (reempaque.php, workstation clasificación)'],
    ['Lavado',             'Reempaque',    'cj/h', 'mayor', 17,  null, 'Cajas lavadas por hora (reempaque.php, workstation lavado)'],
    ['Vertimiento NR',     'Vertimiento',  'cj/h', 'mayor', 32,  null, 'Vertimiento No Retornable / lata (vertimiento.php)'],
    ['Vertimiento R',      'Vertimiento',  'cj/h', 'mayor', 35,  null, 'Vertimiento Retornable (vertimiento.php)'],
    ['Vertimiento PET',    'Vertimiento',  'cj/h', 'mayor', 28,  null, 'Vertimiento PET-TW (vertimiento.php)'],
    ['Revisión NR',        'Revisión',     'cj/h', 'mayor', 0,   null, 'Revisión No Retornable (revision.php)'],
    ['Revisión',           'Revisión',     'cj/h', 'mayor', 0,   null, 'Revisión Retornable y PET-TW (revision.php)'],
    ['Revisión de envase', 'Revisión',     'cj/h', 'mayor', 0,   null, 'Revisión de envase (revision.php)'],
    ['Sorting',            'Sorting',      'cj/h', 'mayor', 380, null, 'Cajas por hora en sorting (workstation sorting)'],
    ['Recargue T2',        'Recargue',     'min',  'menor', 45,  null, 'Tiempo promedio de recargue en bahía (workstation recargue)'],
    ['Tiempo T1',          'Montacarga',   'min',  'menor', 60,  null, 'Tiempo de atención T1 (compensación OPM)'],
    ['Tiempo T2',          'Montacarga',   'min',  'menor', 50,  null, 'Tiempo de atención T2 (compensación OPM)'],
    ['Tiempo T4',          'Montacarga',   'min',  'menor', 45,  null, 'Tiempo de atención T4 (compensación OPM)'],
];

/** Metas del catálogo que se configuraron con un valor real por defecto (no 0). */
function metaCatalogoTieneValorInicial(array $item): bool {
    return (float)$item[4] > 0;
}

/**
 * Crea en la operación las metas de sistema que falten y marca como sistema las existentes.
 * Es barato (una consulta) cuando ya están todas creadas.
 */
function asegurarMetasBase(PDO $pdo, $operacionId): void {
    $operacionId = (int)$operacionId;
    if ($operacionId <= 0) return;

    $stmt = $pdo->prepare("SELECT actividad FROM metas WHERE operacion_id = ? AND es_sistema = 1");
    $stmt->execute([$operacionId]);
    $existentes = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
    if (count($existentes) >= count(METAS_CATALOGO)) return;

    $ins = $pdo->prepare("INSERT INTO metas (operacion_id, actividad, meta_minima, disparador, unidad_medida, sentido, modulo, descripcion, es_sistema, configurada, activo)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 1)
                          ON DUPLICATE KEY UPDATE sentido = IF(es_sistema = 1, sentido, VALUES(sentido)), es_sistema = 1, activo = 1,
                                                  modulo = COALESCE(modulo, VALUES(modulo)), descripcion = COALESCE(descripcion, VALUES(descripcion))");
    foreach (METAS_CATALOGO as $item) {
        if (isset($existentes[$item[0]])) continue;
        $ins->execute([$operacionId, $item[0], $item[4], $item[5], $item[2], $item[3], $item[1], $item[6], metaCatalogoTieneValorInicial($item) ? 1 : 0]);
    }
}

/** Devuelve la meta activa de una actividad en la operación (o null). Siembra el catálogo si hace falta. */
function obtenerMeta(PDO $pdo, $operacionId, string $actividad): ?array {
    asegurarMetasBase($pdo, $operacionId);
    $stmt = $pdo->prepare("SELECT * FROM metas WHERE operacion_id = ? AND actividad = ? AND activo = 1 LIMIT 1");
    $stmt->execute([(int)$operacionId, $actividad]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/** Devuelve [actividad => fila] de varias metas de la operación. */
function obtenerMetas(PDO $pdo, $operacionId, array $actividades): array {
    asegurarMetasBase($pdo, $operacionId);
    if (!$actividades) return [];
    $in = implode(',', array_fill(0, count($actividades), '?'));
    $stmt = $pdo->prepare("SELECT * FROM metas WHERE operacion_id = ? AND activo = 1 AND actividad IN ($in)");
    $stmt->execute(array_merge([(int)$operacionId], array_values($actividades)));
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $out[$row['actividad']] = $row;
    return $out;
}

/**
 * Evalúa un valor contra meta/disparador.
 * Devuelve 'disparador', 'cumple' o 'no_cumple'. Si no hay meta, devuelve 'cumple'
 * (mismo comportamiento que tenía el sistema antes de existir metas).
 */
function evaluarMeta($valor, $meta, $disparador = null, string $sentido = 'mayor'): string {
    $valor = (float)$valor;
    if ($disparador !== null && $disparador !== '') {
        $d = (float)$disparador;
        if ($sentido === 'menor' ? $valor >= $d : $valor <= $d) return 'disparador';
    }
    if ($meta === null || $meta === '') return 'cumple';
    $m = (float)$meta;
    return ($sentido === 'menor' ? $valor <= $m : $valor >= $m) ? 'cumple' : 'no_cumple';
}

/** Atajo: evalúa contra una fila de la tabla metas. */
function evaluarContraMeta($valor, ?array $metaRow): string {
    if (!$metaRow) return 'cumple';
    return evaluarMeta($valor, $metaRow['meta_minima'], $metaRow['disparador'], $metaRow['sentido'] ?? 'mayor');
}

/** Roles que pueden configurar metas, indicadores de turno y compensación. */
const ROLES_GESTION_METAS = ['super_admin', 'admin', 'lider', 'supervisor'];
const ROLES_GESTION_COMPENSACION = ['super_admin', 'admin'];

function puedeGestionarMetas(): bool {
    return in_array($_SESSION['cargo'] ?? '', ROLES_GESTION_METAS, true);
}
