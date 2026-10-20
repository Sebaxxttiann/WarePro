<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/metas.php';

$operacionId = (int)getOperacionActiva();
$puedeGestionar = puedeGestionarMetas();

if (empty($_SESSION['metas_csrf'])) {
    $_SESSION['metas_csrf'] = bin2hex(random_bytes(16));
}

/** Guarda un mensaje flash y redirige (patrón POST-redirect-GET). */
function metasRedirigir(string $icon, string $title, string $text): void {
    $_SESSION['flash_metas'] = ['icon' => $icon, 'title' => $title, 'text' => $text];
    header('Location: metas.php');
    exit();
}

/** Convierte un campo numérico del formulario. Devuelve null si viene vacío; lanza si no es válido. */
function metasNumero($valor, string $campo, bool $obligatorio) {
    $valor = trim(str_replace(',', '.', (string)$valor));
    if ($valor === '') {
        if ($obligatorio) throw new InvalidArgumentException("El campo $campo es obligatorio.");
        return null;
    }
    if (!is_numeric($valor) || (float)$valor < 0) {
        throw new InvalidArgumentException("El campo $campo debe ser un número mayor o igual a 0.");
    }
    return round((float)$valor, 2);
}

function metasTexto($valor, string $campo, int $max, bool $obligatorio): ?string {
    $valor = trim(preg_replace('/\s+/u', ' ', (string)$valor));
    if ($valor === '') {
        if ($obligatorio) throw new InvalidArgumentException("El campo $campo es obligatorio.");
        return null;
    }
    if (mb_strlen($valor, 'UTF-8') > $max) {
        throw new InvalidArgumentException("El campo $campo admite máximo $max caracteres.");
    }
    return $valor;
}

/** El disparador es la alerta crítica: debe estar "peor" que la meta según el sentido. */
function metasValidarCoherencia(float $meta, ?float $disparador, string $sentido): void {
    if ($disparador === null) return;
    if ($sentido === 'mayor' && $disparador >= $meta) {
        throw new InvalidArgumentException('Cuando "más es mejor", el disparador debe ser menor que la meta.');
    }
    if ($sentido === 'menor' && $disparador <= $meta) {
        throw new InvalidArgumentException('Cuando "menos es mejor", el disparador debe ser mayor que la meta.');
    }
}

