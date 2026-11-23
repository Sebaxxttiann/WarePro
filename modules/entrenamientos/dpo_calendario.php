<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/dpo.php';
date_default_timezone_set('America/Bogota');

if (!dpoEsAdmin()) { header('Location: index.php'); exit; }

$op = (int)getOperacionActiva();
$pdo->exec("SET time_zone = '-05:00'");

// Mes a mostrar (?mes=YYYY-MM), sin pasar del mes actual
$mesParam = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^(\d{4})-(\d{2})$/', $mesParam, $m) || (int)$m[2] < 1 || (int)$m[2] > 12 || $mesParam > date('Y-m')) {
    $mesParam = date('Y-m');
    preg_match('/^(\d{4})-(\d{2})$/', $mesParam, $m);
}
$anio = (int)$m[1];
$mes = (int)$m[2];
$M = dpoMatrizMes($pdo, $op, $anio, $mes);

$stmt = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(peso_bytes), 0) AS peso, COUNT(DISTINCT colaborador_id) AS personas
                       FROM dpo_registros WHERE operacion_id = ? AND fecha BETWEEN ? AND ?");
$stmt->execute([$op, sprintf('%04d-%02d-01', $anio, $mes), sprintf('%04d-%02d-%02d', $anio, $mes, $M['dias_mes'])]);
$resumen = $stmt->fetch(PDO::FETCH_ASSOC);

$MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$LETRA = ['', 'L', 'M', 'X', 'J', 'V', 'S', 'D'];
$mesAnterior = date('Y-m', strtotime(sprintf('%04d-%02d-01 -1 month', $anio, $mes)));
$mesSiguiente = date('Y-m', strtotime(sprintf('%04d-%02d-01 +1 month', $anio, $mes)));
$hayMesSiguiente = $mesSiguiente <= date('Y-m');

function pctTxt(?int $p): string { return $p === null ? '—' : $p . '%'; }

