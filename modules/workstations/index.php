<?php
require_once '../../core/config.php';
verificarLogin();
require_once __DIR__ . '/ws_common.php';
date_default_timezone_set('America/Bogota');

$operacion_id = wsOperacionId();
$catalogo = wsCatalogo();

/** Resúmenes de hoy de todas las workstations de la operación activa. */
function wsResumenes(PDO $pdo, int $op, array $catalogo): array {
    $pdo->exec("SET time_zone = '-05:00'");
    $out = [];
    foreach (array_keys($catalogo) as $key) {
        try {
            $out[$key] = wsResumenHoy($pdo, $op, $key);
        } catch (Throwable $e) {
            $out[$key] = ['meta_txt' => '—', 'total_txt' => '—', 'detalle' => 'No disponible', 'estado' => 'sin_datos'];
        }
    }
    return $out;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    echo json_encode([
        'hora' => date('h:i a'),
        'resumen' => wsResumenes($pdo, $operacion_id, $catalogo),
    ], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$resumenes = wsResumenes($pdo, $operacion_id, $catalogo);
$estados = [
    'cumple'    => ['Cumpliendo meta', 'fa-circle-check'],
    'no_cumple' => ['Por debajo de la meta', 'fa-triangle-exclamation'],
    'sin_datos' => ['Sin registros hoy', 'fa-clock'],
    'sin_meta'  => ['Sin meta para comparar', 'fa-circle-info'],
];

require_once '../../core/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { font-family: 'Poppins', sans-serif; background: #f4f5f7; margin: 0; }
    .wsh-page *, .wsh-page *::before, .wsh-page *::after { box-sizing: border-box; }
    .wsh-page { max-width: 1400px; margin: 0 auto; padding: 28px 16px 48px; font-family: 'Poppins', sans-serif; color: #222; }
    .wsh-hero { background: linear-gradient(135deg, #000 0%, #1c1c1c 100%); color: #fff; border-radius: 20px; padding: 26px 28px; border-left: 6px solid #FFD700; box-shadow: 0 10px 30px rgba(0,0,0,.18); display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; margin-bottom: 26px; }
    .wsh-hero h1 { font-size: 2rem; font-weight: 800; margin: 0 0 4px; display: flex; align-items: center; gap: 12px; }
    .wsh-hero h1 i { color: #FFD700; }
    .wsh-hero p { margin: 0; color: #bbb; font-size: .98rem; }
    .wsh-hero .wsh-op { color: #FFD700; font-weight: 600; }
    .wsh-hero-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .wsh-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,215,0,.12); color: #FFD700; border: 1px solid rgba(255,215,0,.4); border-radius: 999px; padding: 7px 14px; font-size: .85rem; font-weight: 600; text-decoration: none; }
    a.wsh-chip:hover { background: #FFD700; color: #000; }
    .wsh-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 22px; }
    .wsh-card { background: #fff; border-radius: 18px; box-shadow: 0 8px 25px rgba(0,0,0,.08); overflow: hidden; display: flex; flex-direction: column; border-top: 4px solid #FFD700; transition: transform .25s ease, box-shadow .25s ease; }
    .wsh-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,.14); }
    .wsh-card-head { display: flex; align-items: center; gap: 14px; padding: 20px 20px 10px; }
    .wsh-icon { width: 54px; height: 54px; flex: 0 0 54px; border-radius: 14px; background: #000; color: #FFD700; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; box-shadow: 0 6px 14px rgba(0,0,0,.2); }
    .wsh-card-head h3 { margin: 0; font-size: 1.2rem; font-weight: 700; color: #111; }
    .wsh-desc { margin: 0; padding: 0 20px; color: #666; font-size: .9rem; line-height: 1.5; min-height: 2.7em; }
    .wsh-meta { margin: 14px 20px 0; padding: 8px 12px; background: #fffbe6; border: 1px dashed #e6c200; border-radius: 10px; font-size: .85rem; color: #6b5800; }
    .wsh-meta b { color: #000; }
    .wsh-hoy { padding: 14px 20px 4px; }
    .wsh-hoy-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: #888; font-weight: 600; }
    .wsh-hoy-total { font-size: 1.6rem; font-weight: 800; color: #111; line-height: 1.2; }
    .wsh-hoy-det { font-size: .85rem; color: #555; margin-top: 2px; }
    .wsh-estado { display: inline-flex; align-items: center; gap: 7px; margin: 10px 20px 0; padding: 5px 12px; border-radius: 999px; font-size: .8rem; font-weight: 600; align-self: flex-start; }
    .wsh-estado.cumple { background: #d4edda; color: #155724; }
    .wsh-estado.no_cumple { background: #f8d7da; color: #721c24; }
    .wsh-estado.sin_datos { background: #eceff1; color: #546e7a; }
    .wsh-estado.sin_meta { background: #fff3cd; color: #856404; }
    .wsh-foot { margin-top: auto; padding: 16px 20px 20px; display: flex; gap: 10px; }
    .wsh-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 14px; border-radius: 12px; font-weight: 600; font-size: .92rem; text-decoration: none; transition: all .2s ease; }
    .wsh-btn-primary { background: #000; color: #FFD700; }
    .wsh-btn-primary:hover { background: #FFD700; color: #000; }
    .wsh-btn-ghost { flex: 0 0 auto; background: #f4f4f4; color: #333; }
    .wsh-btn-ghost:hover { background: #e8e8e8; }
    .wsh-nota { margin-top: 22px; font-size: .82rem; color: #777; text-align: center; }
    @media (max-width: 600px) {
        .wsh-hero { padding: 20px; }
        .wsh-hero h1 { font-size: 1.5rem; }
        .wsh-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="wsh-page">
    <div class="wsh-hero">
        <div>
            <h1><i class="fas fa-desktop"></i> Mis Workstations</h1>
            <p>Seguimiento en vivo de cada estación de trabajo &middot; <span class="wsh-op"><?= wsNombreCD($pdo, $operacion_id) ?></span></p>
        </div>
        <div class="wsh-hero-actions">
            <span class="wsh-chip" title="Los datos se actualizan cada minuto"><i class="fas fa-rotate"></i> Actualizado <span id="wsh-hora"><?= wsE(date('h:i a')) ?></span></span>
            <?php if (function_exists('puedeGestionarMetas') && puedeGestionarMetas()): ?>
                <a class="wsh-chip" href="../reportes/metas.php"><i class="fas fa-bullseye"></i> Configurar metas</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="wsh-grid">
        <?php foreach ($catalogo as $key => $ws):
            $r = $resumenes[$key];
            $est = $estados[$r['estado']] ?? $estados['sin_datos']; ?>
        <div class="wsh-card" data-ws="<?= wsE($key) ?>">
            <div class="wsh-card-head">
                <div class="wsh-icon"><i class="fas <?= wsE($ws['icono']) ?>"></i></div>
                <h3><?= wsE($ws['nombre']) ?></h3>
            </div>
            <p class="wsh-desc"><?= wsE($ws['desc']) ?></p>
            <div class="wsh-meta"><i class="fas fa-bullseye"></i> Meta: <b class="js-meta"><?= wsE($r['meta_txt']) ?></b></div>
            <div class="wsh-hoy">
                <div class="wsh-hoy-label">Hoy</div>
                <div class="wsh-hoy-total js-total"><?= wsE($r['total_txt']) ?></div>
                <div class="wsh-hoy-det js-detalle"><?= wsE($r['detalle']) ?></div>
            </div>
            <span class="wsh-estado js-estado <?= wsE($r['estado']) ?>"><i class="fas <?= $est[1] ?>"></i> <span><?= wsE($est[0]) ?></span></span>
            <div class="wsh-foot">
                <a class="wsh-btn wsh-btn-primary" href="<?= wsE($ws['archivo']) ?>" target="_blank" rel="noopener">
                    <i class="fas fa-up-right-from-square"></i> Abrir
                </a>
                <a class="wsh-btn wsh-btn-ghost" href="<?= wsE($ws['archivo']) ?>" title="Abrir en esta pestaña">
                    <i class="fas fa-tv"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="wsh-nota">"Abrir" lanza el tablero en una pestaña nueva, pensado para dejarlo en la pantalla/TV del área (se actualiza solo cada 5 segundos).</p>
</div>

<script>
(function () {
    const ESTADOS = <?= json_encode($estados, JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    async function refrescar() {
        try {
            const res = await fetch('index.php?ajax=1', { cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.resumen) return;
            Object.keys(data.resumen).forEach(function (key) {
                const card = document.querySelector('.wsh-card[data-ws="' + key + '"]');
                if (!card) return;
                const r = data.resumen[key];
                const est = ESTADOS[r.estado] || ESTADOS.sin_datos;
                card.querySelector('.js-meta').textContent = r.meta_txt;
                card.querySelector('.js-total').textContent = r.total_txt;
                card.querySelector('.js-detalle').textContent = r.detalle;
                const pill = card.querySelector('.js-estado');
                pill.className = 'wsh-estado js-estado ' + r.estado;
                pill.querySelector('i').className = 'fas ' + est[1];
                pill.querySelector('span').textContent = est[0];
            });
            if (data.hora) document.getElementById('wsh-hora').textContent = data.hora;
        } catch (e) { /* sin conexión: se reintenta en el próximo ciclo */ }
    }
    setInterval(refrescar, 60000);
})();
</script>
</body>
</html>