// ---------------------------------------------------------------------------
// Procesamiento de formularios (antes de imprimir cualquier HTML)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['action'] ?? '';

    if (!$puedeGestionar) {
        metasRedirigir('error', 'Sin permisos', 'Tu cargo solo permite consultar las metas.');
    }
    if (!$operacionId) {
        metasRedirigir('error', 'Sin operación', 'No hay una operación activa seleccionada.');
    }
    if (!hash_equals($_SESSION['metas_csrf'], (string)($_POST['csrf'] ?? ''))) {
        metasRedirigir('error', 'Sesión expirada', 'Recarga la página e inténtalo de nuevo.');
    }

    try {
        if ($accion === 'crear_meta') {
            $actividad   = metasTexto($_POST['actividad'] ?? '', 'actividad', 50, true);
            $modulo      = metasTexto($_POST['modulo'] ?? '', 'módulo', 50, false) ?? 'Otras';
            $unidad      = metasTexto($_POST['unidad_medida'] ?? '', 'unidad', 20, true);
            $descripcion = metasTexto($_POST['descripcion'] ?? '', 'descripción', 255, false);
            $sentido     = ($_POST['sentido'] ?? '') === 'menor' ? 'menor' : 'mayor';
            $meta        = metasNumero($_POST['meta_minima'] ?? '', 'meta', true);
            $disparador  = metasNumero($_POST['disparador'] ?? '', 'disparador', false);
            metasValidarCoherencia($meta, $disparador, $sentido);

            $stmt = $pdo->prepare("SELECT id, activo, es_sistema FROM metas WHERE operacion_id = ? AND actividad = ? LIMIT 1");
            $stmt->execute([$operacionId, $actividad]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existente && (int)$existente['activo'] === 1) {
                throw new InvalidArgumentException("Ya existe una meta llamada \"$actividad\" en esta operación.");
            }
            if ($existente) {
                if ((int)$existente['es_sistema'] === 1) {
                    // Meta de sistema desactivada: se reactiva sin cambiar nombre ni sentido.
                    $stmt = $pdo->prepare("UPDATE metas SET activo = 1, meta_minima = ?, disparador = ?, unidad_medida = ?, configurada = 1 WHERE id = ? AND operacion_id = ?");
                    $stmt->execute([$meta, $disparador, $unidad, $existente['id'], $operacionId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE metas SET activo = 1, meta_minima = ?, disparador = ?, unidad_medida = ?, sentido = ?, modulo = ?, descripcion = ?, configurada = 1 WHERE id = ? AND operacion_id = ?");
                    $stmt->execute([$meta, $disparador, $unidad, $sentido, $modulo, $descripcion, $existente['id'], $operacionId]);
                }
                metasRedirigir('success', 'Meta reactivada', "La meta \"$actividad\" se reactivó con los nuevos valores.");
            }

            $stmt = $pdo->prepare("INSERT INTO metas (operacion_id, actividad, meta_minima, disparador, unidad_medida, sentido, modulo, descripcion, es_sistema, configurada, activo)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 1)");
            $stmt->execute([$operacionId, $actividad, $meta, $disparador, $unidad, $sentido, $modulo, $descripcion]);
            metasRedirigir('success', 'Meta creada', "La meta \"$actividad\" quedó creada para tu operación.");
        }

        if ($accion === 'editar_meta') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM metas WHERE id = ? AND operacion_id = ? AND activo = 1");
            $stmt->execute([$id, $operacionId]);
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new InvalidArgumentException('La meta no existe en esta operación.');
            }

            $esSistema  = (int)$actual['es_sistema'] === 1;
            $unidad     = metasTexto($_POST['unidad_medida'] ?? '', 'unidad', 20, true);
            $meta       = metasNumero($_POST['meta_minima'] ?? '', 'meta', true);
            $disparador = metasNumero($_POST['disparador'] ?? '', 'disparador', false);
            $sentido    = ($_POST['sentido'] ?? '') === 'menor' ? 'menor' : 'mayor';
            $modulo      = metasTexto($_POST['modulo'] ?? '', 'módulo', 50, false) ?? 'Otras';
            $descripcion = metasTexto($_POST['descripcion'] ?? '', 'descripción', 255, false);
            metasValidarCoherencia($meta, $disparador, $sentido);

            if ($esSistema) {
                // El nombre de una meta del sistema no cambia: las productividades la buscan por ese nombre.
                $stmt = $pdo->prepare("UPDATE metas SET modulo = ?, descripcion = ?, meta_minima = ?, disparador = ?, unidad_medida = ?, sentido = ?, configurada = 1 WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$modulo, $descripcion, $meta, $disparador, $unidad, $sentido, $id, $operacionId]);
            } else {
                $actividad   = metasTexto($_POST['actividad'] ?? '', 'actividad', 50, true);

                $stmt = $pdo->prepare("SELECT id FROM metas WHERE operacion_id = ? AND actividad = ? AND id <> ? LIMIT 1");
                $stmt->execute([$operacionId, $actividad, $id]);
                if ($stmt->fetchColumn()) {
                    throw new InvalidArgumentException("Ya existe otra meta llamada \"$actividad\" en esta operación (puede estar eliminada; créala de nuevo para reactivarla).");
                }

                $stmt = $pdo->prepare("UPDATE metas SET actividad = ?, modulo = ?, descripcion = ?, meta_minima = ?, disparador = ?, unidad_medida = ?, sentido = ?, configurada = 1 WHERE id = ? AND operacion_id = ? AND es_sistema = 0");
                $stmt->execute([$actividad, $modulo, $descripcion, $meta, $disparador, $unidad, $sentido, $id, $operacionId]);
            }
            metasRedirigir('success', 'Meta actualizada', 'Los valores de "' . ($esSistema ? $actual['actividad'] : $actividad) . '" quedaron guardados.');
        }

        if ($accion === 'eliminar_meta') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT actividad, es_sistema FROM metas WHERE id = ? AND operacion_id = ? AND activo = 1");
            $stmt->execute([$id, $operacionId]);
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new InvalidArgumentException('La meta no existe en esta operación.');
            }
            if ((int)$actual['es_sistema'] === 1) {
                throw new InvalidArgumentException('Las metas del sistema no se pueden eliminar: las usan las productividades.');
            }
            $stmt = $pdo->prepare("UPDATE metas SET activo = 0 WHERE id = ? AND operacion_id = ? AND es_sistema = 0");
            $stmt->execute([$id, $operacionId]);
            metasRedirigir('success', 'Meta eliminada', "La meta \"{$actual['actividad']}\" fue eliminada.");
        }

        metasRedirigir('error', 'Acción no válida', 'No se reconoce la acción solicitada.');
    } catch (InvalidArgumentException $e) {
        metasRedirigir('warning', 'Revisa los datos', $e->getMessage());
    } catch (PDOException $e) {
        metasRedirigir('error', 'Error', 'No se pudo guardar la meta. Verifica que el nombre no esté repetido y que los textos no sean demasiado largos.');
    }
}

