<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';

if ($_POST && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'create') {
            $sql = "INSERT INTO devoluciones (
                fecha, canal, operador, sku, dt, unidades, casual,
                verificador, facturador, status, placa, operacion_id, fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['fecha']       ?? null,
                $_POST['canal']       ?? null,
                $_POST['operador']    ?? null,
                $_POST['sku']         ?? null,
                $_POST['dt']          ?? null,
                $_POST['unidades']    ?? null,
                $_POST['casual']      ?? null,
                $_POST['verificador'] ?? null,
                $_POST['facturador']  ?? null,
                $_POST['status']      ?? null,
                $_POST['placa']       ?? null,
                getOperacionActiva(),
            ]);
            $success_message = "Devolución creada exitosamente";
        }

        if ($_POST['action'] === 'update') {
            $sql = "UPDATE devoluciones SET
                fecha = ?, canal = ?, operador = ?, sku = ?, dt = ?,
                unidades = ?, casual = ?, verificador = ?, facturador = ?,
                status = ?, placa = ?
                WHERE id = ? AND operacion_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['fecha']       ?? null,
                $_POST['canal']       ?? null,
                $_POST['operador']    ?? null,
                $_POST['sku']         ?? null,
                $_POST['dt']          ?? null,
                $_POST['unidades']    ?? null,
                $_POST['casual']      ?? null,
                $_POST['verificador'] ?? null,
                $_POST['facturador']  ?? null,
                $_POST['status']      ?? null,
                $_POST['placa']       ?? null,
                $_POST['id'],
                getOperacionActiva(),
            ]);
            $success_message = "Devolución actualizada exitosamente";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

