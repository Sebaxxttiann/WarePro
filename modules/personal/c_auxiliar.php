<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/metas.php';
require_once '../../core/compensacion.php';

date_default_timezone_set('America/Bogota');

$operacion_id = (int)getOperacionActiva();
$es_gestor = in_array($_SESSION['cargo'] ?? '', ROLES_GESTION_METAS, true);
$aviso = null;

// ---------- Persona consultada ----------
$usuario_id = (int)$_SESSION['usuario_id'];
if ($es_gestor && isset($_GET['usuario_id']) && $_GET['usuario_id'] !== '') {
    $usuario_id = (int)$_GET['usuario_id'];
}
$stmt = $pdo->prepare("SELECT id, nombre, cargo FROM usuarios WHERE id = ? AND operacion_id = ?");
$stmt->execute([$usuario_id, $operacion_id]);
$persona = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$persona && $usuario_id !== (int)$_SESSION['usuario_id']) {
    $aviso = 'La persona seleccionada no pertenece a esta operación.';
    $usuario_id = (int)$_SESSION['usuario_id'];
    $stmt->execute([$usuario_id, $operacion_id]);
    $persona = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$persona) $persona = ['id' => $usuario_id, 'nombre' => $_SESSION['nombre'] ?? '', 'cargo' => $_SESSION['cargo'] ?? ''];
$es_propia = $usuario_id === (int)$_SESSION['usuario_id'];
// Un gestor sin productividades propias ve primero el selector
$pedir_seleccion = $es_gestor && $es_propia && ($persona['cargo'] ?? '') !== 'auxiliar';

// ---------- Mes ----------
$mes_param = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^(\d{4})-(\d{2})$/', $mes_param, $mm) || (int)$mm[2] < 1 || (int)$mm[2] > 12) $mes_param = date('Y-m');
[$anio, $mes] = array_map('intval', explode('-', $mes_param));
$inicio = sprintf('%04d-%02d-01', $anio, $mes);
$dias_mes = (int)date('t', strtotime($inicio));
$fin = sprintf('%04d-%02d-%02d', $anio, $mes, $dias_mes);
$hoy = date('Y-m-d');
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_texto = $meses[$mes - 1] . ' ' . $anio;
$mes_prev = date('Y-m', strtotime($inicio . ' -1 month'));
$mes_next = date('Y-m', strtotime($inicio . ' +1 month'));

// ---------- Montos ----------
$comp = obtenerCompensacion($pdo, $operacion_id, 'auxiliar');
$bono_mensual_total = $comp['monto_variable'];
$dias_habiles = $comp['dias_habiles'];
$bono_diario = $bono_mensual_total / $dias_habiles;

