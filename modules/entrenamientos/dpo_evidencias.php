<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/dpo.php';

$esAdmin = dpoEsAdmin();

require_once '../../core/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/entrenamientos.css">
<style>
    .ev-filtros { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end; }
    .ev-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
    .ev-item { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.06); display: flex; flex-direction: column; }
    .ev-item .en-thumb { border-radius: 0; }
    .ev-info { padding: 10px 12px 12px; font-size: .8rem; }
    .ev-info b { display: block; font-size: .88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ev-info span { color: #888; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ev-total { font-size: .85rem; color: #666; margin: 0 0 12px; }
    .ev-det { display: grid; grid-template-columns: auto 1fr; gap: 4px 14px; font-size: .86rem; margin-top: 14px; }
    .ev-det dt { color: #888; font-weight: 600; }
    .ev-det dd { margin: 0; }
</style>

<div class="en-wrap">
    <section class="en-hero">
        <div class="en-hero-top">
            <div>
                <h1><i class="fas fa-images"></i> <?= $esAdmin ? 'Evidencias DPO' : 'Mis evidencias DPO' ?></h1>
                <p>Las miniaturas pesan pocos KB; la foto o el video completo solo se descarga cuando lo abres.</p>
            </div>
            <div class="en-acciones">
                <?php if ($esAdmin): ?><a class="en-chip" href="dpo_calendario.php"><i class="fas fa-calendar-check"></i> Calendario</a><?php endif; ?>
                <a class="en-chip" href="index.php"><i class="fas fa-arrow-left"></i> Entrenamientos</a>
            </div>
        </div>
    </section>

    <form class="en-card ev-filtros" id="filtros">
        <?php if ($esAdmin): ?>
        <div><label class="en-label" for="fLider">Líder</label>
            <select id="fLider" name="lider" class="en-select"><option value="">Todos</option></select></div>
        <?php endif; ?>
        <div><label class="en-label" for="fQ">Persona entrenada</label>
            <input id="fQ" name="q" class="en-input" placeholder="Nombre..."></div>
        <div><label class="en-label" for="fDesde">Desde</label>
            <input id="fDesde" name="desde" type="date" class="en-input" value="<?= date('Y-m-01') ?>"></div>
        <div><label class="en-label" for="fHasta">Hasta</label>
            <input id="fHasta" name="hasta" type="date" class="en-input" value="<?= date('Y-m-d') ?>"></div>
        <div><label class="en-label" for="fTipo">Tipo</label>
            <select id="fTipo" name="tipo" class="en-select"><option value="">Fotos y videos</option><option value="imagen">Fotos</option><option value="video">Videos</option></select></div>
        <div><button type="submit" class="en-btn en-btn-dark" style="width:100%"><i class="fas fa-search"></i> Filtrar</button></div>
    </form>

    <p class="ev-total" id="total"></p>
    <div class="ev-grid" id="grid"></div>
    <div class="en-paginas" id="paginas"></div>
</div>

<div class="en-modal" id="modalVer" hidden>
    <div class="en-modal-box ancho">
        <div class="en-modal-head"><h3 id="verTitulo"></h3><button type="button" class="en-modal-close" data-cerrar>&times;</button></div>
        <div class="en-modal-body">
            <div class="en-visor" id="verVisor"></div>
            <dl class="ev-det" id="verDet"></dl>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE = <?= json_encode(BASE_URL) ?>;
    const API = BASE + '/api/entrenamientos/dpo.php';
    const RAIZ = BASE + '/';
    const ES_ADMIN = <?= $esAdmin ? 'true' : 'false' ?>;
    const $ = s => document.querySelector(s);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmtFecha = f => f.split('-').reverse().join('/');
    const mb = b => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    let pagina = 1, datos = [];

    async function cargar() {
        const p = new URLSearchParams(new FormData($('#filtros')));
        p.set('action', 'evidencias'); p.set('pagina', pagina);
        $('#grid').innerHTML = '<p class="en-sub">Cargando...</p>';
        try {
            const j = await (await fetch(API + '?' + p, { credentials: 'same-origin' })).json();
            if (!j.success) throw new Error(j.message);
            datos = j.data;
            $('#total').textContent = j.total + ' evidencia' + (j.total === 1 ? '' : 's');
            $('#grid').innerHTML = datos.length ? datos.map((r, i) =>
                '<div class="ev-item"><button type="button" class="en-thumb" data-i="' + i + '">' +
                (r.miniatura ? '<img loading="lazy" decoding="async" src="' + RAIZ + esc(r.miniatura) + '" alt="">' : '<span class="en-sin"><i class="fas fa-' + (r.tipo === 'video' ? 'film' : 'image') + '"></i></span>') +
                (r.tipo === 'video' ? '<span class="en-play"><i class="fas fa-play-circle"></i></span>' + (r.duracion_seg ? '<span class="en-tag">' + r.duracion_seg + ' s</span>' : '') : '') +
                '</button><div class="ev-info"><b>' + esc(r.colaborador) + '</b>' +
                (ES_ADMIN ? '<span><i class="fas fa-user-tie"></i> ' + esc(r.lider) + '</span>' : '') +
                '<span>' + fmtFecha(r.fecha) + '</span></div></div>').join('')
                : '<div class="en-card en-empty" style="grid-column:1/-1"><i class="fas fa-folder-open"></i>No hay evidencias con estos filtros.</div>';
            paginar(j.pagina, j.paginas);
        } catch (e) {
            $('#grid').innerHTML = '<div class="en-card en-empty" style="grid-column:1/-1">No se pudieron cargar las evidencias.</div>';
            $('#paginas').innerHTML = '';
        }
    }

    function paginar(act, tot) {
        if (tot <= 1) { $('#paginas').innerHTML = ''; return; }
        const nums = [];
        for (let n = 1; n <= tot; n++) if (n === 1 || n === tot || Math.abs(n - act) <= 2) nums.push(n);
        let html = '<button type="button" data-p="' + (act - 1) + '"' + (act === 1 ? ' disabled' : '') + '>&lsaquo;</button>', prev = 0;
        nums.forEach(n => { if (n - prev > 1) html += '<button type="button" disabled>…</button>'; html += '<button type="button" data-p="' + n + '"' + (n === act ? ' class="on"' : '') + '>' + n + '</button>'; prev = n; });
        html += '<button type="button" data-p="' + (act + 1) + '"' + (act === tot ? ' disabled' : '') + '>&rsaquo;</button>';
        $('#paginas').innerHTML = html;
    }
    $('#paginas').addEventListener('click', e => {
        const b = e.target.closest('[data-p]'); if (!b || b.disabled) return;
        pagina = +b.dataset.p; cargar(); window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    $('#filtros').addEventListener('submit', e => { e.preventDefault(); pagina = 1; cargar(); });

    $('#grid').addEventListener('click', e => {
        const b = e.target.closest('[data-i]'); if (!b) return;
        const r = datos[b.dataset.i];
        $('#verTitulo').textContent = r.colaborador;
        // El archivo completo se pide solo aquí
        $('#verVisor').innerHTML = r.tipo === 'video'
            ? '<video src="' + RAIZ + esc(r.archivo) + '" controls autoplay playsinline preload="metadata"' + (r.miniatura ? ' poster="' + RAIZ + esc(r.miniatura) + '"' : '') + '></video>'
            : '<img src="' + RAIZ + esc(r.archivo) + '" alt="">';
        $('#verDet').innerHTML = '<dt>Fecha</dt><dd>' + fmtFecha(r.fecha) + '</dd>' +
            '<dt>Líder</dt><dd>' + esc(r.lider) + '</dd>' +
            '<dt>Entrenado</dt><dd>' + esc(r.colaborador) + '</dd>' +
            (r.observacion ? '<dt>Observación</dt><dd>' + esc(r.observacion) + '</dd>' : '') +
            '<dt>Peso</dt><dd>' + mb(+r.peso_bytes) + '</dd>' +
            '<dt>Archivo</dt><dd><a href="' + RAIZ + esc(r.archivo) + '" download><i class="fas fa-download"></i> Descargar</a></dd>';
        $('#modalVer').hidden = false;
    });
    function cerrar() { $('#modalVer').hidden = true; $('#verVisor').innerHTML = ''; }
    document.querySelector('[data-cerrar]').addEventListener('click', cerrar);
    $('#modalVer').addEventListener('click', e => { if (e.target.id === 'modalVer') cerrar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !$('#modalVer').hidden) cerrar(); });

    // Desde el calendario se puede llegar con ?lider=ID&fecha=YYYY-MM-DD
    const q = new URLSearchParams(location.search);
    if (/^\d{4}-\d{2}-\d{2}$/.test(q.get('fecha') || '')) $('#fDesde').value = $('#fHasta').value = q.get('fecha');
    if (ES_ADMIN) {
        fetch(API + '?action=lideres', { credentials: 'same-origin' }).then(r => r.json()).then(j => {
            if (j.success) $('#fLider').insertAdjacentHTML('beforeend', j.data.map(l => '<option value="' + l.id + '">' + esc(l.nombre) + '</option>').join(''));
            if (q.get('lider')) $('#fLider').value = q.get('lider');
        }).finally(cargar);
    } else cargar();
})();
</script>
</body>
</html>