/** Tabla de cumplimiento (se reutiliza para refrescar sin recargar la página). */
function tablaCalendario(array $M, array $LETRA): void {
    $numSemana = 0; ?>
    <table class="cal">
        <thead><tr>
            <th class="cal-nom">Líder</th>
            <?php foreach ($M['dias'] as $d => $info):
                $cls = ['cal-dia'];
                if ($info['inactivo']) $cls[] = 'off';
                if ($info['hoy']) $cls[] = 'hoy';
                $titulo = $info['festivo'] ? 'Festivo: ' . $info['festivo'] : ($info['bloqueado'] ? 'Día no laborable (clic para habilitar)' : ($info['domingo'] ? 'Domingo' : 'Clic para marcar como no laborable'));
            ?>
                <th class="<?= implode(' ', $cls) ?>" title="<?= htmlspecialchars($titulo) ?>"
                    <?= (!$info['domingo'] && !$info['festivo']) ? 'data-dia="' . $info['fecha'] . '" data-bloq="' . (int)$info['bloqueado'] . '"' : '' ?>>
                    <small><?= $LETRA[$info['dow']] ?></small><?= $d ?>
                    <?php if ($info['festivo']): ?><i class="cal-flag fas fa-flag"></i><?php elseif ($info['bloqueado']): ?><i class="cal-flag fas fa-ban"></i><?php endif; ?>
                </th>
                <?php if ($info['fin_semana']): $numSemana++; ?><th class="cal-sem">S<?= $numSemana ?></th><?php endif; ?>
            <?php endforeach; ?>
            <th class="cal-mtd">Mes</th>
        </tr></thead>
        <tbody>
        <?php if (!$M['lideres']): ?>
            <tr><td colspan="<?= $M['dias_mes'] + $numSemana + 2 ?>" class="cal-vacio">
                <i class="fas fa-user-plus"></i> Aún no hay líderes. Usa <b>Líderes</b> para elegir quiénes registran entrenamientos.
            </td></tr>
        <?php endif; ?>
        <?php foreach ($M['lideres'] as $l): ?>
            <tr>
                <td class="cal-nom"><b><?= htmlspecialchars($l['nombre']) ?></b><span><?= htmlspecialchars($l['cargo'] ?? '') ?></span></td>
                <?php foreach ($M['dias'] as $d => $info):
                    $c = $l['celdas'][$d];
                    $txt = ['cumple' => $c['n'] > 1 ? $c['n'] : '<i class="fas fa-check"></i>', 'no_cumple' => '<i class="fas fa-times"></i>', 'na' => 'N/A', 'futuro' => '', 'off' => ''][$c['estado']];
                    $clic = $c['estado'] !== 'off' || $c['n'] > 0;
                ?>
                    <td class="cal-c c-<?= $c['estado'] ?><?= $info['hoy'] ? ' hoy' : '' ?>"
                        <?php if ($clic): ?>data-lider="<?= (int)$l['id'] ?>" data-fecha="<?= $info['fecha'] ?>" data-estado="<?= $c['estado'] ?>" data-n="<?= $c['n'] ?>" data-nom="<?= htmlspecialchars($l['nombre']) ?>"<?php endif; ?>
                        title="<?= $c['n'] ?> registro<?= $c['n'] == 1 ? '' : 's' ?>"><?= $txt ?><?php if ($c['estado'] === 'off' && $c['n'] > 0): ?><span class="cal-extra"><?= $c['n'] ?></span><?php endif; ?></td>
                    <?php if ($info['fin_semana']): $s = $l['semanas'][$d]; ?>
                        <td class="cal-sem <?= dpoClasePct($s['pct']) ?>" title="<?= $s['ok'] ?> de <?= $s['val'] ?> días"><?= pctTxt($s['pct']) ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
                <td class="cal-mtd <?= dpoClasePct($l['pct']) ?>" title="<?= $l['ok'] ?> de <?= $l['val'] ?> días"><?= pctTxt($l['pct']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($M['lideres']): ?>
        <tfoot><tr>
            <td class="cal-nom"><b>Total equipo</b></td>
            <?php foreach ($M['dias'] as $d => $info):
                $t = $M['tot_dia'][$d];
                $p = $t['val'] ? (int)round($t['ok'] * 100 / $t['val']) : null; ?>
                <td class="cal-tot <?= $p === null ? '' : dpoClasePct($p) ?>"><?= $p === null ? '' : $p . '%' ?></td>
                <?php if ($info['fin_semana']): $t = $M['tot_semana'][$d]; $p = $t['val'] ? (int)round($t['ok'] * 100 / $t['val']) : null; ?>
                    <td class="cal-sem <?= dpoClasePct($p) ?>"><?= pctTxt($p) ?></td>
                <?php endif; ?>
            <?php endforeach; ?>
            <td class="cal-mtd <?= dpoClasePct($M['global']['pct']) ?>"><?= pctTxt($M['global']['pct']) ?></td>
        </tr></tfoot>
        <?php endif; ?>
    </table>
<?php }

// Refresco parcial: solo la tabla y los indicadores
if (isset($_GET['parcial'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_start(); tablaCalendario($M, $LETRA); $html = ob_get_clean();
    echo json_encode(['html' => $html, 'global' => $M['global'], 'lideres' => count($M['lideres']), 'registros' => (int)$resumen['n']], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once '../../core/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/entrenamientos.css">
<style>
    .cal-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 18px; }
    .cal-kpi { background: #fff; border-radius: 16px; padding: 16px 18px; box-shadow: 0 6px 22px rgba(0,0,0,.06); display: flex; align-items: center; gap: 14px; }
    .cal-kpi .ico { width: 46px; height: 46px; border-radius: 13px; background: #111; color: #FFD700; display: grid; place-items: center; font-size: 1.15rem; flex-shrink: 0; }
    .cal-kpi b { display: block; font-size: 1.5rem; line-height: 1.1; }
    .cal-kpi span { font-size: .76rem; color: #777; font-weight: 600; }
    .cal-kpi.anillo .ico { background: conic-gradient(var(--c, #FFD700) calc(var(--p, 0) * 1%), #eee 0); color: #111; position: relative; }
    .cal-kpi.anillo .ico::after { content: ''; position: absolute; inset: 6px; background: #fff; border-radius: 9px; }
    .cal-kpi.anillo .ico i { position: relative; z-index: 1; }

    .cal-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
    .cal-mes { display: flex; align-items: center; gap: 8px; }
    .cal-mes h2 { margin: 0 6px; font-size: 1.15rem; min-width: 150px; text-align: center; }
    .cal-mes a, .cal-mes span.dis { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: #111; color: #FFD700; text-decoration: none; }
    .cal-mes span.dis { background: #e5e7eb; color: #aaa; }
    .cal-ley { display: flex; flex-wrap: wrap; gap: 12px; font-size: .74rem; color: #666; margin-top: 12px; }
    .cal-ley i { display: inline-block; width: 14px; height: 14px; border-radius: 4px; vertical-align: -2px; margin-right: 4px; }

    .cal-scroll { overflow-x: auto; border-radius: 14px; border: 1px solid #eee; position: relative; }
    .cal-scroll.cargando { opacity: .5; pointer-events: none; }
    .cal { border-collapse: separate; border-spacing: 0; font-size: .78rem; width: 100%; }
    .cal th, .cal td { text-align: center; padding: 0; height: 38px; min-width: 34px; border-bottom: 1px solid #f0f0f0; }
    .cal thead th { background: #111; color: #fff; font-weight: 600; position: sticky; top: 0; z-index: 2; line-height: 1.1; padding: 6px 2px; }
    .cal thead th small { display: block; font-size: .62rem; color: #FFD700; font-weight: 500; }
    .cal th.cal-dia[data-dia] { cursor: pointer; }
    .cal th.cal-dia[data-dia]:hover { background: #333; }
    .cal th.off { background: #3a3a3a; color: #999; }
    .cal th.hoy { background: #FFD700; color: #000; }
    .cal th.hoy small { color: #000; }
    .cal-flag { display: block; font-size: .55rem; margin-top: 2px; color: #f87171; }
    .cal .cal-nom { position: sticky; left: 0; z-index: 1; background: #fff; text-align: left; padding: 6px 12px; min-width: 190px; max-width: 220px; border-right: 2px solid #f0f0f0; }
    .cal thead .cal-nom { background: #111; z-index: 3; }
    .cal .cal-nom b { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: .82rem; }
    .cal .cal-nom span { display: block; font-size: .66rem; color: #999; text-transform: capitalize; }
    .cal-c { cursor: pointer; font-weight: 700; transition: filter .15s; position: relative; }
    .cal-c:hover { filter: brightness(.93); }
    .c-cumple { background: #dcfce7; color: #15803d; }
    .c-no_cumple { background: #fee2e2; color: #dc2626; }
    .c-na { background: #f3f4f6; color: #9ca3af; font-size: .62rem; }
    .c-futuro { background: #fff; cursor: default; }
    .c-futuro[data-n="0"] { cursor: pointer; }
    .c-off { background: repeating-linear-gradient(45deg, #fafafa, #fafafa 4px, #f0f0f0 4px, #f0f0f0 8px); cursor: default; }
    .cal-c.hoy { box-shadow: inset 0 0 0 2px #FFD700; }
    .cal-extra { position: absolute; top: 2px; right: 3px; font-size: .58rem; background: #111; color: #FFD700; border-radius: 99px; padding: 0 4px; }
    .cal-sem, .cal-mtd { font-weight: 700; min-width: 48px !important; border-left: 2px solid #fff; }
    .cal thead .cal-sem, .cal thead .cal-mtd { background: #222; color: #FFD700; }
    .cal-mtd { min-width: 60px !important; font-size: .85rem; }
    .cal tfoot td { background: #fafafa; font-weight: 700; border-top: 2px solid #111; }
    .cal tfoot .cal-nom { background: #fafafa; }
    .cal-tot { font-size: .64rem; }
    .p-ok { background: #16a34a !important; color: #fff; }
    .p-mid { background: #FFD700 !important; color: #000; }
    .p-bad { background: #dc2626 !important; color: #fff; }
    .p-na { background: #f3f4f6 !important; color: #9ca3af; }
    .cal-vacio { padding: 40px 16px !important; color: #777; height: auto !important; }
    .cal-vacio i { color: #FFD700; margin-right: 6px; }

    .ld-add { display: flex; gap: 10px; align-items: flex-start; }
    .ld-add > div { flex: 1; min-width: 0; }
    .ld-lista { list-style: none; margin: 16px 0 0; padding: 0; max-height: 50vh; overflow-y: auto; }
    .ld-lista li { display: flex; align-items: center; gap: 12px; padding: 10px 4px; border-bottom: 1px solid #f1f1f1; }
    .ld-lista .av { width: 36px; height: 36px; border-radius: 50%; background: #FFD700; display: grid; place-items: center; font-weight: 700; flex-shrink: 0; }
    .ld-lista .tx { flex: 1; min-width: 0; font-size: .86rem; }
    .ld-lista .tx span { display: block; color: #888; font-size: .74rem; }
    .cel-regs { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-top: 14px; }
    .cel-reg { border: 1px solid #eee; border-radius: 12px; overflow: hidden; }
    .cel-reg .en-thumb { border-radius: 0; }
    .cel-reg .tx { padding: 8px 10px; font-size: .76rem; }
    .cel-reg .tx b { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cel-reg .bt { display: flex; gap: 6px; padding: 0 10px 10px; }
    .cel-reg .bt .en-btn { flex: 1; }
    @media (max-width: 640px) { .cal .cal-nom { min-width: 130px; } .ld-add { flex-direction: column; } .ld-add .en-btn { width: 100%; } }
</style>

<div class="en-wrap">
    <section class="en-hero">
        <div class="en-hero-top">
            <div>
                <h1><i class="fas fa-calendar-check"></i> Calendario DPO</h1>
                <p>Un día cumple cuando el líder registra al menos un entrenamiento. Domingos, festivos y días no laborables no cuentan.</p>
            </div>
            <div class="en-acciones">
                <button type="button" class="en-btn en-btn-gold" id="btnLideres"><i class="fas fa-users-cog"></i> Líderes</button>
                <a class="en-chip" href="dpo_evidencias.php"><i class="fas fa-images"></i> Evidencias</a>
                <a class="en-chip" href="index.php"><i class="fas fa-arrow-left"></i> Entrenamientos</a>
            </div>
        </div>
    </section>

    <?php $g = $M['global']; $colorG = $g['pct'] === null ? '#d1d5db' : ($g['pct'] >= 100 ? '#16a34a' : ($g['pct'] >= 75 ? '#FFD700' : '#dc2626')); ?>
    <div class="cal-kpis">
        <div class="cal-kpi anillo" id="kpiAnillo" style="--p:<?= (int)$g['pct'] ?>;--c:<?= $colorG ?>"><div class="ico"><i class="fas fa-bullseye"></i></div><div><b id="kpiPct"><?= pctTxt($g['pct']) ?></b><span>Cumplimiento del mes</span></div></div>
        <div class="cal-kpi"><div class="ico"><i class="fas fa-user-tie"></i></div><div><b id="kpiLideres"><?= count($M['lideres']) ?></b><span>Líderes en seguimiento</span></div></div>
        <div class="cal-kpi"><div class="ico"><i class="fas fa-photo-video"></i></div><div><b id="kpiRegistros"><?= (int)$resumen['n'] ?></b><span>Registros del mes</span></div></div>
        <div class="cal-kpi"><div class="ico"><i class="fas fa-user-graduate"></i></div><div><b><?= (int)$resumen['personas'] ?></b><span>Personas entrenadas</span></div></div>
        <div class="cal-kpi"><div class="ico"><i class="fas fa-hdd"></i></div><div><b><?php $pb = (int)$resumen['peso']; echo $pb >= 1073741824 ? round($pb / 1073741824, 2) . ' GB' : ($pb >= 1048576 ? round($pb / 1048576) . ' MB' : round($pb / 1024) . ' KB'); ?></b><span>Espacio usado en el mes</span></div></div>
    </div>

    <div class="en-card">
        <div class="cal-bar">
            <div class="cal-mes">
                <a href="?mes=<?= $mesAnterior ?>" title="Mes anterior"><i class="fas fa-chevron-left"></i></a>
                <h2><?= $MESES[$mes] . ' ' . $anio ?></h2>
                <?php if ($hayMesSiguiente): ?><a href="?mes=<?= $mesSiguiente ?>" title="Mes siguiente"><i class="fas fa-chevron-right"></i></a><?php else: ?><span class="dis"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
            </div>
            <span class="en-sub" style="margin:0">Clic en un <b>día</b> para marcarlo no laborable · clic en una <b>celda</b> para ver o gestionar</span>
        </div>
        <div class="cal-scroll" id="calScroll"><?php tablaCalendario($M, $LETRA); ?></div>
        <div class="cal-ley">
            <span><i style="background:#dcfce7"></i>Cumple</span>
            <span><i style="background:#fee2e2"></i>No cumple</span>
            <span><i style="background:#f3f4f6"></i>N/A (excusado)</span>
            <span><i style="background:repeating-linear-gradient(45deg,#fafafa,#fafafa 3px,#e5e5e5 3px,#e5e5e5 6px)"></i>Domingo / festivo / no laborable</span>
            <span><i style="background:#16a34a"></i>100%</span>
            <span><i style="background:#FFD700"></i>75–99%</span>
            <span><i style="background:#dc2626"></i>&lt; 75%</span>
        </div>
    </div>
</div>

<!-- Líderes -->
<div class="en-modal" id="modalLideres" hidden>
    <div class="en-modal-box">
        <div class="en-modal-head"><h3><i class="fas fa-users-cog"></i> Líderes DPO</h3><button type="button" class="en-modal-close" data-cerrar>&times;</button></div>
        <div class="en-modal-body">
            <p class="en-sub" style="margin-top:0">Los líderes pueden registrar entrenamientos y aparecen en el calendario. Al retirarlos se conserva su historial.</p>
            <div class="ld-add">
                <div><select id="nuevoLider" data-person-select data-value-field="id" data-placeholder="Buscar usuario por nombre o cédula..."></select></div>
                <button type="button" class="en-btn en-btn-dark" id="btnAgregar"><i class="fas fa-plus"></i> Agregar</button>
            </div>
            <ul class="ld-lista" id="listaLideres"></ul>
        </div>
    </div>
</div>

<!-- Celda -->
<div class="en-modal" id="modalCelda" hidden>
    <div class="en-modal-box ancho">
        <div class="en-modal-head"><h3 id="celTitulo"></h3><button type="button" class="en-modal-close" data-cerrar>&times;</button></div>
        <div class="en-modal-body">
            <div class="en-acciones" id="celAcciones"></div>
            <div class="cel-regs" id="celRegs"></div>
        </div>
    </div>
</div>

<!-- Visor -->
<div class="en-modal" id="modalVer" hidden style="z-index:2100">
    <div class="en-modal-box ancho"><div class="en-modal-head"><h3 id="verTitulo"></h3><button type="button" class="en-modal-close" data-cerrar>&times;</button></div>
        <div class="en-modal-body"><div class="en-visor" id="verVisor"></div></div></div>
</div>

<script>window.WAREPRO_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/public/js/person-select.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script>
(function () {
    const API = WAREPRO_BASE_URL + '/api/entrenamientos/dpo.php';
    const RAIZ = WAREPRO_BASE_URL + '/';
    const MES = <?= json_encode(sprintf('%04d-%02d', $anio, $mes)) ?>;
    const HOY = <?= json_encode(date('Y-m-d')) ?>;
    const $ = s => document.querySelector(s);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = f => f.split('-').reverse().join('/');
    const toast = (icon, title) => Swal.fire({ toast: true, position: 'top-end', icon, title, timer: 2200, showConfirmButton: false });

    async function post(datos) {
        const fd = new FormData();
        Object.entries(datos).forEach(([k, v]) => fd.append(k, v));
        const r = await fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json().catch(() => ({ success: false, message: 'Respuesta inválida del servidor.' }));
        if (!j.success) throw new Error(j.message || 'No se pudo completar.');
        return j;
    }

    // ---------- Refresco parcial de la tabla (sin recargar la página) ----------
    async function refrescar() {
        const sc = $('#calScroll'), x = sc.scrollLeft;
        sc.classList.add('cargando');
        try {
            const j = await (await fetch('?mes=' + MES + '&parcial=1', { credentials: 'same-origin' })).json();
            sc.innerHTML = j.html; sc.scrollLeft = x;
            const p = j.global.pct;
            $('#kpiPct').textContent = p === null ? '—' : p + '%';
            $('#kpiAnillo').style.setProperty('--p', p || 0);
            $('#kpiAnillo').style.setProperty('--c', p === null ? '#d1d5db' : p >= 100 ? '#16a34a' : p >= 75 ? '#FFD700' : '#dc2626');
            $('#kpiLideres').textContent = j.lideres;
            $('#kpiRegistros').textContent = j.registros;
        } finally { sc.classList.remove('cargando'); }
    }

    // Lleva el scroll hasta hoy en pantallas pequeñas
    const hoyTh = document.querySelector('.cal th.hoy');
    if (hoyTh) $('#calScroll').scrollLeft = Math.max(0, hoyTh.offsetLeft - $('#calScroll').clientWidth / 2);

    // ---------- Día no laborable ----------
    $('#calScroll').addEventListener('click', async e => {
        const th = e.target.closest('th[data-dia]');
        if (th) {
            const bloq = th.dataset.bloq === '1';
            const r = await Swal.fire({
                title: (bloq ? 'Habilitar ' : 'Marcar no laborable ') + fmt(th.dataset.dia),
                text: bloq ? 'El día volverá a contar para el cumplimiento de todos los líderes.' : 'El día dejará de contar para todos los líderes.',
                icon: 'question', showCancelButton: true, confirmButtonText: bloq ? 'Habilitar' : 'Marcar', cancelButtonText: 'Cancelar', confirmButtonColor: '#111'
            });
            if (!r.isConfirmed) return;
            try { await post({ action: 'toggle_dia', fecha: th.dataset.dia }); await refrescar(); }
            catch (err) { toast('error', err.message); }
            return;
        }
        const td = e.target.closest('td[data-lider]');
        if (td) abrirCelda(td.dataset);
    });

    // ---------- Celda: excusar / ver evidencias / corregir ----------
    let celda = null;
    async function abrirCelda(d) {
        celda = d;
        $('#celTitulo').textContent = d.nom + ' · ' + fmt(d.fecha);
        let acc = '';
        if (d.estado === 'na') acc += '<button type="button" class="en-btn en-btn-ghost en-btn-sm" data-acc="toggle"><i class="fas fa-undo"></i> Quitar N/A</button>';
        else if (d.estado !== 'off' && +d.n === 0) acc += '<button type="button" class="en-btn en-btn-ghost en-btn-sm" data-acc="toggle"><i class="fas fa-user-slash"></i> Marcar N/A (vacaciones, incapacidad...)</button>';
        if (+d.n > 0) acc += '<a class="en-btn en-btn-ghost en-btn-sm" href="dpo_evidencias.php?lider=' + d.lider + '&fecha=' + d.fecha + '"><i class="fas fa-images"></i> Ver en evidencias</a>';
        $('#celAcciones').innerHTML = acc;
        $('#celRegs').innerHTML = +d.n > 0 ? '<p class="en-sub">Cargando...</p>' : '<p class="en-sub" style="grid-column:1/-1;margin:14px 0 0">Sin registros este día.</p>';
        $('#modalCelda').hidden = false;
        if (+d.n === 0) return;
        try {
            const j = await (await fetch(API + '?action=registros_celda&lider_id=' + d.lider + '&fecha=' + d.fecha, { credentials: 'same-origin' })).json();
            $('#celRegs').innerHTML = j.data.map(r => '<div class="cel-reg">' +
                '<button type="button" class="en-thumb" data-ver="' + esc(r.archivo) + '" data-tipo="' + r.tipo + '" data-nom="' + esc(r.colaborador) + '">' +
                (r.miniatura ? '<img loading="lazy" src="' + RAIZ + esc(r.miniatura) + '" alt="">' : '<span class="en-sin"><i class="fas fa-' + (r.tipo === 'video' ? 'film' : 'image') + '"></i></span>') +
                (r.tipo === 'video' ? '<span class="en-play"><i class="fas fa-play-circle"></i></span>' : '') + '</button>' +
                '<div class="tx"><b>' + esc(r.colaborador) + '</b>' + esc(r.created_at.slice(11, 16)) + (r.observacion ? ' · ' + esc(r.observacion) : '') + '</div>' +
                '<div class="bt"><button type="button" class="en-btn en-btn-ghost en-btn-sm" data-mover="' + r.id + '" title="Cambiar fecha"><i class="fas fa-calendar-alt"></i></button>' +
                '<button type="button" class="en-btn en-btn-danger en-btn-sm" data-borrar="' + r.id + '" title="Eliminar"><i class="fas fa-trash"></i></button></div></div>').join('');
        } catch (e) { $('#celRegs').innerHTML = '<p class="en-sub">No se pudieron cargar los registros.</p>'; }
    }

    $('#modalCelda').addEventListener('click', async e => {
        if (e.target.closest('[data-acc="toggle"]')) {
            try { await post({ action: 'toggle_celda', lider_id: celda.lider, fecha: celda.fecha }); cerrar($('#modalCelda')); await refrescar(); }
            catch (err) { toast('error', err.message); }
            return;
        }
        const ver = e.target.closest('[data-ver]');
        if (ver) {
            $('#verTitulo').textContent = ver.dataset.nom;
            $('#verVisor').innerHTML = ver.dataset.tipo === 'video'
                ? '<video src="' + RAIZ + ver.dataset.ver + '" controls autoplay playsinline preload="metadata"></video>'
                : '<img src="' + RAIZ + ver.dataset.ver + '" alt="">';
            $('#modalVer').hidden = false;
            return;
        }
        const mover = e.target.closest('[data-mover]');
        if (mover) {
            const r = await Swal.fire({ title: 'Mover a otra fecha', input: 'date', inputValue: celda.fecha, inputAttributes: { max: HOY },
                showCancelButton: true, confirmButtonText: 'Mover', cancelButtonText: 'Cancelar', confirmButtonColor: '#111' });
            if (!r.isConfirmed || !r.value || r.value === celda.fecha) return;
            try { const j = await post({ action: 'cambiar_fecha', id: mover.dataset.mover, fecha: r.value }); toast('success', j.message); cerrar($('#modalCelda')); await refrescar(); }
            catch (err) { toast('error', err.message); }
            return;
        }
        const borrar = e.target.closest('[data-borrar]');
        if (borrar) {
            const r = await Swal.fire({ title: '¿Eliminar este registro?', text: 'Se borra también la foto o el video. No se puede deshacer.', icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc2626' });
            if (!r.isConfirmed) return;
            try { const j = await post({ action: 'eliminar', id: borrar.dataset.borrar }); toast('success', j.message); cerrar($('#modalCelda')); await refrescar(); }
            catch (err) { toast('error', err.message); }
        }
    });

    // ---------- Líderes ----------
    async function cargarLideres() {
        const ul = $('#listaLideres');
        ul.innerHTML = '<li class="en-sub">Cargando...</li>';
        const j = await (await fetch(API + '?action=lideres', { credentials: 'same-origin' })).json();
        ul.innerHTML = j.data && j.data.length ? j.data.map(l =>
            '<li><span class="av">' + esc(l.nombre.charAt(0).toUpperCase()) + '</span><span class="tx"><b>' + esc(l.nombre) + '</b><span>' + esc(l.cedula) + ' · ' + esc(l.cargo) + '</span></span>' +
            '<button type="button" class="en-btn en-btn-danger en-btn-sm" data-quitar="' + l.id + '" data-nom="' + esc(l.nombre) + '"><i class="fas fa-user-minus"></i> Retirar</button></li>').join('')
            : '<li class="en-sub">Todavía no hay líderes. Busca un usuario arriba y agrégalo.</li>';
    }
    let cambiosLideres = false;
    $('#btnLideres').addEventListener('click', () => { $('#modalLideres').hidden = false; cambiosLideres = false; cargarLideres(); });
    $('#btnAgregar').addEventListener('click', async () => {
        const sel = $('#nuevoLider');
        if (!sel.value) return toast('info', 'Busca y selecciona un usuario.');
        try { const j = await post({ action: 'agregar_lider', usuario_id: sel.value }); toast('success', j.message); PersonSelect.setValue(sel, ''); cambiosLideres = true; cargarLideres(); }
        catch (err) { toast('error', err.message); }
    });
    $('#listaLideres').addEventListener('click', async e => {
        const b = e.target.closest('[data-quitar]'); if (!b) return;
        const r = await Swal.fire({ title: '¿Retirar a ' + b.dataset.nom + '?', text: 'Ya no podrá registrar ni aparecerá en el calendario. Sus evidencias se conservan.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Retirar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc2626' });
        if (!r.isConfirmed) return;
        try { await post({ action: 'quitar_lider', usuario_id: b.dataset.quitar }); cambiosLideres = true; cargarLideres(); }
        catch (err) { toast('error', err.message); }
    });

    // ---------- Modales ----------
    function cerrar(modal) {
        modal.hidden = true;
        if (modal.id === 'modalVer') $('#verVisor').innerHTML = '';
        if (modal.id === 'modalLideres' && cambiosLideres) refrescar();
    }
    document.querySelectorAll('.en-modal').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m || e.target.closest('[data-cerrar]')) cerrar(m); });
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        const abierto = [...document.querySelectorAll('.en-modal:not([hidden])')].pop();
        if (abierto && !document.querySelector('.swal2-container')) cerrar(abierto);
    });
})();
</script>
</body>
</html>
