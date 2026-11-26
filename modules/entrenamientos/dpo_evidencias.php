<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/dpo.php';

$esAdmin = dpoEsAdmin();
// admin: todo · lider: lo que registró · personal: solo los entrenamientos que le hicieron
$modo = $esAdmin ? 'admin' : (dpoEsLider($pdo, (int)getOperacionActiva(), (int)$_SESSION['usuario_id']) ? 'lider' : 'personal');

require_once '../../core/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/entrenamientos.css">
<style>
    .ev-filtros { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; align-items: end; }
    .ev-filtros .wp-label { margin-bottom: .5rem; font-size: .85rem; }
    .ev-acciones { display: flex; gap: .6rem; }
    .ev-acciones .wp-btn { flex: 1; padding-top: .95rem; padding-bottom: .95rem; }
    .ev-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 1.25rem; }
    .ev-item { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: var(--wp-sombra); border: 1px solid var(--wp-borde); display: flex; flex-direction: column; transition: transform .3s, box-shadow .3s; }
    .ev-item:hover { transform: translateY(-3px); box-shadow: var(--wp-sombra-lg); }
    .ev-item .en-thumb { border-radius: 0; }
    .ev-info { padding: .75rem .9rem .9rem; font-size: .82rem; }
    .ev-info b { display: block; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ev-info span { color: var(--wp-gris); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ev-total { font-size: .85rem; color: var(--wp-gris); margin: 0 0 1rem; font-weight: 600; }
    .ev-mes { display: flex; align-items: center; justify-content: center; gap: 1rem; }
    .ev-mes b { min-width: 190px; text-align: center; font-size: 1.15rem; text-transform: capitalize; }
    .ev-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .ev-kpi { background: #fff; border-radius: 16px; padding: 1.25rem; box-shadow: var(--wp-sombra); border: 1px solid var(--wp-borde); border-left: 4px solid var(--wp-oro); display: flex; align-items: center; gap: 1rem; }
    .ev-kpi .ico { width: 48px; height: 48px; border-radius: 12px; background: var(--wp-grad-negro); color: var(--wp-oro); display: grid; place-items: center; font-size: 1.2rem; flex-shrink: 0; }
    .ev-kpi b { display: block; font-size: 1.75rem; line-height: 1.1; }
    .ev-kpi span { color: var(--wp-gris); font-size: .82rem; font-weight: 600; }
</style>

<div class="wp-page">
    <section class="wp-hero">
        <div class="wp-hero-top">
            <div>
                <h1><i class="fas fa-images"></i> <?= ['admin' => 'Evidencias DPO', 'lider' => 'Mis evidencias DPO', 'personal' => 'Mis entrenamientos DPO'][$modo] ?></h1>
                <p><?= $modo === 'personal' ? 'Los entrenamientos que te han hecho, con su foto de evidencia.' : 'Las miniaturas pesan pocos KB; la foto completa solo se descarga cuando la abres.' ?></p>
            </div>
            <div class="wp-hero-acciones">
                <?php if ($esAdmin): ?><a class="wp-btn wp-btn-ghost wp-btn-sm" href="dpo_calendario.php"><i class="fas fa-calendar-check"></i> Calendario</a><?php endif; ?>
                <a class="wp-btn wp-btn-ghost wp-btn-sm" href="index.php"><i class="fas fa-arrow-left"></i> Entrenamientos</a>
            </div>
        </div>
    </section>

    <?php if ($modo === 'personal'): ?>
    <div class="wp-card">
        <div class="wp-card-body ev-mes">
            <button type="button" class="wp-btn wp-btn-secondary wp-btn-icon" id="mesAnt" title="Mes anterior"><i class="fas fa-chevron-left"></i></button>
            <b id="mesTxt"></b>
            <button type="button" class="wp-btn wp-btn-secondary wp-btn-icon" id="mesSig" title="Mes siguiente"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    <div class="ev-kpis">
        <div class="ev-kpi"><div class="ico"><i class="fas fa-calendar-check"></i></div><div><b id="kMes">–</b><span id="kMesTxt">Este mes</span></div></div>
        <div class="ev-kpi"><div class="ico"><i class="fas fa-history"></i></div><div><b id="kAnt">–</b><span id="kAntTxt">Mes anterior</span></div></div>
        <div class="ev-kpi"><div class="ico"><i class="fas fa-trophy"></i></div><div><b id="kTot">–</b><span>Total recibidos</span></div></div>
    </div>
    <form id="filtros" hidden>
        <input type="hidden" id="fDesde" name="desde"><input type="hidden" id="fHasta" name="hasta">
    </form>
    <?php else: ?>
    <div class="wp-card">
        <div class="wp-card-head"><h2><i class="fas fa-filter"></i> Filtros</h2></div>
        <div class="wp-card-body">
            <form class="ev-filtros" id="filtros">
                <?php if ($esAdmin): ?>
                <div><label class="wp-label" for="fLider"><i class="fas fa-user-tie"></i> Líder</label>
                    <select id="fLider" name="lider" class="wp-select"><option value="">Todos los líderes</option></select></div>
                <?php endif; ?>
                <div><label class="wp-label"><i class="fas fa-user"></i> Persona entrenada</label>
                    <select id="fColaborador" name="colaborador" class="wp-select" data-person-select data-value-field="id" data-placeholder="Todas las personas"></select></div>
                <div><label class="wp-label" for="fRango"><i class="fas fa-calendar-alt"></i> Rango de fechas</label>
                    <div class="wp-campo-icono">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="text" id="fRango" class="wp-input" placeholder="Desde – hasta" readonly data-fp-rango="#fDesde,#fHasta">
                        <button type="button" class="wp-limpiar" id="limpiarRango" title="Quitar fechas"><i class="fas fa-times"></i></button>
                    </div>
                    <input type="hidden" id="fDesde" name="desde" value="<?= date('Y-m-01') ?>">
                    <input type="hidden" id="fHasta" name="hasta" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="ev-acciones">
                    <button type="submit" class="wp-btn wp-btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                    <button type="button" class="wp-btn wp-btn-secondary" id="btnLimpiar" title="Limpiar filtros"><i class="fas fa-eraser"></i></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <p class="ev-total" id="total"></p>
    <div class="ev-grid" id="grid"></div>
    <div class="en-paginas" id="paginas"></div>
</div>

<div class="wp-modal" id="modalVer" hidden>
    <div class="wp-modal-box">
        <div class="wp-modal-head"><h2><i class="fas fa-image"></i> <span id="verTitulo"></span></h2><button type="button" class="wp-modal-close" data-wp-cerrar>&times;</button></div>
        <div class="en-visor" id="verVisor"></div>
        <dl class="en-det" id="verDet"></dl>
        <div class="wp-modal-foot">
            <button type="button" class="wp-btn wp-btn-secondary" data-wp-cerrar><i class="fas fa-times"></i> Cerrar</button>
            <a class="wp-btn wp-btn-primary" id="verDescargar" download><i class="fas fa-download"></i> Descargar</a>
        </div>
    </div>
</div>

<script>window.WAREPRO_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/public/js/person-select.js"></script>
<script>
(function () {
    const API = WAREPRO_BASE_URL + '/api/entrenamientos/dpo.php';
    const RAIZ = WAREPRO_BASE_URL + '/';
    const PILARES = <?= json_encode(DPO_PILARES, JSON_UNESCAPED_UNICODE) ?>;
    const txtPilares = v => (v || '').split(',').filter(Boolean).map(k => PILARES[k] || k).join(', ');
    const ES_ADMIN = <?= $esAdmin ? 'true' : 'false' ?>;
    const PERSONAL = <?= $modo === 'personal' ? 'true' : 'false' ?>;
    const INICIO = { desde: <?= json_encode(date('Y-m-01')) ?>, hasta: <?= json_encode(date('Y-m-d')) ?> };
    const $ = s => document.querySelector(s);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmtFecha = f => f.split('-').reverse().join('/');
    const mb = b => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    let pagina = 1, datos = [];

    async function cargar() {
        const p = new URLSearchParams(new FormData($('#filtros')));
        p.set('action', 'evidencias'); p.set('pagina', pagina);
        $('#grid').innerHTML = '<p class="wp-sub">Cargando...</p>';
        try {
            const j = await (await fetch(API + '?' + p, { credentials: 'same-origin' })).json();
            if (!j.success) throw new Error(j.message);
            datos = j.data;
            $('#total').textContent = j.total + ' evidencia' + (j.total === 1 ? '' : 's');
            $('#grid').innerHTML = datos.length ? datos.map((r, i) =>
                '<div class="ev-item"><button type="button" class="en-thumb" data-i="' + i + '">' +
                (r.miniatura ? '<img loading="lazy" decoding="async" src="' + RAIZ + esc(r.miniatura) + '" alt="">' : '<span class="en-sin"><i class="fas fa-image"></i></span>') +
                '</button><div class="ev-info"><b>' + esc(r.colaborador) + '</b>' +
                (ES_ADMIN || PERSONAL ? '<span><i class="fas fa-user-tie"></i> ' + esc(r.lider) + '</span>' : '') +
                '<span><i class="fas fa-calendar"></i> ' + fmtFecha(r.fecha) + '</span></div></div>').join('')
                : '<div class="wp-card" style="grid-column:1/-1"><div class="wp-empty"><i class="fas fa-folder-open"></i>' + (PERSONAL ? 'No te registraron entrenamientos en este mes.' : 'No hay evidencias con estos filtros.') + '</div></div>';
            paginar(j.pagina, j.paginas);
        } catch (e) {
            $('#grid').innerHTML = '<div class="wp-card" style="grid-column:1/-1"><div class="wp-empty">No se pudieron cargar las evidencias.</div></div>';
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
    $('#grid').addEventListener('click', e => {
        const b = e.target.closest('[data-i]'); if (!b) return;
        const r = datos[b.dataset.i];
        $('#verTitulo').textContent = r.colaborador;
        $('#verVisor').innerHTML = '<img src="' + RAIZ + esc(r.archivo) + '" alt="">'; // la foto completa se pide solo aquí
        $('#verDet').innerHTML = '<dt>Fecha</dt><dd>' + fmtFecha(r.fecha) + '</dd>' +
            '<dt>Líder</dt><dd>' + esc(r.lider) + '</dd>' +
            '<dt>Entrenado</dt><dd>' + esc(r.colaborador) + '</dd>' +
            (r.pilares ? '<dt>Pilar impactado</dt><dd>' + esc(txtPilares(r.pilares)) + '</dd>' : '') +
            (r.observacion ? '<dt>Observación</dt><dd>' + esc(r.observacion) + '</dd>' : '') +
            '<dt>Tamaño</dt><dd>' + mb(+r.peso_bytes) + '</dd>';
        $('#verDescargar').href = RAIZ + r.archivo;
        WP.modal.abrir('modalVer');
    });
    $('#modalVer').addEventListener('wp-cerrar', () => { $('#verVisor').innerHTML = ''; });

    // ---------- Usuario que no es líder: sus entrenamientos por mes ----------
    if (PERSONAL) {
        const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const hoy = new Date();
        let anio = hoy.getFullYear(), mes = hoy.getMonth(); // mes 0-11
        const dos = n => String(n).padStart(2, '0');
        async function verMes() {
            const ult = new Date(anio, mes + 1, 0).getDate();
            $('#fDesde').value = anio + '-' + dos(mes + 1) + '-01';
            $('#fHasta').value = anio + '-' + dos(mes + 1) + '-' + dos(ult);
            $('#mesTxt').textContent = MESES[mes] + ' ' + anio;
            const ant = new Date(anio, mes - 1, 1);
            $('#kMesTxt').textContent = 'En ' + MESES[mes];
            $('#kAntTxt').textContent = 'En ' + MESES[ant.getMonth()] + ' (mes anterior)';
            $('#mesSig').disabled = anio > hoy.getFullYear() || (anio === hoy.getFullYear() && mes >= hoy.getMonth());
            pagina = 1; cargar();
            try {
                const j = await (await fetch(API + '?action=resumen_personal&mes=' + anio + '-' + dos(mes + 1), { credentials: 'same-origin' })).json();
                if (j.success) { $('#kMes').textContent = j.mes; $('#kAnt').textContent = j.anterior; $('#kTot').textContent = j.total; }
            } catch (e) { /* los contadores quedan en – */ }
        }
        $('#mesAnt').addEventListener('click', () => { if (--mes < 0) { mes = 11; anio--; } verMes(); });
        $('#mesSig').addEventListener('click', () => { if (++mes > 11) { mes = 0; anio++; } verMes(); });
        verMes();
        return;
    }

    $('#filtros').addEventListener('submit', e => { e.preventDefault(); pagina = 1; cargar(); });
    // Al elegir el rango o la persona se filtra de una vez
    $('#fRango').addEventListener('wp-rango', () => { pagina = 1; cargar(); });
    $('#fColaborador').addEventListener('change', () => { pagina = 1; cargar(); });
    if (ES_ADMIN) $('#fLider').addEventListener('change', () => { pagina = 1; cargar(); });

    function ponerRango(desde, hasta) {
        $('#fDesde').value = desde || ''; $('#fHasta').value = hasta || '';
        const fp = $('#fRango')._flatpickr;
        if (fp) fp.setDate([desde, hasta].filter(Boolean), false, 'Y-m-d');
        else $('#fRango').value = desde ? fmtFecha(desde) + (hasta && hasta !== desde ? ' a ' + fmtFecha(hasta) : '') : '';
    }
    $('#limpiarRango').addEventListener('click', () => { ponerRango('', ''); pagina = 1; cargar(); });
    $('#btnLimpiar').addEventListener('click', () => {
        ponerRango(INICIO.desde, INICIO.hasta);
        PersonSelect.setValue($('#fColaborador'), '');
        if (ES_ADMIN) $('#fLider').value = '';
        pagina = 1; cargar();
    });


    // Desde el calendario se puede llegar con ?lider=ID&fecha=YYYY-MM-DD
    const q = new URLSearchParams(location.search);
    if (/^\d{4}-\d{2}-\d{2}$/.test(q.get('fecha') || '')) ponerRango(q.get('fecha'), q.get('fecha'));
    else ponerRango(INICIO.desde, INICIO.hasta);
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
