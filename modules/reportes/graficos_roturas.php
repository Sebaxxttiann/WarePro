<?php
require_once '../../core/config.php';

verificarLogin();
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    $where = ["operacion_id = ?"];
    $params = [getOperacionActiva()];

    if (!empty($_GET['fecha_inicio']) && !empty($_GET['fecha_fin'])) {
        $where[] = "DATE(fecha_registro) BETWEEN ? AND ?";
        $params[] = $_GET['fecha_inicio'];
        $params[] = $_GET['fecha_fin'];
    }
    if (!empty($_GET['turno'])) {
        $where[] = "turno = ?";
        $params[] = $_GET['turno'];
    }
    if (!empty($_GET['persona'])) {
        $where[] = "persona_rotura = ?";
        $params[] = $_GET['persona'];
    }

    $whereClause = implode(" AND ", $where);

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_eventos, SUM(unidades) as total_unidades, SUM(precio_rotura) as total_precio FROM roturas WHERE $whereClause");
        $stmt->execute($params);
        $kpis = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT persona_rotura as name, SUM(unidades) as y FROM roturas WHERE $whereClause GROUP BY persona_rotura ORDER BY y DESC LIMIT 10");
        $stmt->execute($params);
        $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT turno as name, SUM(unidades) as y FROM roturas WHERE $whereClause GROUP BY turno ORDER BY y DESC");
        $stmt->execute($params);
        $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT descripcion_material as name, SUM(unidades) as y FROM roturas WHERE $whereClause GROUP BY descripcion_material ORDER BY y DESC LIMIT 10");
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes, SUM(unidades) as unidades, SUM(precio_rotura) as precio FROM roturas WHERE $whereClause GROUP BY mes ORDER BY mes ASC");
        $stmt->execute($params);
        $mesesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $meses = ['categorias' => [], 'unidades' => [], 'precios' => []];
        foreach ($mesesData as $row) {
            $meses['categorias'][] = $row['mes'];
            $meses['unidades'][] = (int)$row['unidades'];
            $meses['precios'][] = (float)$row['precio'];
        }

        $stmt = $pdo->prepare("SELECT casual as name, COUNT(*) as y FROM roturas WHERE $whereClause GROUP BY casual ORDER BY y DESC");
        $stmt->execute($params);
        $causas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'kpis' => [
                'eventos' => $kpis['total_eventos'] ?? 0,
                'unidades' => $kpis['total_unidades'] ?? 0,
                'precio' => $kpis['total_precio'] ?? 0
            ],
            'charts' => [
                'personas' => $personas,
                'turnos' => $turnos,
                'productos' => $productos,
                'meses' => $meses,
                'causas' => $causas
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

$stmtTurnos = $pdo->prepare("SELECT DISTINCT turno FROM roturas WHERE turno IS NOT NULL AND turno != '' AND operacion_id = ? ORDER BY turno");
$stmtTurnos->execute([getOperacionActiva()]);
$turnosList = $stmtTurnos->fetchAll(PDO::FETCH_COLUMN);

$stmtPersonas = $pdo->prepare("SELECT DISTINCT persona_rotura FROM roturas WHERE persona_rotura IS NOT NULL AND persona_rotura != '' AND operacion_id = ? ORDER BY persona_rotura");
$stmtPersonas->execute([getOperacionActiva()]);
$personasList = $stmtPersonas->fetchAll(PDO::FETCH_COLUMN);

require_once '../../core/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { corePlugins: { preflight: false } }</script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

<style>
    :root {
        --gold: #FFD700;
        --gold-glow: rgba(255,215,0,0.15);
        --gold-border: rgba(255,215,0,0.25);
        --ink: #0a0a0a;
        --bg: #f1f3f8;
        --surface: #ffffff;
        --border: #e3e6ef;
        --muted: #7b8096;
        --r-lg: 16px;
        --r-md: 10px;
        --shadow: 0 1px 4px rgba(0,0,0,0.04), 0 6px 20px rgba(0,0,0,0.06);
        --shadow-up: 0 4px 10px rgba(0,0,0,0.07), 0 16px 36px rgba(0,0,0,0.11);
    }

    html, body {
        margin: 0 !important;
        padding: 0 !important;
    }

    body, body * {
        font-family: 'Poppins', sans-serif !important;
    }

    body > .wrapper,
    body > #wrapper,
    body > #page-wrapper,
    body > .page-wrapper,
    .content-wrapper,
    #content-wrapper,
    .main-content,
    #main-content {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }

    .highcharts-credits { display: none !important; }
    .chart-box { min-height: 380px; width: 100%; }

    
    #rot-topbar {
        background: var(--ink);
        border-bottom: 2.5px solid var(--gold);
        height: 62px;
        padding: 0 30px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: sticky;
        top: 0;
        z-index: 300;
    }

    .tb-badge {
        width: 36px;
        height: 36px;
        background: var(--gold);
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ink);
        font-size: 15px;
        flex-shrink: 0;
    }

    .tb-sep { width: 1px; height: 26px; background: #272727; margin: 0 2px; }

    .tb-title {
        font-size: 15px;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.1px;
    }

    .tb-sub {
        font-size: 10px;
        font-weight: 400;
        color: #555;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-top: 1px;
    }

    .tb-live {
        margin-left: auto;
        background: #141414;
        border: 1px solid #262626;
        border-radius: 20px;
        padding: 5px 13px;
        font-size: 10.5px;
        font-weight: 500;
        color: var(--gold);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tb-live-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    
    #rot-body {
        background: var(--bg);
        min-height: calc(100vh - 62px);
        padding: 26px 30px 44px;
    }

    
    .fp-card {
        background: var(--surface);
        border-radius: var(--r-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        margin-bottom: 22px;
        overflow: hidden;
    }

    .fp-head {
        background: var(--ink);
        padding: 12px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .fp-head-left {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .fp-head-icon {
        width: 26px;
        height: 26px;
        background: var(--gold-glow);
        border: 1px solid var(--gold-border);
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gold);
        font-size: 11px;
    }

    .fp-head-title {
        font-size: 10.5px;
        font-weight: 700;
        color: #aaa;
        letter-spacing: 1.1px;
        text-transform: uppercase;
    }

    .fp-clear {
        background: none;
        border: 1px solid #282828;
        border-radius: 6px;
        padding: 4px 12px;
        font-size: 10.5px;
        font-weight: 500;
        color: #666;
        cursor: pointer;
        transition: all 0.17s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .fp-clear:hover { border-color: var(--gold); color: var(--gold); }

    .fp-body {
        padding: 20px 22px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    @media (max-width: 860px) {
        .fp-body { grid-template-columns: 1fr; }
        #rot-body { padding: 18px 14px 32px; }
        #rot-topbar { padding: 0 14px; }
    }

    .fgrp label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        font-weight: 700;
        color: var(--muted);
        letter-spacing: 0.9px;
        text-transform: uppercase;
        margin-bottom: 7px;
    }

    .fgrp label i { color: var(--gold); width: 13px; font-size: 11px; }

    .finput-wrap { position: relative; }

    .finput-wrap .fi {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #c5c9d6;
        font-size: 11.5px;
        pointer-events: none;
        z-index: 1;
    }

    .finput-wrap .fchev {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #c5c9d6;
        font-size: 9px;
    }

    .finput-wrap input,
    .finput-wrap select {
        width: 100%;
        background: #f7f8fc;
        border: 1.5px solid var(--border);
        border-radius: var(--r-md);
        padding: 10px 14px 10px 34px;
        font-size: 12.5px;
        font-weight: 500;
        color: var(--ink);
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
        appearance: none;
        cursor: pointer;
    }

    .finput-wrap input:focus,
    .finput-wrap select:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 3px var(--gold-glow);
        background: #fff;
    }

    .finput-wrap input::placeholder { color: #c4c8d6; font-weight: 400; }

    
    .kpi-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }

    @media (max-width: 860px) { .kpi-row { grid-template-columns: 1fr; } }

    .kpi-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        padding: 20px 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: var(--shadow);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }

    .kpi-card::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2.5px;
        background: var(--ka);
        border-radius: 2px 2px 0 0;
    }

    .kpi-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-up); }

    .kpi-card.ka-ink   { --ka: var(--ink); }
    .kpi-card.ka-gold  { --ka: var(--gold); }
    .kpi-card.ka-green { --ka: #22c55e; }

    .kpi-ico {
        width: 50px; height: 50px;
        border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 19px;
        flex-shrink: 0;
    }

    .ki-ink   { background: var(--ink); color: var(--gold); }
    .ki-gold  { background: var(--gold); color: var(--ink); }
    .ki-green { background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; }

    .kpi-lbl {
        font-size: 9.5px; font-weight: 700;
        color: var(--muted); letter-spacing: 1px; text-transform: uppercase; margin-bottom: 5px;
    }

    .kpi-val {
        font-size: 25px; font-weight: 800;
        color: var(--ink); letter-spacing: -0.8px; line-height: 1;
    }

    
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    @media (max-width: 1060px) { .charts-grid { grid-template-columns: 1fr; } }

    .chart-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        padding: 20px;
        box-shadow: var(--shadow);
    }

    .chart-card.span2 { grid-column: 1 / -1; }

    
    .flatpickr-calendar {
        font-family: 'Poppins', sans-serif !important;
        border-radius: 14px !important;
        box-shadow: 0 8px 40px rgba(0,0,0,0.13) !important;
    }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
        background: var(--ink) !important; border-color: var(--ink) !important;
    }
    .flatpickr-day.inRange {
        background: var(--gold-glow) !important; border-color: transparent !important; color: var(--ink) !important;
    }
    .flatpickr-day:hover { background: var(--gold) !important; color: var(--ink) !important; }
