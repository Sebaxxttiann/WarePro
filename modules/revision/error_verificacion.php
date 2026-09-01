<?php
require_once '../../core/config.php';

verificarLogin();
if (!function_exists('limpiarDatos')) {
    function limpiarDatos($dato) {
        return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
    }
}

date_default_timezone_set('America/Bogota');
$fecha_hoy = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'agregar') {
    try {
        $stmt = $pdo->prepare("INSERT INTO error_verificacion (marca_temporal, reportado_por, tipo_novedad, placa_completa, sku, descripcion, cantidad_unidad_presentacion, verificador_responsable, observaciones, turno, novedad_genero_rechazo, auxiliar_responsable, operacion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['marca_temporal'],
            limpiarDatos($_POST['reportado_por']),
            limpiarDatos($_POST['tipo_novedad']),
            strtoupper(limpiarDatos($_POST['placa_completa'])),
            limpiarDatos($_POST['sku']),
            limpiarDatos($_POST['descripcion']),
            (int)$_POST['cantidad_unidad_presentacion'],
            limpiarDatos($_POST['verificador_responsable']),
            limpiarDatos($_POST['observaciones']),
            $_POST['turno'],
            $_POST['novedad_genero_rechazo'],
            limpiarDatos($_POST['auxiliar_responsable']),
            getOperacionActiva()
        ]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Error de verificación registrado correctamente']);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'obtener_personal') {
    try {
        $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE activo = 1 AND operacion_id = ? ORDER BY nombre ASC");
        $stmt->execute([getOperacionActiva()]);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll());
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'obtener_placas') {
    try {
        $stmt = $pdo->prepare("SELECT id, placa FROM placas WHERE operacion_id = ? ORDER BY placa ASC");
        $stmt->execute([getOperacionActiva()]);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll());
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'obtener_productos') {
    try {
        $buscar = $_GET['buscar'] ?? '';
        $sql = "SELECT id_material, material FROM productos";
        $params = [];
        if (!empty($buscar)) {
            $sql .= " WHERE id_material LIKE ? OR material LIKE ?";
            $params = ["%$buscar%", "%$buscar%"];
        }
        $sql .= " ORDER BY material ASC LIMIT 800";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll());
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'grafica_datos') {
    try {
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-01-01');
        $fecha_fin    = $_GET['fecha_fin']    ?? date('Y-12-31');
        $verificador_filtro = $_GET['verificador'] ?? '';
        $sql = "SELECT verificador_responsable, COUNT(*) as total_errores FROM error_verificacion WHERE marca_temporal BETWEEN ? AND ? AND operacion_id = ?";
        $params = [$fecha_inicio, $fecha_fin, getOperacionActiva()];
        if (!empty($verificador_filtro)) { $sql .= " AND verificador_responsable LIKE ?"; $params[] = "%$verificador_filtro%"; }
        $sql .= " GROUP BY verificador_responsable ORDER BY total_errores DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll());
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'todos_registros') {
    try {
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-01-01');
        $fecha_fin    = $_GET['fecha_fin']    ?? date('Y-12-31');
        $verificador_filtro = $_GET['verificador'] ?? '';
        $sql = "SELECT * FROM error_verificacion WHERE marca_temporal BETWEEN ? AND ? AND operacion_id = ?";
        $params = [$fecha_inicio, $fecha_fin, getOperacionActiva()];
        if (!empty($verificador_filtro)) { $sql .= " AND verificador_responsable LIKE ?"; $params[] = "%$verificador_filtro%"; }
        $sql .= " ORDER BY marca_temporal DESC, created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll());
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'obtener_verificadores') {
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT verificador_responsable FROM error_verificacion WHERE operacion_id = ? ORDER BY verificador_responsable ASC");
        $stmt->execute([getOperacionActiva()]);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll());
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

require_once '../../core/header.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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

    @keyframes fadeIn  {from{opacity:0}to{opacity:1}}
    @keyframes slideUp {from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
    @keyframes gPulse  {0%{box-shadow:0 0 0 0 rgba(255,215,0,.7)}70%{box-shadow:0 0 0 8px rgba(255,215,0,0)}100%{box-shadow:0 0 0 0 rgba(255,215,0,0)}}
    .m-bg  {animation:fadeIn  .22s ease-out}
    .m-box {animation:slideUp .28s ease-out}
    .gpulse{animation:gPulse 2s infinite}

    
    .fi{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:.83rem;font-family:'Poppins',sans-serif;background:#fff;color:var(--black);transition:border-color .2s,box-shadow .2s;-webkit-appearance:none;appearance:none}
    .fi:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(255,215,0,.18)}
    .fi:disabled{background:var(--bg);color:var(--text-g);cursor:not-allowed}
    textarea.fi{resize:vertical;min-height:72px}
    select.fi{cursor:pointer;background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");background-position:right .6rem center;background-repeat:no-repeat;background-size:1.2em;padding-right:2.2rem}

    .lbl{display:block;font-size:.72rem;font-weight:600;color:var(--black-m);margin-bottom:5px}
    .lbl i{color:var(--gold-d);margin-right:3px}

    
    .sec{position:relative;background:var(--bg);border:1px solid var(--border);border-radius:var(--rad);padding:1.1rem;margin-bottom:1rem;overflow:hidden}
    .sec::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--gold-grad)}
    .sec-title{font-size:.85rem;font-weight:700;color:var(--black);display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.55rem;border-bottom:1.5px solid var(--border)}
    .sec-title i{color:var(--gold)}

    
    .sd {position:relative}
    .sdl{position:absolute;top:calc(100% + 3px);left:0;right:0;background:#fff;border:1.5px solid var(--gold);border-radius:10px;max-height:195px;overflow-y:auto;z-index:9999;box-shadow:0 8px 24px rgba(255,215,0,.18);display:none}
    .sdl.open{display:block}
    .sdl li{padding:8px 12px;cursor:pointer;font-size:.8rem;color:var(--black);list-style:none;transition:background .12s}
    .sdl li:hover{background:#fffbeb}
    .sdl li.nores{color:#94a3b8;cursor:default}
    .sdl li.nores:hover{background:transparent}

    
    .card{background:#fff;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-md);overflow:hidden}

    
    .rtable{width:100%;border-collapse:collapse;font-size:.76rem}
    .rtable thead tr{background:var(--black-grad);border-bottom:2px solid var(--gold)}
    .rtable th{padding:.7rem .8rem;text-align:left;font-weight:700;color:#fff;white-space:nowrap;font-size:.72rem;text-transform:uppercase;letter-spacing:.4px}
    .rtable td{padding:.6rem .8rem;border-bottom:1px solid #f1f5f9;color:#444;vertical-align:middle}
    .rtable tbody tr{transition:background .14s}
    .rtable tbody tr:hover{background:rgba(255,215,0,.05)}
    .rtable tbody tr:nth-child(even){background:rgba(248,249,250,.5)}
    .rtable tbody tr:nth-child(even):hover{background:rgba(255,215,0,.06)}

    
    .pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:28px;padding:0 8px;border-radius:7px;border:1.5px solid var(--border);font-size:.75rem;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;background:#fff;color:var(--black);transition:var(--tr)}
    .pg-btn:hover{border-color:var(--gold);background:rgba(255,215,0,.1)}
    .pg-btn.active{background:var(--gold-grad);border-color:var(--gold-d);color:var(--black)}
    .pg-btn:disabled{opacity:.4;cursor:not-allowed}

    
    .placa-b{background:var(--gold-grad);color:var(--black);padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem;display:inline-block}
    .turno-a{background:linear-gradient(135deg,#28a745,#20c997);color:#fff;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem}
    .turno-b{background:linear-gradient(135deg,#ffc107,#ffb300);color:#1a1a1a;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem}
    .turno-c{background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem}
    .rec-si{background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem}
    .rec-no{background:linear-gradient(135deg,#28a745,#20c997);color:#fff;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem}
    .sb-act{background:#dcfce7;color:#16a34a;border:1px solid #86efac;padding:2px 9px;border-radius:20px;font-size:.71rem;font-weight:700}

    
    .modal-wrap{display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);overflow-y:auto;padding:.75rem}
    .modal-box{background:#fff;margin:auto;width:100%;max-width:980px;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-lg)}
    .modal-hdr{position:sticky;top:0;z-index:20;background:var(--black-grad);border-radius:var(--rad) var(--rad) 0 0;display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;box-shadow:0 4px 6px rgba(0,0,0,.2);border-bottom:2px solid var(--gold)}
    .modal-hdr h2{color:var(--gold);font-weight:700;font-size:clamp(.83rem,3vw,1.05rem);display:flex;align-items:center;gap:.5rem;margin:0}
    .close-btn{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:transparent;border:none;color:#fff;font-size:1.4rem;cursor:pointer;font-weight:700;line-height:1;transition:var(--tr)}
    .close-btn:hover{background:rgba(255,215,0,.2);color:var(--gold)}

    
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
    @media(max-width:480px){.h-xs{display:none!important}}
</style>

<div style="max-width:1400px;margin:0 auto;padding:1rem .75rem;">

    <div style="position:relative;background:var(--black-grad);border-radius:var(--rad);padding:clamp(1.2rem,4vw,2.5rem);margin-bottom:1.25rem;overflow:hidden;box-shadow:var(--sh-lg);">
        <div style="position:absolute;inset:0;background:radial-gradient(circle at 80% 50%,rgba(255,215,0,.08) 0%,transparent 60%);pointer-events:none;"></div>
        <div style="position:relative;z-index:2;display:flex;align-items:center;gap:clamp(.75rem,3vw,1.5rem);">
            <div class="gpulse" style="flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--gold-grad);color:var(--black);border-radius:var(--rad);width:clamp(44px,8vw,64px);height:clamp(44px,8vw,64px);font-size:clamp(1.2rem,3.5vw,1.8rem);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h1 style="color:var(--gold);font-weight:800;margin:0;line-height:1.2;font-size:clamp(1.1rem,3.5vw,2rem);">Error de Verificación</h1>
                <p style="color:#cbd5e1;font-size:clamp(.72rem,2vw,.95rem);margin-top:4px;">Sistema de registro y seguimiento de errores en verificación</p>
            </div>
        </div>
    </div>

    <div style="background:#fff;border-radius:var(--rad);padding:.9rem 1.25rem;margin-bottom:1.25rem;box-shadow:var(--sh-md);border:1px solid var(--border);display:flex;align-items:flex-end;flex-wrap:wrap;gap:1rem;">
        <button onclick="openModal()"
            style="display:inline-flex;align-items:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.65rem 1.1rem;border-radius:12px;font-weight:700;cursor:pointer;font-size:.83rem;font-family:'Poppins',sans-serif;box-shadow:0 4px 6px rgba(0,0,0,.08);transition:var(--tr);white-space:nowrap;flex-shrink:0;"
            onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-md)'"
            onmouseout="this.style.transform='';this.style.boxShadow='0 4px 6px rgba(0,0,0,.08)'">
            <i class="fas fa-plus-circle"></i> Reportar Error
        </button>

        <div style="flex:1;min-width:200px;">
            <label class="lbl"><i class="fas fa-calendar-alt"></i> Rango de fechas</label>
            <div style="position:relative;">
                <input type="text" id="dateRange" placeholder="Seleccionar rango…" readonly
                    style="width:100%;padding:9px 12px 9px 34px;border:1.5px solid var(--border);border-radius:10px;font-size:.83rem;font-family:'Poppins',sans-serif;background:#fff;color:var(--black);cursor:pointer;transition:border-color .2s;">
                <i class="fas fa-calendar-alt" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gold-d);font-size:.78rem;pointer-events:none;"></i>
            </div>
        </div>

        <div style="min-width:190px;">
            <label class="lbl"><i class="fas fa-user-check"></i> Verificador</label>
            <select id="verificadorFiltro" class="fi" style="width:100%;" onchange="actualizarGrafica()">
                <option value="">Todos los verificadores</option>
            </select>
        </div>

        <button onclick="limpiarFiltros()"
            style="display:inline-flex;align-items:center;gap:.5rem;background:#6c757d;color:#fff;border:none;padding:.65rem 1rem;border-radius:10px;font-weight:600;cursor:pointer;font-size:.8rem;font-family:'Poppins',sans-serif;transition:var(--tr);white-space:nowrap;flex-shrink:0;"
            onmouseover="this.style.background='#495057'" onmouseout="this.style.background='#6c757d'">
            <i class="fas fa-eraser"></i> Limpiar
        </button>

        <span style="display:inline-flex;align-items:center;gap:.5rem;font-size:.78rem;font-weight:700;color:var(--black);background:var(--bg);border:2px solid var(--gold);padding:.4rem .9rem;border-radius:20px;white-space:nowrap;flex-shrink:0;">
            <i class="fas fa-database" style="color:var(--gold);"></i>
            <span id="totalCount">0 registros</span>
        </span>
    </div>

    <div class="card" style="margin-bottom:1.25rem;padding:1.25rem;">
        <div class="sec-title"><i class="fas fa-chart-bar"></i> Errores por Verificador Responsable</div>
        <div id="chartContainer" style="min-height:340px;"></div>
    </div>

    <div class="card" style="margin-bottom:1.25rem;">
        <div style="background:var(--black-grad);padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;border-bottom:2px solid var(--gold);">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.88rem,3vw,1.1rem);margin:0;display:flex;align-items:center;gap:.5rem;">
                <i class="fas fa-table"></i> Todos los Registros
            </h2>
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                <div style="position:relative;">
                    <input type="text" id="tableSearch" placeholder="Buscar en tabla…"
                        style="padding:6px 10px 6px 30px;border:1.5px solid rgba(255,215,0,.3);border-radius:8px;font-size:.75rem;font-family:'Poppins',sans-serif;background:rgba(255,255,255,.08);color:#fff;outline:none;width:160px;"
                        oninput="filterTable(this.value)"
                        onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='rgba(255,215,0,.3)'">
                    <i class="fas fa-search" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:rgba(255,215,0,.6);font-size:.7rem;pointer-events:none;"></i>
                </div>
                <span class="sb-act" id="totalCount2">0 registros</span>
            </div>
        </div>
        <div class="cscroll" style="overflow-x:auto;max-height:65vh;">
            <table class="rtable" id="recordsTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar" style="color:var(--gold);margin-right:4px;"></i>Fecha</th>
                        <th class="h-sm"><i class="fas fa-user" style="color:var(--gold);margin-right:4px;"></i>Reportado Por</th>
                        <th class="h-sm">Novedad</th>
                        <th><i class="fas fa-truck" style="color:var(--gold);margin-right:4px;"></i>Placa</th>
                        <th class="h-sm"><i class="fas fa-barcode" style="color:var(--gold);margin-right:4px;"></i>SKU</th>
                        <th class="h-xs">Descripción</th>
                        <th class="h-sm">Cant. UP</th>
                        <th><i class="fas fa-user-check" style="color:var(--gold);margin-right:4px;"></i>Verificador</th>
                        <th class="h-sm">Turno</th>
                        <th class="h-sm">Rechazo</th>
                        <th class="h-xs">Auxiliar</th>
                        <th class="h-xs">Obs.</th>
                        <th style="text-align:center;"><i class="fas fa-eye" style="color:var(--gold);margin-right:4px;"></i>Ver</th>
                    </tr>
                </thead>
                <tbody id="recordsTbody"></tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;border-top:1px solid var(--border);flex-wrap:wrap;gap:.5rem;">
            <span style="font-size:.75rem;color:var(--text-g);" id="pgInfo"></span>
            <div id="pgControls" style="display:flex;gap:4px;flex-wrap:wrap;"></div>
        </div>
    </div>
</div>

<div id="errorModal" class="modal-wrap m-bg">
    <div class="modal-box m-box">
        <div class="modal-hdr">
            <h2><i class="fas fa-exclamation-triangle"></i> Reportar Nuevo Error de Verificación</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div style="padding:1.1rem;">
        <form id="errorForm">

            <div class="sec">
                <div class="sec-title"><i class="fas fa-info-circle"></i> Información General</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:.85rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-calendar-day"></i> Marca Temporal</label>
                        <input type="date" name="marca_temporal" class="fi" value="<?php echo $fecha_hoy; ?>" required>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-user"></i> Reportado Por</label>
                        <div class="sd" id="dd_reportado">
                            <input type="text" id="search_reportado" placeholder="Buscar persona…" class="fi" autocomplete="off"
                                oninput="filterDD('reportado')" onfocus="openDD('reportado')" onblur="blurDD('reportado')">
                            <input type="hidden" name="reportado_por" id="val_reportado">
                            <ul id="list_reportado" class="sdl cscroll"></ul>
                        </div>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-exclamation"></i> Tipo de Novedad</label>
                        <select name="tipo_novedad" class="fi" required>
                            <option value="">Seleccionar tipo…</option>
                            <option value="ERROR DE COBRO DE CHECKIN">ERROR DE COBRO DE CHECKIN</option>
                            <option value="ERROR EN RECEPCION DE ENVASE">ERROR EN RECEPCION DE ENVASE</option>
                            <option value="ERROR DESPACHO DE T1">ERROR DESPACHO DE T1</option>
                            <option value="ERROR RECEPCION DE T1">ERROR RECEPCION DE T1</option>
                            <option value="PRODUCTO TROCADO">PRODUCTO TROCADO</option>
                            <option value="PRODUCTO FALTANTE">PRODUCTO FALTANTE</option>
                            <option value="PRODUCTO SOBRANTE">PRODUCTO SOBRANTE</option>
                            <option value="IF NO ADECUADO">IF NO ADECUADO</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-clock"></i> Turno</label>
                        <select name="turno" class="fi" required>
                            <option value="">Seleccionar turno…</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-times-circle"></i> ¿Generó rechazo?</label>
                        <select name="novedad_genero_rechazo" class="fi" required>
                            <option value="">Seleccionar…</option>
                            <option value="N">No</option>
                            <option value="Y">Sí</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="sec">
                <div class="sec-title"><i class="fas fa-truck"></i> Vehículo y Producto</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.85rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-truck"></i> Placa</label>
                        <div class="sd" id="dd_placa">
                            <input type="text" id="search_placa" placeholder="Buscar placa…" class="fi" autocomplete="off"
                                oninput="filterDD('placa')" onfocus="openDD('placa')" onblur="blurDD('placa')">
                            <input type="hidden" name="placa_completa" id="val_placa">
                            <ul id="list_placa" class="sdl cscroll"></ul>
                        </div>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-barcode"></i> SKU / Producto</label>
                        <div class="sd" id="dd_sku">
                            <input type="text" id="search_sku" placeholder="Buscar SKU o producto…" class="fi" autocomplete="off"
                                oninput="filterDD('sku')" onfocus="openDD('sku')" onblur="blurDD('sku')">
                            <input type="hidden" name="sku" id="val_sku">
                            <ul id="list_sku" class="sdl cscroll"></ul>
                        </div>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label class="lbl"><i class="fas fa-align-left"></i> Descripción <span style="font-weight:400;color:var(--text-g);">(auto)</span></label>
                        <textarea name="descripcion" id="descripcion" class="fi" readonly style="background:var(--bg);color:var(--text-g);"></textarea>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-boxes"></i> Cantidad Unidad Presentación <span style="font-weight:400;color:var(--text-g);">(SXP, DCP, CA, UN)</span></label>
                        <input type="number" name="cantidad_unidad_presentacion" class="fi" min="0" placeholder="0" required>
                        <p style="font-size:.69rem;color:var(--text-g);margin-top:4px;"><i class="fas fa-info-circle" style="color:var(--gold-d);margin-right:3px;"></i>Debe estar en unidades</p>
                    </div>
                </div>
            </div>

            <div class="sec">
                <div class="sec-title"><i class="fas fa-users"></i> Personal Responsable</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.85rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-user-check"></i> Verificador Responsable</label>
                        <div class="sd" id="dd_verificador">
                            <input type="text" id="search_verificador" placeholder="Buscar verificador…" class="fi" autocomplete="off"
                                oninput="filterDD('verificador')" onfocus="openDD('verificador')" onblur="blurDD('verificador')">
                            <input type="hidden" name="verificador_responsable" id="val_verificador">
                            <ul id="list_verificador" class="sdl cscroll"></ul>
                        </div>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-user-cog"></i> Auxiliar Responsable</label>
                        <div class="sd" id="dd_auxiliar">
                            <input type="text" id="search_auxiliar" placeholder="Buscar auxiliar…" class="fi" autocomplete="off"
                                oninput="filterDD('auxiliar')" onfocus="openDD('auxiliar')" onblur="blurDD('auxiliar')">
                            <input type="hidden" name="auxiliar_responsable" id="val_auxiliar">
                            <ul id="list_auxiliar" class="sdl cscroll"></ul>
                        </div>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label class="lbl"><i class="fas fa-comment-alt"></i> Observaciones <span style="font-weight:400;color:var(--text-g);">(Opcional)</span></label>
                        <textarea name="observaciones" class="fi" placeholder="Observaciones adicionales…"></textarea>
                    </div>
                </div>
            </div>

            <button type="submit"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.95rem 2rem;border-radius:var(--rad);font-weight:800;cursor:pointer;font-size:.9rem;font-family:'Poppins',sans-serif;box-shadow:var(--sh-md);transition:var(--tr);"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-lg)'"
                onmouseout="this.style.transform='';this.style.boxShadow='var(--sh-md)'">
                <i class="fas fa-save"></i> Guardar Error de Verificación
            </button>
        </form>
        </div>
    </div>
</div>

<script>
let chartInstance = null;
let fechaInicio = '<?php echo date('Y-01-01'); ?>';
let fechaFin    = '<?php echo date('Y-12-31'); ?>';

let PERSONAL = [];
let PLACAS   = [];
let PRODUCTOS = [];

let allRows = [];
let filteredRows = [];
let currentPage = 1;
const PAGE_SIZE = 25;


function formatoFechaValida(fechaStr) {
    if (!fechaStr) return '—';
    const soloFecha = fechaStr.split(' ')[0];
    const partes = soloFecha.split('-');
    
    if (partes.length !== 3) return fechaStr;
    
    const fecha = new Date(partes[0], partes[1] - 1, partes[2]);
    return fecha.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}


flatpickr('#dateRange', {
    mode: 'range', locale: 'es', dateFormat: 'd/m/Y',
    defaultDate: [fechaInicio, fechaFin],
    showMonths: window.innerWidth > 640 ? 2 : 1,
    disableMobile: true,
    onChange(selectedDates) {
        if (selectedDates.length === 2) {
            fechaInicio = fmt(selectedDates[0]);
            fechaFin    = fmt(selectedDates[1]);
            actualizarGrafica();
        }
    }
});
function fmt(d){ return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }


function openModal() {
    document.getElementById('errorModal').style.display = 'block';
    if (!PERSONAL.length) cargarPersonal();
    if (!PLACAS.length)   cargarPlacas();
    if (!PRODUCTOS.length) cargarProductos();
}
function closeModal() {
    document.getElementById('errorModal').style.display = 'none';
    document.getElementById('errorForm').reset();
    ['reportado','placa','sku','verificador','auxiliar'].forEach(k => {
        document.getElementById('search_'+k).value = '';
        document.getElementById('val_'+k).value = '';
    });
    document.getElementById('descripcion').value = '';
}
window.onclick = e => { if (e.target === document.getElementById('errorModal')) closeModal(); };


const DD_DATA = {
    reportado:   () => PERSONAL.map(p => ({ label: p.nombre, val: p.nombre })),
    placa:       () => PLACAS.map(p   => ({ label: p.placa,  val: p.placa })),
    sku:         () => PRODUCTOS.map(p => ({ label: `${p.id_material} — ${p.material}`, val: p.id_material, desc: p.material })),
    verificador: () => PERSONAL.map(p => ({ label: p.nombre, val: p.nombre })),
    auxiliar:    () => PERSONAL.map(p => ({ label: p.nombre, val: p.nombre })),
};

function initDD(key) {
    const ul = document.getElementById('list_'+key);
    ul.innerHTML = '';
    DD_DATA[key]().forEach(item => {
        const li = document.createElement('li');
        li.textContent = item.label;
        li.onmousedown = e => { e.preventDefault(); selectDD(key, item); };
        ul.appendChild(li);
    });
}
function filterDD(key) {
    const q  = document.getElementById('search_'+key).value.toLowerCase();
    const ul = document.getElementById('list_'+key);
    let vis = 0;
    ul.querySelectorAll('li:not(.nores)').forEach(li => {
        const show = li.textContent.toLowerCase().includes(q);
        li.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    const nr = ul.querySelector('.nores');
    if (vis===0 && !nr) { const li=document.createElement('li'); li.className='nores'; li.textContent='Sin resultados'; ul.appendChild(li); }
    else if (vis>0 && nr) nr.remove();
    ul.classList.add('open');
}
function openDD(key) { initDD(key); filterDD(key); }
function blurDD(key) { setTimeout(()=>document.getElementById('list_'+key).classList.remove('open'), 160); }
function selectDD(key, item) {
    document.getElementById('search_'+key).value = item.label;
    document.getElementById('val_'+key).value     = item.val;
    document.getElementById('list_'+key).classList.remove('open');
    if (key === 'sku' && item.desc) document.getElementById('descripcion').value = item.desc;
}


function cargarPersonal() {
    fetch('error_verificacion.php?action=obtener_personal')
        .then(r=>r.json()).then(data=>{ PERSONAL=data; }).catch(console.error);
}
function cargarPlacas() {
    fetch('error_verificacion.php?action=obtener_placas')
        .then(r=>r.json()).then(data=>{ PLACAS=data; }).catch(console.error);
}
function cargarProductos() {
    fetch('error_verificacion.php?action=obtener_productos')
        .then(r=>r.json()).then(data=>{ PRODUCTOS=data; }).catch(console.error);
}
function cargarVerificadores() {
    fetch('error_verificacion.php?action=obtener_verificadores')
        .then(r=>r.json()).then(data=>{
            const sel = document.getElementById('verificadorFiltro');
            while (sel.children.length>1) sel.removeChild(sel.lastChild);
            data.forEach(v=>{ const o=document.createElement('option'); o.value=v.verificador_responsable; o.textContent=v.verificador_responsable; sel.appendChild(o); });
        }).catch(console.error);
}


function actualizarGrafica() {
    const ver = document.getElementById('verificadorFiltro').value;
    fetch(`error_verificacion.php?action=grafica_datos&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&verificador=${encodeURIComponent(ver)}`)
        .then(r=>r.json()).then(data=>{
            const verificadores = data.map(d=>d.verificador_responsable);
            const errores = data.map(d=>parseInt(d.total_errores));
            if (chartInstance) chartInstance.destroy();
            chartInstance = new ApexCharts(document.getElementById('chartContainer'), {
                series:[{ name:'Total Errores', data:errores }],
                chart:{ type:'bar', height:340, background:'transparent', toolbar:{ show:true }, fontFamily:'Poppins,sans-serif' },
                plotOptions:{ bar:{ horizontal:false, columnWidth:'55%', borderRadius:8, dataLabels:{ position:'top' } } },
                colors:['#FFD700'],
                dataLabels:{ enabled:true, formatter:v=>v, offsetY:-22, style:{ fontSize:'12px', fontWeight:'bold', colors:['#1a1a1a'] } },
                stroke:{ show:true, width:2, colors:['transparent'] },
                xaxis:{ categories:verificadores, labels:{ style:{ colors:'#666', fontSize:'11px', fontWeight:600 }, rotate:-35, rotateAlways:verificadores.length>7 } },
                yaxis:{ title:{ text:'Cantidad de Errores', style:{ color:'#1a1a1a', fontSize:'12px', fontWeight:600 } } },
                grid:{ borderColor:'#e9ecef', strokeDashArray:4 },
                fill:{ type:'gradient', gradient:{ shade:'light', type:'vertical', shadeIntensity:.4, gradientToColors:['#FFA500'], opacityFrom:1, opacityTo:.85 } },
                tooltip:{ y:{ formatter:v=>v+' errores' } }
            });
            chartInstance.render();
            cargarTodosRegistros();
        }).catch(console.error);
}


function cargarTodosRegistros() {
    const ver = document.getElementById('verificadorFiltro').value;
    fetch(`error_verificacion.php?action=todos_registros&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&verificador=${encodeURIComponent(ver)}`)
        .then(r=>r.json()).then(data=>{
            allRows = data;
            filteredRows = [...data];
            const label = data.length+' registros';
            document.getElementById('totalCount').textContent  = label;
            document.getElementById('totalCount2').textContent = label;
            currentPage = 1;
            renderPage();
        }).catch(console.error);
}

function filterTable(q) {
    const lq = q.toLowerCase();
    filteredRows = lq ? allRows.filter(r =>
        Object.values(r).some(v => String(v||'').toLowerCase().includes(lq))
    ) : [...allRows];
    currentPage = 1;
    renderPage();
}

function renderPage() {
    const tbody = document.getElementById('recordsTbody');
    tbody.innerHTML = '';
    const start = (currentPage-1)*PAGE_SIZE;
    const slice = filteredRows.slice(start, start+PAGE_SIZE);
    if (!slice.length) {
        tbody.innerHTML = `<tr><td colspan="13" style="text-align:center;padding:3rem;color:var(--text-g);"><i class="fas fa-inbox" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:var(--border);"></i><strong>No hay registros</strong></td></tr>`;
    } else {
        slice.forEach(r => {
            const tr = document.createElement('tr');
            
            const dataString = JSON.stringify(r).replace(/'/g, "&apos;");
            
            tr.innerHTML = `
                <td style="white-space:nowrap;font-weight:600;">${formatoFechaValida(r.marca_temporal)}</td>
                <td class="h-sm" style="white-space:nowrap;"><i class="fas fa-user" style="color:var(--gold-d);margin-right:4px;"></i>${r.reportado_por}</td>
                <td class="h-sm"><span style="background:rgba(255,215,0,.12);color:var(--gold-d);padding:2px 7px;border-radius:6px;font-size:.71rem;font-weight:600;">${r.tipo_novedad}</span></td>
                <td><span class="placa-b">${r.placa_completa}</span></td>
                <td class="h-sm" style="white-space:nowrap;"><i class="fas fa-barcode" style="color:#17a2b8;margin-right:4px;"></i><strong>${r.sku}</strong></td>
                <td class="h-xs" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${r.descripcion}">${r.descripcion}</td>
                <td class="h-sm"><strong style="color:#16a34a;">${r.cantidad_unidad_presentacion}</strong> <small style="color:var(--text-g);">uds</small></td>
                <td style="white-space:nowrap;"><i class="fas fa-user-check" style="color:var(--gold-d);margin-right:4px;"></i><strong>${r.verificador_responsable}</strong></td>
                <td class="h-sm"><span class="turno-${r.turno.toLowerCase()}">${r.turno}</span></td>
                <td class="h-sm"><span class="${r.novedad_genero_rechazo==='Y'?'rec-si':'rec-no'}">${r.novedad_genero_rechazo==='Y'?'SÍ':'NO'}</span></td>
                <td class="h-xs" style="white-space:nowrap;"><i class="fas fa-user-cog" style="color:#7c3aed;margin-right:4px;"></i>${r.auxiliar_responsable}</td>
                <td class="h-xs" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${r.observaciones||''}">${r.observaciones||'—'}</td>
                <td style="text-align:center;">
                    <button onclick='verDetalleRegistro(${dataString})' class="pg-btn" style="color:var(--gold-d); border-color:var(--gold);">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>`;
            tbody.appendChild(tr);
        });
    }
    renderPagination();
}

function renderPagination() {
    const total = filteredRows.length;
    const pages = Math.ceil(total/PAGE_SIZE);
    const start = (currentPage-1)*PAGE_SIZE+1;
    const end   = Math.min(currentPage*PAGE_SIZE, total);
    document.getElementById('pgInfo').textContent = total ? `Mostrando ${start}–${end} de ${total}` : '';
    const ctrl = document.getElementById('pgControls');
    ctrl.innerHTML = '';
    if (pages <= 1) return;
    const addBtn = (label, page, disabled, active) => {
        const b = document.createElement('button');
        b.className = 'pg-btn' + (active?' active':'');
        b.textContent = label;
        b.disabled = disabled;
        if (!disabled) b.onclick = () => { currentPage=page; renderPage(); };
        ctrl.appendChild(b);
    };
    addBtn('«', 1, currentPage===1, false);
    addBtn('‹', currentPage-1, currentPage===1, false);
    let s = Math.max(1, currentPage-2), e = Math.min(pages, s+4);
    if (e-s<4) s = Math.max(1, e-4);
    for (let p=s; p<=e; p++) addBtn(p, p, false, p===currentPage);
    addBtn('›', currentPage+1, currentPage===pages, false);
    addBtn('»', pages, currentPage===pages, false);
}


document.getElementById('errorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const required = [
        { id:'val_reportado',   label:'Reportado Por' },
        { id:'val_placa',       label:'Placa' },
        { id:'val_sku',         label:'SKU / Producto' },
        { id:'val_verificador', label:'Verificador Responsable' },
        { id:'val_auxiliar',    label:'Auxiliar Responsable' },
    ];
    for (const f of required) {
        if (!document.getElementById(f.id).value.trim()) {
            Swal.fire({ title:'Campo requerido', text:`Por favor selecciona: ${f.label}`, icon:'warning', confirmButtonColor:'#FFD700' });
            return;
        }
    }
    Swal.fire({ title:'Guardando…', allowOutsideClick:false, showConfirmButton:false, didOpen:()=>Swal.showLoading() });
    const fd = new FormData(this);
    fd.append('action','agregar');
    fetch('error_verificacion.php',{ method:'POST', body:fd })
        .then(r=>r.json()).then(data=>{
            if (data.success) {
                Swal.fire({ title:'¡Éxito!', text:data.message, icon:'success', confirmButtonColor:'#FFD700' })
                .then(()=>{ closeModal(); actualizarGrafica(); cargarVerificadores(); });
            } else {
                Swal.fire({ title:'Error', text:data.message, icon:'error', confirmButtonColor:'#FFD700' });
            }
        }).catch(()=>Swal.fire({ title:'Error inesperado', icon:'error', confirmButtonColor:'#FFD700' }));
});


function limpiarFiltros() {
    fechaInicio = '<?php echo date('Y-01-01'); ?>';
    fechaFin    = '<?php echo date('Y-12-31'); ?>';
    document.getElementById('verificadorFiltro').value = '';
    document.getElementById('tableSearch').value = '';
    const fp = document.getElementById('dateRange')._flatpickr;
    if (fp) fp.setDate([fechaInicio, fechaFin]);
    actualizarGrafica();
}


document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key==='n') { e.preventDefault(); openModal(); }
    if (e.key==='Escape') closeModal();
});


function verDetalleRegistro(r) {
    const contenido = `
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; text-align: left; font-size: 0.9rem;">
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>📅 Fecha:</strong><br> ${formatoFechaValida(r.marca_temporal)}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>👤 Reportado por:</strong><br> ${r.reportado_por}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>⚠️ Novedad:</strong><br> ${r.tipo_novedad}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>🚚 Placa:</strong><br> ${r.placa_completa}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>📦 SKU:</strong><br> ${r.sku}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>📝 Producto:</strong><br> ${r.descripcion}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>🔢 Cantidad:</strong><br> ${r.cantidad_unidad_presentacion} unidades</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>✅ Verificador:</strong><br> ${r.verificador_responsable}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>⚙️ Auxiliar:</strong><br> ${r.auxiliar_responsable}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>🕒 Turno:</strong><br> ${r.turno}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px;"><strong>🚫 ¿Generó Rechazo?:</strong><br> ${r.novedad_genero_rechazo === 'Y' ? 'SÍ' : 'NO'}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px; grid-column: 1 / -1;"><strong>💬 Observaciones:</strong><br> ${r.observaciones || 'Sin observaciones'}</div>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 5px; grid-column: 1 / -1; font-size: 0.7rem; color: gray;"><strong>🕒 Registrado el:</strong> ${r.created_at || 'N/A'}</div>
        </div>
    `;

    Swal.fire({
        title: '<i class="fas fa-info-circle" style="color:var(--gold)"></i> Detalle del Registro',
        html: contenido,
        width: '600px',
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#1a1a1a'
    });
}


document.addEventListener('DOMContentLoaded', () => {
    actualizarGrafica();
    cargarVerificadores();
    cargarPersonal();
    cargarPlacas();
    cargarProductos();
});
</script>