<?php
/**
 * Entrenamientos · DPO
 * Un líder (persona habilitada por el administrador) registra a quién entrenó con una foto o video.
 * El cumplimiento de un líder es: días con al menos un registro / días laborables transcurridos.
 * Requiere core/config.php cargado ($pdo, getOperacionActiva()).
 */

/** Cargos que ven el calendario, eligen líderes y ajustan registros. */
const DPO_ROLES_ADMIN = ['super_admin', 'admin', 'supervisor'];

/** Límites de evidencia (el navegador comprime antes de subir; esto es la última barrera). */
const DPO_MAX_IMAGEN = 3 * 1024 * 1024;
const DPO_MAX_MINIATURA = 400 * 1024;
const DPO_MAX_VIDEO = 40 * 1024 * 1024;
const DPO_MIME = [
    'image/jpeg' => ['imagen', 'jpg'],
    'image/webp' => ['imagen', 'webp'],
    'image/png'  => ['imagen', 'png'],
    'video/mp4'  => ['video', 'mp4'],
    'video/webm' => ['video', 'webm'],
    'video/quicktime' => ['video', 'mov'],
];

function dpoEsAdmin(): bool {
    return in_array($_SESSION['cargo'] ?? '', DPO_ROLES_ADMIN, true);
}

function dpoEsLider(PDO $pdo, int $op, int $usuarioId): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM dpo_lideres WHERE operacion_id = ? AND usuario_id = ? AND activo = 1");
    $stmt->execute([$op, $usuarioId]);
    return (bool)$stmt->fetchColumn();
}

/** Festivos de Colombia (Ley 51 de 1983 "Ley Emiliani" + festivos según Pascua). Devuelve ['Y-m-d' => nombre]. */
function dpoFestivosColombia(int $anio): array {
    static $cache = [];
    if (isset($cache[$anio])) return $cache[$anio];

    $lunesSiguiente = function (DateTime $d): DateTime {
        $n = (int)$d->format('N');
        if ($n !== 1) $d->modify('+' . (8 - $n) . ' days');
        return $d;
    };
    $fecha = fn(int $m, int $dia) => new DateTime(sprintf('%04d-%02d-%02d', $anio, $m, $dia));
    $pascua = (new DateTime("$anio-03-21"))->modify('+' . easter_days($anio) . ' days');
    $desdePascua = fn(int $dias) => (clone $pascua)->modify(($dias >= 0 ? '+' : '') . $dias . ' days');

    $f = [];
    $agregar = function (DateTime $d, string $nombre) use (&$f) { $f[$d->format('Y-m-d')] = $nombre; };

    // Fijos
    $agregar($fecha(1, 1), 'Año Nuevo');
    $agregar($fecha(5, 1), 'Día del Trabajo');
    $agregar($fecha(7, 20), 'Independencia');
    $agregar($fecha(8, 7), 'Batalla de Boyacá');
    $agregar($fecha(12, 8), 'Inmaculada Concepción');
    $agregar($fecha(12, 25), 'Navidad');
    // Trasladables al lunes
    $agregar($lunesSiguiente($fecha(1, 6)), 'Reyes Magos');
    $agregar($lunesSiguiente($fecha(3, 19)), 'San José');
    $agregar($lunesSiguiente($fecha(6, 29)), 'San Pedro y San Pablo');
    $agregar($lunesSiguiente($fecha(8, 15)), 'Asunción de la Virgen');
    $agregar($lunesSiguiente($fecha(10, 12)), 'Día de la Raza');
    $agregar($lunesSiguiente($fecha(11, 1)), 'Todos los Santos');
    $agregar($lunesSiguiente($fecha(11, 11)), 'Independencia de Cartagena');
    // Según Pascua
    $agregar($desdePascua(-3), 'Jueves Santo');
    $agregar($desdePascua(-2), 'Viernes Santo');
    $agregar($lunesSiguiente($desdePascua(39)), 'Ascensión del Señor');
    $agregar($lunesSiguiente($desdePascua(60)), 'Corpus Christi');
    $agregar($lunesSiguiente($desdePascua(68)), 'Sagrado Corazón');

    return $cache[$anio] = $f;
}

/**
 * Matriz del mes para el calendario: líderes × días con su cumplimiento.
 * Hace 4 consultas en total (líderes, registros agregados, días y celdas bloqueadas), sin importar
 * cuántos líderes o días haya.
 */
