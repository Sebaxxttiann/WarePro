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
    .rg-lider { display: flex; align-items: center; gap: 12px; }
    .rg-avatar { width: 42px; height: 42px; border-radius: 50%; background: #FFD700; color: #000; display: grid; place-items: center; font-weight: 700; flex-shrink: 0; }
    .rg-opciones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .rg-op { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 18px 10px; border: 2px dashed #e0e0e0; border-radius: 14px; background: #fafafa; cursor: pointer; font: inherit; font-weight: 600; font-size: .85rem; color: #333; text-align: center; transition: border-color .2s, background .2s; }
    .rg-op:hover { border-color: #FFD700; background: #fffbea; }
    .rg-op i { font-size: 1.5rem; color: #111; }
    .rg-op small { font-weight: 500; color: #888; font-size: .72rem; }
    .rg-op .rg-reco { position: absolute; top: -9px; right: 8px; background: #111; color: #FFD700; font-size: .62rem; padding: 2px 8px; border-radius: 999px; }
    .rg-op input { display: none; }
    .rg-prev { display: none; margin-top: 14px; gap: 14px; align-items: center; background: #f8f8f8; border-radius: 14px; padding: 10px; }
    .rg-prev.on { display: flex; }
    .rg-prev .en-thumb { width: 120px; flex-shrink: 0; }
    .rg-ahorro { font-size: .8rem; color: #15803d; font-weight: 600; }
    .rg-fechas { display: flex; gap: 8px; }
    .rg-fechas label { flex: 1; }
    .rg-fechas input { display: none; }
    .rg-fechas span { display: block; text-align: center; padding: 10px; border: 2px solid #e5e7eb; border-radius: 11px; font-weight: 600; font-size: .85rem; cursor: pointer; }
    .rg-fechas input:checked + span { border-color: #111; background: #111; color: #FFD700; }
    .rg-barra { height: 8px; background: #eee; border-radius: 99px; overflow: hidden; margin-top: 12px; display: none; }
    .rg-barra.on { display: block; }
    .rg-barra div { height: 100%; width: 0; background: linear-gradient(90deg, #FFD700, #FFA500); transition: width .2s; }
    .rg-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
    .rg-lista .nom { font-size: .76rem; font-weight: 600; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rg-lista .fec { font-size: .68rem; color: #888; }
    .rg-cam video { width: 100%; max-height: 60vh; background: #000; border-radius: 12px; display: block; }
    .rg-cam-bar { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 12px; }
    .rg-rec { display: none; align-items: center; gap: 8px; font-weight: 700; color: #dc2626; }
    .rg-rec.on { display: inline-flex; }
    .rg-rec i { animation: rgParpadeo 1s infinite; }
    @keyframes rgParpadeo { 50% { opacity: .2; } }
    @media (max-width: 480px) { .rg-opciones { grid-template-columns: 1fr; } .rg-op { flex-direction: row; justify-content: flex-start; padding: 14px; } }
</style>

<div class="en-wrap estrecho">
    <section class="en-hero">
        <div class="en-hero-top">
            <div>
                <h1><i class="fas fa-camera"></i> Registrar DPO</h1>
                <p>Entrenamiento en el puesto: elige a quién entrenaste y sube la evidencia.</p>
            </div>
            <a class="en-chip" href="index.php"><i class="fas fa-arrow-left"></i> Entrenamientos</a>
        </div>
    </section>

<?php if (!$esLider): ?>
    <div class="en-card en-empty">
        <i class="fas fa-lock"></i>
        <b>No estás habilitado como líder DPO</b>
        <p>Pídele a tu supervisor que te agregue en el calendario de DPO.</p>
        <?php if (dpoEsAdmin()): ?><a class="en-btn en-btn-dark" href="dpo_calendario.php"><i class="fas fa-calendar-check"></i> Ir al calendario</a><?php endif; ?>
    </div>
<?php else: ?>
    <div class="en-card">
        <div class="rg-lider">
            <div class="rg-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
            <div>
                <div style="font-size:.72rem;color:#888;font-weight:600;text-transform:uppercase">Líder</div>
                <div style="font-weight:700"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
            </div>
        </div>
    </div>

    <form id="frm" class="en-card" novalidate>
        <h2><span class="en-num">1</span> ¿A quién entrenaste?</h2>
        <p class="en-sub">Busca por nombre o cédula entre los usuarios de la operación.</p>
        <select id="colaborador" name="colaborador_id" data-person-select data-value-field="id" data-placeholder="Buscar persona..."></select>

        <h2 style="margin-top:26px"><span class="en-num">2</span> Evidencia</h2>
        <p class="en-sub">Las fotos se comprimen en tu celular antes de subirse; los videos grabados aquí quedan livianos.</p>
        <div class="rg-opciones">
            <label class="rg-op">
                <i class="fas fa-camera"></i> Tomar foto
                <input type="file" accept="image/*" capture="environment" data-origen>
            </label>
            <button type="button" class="rg-op" id="btnGrabar">
                <span class="rg-reco">Recomendado</span>
                <i class="fas fa-video"></i> Grabar video
                <small>Máx. <?= 90 ?> s · liviano</small>
            </button>
            <label class="rg-op">
                <i class="fas fa-images"></i> Galería
                <small>Foto o video</small>
                <input type="file" accept="image/*,video/*" data-origen>
            </label>
        </div>
        <div class="rg-prev" id="prev">
            <div class="en-thumb" id="prevThumb"></div>
            <div style="min-width:0;flex:1">
                <div id="prevTipo" style="font-weight:700"></div>
                <div class="rg-ahorro" id="prevPeso"></div>
                <button type="button" class="en-btn en-btn-ghost en-btn-sm" id="btnQuitar" style="margin-top:8px"><i class="fas fa-times"></i> Cambiar</button>
            </div>
        </div>

        <h2 style="margin-top:26px"><span class="en-num">3</span> Detalles</h2>
        <label class="en-label">Fecha del entrenamiento</label>
        <div class="rg-fechas">
            <label><input type="radio" name="fecha" value="<?= date('Y-m-d') ?>" checked><span>Hoy · <?= date('d/m') ?></span></label>
            <label><input type="radio" name="fecha" value="<?= date('Y-m-d', strtotime('-1 day')) ?>"><span>Ayer · <?= date('d/m', strtotime('-1 day')) ?></span></label>
        </div>
        <label class="en-label" for="obs" style="margin-top:14px">Observación (opcional)</label>
        <input type="text" id="obs" name="observacion" class="en-input" maxlength="255" placeholder="Ej: Uso correcto de estibadora">

        <button type="submit" class="en-btn en-btn-dark en-btn-block" id="btnGuardar" style="margin-top:20px"><i class="fas fa-cloud-upload-alt"></i> Guardar entrenamiento</button>
        <div class="rg-barra" id="barra"><div></div></div>
        <p id="estado" style="text-align:center;font-size:.85rem;margin:8px 0 0;color:#666"></p>
    </form>

    <div class="en-card">
        <h2><i class="fas fa-history" style="color:#d4a900"></i> Tus registros de los últimos 7 días</h2>
        <div class="rg-lista" id="lista" style="margin-top:12px"><p class="en-sub">Cargando...</p></div>
    </div>

    <div class="en-modal" id="modalCam" hidden>
        <div class="en-modal-box">
            <div class="en-modal-head"><h3><i class="fas fa-video"></i> Grabar video</h3><button type="button" class="en-modal-close" data-cerrar>&times;</button></div>
            <div class="en-modal-body rg-cam">
                <video id="camVideo" playsinline muted></video>
                <div class="rg-cam-bar">
                    <span class="rg-rec" id="recEstado"><i class="fas fa-circle"></i> <span id="recTiempo">0:00</span></span>
                    <span style="color:#888;font-size:.8rem" id="recAyuda">Encuadra y presiona grabar.</span>
                    <button type="button" class="en-btn en-btn-gold" id="btnRec"><i class="fas fa-circle"></i> Grabar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="en-modal" id="modalVer" hidden>
        <div class="en-modal-box"><div class="en-modal-head"><h3 id="verTitulo"></h3><button type="button" class="en-modal-close" data-cerrar>&times;</button></div>
            <div class="en-modal-body"><div class="en-visor" id="verVisor"></div></div></div>
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
    let evidencia = null; // {tipo, archivo, miniatura, original, duracion}

    function estado(t, color) { $('#estado').textContent = t || ''; $('#estado').style.color = color || '#666'; }

    // ---------- Evidencia desde archivo ----------
    document.querySelectorAll('[data-origen]').forEach(inp => inp.addEventListener('change', async function () {
        const f = this.files[0]; this.value = '';
        if (!f) return;
        await procesar(f);
    }));

    async function procesar(blob) {
        estado('Preparando evidencia...');
        try {
            evidencia = blob.type.startsWith('video/') ? await DpoMedia.prepararVideo(blob) : await DpoMedia.comprimirImagen(blob);
            mostrarPrevia();
            estado('');
        } catch (e) {
            evidencia = null; $('#prev').classList.remove('on');
            estado(e.message, '#dc2626');
        }
    }

    function mostrarPrevia() {
        const ev = evidencia;
        const url = ev.miniatura ? URL.createObjectURL(ev.miniatura) : null;
        $('#prevThumb').innerHTML = (url ? '<img src="' + url + '" alt="">' : '<span class="en-sin"><i class="fas fa-film"></i></span>') +
            (ev.tipo === 'video' ? '<span class="en-play"><i class="fas fa-play-circle"></i></span>' : '');
        $('#prevTipo').textContent = ev.tipo === 'video' ? 'Video' + (ev.duracion ? ' · ' + ev.duracion + ' s' : '') : 'Foto';
        const ahorro = ev.original > ev.archivo.size ? ' (antes ' + DpoMedia.mb(ev.original) + ')' : '';
        $('#prevPeso').textContent = 'Se subirán ' + DpoMedia.mb(ev.archivo.size) + ahorro;
        $('#prev').classList.add('on');
    }
    $('#btnQuitar').addEventListener('click', () => { evidencia = null; $('#prev').classList.remove('on'); });

    // ---------- Grabación en la app ----------
    const modalCam = $('#modalCam');
    let grab = null, grabando = false;
    $('#btnGrabar').addEventListener('click', async () => {
        if (!DpoMedia.Grabador.soportado()) { estado('Tu navegador no permite grabar aquí. Usa "Galería".', '#dc2626'); return; }
        grab = new DpoMedia.Grabador($('#camVideo'), (seg, max) => {
            $('#recTiempo').textContent = Math.floor(seg / 60) + ':' + String(seg % 60).padStart(2, '0');
            $('#recAyuda').textContent = 'Quedan ' + (max - seg) + ' s';
        });
        modalCam.hidden = false;
        try { await grab.abrir(); }
        catch (e) { cerrarCam(); estado('No se pudo abrir la cámara. Revisa los permisos del navegador.', '#dc2626'); }
    });
    $('#btnRec').addEventListener('click', async () => {
        if (!grabando) {
            grab.iniciar(); grabando = true;
            $('#btnRec').innerHTML = '<i class="fas fa-stop"></i> Detener';
            $('#recEstado').classList.add('on');
            return;
        }
        const blob = await grab.detener();
        cerrarCam();
        if (blob && blob.size) await procesar(blob);
    });
    function cerrarCam() {
        if (grab) grab.cerrar();
        grabando = false; modalCam.hidden = true;
        $('#btnRec').innerHTML = '<i class="fas fa-circle"></i> Grabar';
        $('#recEstado').classList.remove('on'); $('#recTiempo').textContent = '0:00'; $('#recAyuda').textContent = 'Encuadra y presiona grabar.';
    }

    // ---------- Guardar ----------
    $('#frm').addEventListener('submit', function (e) {
        e.preventDefault();
        const colab = $('#colaborador').value;
        if (!colab) return estado('Selecciona a la persona que entrenaste.', '#dc2626');
        if (!evidencia) return estado('Agrega la foto o el video del entrenamiento.', '#dc2626');

        const ext = evidencia.archivo.type.includes('mp4') ? 'mp4' : evidencia.archivo.type.includes('webm') ? 'webm' : evidencia.archivo.type.includes('quicktime') ? 'mov' : 'jpg';
        const fd = new FormData();
        fd.append('action', 'subir');
        fd.append('colaborador_id', colab);
        fd.append('fecha', document.querySelector('input[name="fecha"]:checked').value);
        fd.append('observacion', $('#obs').value);
        fd.append('archivo', evidencia.archivo, 'evidencia.' + ext);
        if (evidencia.miniatura) fd.append('miniatura', evidencia.miniatura, 'miniatura.jpg');
        if (evidencia.duracion) fd.append('duracion', evidencia.duracion);

        const btn = $('#btnGuardar'); btn.disabled = true;
        const barra = $('#barra'); barra.classList.add('on');
        const xhr = new XMLHttpRequest();
        xhr.open('POST', API);
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) xhr.setRequestHeader('X-CSRF-Token', csrf.content);
        xhr.upload.onprogress = ev => { if (ev.lengthComputable) { const p = Math.round(ev.loaded * 100 / ev.total); barra.firstElementChild.style.width = p + '%'; estado('Subiendo ' + p + '%'); } };
        xhr.onload = () => {
            let j = {}; try { j = JSON.parse(xhr.responseText); } catch (err) { j = { message: xhr.status === 413 ? 'El archivo es demasiado pesado.' : 'Respuesta inválida del servidor.' }; }
            btn.disabled = false; barra.classList.remove('on'); barra.firstElementChild.style.width = 0;
            if (j.success) {
                estado('');
                Swal.fire({ icon: 'success', title: '¡Registrado!', text: j.message, timer: 1800, showConfirmButton: false });
                evidencia = null; $('#prev').classList.remove('on'); $('#obs').value = '';
                PersonSelect.setValue($('#colaborador'), '');
                cargarLista();
            } else estado(j.message || 'No se pudo guardar.', '#dc2626');
        };
        xhr.onerror = () => { btn.disabled = false; barra.classList.remove('on'); estado('Sin conexión. Intenta de nuevo.', '#dc2626'); };
        xhr.send(fd);
    });

    // ---------- Mis registros ----------
    async function cargarLista() {
        try {
            const j = await (await fetch(API + '?action=mis_registros', { credentials: 'same-origin' })).json();
            const l = $('#lista');
            if (!j.success || !j.data.length) { l.innerHTML = '<p class="en-sub">Aún no tienes registros esta semana.</p>'; return; }
            l.innerHTML = j.data.map(r => '<div><button type="button" class="en-thumb" data-ver="' + esc(r.archivo) + '" data-tipo="' + r.tipo + '" data-nom="' + esc(r.colaborador) + '">' +
                (r.miniatura ? '<img loading="lazy" src="' + RAIZ + esc(r.miniatura) + '" alt="">' : '<span class="en-sin"><i class="fas fa-' + (r.tipo === 'video' ? 'film' : 'image') + '"></i></span>') +
                (r.tipo === 'video' ? '<span class="en-play"><i class="fas fa-play-circle"></i></span>' : '') + '</button>' +
                '<div class="nom">' + esc(r.colaborador) + '</div><div class="fec">' + r.fecha.split('-').reverse().slice(0, 2).join('/') + '</div></div>').join('');
        } catch (e) { $('#lista').innerHTML = '<p class="en-sub">No se pudieron cargar tus registros.</p>'; }
    }
    $('#lista').addEventListener('click', e => {
        const b = e.target.closest('[data-ver]'); if (!b) return;
        $('#verTitulo').textContent = b.dataset.nom;
        $('#verVisor').innerHTML = b.dataset.tipo === 'video'
            ? '<video src="' + RAIZ + b.dataset.ver + '" controls autoplay playsinline preload="metadata"></video>'
            : '<img src="' + RAIZ + b.dataset.ver + '" alt="">';
        $('#modalVer').hidden = false;
    });
    document.querySelectorAll('[data-cerrar]').forEach(b => b.addEventListener('click', () => {
        if (b.closest('#modalCam')) return cerrarCam();
        $('#modalVer').hidden = true; $('#verVisor').innerHTML = '';
    }));

    cargarLista();
})();
</script>
<?php endif; ?>
</body>
</html>