// ---------------------------------------------------------------------------
// Consulta
// ---------------------------------------------------------------------------
asegurarMetasBase($pdo, $operacionId);

$flash = $_SESSION['flash_metas'] ?? null;
unset($_SESSION['flash_metas']);

$stmt = $pdo->prepare("SELECT nombre FROM operaciones WHERE id = ?");
$stmt->execute([$operacionId]);
$nombreOperacion = $stmt->fetchColumn() ?: ('Operación #' . $operacionId);

$stmt = $pdo->prepare("SELECT * FROM metas WHERE operacion_id = ? AND activo = 1 ORDER BY es_sistema DESC, actividad");
$stmt->execute([$operacionId]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Orden de módulos: primero los del catálogo (en su orden), luego los propios alfabéticamente.
$ordenModulos = [];
foreach (METAS_CATALOGO as $item) {
    if (!isset($ordenModulos[$item[1]])) $ordenModulos[$item[1]] = count($ordenModulos);
}
$grupos = [];
foreach ($filas as $fila) {
    $modulo = trim((string)$fila['modulo']) !== '' ? $fila['modulo'] : 'Otras';
    $grupos[$modulo][] = $fila;
}
uksort($grupos, function ($a, $b) use ($ordenModulos) {
    $oa = $ordenModulos[$a] ?? 1000;
    $ob = $ordenModulos[$b] ?? 1000;
    return $oa === $ob ? strcasecmp($a, $b) : $oa <=> $ob;
});

$totalMetas = count($filas);
$pendientes = count(array_filter($filas, fn($f) => (int)$f['configurada'] === 0));
$propias = count(array_filter($filas, fn($f) => (int)$f['es_sistema'] === 0));

$iconosModulo = [
    'Maquila'   => 'fa-box-open',
    'Vertimiento' => 'fa-fill-drip',
    'Revisión'    => 'fa-clipboard-check',
    'Sorting'     => 'fa-random',
    'Recargue'    => 'fa-truck-loading',
    'Montacarga'  => 'fa-stopwatch',
];

function metasNum($v): string {
    if ($v === null || $v === '') return '';
    $s = number_format((float)$v, 2, ',', '.');
    return rtrim(rtrim($s, '0'), ',');
}

require_once '../../core/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { font-family: 'Poppins', sans-serif; background: #f4f5f7; margin: 0; color: #1a1a1a; }
    .mt-wrap { max-width: 1300px; margin: 0 auto; padding: 32px 20px 60px; box-sizing: border-box; }
    .mt-wrap *, .mt-modal * { box-sizing: border-box; }

    .mt-hero { background: linear-gradient(135deg, #1a1a1a 0%, #000 100%); color: #fff; border-radius: 20px; padding: 28px 30px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,.12); }
    .mt-hero::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 5px; background: linear-gradient(135deg, #FFD700, #FFA500); }
    .mt-hero-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap; }
    .mt-hero h1 { margin: 0 0 6px; font-size: 1.9rem; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .mt-hero h1 i { color: #FFD700; }
    .mt-hero p { margin: 0; color: #bbb; font-size: .95rem; max-width: 720px; line-height: 1.5; }
    .mt-op { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,215,0,.12); border: 1px solid rgba(255,215,0,.4); color: #FFD700; padding: 6px 14px; border-radius: 50px; font-weight: 600; font-size: .85rem; margin-bottom: 12px; }

    .mt-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-top: 22px; }
    .mt-stat { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); border-radius: 14px; padding: 14px 18px; }
    .mt-stat .n { font-size: 1.7rem; font-weight: 700; color: #FFD700; line-height: 1.1; }
    .mt-stat .l { font-size: .8rem; color: #aaa; text-transform: uppercase; letter-spacing: .5px; }
    .mt-stat.warn { border-color: rgba(255,165,0,.6); background: rgba(255,165,0,.12); }
    .mt-stat.warn .n { color: #FFA500; }
    .mt-stat.ok .n { color: #2ecc71; }

    .mt-btn { border: none; border-radius: 10px; padding: 10px 18px; font-family: inherit; font-weight: 600; font-size: .9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all .2s; }
    .mt-btn-gold { background: linear-gradient(135deg, #FFD700, #FFA500); color: #1a1a1a; }
    .mt-btn-gold:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(255,215,0,.35); }
    .mt-btn-ghost { background: #eceff1; color: #333; }
    .mt-btn-ghost:hover { background: #dfe3e6; }
    .mt-btn-sm { padding: 7px 12px; font-size: .8rem; border-radius: 8px; }
    .mt-btn-edit { background: #1a1a1a; color: #FFD700; }
    .mt-btn-edit:hover { background: #333; }
    .mt-btn-del { background: rgba(231,76,60,.1); color: #e74c3c; }
    .mt-btn-del:hover { background: #e74c3c; color: #fff; }

    .mt-help { background: #fff; border-left: 4px solid #FFD700; border-radius: 12px; padding: 14px 18px; margin: 22px 0; font-size: .85rem; color: #555; line-height: 1.6; box-shadow: 0 4px 12px rgba(0,0,0,.04); }
    .mt-help strong { color: #1a1a1a; }

    .mt-modulo { background: #fff; border-radius: 18px; box-shadow: 0 8px 24px rgba(0,0,0,.06); margin-bottom: 22px; overflow: hidden; }
    .mt-modulo-head { display: flex; align-items: center; gap: 12px; padding: 16px 22px; border-bottom: 1px solid #f0f0f0; }
    .mt-modulo-icon { width: 38px; height: 38px; border-radius: 10px; background: #1a1a1a; color: #FFD700; display: flex; align-items: center; justify-content: center; }
    .mt-modulo-head h2 { margin: 0; font-size: 1.1rem; font-weight: 600; }
    .mt-modulo-head span { margin-left: auto; font-size: .8rem; color: #888; }

    .mt-table-wrap { overflow-x: auto; }
    .mt-table { width: 100%; border-collapse: collapse; min-width: 860px; }
    .mt-table th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #777; background: #fafafa; padding: 10px 16px; font-weight: 600; }
    .mt-table td { padding: 14px 16px; border-top: 1px solid #f3f3f3; font-size: .88rem; vertical-align: middle; }
    .mt-table tr.pend td { background: rgba(255,165,0,.05); }
    .mt-act { font-weight: 600; }
    .mt-desc { font-size: .78rem; color: #888; margin-top: 2px; max-width: 360px; }
    .mt-val { font-weight: 700; font-size: 1.05rem; }
    .mt-muted { color: #aaa; font-style: italic; font-size: .82rem; }
    .mt-actions { display: flex; gap: 6px; justify-content: flex-end; }

    .mt-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 50px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
    .mt-b-sis { background: #1a1a1a; color: #FFD700; }
    .mt-b-own { background: rgba(52,152,219,.12); color: #2471a3; }
    .mt-b-pend { background: rgba(255,165,0,.15); color: #c77700; }
    .mt-b-up { background: rgba(39,174,96,.12); color: #1e8449; }
    .mt-b-down { background: rgba(142,68,173,.12); color: #7d3c98; }

    .mt-empty { text-align: center; padding: 50px 20px; color: #888; background: #fff; border-radius: 18px; }

    .mt-modal { position: fixed; inset: 0; background: rgba(0,0,0,.55); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: flex-start; justify-content: center; padding: 40px 16px; overflow-y: auto; font-family: 'Poppins', sans-serif; }
    .mt-modal[hidden] { display: none; }
    .mt-modal-box { background: #fff; border-radius: 18px; width: 100%; max-width: 560px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
    .mt-modal-head { background: linear-gradient(135deg, #1a1a1a, #000); color: #fff; padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #FFD700; }
    .mt-modal-head h3 { margin: 0; font-size: 1.1rem; }
    .mt-modal-close { background: none; border: none; color: #fff; font-size: 1.4rem; cursor: pointer; }
    .mt-modal-body { padding: 22px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; }
    .mt-field { display: flex; flex-direction: column; gap: 5px; }
    .mt-field.full { grid-column: 1 / -1; }
    .mt-field label { font-size: .8rem; font-weight: 600; color: #333; }
    .mt-field small { font-size: .72rem; color: #888; }
    .mt-field input, .mt-field select, .mt-field textarea { font-family: inherit; font-size: .92rem; padding: 10px 12px; border: 2px solid #eceff1; border-radius: 10px; background: #fafafa; outline: none; width: 100%; }
    .mt-field input:focus, .mt-field select:focus, .mt-field textarea:focus { border-color: #FFD700; background: #fff; }
    .mt-field input:disabled, .mt-field select:disabled { color: #777; background: #f0f0f0; }
    .mt-modal-foot { padding: 14px 22px 20px; display: flex; justify-content: flex-end; gap: 10px; }

    @media (max-width: 640px) {
        .mt-wrap { padding: 20px 14px 40px; }
        .mt-hero { padding: 22px 18px; }
        .mt-hero h1 { font-size: 1.5rem; }
        .mt-modal-body { grid-template-columns: 1fr; }
    }
</style>

<div class="mt-wrap">
    <section class="mt-hero">
        <div class="mt-hero-top">
            <div>
                <div class="mt-op"><i class="fas fa-warehouse"></i> <?php echo htmlspecialchars($nombreOperacion); ?></div>
                <h1><i class="fas fa-bullseye"></i> Metas de productividad</h1>
                <p>Estas son las metas con las que se evalúan las productividades de tu operación. Las metas del sistema ya están creadas: solo coloca sus valores. También puedes crear metas propias para tus indicadores.</p>
            </div>
            <?php if ($puedeGestionar): ?>
                <button type="button" class="mt-btn mt-btn-gold" onclick="abrirCrear()"><i class="fas fa-plus"></i> Nueva meta</button>
            <?php endif; ?>
        </div>
        <div class="mt-stats">
            <div class="mt-stat"><div class="n"><?php echo $totalMetas; ?></div><div class="l">Metas activas</div></div>
            <div class="mt-stat <?php echo $pendientes > 0 ? 'warn' : 'ok'; ?>">
                <div class="n"><?php echo $pendientes; ?></div>
                <div class="l"><?php echo $pendientes > 0 ? 'Pendientes por configurar' : 'Todas configuradas'; ?></div>
            </div>
            <div class="mt-stat"><div class="n"><?php echo $propias; ?></div><div class="l">Metas propias</div></div>
        </div>
    </section>

    <div class="mt-help">
        <strong>¿Cómo se evalúa?</strong>
        <i class="fas fa-arrow-up" style="color:#1e8449"></i> <strong>Más es mejor</strong> (ej. cajas/hora): cumple si el resultado es mayor o igual a la meta.
        <i class="fas fa-arrow-down" style="color:#7d3c98"></i> <strong>Menos es mejor</strong> (ej. minutos): cumple si es menor o igual a la meta.
        El <strong>disparador</strong> (opcional) es el nivel crítico de alerta: si se alcanza, el registro se marca como disparador aunque esté cerca de la meta.
        <?php if (!$puedeGestionar): ?><br><i class="fas fa-lock"></i> Tu cargo puede consultar las metas; solo supervisores, líderes y administradores pueden modificarlas.<?php endif; ?>
    </div>

    <?php if (!$grupos): ?>
        <div class="mt-empty"><i class="fas fa-bullseye" style="font-size:2.5rem;color:#FFD700"></i><p>No hay metas para esta operación.</p></div>
    <?php endif; ?>

    <?php foreach ($grupos as $modulo => $metasModulo): ?>
        <section class="mt-modulo">
            <div class="mt-modulo-head">
                <div class="mt-modulo-icon"><i class="fas <?php echo $iconosModulo[$modulo] ?? 'fa-tasks'; ?>"></i></div>
                <h2><?php echo htmlspecialchars($modulo); ?></h2>
                <span><?php echo count($metasModulo); ?> meta<?php echo count($metasModulo) === 1 ? '' : 's'; ?></span>
            </div>
            <div class="mt-table-wrap">
                <table class="mt-table">
                    <thead>
                        <tr>
                            <th>Actividad</th>
                            <th>Unidad</th>
                            <th>Sentido</th>
                            <th>Meta</th>
                            <th>Disparador</th>
                            <th>Estado</th>
                            <?php if ($puedeGestionar): ?><th style="text-align:right">Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($metasModulo as $m):
                        $esSistema = (int)$m['es_sistema'] === 1;
                        $pendiente = (int)$m['configurada'] === 0;
                        $datosJs = [
                            'id' => (int)$m['id'],
                            'actividad' => $m['actividad'],
                            'modulo' => $m['modulo'],
                            'unidad_medida' => $m['unidad_medida'],
                            'sentido' => $m['sentido'],
                            'meta_minima' => $m['meta_minima'] === null ? '' : (float)$m['meta_minima'],
                            'disparador' => $m['disparador'] === null ? '' : (float)$m['disparador'],
                            'descripcion' => $m['descripcion'],
                            'es_sistema' => $esSistema,
                        ];
                    ?>
                        <tr class="<?php echo $pendiente ? 'pend' : ''; ?>">
                            <td>
                                <div class="mt-act"><?php echo htmlspecialchars($m['actividad']); ?></div>
                                <?php if (!empty($m['descripcion'])): ?><div class="mt-desc"><?php echo htmlspecialchars($m['descripcion']); ?></div><?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($m['unidad_medida']); ?></td>
                            <td>
                                <?php if ($m['sentido'] === 'menor'): ?>
                                    <span class="mt-badge mt-b-down"><i class="fas fa-arrow-down"></i> Menos es mejor</span>
                                <?php else: ?>
                                    <span class="mt-badge mt-b-up"><i class="fas fa-arrow-up"></i> Más es mejor</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="mt-val"><?php echo metasNum($m['meta_minima']); ?></span></td>
                            <td>
                                <?php if ($m['disparador'] === null): ?>
                                    <span class="mt-muted">Sin disparador</span>
                                <?php else: ?>
                                    <span class="mt-val" style="color:#e74c3c"><?php echo metasNum($m['disparador']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pendiente): ?>
                                    <span class="mt-badge mt-b-pend"><i class="fas fa-exclamation-circle"></i> Pendiente por configurar</span>
                                <?php elseif ($esSistema): ?>
                                    <span class="mt-badge mt-b-sis" title="La usan las productividades; no se puede renombrar ni eliminar"><i class="fas fa-cog"></i> Sistema</span>
                                <?php else: ?>
                                    <span class="mt-badge mt-b-own"><i class="fas fa-user-edit"></i> Propia</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($puedeGestionar): ?>
                                <td>
                                    <div class="mt-actions">
                                        <button type="button" class="mt-btn mt-btn-sm mt-btn-edit" onclick='abrirEditar(<?php echo htmlspecialchars(json_encode($datosJs, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>)'>
                                            <i class="fas fa-pen"></i> <?php echo $pendiente ? 'Configurar' : 'Editar'; ?>
                                        </button>
                                        <?php if (!$esSistema): ?>
                                            <button type="button" class="mt-btn mt-btn-sm mt-btn-del" onclick='confirmarEliminar(<?php echo (int)$m['id']; ?>, <?php echo htmlspecialchars(json_encode($m['actividad'], JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>)'>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<?php if ($puedeGestionar): ?>
<datalist id="mtModulos">
    <?php foreach (array_keys($grupos) as $mod): ?><option value="<?php echo htmlspecialchars($mod); ?>"><?php endforeach; ?>
</datalist>

<div class="mt-modal" id="mtModal" hidden>
    <div class="mt-modal-box">
        <div class="mt-modal-head">
            <h3 id="mtModalTitulo">Nueva meta</h3>
            <button type="button" class="mt-modal-close" onclick="cerrarModal()" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" id="mtForm" autocomplete="off">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['metas_csrf']); ?>">
            <input type="hidden" name="action" id="mtAction" value="crear_meta">
            <input type="hidden" name="id" id="mtId" value="">
            <div class="mt-modal-body">
                <div class="mt-field full">
                    <label for="mtActividad">Actividad / indicador</label>
                    <input type="text" name="actividad" id="mtActividad" maxlength="50" required>
                    <small id="mtActividadAyuda">Nombre único dentro de tu operación.</small>
                </div>
                <div class="mt-field">
                    <label for="mtModulo">Módulo</label>
                    <input type="text" name="modulo" id="mtModulo" maxlength="50" list="mtModulos" placeholder="Ej. Picking">
                </div>
                <div class="mt-field">
                    <label for="mtUnidad">Unidad de medida</label>
                    <input type="text" name="unidad_medida" id="mtUnidad" maxlength="20" required placeholder="cj/h, min, %">
                </div>
                <div class="mt-field full">
                    <label for="mtSentido">Sentido</label>
                    <select name="sentido" id="mtSentido">
                        <option value="mayor">Más es mejor (cumple si el valor es mayor o igual a la meta)</option>
                        <option value="menor">Menos es mejor (cumple si el valor es menor o igual a la meta)</option>
                    </select>
                    <small id="mtSentidoAyuda"></small>
                </div>
                <div class="mt-field">
                    <label for="mtMeta">Meta</label>
                    <input type="number" name="meta_minima" id="mtMeta" step="0.01" min="0" required>
                </div>
                <div class="mt-field">
                    <label for="mtDisparador">Disparador (opcional)</label>
                    <input type="number" name="disparador" id="mtDisparador" step="0.01" min="0" placeholder="Sin disparador">
                    <small id="mtDispAyuda"></small>
                </div>
                <div class="mt-field full">
                    <label for="mtDescripcion">Descripción</label>
                    <textarea name="descripcion" id="mtDescripcion" maxlength="255" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-modal-foot">
                <button type="button" class="mt-btn mt-btn-ghost" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="mt-btn mt-btn-gold"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="mtFormEliminar" hidden>
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['metas_csrf']); ?>">
    <input type="hidden" name="action" value="eliminar_meta">
    <input type="hidden" name="id" id="mtEliminarId" value="">
</form>
<?php endif; ?>

<script>
<?php if ($flash): ?>
    Swal.fire(Object.assign(<?php echo json_encode($flash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>,{ confirmButtonColor: '#FFD700' }));
<?php endif; ?>

<?php if ($puedeGestionar): ?>
    const mtModal = document.getElementById('mtModal');

    function mtSet(id, valor) { document.getElementById(id).value = (valor === null || valor === undefined) ? '' : valor; }

    function actualizarAyudaDisparador() {
        const sentido = document.getElementById('mtSentido').value;
        document.getElementById('mtDispAyuda').textContent = sentido === 'menor'
            ? 'Debe ser mayor que la meta (alerta crítica).'
            : 'Debe ser menor que la meta (alerta crítica).';
    }

    function abrirCrear() {
        document.getElementById('mtForm').reset();
        document.getElementById('mtModalTitulo').textContent = 'Nueva meta';
        mtSet('mtAction', 'crear_meta');
        mtSet('mtId', '');
        ['mtActividad', 'mtModulo', 'mtSentido', 'mtDescripcion'].forEach(id => document.getElementById(id).disabled = false);
        document.getElementById('mtActividadAyuda').textContent = 'Nombre único dentro de tu operación.';
        document.getElementById('mtSentidoAyuda').textContent = '';
        actualizarAyudaDisparador();
        mtModal.hidden = false;
        document.getElementById('mtActividad').focus();
    }

    function abrirEditar(meta) {
        document.getElementById('mtForm').reset();
        document.getElementById('mtModalTitulo').textContent = (meta.es_sistema ? 'Configurar: ' : 'Editar: ') + meta.actividad;
        mtSet('mtAction', 'editar_meta');
        mtSet('mtId', meta.id);
        mtSet('mtActividad', meta.actividad);
        mtSet('mtModulo', meta.modulo);
        mtSet('mtUnidad', meta.unidad_medida);
        mtSet('mtSentido', meta.sentido);
        mtSet('mtMeta', meta.meta_minima);
        mtSet('mtDisparador', meta.disparador);
        mtSet('mtDescripcion', meta.descripcion);
        // En las metas del sistema solo el nombre es fijo; lo demás se puede editar.
        ['mtModulo', 'mtSentido', 'mtDescripcion'].forEach(id => document.getElementById(id).disabled = false);
        document.getElementById('mtActividad').disabled = !!meta.es_sistema;
        document.getElementById('mtActividadAyuda').textContent = meta.es_sistema
            ? 'Meta del sistema: la usan las productividades, por eso no se puede renombrar.'
            : 'Nombre único dentro de tu operación.';
        document.getElementById('mtSentidoAyuda').textContent = '';
        actualizarAyudaDisparador();
        mtModal.hidden = false;
        document.getElementById('mtMeta').focus();
    }

    function cerrarModal() { mtModal.hidden = true; }

    document.getElementById('mtSentido').addEventListener('change', actualizarAyudaDisparador);
    mtModal.addEventListener('click', e => { if (e.target === mtModal) cerrarModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

    document.getElementById('mtForm').addEventListener('submit', function (e) {
        const meta = parseFloat(document.getElementById('mtMeta').value);
        const dispTxt = document.getElementById('mtDisparador').value.trim();
        const sentido = document.getElementById('mtSentido').value;
        if (dispTxt !== '' && !isNaN(meta)) {
            const disp = parseFloat(dispTxt);
            const incoherente = sentido === 'menor' ? disp <= meta : disp >= meta;
            if (incoherente) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Disparador no válido',
                    text: sentido === 'menor' ? 'Cuando "menos es mejor", el disparador debe ser mayor que la meta.' : 'Cuando "más es mejor", el disparador debe ser menor que la meta.',
                    confirmButtonColor: '#FFD700'
                });
            }
        }
    });

    function confirmarEliminar(id, actividad) {
        Swal.fire({
            title: '¿Eliminar meta?',
            text: `Se eliminará la meta "${actividad}" de esta operación.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(r => {
            if (r.isConfirmed) {
                document.getElementById('mtEliminarId').value = id;
                document.getElementById('mtFormEliminar').submit();
            }
        });
    }
<?php endif; ?>
</script>
</body>
</html>
