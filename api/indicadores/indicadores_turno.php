<?php
/**
 * API de indicadores diarios por turno (A/B/C).
 *
 * Body JSON con 'action':
 *   get_tablero      {turno, fecha}                 -> indicadores del turno + valores del día + KPIs de los 3 turnos
 *   get_mes          {turno, year, month}           -> heatmap indicador x día + cumplimiento por persona
 *   save_valor       {indicador_id, fecha, valor, responsables[], observacion}
 *   save_indicador   {id?, turno, nombre, unidad, sentido, meta_valor, disparador_valor, aplica_compensacion}
 *   toggle_indicador {id}
 *   delete_indicador {id}                           -> solo indicadores propios (sin meta_id)
 *   update_order     {turno, ids[]}
 *   resumen_persona  {usuario_id, year, month}
 *
 * Lectura: cualquier usuario logueado. Escritura: puedeGestionarMetas().
 * Respuesta: {success: bool, message?, ...}
 */
require_once '../../core/config.php';
require_once '../../core/metas.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'], $_SESSION['cargo'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada. Vuelve a iniciar sesión.']);
    exit;
}

date_default_timezone_set('America/Bogota');

const TURNOS_VALIDOS = ['A', 'B', 'C'];

function responder(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fallar(string $msg, int $code = 400): void {
    responder(['success' => false, 'message' => $msg], $code);
}

function exigirGestion(): void {
    if (!puedeGestionarMetas()) fallar('No tienes permisos para modificar indicadores.', 403);
}

function validarTurno($t): string {
    $t = strtoupper(trim((string)$t));
    if (!in_array($t, TURNOS_VALIDOS, true)) fallar('Turno inválido.');
    return $t;
}

function validarFecha($f): string {
    $f = (string)$f;
    $d = DateTime::createFromFormat('Y-m-d', $f);
    if (!$d || $d->format('Y-m-d') !== $f) fallar('Fecha inválida.');
    return $f;
}

function numOrNull($v) {
    if ($v === null || $v === '') return null;
    if (!is_numeric($v)) fallar('Valor numérico inválido: ' . $v);
    return round((float)$v, 2);
}

/** Crea en cada turno los indicadores ligados a las metas activas de la operación que falten. */
function asegurarIndicadoresBase(PDO $pdo, int $op): void {
    asegurarMetasBase($pdo, $op);
    $sql = "INSERT IGNORE INTO ind_turno_indicadores (operacion_id, turno, nombre, meta_id, unidad, sentido, orden)
            SELECT m.operacion_id, ?, m.actividad, m.id, m.unidad_medida, m.sentido, m.id
            FROM metas m
            WHERE m.operacion_id = ? AND m.activo = 1
              AND NOT EXISTS (SELECT 1 FROM ind_turno_indicadores i WHERE i.operacion_id = m.operacion_id AND i.turno = ? AND i.meta_id = m.id)";
    $stmt = $pdo->prepare($sql);
    foreach (TURNOS_VALIDOS as $t) $stmt->execute([$t, $op, $t]);
}

/** SELECT base de indicadores con su meta efectiva. */
const SQL_INDICADORES = "SELECT i.*, m.actividad AS m_actividad, m.meta_minima AS m_meta, m.disparador AS m_disparador,
                                m.sentido AS m_sentido, m.unidad_medida AS m_unidad, m.configurada AS m_configurada,
                                m.descripcion AS m_descripcion, m.modulo AS m_modulo
                         FROM ind_turno_indicadores i
                         LEFT JOIN metas m ON m.id = i.meta_id
                         WHERE i.operacion_id = ? AND (i.meta_id IS NULL OR m.activo = 1)";

/** Normaliza una fila de indicador: meta/disparador/sentido efectivos. */
function normalizarIndicador(array $r): array {
    $sistema = $r['meta_id'] !== null;
    if ($sistema) {
        $meta = $r['m_meta'] !== null ? (float)$r['m_meta'] : null;
        $disp = $r['m_disparador'] !== null ? (float)$r['m_disparador'] : null;
        $sentido = $r['m_sentido'] ?: 'mayor';
        $unidad = $r['m_unidad'];
        $nombre = $r['m_actividad'] ?: $r['nombre'];
        $configurada = ((int)$r['m_configurada'] === 1) || ($meta !== null && $meta > 0);
    } else {
        $meta = $r['meta_valor'] !== null ? (float)$r['meta_valor'] : null;
        $disp = $r['disparador_valor'] !== null ? (float)$r['disparador_valor'] : null;
        $sentido = $r['sentido'] ?: 'mayor';
        $unidad = $r['unidad'];
        $nombre = $r['nombre'];
        $configurada = $meta !== null;
    }
    return [
        'id'                  => (int)$r['id'],
        'turno'               => $r['turno'],
        'nombre'              => $nombre,
        'unidad'              => $unidad,
        'sentido'             => $sentido,
        'meta'                => $meta,
        'disparador'          => $disp,
        'configurada'         => $configurada,
        'es_sistema'          => $sistema,
        'meta_id'             => $sistema ? (int)$r['meta_id'] : null,
        'modulo'              => $r['m_modulo'] ?? null,
        'descripcion'         => $r['m_descripcion'] ?? null,
        'aplica_compensacion' => (int)$r['aplica_compensacion'] === 1,
        'activo'              => (int)$r['activo'] === 1,
        'orden'               => (int)$r['orden'],
    ];
}

function obtenerIndicador(PDO $pdo, int $op, int $id): array {
    $stmt = $pdo->prepare(SQL_INDICADORES . " AND i.id = ?");
    $stmt->execute([$op, $id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) fallar('Indicador no encontrado en esta operación.', 404);
    return normalizarIndicador($r);
}

function rangoMes($year, $month): array {
    $y = (int)$year; $m = (int)$month;
    if ($y < 2000 || $y > 2100 || $m < 1 || $m > 12) fallar('Mes inválido.');
    $ini = sprintf('%04d-%02d-01', $y, $m);
    $dias = (int)date('t', strtotime($ini));
    $fin = sprintf('%04d-%02d-%02d', $y, $m, $dias);
    return [$ini, $fin, $dias];
}

/** Responsables por valor_id: [valor_id => [{id,nombre,cargo}]] */
function responsablesDe(PDO $pdo, array $valorIds): array {
    if (!$valorIds) return [];
    $in = implode(',', array_fill(0, count($valorIds), '?'));
    $stmt = $pdo->prepare("SELECT r.valor_id, u.id, u.nombre, u.cargo FROM ind_turno_responsables r
                           JOIN usuarios u ON u.id = r.usuario_id WHERE r.valor_id IN ($in) ORDER BY u.nombre");
    $stmt->execute(array_values($valorIds));
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[(int)$r['valor_id']][] = ['id' => (int)$r['id'], 'nombre' => $r['nombre'], 'cargo' => $r['cargo']];
    }
    return $out;
}

$op = (int)getOperacionActiva();
if ($op <= 0) fallar('No hay una operación activa en la sesión.');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = $_POST ?: [];
$action = $data['action'] ?? '';
$puedeGestionar = puedeGestionarMetas();
$metasUrl = BASE_URL . '/modules/reportes/metas.php';

try {
    switch ($action) {

        case 'get_tablero': {
            $turno = validarTurno($data['turno'] ?? '');
            $fecha = validarFecha($data['fecha'] ?? '');
            asegurarIndicadoresBase($pdo, $op);

            $stmt = $pdo->prepare(SQL_INDICADORES . " ORDER BY i.turno, i.orden, i.id");
            $stmt->execute([$op]);
            $porTurno = ['A' => [], 'B' => [], 'C' => []];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $porTurno[$r['turno']][] = normalizarIndicador($r);
            }

            $stmt = $pdo->prepare("SELECT v.id, v.indicador_id, v.turno, v.valor, v.estado, v.meta_hist, v.disparador_hist, v.sentido_hist,
                                          v.observacion, v.fecha_registro, u.nombre AS registrado_por_nombre
                                   FROM ind_turno_valores v
                                   LEFT JOIN usuarios u ON u.id = v.registrado_por
                                   WHERE v.operacion_id = ? AND v.fecha = ?");
            $stmt->execute([$op, $fecha]);
            $valores = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $v) $valores[(int)$v['indicador_id']] = $v;
            $resp = responsablesDe($pdo, array_map(fn($v) => (int)$v['id'], array_values($valores)));

            // KPIs del día por turno (solo indicadores visibles)
            $kpis = [];
            foreach ($porTurno as $t => $lista) {
                $k = ['total' => 0, 'registrados' => 0, 'cumple' => 0, 'no_cumple' => 0, 'disparador' => 0, 'sin_registrar' => 0, 'sin_configurar' => 0];
                foreach ($lista as $ind) {
                    if (!$ind['activo']) continue;
                    $k['total']++;
                    if (!$ind['configurada']) $k['sin_configurar']++;
                    if (isset($valores[$ind['id']])) {
                        $k['registrados']++;
                        $k[$valores[$ind['id']]['estado']]++;
                    } elseif ($ind['configurada']) {
                        $k['sin_registrar']++;
                    }
                }
                $k['pct'] = $k['registrados'] > 0 ? round($k['cumple'] * 100 / $k['registrados']) : null;
                $kpis[$t] = $k;
            }

            $indicadores = [];
            foreach ($porTurno[$turno] as $ind) {
                $v = $valores[$ind['id']] ?? null;
                $ind['valor'] = $v ? [
                    'id'           => (int)$v['id'],
                    'valor'        => (float)$v['valor'],
                    'estado'       => $v['estado'],
                    'meta_hist'    => $v['meta_hist'] !== null ? (float)$v['meta_hist'] : null,
                    'disparador_hist' => $v['disparador_hist'] !== null ? (float)$v['disparador_hist'] : null,
                    'sentido_hist' => $v['sentido_hist'],
                    'observacion'  => $v['observacion'],
                    'registrado_por' => $v['registrado_por_nombre'],
                    'fecha_registro' => $v['fecha_registro'],
                    'responsables' => $resp[(int)$v['id']] ?? [],
                ] : null;
                $indicadores[] = $ind;
            }

            responder([
                'success'     => true,
                'turno'       => $turno,
                'fecha'       => $fecha,
                'puede_gestionar' => $puedeGestionar,
                'metas_url'   => $metasUrl,
                'indicadores' => $indicadores,
                'kpis'        => $kpis,
            ]);
        }

        case 'get_mes': {
            $turno = validarTurno($data['turno'] ?? '');
            [$ini, $fin, $dias] = rangoMes($data['year'] ?? 0, $data['month'] ?? 0);
            asegurarIndicadoresBase($pdo, $op);

            $stmt = $pdo->prepare("SELECT v.id, v.indicador_id, DAY(v.fecha) AS dia, v.valor, v.estado, v.meta_hist, v.disparador_hist
                                   FROM ind_turno_valores v
                                   WHERE v.operacion_id = ? AND v.turno = ? AND v.fecha BETWEEN ? AND ?");
            $stmt->execute([$op, $turno, $ini, $fin]);
            $vals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $conValores = [];
            $matriz = [];
            foreach ($vals as $v) {
                $iid = (int)$v['indicador_id'];
                $conValores[$iid] = true;
                $matriz[$iid][(int)$v['dia']] = [
                    'valor' => (float)$v['valor'], 'estado' => $v['estado'],
                    'meta' => $v['meta_hist'] !== null ? (float)$v['meta_hist'] : null,
                    'disparador' => $v['disparador_hist'] !== null ? (float)$v['disparador_hist'] : null,
                ];
            }

            $stmt = $pdo->prepare(SQL_INDICADORES . " AND i.turno = ? ORDER BY i.orden, i.id");
            $stmt->execute([$op, $turno]);
            $indicadores = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $ind = normalizarIndicador($r);
                if (!$ind['activo'] && empty($conValores[$ind['id']])) continue;
                $ind['dias'] = (object)($matriz[$ind['id']] ?? []);
                $indicadores[] = $ind;
            }

            // Cumplimiento por persona en el turno: un día cumple si todos sus indicadores con compensación cumplen
            $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.cargo, v.fecha, v.estado, i.nombre AS indicador, m.actividad AS m_actividad
                                   FROM ind_turno_valores v
                                   JOIN ind_turno_responsables r ON r.valor_id = v.id
                                   JOIN usuarios u ON u.id = r.usuario_id
                                   JOIN ind_turno_indicadores i ON i.id = v.indicador_id
                                   LEFT JOIN metas m ON m.id = i.meta_id
                                   WHERE v.operacion_id = ? AND v.turno = ? AND v.fecha BETWEEN ? AND ? AND i.aplica_compensacion = 1
                                   ORDER BY u.nombre, v.fecha");
            $stmt->execute([$op, $turno, $ini, $fin]);
            $personas = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $uid = (int)$r['id'];
                if (!isset($personas[$uid])) $personas[$uid] = ['id' => $uid, 'nombre' => $r['nombre'], 'cargo' => $r['cargo'], 'dias' => []];
                $f = $r['fecha'];
                if (!isset($personas[$uid]['dias'][$f])) $personas[$uid]['dias'][$f] = true;
                if ($r['estado'] !== 'cumple') $personas[$uid]['dias'][$f] = false;
            }
            $resumen = [];
            foreach ($personas as $p) {
                $ok = count(array_filter($p['dias']));
                $resumen[] = ['id' => $p['id'], 'nombre' => $p['nombre'], 'cargo' => $p['cargo'],
                              'cumplidos' => $ok, 'no_cumplidos' => count($p['dias']) - $ok,
                              'dias' => array_map(fn($x) => $x ? 1 : 0, $p['dias'])];
            }

            responder([
                'success' => true, 'turno' => $turno, 'inicio' => $ini, 'dias_mes' => $dias,
                'indicadores' => $indicadores, 'personas' => $resumen,
                'puede_gestionar' => $puedeGestionar,
            ]);
        }

        case 'save_valor': {
            exigirGestion();
            $id = (int)($data['indicador_id'] ?? 0);
            $fecha = validarFecha($data['fecha'] ?? '');
            if ($fecha > date('Y-m-d', strtotime('+1 day'))) fallar('No se pueden registrar resultados de fechas futuras.');
            $ind = obtenerIndicador($pdo, $op, $id);
            $valorRaw = $data['valor'] ?? '';
            $valorRaw = is_string($valorRaw) ? str_replace(',', '.', trim($valorRaw)) : $valorRaw;

            if ($valorRaw === '' || $valorRaw === null) {
                $stmt = $pdo->prepare("DELETE FROM ind_turno_valores WHERE indicador_id = ? AND fecha = ? AND operacion_id = ?");
                $stmt->execute([$id, $fecha, $op]);
                responder(['success' => true, 'estado' => null, 'borrado' => true]);
            }

            if (!is_numeric($valorRaw)) fallar('El resultado debe ser numérico.');
            $valor = round((float)$valorRaw, 2);
            if (!$ind['configurada']) fallar('Este indicador no tiene meta configurada. Configúrala antes de registrar resultados.');
            if (!$ind['activo']) fallar('El indicador está oculto. Muéstralo para registrar resultados.');

            $responsables = array_values(array_unique(array_filter(array_map('intval', (array)($data['responsables'] ?? [])), fn($x) => $x > 0)));
            if ($responsables) {
                $in = implode(',', array_fill(0, count($responsables), '?'));
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE operacion_id = ? AND id IN ($in)");
                $stmt->execute(array_merge([$op], $responsables));
                if ((int)$stmt->fetchColumn() !== count($responsables)) fallar('Algún responsable no pertenece a esta operación.');
            }
            $obs = trim((string)($data['observacion'] ?? ''));
            $obs = $obs === '' ? null : mb_substr(strip_tags($obs), 0, 255);

            $estado = evaluarMeta($valor, $ind['meta'], $ind['disparador'], $ind['sentido']);

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO ind_turno_valores (operacion_id, indicador_id, fecha, turno, valor, sentido_hist, meta_hist, disparador_hist, estado, observacion, registrado_por)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE valor = VALUES(valor), sentido_hist = VALUES(sentido_hist), meta_hist = VALUES(meta_hist),
                                       disparador_hist = VALUES(disparador_hist), estado = VALUES(estado), observacion = VALUES(observacion),
                                       registrado_por = VALUES(registrado_por), turno = VALUES(turno)");
            $stmt->execute([$op, $id, $fecha, $ind['turno'], $valor, $ind['sentido'], $ind['meta'], $ind['disparador'], $estado, $obs, (int)$_SESSION['usuario_id']]);
            $stmt = $pdo->prepare("SELECT id FROM ind_turno_valores WHERE indicador_id = ? AND fecha = ?");
            $stmt->execute([$id, $fecha]);
            $valorId = (int)$stmt->fetchColumn();
            $pdo->prepare("DELETE FROM ind_turno_responsables WHERE valor_id = ?")->execute([$valorId]);
            if ($responsables) {
                $ins = $pdo->prepare("INSERT INTO ind_turno_responsables (valor_id, usuario_id) VALUES (?, ?)");
                foreach ($responsables as $uid) $ins->execute([$valorId, $uid]);
            }
            $pdo->commit();

            responder(['success' => true, 'estado' => $estado, 'valor_id' => $valorId, 'valor' => $valor,
                       'meta' => $ind['meta'], 'disparador' => $ind['disparador'], 'sentido' => $ind['sentido'],
                       'responsables' => count($responsables)]);
        }

        case 'save_indicador': {
            exigirGestion();
            $id = (int)($data['id'] ?? 0);
            $aplica = !empty($data['aplica_compensacion']) ? 1 : 0;

            if ($id > 0) {
                $ind = obtenerIndicador($pdo, $op, $id);
                if ($ind['es_sistema']) {
                    // Los de sistema solo cambian si aplican a compensación; la meta se edita en metas.php
                    $pdo->prepare("UPDATE ind_turno_indicadores SET aplica_compensacion = ? WHERE id = ? AND operacion_id = ?")
                        ->execute([$aplica, $id, $op]);
                    responder(['success' => true, 'message' => 'Indicador actualizado. La meta y el disparador se editan en Metas.', 'metas_url' => $metasUrl]);
                }
            }

            $nombre = trim(strip_tags((string)($data['nombre'] ?? '')));
            if ($nombre === '' || mb_strlen($nombre) > 100) fallar('El nombre es obligatorio (máx. 100 caracteres).');
            $unidad = mb_substr(trim(strip_tags((string)($data['unidad'] ?? ''))), 0, 30);
            $unidad = $unidad === '' ? null : $unidad;
            $sentido = ($data['sentido'] ?? 'mayor') === 'menor' ? 'menor' : 'mayor';
            $meta = numOrNull($data['meta_valor'] ?? null);
            $disp = numOrNull($data['disparador_valor'] ?? null);
            if ($meta === null) fallar('La meta es obligatoria.');
            if ($disp !== null) {
                if ($sentido === 'mayor' && $disp >= $meta) fallar('Si más alto es mejor, el disparador debe ser menor que la meta.');
                if ($sentido === 'menor' && $disp <= $meta) fallar('Si más bajo es mejor, el disparador debe ser mayor que la meta.');
            }

            if ($id > 0) {
                $pdo->prepare("UPDATE ind_turno_indicadores SET nombre = ?, unidad = ?, sentido = ?, meta_valor = ?, disparador_valor = ?, aplica_compensacion = ?
                               WHERE id = ? AND operacion_id = ? AND meta_id IS NULL")
                    ->execute([$nombre, $unidad, $sentido, $meta, $disp, $aplica, $id, $op]);
                responder(['success' => true, 'id' => $id, 'message' => 'Indicador actualizado.']);
            }

            $turno = validarTurno($data['turno'] ?? '');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ind_turno_indicadores WHERE operacion_id = ? AND turno = ? AND nombre = ? AND meta_id IS NULL");
            $stmt->execute([$op, $turno, $nombre]);
            if ((int)$stmt->fetchColumn() > 0) fallar('Ya existe un indicador con ese nombre en este turno.');
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM ind_turno_indicadores WHERE operacion_id = ? AND turno = ?");
            $stmt->execute([$op, $turno]);
            $orden = (int)$stmt->fetchColumn();
            $pdo->prepare("INSERT INTO ind_turno_indicadores (operacion_id, turno, nombre, meta_id, unidad, sentido, meta_valor, disparador_valor, aplica_compensacion, orden, activo, creado_por)
                           VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, 1, ?)")
                ->execute([$op, $turno, $nombre, $unidad, $sentido, $meta, $disp, $aplica, $orden, (int)$_SESSION['usuario_id']]);
            responder(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => 'Indicador creado.']);
        }

        case 'toggle_indicador': {
            exigirGestion();
            $id = (int)($data['id'] ?? 0);
            $ind = obtenerIndicador($pdo, $op, $id);
            $nuevo = $ind['activo'] ? 0 : 1;
            $pdo->prepare("UPDATE ind_turno_indicadores SET activo = ? WHERE id = ? AND operacion_id = ?")->execute([$nuevo, $id, $op]);
            responder(['success' => true, 'activo' => (bool)$nuevo, 'metas_url' => $ind['es_sistema'] ? $metasUrl : null]);
        }

        case 'delete_indicador': {
            exigirGestion();
            $id = (int)($data['id'] ?? 0);
            $ind = obtenerIndicador($pdo, $op, $id);
            if ($ind['es_sistema']) fallar('Los indicadores de productividad no se eliminan; puedes ocultarlos.');
            $pdo->prepare("DELETE FROM ind_turno_indicadores WHERE id = ? AND operacion_id = ? AND meta_id IS NULL")->execute([$id, $op]);
            responder(['success' => true, 'message' => 'Indicador eliminado.']);
        }

        case 'update_order': {
            exigirGestion();
            $turno = validarTurno($data['turno'] ?? '');
            $ids = array_values(array_filter(array_map('intval', (array)($data['ids'] ?? []))));
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE ind_turno_indicadores SET orden = ? WHERE id = ? AND operacion_id = ? AND turno = ?");
            foreach ($ids as $i => $iid) $stmt->execute([$i + 1, $iid, $op, $turno]);
            $pdo->commit();
            responder(['success' => true]);
        }

        case 'resumen_persona': {
            $uid = (int)($data['usuario_id'] ?? 0);
            if ($uid !== (int)$_SESSION['usuario_id'] && !$puedeGestionar) fallar('Solo puedes consultar tu propio resumen.', 403);
            [$ini, $fin] = rangoMes($data['year'] ?? 0, $data['month'] ?? 0);
            $stmt = $pdo->prepare("SELECT id, nombre, cargo FROM usuarios WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$uid, $op]);
            $persona = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$persona) fallar('Persona no encontrada en esta operación.', 404);

            $stmt = $pdo->prepare("SELECT v.fecha, v.turno, v.valor, v.estado, v.meta_hist, v.disparador_hist, v.sentido_hist,
                                          i.aplica_compensacion, COALESCE(m.actividad, i.nombre) AS indicador, COALESCE(m.unidad_medida, i.unidad) AS unidad
                                   FROM ind_turno_valores v
                                   JOIN ind_turno_responsables r ON r.valor_id = v.id AND r.usuario_id = ?
                                   JOIN ind_turno_indicadores i ON i.id = v.indicador_id
                                   LEFT JOIN metas m ON m.id = i.meta_id
                                   WHERE v.operacion_id = ? AND v.fecha BETWEEN ? AND ?
                                   ORDER BY v.fecha, v.turno, i.orden");
            $stmt->execute([$uid, $op, $ini, $fin]);
            $dias = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $f = $r['fecha'];
                if (!isset($dias[$f])) $dias[$f] = ['fecha' => $f, 'cumple' => null, 'indicadores' => []];
                $dias[$f]['indicadores'][] = [
                    'indicador' => $r['indicador'], 'turno' => $r['turno'], 'valor' => (float)$r['valor'], 'unidad' => $r['unidad'],
                    'estado' => $r['estado'], 'meta' => $r['meta_hist'] !== null ? (float)$r['meta_hist'] : null,
                    'disparador' => $r['disparador_hist'] !== null ? (float)$r['disparador_hist'] : null,
                    'sentido' => $r['sentido_hist'], 'aplica_compensacion' => (int)$r['aplica_compensacion'] === 1,
                ];
                if ((int)$r['aplica_compensacion'] === 1) {
                    $ok = $r['estado'] === 'cumple';
                    $dias[$f]['cumple'] = ($dias[$f]['cumple'] === null) ? $ok : ($dias[$f]['cumple'] && $ok);
                }
            }
            $vals = array_values($dias);
            responder([
                'success' => true, 'persona' => $persona, 'dias' => $vals,
                'cumplidos' => count(array_filter($vals, fn($d) => $d['cumple'] === true)),
                'no_cumplidos' => count(array_filter($vals, fn($d) => $d['cumple'] === false)),
            ]);
        }

        default:
            fallar('Acción no válida.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[indicadores_turno] ' . $e->getMessage());
    fallar('Error del servidor al procesar la solicitud.', 500);
}