</style>

<div id="rot-topbar">
    <div class="tb-badge"><i class="fas fa-chart-line"></i></div>
    <div class="tb-sep"></div>
    <div>
        <div class="tb-title">Análisis de Roturas</div>
        <div class="tb-sub">WareProSystem · Dashboard analítico</div>
    </div>
    <div class="tb-live">
        <span class="tb-live-dot"></span> En vivo
    </div>
</div>

<div id="rot-body">

    <div class="fp-card">
        <div class="fp-head">
            <div class="fp-head-left">
                <div class="fp-head-icon"><i class="fas fa-sliders-h"></i></div>
                <span class="fp-head-title">Filtros de Consulta</span>
            </div>
            <button class="fp-clear" onclick="clearFilters()">
                <i class="fas fa-rotate-left"></i> Limpiar
            </button>
        </div>

        <div class="fp-body">
            <div class="fgrp">
                <label><i class="fas fa-calendar-alt"></i> Rango de Fechas</label>
                <div class="finput-wrap">
                    <i class="fi fas fa-calendar"></i>
                    <input type="text" id="fechaRango" placeholder="Selecciona un rango…">
                </div>
            </div>

            <div class="fgrp">
                <label><i class="fas fa-user"></i> Operador (OPM)</label>
                <div class="finput-wrap">
                    <i class="fi fas fa-user-circle"></i>
                    <select id="filtroPersona">
                        <option value="">Todos los operadores</option>
                        <?php foreach($personasList as $p): ?>
                            <option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fchev fas fa-chevron-down"></i>
                </div>
            </div>

            <div class="fgrp">
                <label><i class="fas fa-clock"></i> Turno</label>
                <div class="finput-wrap">
                    <i class="fi fas fa-business-time"></i>
                    <select id="filtroTurno">
                        <option value="">Todos los turnos</option>
                        <?php foreach($turnosList as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fchev fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card ka-ink">
            <div class="kpi-ico ki-ink"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <div class="kpi-lbl">Total Incidentes</div>
                <div class="kpi-val" id="kpiEventos">0</div>
            </div>
        </div>
        <div class="kpi-card ka-gold">
            <div class="kpi-ico ki-gold"><i class="fas fa-cube"></i></div>
            <div>
                <div class="kpi-lbl">Unidades Rotas</div>
                <div class="kpi-val" id="kpiUnidades">0</div>
            </div>
        </div>
        <div class="kpi-card ka-green">
            <div class="kpi-ico ki-green"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="kpi-lbl">Precio Acumulado</div>
                <div class="kpi-val" id="kpiPrecio">$0</div>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card"><div id="chartPersonas" class="chart-box"></div></div>
        <div class="chart-card"><div id="chartProductos" class="chart-box"></div></div>
        <div class="chart-card"><div id="chartTurnos" class="chart-box"></div></div>
        <div class="chart-card"><div id="chartCausas" class="chart-box"></div></div>
        <div class="chart-card span2"><div id="chartMeses" class="chart-box"></div></div>
        <div class="chart-card span2"><div id="chartPrecios" class="chart-box"></div></div>
    </div>

</div>

<script>
Highcharts.setOptions({
    colors: ['#0a0a0a', '#FFD700', '#444', '#FFA500', '#f1c40f', '#e67e22', '#d35400'],
    chart: { style: { fontFamily: "'Poppins', sans-serif" }, backgroundColor: 'transparent' },
    title: { style: { fontWeight: '700', fontSize: '15px', color: '#111' }, margin: 22 },
    lang: { numericSymbols: [' mil', ' M', ' G', ' T', ' P', ' E'] }
});

let currentFilters = { fecha_inicio: '', fecha_fin: '', persona: '', turno: '' };
let charts = {};
let fpInst = null;

document.addEventListener('DOMContentLoaded', function () {
    fpInst = flatpickr("#fechaRango", {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: "es",
        onChange: function (dates, str, inst) {
            if (dates.length === 2) {
                currentFilters.fecha_inicio = inst.formatDate(dates[0], "Y-m-d");
                currentFilters.fecha_fin   = inst.formatDate(dates[1], "Y-m-d");
                updateDashboard();
            } else if (dates.length === 0) {
                currentFilters.fecha_inicio = '';
                currentFilters.fecha_fin   = '';
                updateDashboard();
            }
        }
    });

    document.getElementById('filtroPersona').addEventListener('change', function (e) {
        currentFilters.persona = e.target.value;
        updateDashboard();
    });

    document.getElementById('filtroTurno').addEventListener('change', function (e) {
        currentFilters.turno = e.target.value;
        updateDashboard();
    });

    initCharts();
    updateDashboard();
});

function clearFilters() {
    if (fpInst) fpInst.clear();
    document.getElementById('filtroPersona').value = '';
    document.getElementById('filtroTurno').value   = '';
    currentFilters = { fecha_inicio: '', fecha_fin: '', persona: '', turno: '' };
    updateDashboard();
}

function initCharts() {
    charts.personas = Highcharts.chart('chartPersonas', {
        chart: { type: 'bar' },
        title: { text: 'Top Operadores · Mayor Rotura' },
        xAxis: { type: 'category' },
        yAxis: { title: { text: 'Unidades' } },
        legend: { enabled: false },
        plotOptions: { bar: { borderRadius: 5, dataLabels: { enabled: true } } },
        series: [{ name: 'Unidades', colorByPoint: true, data: [] }]
    });

    charts.productos = Highcharts.chart('chartProductos', {
        chart: { type: 'column' },
        title: { text: 'Top Productos Afectados' },
        xAxis: { type: 'category' },
        yAxis: { title: { text: 'Unidades' } },
        legend: { enabled: false },
        plotOptions: { column: { borderRadius: 5, dataLabels: { enabled: true } } },
        series: [{ name: 'Unidades', color: '#FFD700', borderColor: '#0a0a0a', borderWidth: 2, data: [] }]
    });

    charts.turnos = Highcharts.chart('chartTurnos', {
        chart: { type: 'pie' },
        title: { text: 'Distribución por Turno' },
        tooltip: { pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b> ({point.y} unds)' },
        plotOptions: {
            pie: {
                allowPointSelect: true, cursor: 'pointer', innerSize: '58%',
                dataLabels: { enabled: true, format: '<b>{point.name}</b>: {point.percentage:.1f}%' }
            }
        },
        series: [{ name: 'Unidades', colorByPoint: true, data: [] }]
    });

    charts.causas = Highcharts.chart('chartCausas', {
        chart: { type: 'pie' },
        title: { text: 'Principales Causas de Rotura' },
        tooltip: { pointFormat: '{series.name}: <b>{point.y} eventos</b>' },
        plotOptions: {
            pie: { allowPointSelect: true, cursor: 'pointer', dataLabels: { enabled: true, format: '{point.name}' } }
        },
        series: [{ name: 'Eventos', data: [] }]
    });

    charts.meses = Highcharts.chart('chartMeses', {
        chart: { type: 'areaspline' },
        title: { text: 'Tendencia de Unidades Rotas por Mes' },
        xAxis: { categories: [], crosshair: true },
        yAxis: { title: { text: 'Unidades' } },
        tooltip: { shared: true },
        plotOptions: {
            areaspline: {
                fillColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [[0, Highcharts.color('#FFD700').setOpacity(0.42).get('rgba')], [1, Highcharts.color('#FFD700').setOpacity(0).get('rgba')]]
                },
                color: '#0a0a0a', lineWidth: 3, marker: { radius: 5 }
            }
        },
        series: [{ name: 'Unidades Rotas', data: [] }]
    });

    charts.precios = Highcharts.chart('chartPrecios', {
        chart: { type: 'areaspline' },
        title: { text: 'Tendencia de Costos de Rotura por Mes' },
        xAxis: { categories: [], crosshair: true },
        yAxis: { title: { text: 'Costo ($)' }, labels: { format: '${value:,.0f}' } },
        tooltip: { shared: true, valuePrefix: '$', valueDecimals: 0 },
        plotOptions: {
            areaspline: {
                fillColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [[0, Highcharts.color('#22c55e').setOpacity(0.4).get('rgba')], [1, Highcharts.color('#22c55e').setOpacity(0).get('rgba')]]
                },
                color: '#16a34a', lineWidth: 3, marker: { radius: 5 }
            }
        },
        series: [{ name: 'Costo Total', data: [] }]
    });
}

