<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/metas.php';
require_once '../../core/header.php';

$puede_gestionar = puedeGestionarMetas();
$stmtOp = $pdo->prepare("SELECT nombre FROM operaciones WHERE id = ?");
$stmtOp->execute([getOperacionActiva()]);
$operacion_nombre = $stmtOp->fetchColumn() ?: 'Operación';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores por turno - WARE PRO</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['Poppins', 'sans-serif'] },
                colors: { brand: { 400: '#FFD700', 500: '#FFA500' }, dark: '#1a1a1a' }
            } }
        };
        window.WAREPRO_BASE_URL = <?= json_encode(BASE_URL) ?>;
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
        /* Paleta única de la web: negro, amarillo y blanco. Los turnos se distinguen por ícono y nombre, no por color. */
        .it-wrap { --acc: #FFD700; --acc2: #FFA500; --ink: #1a1a1a; }
        .turno-tab { position: relative; overflow: hidden; border-radius: 1rem; cursor: pointer; transition: transform .2s, box-shadow .2s, background .2s; text-align: left; width: 100%;
            background: #fff; color: var(--ink); border: 2px solid #e5e7eb; }
        .turno-tab:hover { transform: translateY(-2px); border-color: var(--acc); }
        .turno-tab.active { background: linear-gradient(135deg, #1a1a1a 0%, #000 100%); color: #fff; border-color: var(--acc); box-shadow: 0 14px 30px -14px rgba(0,0,0,.6); }
        .turno-tab::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 4px; background: linear-gradient(90deg, var(--acc), var(--acc2)); opacity: 0; transition: opacity .2s; }
        .turno-tab.active::before { opacity: 1; }
        .turno-tab .deco { position: absolute; right: -14px; bottom: -18px; font-size: 5.5rem; color: var(--acc); opacity: .12; pointer-events: none; }
        .turno-tab.active .deco { opacity: .18; }
        .turno-ico { width: 2.5rem; height: 2.5rem; border-radius: .75rem; place-items: center; font-size: 1.15rem; background: #000; color: var(--acc); flex-shrink: 0; }
        .turno-tab.active .turno-ico { background: var(--acc); color: #000; }
        .turno-tab .t-sub { color: #6b7280; }
        .turno-tab.active .t-sub { color: #d1d5db; }
        .kpi-ring { --p: 0; width: 58px; height: 58px; border-radius: 50%; place-items: center; flex-shrink: 0;
            background: conic-gradient(var(--acc) calc(var(--p) * 1%), #e5e7eb 0); }
        .turno-tab.active .kpi-ring { background: conic-gradient(var(--acc) calc(var(--p) * 1%), rgba(255,255,255,.18) 0); }
        .kpi-ring > span { width: 46px; height: 46px; border-radius: 50%; display: grid; place-items: center; font-weight: 800; font-size: .8rem; background: #fff; color: var(--ink); }
        .turno-tab.active .kpi-ring > span { background: #111; color: var(--acc); }
        .chip-k { display: inline-flex; align-items: center; gap: .3rem; padding: .1rem .5rem; border-radius: 999px; background: #f3f4f6; color: #374151; font-size: .68rem; font-weight: 600; white-space: nowrap; }
        .turno-tab.active .chip-k { background: rgba(255,215,0,.14); color: var(--acc); }
        .panel-head { background: linear-gradient(135deg, #1a1a1a 0%, #000 100%); border-bottom: 3px solid var(--acc); }
        .acc-text { color: #d4a900; }
        .acc-btn { background: var(--acc); color: #000; }
        .acc-btn:hover { filter: brightness(1.05); }
        .ind-card { border-left: 5px solid #e5e7eb; transition: border-color .2s, box-shadow .2s; }
        .ind-card:hover { box-shadow: 0 4px 14px -6px rgba(0,0,0,.15); }
        .cols-head { display: none; }
        @media (min-width: 1024px) {
            .ind-row { grid-template-columns: minmax(0, 2.3fr) 120px minmax(0, 2fr) minmax(0, 1.7fr) 190px !important; gap: .9rem; align-items: center; }
            .ind-row > * { grid-column: auto !important; order: 0 !important; }
            .cols-head { display: grid; padding: 0 .75rem 0 1.05rem; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; }
            .ind-card .m-label { display: none; }
        }
        .ind-card.st-cumple { border-left-color: #16a34a; }
        .ind-card.st-no_cumple { border-left-color: #dc2626; }
        .ind-card.st-disparador { border-left-color: #2563eb; }
        .ind-card.st-cumple .res-input { border-color: #86efac; background: #f0fdf4; }
        .ind-card.st-no_cumple .res-input { border-color: #fca5a5; background: #fef2f2; }
        .ind-card.st-disparador .res-input { border-color: #3b82f6; background: #fef2f2; box-shadow: 0 0 0 2px #bfdbfe; }
        .ind-card.oculto { opacity: .55; }
        .badge-st { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .b-cumple { background: #dcfce7; color: #166534; }
        .b-no_cumple { background: #fee2e2; color: #991b1b; }
        .b-disparador { background: #fee2e2; color: #991b1b; border: 2px solid #2563eb; }
        .b-vacio { background: #f3f4f6; color: #6b7280; }
        .b-preview { outline: 2px dashed rgba(0,0,0,.15); outline-offset: 1px; }
        .res-input { font-size: 1.05rem; font-weight: 700; text-align: center; border: 2px solid #e5e7eb; border-radius: .6rem; width: 100%; padding: .3rem .4rem; outline: none; transition: border-color .2s, background .2s; }
        .res-input:focus { border-color: var(--acc); }
        .res-input:disabled { background: #f9fafb; color: #9ca3af; cursor: not-allowed; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        .drag-handle { cursor: grab; touch-action: none; }
        .sortable-ghost { opacity: .4; }
        .hm-cell { width: 26px; height: 26px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: .6rem; font-weight: 700; cursor: pointer; transition: transform .1s; }
        .hm-cell:hover { transform: scale(1.18); }
        .hm-cumple { background: #22c55e; color: #fff; }
        .hm-no_cumple { background: #ef4444; color: #fff; }
        .hm-disparador { background: #ef4444; color: #fff; box-shadow: inset 0 0 0 3px #2563eb; }
        .hm-vacio { background: #f1f5f9; color: #cbd5e1; }
        .hm-futuro { background: #fafafa; color: #e5e7eb; cursor: default; }
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .save-state { font-size: .7rem; font-weight: 600; min-height: 1rem; }
        .sticky-col { position: sticky; left: 0; z-index: 2; background: #fff; }
        .seg-btn.on { background: #111; color: #FFD700; }
        .pg-btn { min-width: 2rem; height: 2rem; padding: 0 .5rem; border-radius: .5rem; font-size: .8rem; font-weight: 600; border: 1px solid #e5e7eb; background: #fff; color: #374151; }
        .pg-btn:hover:not(:disabled) { border-color: #FFD700; }
        .pg-btn.on { background: #111; color: #FFD700; border-color: #111; }
        .pg-btn:disabled { opacity: .4; cursor: not-allowed; }
        @keyframes pop { 0% { transform: scale(.8); } 60% { transform: scale(1.1); } 100% { transform: scale(1); } }
        .pop { animation: pop .3s ease; }
    </style>
</head>
<body class="text-gray-800 antialiased min-h-screen pb-24">
<div class="it-wrap max-w-[1500px] mx-auto px-4 py-6 md:px-8 md:py-8" id="app">

    <!-- Encabezado -->
    <div class="bg-white p-5 md:p-6 rounded-2xl shadow-sm mb-6 border-l-[5px] border-brand-400 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-1 flex items-center gap-3">
                <i class="fas fa-clipboard-check text-brand-400"></i> Indicadores por turno
            </h1>
            <p class="text-gray-500 text-sm">
                <i class="fas fa-warehouse mr-1"></i><?= htmlspecialchars($operacion_nombre) ?> ·
                Resultados diarios por área. Al registrar, se calcula el cumplimiento de los responsables para su compensación variable.
            </p>
            <?php if (!$puede_gestionar): ?>
                <p class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-gray-600 bg-gray-100 rounded-full px-3 py-1"><i class="fas fa-eye"></i> Modo solo lectura</p>
            <?php endif; ?>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center bg-gray-100 rounded-xl p-1">
                <button class="seg-btn on px-4 py-2 rounded-lg text-sm font-semibold" data-vista="dia"><i class="fas fa-calendar-day mr-1"></i> Diario</button>
                <button class="seg-btn px-4 py-2 rounded-lg text-sm font-semibold" data-vista="mes"><i class="fas fa-th mr-1"></i> Mensual</button>
            </div>
            <div class="flex items-center gap-1 bg-white border-2 border-gray-200 rounded-xl px-1 py-1" id="navDia">
                <button class="w-9 h-9 rounded-lg hover:bg-gray-100" id="btnPrev" title="Día anterior"><i class="fas fa-chevron-left"></i></button>
                <input type="date" id="fecha" class="px-2 py-1 text-sm font-semibold outline-none bg-transparent">
                <button class="w-9 h-9 rounded-lg hover:bg-gray-100" id="btnNext" title="Día siguiente"><i class="fas fa-chevron-right"></i></button>
                <button class="px-3 h-9 rounded-lg text-xs font-bold bg-dark text-brand-400 hover:opacity-90" id="btnHoy">Hoy</button>
            </div>
            <div class="flex items-center gap-1 bg-white border-2 border-gray-200 rounded-xl px-1 py-1 hidden" id="navMes">
                <button class="w-9 h-9 rounded-lg hover:bg-gray-100" id="btnMesPrev"><i class="fas fa-chevron-left"></i></button>
                <input type="month" id="mes" class="px-2 py-1 text-sm font-semibold outline-none bg-transparent">
                <button class="w-9 h-9 rounded-lg hover:bg-gray-100" id="btnMesNext"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>

    <!-- Tableros de turno -->
    <div class="grid grid-cols-3 gap-2 sm:gap-3 md:gap-4 mb-5" id="tabs"></div>

    <!-- Panel del turno -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="panel-head rounded-t-2xl px-4 md:px-5 py-3 flex flex-col md:flex-row md:items-center justify-between gap-3" id="panelHead"></div>

        <div class="px-4 md:px-6 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-600">
                <span class="badge-st b-cumple"><i class="fas fa-check"></i> Cumple</span>
                <span class="badge-st b-no_cumple"><i class="fas fa-times"></i> No cumple</span>
                <span class="badge-st b-disparador"><i class="fas fa-bell"></i> Disparador</span>
                <span class="badge-st b-vacio"><i class="far fa-circle"></i> Sin registrar</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <?php if ($puede_gestionar): ?>
                    <label class="text-xs font-semibold text-gray-600 flex items-center gap-2 cursor-pointer mr-2">
                        <input type="checkbox" id="chkOcultos" class="accent-gray-800"> Mostrar ocultos
                    </label>
                    <button class="acc-btn px-4 py-2 rounded-xl text-sm font-bold shadow-sm" id="btnNuevo"><i class="fas fa-plus mr-1"></i> Nuevo indicador</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="relative">
            <div id="loader" class="absolute inset-0 bg-white/80 z-30 hidden flex-col items-center justify-center py-16 rounded-b-2xl">
                <i class="fas fa-circle-notch fa-spin text-4xl acc-text mb-2"></i>
                <p class="font-semibold text-gray-600">Cargando...</p>
            </div>
            <div id="vistaDia" class="p-3 md:p-5">
                <div class="cols-head ind-row mb-2"><span>Indicador</span><span class="text-center">Resultado</span><span>¿Quién estuvo en el área?</span><span>Observación</span><span>Estado</span></div>
                <div id="lista" class="space-y-2"></div>
            </div>
            <div id="vistaMes" class="p-4 md:p-6 hidden"></div>
        </div>
    </div>
</div>

<!-- Modal indicador -->
<div id="modal" class="fixed inset-0 bg-black/50 z-[60] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden flex flex-col max-h-[92vh]">
        <div class="bg-dark p-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg" id="modalTitle">Nuevo indicador</h3>
            <button type="button" class="text-gray-400 hover:text-white" data-close><i class="fas fa-times"></i></button>
        </div>
        <form id="formInd" class="p-6 space-y-4 overflow-y-auto">
            <input type="hidden" id="fId">
            <div id="fSistemaAviso" class="hidden bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl p-3"></div>
            <div id="fCampos" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre del indicador</label>
                        <input type="text" id="fNombre" maxlength="100" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-brand-400 outline-none" placeholder="Ej: Exactitud de cargue">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Unidad</label>
                        <input type="text" id="fUnidad" maxlength="30" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-brand-400 outline-none" placeholder="%, cj/h, min">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">¿Qué es mejor?</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="border rounded-lg px-3 py-2 flex items-center gap-2 cursor-pointer text-sm has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                            <input type="radio" name="fSentido" value="mayor" checked class="accent-green-600"> <i class="fas fa-arrow-up text-green-600"></i> Más alto (≥ meta)
                        </label>
                        <label class="border rounded-lg px-3 py-2 flex items-center gap-2 cursor-pointer text-sm has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                            <input type="radio" name="fSentido" value="menor" class="accent-green-600"> <i class="fas fa-arrow-down text-green-600"></i> Más bajo (≤ meta)
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 p-3 rounded-xl border border-green-100">
                        <label class="block text-xs font-bold text-green-800 uppercase mb-1"><i class="fas fa-flag-checkered"></i> Meta</label>
                        <input type="number" step="0.01" id="fMeta" class="w-full px-3 py-2 border rounded-lg outline-none" required>
                    </div>
                    <div class="bg-red-50 p-3 rounded-xl border border-red-100">
                        <label class="block text-xs font-bold text-red-800 uppercase mb-1"><i class="fas fa-bell"></i> Disparador <span class="normal-case font-normal">(opcional)</span></label>
                        <input type="number" step="0.01" id="fDisp" class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>
                </div>
                <p class="text-xs text-gray-500" id="fAyuda"></p>
            </div>
            <label class="flex items-start gap-3 bg-gray-50 border rounded-xl p-3 cursor-pointer">
                <input type="checkbox" id="fAplica" class="mt-1 accent-yellow-500" checked>
                <span class="text-sm"><b>Cuenta para la compensación variable</b><br><span class="text-gray-500 text-xs">Si el resultado no cumple, a los responsables se les tacha ese día.</span></span>
            </label>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="px-4 py-2 rounded-lg border font-semibold text-gray-600 hover:bg-gray-50" data-close>Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-dark text-brand-400 font-bold hover:opacity-90"><i class="fas fa-save mr-1"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>/public/js/person-select.js"></script>
<script>
(function () {
    'use strict';
    const API = WAREPRO_BASE_URL + '/api/indicadores/indicadores_turno.php';
    const C_AUX = WAREPRO_BASE_URL + '/modules/personal/c_auxiliar.php';
    const PUEDE = <?= $puede_gestionar ? 'true' : 'false' ?>;
    const TURNOS = {
        A: { nombre: 'Mañana', horario: '06:00 – 14:00', icon: 'fa-sun' },
        B: { nombre: 'Tarde', horario: '14:00 – 22:00', icon: 'fa-cloud-sun' },
        C: { nombre: 'Noche', horario: '22:00 – 06:00', icon: 'fa-moon' }
    };
    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const DIAS = ['D','L','M','M','J','V','S'];
    const ESTADO = {
        cumple: { label: 'Cumple', icon: 'fa-check', cls: 'b-cumple' },
        no_cumple: { label: 'No cumple', icon: 'fa-times', cls: 'b-no_cumple' },
        disparador: { label: 'Disparador', icon: 'fa-bell', cls: 'b-disparador' }
    };

    const $ = (s, r) => (r || document).querySelector(s);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const pad = n => String(n).padStart(2, '0');
    const ymd = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const parseYmd = s => { const p = s.split('-').map(Number); return new Date(p[0], p[1] - 1, p[2]); };
    const fmt = n => n == null ? '—' : Number(n).toLocaleString('es-CO', { maximumFractionDigits: 2 });

    function turnoActual() {
        const h = new Date().getHours();
        if (h >= 6 && h < 14) return 'A';
        if (h >= 14 && h < 22) return 'B';
        return 'C';
    }
    function fechaTurnoActual() {
        // Entre 00:00 y 06:00 el turno C pertenece a la fecha en que inició (el día anterior)
        const d = new Date();
        if (d.getHours() < 6) d.setDate(d.getDate() - 1);
        return ymd(d);
    }

    const state = {
        turno: turnoActual(), fecha: fechaTurnoActual(), vista: 'dia',
        mes: fechaTurnoActual().slice(0, 7), data: null, kpis: null, verOcultos: false, sortable: null,
        personas: [], pPag: 1, pBuscar: ''
    };

    async function api(body) {
        const r = await fetch(API, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        let j;
        try { j = await r.json(); } catch (e) { throw new Error('Respuesta inválida del servidor'); }
        if (!j.success) throw new Error(j.message || 'Error');
        return j;
    }
    function toast(icon, title) {
        Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 2200, timerProgressBar: true });
    }
    function loader(on) { const l = $('#loader'); l.classList.toggle('hidden', !on); l.classList.toggle('flex', on); }

    function evaluar(valor, meta, disp, sentido) {
        valor = Number(valor);
        if (disp !== null && disp !== undefined) {
            if (sentido === 'menor' ? valor >= disp : valor <= disp) return 'disparador';
        }
        if (meta === null || meta === undefined) return 'cumple';
        return (sentido === 'menor' ? valor <= meta : valor >= meta) ? 'cumple' : 'no_cumple';
    }
    function badge(estado, preview) {
        if (!estado) return '<span class="badge-st b-vacio"><i class="far fa-circle"></i> Sin registrar</span>';
        const e = ESTADO[estado];
        return '<span class="badge-st ' + e.cls + (preview ? ' b-preview' : '') + '"><i class="fas ' + e.icon + '"></i> ' + e.label + '</span>';
    }

    // ---------- Tableros (tabs) ----------
    function renderTabs() {
        const k = state.kpis || {};
        const actual = turnoActual();
        $('#tabs').innerHTML = ['A', 'B', 'C'].map(t => {
            const T = TURNOS[t], d = k[t] || {};
            const pct = d.pct == null ? null : d.pct;
            const enCurso = t === actual && state.fecha === fechaTurnoActual();
            return '<button type="button" class="turno-tab ' + (t === state.turno ? 'active' : '') + ' p-2.5 sm:p-4" data-turno="' + t + '">' +
                '<i class="fas ' + T.icon + ' deco"></i>' +
                '<div class="flex items-center justify-between gap-2 relative">' +
                  '<div class="flex items-center gap-2 sm:gap-3 min-w-0">' +
                    '<div class="turno-ico hidden sm:grid"><i class="fas ' + T.icon + '"></i></div>' +
                    '<div class="min-w-0"><div class="text-[10px] font-bold uppercase tracking-widest t-sub"><i class="fas ' + T.icon + ' sm:hidden mr-1"></i>Turno ' + t + '</div>' +
                    '<div class="text-base sm:text-xl font-extrabold leading-tight truncate">' + T.nombre + '</div>' +
                    '<div class="text-[11px] sm:text-xs font-medium t-sub hidden sm:block"><i class="far fa-clock mr-1"></i>' + T.horario + '</div></div>' +
                  '</div>' +
                  '<div class="kpi-ring hidden md:grid" style="--p:' + (pct || 0) + '"><span>' + (pct == null ? '—' : pct + '%') + '</span></div>' +
                '</div>' +
                '<div class="md:hidden text-xs font-bold mt-1 relative">' + (pct == null ? '—' : pct + '% cumpl.') + '</div>' +
                (enCurso ? '<span class="inline-flex items-center gap-1 mt-2 text-[10px] font-bold uppercase rounded-full px-2 py-0.5 bg-yellow-400 text-black relative"><span class="w-1.5 h-1.5 rounded-full bg-black animate-pulse"></span> En curso</span>' : '') +
                '<div class="hidden md:flex flex-wrap gap-1.5 mt-3 relative">' +
                  '<span class="chip-k"><i class="fas fa-check"></i> ' + (d.cumple || 0) + ' cumplen</span>' +
                  '<span class="chip-k"><i class="fas fa-bell"></i> ' + (d.disparador || 0) + ' disparador</span>' +
                  '<span class="chip-k"><i class="fas fa-hourglass-half"></i> ' + (d.sin_registrar || 0) + ' sin registrar</span>' +
                '</div></button>';
        }).join('');
    }

    function aplicarTema() {
        const T = TURNOS[state.turno];
        const k = (state.kpis || {})[state.turno] || {};
        const f = parseYmd(state.fecha);
        const titulo = state.vista === 'dia'
            ? f.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
            : MESES[Number(state.mes.slice(5)) - 1] + ' ' + state.mes.slice(0, 4);
        $('#panelHead').innerHTML =
            '<div class="flex items-center gap-3 text-white"><div class="w-10 h-10 rounded-xl bg-yellow-400 text-black grid place-items-center text-lg"><i class="fas ' + T.icon + '"></i></div>' +
            '<div><div class="text-lg font-bold leading-tight">Turno ' + state.turno + ' · ' + T.nombre + '</div>' +
            '<div class="text-sm opacity-90 capitalize">' + esc(titulo) + '</div></div></div>' +
            (state.vista === 'dia' ? '<div class="flex flex-wrap gap-2 text-white text-xs font-semibold">' +
                '<span class="bg-yellow-400 text-black rounded-full px-3 py-1">' + (k.registrados || 0) + '/' + ((k.total || 0) - (k.sin_configurar || 0)) + ' registrados</span>' +
                (k.no_cumple ? '<span class="bg-white/15 rounded-full px-3 py-1">' + k.no_cumple + ' no cumplen</span>' : '') +
                (k.sin_configurar ? '<span class="bg-white/15 rounded-full px-3 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>' + k.sin_configurar + ' sin meta</span>' : '') +
              '</div>' : '');
    }

    // ---------- Vista diaria ----------
    async function cargarDia() {
        loader(true);
        try {
            const j = await api({ action: 'get_tablero', turno: state.turno, fecha: state.fecha });
            state.data = j; state.kpis = j.kpis;
            renderTabs(); aplicarTema(); renderLista();
        } catch (e) {
            $('#lista').innerHTML = '<div class="text-center text-red-600 py-10"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>' + esc(e.message) + '</p></div>';
        } finally { loader(false); }
    }

    function metaTexto(ind) {
        if (!ind.configurada) return '<span class="text-amber-600 font-semibold">Sin meta</span>';
        const op = ind.sentido === 'menor' ? '≤' : '≥', opd = ind.sentido === 'menor' ? '≥' : '≤';
        return '<span class="inline-flex items-center gap-1 bg-green-50 text-green-800 border border-green-100 rounded-lg px-2 py-0.5"><i class="fas fa-flag-checkered text-[10px]"></i> Meta ' + op + ' ' + fmt(ind.meta) + '</span>' +
            (ind.disparador != null ? ' <span class="inline-flex items-center gap-1 bg-red-50 text-red-800 border border-red-100 rounded-lg px-2 py-0.5"><i class="fas fa-bell text-[10px]"></i> Disp. ' + opd + ' ' + fmt(ind.disparador) + '</span>' : '');
    }

    function cardHtml(ind) {
        const v = ind.valor;
        const estado = v ? v.estado : null;
        const editable = PUEDE && ind.configurada && ind.activo;
        const resp = v ? v.responsables : (ind._respPend || []);
        const opts = resp.map(p => '<option value="' + p.id + '" selected>' + esc(p.nombre) + '</option>').join('');
        let aviso = '';
        if (!ind.configurada) {
            aviso = '<div class="mt-2 flex flex-wrap items-center gap-2 text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-1.5">' +
                '<i class="fas fa-exclamation-triangle"></i> <b>Configura la meta</b> para empezar a registrar resultados.' +
                (PUEDE ? (ind.es_sistema
                    ? ' <a class="underline font-semibold" href="' + esc(state.data.metas_url) + '"><i class="fas fa-external-link-alt mr-1"></i>Ir a Metas</a>'
                    : ' <button type="button" class="underline font-semibold" data-act="edit">Editar indicador</button>') : '') + '</div>';
        }
        const acciones = PUEDE ? '<div class="flex items-center">' +
                '<button type="button" class="w-7 h-7 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 text-xs" title="Editar" data-act="edit"><i class="fas fa-pen"></i></button>' +
                '<button type="button" class="w-7 h-7 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 text-xs" title="' + (ind.activo ? 'Ocultar' : 'Mostrar') + '" data-act="toggle"><i class="fas ' + (ind.activo ? 'fa-eye-slash' : 'fa-eye') + '"></i></button>' +
                (!ind.es_sistema ? '<button type="button" class="w-7 h-7 rounded-md text-gray-400 hover:bg-red-50 hover:text-red-600 text-xs" title="Eliminar" data-act="delete"><i class="fas fa-trash"></i></button>' : '') +
            '</div>' : '';
        return '<div class="ind-card bg-white border border-gray-100 rounded-xl shadow-sm px-3 py-2.5 ' + (estado ? 'st-' + estado : '') + (ind.activo ? '' : ' oculto') + '" data-id="' + ind.id + '">' +
            '<div class="ind-row grid grid-cols-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_120px] gap-x-3 gap-y-2 items-center">' +
              // Indicador
              '<div class="col-span-2 flex gap-2 min-w-0 lg:col-span-1">' +
                (PUEDE ? '<div class="drag-handle text-gray-300 hover:text-gray-500 pt-0.5 text-sm" title="Arrastra para ordenar"><i class="fas fa-grip-vertical"></i></div>' : '') +
                '<div class="min-w-0">' +
                  '<div class="font-bold text-gray-800 text-sm leading-snug flex flex-wrap items-center gap-1.5">' + esc(ind.nombre) +
                    (ind.unidad ? '<span class="text-[10px] font-semibold text-gray-500 bg-gray-100 rounded-full px-1.5 py-0.5">' + esc(ind.unidad) + '</span>' : '') +
                    (!ind.activo ? '<span class="text-[10px] font-semibold text-gray-600 bg-gray-200 rounded-full px-1.5 py-0.5"><i class="fas fa-eye-slash"></i> Oculto</span>' : '') +
                  '</div>' +
                  '<div class="text-[11px] mt-1 flex flex-wrap items-center gap-1">' + metaTexto(ind) +
                    '<span class="text-[10px] text-gray-400 ml-1">' +
                      (ind.es_sistema ? '<i class="fas fa-link mr-0.5"></i>' + (ind.modulo ? esc(ind.modulo) : 'Productividad') : '<i class="fas fa-user-edit mr-0.5"></i>Propio') +
                      (ind.aplica_compensacion ? ' · <i class="fas fa-coins text-yellow-500" title="Cuenta para compensación"></i>' : ' · <span class="line-through">Comp.</span>') +
                    '</span>' +
                  '</div>' +
                '</div>' +
              '</div>' +
              // Resultado
              '<div class="col-span-1">' +
                '<label class="m-label block text-[10px] font-bold text-gray-400 uppercase mb-0.5">Resultado</label>' +
                '<input type="number" step="0.01" inputmode="decimal" class="res-input" data-f="valor" value="' + (v ? v.valor : '') + '" ' + (editable ? '' : 'disabled') + ' placeholder="—">' +
              '</div>' +
              // Estado en móvil, junto al resultado
              '<div class="col-span-1 sm:hidden flex items-end justify-end self-end pb-1"><span data-f="badge-m">' + badge(estado) + '</span></div>' +
              // Responsables
              '<div class="col-span-2 sm:col-span-1 min-w-0">' +
                '<label class="m-label block text-[10px] font-bold text-gray-400 uppercase mb-0.5">Responsables del área</label>' +
                '<select multiple data-person-select data-value-field="id" data-cargos="auxiliar,verificador,operador" data-placeholder="¿Quién estuvo en el área?" data-f="resp" class="w-full px-2 py-1 border-2 border-gray-200 rounded-lg text-sm" ' + (editable ? '' : 'disabled') + '>' + opts + '</select>' +
              '</div>' +
              // Observación
              '<div class="col-span-2 min-w-0">' +
                '<label class="m-label block text-[10px] font-bold text-gray-400 uppercase mb-0.5">Observación</label>' +
                '<input type="text" maxlength="255" data-f="obs" class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-sm outline-none focus:border-gray-400" placeholder="Opcional" value="' + esc(v ? v.observacion || '' : '') + '" ' + (editable ? '' : 'disabled') + '>' +
              '</div>' +
              // Estado + acciones
              '<div class="col-span-2 sm:col-span-3 lg:col-span-1 flex sm:flex-row lg:flex-col sm:items-center lg:items-stretch gap-x-3 gap-y-0.5 min-w-0">' +
                '<div class="flex items-center justify-between gap-1 lg:w-full">' +
                  '<span class="hidden sm:inline-flex" data-f="badge">' + badge(estado) + '</span>' + acciones +
                '</div>' +
                '<div class="save-state text-gray-400 truncate" data-f="save">' + (v && v.registrado_por ? '<i class="fas fa-user-check mr-1"></i>' + esc(v.registrado_por) : '') + '</div>' +
              '</div>' +
            '</div>' + aviso + '</div>';
    }

    function visibles() {
        return state.data.indicadores.filter(i => i.activo || state.verOcultos);
    }

    function renderLista() {
        const lista = $('#lista');
        const inds = visibles();
        if (!inds.length) {
            lista.innerHTML = '<div class="text-center text-gray-500 py-14"><i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i><p>No hay indicadores en este turno.</p>' +
                (PUEDE ? '<p class="text-sm">Crea uno con <b>Nuevo indicador</b>.</p>' : '') + '</div>';
            return;
        }
        lista.innerHTML = inds.map(cardHtml).join('');
        PersonSelect.init(lista);
        if (PUEDE && window.Sortable) {
            if (state.sortable) state.sortable.destroy();
            state.sortable = Sortable.create(lista, {
                handle: '.drag-handle', animation: 150, ghostClass: 'sortable-ghost',
                onEnd: async () => {
                    const ids = Array.from(lista.children).map(c => Number(c.dataset.id));
                    // Conserva los ocultos que no están en pantalla al final
                    state.data.indicadores.forEach(i => { if (ids.indexOf(i.id) === -1) ids.push(i.id); });
                    try { await api({ action: 'update_order', turno: state.turno, ids }); toast('success', 'Orden guardado'); }
                    catch (e) { toast('error', e.message); }
                }
            });
        }
    }

    // Autoguardado por tarjeta
    const timers = {}, saving = {}, pendiente = {};
    function indById(id) { return state.data.indicadores.find(i => i.id === id); }

    function previewCard(card) {
        const ind = indById(Number(card.dataset.id));
        const raw = card.querySelector('[data-f="valor"]').value.trim();
        const est = raw === '' ? null : evaluar(raw, ind.meta, ind.disparador, ind.sentido);
        setEstadoCard(card, est, true);
    }
    function setEstadoCard(card, est, preview) {
        card.classList.remove('st-cumple', 'st-no_cumple', 'st-disparador');
        if (est) card.classList.add('st-' + est);
        card.querySelectorAll('[data-f="badge"], [data-f="badge-m"]').forEach(b => {
            b.innerHTML = badge(est, preview);
            if (!preview && b.firstChild) b.firstChild.classList.add('pop');
        });
    }
    function programarGuardado(card, delay) {
        const id = Number(card.dataset.id);
        clearTimeout(timers[id]);
        card.querySelector('[data-f="save"]').innerHTML = '<span class="text-gray-400"><i class="fas fa-pen mr-1"></i>Cambios sin guardar...</span>';
        timers[id] = setTimeout(() => guardar(card), delay);
    }
    async function guardar(card) {
        const id = Number(card.dataset.id);
        if (saving[id]) { pendiente[id] = true; return; }
        const ind = indById(id);
        const valor = card.querySelector('[data-f="valor"]').value.trim();
        const sel = card.querySelector('[data-f="resp"]');
        const responsables = PersonSelect.getValues(sel).map(Number);
        const observacion = card.querySelector('[data-f="obs"]').value;
        const st = card.querySelector('[data-f="save"]');
        if (valor === '' && !ind.valor) {
            st.innerHTML = responsables.length ? '<span class="text-amber-600"><i class="fas fa-info-circle mr-1"></i>Ingresa el resultado para guardar</span>' : '';
            return;
        }
        saving[id] = true;
        st.innerHTML = '<span class="acc-text"><i class="fas fa-circle-notch fa-spin mr-1"></i>Guardando...</span>';
        try {
            const j = await api({ action: 'save_valor', indicador_id: id, fecha: state.fecha, valor, responsables, observacion });
            const anterior = ind.valor ? ind.valor.estado : null;
            if (j.borrado) {
                ind.valor = null;
                setEstadoCard(card, null, false);
                st.innerHTML = '<span class="text-gray-500"><i class="fas fa-eraser mr-1"></i>Resultado borrado</span>';
            } else {
                ind.valor = Object.assign(ind.valor || {}, { id: j.valor_id, valor: j.valor, estado: j.estado, observacion,
                    responsables: Array.from(sel.selectedOptions).filter(o => o.value !== '').map(o => ({ id: Number(o.value), nombre: o.textContent })) });
                setEstadoCard(card, j.estado, false);
                const h = new Date();
                st.innerHTML = '<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Guardado ' + pad(h.getHours()) + ':' + pad(h.getMinutes()) +
                    (j.responsables ? ' · ' + j.responsables + ' responsable(s)' : ' · <span class="text-amber-600">sin responsables</span>') + '</span>';
            }
            actualizarKpiLocal(anterior, ind.valor ? ind.valor.estado : null);
        } catch (e) {
            st.innerHTML = '<span class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>' + esc(e.message) + '</span>';
        } finally {
            saving[id] = false;
            if (pendiente[id]) { pendiente[id] = false; guardar(card); }
        }
    }
    function actualizarKpiLocal(antes, despues) {
        if (antes === despues) return;
        const k = state.kpis[state.turno];
        if (antes) { k[antes]--; k.registrados--; k.sin_registrar++; }
        if (despues) { k[despues]++; k.registrados++; k.sin_registrar--; }
        k.pct = k.registrados > 0 ? Math.round(k.cumple * 100 / k.registrados) : null;
        renderTabs(); aplicarTema();
    }

    $('#lista').addEventListener('input', e => {
        const card = e.target.closest('.ind-card'); if (!card) return;
        const f = e.target.dataset.f;
        if (f === 'valor') { previewCard(card); programarGuardado(card, 800); }
        else if (f === 'obs') programarGuardado(card, 1200);
    });
    $('#lista').addEventListener('change', e => {
        const card = e.target.closest('.ind-card'); if (!card) return;
        if (e.target.dataset.f === 'resp') programarGuardado(card, 900);
    });
    $('#lista').addEventListener('click', async e => {
        const btn = e.target.closest('[data-act]'); if (!btn) return;
        const card = btn.closest('.ind-card'); const ind = indById(Number(card.dataset.id));
        const act = btn.dataset.act;
        if (act === 'edit') abrirModal(ind);
        else if (act === 'toggle') {
            try {
                const j = await api({ action: 'toggle_indicador', id: ind.id });
                toast('success', j.activo ? 'Indicador visible' : 'Indicador oculto');
                cargarDia();
            } catch (err) { toast('error', err.message); }
        } else if (act === 'delete') {
            const r = await Swal.fire({ icon: 'warning', title: '¿Eliminar indicador?', html: 'Se borrará <b>' + esc(ind.nombre) + '</b> y todos sus resultados registrados. Esto afecta la compensación de los responsables.',
                showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc2626' });
            if (!r.isConfirmed) return;
            try { await api({ action: 'delete_indicador', id: ind.id }); toast('success', 'Indicador eliminado'); cargarDia(); }
            catch (err) { toast('error', err.message); }
        }
    });

    // ---------- Modal ----------
    const modal = $('#modal');
    function abrirModal(ind) {
        $('#formInd').reset();
        $('#fId').value = ind ? ind.id : '';
        $('#modalTitle').textContent = ind ? 'Editar indicador' : 'Nuevo indicador · Turno ' + state.turno;
        const sistema = ind && ind.es_sistema;
        $('#fCampos').classList.toggle('hidden', !!sistema);
        const av = $('#fSistemaAviso');
        av.classList.toggle('hidden', !sistema);
        if (sistema) {
            av.innerHTML = '<i class="fas fa-link mr-1"></i><b>' + esc(ind.nombre) + '</b> viene de las productividades. Su meta y disparador son de toda la operación y se editan en ' +
                '<a class="underline font-semibold" href="' + esc(state.data.metas_url) + '">Metas</a>. Aquí solo defines si cuenta para la compensación.';
        }
        if (ind) {
            $('#fNombre').value = ind.nombre; $('#fUnidad').value = ind.unidad || '';
            document.querySelector('input[name="fSentido"][value="' + ind.sentido + '"]').checked = true;
            $('#fMeta').value = ind.meta == null ? '' : ind.meta;
            $('#fDisp').value = ind.disparador == null ? '' : ind.disparador;
            $('#fAplica').checked = !!ind.aplica_compensacion;
        }
        $('#fMeta').required = !sistema;
        ayuda();
        modal.classList.remove('hidden'); modal.classList.add('flex');
    }
    function cerrarModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }
    function ayuda() {
        const s = document.querySelector('input[name="fSentido"]:checked').value;
        $('#fAyuda').innerHTML = s === 'mayor'
            ? 'Cumple si el resultado es <b>≥ meta</b>. Disparador (alerta crítica) si es <b>≤ disparador</b>; debe ser menor que la meta.'
            : 'Cumple si el resultado es <b>≤ meta</b>. Disparador (alerta crítica) si es <b>≥ disparador</b>; debe ser mayor que la meta.';
    }
    modal.addEventListener('click', e => { if (e.target === modal || e.target.closest('[data-close]')) cerrarModal(); });
    document.querySelectorAll('input[name="fSentido"]').forEach(r => r.addEventListener('change', ayuda));
    $('#formInd').addEventListener('submit', async e => {
        e.preventDefault();
        const body = {
            action: 'save_indicador', id: $('#fId').value ? Number($('#fId').value) : null, turno: state.turno,
            nombre: $('#fNombre').value, unidad: $('#fUnidad').value,
            sentido: document.querySelector('input[name="fSentido"]:checked').value,
            meta_valor: $('#fMeta').value, disparador_valor: $('#fDisp').value, aplica_compensacion: $('#fAplica').checked
        };
        try {
            const j = await api(body);
            cerrarModal(); toast('success', j.message || 'Guardado'); cargarDia();
        } catch (err) { Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: err.message }); }
    });
    if ($('#btnNuevo')) $('#btnNuevo').addEventListener('click', () => abrirModal(null));
    if ($('#chkOcultos')) $('#chkOcultos').addEventListener('change', e => { state.verOcultos = e.target.checked; renderLista(); });

    // ---------- Vista mensual ----------
    async function cargarMes() {
        loader(true);
        try {
            const [y, m] = state.mes.split('-').map(Number);
            const j = await api({ action: 'get_mes', turno: state.turno, year: y, month: m });
            renderTabs(); aplicarTema(); renderMes(j, y, m);
        } catch (e) {
            $('#vistaMes').innerHTML = '<div class="text-center text-red-600 py-10">' + esc(e.message) + '</div>';
        } finally { loader(false); }
    }

    function renderMes(j, y, m) {
        const dias = j.dias_mes, hoy = ymd(new Date());
        let head = '<th class="sticky-col text-left text-xs font-bold text-gray-500 uppercase px-3 py-2 min-w-[190px]">Indicador</th>';
        for (let d = 1; d <= dias; d++) {
            const f = new Date(y, m - 1, d), dom = f.getDay() === 0, esHoy = ymd(f) === hoy;
            head += '<th class="px-0.5 py-1 text-center ' + (dom ? 'text-red-400' : 'text-gray-500') + '"><div class="text-[9px] font-semibold">' + DIAS[f.getDay()] + '</div>' +
                '<div class="text-[11px] font-bold ' + (esHoy ? 'acc-btn rounded-full w-5 h-5 mx-auto grid place-items-center' : '') + '">' + d + '</div></th>';
        }
        head += '<th class="px-3 text-xs font-bold text-gray-500 uppercase text-center">% Cumpl.</th>';
        let body = '';
        j.indicadores.forEach(ind => {
            let ok = 0, tot = 0, cells = '';
            for (let d = 1; d <= dias; d++) {
                const c = ind.dias[d], f = ymd(new Date(y, m - 1, d));
                if (c) {
                    tot++; if (c.estado === 'cumple') ok++;
                    const tip = fmt(c.valor) + ' ' + (ind.unidad || '') + ' · ' + ESTADO[c.estado].label + (c.meta != null ? ' (meta ' + fmt(c.meta) + ')' : '');
                    cells += '<td class="p-0.5 text-center"><span class="hm-cell hm-' + c.estado + '" title="' + esc(tip) + '" data-fecha="' + f + '">' + (c.estado === 'cumple' ? '✓' : (c.estado === 'disparador' ? '!' : '✗')) + '</span></td>';
                } else {
                    const fut = f > hoy;
                    cells += '<td class="p-0.5 text-center"><span class="hm-cell ' + (fut ? 'hm-futuro' : 'hm-vacio') + '" ' + (fut ? '' : 'data-fecha="' + f + '" title="Sin registrar"') + '>·</span></td>';
                }
            }
            const pct = tot ? Math.round(ok * 100 / tot) : null;
            body += '<tr class="border-t border-gray-100' + (ind.activo ? '' : ' opacity-60') + '"><td class="sticky-col px-3 py-1.5"><div class="text-sm font-semibold text-gray-800 truncate max-w-[220px]" title="' + esc(ind.nombre) + '">' + esc(ind.nombre) + '</div>' +
                '<div class="text-[10px] text-gray-400">' + (ind.configurada ? (ind.sentido === 'menor' ? '≤ ' : '≥ ') + fmt(ind.meta) + ' ' + esc(ind.unidad || '') : 'Sin meta') + '</div></td>' + cells +
                '<td class="px-3 text-center"><span class="text-sm font-bold ' + (pct == null ? 'text-gray-300' : pct >= 80 ? 'text-green-600' : pct >= 50 ? 'text-amber-600' : 'text-red-600') + '">' + (pct == null ? '—' : pct + '%') + '</span></td></tr>';
        });
        if (!j.indicadores.length) body = '<tr><td colspan="' + (dias + 2) + '" class="text-center text-gray-400 py-10">No hay indicadores en este turno.</td></tr>';

        state.personas = j.personas.slice().sort((a, b) => (b.no_cumplidos - a.no_cumplidos) || a.nombre.localeCompare(b.nombre));
        state.pPag = 1; state.pBuscar = '';

        $('#vistaMes').innerHTML =
            '<div class="overflow-x-auto custom-scrollbar border border-gray-100 rounded-xl"><table class="min-w-full"><thead class="bg-gray-50"><tr>' + head + '</tr></thead><tbody>' + body + '</tbody></table></div>' +
            '<p class="text-xs text-gray-400 mt-2"><i class="fas fa-mouse-pointer mr-1"></i>Haz clic en una celda para ir a ese día.</p>' +
            '<div class="mt-8 mb-4 flex flex-col md:flex-row md:items-end justify-between gap-3">' +
              '<div><h3 class="text-lg font-bold text-gray-800 mb-1 flex items-center gap-2"><i class="fas fa-users acc-text"></i> Cumplimiento por persona</h3>' +
              '<p class="text-sm text-gray-500">Días del mes en este turno. Un día cuenta como cumplido si todos los indicadores con compensación en que fue responsable cumplieron.</p></div>' +
              (state.personas.length ? '<div class="relative w-full md:w-64 shrink-0"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>' +
                '<input type="search" id="pBuscar" class="w-full pl-9 pr-3 py-2 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-yellow-400" placeholder="Buscar persona..."></div>' : '') +
            '</div>' +
            '<div id="pGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3"></div>' +
            '<div id="pPager" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4"></div>';
        renderPersonas();
    }

    const P_POR_PAG = 9;
    function renderPersonas() {
        const q = state.pBuscar.trim().toLowerCase();
        const lista = q ? state.personas.filter(p => p.nombre.toLowerCase().indexOf(q) !== -1 || String(p.cargo || '').toLowerCase().indexOf(q) !== -1) : state.personas;
        const paginas = Math.max(1, Math.ceil(lista.length / P_POR_PAG));
        if (state.pPag > paginas) state.pPag = paginas;
        const desde = (state.pPag - 1) * P_POR_PAG;
        const pagina = lista.slice(desde, desde + P_POR_PAG);
        $('#pGrid').innerHTML = pagina.length ? pagina.map(p => {
            const tot = p.cumplidos + p.no_cumplidos, pct = tot ? Math.round(p.cumplidos * 100 / tot) : 0;
            const ini = p.nombre.split(/\s+/).slice(0, 2).map(s => s[0] || '').join('').toUpperCase();
            return '<button type="button" class="text-left bg-white border border-gray-100 rounded-xl p-3 shadow-sm hover:shadow-md hover:border-yellow-400 transition min-w-0" data-persona="' + p.id + '">' +
                '<div class="flex items-center gap-3"><div class="w-9 h-9 rounded-full bg-dark text-brand-400 grid place-items-center text-xs font-bold shrink-0">' + esc(ini) + '</div>' +
                '<div class="min-w-0 flex-1"><div class="font-semibold text-sm text-gray-800 truncate">' + esc(p.nombre) + '</div><div class="text-[10px] uppercase text-gray-400">' + esc(p.cargo) + '</div></div>' +
                '<div class="text-right shrink-0"><div class="text-sm font-bold"><span class="text-green-600">' + p.cumplidos + ' ✓</span> <span class="text-red-600 ml-1">' + p.no_cumplidos + ' ✗</span></div><div class="text-[10px] text-gray-400">' + pct + '%</div></div></div>' +
                '<div class="h-1.5 bg-red-100 rounded-full mt-2.5 overflow-hidden"><div class="h-full bg-green-500 rounded-full" style="width:' + pct + '%"></div></div></button>';
        }).join('') : '<p class="text-gray-400 text-sm col-span-full">' + (q ? 'Nadie coincide con la búsqueda.' : 'Aún no hay responsables asignados en este turno durante el mes.') + '</p>';

        const pager = $('#pPager');
        if (lista.length <= P_POR_PAG) { pager.innerHTML = lista.length ? '<span class="text-xs text-gray-400">' + lista.length + ' persona(s)</span>' : ''; return; }
        let nums = '';
        for (let i = 1; i <= paginas; i++) {
            if (i === 1 || i === paginas || Math.abs(i - state.pPag) <= 1) nums += '<button type="button" class="pg-btn ' + (i === state.pPag ? 'on' : '') + '" data-pag="' + i + '">' + i + '</button>';
            else if (Math.abs(i - state.pPag) === 2) nums += '<span class="px-1 text-gray-400">…</span>';
        }
        pager.innerHTML = '<span class="text-xs text-gray-500">Mostrando ' + (desde + 1) + '–' + Math.min(desde + P_POR_PAG, lista.length) + ' de ' + lista.length + ' personas</span>' +
            '<div class="flex items-center gap-1 flex-wrap justify-center">' +
              '<button type="button" class="pg-btn" data-pag="' + (state.pPag - 1) + '" ' + (state.pPag === 1 ? 'disabled' : '') + '><i class="fas fa-chevron-left"></i></button>' + nums +
              '<button type="button" class="pg-btn" data-pag="' + (state.pPag + 1) + '" ' + (state.pPag === paginas ? 'disabled' : '') + '><i class="fas fa-chevron-right"></i></button>' +
            '</div>';
    }

    $('#vistaMes').addEventListener('click', async e => {
        const cell = e.target.closest('[data-fecha]');
        if (cell) { state.fecha = cell.dataset.fecha; $('#fecha').value = state.fecha; setVista('dia'); return; }
        const pg = e.target.closest('[data-pag]');
        if (pg && !pg.disabled) { state.pPag = Number(pg.dataset.pag); renderPersonas(); return; }
        const p = e.target.closest('[data-persona]');
        if (p) verPersona(Number(p.dataset.persona));
    });
    $('#vistaMes').addEventListener('input', e => {
        if (e.target.id === 'pBuscar') { state.pBuscar = e.target.value; state.pPag = 1; renderPersonas(); }
    });

    async function verPersona(id) {
        const [y, m] = state.mes.split('-').map(Number);
        try {
            const j = await api({ action: 'resumen_persona', usuario_id: id, year: y, month: m });
            const filas = j.dias.map(d => {
                const f = parseYmd(d.fecha);
                const icon = d.cumple === true ? '<span class="text-green-600 font-bold">✓</span>' : d.cumple === false ? '<span class="text-red-600 font-bold">✗</span>' : '<span class="text-gray-400">–</span>';
                return '<tr class="border-t"><td class="py-1.5 pr-2 text-left whitespace-nowrap">' + icon + ' ' + f.toLocaleDateString('es-CO', { weekday: 'short', day: 'numeric' }) + '</td><td class="py-1.5 text-left">' +
                    d.indicadores.map(i => '<div class="text-xs"><b>T' + i.turno + '</b> ' + esc(i.indicador) + ': ' + fmt(i.valor) + ' ' + esc(i.unidad || '') + ' ' + badge(i.estado).replace('badge-st', 'badge-st !py-0 !px-1.5 !text-[10px]') + (i.aplica_compensacion ? '' : ' <span class="text-gray-400">(no aplica)</span>') + '</div>').join('') +
                    '</td></tr>';
            }).join('');
            const link = j.persona.cargo === 'auxiliar' ? '<a class="inline-block mt-4 font-semibold underline" href="' + C_AUX + '?usuario_id=' + j.persona.id + '&mes=' + state.mes + '">Ver compensación variable</a>' : '';
            Swal.fire({
                title: esc(j.persona.nombre), width: 640,
                html: '<p class="text-sm text-gray-500 mb-3">' + MESES[m - 1] + ' ' + y + ' · todos los turnos · <span class="text-green-600 font-bold">' + j.cumplidos + ' ✓</span> <span class="text-red-600 font-bold">' + j.no_cumplidos + ' ✗</span></p>' +
                    (filas ? '<div class="max-h-[50vh] overflow-y-auto"><table class="w-full text-sm">' + filas + '</table></div>' : '<p class="text-gray-400">Sin registros.</p>') + link,
                confirmButtonText: 'Cerrar', confirmButtonColor: '#1a1a1a'
            });
        } catch (err) { toast('error', err.message); }
    }

    // ---------- Navegación ----------
    function setVista(v) {
        state.vista = v;
        document.querySelectorAll('.seg-btn').forEach(b => b.classList.toggle('on', b.dataset.vista === v));
        $('#vistaDia').classList.toggle('hidden', v !== 'dia');
        $('#vistaMes').classList.toggle('hidden', v !== 'mes');
        $('#navDia').classList.toggle('hidden', v !== 'dia');
        $('#navMes').classList.toggle('hidden', v !== 'mes');
        if (v === 'mes') { state.mes = state.fecha.slice(0, 7); $('#mes').value = state.mes; }
        recargar();
    }
    function recargar() { state.vista === 'dia' ? cargarDia() : cargarMes(); }
    function moverDia(n) { const d = parseYmd(state.fecha); d.setDate(d.getDate() + n); state.fecha = ymd(d); $('#fecha').value = state.fecha; cargarDia(); }
    function moverMes(n) { const [y, m] = state.mes.split('-').map(Number); const d = new Date(y, m - 1 + n, 1); state.mes = d.getFullYear() + '-' + pad(d.getMonth() + 1); $('#mes').value = state.mes; cargarMes(); }

    document.querySelectorAll('.seg-btn').forEach(b => b.addEventListener('click', () => setVista(b.dataset.vista)));
    $('#tabs').addEventListener('click', e => {
        const t = e.target.closest('[data-turno]'); if (!t || t.dataset.turno === state.turno) return;
        state.turno = t.dataset.turno; renderTabs(); aplicarTema(); recargar();
    });
    $('#btnPrev').addEventListener('click', () => moverDia(-1));
    $('#btnNext').addEventListener('click', () => moverDia(1));
    $('#btnHoy').addEventListener('click', () => { state.fecha = fechaTurnoActual(); state.turno = turnoActual(); $('#fecha').value = state.fecha; cargarDia(); });
    $('#fecha').addEventListener('change', e => { if (e.target.value) { state.fecha = e.target.value; cargarDia(); } });
    $('#btnMesPrev').addEventListener('click', () => moverMes(-1));
    $('#btnMesNext').addEventListener('click', () => moverMes(1));
    $('#mes').addEventListener('change', e => { if (e.target.value) { state.mes = e.target.value; cargarMes(); } });
    window.addEventListener('beforeunload', e => {
        if (Object.values(timers).length && document.querySelector('[data-f="save"] .fa-pen')) { e.preventDefault(); e.returnValue = ''; }
    });

    $('#fecha').value = state.fecha;
    renderTabs(); aplicarTema(); cargarDia();
})();
</script>
</body>
</html>