// ---------- Productividades del mes (por día y actividad) ----------
$dias = []; // fecha => ['prod' => [...], 'ind' => [...]]
if (!$pedir_seleccion) {
    $stmt = $pdo->prepare("
        SELECT fecha, fuente, actividad, MIN(cumple_meta) AS cumple, COUNT(*) AS registros
        FROM (
            SELECT fecha, 'Reempaque' AS fuente, actividad, cumple_meta FROM reempaque1 WHERE auxiliar_id = ? AND operacion_id = ? AND fecha BETWEEN ? AND ?
            UNION ALL
            SELECT fecha, 'Revisión', actividad, cumple_meta FROM revision WHERE auxiliar_id = ? AND operacion_id = ? AND fecha BETWEEN ? AND ?
            UNION ALL
            SELECT fecha, 'Vertimiento', actividad, cumple_meta FROM vertimiento WHERE auxiliar_id = ? AND operacion_id = ? AND fecha BETWEEN ? AND ?
            UNION ALL
            SELECT fecha, 'OWS Reempaque', actividad, 1 FROM ows_reempaque WHERE usuario_id = ? AND operacion_id = ? AND fecha BETWEEN ? AND ?
            UNION ALL
            SELECT fecha, 'OWS Vertimiento', actividad, 1 FROM ows_vertimiento WHERE usuario_id = ? AND operacion_id = ? AND fecha BETWEEN ? AND ?
            UNION ALL
            SELECT fecha, 'OWS Revisión', actividad, 1 FROM ows_revision WHERE usuario_id = ? AND operacion_id = ? AND fecha BETWEEN ? AND ?
        ) AS r
        GROUP BY fecha, fuente, actividad
        ORDER BY fecha, fuente, actividad
    ");
    $params = [];
    for ($i = 0; $i < 6; $i++) array_push($params, $usuario_id, $operacion_id, $inicio, $fin);
    $stmt->execute($params);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $dias[$r['fecha']]['prod'][] = [
            'nombre' => $r['fuente'] . ($r['actividad'] !== null && $r['actividad'] !== '' && $r['actividad'] !== $r['fuente'] ? ' · ' . $r['actividad'] : ''),
            'ok' => (int)$r['cumple'] === 1 && $r['cumple'] !== null,
            'registros' => (int)$r['registros'],
        ];
    }

    // ---------- Indicadores de turno donde fue responsable ----------
    $stmt = $pdo->prepare("
        SELECT v.fecha, v.turno, v.valor, v.estado, v.meta_hist, v.disparador_hist, v.sentido_hist,
               COALESCE(m.actividad, i.nombre) AS nombre, COALESCE(m.unidad_medida, i.unidad) AS unidad
        FROM ind_turno_valores v
        JOIN ind_turno_responsables r ON r.valor_id = v.id AND r.usuario_id = ?
        JOIN ind_turno_indicadores i ON i.id = v.indicador_id
        LEFT JOIN metas m ON m.id = i.meta_id
        WHERE v.operacion_id = ? AND i.aplica_compensacion = 1 AND v.fecha BETWEEN ? AND ?
        ORDER BY v.fecha, v.turno, i.orden
    ");
    $stmt->execute([$usuario_id, $operacion_id, $inicio, $fin]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $dias[$r['fecha']]['ind'][] = [
            'nombre' => $r['nombre'], 'turno' => $r['turno'], 'valor' => (float)$r['valor'], 'unidad' => $r['unidad'],
            'estado' => $r['estado'], 'meta' => $r['meta_hist'], 'disparador' => $r['disparador_hist'], 'sentido' => $r['sentido_hist'],
            'ok' => $r['estado'] === 'cumple',
        ];
    }
}

// ---------- Evaluación por día ----------
$dias_logrados = 0;
$dias_fallados = 0;
foreach ($dias as $fecha => &$d) {
    $d['prod'] = $d['prod'] ?? [];
    $d['ind'] = $d['ind'] ?? [];
    $fallas = [];
    foreach ($d['prod'] as $p) if (!$p['ok']) $fallas[] = $p['nombre'];
    foreach ($d['ind'] as $p) if (!$p['ok']) $fallas[] = 'T' . $p['turno'] . ' ' . $p['nombre'] . ($p['estado'] === 'disparador' ? ' (disparador)' : '');
    $d['fallas'] = $fallas;
    $d['cumple'] = empty($fallas);
    if ($d['cumple']) $dias_logrados++; else $dias_fallados++;
}
unset($d);
ksort($dias);

$dinero_bruto = $dias_logrados * $bono_diario;
$dinero_acumulado = min($bono_mensual_total, $dinero_bruto);
$tope_alcanzado = $dinero_bruto > $bono_mensual_total;

// Proyección: si cumple todos los días que faltan del mes (sin domingos)
$dias_restantes = 0;
if ($fin >= $hoy) {
    $desde = max($inicio, date('Y-m-d', strtotime($hoy . ' +1 day')));
    for ($t = strtotime($desde); $t <= strtotime($fin); $t += 86400) {
        if (date('N', $t) != 7) $dias_restantes++;
    }
}
$proyeccion = min($bono_mensual_total, $dinero_acumulado + $dias_restantes * $bono_diario);
$pct = $bono_mensual_total > 0 ? min(100, ($dinero_acumulado / $bono_mensual_total) * 100) : 0;

function cop($n) { return '$' . number_format($n, 0, ',', '.'); }

require_once '../../core/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensación Variable - Ware Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cv * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; min-height: 100vh; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: 'Poppins', sans-serif; }
        .welcome-section { margin-bottom: 30px; display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px; }
        .welcome-section h2 { font-size: 2.2rem; color: #1a1a1a; margin: 0 0 5px; font-weight: 700; }
        .welcome-section p { font-size: 1.05rem; color: #666; margin: 0; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .mes-nav { display: flex; align-items: center; gap: 6px; background: #fff; border-radius: 12px; padding: 6px; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
        .mes-nav a { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 8px; color: #1a1a1a; text-decoration: none; }
        .mes-nav a:hover { background: #f1f3f5; }
        .mes-nav span { font-weight: 600; padding: 0 8px; min-width: 130px; text-align: center; }
        .selector-box { background: #fff; border-radius: 12px; padding: 8px 10px; box-shadow: 0 4px 14px rgba(0,0,0,.06); min-width: 280px; }
        .selector-box label { font-size: .7rem; text-transform: uppercase; color: #999; font-weight: 600; letter-spacing: .5px; }
        .selector-box select { width: 100%; padding: 6px 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: inherit; }
        .alerta { background: #fff4e5; color: #8a5300; border: 1px solid #ffd8a8; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; }
        .cards-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; margin-top: 10px; }
        @media (max-width: 900px) { .cards-grid { grid-template-columns: 1fr; } }
        .dashboard-card { background: white; border-radius: 20px; padding: 40px 30px; box-shadow: 0 10px 30px rgba(0,0,0,.1); position: relative; overflow: hidden; }
        .premium-card { background: linear-gradient(135deg, #1a1a1a 0%, #000 100%); color: white; border: 2px solid transparent; transition: all .4s ease; }
        .premium-card:hover { border-color: #FFD700; box-shadow: 0 20px 40px rgba(255,215,0,.15); transform: translateY(-5px); }
        .premium-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
        .card-content { display: flex; align-items: center; gap: 25px; }
        @media (max-width: 520px) { .card-content { flex-direction: column; align-items: flex-start; } .welcome-section h2 { font-size: 1.7rem; } .monto-gigante { font-size: 2.3rem !important; } }
        .card-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #1a1a1a; box-shadow: 0 5px 15px rgba(255,215,0,.3); flex-shrink: 0; }
        .card-data { flex: 1; width: 100%; }
        .card-data h3 { font-size: 1rem; color: #ccc; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 5px; }
        .monto-gigante { font-size: 3rem; font-weight: 700; color: #FFD700; line-height: 1; margin-bottom: 15px; }
        .monto-gigante small { font-size: 1.2rem; color: #fff; }
        .progreso-texto { display: flex; justify-content: space-between; font-size: .9rem; color: #aaa; margin-bottom: 8px; gap: 10px; }
        .barra-progreso { height: 6px; background: rgba(255,255,255,.1); border-radius: 10px; overflow: hidden; }
        .progreso-fill { height: 100%; background: linear-gradient(90deg, #FFD700, #FFA500); border-radius: 10px; }
        .proyeccion { margin-top: 14px; font-size: .85rem; color: #bbb; }
        .proyeccion b { color: #fff; }
        .stats-container { display: flex; flex-direction: column; gap: 20px; }
        .stat-card { background: white; border-radius: 20px; padding: 22px 25px; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,.05); transition: transform .3s ease; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon-small { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; }
        .success-stat .stat-icon-small { background: rgba(39,174,96,.1); color: #27ae60; }
        .danger-stat .stat-icon-small { background: rgba(231,76,60,.1); color: #e74c3c; }
        .info-stat .stat-icon-small { background: rgba(255,215,0,.15); color: #cca300; }
        .stat-info .stat-label { color: #666; font-size: .9rem; font-weight: 500; }
        .stat-info .stat-value { font-size: 1.8rem; font-weight: 700; color: #1a1a1a; line-height: 1.2; display: block; }
        .stat-desc { font-size: .85rem; }
        .success-stat .stat-desc { color: #27ae60; font-weight: 600; }
        .danger-stat .stat-desc, .info-stat .stat-desc { color: #888; }
        .history-section { background: white; border-radius: 20px; padding: 30px; margin-top: 30px; box-shadow: 0 10px 30px rgba(0,0,0,.05); }
        .history-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; align-items: center; }
        .history-header h3 { font-size: 1.3rem; color: #1a1a1a; margin: 0; }
        .leyenda { display: flex; flex-wrap: wrap; gap: 14px; font-size: .8rem; color: #666; }
        .leyenda i { margin-right: 4px; }
        /* Calendario */
        .cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; }
        .cal-h { text-align: center; font-size: .75rem; font-weight: 700; color: #999; text-transform: uppercase; padding-bottom: 4px; }
        .cal-d { min-height: 92px; border-radius: 14px; padding: 8px; position: relative; border: 2px solid #f1f3f5; background: #fafafa; display: flex; flex-direction: column; justify-content: space-between; cursor: default; transition: transform .15s; }
        .cal-d.vacio { background: transparent; border-color: transparent; }
        .cal-d .num { font-weight: 700; font-size: .9rem; color: #555; }
        .cal-d .ico { font-size: 1.6rem; text-align: center; line-height: 1; }
        .cal-d .amt { font-size: .75rem; font-weight: 700; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cal-d.ok { background: #ecfdf3; border-color: #a7f3c4; cursor: pointer; }
        .cal-d.ok .ico, .cal-d.ok .amt { color: #16a34a; }
        .cal-d.fail { background: #fef2f2; border-color: #fecaca; cursor: pointer; }
        .cal-d.fail .ico { color: #dc2626; }
        .cal-d.fail .amt { color: #dc2626; text-decoration: line-through; opacity: .75; }
        .cal-d.fail::after { content: ''; position: absolute; left: 10%; right: 10%; top: 50%; border-top: 2px solid rgba(220,38,38,.35); transform: rotate(-24deg); pointer-events: none; }
        .cal-d.hoy { box-shadow: 0 0 0 3px #FFD700; }
        .cal-d.futuro { opacity: .55; }
        .cal-d.domingo .num { color: #e74c3c; }
        .cal-d.ok:hover, .cal-d.fail:hover { transform: translateY(-3px); }
        @media (max-width: 640px) { .cal { gap: 4px; } .cal-d { min-height: 64px; padding: 4px; border-radius: 10px; } .cal-d .ico { font-size: 1.1rem; } .cal-d .amt { font-size: .6rem; } .cal-h { font-size: .6rem; } .history-section { padding: 18px; } }
        .table-responsive { overflow-x: auto; }
        table.det { width: 100%; border-collapse: collapse; }
        table.det th { background: #f8f9fa; padding: 12px 15px; text-align: left; font-size: .85rem; color: #666; font-weight: 600; }
        table.det td { padding: 12px 15px; border-bottom: 1px solid #eee; color: #1a1a1a; vertical-align: top; font-size: .9rem; }
        table.det tr.sel td { background: #fffbea; }
        .badge { padding: 4px 10px; border-radius: 50px; font-size: .78rem; font-weight: 600; display: inline-block; margin: 2px 4px 2px 0; }
        .badge-success { background: rgba(39,174,96,.1); color: #27ae60; }
        .badge-danger { background: rgba(231,76,60,.1); color: #e74c3c; }
        .badge-trigger { background: rgba(231,76,60,.1); color: #e74c3c; border: 2px solid #2563eb; }
        .badge-neutral { background: #f1f3f5; color: #495057; }
        .monto-ganado { font-weight: 700; color: #cca300; white-space: nowrap; }
        .empty-state { text-align: center; padding: 40px 20px; }
        .empty-state i { font-size: 3rem; color: #ddd; margin-bottom: 15px; }
        .empty-state p { color: #666; font-size: 1rem; }
    </style>
</head>
<body>
<div class="dashboard-container cv">
    <div class="welcome-section">
        <div>
            <h2>Compensación Variable</h2>
            <p><?= $es_propia ? 'Tu resumen' : 'Resumen de <strong>' . htmlspecialchars($persona['nombre']) . '</strong>' ?> · <strong><?= $mes_texto ?></strong></p>
        </div>
        <div class="toolbar">
            <?php if ($es_gestor): ?>
                <div class="selector-box">
                    <label><i class="fas fa-user"></i> Ver auxiliar</label>
                    <select id="selAuxiliar" data-person-select data-value-field="id" data-cargos="auxiliar" data-placeholder="Buscar auxiliar...">
                        <?php if (!$es_propia || !$pedir_seleccion): ?>
                            <option value="<?= (int)$persona['id'] ?>" selected><?= htmlspecialchars($persona['nombre']) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="mes-nav">
                <?php $qs = fn($m) => '?' . http_build_query(array_filter(['usuario_id' => $es_propia ? null : $usuario_id, 'mes' => $m])); ?>
                <a href="<?= $qs($mes_prev) ?>" title="Mes anterior"><i class="fas fa-chevron-left"></i></a>
                <span><?= $mes_texto ?></span>
                <a href="<?= $qs($mes_next) ?>" title="Mes siguiente"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
    </div>

    <?php if ($aviso): ?><div class="alerta"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($aviso) ?></div><?php endif; ?>

    <?php if ($pedir_seleccion): ?>
        <div class="history-section">
            <div class="empty-state">
                <i class="fas fa-user-check"></i>
                <p>Selecciona un auxiliar arriba para ver su compensación variable del mes.</p>
            </div>
        </div>
    <?php else: ?>

    <div class="cards-grid">
        <div class="dashboard-card premium-card">
            <div class="card-content">
                <div class="card-icon"><i class="fas fa-wallet"></i></div>
                <div class="card-data">
                    <h3>Acumulado <?= $fin < $hoy ? 'del mes' : 'actual' ?></h3>
                    <div class="monto-gigante"><?= cop($dinero_acumulado) ?> <small>COP</small></div>
                    <div class="progreso-texto">
                        <span>Bolsa mensual: <?= cop($bono_mensual_total) ?></span>
                        <span><?= round($pct) ?>%</span>
                    </div>
                    <div class="barra-progreso"><div class="progreso-fill" style="width: <?= round($pct, 1) ?>%"></div></div>
                    <div class="proyeccion">
                        <i class="fas fa-chart-line"></i>
                        <?php if ($dias_restantes > 0): ?>
                            Si cumple los <?= $dias_restantes ?> días que faltan: <b><?= cop($proyeccion) ?></b>
                        <?php else: ?>
                            Mes cerrado: <b><?= cop($dinero_acumulado) ?></b>
                        <?php endif; ?>
                        <br><small><?= cop($bono_diario) ?> por día cumplido (<?= cop($bono_mensual_total) ?> ÷ <?= $dias_habiles ?> días)<?= $tope_alcanzado ? ' · tope mensual alcanzado' : '' ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card success-stat">
                <div class="stat-icon-small"><i class="fas fa-check"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Días cumplidos</span>
                    <span class="stat-value"><?= $dias_logrados ?></span>
                    <span class="stat-desc">+<?= cop($dinero_acumulado) ?> ganados</span>
                </div>
            </div>
            <div class="stat-card danger-stat">
                <div class="stat-icon-small"><i class="fas fa-times"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Días tachados</span>
                    <span class="stat-value"><?= $dias_fallados ?></span>
                    <span class="stat-desc">Dejó de sumar <?= cop($dias_fallados * $bono_diario) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="history-section">
        <div class="history-header">
            <h3><i class="fas fa-calendar-alt" style="color: #FFD700; margin-right: 10px;"></i> Calendario del mes</h3>
            <div class="leyenda">
                <span><i class="fas fa-check-circle" style="color:#16a34a"></i>Sumó</span>
                <span><i class="fas fa-times-circle" style="color:#dc2626"></i>Tachado</span>
                <span><i class="far fa-circle" style="color:#bbb"></i>Sin datos</span>
            </div>
        </div>
        <div class="cal">
            <?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $h): ?><div class="cal-h"><?= $h ?></div><?php endforeach; ?>
            <?php
            $offset = (int)date('N', strtotime($inicio)) - 1;
            for ($i = 0; $i < $offset; $i++) echo '<div class="cal-d vacio"></div>';
            for ($d = 1; $d <= $dias_mes; $d++):
                $f = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                $info = $dias[$f] ?? null;
                $cls = ['cal-d'];
                if ($info) $cls[] = $info['cumple'] ? 'ok' : 'fail';
                if ($f === $hoy) $cls[] = 'hoy';
                if ($f > $hoy) $cls[] = 'futuro';
                if (date('N', strtotime($f)) == 7) $cls[] = 'domingo';
                $title = $info ? ($info['cumple'] ? 'Sumó ' . cop($bono_diario) : 'No sumó: ' . implode(', ', $info['fallas'])) : 'Sin registros';
            ?>
                <div class="<?= implode(' ', $cls) ?>" title="<?= htmlspecialchars($title) ?>" <?= $info ? 'data-fecha="' . $f . '"' : '' ?>>
                    <span class="num"><?= $d ?></span>
                    <span class="ico"><?= $info ? ($info['cumple'] ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') : '' ?></span>
                    <span class="amt"><?= $info ? ($info['cumple'] ? '+' : '') . cop($bono_diario) : '' ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="history-section">
        <div class="history-header">
            <h3><i class="fas fa-list-ul" style="color: #FFD700; margin-right: 10px;"></i> Detalle por día</h3>
        </div>
        <?php if (empty($dias)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Aún no hay productividades ni indicadores de turno registrados para calcular la compensación de este mes.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="det">
                    <thead>
                        <tr><th>Fecha</th><th>Productividades</th><th>Indicadores de turno</th><th>Estado</th><th style="text-align:right">Bono</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_reverse($dias, true) as $f => $info): ?>
                        <tr id="d-<?= $f ?>">
                            <td><strong><?= date('d/m/Y', strtotime($f)) ?></strong></td>
                            <td>
                                <?php if (!$info['prod']): ?><span class="badge badge-neutral">—</span><?php endif; ?>
                                <?php foreach ($info['prod'] as $p): ?>
                                    <span class="badge <?= $p['ok'] ? 'badge-success' : 'badge-danger' ?>"><i class="fas <?= $p['ok'] ? 'fa-check' : 'fa-times' ?>"></i> <?= htmlspecialchars($p['nombre']) ?> (<?= $p['registros'] ?>)</span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if (!$info['ind']): ?><span class="badge badge-neutral">—</span><?php endif; ?>
                                <?php foreach ($info['ind'] as $p):
                                    $cls = $p['estado'] === 'cumple' ? 'badge-success' : ($p['estado'] === 'disparador' ? 'badge-trigger' : 'badge-danger');
                                    $op = $p['sentido'] === 'menor' ? '≤' : '≥';
                                ?>
                                    <span class="badge <?= $cls ?>" title="Meta <?= $op ?> <?= $p['meta'] !== null ? (float)$p['meta'] : '—' ?><?= $p['disparador'] !== null ? ' · disparador ' . (float)$p['disparador'] : '' ?>">
                                        <i class="fas <?= $p['ok'] ? 'fa-check' : ($p['estado'] === 'disparador' ? 'fa-bell' : 'fa-times') ?>"></i>
                                        T<?= htmlspecialchars($p['turno']) ?> <?= htmlspecialchars($p['nombre']) ?>: <?= number_format($p['valor'], 2, ',', '.') ?> <?= htmlspecialchars($p['unidad'] ?? '') ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if ($info['cumple']): ?>
                                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Cumplió</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Tachado</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($info['cumple']): ?>
                                    <span class="monto-ganado">+<?= cop($bono_diario) ?></span>
                                <?php else: ?>
                                    <span style="color:#aaa; text-decoration: line-through;"><?= cop($bono_diario) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($es_gestor): ?>
<script>window.WAREPRO_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/public/js/person-select.js"></script>
<script>
    (function () {
        var sel = document.getElementById('selAuxiliar');
        sel.addEventListener('change', function () {
            if (!sel.value || sel.value == <?= (int)$persona['id'] ?>) return;
            location.href = '?usuario_id=' + encodeURIComponent(sel.value) + '&mes=<?= $mes_param ?>';
        });
    })();
</script>
<?php endif; ?>
<script>
    document.querySelectorAll('.cal-d[data-fecha]').forEach(function (c) {
        c.addEventListener('click', function () {
            var row = document.getElementById('d-' + c.dataset.fecha);
            if (!row) return;
            document.querySelectorAll('tr.sel').forEach(function (r) { r.classList.remove('sel'); });
            row.classList.add('sel');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
</script>
</body>
</html>