function updateDashboard() {
    const params = new URLSearchParams({
        ajax: '1',
        fecha_inicio: currentFilters.fecha_inicio,
        fecha_fin:    currentFilters.fecha_fin,
        persona:      currentFilters.persona,
        turno:        currentFilters.turno
    });

    fetch(`graficos_roturas.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') return;

            const numFmt   = new Intl.NumberFormat('es-CO');
            const moneyFmt = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

            document.getElementById('kpiEventos').textContent  = numFmt.format(data.kpis.eventos);
            document.getElementById('kpiUnidades').textContent = numFmt.format(data.kpis.unidades);
            document.getElementById('kpiPrecio').textContent   = moneyFmt.format(data.kpis.precio);

            const fmt = arr => arr.map(i => ({ name: i.name, y: parseFloat(i.y) }));

            charts.personas.series[0].setData(fmt(data.charts.personas));
            charts.productos.series[0].setData(fmt(data.charts.productos));
            charts.turnos.series[0].setData(fmt(data.charts.turnos));
            charts.causas.series[0].setData(fmt(data.charts.causas));

            charts.meses.xAxis[0].setCategories(data.charts.meses.categorias);
            charts.meses.series[0].setData(data.charts.meses.unidades);

            charts.precios.xAxis[0].setCategories(data.charts.meses.categorias);
            charts.precios.series[0].setData(data.charts.meses.precios);
        })
        .catch(err => console.error(err));
}
</script>

</body>
</html>