<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/dpo.php';

$op = (int)getOperacionActiva();
$esLider = dpoEsLider($pdo, $op, (int)$_SESSION['usuario_id']);

require_once '../../core/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/entrenamientos.css">
<style>
    .rg-lider { display: flex; align-items: center; gap: .75rem; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid var(--wp-borde); border-radius: var(--wp-radio); padding: 1rem; }
    .rg-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--wp-grad-oro); color: var(--wp-negro); display: grid; place-items: center; font-weight: 700; flex-shrink: 0; }
    .rg-lider b { display: block; font-size: .95rem; }
    .rg-lider .wp-badge { margin-left: auto; }
    .rg-paso { display: flex; align-items: center; gap: .6rem; font-size: 1.1rem; font-weight: 700; margin: 0 0 .35rem; }
    .rg-bloque + .rg-bloque { margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--wp-borde); }
    .rg-opciones { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .rg-prev { display: none; margin-top: 1rem; gap: 1rem; align-items: center; background: var(--wp-gris-claro); border: 2px solid var(--wp-borde); border-radius: var(--wp-radio); padding: .75rem; }
    .rg-prev.on { display: flex; }
    .rg-prev .en-thumb { width: 130px; flex-shrink: 0; }
    .rg-ahorro { font-size: .82rem; color: #15803d; font-weight: 600; margin-top: .2rem; }
    .rg-fechas { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .rg-fechas label { position: relative; }
    .rg-fechas input { position: absolute; opacity: 0; pointer-events: none; }
    .rg-fechas span { display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .9rem; border: 2px solid var(--wp-borde); border-radius: var(--wp-radio); background: var(--wp-gris-claro); font-weight: 600; cursor: pointer; transition: all .2s; }
    .rg-fechas input:checked + span { border-color: var(--wp-oro); background: rgba(255, 215, 0, .12); box-shadow: 0 0 0 4px rgba(255, 215, 0, .1); }
    .rg-fechas input:focus-visible + span { outline: 2px solid var(--wp-oro); }
    .rg-barra { height: 8px; background: #eee; border-radius: 99px; overflow: hidden; margin-top: 1rem; display: none; }
    .rg-barra.on { display: block; }
    .rg-barra div { height: 100%; width: 0; background: var(--wp-grad-oro); transition: width .2s; }
    .rg-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; }
    .rg-lista .nom { font-size: .8rem; font-weight: 600; margin-top: .4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rg-lista .fec { font-size: .72rem; color: var(--wp-gris); }
    @media (max-width: 560px) { .rg-opciones { grid-template-columns: 1fr; } .rg-lider .wp-badge { display: none; } }
    .rg-pilares { position: relative; }
    .rg-pil-btn { display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; text-align: left; cursor: pointer; min-height: 50px; font: inherit; }
    .rg-pil-btn > i { color: var(--wp-gris); transition: transform .2s; flex-shrink: 0; }
    .rg-pilares.on .rg-pil-btn { border-color: var(--wp-oro); background: #fff; }
    .rg-pilares.on .rg-pil-btn > i { transform: rotate(180deg); }
    .rg-pil-ph { color: #9ca3af; }
    .rg-pil-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    .rg-pil-chip { background: var(--wp-grad-oro); color: var(--wp-negro); font-weight: 600; font-size: .8rem; padding: .2rem .65rem; border-radius: 999px; }
    .rg-pil-panel { display: none; position: absolute; left: 0; right: 0; top: calc(100% + 6px); background: #fff; border: 2px solid var(--wp-borde); border-radius: var(--wp-radio); box-shadow: var(--wp-sombra-lg); padding: .4rem; z-index: 30; }
    .rg-pilares.on .rg-pil-panel { display: block; animation: wpFadeIn .15s ease-out; }
    .rg-pil-op { position: relative; display: flex; align-items: center; gap: .75rem; padding: .75rem .85rem; border-radius: 10px; cursor: pointer; font-weight: 500; }
    .rg-pil-op:hover { background: var(--wp-gris-claro); }
    .rg-pil-op input { position: absolute; opacity: 0; pointer-events: none; }
    .rg-pil-check { width: 22px; height: 22px; border: 2px solid var(--wp-borde); border-radius: 6px; display: grid; place-items: center; color: transparent; font-size: .7rem; transition: all .15s; flex-shrink: 0; }
    .rg-pil-op input:checked + .rg-pil-check { background: var(--wp-grad-oro); border-color: var(--wp-oro); color: var(--wp-negro); }
    .rg-pil-op:has(input:checked) { background: #fffbea; }
    .rg-pil-op input:focus-visible + .rg-pil-check { box-shadow: 0 0 0 3px rgba(255, 215, 0, .35); }
</style>

<div class="wp-page estrecho">
    <section class="wp-hero">
        <div class="wp-hero-top">
            <div>
                <h1><i class="fas fa-camera"></i> Registrar DPO</h1>
                <p>Entrenamiento en el puesto: elige a quién entrenaste y sube la foto como evidencia.</p>
            </div>
            <a class="wp-btn wp-btn-ghost wp-btn-sm" href="index.php"><i class="fas fa-arrow-left"></i> Entrenamientos</a>
        </div>
    </section>

<?php if (!$esLider): ?>
    <div class="wp-card"><div class="wp-card-body wp-empty">
        <i class="fas fa-lock"></i>
        <b>No estás habilitado como líder DPO</b>
        <p>Pídele a tu supervisor que te agregue en el calendario de DPO.</p>
        <?php if (dpoEsAdmin()): ?><a class="wp-btn wp-btn-primary" href="dpo_calendario.php"><i class="fas fa-calendar-check"></i> Ir al calendario</a><?php endif; ?>
    </div></div>
<?php else: ?>
    <form id="frm" class="wp-card" novalidate>
        <div class="wp-card-head"><h2><i class="fas fa-clipboard-check"></i> Nuevo entrenamiento</h2></div>
        <div class="wp-card-body">
            <div class="rg-bloque">
                <label class="wp-label"><i class="fas fa-user-tie"></i> Líder</label>
                <div class="rg-lider">
                    <div class="rg-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                    <b><?= htmlspecialchars($_SESSION['nombre']) ?></b>
                    <span class="wp-badge wp-badge-success"><i class="fas fa-lock"></i> Sesión activa</span>
                </div>
            </div>

            <div class="rg-bloque">
                <div class="rg-paso"><span class="en-num">1</span> ¿A quién entrenaste? *</div>
                <p class="wp-sub">Busca por nombre o cédula entre los usuarios de la operación.</p>
                <select id="colaborador" name="colaborador_id" class="wp-select" data-person-select data-value-field="id" data-placeholder="Buscar persona..."></select>
            </div>

            <div class="rg-bloque">
                <div class="rg-paso"><span class="en-num">2</span> Foto de evidencia *</div>
                <p class="wp-sub">La foto se comprime en tu celular antes de subirse, así ocupa poco espacio.</p>
                <div class="rg-opciones">
                    <label class="wp-upload">
                        <input type="file" accept="image/*" capture="environment" data-origen>
                        <i class="fas fa-camera"></i>
                        <div><b>Tomar foto</b><small>Abre la cámara</small></div>
                    </label>
                    <label class="wp-upload">
                        <input type="file" accept="image/jpeg,image/png,image/webp" data-origen>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div><b>Adjuntar imagen</b><small>Desde la galería o el equipo</small></div>
                    </label>
                </div>
                <div class="rg-prev" id="prev">
                    <div class="en-thumb" id="prevThumb"></div>
                    <div style="min-width:0;flex:1">
                        <b>Foto lista</b>
                        <div class="rg-ahorro" id="prevPeso"></div>
                        <button type="button" class="wp-btn wp-btn-ghost wp-btn-sm" id="btnQuitar" style="margin-top:.6rem"><i class="fas fa-sync-alt"></i> Cambiar</button>
                    </div>
                </div>
            </div>

            <div class="rg-bloque">
                <div class="rg-paso"><span class="en-num">3</span> Detalles</div>
                <label class="wp-label" style="margin-top:1rem"><i class="fas fa-calendar-alt"></i> Fecha del entrenamiento</label>
                <div class="rg-fechas">
                    <label><input type="radio" name="fecha" value="<?= date('Y-m-d') ?>" checked><span><i class="fas fa-sun"></i> Hoy · <?= date('d/m') ?></span></label>
                    <label><input type="radio" name="fecha" value="<?= date('Y-m-d', strtotime('-1 day')) ?>"><span><i class="fas fa-history"></i> Ayer · <?= date('d/m', strtotime('-1 day')) ?></span></label>
                </div>
                <label class="wp-label" id="pilLbl" style="margin-top:1.5rem"><i class="fas fa-layer-group"></i> Pilar impactado *</label>
                <div class="rg-pilares" id="pilares">
                    <button type="button" class="wp-input rg-pil-btn" id="pilBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="pilLbl">
                        <span id="pilTxt"><span class="rg-pil-ph">Selecciona uno o varios pilares</span></span><i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="rg-pil-panel" id="pilPanel" role="listbox" aria-multiselectable="true">
                        <?php foreach (DPO_PILARES as $k => $v): ?>
                        <label class="rg-pil-op"><input type="checkbox" name="pilares[]" value="<?= $k ?>"><span class="rg-pil-check"><i class="fas fa-check"></i></span><?= htmlspecialchars($v) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="wp-ayuda">Puedes marcar varios.</p>
                <label class="wp-label" for="obs" style="margin-top:1.5rem"><i class="fas fa-sticky-note"></i> Observación</label>
                <textarea id="obs" name="observacion" class="wp-textarea" maxlength="255" rows="2" placeholder="Opcional. Ej: se entrenó sobre el mapa de procesos"></textarea>
            </div>

            <div class="wp-modal-foot">
                <a href="index.php" class="wp-btn wp-btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                <button type="submit" class="wp-btn wp-btn-primary" id="btnGuardar"><i class="fas fa-save"></i> Guardar entrenamiento</button>
            </div>
            <div class="rg-barra" id="barra"><div></div></div>
        </div>
    </form>

    <div class="wp-card">
        <div class="wp-card-head"><h2><i class="fas fa-history"></i> Tus registros de los últimos 7 días</h2></div>
        <div class="wp-card-body"><div class="rg-lista" id="lista"><p class="wp-sub">Cargando...</p></div></div>
    </div>

    <div class="wp-modal" id="modalVer" hidden>
        <div class="wp-modal-box">
            <div class="wp-modal-head"><h2><i class="fas fa-image"></i> <span id="verTitulo"></span></h2><button type="button" class="wp-modal-close" data-wp-cerrar>&times;</button></div>
            <div class="en-visor" id="verVisor"></div>
        </div>
    </div>
<?php endif; ?>
</div>

<?php if ($esLider): ?>
<script>window.WAREPRO_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/public/js/person-select.js"></script>
<script src="<?= BASE_URL ?>/public/js/dpo-media.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script>
(function () {
    const API = WAREPRO_BASE_URL + '/api/entrenamientos/dpo.php';
    const RAIZ = WAREPRO_BASE_URL + '/';
    const $ = s => document.querySelector(s);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // ---------- Pilar impactado (selección múltiple) ----------
    const pil = $('#pilares'), pilBtn = $('#pilBtn');
    const pilChecks = () => [...pil.querySelectorAll('input[name="pilares[]"]')];
    function pintarPilares() {
        const sel = pilChecks().filter(c => c.checked);
        $('#pilTxt').innerHTML = sel.length
            ? '<span class="rg-pil-chips">' + sel.map(c => '<span class="rg-pil-chip">' + esc(c.parentElement.textContent.trim()) + '</span>').join('') + '</span>'
            : '<span class="rg-pil-ph">Selecciona uno o varios pilares</span>';
    }
    function abrirPilares(si) { pil.classList.toggle('on', si); pilBtn.setAttribute('aria-expanded', si ? 'true' : 'false'); }
    pilBtn.addEventListener('click', () => abrirPilares(!pil.classList.contains('on')));
    pil.addEventListener('change', pintarPilares);
    document.addEventListener('click', e => { if (!pil.contains(e.target)) abrirPilares(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && pil.classList.contains('on')) { abrirPilares(false); pilBtn.focus(); } });
    const aviso = (icon, title, text) => Swal.fire({ icon, title, text, confirmButtonText: 'Entendido' });
    let evidencia = null; // {archivo, miniatura, original}

    // ---------- Foto ----------
    document.querySelectorAll('[data-origen]').forEach(inp => inp.addEventListener('change', async function () {
        const f = this.files[0]; this.value = '';
        if (!f) return;
        try {
            evidencia = await DpoMedia.comprimirImagen(f);
            mostrarPrevia();
        } catch (e) {
            evidencia = null; $('#prev').classList.remove('on');
            aviso('error', 'No se pudo usar la foto', e.message);
        }
    }));

    function mostrarPrevia() {
        $('#prevThumb').innerHTML = '<img src="' + URL.createObjectURL(evidencia.miniatura) + '" alt="">';
        const ahorro = evidencia.original > evidencia.archivo.size ? ' (antes ' + DpoMedia.mb(evidencia.original) + ')' : '';
        $('#prevPeso').textContent = 'Se subirán ' + DpoMedia.mb(evidencia.archivo.size) + ahorro;
        $('#prev').classList.add('on');
    }
    $('#btnQuitar').addEventListener('click', () => { evidencia = null; $('#prev').classList.remove('on'); });

    // ---------- Guardar ----------
    $('#frm').addEventListener('submit', function (e) {
        e.preventDefault();
        const colab = $('#colaborador').value;
        if (!colab) return aviso('warning', 'Falta la persona', 'Selecciona a la persona que entrenaste.');
        if (!evidencia) return aviso('warning', 'Falta la foto', 'Toma o adjunta la foto del entrenamiento.');
        const pilares = pilChecks().filter(c => c.checked).map(c => c.value);
        if (!pilares.length) { abrirPilares(true); return aviso('warning', 'Falta el pilar', 'Selecciona al menos un pilar impactado.'); }

        const fd = new FormData();
        fd.append('action', 'subir');
        fd.append('colaborador_id', colab);
        fd.append('fecha', document.querySelector('input[name="fecha"]:checked').value);
        pilares.forEach(p => fd.append('pilares[]', p));
        fd.append('observacion', $('#obs').value);
        fd.append('archivo', evidencia.archivo, 'evidencia.jpg');
        fd.append('miniatura', evidencia.miniatura, 'miniatura.jpg');

        const btn = $('#btnGuardar'), barra = $('#barra'), txt = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
        barra.classList.add('on');
        const fin = () => { btn.disabled = false; btn.innerHTML = txt; barra.classList.remove('on'); barra.firstElementChild.style.width = 0; };

        const xhr = new XMLHttpRequest();
        xhr.open('POST', API);
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) xhr.setRequestHeader('X-CSRF-Token', csrf.content);
        xhr.upload.onprogress = ev => { if (ev.lengthComputable) barra.firstElementChild.style.width = Math.round(ev.loaded * 100 / ev.total) + '%'; };
        xhr.onload = () => {
            let j = {}; try { j = JSON.parse(xhr.responseText); } catch (err) { j = { message: xhr.status === 413 ? 'La foto es demasiado pesada.' : 'Respuesta inválida del servidor.' }; }
            fin();
            if (j.success) {
                Swal.fire({ icon: 'success', title: '¡Registrado!', text: j.message, timer: 1800, showConfirmButton: false });
                evidencia = null; $('#prev').classList.remove('on'); $('#obs').value = '';
                pilChecks().forEach(c => { c.checked = false; }); pintarPilares();
                PersonSelect.setValue($('#colaborador'), '');
                cargarLista();
            } else aviso('error', 'No se pudo guardar', j.message || 'Intenta de nuevo.');
        };
        xhr.onerror = () => { fin(); aviso('error', 'Sin conexión', 'Revisa tu conexión e intenta de nuevo.'); };
        xhr.send(fd);
    });

    // ---------- Mis registros ----------
    async function cargarLista() {
        const l = $('#lista');
        try {
            const j = await (await fetch(API + '?action=mis_registros', { credentials: 'same-origin' })).json();
            if (!j.success || !j.data.length) { l.innerHTML = '<div class="wp-empty" style="grid-column:1/-1"><i class="fas fa-camera"></i>Aún no tienes registros esta semana.</div>'; return; }
            l.innerHTML = j.data.map(r => '<div><button type="button" class="en-thumb" data-ver="' + esc(r.archivo) + '" data-nom="' + esc(r.colaborador) + '">' +
                (r.miniatura ? '<img loading="lazy" src="' + RAIZ + esc(r.miniatura) + '" alt="">' : '<span class="en-sin"><i class="fas fa-image"></i></span>') + '</button>' +
                '<div class="nom">' + esc(r.colaborador) + '</div><div class="fec">' + r.fecha.split('-').reverse().slice(0, 2).join('/') + '</div></div>').join('');
        } catch (e) { l.innerHTML = '<p class="wp-sub">No se pudieron cargar tus registros.</p>'; }
    }
    $('#lista').addEventListener('click', e => {
        const b = e.target.closest('[data-ver]'); if (!b) return;
        $('#verTitulo').textContent = b.dataset.nom;
        $('#verVisor').innerHTML = '<img src="' + RAIZ + b.dataset.ver + '" alt="">';
        WP.modal.abrir('modalVer');
    });
    $('#modalVer').addEventListener('wp-cerrar', () => { $('#verVisor').innerHTML = ''; });

    cargarLista();
})();
</script>
<?php endif; ?>
</body>
</html>
