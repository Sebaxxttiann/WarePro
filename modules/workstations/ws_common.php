<?php
/**
 * Utilidades compartidas por el hub (index.php) y los tableros workstation_*.php.
 * Requiere que core/config.php ya esté cargado ($pdo, getOperacionActiva()).
 */
require_once __DIR__ . '/../../core/metas.php';

/** Operación activa del usuario como entero (0 si no hay). */
function wsOperacionId(): int {
    return (int)(getOperacionActiva() ?? 0);
}

/** Nombre de la operación (sin escapar). */
function wsOperacionNombre(PDO $pdo, int $op): string {
    static $cache = [];
    if (isset($cache[$op])) return $cache[$op];
    $nombre = false;
    if ($op > 0) {
        $stmt = $pdo->prepare("SELECT nombre FROM operaciones WHERE id = ?");
        $stmt->execute([$op]);
        $nombre = $stmt->fetchColumn();
    }
    return $cache[$op] = ($nombre !== false && $nombre !== null && $nombre !== '') ? (string)$nombre : 'Sin operación';
}

/** Escapa para HTML tolerando bytes no UTF-8. */
function wsE($texto): string {
    return htmlspecialchars((string)$texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** "CD NOMBRE" en mayúsculas y escapado, para títulos de los tableros. */
function wsTituloCD(PDO $pdo, int $op): string {
    $nombre = wsOperacionNombre($pdo, $op);
    $upper = function_exists('mb_strtoupper') ? mb_strtoupper($nombre, 'UTF-8') : strtoupper($nombre);
    return wsE('CD ' . $upper);
}

/** "CD Nombre" escapado, para textos normales. */
function wsNombreCD(PDO $pdo, int $op): string {
    return wsE('CD ' . wsOperacionNombre($pdo, $op));
}

/**
 * Meta de una actividad para la operación (tabla metas, vía obtenerMeta()).
 * $factor convierte la meta (p. ej. cajas/hora) a la unidad que registra el tablero
 * (p. ej. unidades/hora = cajas × 24). Si la operación no tiene la meta (o es <= 0)
 * se usa $default, que es el valor que el tablero tenía fijo antes.
 */
function wsMeta(PDO $pdo, int $op, string $actividad, float $default, float $factor = 1.0): array {
    $row = null;
    if ($op > 0) {
        try { $row = obtenerMeta($pdo, $op, $actividad); } catch (Throwable $e) { $row = null; }
    }
    $configurada = $row && (float)$row['meta_minima'] > 0;
    $meta = $configurada ? (float)$row['meta_minima'] : $default;
    $disp = ($row && $row['disparador'] !== null && $row['disparador'] !== '') ? (float)$row['disparador'] : null;
    return [
        'actividad'         => $actividad,
        'meta'              => $meta + 0,
        'disparador'        => $disp,
        'unidad'            => ($row['unidad_medida'] ?? '') ?: '',
        'sentido'           => ($row['sentido'] ?? '') === 'menor' ? 'menor' : 'mayor',
        'factor'            => $factor,
        'umbral'            => (int)round($meta * $factor),
        'umbral_disparador' => $disp !== null ? (int)round($disp * $factor) : null,
        'desde_tabla'       => (bool)$configurada,
    ];
}

/** Botón discreto para volver al hub (va dentro del header del tablero). */
function wsBotonVolver(): string {
    return '<style>.ws-back{position:absolute;left:8px;top:4px;font-size:11px;font-weight:600;color:#bbb;text-decoration:none;'
         . 'padding:2px 8px;border:1px solid #444;border-radius:6px;opacity:.45;transition:opacity .2s;z-index:1001;letter-spacing:0;line-height:1.4;}'
         . '.ws-back:hover,.ws-back:focus{opacity:1;color:#ffcc00;border-color:#ffcc00;}'
         . '.header-container{position:relative;}</style>'
         . '<a href="index.php" class="ws-back" title="Volver a Mis Workstations">&larr; Workstations</a>';
}

/** Catálogo de workstations del hub. */
function wsCatalogo(): array {
    return [
        'reempaque'     => ['nombre' => 'Reempaque',     'archivo' => 'workstation_reempaque.php',     'icono' => 'fa-box-open',      'desc' => 'Unidades reempacadas por hora, tendencia del mes y ranking.'],
        'clasificacion' => ['nombre' => 'Clasificación', 'archivo' => 'workstation_clasificacion.php', 'icono' => 'fa-layer-group',   'desc' => 'Unidades clasificadas por hora, tendencia del mes y ranking.'],
        'lavado'        => ['nombre' => 'Lavado',        'archivo' => 'workstation_lavado.php',        'icono' => 'fa-soap',          'desc' => 'Unidades lavadas por hora, tendencia del mes y ranking.'],
        'vertimiento'   => ['nombre' => 'Vertimiento',   'archivo' => 'workstation_vertimiento.php',   'icono' => 'fa-tint',          'desc' => 'Unidades vertidas por hora (lata, PET, retornable) y ranking.'],
        'sorting'       => ['nombre' => 'Sorting',       'archivo' => 'workstation_sorting.php',       'icono' => 'fa-tasks',         'desc' => 'Cajas ingresadas a sorting por hora y tendencia del mes.'],
        'recargue'      => ['nombre' => 'Recargue',      'archivo' => 'workstation_recargue.php',      'icono' => 'fa-truck-loading', 'desc' => 'Tiempo promedio de recargue en bahía (T2) y vehículos atendidos.'],
        'cargue'        => ['nombre' => 'Cargue',        'archivo' => 'workstation_cargue.php',        'icono' => 'fa-truck',         'desc' => 'Vehículos cargados vs. planeados en el día y por franja.'],
        'descargue'     => ['nombre' => 'Descargue',     'archivo' => 'workstation_descargue.php',     'icono' => 'fa-dolly',         'desc' => 'Vehículos descargados vs. ingresados por portería.'],
    ];
}

/** Formato numérico es-CO. Sin $dec: entero si no tiene decimales, si no 1 decimal. */
function wsNum($n, ?int $dec = null): string {
    $n = (float)$n;
    if ($dec === null) $dec = (abs($n - round($n)) < 0.005) ? 0 : 1;
    return number_format($n, $dec, ',', '.');
}

/**
 * Resumen liviano de HOY para una workstation (usado por el hub).
 * Devuelve: meta_txt, total_txt, detalle, estado ('cumple'|'no_cumple'|'sin_datos'|'sin_meta').
 */
function wsResumenHoy(PDO $pdo, int $op, string $key): array {
    $out = ['meta_txt' => 'Sin meta', 'total_txt' => '0', 'detalle' => 'Sin registros hoy', 'estado' => 'sin_datos'];

    $unidadesActividad = [
        'reempaque'     => ['ows_reempaque',   'REEMPAQUE',     'Reempaque',     20, 24, 'reempacadas'],
        'clasificacion' => ['ows_reempaque',   'CLASIFICACION', 'Clasificación', 18, 24, 'clasificadas'],
        'lavado'        => ['ows_reempaque',   'LAVADO',        'Lavado',        17, 24, 'lavadas'],
    ];

    if (isset($unidadesActividad[$key])) {
        [$tabla, $act, $metaNombre, $def, $factor] = $unidadesActividad[$key];
        $m = wsMeta($pdo, $op, $metaNombre, $def, $factor);
        $out['meta_txt'] = wsNum($m['meta']) . ' cj/h · ' . wsNum($m['umbral']) . ' unid/h';
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(unidades),0) AS total, COUNT(DISTINCT HOUR(hora)) AS horas
                               FROM $tabla WHERE operacion_id = ? AND actividad = ? AND fecha = CURDATE()");
        $stmt->execute([$op, $act]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = (float)$r['total'];
        $horas = (int)$r['horas'];
        $out['total_txt'] = wsNum($total) . ' unid';
        if ($horas > 0) {
            $prom = $total / $horas;
            $out['detalle'] = 'Promedio ' . wsNum($prom, 0) . ' unid/h en ' . $horas . ' h';
            $out['estado'] = $prom >= $m['umbral'] ? 'cumple' : 'no_cumple';
        }
        return $out;
    }

    switch ($key) {
        case 'vertimiento':
            $nr  = wsMeta($pdo, $op, 'Vertimiento NR', 32, 24);
            $pet = wsMeta($pdo, $op, 'Vertimiento PET', 28, 24);
            $ret = wsMeta($pdo, $op, 'Vertimiento R', 35, 19);
            $umbral = min($nr['umbral'], $pet['umbral'], $ret['umbral']);
            $out['meta_txt'] = 'Lata ' . wsNum($nr['meta']) . ' · PET ' . wsNum($pet['meta']) . ' · RET ' . wsNum($ret['meta']) . ' cj/h';
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(unidades),0) AS total, COUNT(DISTINCT HOUR(hora)) AS horas
                                   FROM ows_vertimiento WHERE operacion_id = ? AND actividad = 'VERTIMIENTO' AND fecha = CURDATE()");
            $stmt->execute([$op]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $out['total_txt'] = wsNum($r['total']) . ' unid';
            if ((int)$r['horas'] > 0) {
                $prom = (float)$r['total'] / (int)$r['horas'];
                $out['detalle'] = 'Promedio ' . wsNum($prom) . ' unid/h (mín. ' . wsNum($umbral) . ')';
                $out['estado'] = $prom >= $umbral ? 'cumple' : 'no_cumple';
            }
            return $out;

        case 'sorting':
            $m = wsMeta($pdo, $op, 'Sorting', 380, 1);
            $out['meta_txt'] = wsNum($m['meta']) . ' cj/h';
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(cajas_sorting),0) AS total, COUNT(DISTINCT HOUR(hora_sorting)) AS horas
                                   FROM sortiing WHERE operacion_id = ? AND fecha_sorting = CURDATE() AND cajas_sorting IS NOT NULL");
            $stmt->execute([$op]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $out['total_txt'] = wsNum($r['total']) . ' cajas';
            if ((int)$r['horas'] > 0) {
                $prom = (float)$r['total'] / (int)$r['horas'];
                $out['detalle'] = 'Promedio ' . wsNum($prom) . ' cj/h en ' . (int)$r['horas'] . ' h';
                $out['estado'] = $prom >= $m['umbral'] ? 'cumple' : 'no_cumple';
            }
            return $out;

        case 'recargue':
            $m = wsMeta($pdo, $op, 'Recargue T2', 45, 1);
            $out['meta_txt'] = '≤ ' . wsNum($m['meta']) . ' min por vehículo';
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total, AVG(TIME_TO_SEC(tiempo)/60) AS prom
                                   FROM recargue_t2 WHERE operacion_id = ? AND fecha = CURDATE() AND estatus = 'Completado'");
            $stmt->execute([$op]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $out['total_txt'] = wsNum($r['total']) . ' vehículos';
            if ((int)$r['total'] > 0) {
                $prom = (float)$r['prom'];
                $out['detalle'] = 'Tiempo promedio ' . wsNum($prom, 1) . ' min';
                $out['estado'] = $prom <= $m['meta'] ? 'cumple' : 'no_cumple';
            }
            return $out;

        case 'cargue':
            $out['meta_txt'] = 'Planeados del día';
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(vehiculos_cargados),0) FROM ows_cargue WHERE operacion_id = ? AND fecha = CURDATE()");
            $stmt->execute([$op]);
            $cargados = (int)$stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT vehiculos_planeados FROM ows_cargue WHERE operacion_id = ? AND fecha = CURDATE() AND vehiculos_planeados > 0 ORDER BY id ASC LIMIT 1");
            $stmt->execute([$op]);
            $planeados = (int)($stmt->fetchColumn() ?: 0);
            $out['total_txt'] = wsNum($cargados) . ' / ' . wsNum($planeados) . ' vehículos';
            if ($cargados > 0 || $planeados > 0) {
                $out['detalle'] = $planeados > 0 ? ('Avance ' . wsNum($cargados * 100 / $planeados) . '% del plan') : 'Sin plan registrado';
                $out['estado'] = $planeados > 0 ? ($cargados >= $planeados ? 'cumple' : 'no_cumple') : 'sin_meta';
            }
            return $out;

        case 'descargue':
            $out['meta_txt'] = 'Ingresados por portería';
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM descargue WHERE operacion_id = ? AND estado = 'finalizado' AND fecha_hora_fin >= CURDATE() AND fecha_hora_fin < CURDATE() + INTERVAL 1 DAY");
            $stmt->execute([$op]);
            $finalizados = (int)$stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sortiing WHERE operacion_id = ? AND fecha_ingreso = CURDATE()");
            $stmt->execute([$op]);
            $ingresados = (int)$stmt->fetchColumn();
            $out['total_txt'] = wsNum($finalizados) . ' / ' . wsNum($ingresados) . ' vehículos';
            if ($finalizados > 0 || $ingresados > 0) {
                $out['detalle'] = 'Descargados vs. ingresados hoy';
                $out['estado'] = $ingresados > 0 ? ($finalizados >= $ingresados ? 'cumple' : 'no_cumple') : 'sin_meta';
            }
            return $out;
    }
    return $out;
}