function dpoMatrizMes(PDO $pdo, int $op, int $anio, int $mes): array {
    $diasMes = (int)cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
    $desde = sprintf('%04d-%02d-01', $anio, $mes);
    $hasta = sprintf('%04d-%02d-%02d', $anio, $mes, $diasMes);
    $hoy = date('Y-m-d');
    $festivos = dpoFestivosColombia($anio);

    $stmt = $pdo->prepare("SELECT l.usuario_id AS id, u.nombre, u.cargo
                           FROM dpo_lideres l JOIN usuarios u ON u.id = l.usuario_id
                           WHERE l.operacion_id = ? AND l.activo = 1
                           ORDER BY u.nombre");
    $stmt->execute([$op]);
    $lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT lider_id, DAY(fecha) AS dia, COUNT(*) AS n
                           FROM dpo_registros WHERE operacion_id = ? AND fecha BETWEEN ? AND ?
                           GROUP BY lider_id, fecha");
    $stmt->execute([$op, $desde, $hasta]);
    $conteos = [];
    foreach ($stmt as $r) $conteos[(int)$r['lider_id']][(int)$r['dia']] = (int)$r['n'];

    $stmt = $pdo->prepare("SELECT DAY(fecha) FROM dpo_dias_bloqueados WHERE operacion_id = ? AND fecha BETWEEN ? AND ?");
    $stmt->execute([$op, $desde, $hasta]);
    $diasBloqueados = array_flip(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));

    $stmt = $pdo->prepare("SELECT lider_id, DAY(fecha) AS dia FROM dpo_celdas_bloqueadas WHERE operacion_id = ? AND fecha BETWEEN ? AND ?");
    $stmt->execute([$op, $desde, $hasta]);
    $celdasBloqueadas = [];
    foreach ($stmt as $r) $celdasBloqueadas[(int)$r['lider_id']][(int)$r['dia']] = true;

    // Encabezado de días
    $dias = [];
    for ($d = 1; $d <= $diasMes; $d++) {
        $f = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
        $n = (int)date('N', strtotime($f));
        $dias[$d] = [
            'fecha' => $f,
            'dow' => $n,
            'domingo' => $n === 7,
            'festivo' => $festivos[$f] ?? null,
            'bloqueado' => isset($diasBloqueados[$d]),
            'futuro' => $f > $hoy,
            'hoy' => $f === $hoy,
            'fin_semana' => $n === 7 || $d === $diasMes,
        ];
        $dias[$d]['inactivo'] = $dias[$d]['domingo'] || $dias[$d]['festivo'] !== null || $dias[$d]['bloqueado'];
    }

    $totDia = array_fill(1, $diasMes, ['ok' => 0, 'val' => 0]);
    $totSemana = [];
    $global = ['ok' => 0, 'val' => 0];

    foreach ($lideres as &$l) {
        $id = (int)$l['id'];
        $l['celdas'] = [];
        $l['semanas'] = [];
        $ok = $val = $wOk = $wVal = 0;
        for ($d = 1; $d <= $diasMes; $d++) {
            $info = $dias[$d];
            $n = $conteos[$id][$d] ?? 0;
            if ($info['inactivo']) {
                $estado = 'off';
            } elseif (isset($celdasBloqueadas[$id][$d])) {
                $estado = 'na';
            } elseif ($info['futuro']) {
                $estado = 'futuro';
            } else {
                $estado = $n > 0 ? 'cumple' : 'no_cumple';
                $val++; $wVal++; $totDia[$d]['val']++;
                if ($n > 0) { $ok++; $wOk++; $totDia[$d]['ok']++; }
            }
            $l['celdas'][$d] = ['estado' => $estado, 'n' => $n];

            if ($info['fin_semana']) {
                $l['semanas'][$d] = ['ok' => $wOk, 'val' => $wVal, 'pct' => $wVal ? (int)round($wOk * 100 / $wVal) : null];
                $totSemana[$d]['ok'] = ($totSemana[$d]['ok'] ?? 0) + $wOk;
                $totSemana[$d]['val'] = ($totSemana[$d]['val'] ?? 0) + $wVal;
                $wOk = $wVal = 0;
            }
        }
        $l['ok'] = $ok;
        $l['val'] = $val;
        $l['pct'] = $val ? (int)round($ok * 100 / $val) : null;
        $global['ok'] += $ok;
        $global['val'] += $val;
    }
    unset($l);

    return [
        'anio' => $anio, 'mes' => $mes, 'dias_mes' => $diasMes,
        'dias' => $dias, 'lideres' => $lideres,
        'tot_dia' => $totDia, 'tot_semana' => $totSemana,
        'global' => $global + ['pct' => $global['val'] ? (int)round($global['ok'] * 100 / $global['val']) : null],
    ];
}

/** Clase de color según porcentaje (paleta Ware-Pro + verde/rojo de estado). */
function dpoClasePct(?int $pct): string {
    if ($pct === null) return 'p-na';
    if ($pct >= 100) return 'p-ok';
    if ($pct >= 75) return 'p-mid';
    return 'p-bad';
}

/** Borra los archivos físicos de un registro (rutas relativas a la raíz del proyecto). */
function dpoBorrarArchivos(array $registro): void {
    $raiz = dirname(__DIR__);
    foreach (['archivo', 'miniatura'] as $campo) {
        $rel = $registro[$campo] ?? '';
        if ($rel === '' || strpos($rel, 'uploads/dpo/') !== 0 || strpos($rel, '..') !== false) continue;
        $abs = $raiz . '/' . $rel;
        if (is_file($abs)) @unlink($abs);
    }
}