try {
    $stmt = $pdo->prepare("SELECT * FROM devoluciones WHERE DATE_FORMAT(fecha,'%Y-%m') = ? AND operacion_id = ? ORDER BY fecha_creacion DESC");
    $stmt->execute([$mes_seleccionado, getOperacionActiva()]);
    $devoluciones = $stmt->fetchAll();

    $stmt_contadores = $pdo->prepare("
        SELECT canal, SUM(unidades) as total_unidades
        FROM devoluciones
        WHERE DATE_FORMAT(fecha,'%Y-%m') = ? AND canal IS NOT NULL AND canal != '' AND operacion_id = ?
        GROUP BY canal
    ");
    $stmt_contadores->execute([$mes_seleccionado, getOperacionActiva()]);
    $contadores = $stmt_contadores->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $devoluciones = [];
    $contadores    = [];
    $error_message = "Error al cargar datos: " . $e->getMessage();
}

try {
    $stmt_productos = $pdo->prepare("SELECT id_material, material FROM productos ORDER BY id_material ASC");
    $stmt_productos->execute();
    $productos = $stmt_productos->fetchAll();
} catch (Exception $e) {
    $productos = [];
}

try {
    $stmt_usuarios = $pdo->prepare("SELECT nombre FROM usuarios WHERE activo = 1 AND operacion_id = ? ORDER BY nombre ASC");
    $stmt_usuarios->execute([getOperacionActiva()]);
    $usuarios_lista = $stmt_usuarios->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $usuarios_lista = [];
}

$canales = ['KA', 'MM', 'T4', 'TAT'];

$canal_colors = [
    'KA'  => ['border' => '#1565c0', 'bg' => '#e3f2fd', 'text' => '#1565c0', 'icon_bg' => '#1565c0'],
    'MM'  => ['border' => '#7b1fa2', 'bg' => '#f3e5f5', 'text' => '#7b1fa2', 'icon_bg' => '#7b1fa2'],
    'T4'  => ['border' => '#2e7d32', 'bg' => '#e8f5e9', 'text' => '#2e7d32', 'icon_bg' => '#2e7d32'],
    'TAT' => ['border' => '#e65100', 'bg' => '#fff3e0', 'text' => '#e65100', 'icon_bg' => '#e65100'],
];
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    select.fi{cursor:pointer;background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");background-position:right .6rem center;background-repeat:no-repeat;background-size:1.2em;padding-right:2.2rem}
    .lbl{display:block;font-size:.72rem;font-weight:600;color:var(--black-m);margin-bottom:5px}
    .lbl i{color:var(--gold-d);margin-right:3px}

    
    .sec{position:relative;background:var(--bg);border:1px solid var(--border);border-radius:var(--rad);padding:1.1rem;margin-bottom:1rem}
    .sec::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--gold-grad);border-radius:var(--rad) var(--rad) 0 0;pointer-events:none}
    .sec-title{font-size:.85rem;font-weight:700;color:var(--black);display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.55rem;border-bottom:1.5px solid var(--border)}
    .sec-title i{color:var(--gold)}

    
    .sd{position:relative;z-index:100}
    .sdl{position:absolute;top:calc(100% + 3px);left:0;right:0;background:#fff;border:1.5px solid var(--gold);border-radius:10px;max-height:195px;overflow-y:auto;z-index:9999;box-shadow:0 8px 24px rgba(255,215,0,.18);display:none}
    .sdl.open{display:block}
    .sdl li{padding:8px 12px;cursor:pointer;font-size:.8rem;color:var(--black);list-style:none;transition:background .12s}
    .sdl li:hover{background:#fffbeb}
    .sdl li.nores{color:#94a3b8;cursor:default}
    .sdl li.nores:hover{background:transparent}

    
    .sku-wrap{position:relative;z-index:100}
    .sku-results{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid var(--gold);border-radius:0 0 10px 10px;max-height:195px;overflow-y:auto;z-index:9999;box-shadow:0 8px 24px rgba(255,215,0,.18);display:none}
    .sku-results.open{display:block}
    .sku-results li{padding:8px 12px;cursor:pointer;font-size:.79rem;list-style:none;border-bottom:1px solid #f1f5f9;transition:background .12s}
    .sku-results li:hover{background:#fffbeb}
    .sku-results li:last-child{border-bottom:none}
    .sku-input{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:10px 10px 0 0;font-size:.83rem;font-family:'Poppins',sans-serif;background:#fff;color:var(--black);transition:border-color .2s}
    .sku-input:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(255,215,0,.18)}
    .sku-input.has-val{border-radius:10px}

    
    .rtable{width:100%;border-collapse:collapse;font-size:.77rem}
    .rtable thead tr{background:var(--black-grad);border-bottom:2px solid var(--gold)}
    .rtable th{padding:.7rem .85rem;text-align:left;font-weight:700;color:#fff;white-space:nowrap;font-size:.71rem;text-transform:uppercase;letter-spacing:.4px}
    .rtable td{padding:.65rem .85rem;border-bottom:1px solid #f1f5f9;color:#444;vertical-align:middle}
    .rtable tbody tr{transition:background .14s}
    .rtable tbody tr:hover{background:rgba(255,215,0,.05)}
    .rtable tbody tr:nth-child(even){background:rgba(248,249,250,.5)}
    .rtable tbody tr:nth-child(even):hover{background:rgba(255,215,0,.06)}

    
    .c-ka  {background:#e3f2fd;color:#1565c0;border:1px solid #90caf9;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem;display:inline-block}
    .c-mm  {background:#f3e5f5;color:#7b1fa2;border:1px solid #ce93d8;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem;display:inline-block}
    .c-t4  {background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem;display:inline-block}
    .c-tat {background:#fff3e0;color:#e65100;border:1px solid #ffcc80;padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem;display:inline-block}

    
    .modal-wrap{display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);overflow-y:auto;padding:.75rem}
    .modal-box{background:#fff;margin:auto;width:100%;max-width:860px;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-lg)}
    .modal-hdr{position:sticky;top:0;z-index:20;background:var(--black-grad);border-radius:var(--rad) var(--rad) 0 0;display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;box-shadow:0 4px 6px rgba(0,0,0,.2);border-bottom:2px solid var(--gold)}
    .modal-hdr h2{color:var(--gold);font-weight:700;font-size:clamp(.83rem,3vw,1.05rem);display:flex;align-items:center;gap:.5rem;margin:0}
    .close-btn{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:transparent;border:none;color:#fff;font-size:1.4rem;cursor:pointer;font-weight:700;line-height:1;transition:var(--tr)}
    .close-btn:hover{background:rgba(255,215,0,.2);color:var(--gold)}

    
    .swal2-popup{border-radius:var(--rad)!important;font-family:'Poppins',sans-serif!important;border:2px solid var(--gold)!important}
    .swal2-title{color:var(--black)!important;font-weight:700!important}
    .swal2-confirm{background:var(--gold-grad)!important;color:var(--black)!important;border-radius:8px!important;font-weight:700!important;border:none!important}
    .swal2-cancel{background:var(--text-g)!important;border-radius:8px!important;font-weight:600!important;border:none!important}

    @media(max-width:640px){.h-sm{display:none!important}}
    @media(max-width:420px){.h-xs{display:none!important}}
</style>

<?php
$usuarios_json  = json_encode($usuarios_lista);
$productos_json = json_encode($productos);
?>

<div style="max-width:1400px;margin:0 auto;padding:1rem .75rem;">

    
    <div style="position:relative;background:var(--black-grad);border-radius:var(--rad);padding:clamp(1.2rem,4vw,2.5rem);margin-bottom:1.25rem;overflow:hidden;box-shadow:var(--sh-lg);">
        <div style="position:absolute;inset:0;background:radial-gradient(circle at 80% 50%,rgba(255,215,0,.08) 0%,transparent 60%);pointer-events:none;"></div>
        <div style="position:relative;z-index:2;display:flex;align-items:center;gap:clamp(.75rem,3vw,1.5rem);">
            <div class="gpulse" style="flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--gold-grad);color:var(--black);border-radius:var(--rad);width:clamp(44px,8vw,64px);height:clamp(44px,8vw,64px);font-size:clamp(1.2rem,3.5vw,1.8rem);">
                <i class="fas fa-undo-alt"></i>
            </div>
            <div>
                <h1 style="color:var(--gold);font-weight:800;margin:0;line-height:1.2;font-size:clamp(1.1rem,3.5vw,2rem);">Control de Devoluciones</h1>
                <p style="color:#cbd5e1;font-size:clamp(.72rem,2vw,.95rem);margin-top:4px;">Gestión y seguimiento de devoluciones de productos</p>
            </div>
        </div>
    </div>

    
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.25rem;">
        <?php foreach ($canales as $canal):
            $c   = $canal_colors[$canal];
            $val = number_format(isset($contadores[$canal]) ? $contadores[$canal] : 0);
        ?>
        <div style="background:#fff;border-radius:var(--rad);border-left:4px solid <?php echo $c['border']; ?>;padding:1.1rem 1.25rem;box-shadow:var(--sh-md);transition:var(--tr);"
            onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.6rem;">
                <span style="font-size:.72rem;font-weight:700;color:var(--text-g);text-transform:uppercase;letter-spacing:.5px;">Canal <?php echo $canal; ?></span>
                <div style="width:34px;height:34px;border-radius:50%;background:<?php echo $c['icon_bg']; ?>;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-boxes" style="color:#fff;font-size:.8rem;"></i>
                </div>
            </div>
            <div style="font-size:1.8rem;font-weight:800;color:var(--black);line-height:1;"><?php echo $val; ?></div>
            <div style="font-size:.68rem;color:var(--text-g);margin-top:3px;">unidades</div>
        </div>
        <?php endforeach; ?>
    </div>

    
    <div style="background:#fff;border-radius:var(--rad);padding:.9rem 1.25rem;margin-bottom:1.25rem;box-shadow:var(--sh-md);border:1px solid var(--border);display:flex;align-items:flex-end;flex-wrap:wrap;gap:1rem;">
        <button onclick="openModal('create')"
            style="display:inline-flex;align-items:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.65rem 1.1rem;border-radius:12px;font-weight:700;cursor:pointer;font-size:.83rem;font-family:'Poppins',sans-serif;box-shadow:0 4px 6px rgba(0,0,0,.08);transition:var(--tr);white-space:nowrap;flex-shrink:0;"
            onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-md)'"
            onmouseout="this.style.transform='';this.style.boxShadow='0 4px 6px rgba(0,0,0,.08)'">
            <i class="fas fa-plus"></i> Nueva Devolución
        </button>

        <div>
            <label class="lbl"><i class="fas fa-calendar-alt"></i> Filtrar por mes</label>
            <input type="month" id="mes_filtro" value="<?php echo $mes_seleccionado; ?>" onchange="filtrarPorMes()"
                style="padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:.83rem;font-family:'Poppins',sans-serif;background:#fff;color:var(--black);cursor:pointer;transition:border-color .2s;"
                onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--border)'">
        </div>

        <span style="margin-left:auto;display:inline-flex;align-items:center;gap:.5rem;font-size:.78rem;font-weight:700;color:var(--black);background:var(--bg);border:2px solid var(--gold);padding:.4rem .9rem;border-radius:20px;white-space:nowrap;flex-shrink:0;">
            <i class="fas fa-database" style="color:var(--gold);"></i>
            <?php echo count($devoluciones); ?> registros
        </span>
    </div>

    
    <div style="background:#fff;border-radius:var(--rad);box-shadow:var(--sh-md);border:1px solid var(--border);overflow:hidden;margin-bottom:1.25rem;">
        <div style="background:var(--black-grad);padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;border-bottom:2px solid var(--gold);">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.88rem,3vw,1.1rem);margin:0;display:flex;align-items:center;gap:.5rem;">
                <i class="fas fa-table"></i>
                Registro de Devoluciones — <?php echo date('F Y', strtotime($mes_seleccionado.'-01')); ?>
            </h2>
            <span style="background:var(--gold);color:var(--black);padding:3px 12px;border-radius:20px;font-size:.76rem;font-weight:700;">
                <?php echo count($devoluciones); ?> registros
            </span>
        </div>

        <?php if (empty($devoluciones)): ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4rem 1rem;color:var(--text-g);">
                <i class="fas fa-inbox" style="font-size:3.5rem;color:var(--border);margin-bottom:1rem;"></i>
                <h3 style="font-weight:700;color:var(--black);margin-bottom:.5rem;">No hay devoluciones registradas</h3>
                <p style="font-size:.9rem;">Comienza agregando una nueva devolución</p>
            </div>
        <?php else: ?>
        <div class="cscroll" style="overflow-x:auto;max-height:68vh;">
            <table class="rtable">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar" style="color:var(--gold);margin-right:4px;"></i>Fecha</th>
                        <th>Canal</th>
                        <th class="h-sm">Operador</th>
                        <th class="h-sm"><i class="fas fa-barcode" style="color:var(--gold);margin-right:4px;"></i>SKU</th>
                        <th class="h-xs">DT</th>
                        <th>Unidades</th>
                        <th class="h-sm">Causal</th>
                        <th class="h-sm"><i class="fas fa-user-check" style="color:var(--gold);margin-right:4px;"></i>Verificador</th>
                        <th class="h-xs">Facturador</th>
                        <th class="h-sm">Status</th>
                        <th class="h-xs">Placa</th>
                        <th style="text-align:center;"><i class="fas fa-cogs" style="color:var(--gold);"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devoluciones as $dev): ?>
                    <tr>
                        <td style="white-space:nowrap;font-weight:600;"><?php echo $dev['fecha'] ? date('d/m/Y', strtotime($dev['fecha'])) : '—'; ?></td>
                        <td>
                            <?php if ($dev['canal']): ?>
                                <span class="c-<?php echo strtolower($dev['canal']); ?>"><?php echo $dev['canal']; ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="h-sm"><?php echo $dev['operador'] ? ucfirst($dev['operador']) : '—'; ?></td>
                        <td class="h-sm" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($dev['sku']); ?>"><?php echo $dev['sku'] ?: '—'; ?></td>
                        <td class="h-xs"><?php echo $dev['dt'] ?: '—'; ?></td>
                        <td><strong style="color:#16a34a;"><?php echo $dev['unidades'] ? number_format($dev['unidades']) : '—'; ?></strong></td>
                        <td class="h-sm"><?php echo $dev['casual'] ?: '—'; ?></td>
                        <td class="h-sm" style="white-space:nowrap;"><i class="fas fa-user-check" style="color:var(--gold-d);margin-right:4px;"></i><?php echo $dev['verificador'] ?: '—'; ?></td>
                        <td class="h-xs" style="white-space:nowrap;"><?php echo $dev['facturador'] ?: '—'; ?></td>
                        <td class="h-sm">
                            <?php if ($dev['status']): ?>
                                <span style="background:rgba(22,163,74,.1);color:#16a34a;padding:2px 8px;border-radius:6px;font-size:.72rem;font-weight:600;"><?php echo ucfirst($dev['status']); ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="h-xs">
                            <?php if ($dev['placa']): ?>
                                <span style="background:var(--gold-grad);color:var(--black);padding:2px 9px;border-radius:7px;font-weight:700;font-size:.73rem;"><?php echo $dev['placa']; ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <div style="display:flex;align-items:center;justify-content:center;gap:5px;">
                                <button onclick="viewRecord(<?php echo $dev['id']; ?>)" title="Ver"
                                    style="width:29px;height:29px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:2px solid #17a2b8;color:#17a2b8;background:transparent;cursor:pointer;transition:var(--tr);"
                                    onmouseover="this.style.background='#17a2b8';this.style.color='#fff'"
                                    onmouseout="this.style.background='transparent';this.style.color='#17a2b8'">
                                    <i class="fas fa-eye" style="font-size:.65rem;"></i>
                                </button>
                                <button onclick="editRecord(<?php echo $dev['id']; ?>)" title="Editar"
                                    style="width:29px;height:29px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:2px solid #ffc107;color:#ffc107;background:transparent;cursor:pointer;transition:var(--tr);"
                                    onmouseover="this.style.background='#ffc107';this.style.color='#1a1a1a'"
                                    onmouseout="this.style.background='transparent';this.style.color='#ffc107'">
                                    <i class="fas fa-edit" style="font-size:.65rem;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>


<div id="devolucionModal" class="modal-wrap m-bg">
    <div class="modal-box m-box">
        <div class="modal-hdr">
            <h2><i class="fas fa-undo-alt"></i> <span id="modalTitle">Nueva Devolución</span></h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div style="padding:1.1rem;">
        <form id="devolucionForm" method="POST">
            <input type="hidden" id="recordId"   name="id">
            <input type="hidden" id="formAction" name="action" value="create">

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-info-circle"></i> Información General</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:.85rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-calendar-day"></i> Fecha</label>
                        <input type="date" id="fecha" name="fecha" class="fi" required>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-layer-group"></i> Canal</label>
                        <select id="canal" name="canal" class="fi" required>
                            <option value="">Seleccionar canal…</option>
                            <?php foreach ($canales as $c): ?>
                                <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-building"></i> Operador</label>
                        <select id="operador" name="operador" class="fi" required>
                            <option value="">Seleccionar operador…</option>
                            <option value="logisticos">Logísticos</option>
                            <option value="surtilicores">Surtilicores</option>
                            <option value="t4">T4</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-hashtag"></i> DT (Número)</label>
                        <input type="text" id="dt" name="dt" class="fi" placeholder="Ej: 12345">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-sort-numeric-up"></i> Unidades</label>
                        <input type="number" id="unidades" name="unidades" class="fi" min="0" placeholder="0" required>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-exclamation-circle"></i> Causal</label>
                        <select id="casual" name="casual" class="fi" required>
                            <option value="">Seleccionar causal…</option>
                            <option value="Rotas">Rotas</option>
                            <option value="faltantes">Faltantes</option>
                            <option value="bajo nivel">Bajo nivel</option>
                            <option value="humedo">Húmedo</option>
                            <option value="desfondada">Desfondada</option>
                            <option value="averiada">Averiada</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-tasks"></i> Status</label>
                        <select id="status" name="status" class="fi" required>
                            <option value="">Seleccionar status…</option>
                            <option value="check in">Check In</option>
                            <option value="cambio mano a mano">Cambio Mano a Mano</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-truck"></i> Placa</label>
                        <input type="text" id="placa" name="placa" class="fi" style="text-transform:uppercase;" placeholder="ABC123" oninput="this.value=this.value.toUpperCase()">
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-barcode"></i> Producto / SKU</div>
                <div>
                    <label class="lbl"><i class="fas fa-search"></i> Buscar producto</label>
                    <div class="sku-wrap">
                        <input type="text" id="skuSearch" class="sku-input" placeholder="Escribir código o nombre del producto…" oninput="searchProducts(this.value)" autocomplete="off">
                        <input type="hidden" id="sku" name="sku" required>
                        <ul id="skuResults" class="sku-results cscroll"></ul>
                    </div>
                    <p id="skuSelected" style="font-size:.72rem;color:#16a34a;margin-top:5px;display:none;"><i class="fas fa-check-circle" style="margin-right:3px;"></i><span></span></p>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-users"></i> Personal</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:.85rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-user-check"></i> Verificador</label>
                        <div class="sd" id="dd_verificador">
                            <input type="text" id="search_verificador" placeholder="Buscar verificador…" class="fi" autocomplete="off"
                                oninput="filterDD('verificador')" onfocus="openDD('verificador')" onblur="blurDD('verificador')">
                            <input type="hidden" name="verificador" id="val_verificador">
                            <ul id="list_verificador" class="sdl cscroll"></ul>
                        </div>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-file-invoice"></i> Facturador</label>
                        <div class="sd" id="dd_facturador">
                            <input type="text" id="search_facturador" placeholder="Buscar facturador…" class="fi" autocomplete="off"
                                oninput="filterDD('facturador')" onfocus="openDD('facturador')" onblur="blurDD('facturador')">
                            <input type="hidden" name="facturador" id="val_facturador">
                            <ul id="list_facturador" class="sdl cscroll"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" onclick="saveRecord()"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.95rem 2rem;border-radius:var(--rad);font-weight:800;cursor:pointer;font-size:.9rem;font-family:'Poppins',sans-serif;box-shadow:var(--sh-md);transition:var(--tr);"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-lg)'"
                onmouseout="this.style.transform='';this.style.boxShadow='var(--sh-md)'">
                <i class="fas fa-save"></i> Guardar Devolución
            </button>
        </form>
        </div>
    </div>
</div>

<script>
const USUARIOS  = <?php echo $usuarios_json; ?>;
const PRODUCTOS = <?php echo $productos_json; ?>;
const DEVOLUCIONES = <?php echo json_encode($devoluciones); ?>;


function openModal(action, id = null) {
    document.getElementById('devolucionModal').style.display = 'block';
    document.getElementById('formAction').value = action;
    if (action === 'create') {
        document.getElementById('modalTitle').textContent = 'Nueva Devolución';
        document.getElementById('devolucionForm').reset();
        document.getElementById('recordId').value = '';
        document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
        clearSku();
        clearDD('verificador');
        clearDD('facturador');
    }
}
function closeModal() {
    document.getElementById('devolucionModal').style.display = 'none';
}
window.onclick = e => { if (e.target === document.getElementById('devolucionModal')) closeModal(); };


function initDD(key) {
    const ul = document.getElementById('list_'+key);
    ul.innerHTML = '';
    USUARIOS.forEach(n => {
        const li = document.createElement('li');
        li.textContent = n;
        li.onmousedown = e => { e.preventDefault(); selectDD(key, n); };
        ul.appendChild(li);
    });
}
function filterDD(key) {
    const q  = document.getElementById('search_'+key).value.toLowerCase();
    const ul = document.getElementById('list_'+key);
    let vis  = 0;
    ul.querySelectorAll('li:not(.nores)').forEach(li => {
        const show = li.textContent.toLowerCase().includes(q);
        li.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    const nr = ul.querySelector('.nores');
    if (vis === 0 && !nr) { const li = document.createElement('li'); li.className='nores'; li.textContent='Sin resultados'; ul.appendChild(li); }
    else if (vis > 0 && nr) nr.remove();
    ul.classList.add('open');
}
function openDD(key) { initDD(key); filterDD(key); }
function blurDD(key) { setTimeout(() => document.getElementById('list_'+key).classList.remove('open'), 160); }
function selectDD(key, val) {
    document.getElementById('search_'+key).value = val;
    document.getElementById('val_'+key).value     = val;
    document.getElementById('list_'+key).classList.remove('open');
}
function clearDD(key) {
    document.getElementById('search_'+key).value = '';
    document.getElementById('val_'+key).value     = '';
}


function searchProducts(q) {
    const ul = document.getElementById('skuResults');
    if (q.length < 2) { ul.classList.remove('open'); return; }
    const filtered = PRODUCTOS.filter(p =>
        p.id_material.toLowerCase().includes(q.toLowerCase()) ||
        p.material.toLowerCase().includes(q.toLowerCase())
    ).slice(0, 12);
    ul.innerHTML = '';
    if (!filtered.length) {
        const li = document.createElement('li'); li.className='nores'; li.textContent='Sin resultados'; ul.appendChild(li);
    } else {
        filtered.forEach(p => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${p.id_material}</strong> — ${p.material}`;
            li.onmousedown = e => { e.preventDefault(); selectProduct(p.id_material, p.material); };
            ul.appendChild(li);
        });
    }
    ul.classList.add('open');
}
function selectProduct(id, name) {
    const val = `${id} - ${name}`;
    document.getElementById('skuSearch').value = val;
    document.getElementById('sku').value        = val;
    document.getElementById('skuSearch').classList.add('has-val');
    document.getElementById('skuResults').classList.remove('open');
    const sel = document.getElementById('skuSelected');
    sel.querySelector('span').textContent = val;
    sel.style.display = 'block';
}
function clearSku() {
    document.getElementById('skuSearch').value = '';
    document.getElementById('sku').value        = '';
    document.getElementById('skuSearch').classList.remove('has-val');
    document.getElementById('skuResults').classList.remove('open');
    document.getElementById('skuSelected').style.display = 'none';
}
document.addEventListener('click', e => {
    if (!e.target.closest('#skuSearch') && !e.target.closest('#skuResults')) {
        document.getElementById('skuResults').classList.remove('open');
    }
});


function viewRecord(id) {
    const r = DEVOLUCIONES.find(d => d.id == id);
    if (!r) return;
    const dt = (v,fallback='-') => v || fallback;
    const badge = c => {
        const cls = { KA:'#1565c0', MM:'#7b1fa2', T4:'#2e7d32', TAT:'#e65100' };
        return c ? `<span style="background:${cls[c]||'#666'};color:#fff;padding:2px 8px;border-radius:6px;font-weight:700;font-size:.8rem;">${c}</span>` : '-';
    };
    Swal.fire({
        title: 'Detalle de Devolución',
        html: `
        <div style="text-align:left;font-family:'Poppins',sans-serif;font-size:.83rem;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem .9rem;">
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Fecha</span><br><strong>${r.fecha ? new Date(r.fecha+'T00:00:00').toLocaleDateString('es-CO') : '-'}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Canal</span><br>${badge(r.canal)}</div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Operador</span><br><strong>${dt(r.operador)}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">SKU</span><br><strong>${dt(r.sku)}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">DT</span><br><strong>${dt(r.dt)}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Unidades</span><br><strong style="color:#16a34a;font-size:1.1rem;">${r.unidades || '-'}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Causal</span><br><strong>${dt(r.casual)}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Status</span><br><strong>${dt(r.status)}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Verificador</span><br><strong>${dt(r.verificador)}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Facturador</span><br><strong>${dt(r.facturador)}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Placa</span><br><strong>${dt(r.placa)}</strong></div>
                <div><span style="color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;">Creación</span><br><strong>${r.fecha_creacion ? new Date(r.fecha_creacion).toLocaleString('es-CO') : '-'}</strong></div>
            </div>
        </div>`,
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#FFD700',
        width: '540px'
    });
}


function editRecord(id) {
    const r = DEVOLUCIONES.find(d => d.id == id);
    if (!r) return;
    document.getElementById('modalTitle').textContent  = 'Editar Devolución';
    document.getElementById('formAction').value        = 'update';
    document.getElementById('recordId').value          = r.id;
    document.getElementById('fecha').value             = r.fecha    || '';
    document.getElementById('canal').value             = r.canal    || '';
    document.getElementById('operador').value          = r.operador || '';
    document.getElementById('dt').value                = r.dt       || '';
    document.getElementById('unidades').value          = r.unidades || '';
    document.getElementById('casual').value            = r.casual   || '';
    document.getElementById('status').value            = r.status   || '';
    document.getElementById('placa').value             = r.placa    || '';
    if (r.sku) { selectProduct('', ''); document.getElementById('skuSearch').value = r.sku; document.getElementById('sku').value = r.sku; document.getElementById('skuSelected').querySelector('span').textContent = r.sku; document.getElementById('skuSelected').style.display='block'; }
    else clearSku();
    if (r.verificador) selectDD('verificador', r.verificador);
    else clearDD('verificador');
    if (r.facturador) selectDD('facturador', r.facturador);
    else clearDD('facturador');
    document.getElementById('devolucionModal').style.display = 'block';
}


function saveRecord() {
    if (!document.getElementById('sku').value) {
        Swal.fire({ title:'Campo requerido', text:'Debe seleccionar un producto', icon:'warning', confirmButtonColor:'#FFD700' });
        return;
    }
    if (!document.getElementById('val_verificador').value) {
        Swal.fire({ title:'Campo requerido', text:'Debe seleccionar un verificador', icon:'warning', confirmButtonColor:'#FFD700' });
        return;
    }
    if (!document.getElementById('val_facturador').value) {
        Swal.fire({ title:'Campo requerido', text:'Debe seleccionar un facturador', icon:'warning', confirmButtonColor:'#FFD700' });
        return;
    }
    Swal.fire({ title:'Guardando…', allowOutsideClick:false, showConfirmButton:false, didOpen:()=>Swal.showLoading() });
    document.getElementById('devolucionForm').submit();
}

function filtrarPorMes() {
    window.location.href = '?mes=' + document.getElementById('mes_filtro').value;
}

document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key==='n') { e.preventDefault(); openModal('create'); }
    if (e.key==='Escape') closeModal();
});

<?php if (isset($success_message)): ?>
    Swal.fire({ title:'¡Éxito!', text:'<?php echo $success_message; ?>', icon:'success', confirmButtonColor:'#FFD700' });
<?php endif; ?>
<?php if (isset($error_message)): ?>
    Swal.fire({ title:'Error', text:'<?php echo addslashes($error_message); ?>', icon:'error', confirmButtonColor:'#FFD700' });
<?php endif; ?>
</script>