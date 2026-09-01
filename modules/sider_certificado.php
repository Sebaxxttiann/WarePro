<?php
require_once '../core/config.php';

verificarLogin();
date_default_timezone_set('America/Bogota');

$nombre_sesion_activa = $_SESSION['nombre'] ?? 'Usuario';
$fecha_hoy = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'agregar') {
    try {
        $stmt = $pdo->prepare("INSERT INTO sider_certificados (fecha, placa, planta_destino, cantidad_estibas, tipo_envase, cantidad_estibas_2, tipo_envase_2, factura, supervisor, facturador, operacion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['fecha'],
            strtoupper(limpiarDatos($_POST['placa'])),
            limpiarDatos($_POST['planta_destino']),
            (int)$_POST['cantidad_estibas'],
            limpiarDatos($_POST['tipo_envase']),
            (int)$_POST['cantidad_estibas_2'],
            !empty($_POST['tipo_envase_2']) ? limpiarDatos($_POST['tipo_envase_2']) : null,
            (int)$_POST['factura'],
            limpiarDatos($_POST['supervisor']),
            limpiarDatos($_POST['facturador']),
            getOperacionActiva()
        ]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Certificado agregado correctamente']);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'grafica_datos') {
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-01-01');
    $fecha_fin    = $_GET['fecha_fin']    ?? date('Y-12-31');
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(fecha,'%Y-%m') as mes,
               DATE_FORMAT(fecha,'%M %Y') as mes_nombre,
               COUNT(*) as total_registros
        FROM sider_certificados
        WHERE fecha BETWEEN ? AND ? AND operacion_id = ?
        GROUP BY DATE_FORMAT(fecha,'%Y-%m')
        ORDER BY mes ASC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin, getOperacionActiva()]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'registros_mes') {
    $mes = $_GET['mes'];
    $stmt = $pdo->prepare("SELECT * FROM sider_certificados WHERE DATE_FORMAT(fecha,'%Y-%m')=? AND operacion_id = ? ORDER BY fecha DESC, created_at DESC");
    $stmt->execute([$mes, getOperacionActiva()]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'todos_registros') {
    $stmt = $pdo->prepare("SELECT * FROM sider_certificados WHERE operacion_id = ? ORDER BY fecha DESC, created_at DESC LIMIT 50");
    $stmt->execute([getOperacionActiva()]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

require_once '../core/header.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{poppins:['Poppins','sans-serif']}}}}</script>

<style>
    *{box-sizing:border-box}
    body{font-family:'Poppins',sans-serif;background:#f8f9fa;color:#1a1a1a}
    :root{
        --gold:#FFD700;--gold-d:#FFA500;--black:#1a1a1a;--black-m:#2d2d2d;
        --bg:#f8f9fa;--border:#e2e8f0;--text-g:#6c757d;--rad:16px;
        --gold-grad:linear-gradient(135deg,#FFD700 0%,#FFA500 100%);
        --black-grad:linear-gradient(135deg,#1a1a1a 0%,#2d2d2d 100%);
        --sh-md:0 8px 25px rgba(0,0,0,.1);--sh-lg:0 20px 40px rgba(0,0,0,.15);
        --tr:all .3s cubic-bezier(.4,0,.2,1);
    }
    .cscroll::-webkit-scrollbar{width:6px;height:6px}
    .cscroll::-webkit-scrollbar-track{background:var(--bg);border-radius:4px}
    .cscroll::-webkit-scrollbar-thumb{background:var(--gold);border-radius:4px}
    @keyframes gPulse{0%{box-shadow:0 0 0 0 rgba(255,215,0,.7)}70%{box-shadow:0 0 0 8px rgba(255,215,0,0)}100%{box-shadow:0 0 0 0 rgba(255,215,0,0)}}
    .gpulse{animation:gPulse 2s infinite}
    .fi{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:.83rem;font-family:'Poppins',sans-serif;background:#fff;color:var(--black);transition:border-color .2s,box-shadow .2s;-webkit-appearance:none;appearance:none}
    .fi:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(255,215,0,.18)}
    .fi:disabled{background:var(--bg);color:var(--text-g);cursor:not-allowed}
    select.fi{cursor:pointer;background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");background-position:right .6rem center;background-repeat:no-repeat;background-size:1.2em;padding-right:2.2rem}
    .lbl{display:block;font-size:.72rem;font-weight:600;color:var(--black-m);margin-bottom:5px}
    .lbl i{color:var(--gold-d);margin-right:3px}
    .sec{position:relative;background:var(--bg);border:1px solid var(--border);border-radius:var(--rad);padding:1.1rem;margin-bottom:1rem;overflow:hidden}
    .sec::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--gold-grad)}
    .sec-title{font-size:.85rem;font-weight:700;color:var(--black);display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.6rem;border-bottom:1.5px solid var(--border)}
    .sec-title i{color:var(--gold)}
    .card{background:#fff;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-md);overflow:hidden}
    .rtable{width:100%;border-collapse:collapse;font-size:.77rem}
    .rtable thead tr{background:var(--black-grad);border-bottom:2px solid var(--gold)}
    .rtable th{padding:.7rem .85rem;text-align:left;font-weight:700;color:#fff;white-space:nowrap}
    .rtable td{padding:.65rem .85rem;border-bottom:1px solid #f1f5f9;color:#444;vertical-align:middle}
    .rtable tbody tr{transition:background .15s}
    .rtable tbody tr:hover{background:rgba(255,215,0,.05)}
    .placa-b{background:var(--gold-grad);color:var(--black);padding:3px 10px;border-radius:7px;font-weight:700;font-size:.76rem;display:inline-block}
    .sb-act{background:#dcfce7;color:#16a34a;border:1px solid #86efac;padding:2px 9px;border-radius:20px;font-size:.71rem;font-weight:700}

    
    .flatpickr-calendar{border-radius:14px!important;border:1.5px solid var(--gold)!important;box-shadow:var(--sh-lg)!important;font-family:'Poppins',sans-serif!important;overflow:hidden}
    .flatpickr-months{background:var(--black-grad)!important;padding:8px 0}
    .flatpickr-month,.flatpickr-current-month,.flatpickr-current-month input.cur-year{color:var(--gold)!important;font-weight:700}
    .flatpickr-current-month{font-size:.95rem!important}
    .flatpickr-prev-month svg,.flatpickr-next-month svg{fill:var(--gold)!important}
    .flatpickr-prev-month:hover,.flatpickr-next-month:hover{background:rgba(255,215,0,.15)!important;border-radius:8px}
    .flatpickr-weekdays{background:#2d2d2d!important}
    .flatpickr-weekday{color:var(--gold-d)!important;font-weight:600;font-size:.73rem}
    .flatpickr-day{border-radius:8px!important;font-size:.79rem;font-family:'Poppins',sans-serif;color:var(--black)}
    .flatpickr-day:hover{background:rgba(255,215,0,.15)!important;border-color:var(--gold)!important}
    .flatpickr-day.selected,.flatpickr-day.startRange,.flatpickr-day.endRange{background:var(--gold-grad)!important;border-color:var(--gold-d)!important;color:var(--black)!important;font-weight:700}
    .flatpickr-day.inRange{background:rgba(255,215,0,.2)!important;border-color:transparent!important;color:var(--black)!important;box-shadow:-5px 0 0 rgba(255,215,0,.2),5px 0 0 rgba(255,215,0,.2)!important}
    .flatpickr-day.today{border-color:var(--gold-d)!important}
    .flatpickr-day.flatpickr-disabled{color:#ccc!important}

    .swal2-popup{border-radius:var(--rad)!important;font-family:'Poppins',sans-serif!important;border:2px solid var(--gold)!important}
    .swal2-title{color:var(--black)!important;font-weight:700!important}
    .swal2-confirm{background:var(--gold-grad)!important;color:var(--black)!important;border-radius:8px!important;font-weight:700!important;border:none!important}
    .swal2-cancel{background:var(--text-g)!important;border-radius:8px!important;font-weight:600!important;border:none!important}

    @media(max-width:640px){.h-sm{display:none!important}}
    @media(max-width:420px){.h-xs{display:none!important}}
    #recordsSection{display:none}
    #formPanel{display:none}
</style>

<div style="max-width:1400px;margin:0 auto;padding:1rem .75rem;">

    
    <div style="position:relative;background:var(--black-grad);border-radius:var(--rad);padding:clamp(1.2rem,4vw,2.5rem);margin-bottom:1.25rem;overflow:hidden;box-shadow:var(--sh-lg);">
        <div style="position:absolute;inset:0;background:radial-gradient(circle at 80% 50%,rgba(255,215,0,.08) 0%,transparent 60%);pointer-events:none;"></div>
        <div style="position:relative;z-index:2;display:flex;align-items:center;gap:clamp(.75rem,3vw,1.5rem);">
            <div class="gpulse" style="flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--gold-grad);color:var(--black);border-radius:var(--rad);width:clamp(44px,8vw,64px);height:clamp(44px,8vw,64px);font-size:clamp(1.2rem,3.5vw,1.8rem);">
                <i class="fas fa-certificate"></i>
            </div>
            <div>
                <h1 style="color:var(--gold);font-weight:800;margin:0;line-height:1.2;font-size:clamp(1.1rem,3.5vw,2rem);">Sider Certificados</h1>
                <p style="color:#cbd5e1;font-size:clamp(.72rem,2vw,.95rem);margin-top:4px;">Sistema de gestión y seguimiento de certificados</p>
            </div>
        </div>
    </div>

    
    <div style="background:#fff;border-radius:var(--rad);padding:.9rem 1.25rem;margin-bottom:1.25rem;box-shadow:var(--sh-md);border:1px solid var(--border);display:flex;align-items:flex-end;flex-wrap:wrap;gap:1rem;">
        <button id="toggleFormBtn" onclick="toggleForm()"
            style="display:inline-flex;align-items:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.65rem 1.1rem;border-radius:12px;font-weight:700;cursor:pointer;font-size:.83rem;font-family:'Poppins',sans-serif;box-shadow:0 4px 6px rgba(0,0,0,.08);transition:var(--tr);white-space:nowrap;flex-shrink:0;"
            onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-md)'"
            onmouseout="this.style.transform='';this.style.boxShadow='0 4px 6px rgba(0,0,0,.08)'">
            <i class="fas fa-plus-circle" id="toggleIcon"></i>
            <span id="toggleText">Nuevo Certificado</span>
        </button>

        <div style="flex:1;min-width:200px;">
            <label class="lbl"><i class="fas fa-calendar-alt"></i> Rango de fechas</label>
            <div style="position:relative;">
                <input type="text" id="dateRange" placeholder="Seleccionar rango…" readonly
                    style="width:100%;padding:9px 12px 9px 34px;border:1.5px solid var(--border);border-radius:10px;font-size:.83rem;font-family:'Poppins',sans-serif;background:#fff;color:var(--black);cursor:pointer;transition:border-color .2s;">
                <i class="fas fa-calendar-alt" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gold-d);font-size:.78rem;pointer-events:none;"></i>
            </div>
        </div>

        <button onclick="limpiarFiltros()"
            style="display:inline-flex;align-items:center;gap:.5rem;background:#6c757d;color:#fff;border:none;padding:.65rem 1rem;border-radius:10px;font-weight:600;cursor:pointer;font-size:.8rem;font-family:'Poppins',sans-serif;transition:var(--tr);white-space:nowrap;flex-shrink:0;"
            onmouseover="this.style.background='#495057'" onmouseout="this.style.background='#6c757d'">
            <i class="fas fa-eraser"></i> Limpiar
        </button>

        <span style="display:inline-flex;align-items:center;gap:.5rem;font-size:.78rem;font-weight:700;color:var(--black);background:var(--bg);border:2px solid var(--gold);padding:.4rem .9rem;border-radius:20px;white-space:nowrap;flex-shrink:0;">
            <i class="fas fa-database" style="color:var(--gold);"></i>
            <span id="totalRecordsCount">0 registros</span>
        </span>
    </div>

    
    <div id="formPanel" style="margin-bottom:1.25rem;">
        <div class="card">
            <div style="background:var(--black-grad);padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;border-bottom:2px solid var(--gold);">
                <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.85rem,3vw,1.05rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                    <i class="fas fa-plus-circle"></i> Agregar Nuevo Certificado
                </h2>
                <button onclick="toggleForm()" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:transparent;border:none;color:#fff;font-size:1.4rem;cursor:pointer;font-weight:700;transition:var(--tr);"
                    onmouseover="this.style.background='rgba(255,215,0,.2)';this.style.color='var(--gold)'"
                    onmouseout="this.style.background='transparent';this.style.color='#fff'">&times;</button>
            </div>
            <div style="padding:1.1rem;">
            <form id="certificadoForm">

                <div class="sec">
                    <div class="sec-title"><i class="fas fa-info-circle"></i> Información General</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:.85rem;">
                        <div>
                            <label class="lbl"><i class="fas fa-calendar-day"></i> Fecha</label>
                            <input type="date" name="fecha" class="fi" value="<?php echo $fecha_hoy; ?>" required>
                        </div>
                        <div>
                            <label class="lbl"><i class="fas fa-user-tie"></i> Supervisor (Usuario actual)</label>
                            <input type="text" class="fi" value="<?php echo htmlspecialchars($nombre_sesion_activa); ?>" disabled>
                            <input type="hidden" name="supervisor" value="<?php echo htmlspecialchars($nombre_sesion_activa); ?>">
                        </div>
                        <div>
                            <label class="lbl"><i class="fas fa-truck"></i> Placa del Vehículo</label>
                            <input type="text" name="placa" id="placaInput" class="fi" style="text-transform:uppercase;" placeholder="ABC123" required>
                        </div>
                        <div>
                            <label class="lbl"><i class="fas fa-map-marker-alt"></i> Planta Destino</label>
                            <select name="planta_destino" class="fi" required>
                                <option value="">Seleccionar planta…</option>
                                <option value="Bucaramanga">Bucaramanga</option>
                                <option value="Tocancipa">Tocancipa</option>
                                <option value="Tibasosa">Tibasosa</option>
                                <option value="Barranquilla">Barranquilla</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl"><i class="fas fa-file-invoice-dollar"></i> Número de Factura</label>
                            <input type="number" name="factura" class="fi" placeholder="0" required>
                        </div>
                        <div>
                            <label class="lbl"><i class="fas fa-user-check"></i> Facturador</label>
                            <select name="facturador" class="fi" required id="facturador-select">
                                <option value="">Seleccionar facturador…</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="sec">
                    <div class="sec-title"><i class="fas fa-boxes"></i> Estibas y Envases</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:.85rem;">
                        <div>
                            <label class="lbl"><i class="fas fa-boxes"></i> Cant. Estibas</label>
                            <input type="number" name="cantidad_estibas" class="fi" min="0" placeholder="0" required>
                        </div>
                        <div>
                            <label class="lbl"><i class="fas fa-archive"></i> Tipo de Envase</label>
                            <select name="tipo_envase" class="fi" required>
                                <option value="">Seleccionar tipo…</option>
                                <option value="Marron 1000">Marrón 1000ml</option>
                                <option value="Marron 750">Marrón 750ml</option>
                                <option value="Flint 1000">Flint 1000ml</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl" style="color:var(--text-g);"><i class="fas fa-boxes"></i> Cant. Estibas 2 <span style="font-weight:400;">(Opc.)</span></label>
                            <input type="number" name="cantidad_estibas_2" class="fi" min="0" value="0">
                        </div>
                        <div>
                            <label class="lbl" style="color:var(--text-g);"><i class="fas fa-archive"></i> Tipo Envase 2 <span style="font-weight:400;">(Opc.)</span></label>
                            <select name="tipo_envase_2" class="fi">
                                <option value="">Seleccionar tipo…</option>
                                <option value="Marron 1000">Marrón 1000ml</option>
                                <option value="Marron 750">Marrón 750ml</option>
                                <option value="Flint 1000">Flint 1000ml</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit"
                    style="width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.95rem 2rem;border-radius:var(--rad);font-weight:800;cursor:pointer;font-size:.9rem;font-family:'Poppins',sans-serif;box-shadow:var(--sh-md);transition:var(--tr);"
                    onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-lg)'"
                    onmouseout="this.style.transform='';this.style.boxShadow='var(--sh-md)'">
                    <i class="fas fa-save"></i> Guardar Certificado
                </button>
            </form>
            </div>
        </div>
    </div>

    
    <div class="card" style="margin-bottom:1.25rem;padding:1.25rem;">
        <div class="sec-title"><i class="fas fa-chart-bar"></i> Análisis de Registros por Mes</div>
        <div style="position:relative;height:320px;background:#fff;border-radius:12px;padding:.5rem;">
            <canvas id="chartRegistros"></canvas>
        </div>
    </div>

    
    <div class="card" id="allRecordsSection" style="margin-bottom:1.25rem;">
        <div style="background:var(--black-grad);padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;border-bottom:2px solid var(--gold);">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.88rem,3vw,1.1rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-table"></i> Todos los Registros
            </h2>
            <span class="sb-act" id="totalRecordsCount2">0 registros</span>
        </div>
        <div class="cscroll" style="overflow-x:auto;max-height:65vh;">
            <table class="rtable" id="allRecordsTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar" style="color:var(--gold);margin-right:4px;"></i>Fecha</th>
                        <th><i class="fas fa-truck" style="color:var(--gold);margin-right:4px;"></i>Placa</th>
                        <th class="h-sm">Destino</th>
                        <th class="h-sm">Estibas</th>
                        <th class="h-sm">Envase</th>
                        <th class="h-xs">Estibas 2</th>
                        <th class="h-xs">Envase 2</th>
                        <th class="h-sm">Factura</th>
                        <th class="h-sm">Supervisor</th>
                        <th class="h-sm">Facturador</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    
    <div class="card" id="recordsSection" style="margin-bottom:1.25rem;">
        <div style="background:var(--black-grad);padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;border-bottom:2px solid var(--gold);">
            <h2 id="recordsTitle" style="color:var(--gold);font-weight:700;font-size:clamp(.88rem,3vw,1.1rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-table"></i> Detalle de Registros
            </h2>
            <span class="sb-act" id="monthCount"></span>
        </div>
        <div class="cscroll" style="overflow-x:auto;max-height:65vh;">
            <table class="rtable" id="recordsTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar" style="color:var(--gold);margin-right:4px;"></i>Fecha</th>
                        <th><i class="fas fa-truck" style="color:var(--gold);margin-right:4px;"></i>Placa</th>
                        <th class="h-sm">Destino</th>
                        <th class="h-sm">Estibas</th>
                        <th class="h-sm">Envase</th>
                        <th class="h-xs">Estibas 2</th>
                        <th class="h-xs">Envase 2</th>
                        <th class="h-sm">Factura</th>
                        <th class="h-sm">Supervisor</th>
                        <th class="h-sm">Facturador</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
let chart = null;
let fechaInicio = '<?php echo date('Y-01-01'); ?>';
let fechaFin    = '<?php echo date('Y-12-31'); ?>';
let formOpen    = false;

function toggleForm() {
    const panel = document.getElementById('formPanel');
    const icon  = document.getElementById('toggleIcon');
    const text  = document.getElementById('toggleText');
    formOpen = !formOpen;
    panel.style.display = formOpen ? 'block' : 'none';
    icon.className = formOpen ? 'fas fa-times' : 'fas fa-plus-circle';
    text.textContent = formOpen ? 'Cerrar formulario' : 'Nuevo Certificado';
    if (formOpen) setTimeout(() => panel.scrollIntoView({ behavior:'smooth', block:'start' }), 50);
}

flatpickr('#dateRange', {
    mode: 'range',
    locale: 'es',
    dateFormat: 'd/m/Y',
    defaultDate: [fechaInicio, fechaFin],
    showMonths: window.innerWidth > 640 ? 2 : 1,
    disableMobile: true,
    onChange: function(selectedDates) {
        if (selectedDates.length === 2) {
            fechaInicio = fmt(selectedDates[0]);
            fechaFin    = fmt(selectedDates[1]);
            actualizarGrafica();
        }
    }
});

function fmt(d) {
    return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}

function limpiarFiltros() {
    fechaInicio = '<?php echo date('Y-01-01'); ?>';
    fechaFin    = '<?php echo date('Y-12-31'); ?>';
    document.getElementById('recordsSection').style.display = 'none';
    const fp = document.getElementById('dateRange')._flatpickr;
    if (fp) fp.setDate([fechaInicio, fechaFin]);
    actualizarGrafica();
}

function buildRow(r) {
    const fecha = new Date(r.fecha + 'T00:00:00').toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
    return `
        <td style="white-space:nowrap;font-weight:600;">${fecha}</td>
        <td><span class="placa-b">${r.placa}</span></td>
        <td class="h-sm" style="white-space:nowrap;"><i class="fas fa-map-marker-alt" style="color:var(--gold-d);margin-right:4px;"></i>${r.planta_destino}</td>
        <td class="h-sm"><strong style="color:#16a34a;">${r.cantidad_estibas}</strong> <small style="color:var(--text-g);">uds</small></td>
        <td class="h-sm"><span style="background:rgba(22,163,74,.1);color:#16a34a;padding:2px 8px;border-radius:6px;font-size:.73rem;">${r.tipo_envase}</span></td>
        <td class="h-xs">${r.cantidad_estibas_2?`<strong style="color:#0891b2;">${r.cantidad_estibas_2}</strong>`:'<span style="color:#ccc;">—</span>'}</td>
        <td class="h-xs">${r.tipo_envase_2?`<span style="background:rgba(8,145,178,.1);color:#0891b2;padding:2px 8px;border-radius:6px;font-size:.73rem;">${r.tipo_envase_2}</span>`:'<span style="color:#ccc;">—</span>'}</td>
        <td class="h-sm"><i class="fas fa-file-invoice" style="color:var(--gold-d);margin-right:3px;"></i><strong>${r.factura}</strong></td>
        <td class="h-sm" style="white-space:nowrap;"><i class="fas fa-user-tie" style="color:#7c3aed;margin-right:3px;"></i>${r.supervisor}</td>
        <td class="h-sm" style="white-space:nowrap;"><i class="fas fa-user-check" style="color:#059669;margin-right:3px;"></i>${r.facturador}</td>`;
}

function emptyRow(msg) {
    return `<tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-g);"><i class="fas fa-inbox" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:var(--border);"></i><strong>${msg}</strong></td></tr>`;
}

function cargarTodosLosRegistros() {
    fetch('sider_certificado.php?action=todos_registros')
        .then(r => r.json())
        .then(data => {
            const tbody = document.querySelector('#allRecordsTable tbody');
            const label = data.length + ' registros';
            document.getElementById('totalRecordsCount').textContent  = label;
            document.getElementById('totalRecordsCount2').textContent = label;
            tbody.innerHTML = '';
            if (!data.length) { tbody.innerHTML = emptyRow('No hay registros disponibles'); return; }
            data.forEach(r => { const tr = document.createElement('tr'); tr.innerHTML = buildRow(r); tbody.appendChild(tr); });
        }).catch(console.error);
}

function cargarFacturadores() {
    fetch('../api/sider/get_facturadores.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const sel = document.getElementById('facturador-select');
            sel.innerHTML = '<option value="">Seleccionar facturador…</option>';
            data.facturadores.forEach(f => { const o = document.createElement('option'); o.value=f; o.textContent=f; sel.appendChild(o); });
        }).catch(console.error);
}

function actualizarGrafica() {
    fetch(`sider_certificado.php?action=grafica_datos&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`)
        .then(r => r.json())
        .then(crearGrafica)
        .catch(console.error);
}

function crearGrafica(datos) {
    const ctx = document.getElementById('chartRegistros').getContext('2d');
    if (chart) chart.destroy();
    const meses  = datos.map(d => d.mes_nombre);
    const valores = datos.map(d => d.total_registros);
    const mesIds  = datos.map(d => d.mes);
    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: meses,
            datasets: [{ label:'Certificados Registrados', data:valores,
                backgroundColor:'rgba(255,215,0,.82)', borderColor:'rgba(255,165,0,1)',
                borderWidth:2, borderRadius:10, borderSkipped:false,
                hoverBackgroundColor:'rgba(255,215,0,.96)', hoverBorderWidth:3 }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            interaction:{ intersect:false, mode:'index' },
            plugins: {
                legend:{ display:true, position:'top', labels:{ color:'#1a1a1a', font:{size:12,weight:'bold'}, padding:14, usePointStyle:true, pointStyle:'rectRounded' } },
                tooltip:{ backgroundColor:'rgba(26,26,26,.93)', titleColor:'#FFD700', bodyColor:'#fff',
                    borderColor:'#FFD700', borderWidth:1.5, cornerRadius:10, displayColors:false,
                    callbacks:{ label:c=>`Certificados: ${c.parsed.y}`, afterLabel:()=>'Haz clic para ver detalles' } }
            },
            scales:{
                y:{ beginAtZero:true, ticks:{stepSize:1,color:'#666',font:{size:11}}, grid:{color:'rgba(0,0,0,.07)'}, border:{display:false} },
                x:{ ticks:{color:'#666',font:{size:11}}, grid:{display:false}, border:{display:false} }
            },
            onClick:(ev,els)=>{ if(!els.length) return; const i=els[0].index; mostrarRegistrosMes(mesIds[i],meses[i]); },
            onHover:(ev,els)=>{ ev.native.target.style.cursor=els.length?'pointer':'default'; }
        }
    });
}

function mostrarRegistrosMes(mes, nombre) {
    Swal.fire({ title:'Cargando…', text:`Obteniendo datos de ${nombre}`, allowOutsideClick:false, showConfirmButton:false, didOpen:()=>Swal.showLoading() });
    fetch(`sider_certificado.php?action=registros_mes&mes=${mes}`)
        .then(r => r.json())
        .then(data => {
            Swal.close();
            const sec   = document.getElementById('recordsSection');
            document.getElementById('recordsTitle').innerHTML = `<i class="fas fa-table"></i> Registros de ${nombre}`;
            document.getElementById('monthCount').textContent = data.length + ' registros';
            const tbody = document.querySelector('#recordsTable tbody');
            tbody.innerHTML = '';
            if (!data.length) { tbody.innerHTML = emptyRow('No hay registros para este mes'); }
            else { data.forEach(r => { const tr=document.createElement('tr'); tr.innerHTML=buildRow(r); tbody.appendChild(tr); }); }
            sec.style.display = 'block';
            sec.scrollIntoView({ behavior:'smooth', block:'start' });
        }).catch(() => Swal.fire({ title:'Error', text:'No se pudieron cargar los registros', icon:'error', confirmButtonColor:'#FFD700' }));
}

document.getElementById('certificadoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({ title:'Guardando…', text:'Por favor espera', allowOutsideClick:false, showConfirmButton:false, didOpen:()=>Swal.showLoading() });
    const fd = new FormData(this);
    fd.append('action', 'agregar');
    fetch('sider_certificado.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ title:'¡Guardado!', text:data.message, icon:'success', confirmButtonColor:'#FFD700', confirmButtonText:'Perfecto' })
                .then(() => { this.reset(); toggleForm(); actualizarGrafica(); cargarTodosLosRegistros(); });
            } else {
                Swal.fire({ title:'Error', text:data.message, icon:'error', confirmButtonColor:'#FFD700' });
            }
        }).catch(() => Swal.fire({ title:'Error inesperado', icon:'error', confirmButtonColor:'#FFD700' }));
});

document.getElementById('placaInput').addEventListener('input', function() { this.value = this.value.toUpperCase(); });

document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 'n') { e.preventDefault(); toggleForm(); }
    if (e.key === 'Escape' && formOpen) toggleForm();
});

document.addEventListener('DOMContentLoaded', () => {
    cargarFacturadores();
    actualizarGrafica();
    cargarTodosLosRegistros();
});
</script>