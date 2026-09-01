<?php
require_once '../../core/config.php';
verificarLogin();
include '../../core/header.php';

date_default_timezone_set('America/Bogota');

$user_cargo           = $_SESSION['cargo']  ?? 'operador';
$nombre_sesion_activa = $_SESSION['nombre'] ?? 'Usuario';

if (!file_exists('uploads')) mkdir('uploads', 0777, true);

$sql_usuarios = "SELECT nombre FROM usuarios WHERE activo = 1 AND operacion_id = ? ORDER BY nombre ASC";
$stmt_u = $pdo->prepare($sql_usuarios);
$stmt_u->execute([getOperacionActiva()]);
$lista_usuarios = $stmt_u->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $picking_img = $pnc_img = $stayin_img = '';
        if (isset($_FILES['picking_img']) && $_FILES['picking_img']['error'] == 0) {
            $picking_img = '../../uploads/'.time().'_picking_'.basename($_FILES['picking_img']['name']);
            move_uploaded_file($_FILES['picking_img']['tmp_name'], $picking_img);
        }
        if (isset($_FILES['pnc_img']) && $_FILES['pnc_img']['error'] == 0) {
            $pnc_img = '../../uploads/'.time().'_pnc_'.basename($_FILES['pnc_img']['name']);
            move_uploaded_file($_FILES['pnc_img']['tmp_name'], $pnc_img);
        }
        if (isset($_FILES['stayin_img']) && $_FILES['stayin_img']['error'] == 0) {
            $stayin_img = '../../uploads/'.time().'_stayin_'.basename($_FILES['stayin_img']['name']);
            move_uploaded_file($_FILES['stayin_img']['tmp_name'], $stayin_img);
        }
        try {
            $sql = "INSERT INTO turnoc_registros (
                marca_temporal,supervisor,proyeccion_turno,cumplimiento_handling,
                vh_t1,tiempos_t1,vh_t2_plan,vh_t2_cargado,vh_cargado_xhr,hr_cargado_xhr,
                hr_inicio_armado_ka,hr_fin_armado_ka,productividad_cajas,hr_inicio_armado,
                hr_fin_armado,cajas_total,cajas_picking,porcentaje_picking,aux_rn,cajas_rn,
                aux_nr,cajas_nr,cajas_mkp,productividad_mkp,errores_auxiliares,pi_reabastecimiento,
                actividad_adicional_1,hrs_1,productividad_1,actividad_adicional_2,hrs_2,
                productividad_2,actividad_adicional_3,productividad_3,hrs_3,auxiliar_entrevistado,
                picking_img,pnc_img,stayin_img,operacion_id
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $_POST['marca_temporal'],$_POST['supervisor'],$_POST['proyeccion_turno'],$_POST['cumplimiento_handling'],
                !empty($_POST['vh_t1'])?$_POST['vh_t1']:0,
                !empty($_POST['tiempos_t1'])?$_POST['tiempos_t1']:0,
                !empty($_POST['vh_t2_plan'])?$_POST['vh_t2_plan']:0,
                !empty($_POST['vh_t2_cargado'])?$_POST['vh_t2_cargado']:0,
                !empty($_POST['vh_cargado_xhr'])?$_POST['vh_cargado_xhr']:0,
                !empty($_POST['hr_cargado_xhr'])?$_POST['hr_cargado_xhr']:0,
                !empty($_POST['hr_inicio_armado_ka'])?$_POST['hr_inicio_armado_ka']:null,
                !empty($_POST['hr_fin_armado_ka'])?$_POST['hr_fin_armado_ka']:null,
                !empty($_POST['productividad_cajas'])?$_POST['productividad_cajas']:0,
                !empty($_POST['hr_inicio_armado'])?$_POST['hr_inicio_armado']:null,
                !empty($_POST['hr_fin_armado'])?$_POST['hr_fin_armado']:null,
                !empty($_POST['cajas_total'])?$_POST['cajas_total']:0,
                !empty($_POST['cajas_picking'])?$_POST['cajas_picking']:0,
                !empty($_POST['porcentaje_picking'])?$_POST['porcentaje_picking']:0.00,
                !empty($_POST['aux_rn'])?$_POST['aux_rn']:0,
                !empty($_POST['cajas_rn'])?$_POST['cajas_rn']:0,
                !empty($_POST['aux_nr'])?$_POST['aux_nr']:0,
                !empty($_POST['cajas_nr'])?$_POST['cajas_nr']:0,
                !empty($_POST['cajas_mkp'])?$_POST['cajas_mkp']:0,
                !empty($_POST['productividad_mkp'])?$_POST['productividad_mkp']:0,
                !empty($_POST['errores_auxiliares'])?$_POST['errores_auxiliares']:0,
                !empty($_POST['pi_reabastecimiento'])?$_POST['pi_reabastecimiento']:0,
                !empty($_POST['actividad_adicional_1'])?$_POST['actividad_adicional_1']:null,
                !empty($_POST['hrs_1'])?$_POST['hrs_1']:null,
                !empty($_POST['productividad_1'])?$_POST['productividad_1']:0,
                !empty($_POST['actividad_adicional_2'])?$_POST['actividad_adicional_2']:null,
                !empty($_POST['hrs_2'])?$_POST['hrs_2']:null,
                !empty($_POST['productividad_2'])?$_POST['productividad_2']:0,
                !empty($_POST['actividad_adicional_3'])?$_POST['actividad_adicional_3']:null,
                !empty($_POST['productividad_3'])?$_POST['productividad_3']:null,
                !empty($_POST['hrs_3'])?$_POST['hrs_3']:null,
                !empty($_POST['auxiliar_entrevistado'])?$_POST['auxiliar_entrevistado']:null,
                $picking_img?:null,$pnc_img?:null,$stayin_img?:null,
                getOperacionActiva()
            ]);
            if ($result) {
                echo "<script>Swal.fire({title:'¡Éxito!',text:'Registro agregado exitosamente',icon:'success',confirmButtonColor:'#FFD700'}).then(()=>window.location.href='turnoc.php');</script>";
            } else throw new Exception('Error al ejecutar la consulta');
        } catch (Exception $e) {
            echo "<script>Swal.fire({title:'Error',text:'Error al agregar registro: ".addslashes($e->getMessage())."',icon:'error',confirmButtonColor:'#FFD700'});</script>";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $picking_img = $_POST['picking_img_actual'];
        $pnc_img     = $_POST['pnc_img_actual'];
        $stayin_img  = $_POST['stayin_img_actual'];
        if (isset($_FILES['picking_img_edit']) && $_FILES['picking_img_edit']['error'] == 0) {
            $picking_img = '../../uploads/'.time().'_picking_'.basename($_FILES['picking_img_edit']['name']);
            move_uploaded_file($_FILES['picking_img_edit']['tmp_name'], $picking_img);
        }
        if (isset($_FILES['pnc_img_edit']) && $_FILES['pnc_img_edit']['error'] == 0) {
            $pnc_img = '../../uploads/'.time().'_pnc_'.basename($_FILES['pnc_img_edit']['name']);
            move_uploaded_file($_FILES['pnc_img_edit']['tmp_name'], $pnc_img);
        }
        if (isset($_FILES['stayin_img_edit']) && $_FILES['stayin_img_edit']['error'] == 0) {
            $stayin_img = '../../uploads/'.time().'_stayin_'.basename($_FILES['stayin_img_edit']['name']);
            move_uploaded_file($_FILES['stayin_img_edit']['tmp_name'], $stayin_img);
        }
        try {
            $sql = "UPDATE turnoc_registros SET
                marca_temporal=?,supervisor=?,proyeccion_turno=?,cumplimiento_handling=?,
                vh_t1=?,tiempos_t1=?,vh_t2_plan=?,vh_t2_cargado=?,vh_cargado_xhr=?,hr_cargado_xhr=?,
                hr_inicio_armado_ka=?,hr_fin_armado_ka=?,productividad_cajas=?,hr_inicio_armado=?,
                hr_fin_armado=?,cajas_total=?,cajas_picking=?,porcentaje_picking=?,aux_rn=?,cajas_rn=?,
                aux_nr=?,cajas_nr=?,cajas_mkp=?,productividad_mkp=?,errores_auxiliares=?,pi_reabastecimiento=?,
                actividad_adicional_1=?,hrs_1=?,productividad_1=?,actividad_adicional_2=?,hrs_2=?,
                productividad_2=?,actividad_adicional_3=?,productividad_3=?,hrs_3=?,auxiliar_entrevistado=?,
                picking_img=?,pnc_img=?,stayin_img=?
                WHERE id=? AND operacion_id=?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $_POST['marca_temporal'],$_POST['supervisor'],$_POST['proyeccion_turno'],$_POST['cumplimiento_handling'],
                !empty($_POST['vh_t1'])?$_POST['vh_t1']:0,
                !empty($_POST['tiempos_t1'])?$_POST['tiempos_t1']:0,
                !empty($_POST['vh_t2_plan'])?$_POST['vh_t2_plan']:0,
                !empty($_POST['vh_t2_cargado'])?$_POST['vh_t2_cargado']:0,
                !empty($_POST['vh_cargado_xhr'])?$_POST['vh_cargado_xhr']:0,
                !empty($_POST['hr_cargado_xhr'])?$_POST['hr_cargado_xhr']:0,
                !empty($_POST['hr_inicio_armado_ka'])?$_POST['hr_inicio_armado_ka']:null,
                !empty($_POST['hr_fin_armado_ka'])?$_POST['hr_fin_armado_ka']:null,
                !empty($_POST['productividad_cajas'])?$_POST['productividad_cajas']:0,
                !empty($_POST['hr_inicio_armado'])?$_POST['hr_inicio_armado']:null,
                !empty($_POST['hr_fin_armado'])?$_POST['hr_fin_armado']:null,
                !empty($_POST['cajas_total'])?$_POST['cajas_total']:0,
                !empty($_POST['cajas_picking'])?$_POST['cajas_picking']:0,
                !empty($_POST['porcentaje_picking'])?$_POST['porcentaje_picking']:0.00,
                !empty($_POST['aux_rn'])?$_POST['aux_rn']:0,
                !empty($_POST['cajas_rn'])?$_POST['cajas_rn']:0,
                !empty($_POST['aux_nr'])?$_POST['aux_nr']:0,
                !empty($_POST['cajas_nr'])?$_POST['cajas_nr']:0,
                !empty($_POST['cajas_mkp'])?$_POST['cajas_mkp']:0,
                !empty($_POST['productividad_mkp'])?$_POST['productividad_mkp']:0,
                !empty($_POST['errores_auxiliares'])?$_POST['errores_auxiliares']:0,
                !empty($_POST['pi_reabastecimiento'])?$_POST['pi_reabastecimiento']:0,
                !empty($_POST['actividad_adicional_1'])?$_POST['actividad_adicional_1']:null,
                !empty($_POST['hrs_1'])?$_POST['hrs_1']:null,
                !empty($_POST['productividad_1'])?$_POST['productividad_1']:0,
                !empty($_POST['actividad_adicional_2'])?$_POST['actividad_adicional_2']:null,
                !empty($_POST['hrs_2'])?$_POST['hrs_2']:null,
                !empty($_POST['productividad_2'])?$_POST['productividad_2']:0,
                !empty($_POST['actividad_adicional_3'])?$_POST['actividad_adicional_3']:null,
                !empty($_POST['productividad_3'])?$_POST['productividad_3']:null,
                !empty($_POST['hrs_3'])?$_POST['hrs_3']:null,
                !empty($_POST['auxiliar_entrevistado'])?$_POST['auxiliar_entrevistado']:null,
                $picking_img?:null,$pnc_img?:null,$stayin_img?:null,
                $id, getOperacionActiva()
            ]);
            if ($result) {
                echo "<script>Swal.fire({title:'¡Éxito!',text:'Registro actualizado exitosamente',icon:'success',confirmButtonColor:'#FFD700'}).then(()=>window.location.href='turnoc.php');</script>";
            } else throw new Exception('Error al actualizar el registro');
        } catch (Exception $e) {
            echo "<script>Swal.fire({title:'Error',text:'Error al actualizar registro: ".addslashes($e->getMessage())."',icon:'error',confirmButtonColor:'#FFD700'});</script>";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM turnoc_registros WHERE id=? AND operacion_id=?");
            $result = $stmt->execute([$id, getOperacionActiva()]);
            if ($result) {
                echo "<script>Swal.fire({title:'¡Eliminado!',text:'Registro eliminado exitosamente',icon:'success',confirmButtonColor:'#FFD700'}).then(()=>window.location.href='turnoc.php');</script>";
            } else throw new Exception('Error al eliminar');
        } catch (Exception $e) {
            echo "<script>Swal.fire({title:'Error',text:'".addslashes($e->getMessage())."',icon:'error',confirmButtonColor:'#FFD700'});</script>";
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM turnoc_registros WHERE operacion_id = ? ORDER BY id DESC");
    $stmt->execute([getOperacionActiva()]);
    $registros = $stmt->fetchAll();
} catch (PDOException $e) {
    $registros = [];
}
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{poppins:['Poppins','sans-serif']}}}}</script>

<style>
    * { box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: #fff; color: #1a1a1a; }
    :root {
        --gold:       #FFD700;
        --gold-d:     #FFA500;
        --black:      #1a1a1a;
        --black-m:    #2d2d2d;
        --bg:         #f8f9fa;
        --border:     #e2e8f0;
        --text-g:     #6c757d;
        --rad:        16px;
        --gold-grad:  linear-gradient(135deg,#FFD700 0%,#FFA500 100%);
        --black-grad: linear-gradient(135deg,#1a1a1a 0%,#2d2d2d 100%);
        --sh-md:      0 8px 25px rgba(0,0,0,.1);
        --sh-lg:      0 20px 40px rgba(0,0,0,.15);
        --tr:         all .3s cubic-bezier(.4,0,.2,1);
    }
    .cscroll::-webkit-scrollbar{width:6px;height:6px}
    .cscroll::-webkit-scrollbar-track{background:var(--bg);border-radius:4px}
    .cscroll::-webkit-scrollbar-thumb{background:var(--gold);border-radius:4px}
    @keyframes fadeIn  {from{opacity:0}to{opacity:1}}
    @keyframes slideUp {from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    @keyframes gPulse  {0%{box-shadow:0 0 0 0 rgba(255,215,0,.7)}70%{box-shadow:0 0 0 8px rgba(255,215,0,0)}100%{box-shadow:0 0 0 0 rgba(255,215,0,0)}}
    .m-bg  { animation: fadeIn  .25s ease-out; }
    .m-box { animation: slideUp .3s  ease-out; }
    .gpulse { animation: gPulse 2s infinite; }
    .fi {
        width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;
        font-size:.82rem;font-family:'Poppins',sans-serif;background:#fff;color:var(--black);
        transition:border-color .2s,box-shadow .2s;-webkit-appearance:none;appearance:none;
    }
    .fi:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(255,215,0,.18)}
    .fi:disabled{background:var(--bg);color:var(--text-g);cursor:not-allowed}
    .fi[type="file"]{padding:6px 12px;cursor:pointer}
    select.fi{cursor:pointer;background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");background-position:right .6rem center;background-repeat:no-repeat;background-size:1.2em;padding-right:2.2rem}
    textarea.fi{resize:vertical;min-height:70px}
    .fi.err{border-color:#dc3545!important}
    .sd{position:relative}
    .sdl{position:absolute;top:calc(100% + 3px);left:0;right:0;background:#fff;border:1.5px solid var(--gold);border-radius:10px;max-height:190px;overflow-y:auto;z-index:9999;box-shadow:0 8px 24px rgba(255,215,0,.18);display:none}
    .sdl.open{display:block}
    .sdl li{padding:8px 12px;cursor:pointer;font-size:.8rem;color:var(--black);transition:background .13s;list-style:none}
    .sdl li:hover{background:#fffbeb}
    .sdl li.nores{color:#94a3b8;cursor:default}
    .sdl li.nores:hover{background:transparent}
    .b-si{background:#dcfce7;color:#16a34a;border:1px solid #86efac}
    .b-no{background:#fee2e2;color:#dc2626;border:1px solid #fca5a5}
    .sec{position:relative;background:var(--bg);border:1px solid var(--border);border-radius:var(--rad);padding:1.1rem;margin-bottom:1.1rem;overflow:hidden}
    .sec::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--gold-grad)}
    .sec-title{font-size:.83rem;font-weight:700;color:var(--black);display:flex;align-items:center;gap:.45rem;margin-bottom:.8rem;padding-bottom:.55rem;border-bottom:1.5px solid var(--border)}
    .sec-title i{color:var(--gold)}
    .lbl{display:block;font-size:.72rem;font-weight:600;color:#2d2d2d;margin-bottom:4px}
    .lbl i{color:var(--gold-d);margin-right:2px}
    .dc{background:var(--bg);border:1px solid var(--border);border-left:4px solid var(--gold);border-radius:10px;padding:.7rem .9rem}
    .dc-lbl{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-g);margin-bottom:2px}
    .dc-val{font-size:.84rem;font-weight:600;color:var(--black)}
    @media(max-width:640px){.h-sm{display:none!important}.sec{padding:.8rem}}
    @media(max-width:420px){.h-xs{display:none!important}}
    .swal2-popup{border-radius:var(--rad)!important;font-family:'Poppins',sans-serif!important;border:2px solid var(--gold)!important}
    .swal2-title{color:var(--black)!important;font-weight:700!important}
    .swal2-confirm{background:var(--gold-grad)!important;color:var(--black)!important;border-radius:8px!important;font-weight:700!important;border:none!important}
    .swal2-cancel{background:var(--text-g)!important;border-radius:8px!important;font-weight:600!important;border:none!important}
</style>

<?php $usuarios_json = json_encode($lista_usuarios); ?>

<div style="max-width:1400px;margin:0 auto;padding:1rem .75rem;">

    
    <div style="position:relative;background:var(--black-grad);border-radius:var(--rad);padding:clamp(1.2rem,4vw,2.5rem);margin-bottom:1.25rem;overflow:hidden;box-shadow:var(--sh-lg);">
        <div style="position:absolute;inset:0;background:radial-gradient(circle at 80% 50%,rgba(255,215,0,.08) 0%,transparent 60%);pointer-events:none;"></div>
        <div style="position:relative;z-index:2;display:flex;align-items:center;gap:clamp(.75rem,3vw,1.5rem);">
            <div class="gpulse" style="flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--gold-grad);color:var(--black);border-radius:var(--rad);width:clamp(44px,8vw,64px);height:clamp(44px,8vw,64px);font-size:clamp(1.2rem,3.5vw,1.8rem);">
                <i class="fas fa-moon"></i>
            </div>
            <div style="min-width:0;">
                <h1 style="color:var(--gold);font-weight:800;margin:0;line-height:1.2;font-size:clamp(1.1rem,3.5vw,2rem);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    Turno C — Control Nocturno
                </h1>
                <p style="color:#cbd5e1;font-size:clamp(.72rem,2vw,.95rem);margin-top:4px;">
                    Sistema de gestión · 10:00 PM – 6:00 AM
                </p>
            </div>
        </div>
    </div>

    
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;background:#fff;border-radius:var(--rad);padding:.9rem 1.25rem;margin-bottom:1.25rem;box-shadow:var(--sh-md);border:1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:.5rem;background:var(--bg);border:1.5px solid var(--border);border-radius:10px;padding:.45rem .8rem;flex:1;max-width:280px;transition:border-color .2s;"
            onfocusin="this.style.borderColor='var(--gold)'" onfocusout="this.style.borderColor='var(--border)'">
            <i class="fas fa-search" style="color:var(--text-g);font-size:.8rem;flex-shrink:0;"></i>
            <input type="text" id="searchInput" placeholder="Buscar registros…"
                style="border:none;outline:none;background:transparent;font-family:'Poppins',sans-serif;font-size:.8rem;width:100%;color:var(--black);">
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;">
            <button onclick="openAddModal()"
                style="display:inline-flex;align-items:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.65rem 1.1rem;border-radius:12px;font-weight:700;cursor:pointer;font-size:.82rem;font-family:'Poppins',sans-serif;box-shadow:0 4px 6px rgba(0,0,0,.08);transition:var(--tr);"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-md)'"
                onmouseout="this.style.transform='';this.style.boxShadow='0 4px 6px rgba(0,0,0,.08)'">
                <i class="fas fa-plus"></i> Nuevo Registro
            </button>
            <span style="display:inline-flex;align-items:center;gap:.5rem;font-size:.78rem;font-weight:700;color:var(--black);background:var(--bg);border:2px solid var(--gold);padding:.4rem .9rem;border-radius:20px;white-space:nowrap;">
                <i class="fas fa-database" style="color:var(--gold);"></i>
                <?php echo count($registros); ?> registros
            </span>
        </div>
    </div>

    
    <div style="background:#fff;border-radius:var(--rad);box-shadow:var(--sh-md);border:1px solid var(--border);overflow:hidden;">
        <div style="background:var(--black-grad);padding:1rem 1.5rem;">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.9rem,3vw,1.25rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-table"></i> Registros del Turno C
            </h2>
        </div>

        <?php if (empty($registros)): ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4rem 1rem;color:var(--text-g);">
                <i class="fas fa-inbox" style="font-size:3.5rem;color:var(--border);margin-bottom:1rem;"></i>
                <h3 style="font-weight:700;color:var(--black);margin-bottom:.5rem;">No hay registros disponibles</h3>
                <p style="font-size:.9rem;">Comienza agregando el primer registro del Turno C</p>
            </div>
        <?php else: ?>
            <div class="cscroll" style="overflow-x:auto;max-height:70vh;">
                <table id="mainTable" style="width:100%;border-collapse:collapse;font-size:.77rem;">
                    <thead style="position:sticky;top:0;z-index:10;">
                        <tr style="background:var(--bg);border-bottom:2px solid var(--gold);">
                            <th style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;"><i class="fas fa-hashtag" style="color:var(--gold-d);margin-right:3px;"></i>ID</th>
                            <th style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;"><i class="fas fa-calendar" style="color:var(--gold-d);margin-right:3px;"></i>Fecha</th>
                            <th class="h-sm" style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;"><i class="fas fa-user-tie" style="color:var(--gold-d);margin-right:3px;"></i>Supervisor</th>
                            <th class="h-sm" style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;">Proyección</th>
                            <th class="h-sm" style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;">Handling</th>
                            <th class="h-xs" style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;">VH T1</th>
                            <th class="h-xs" style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;">Cajas</th>
                            <th class="h-sm" style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;">% Picking</th>
                            <th class="h-sm" style="padding:.65rem .75rem;text-align:left;font-weight:700;white-space:nowrap;"><i class="fas fa-images" style="color:var(--gold-d);"></i></th>
                            <th style="padding:.65rem .75rem;text-align:center;font-weight:700;"><i class="fas fa-cogs" style="color:var(--gold-d);"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($registros as $row): ?>
                            <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;"
                                onmouseover="this.style.background='rgba(255,215,0,.05)'"
                                onmouseout="this.style.background=''">
                                <td style="padding:.6rem .75rem;font-weight:700;color:var(--black);">#<?php echo $row['id']; ?></td>
                                <td style="padding:.6rem .75rem;white-space:nowrap;">
                                    <span style="font-weight:600;color:var(--black);display:block;"><?php echo date('d/m/Y',strtotime($row['marca_temporal'])); ?></span>
                                    <span style="font-size:.7rem;color:var(--text-g);"><?php echo date('H:i',strtotime($row['marca_temporal'])); ?></span>
                                </td>
                                <td class="h-sm" style="padding:.6rem .75rem;white-space:nowrap;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <div style="width:26px;height:26px;border-radius:50%;background:var(--gold-grad);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-user" style="font-size:.6rem;color:var(--black);"></i>
                                        </div>
                                        <span style="font-size:.77rem;color:#444;"><?php echo htmlspecialchars($row['supervisor']); ?></span>
                                    </div>
                                </td>
                                <td class="h-sm" style="padding:.6rem .75rem;">
                                    <span class="<?php echo $row['proyeccion_turno']=='Si'?'b-si':'b-no'; ?>" style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:20px;white-space:nowrap;">
                                        <i class="fas <?php echo $row['proyeccion_turno']=='Si'?'fa-check':'fa-times'; ?>" style="margin-right:3px;"></i><?php echo $row['proyeccion_turno']; ?>
                                    </span>
                                </td>
                                <td class="h-sm" style="padding:.6rem .75rem;">
                                    <span class="<?php echo $row['cumplimiento_handling']=='Si'?'b-si':'b-no'; ?>" style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:20px;white-space:nowrap;">
                                        <i class="fas <?php echo $row['cumplimiento_handling']=='Si'?'fa-check':'fa-times'; ?>" style="margin-right:3px;"></i><?php echo $row['cumplimiento_handling']; ?>
                                    </span>
                                </td>
                                <td class="h-xs" style="padding:.6rem .75rem;font-weight:600;color:#444;"><?php echo number_format($row['vh_t1']); ?></td>
                                <td class="h-xs" style="padding:.6rem .75rem;font-weight:600;color:#444;"><?php echo number_format($row['cajas_total']); ?></td>
                                <td class="h-sm" style="padding:.6rem .75rem;">
                                    <div style="display:flex;align-items:center;gap:5px;">
                                        <div style="flex:1;height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
                                            <div style="height:100%;width:<?php echo min(100,$row['porcentaje_picking']); ?>%;background:var(--gold-grad);border-radius:3px;"></div>
                                        </div>
                                        <span style="font-size:.7rem;font-weight:600;color:var(--black);white-space:nowrap;"><?php echo $row['porcentaje_picking']; ?>%</span>
                                    </div>
                                </td>
                                <td class="h-sm" style="padding:.6rem .75rem;">
                                    
                                    <?php if($row['picking_img']): ?>
                                    <?php echo htmlspecialchars($row['picking_img']); ?>
                                    
                                    <?php echo htmlspecialchars($row['picking_img']); ?>
                                    <?php endif; ?>
                                    <?php if($row['pnc_img']): ?>
                                    <?php echo htmlspecialchars($row['pnc_img']); ?>
                                    
                                    <?php echo htmlspecialchars($row['pnc_img']); ?>
                                    <?php endif; ?>
                                    <?php if($row['stayin_img']): ?>
                                    <?php echo htmlspecialchars($row['stayin_img']); ?>
                                    
                                    <?php echo htmlspecialchars($row['stayin_img']); ?>
                                    <?php endif; ?>
                                    
                                </td>
                                <td style="padding:.6rem .75rem;">
                                    <div style="display:flex;align-items:center;justify-content:center;gap:5px;">
                                        <button onclick="viewRecord(<?php echo htmlspecialchars(json_encode($row)); ?>)" title="Ver"
                                            style="width:29px;height:29px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:2px solid #17a2b8;color:#17a2b8;background:transparent;cursor:pointer;transition:var(--tr);"
                                            onmouseover="this.style.background='#17a2b8';this.style.color='#fff'"
                                            onmouseout="this.style.background='transparent';this.style.color='#17a2b8'">
                                            <i class="fas fa-eye" style="font-size:.65rem;"></i>
                                        </button>
                                        <button onclick="editRecord(<?php echo htmlspecialchars(json_encode($row)); ?>)" title="Editar"
                                            style="width:29px;height:29px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:2px solid #28a745;color:#28a745;background:transparent;cursor:pointer;transition:var(--tr);"
                                            onmouseover="this.style.background='#28a745';this.style.color='#fff'"
                                            onmouseout="this.style.background='transparent';this.style.color='#28a745'">
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


<div id="addModal" class="m-bg" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);overflow-y:auto;padding:.75rem;">
    <div class="m-box" style="background:#fff;margin:auto;width:100%;max-width:980px;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-lg);">
        <div style="position:sticky;top:0;z-index:20;background:var(--black-grad);border-radius:var(--rad) var(--rad) 0 0;display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;box-shadow:0 4px 6px rgba(0,0,0,.2);">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.83rem,3vw,1.1rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-plus"></i> Agregar Nuevo Registro
            </h2>
            <button onclick="closeAddModal()" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:transparent;border:none;color:#fff;font-size:1.5rem;cursor:pointer;font-weight:700;line-height:1;transition:var(--tr);"
                onmouseover="this.style.background='rgba(255,215,0,.18)';this.style.color='var(--gold)'"
                onmouseout="this.style.background='transparent';this.style.color='#fff'">&times;</button>
        </div>
        <div style="padding:1rem;">
        <form method="POST" enctype="multipart/form-data" id="addForm">
            <input type="hidden" name="action" value="add">

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-info-circle"></i> Información General</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.8rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-calendar-alt"></i> Marca Temporal</label>
                        <input type="datetime-local" name="marca_temporal" id="add_marca_temporal" required class="fi" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-user-tie"></i> Supervisor (Usuario actual)</label>
                        <input type="text" id="add_supervisor_visual" class="fi" value="<?php echo htmlspecialchars($nombre_sesion_activa); ?>" disabled>
                        <input type="hidden" name="supervisor" id="add_supervisor" value="<?php echo htmlspecialchars($nombre_sesion_activa); ?>">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-bullseye"></i> Proyección de Turno</label>
                        <select name="proyeccion_turno" required class="fi">
                            <option value="">Seleccionar</option>
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-check-circle"></i> Cumplimiento Handling</label>
                        <select name="cumplimiento_handling" required class="fi">
                            <option value="">Seleccionar</option>
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-industry"></i> Datos de Producción</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.8rem;">
                    <?php foreach([
                        ['vh_t1','VH T1','fa-truck'],['tiempos_t1','Tiempos T1','fa-clock'],
                        ['vh_t2_plan','VH T2 Plan','fa-clipboard-list'],['vh_t2_cargado','VH T2 Cargado','fa-truck-loading'],
                        ['vh_cargado_xhr','VH Cargado XHR','fa-shipping-fast'],['hr_cargado_xhr','HR Cargado XHR','fa-hourglass-half'],
                    ] as [$n,$l,$ic]): ?>
                    <div>
                        <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $l; ?></label>
                        <input type="number" name="<?php echo $n; ?>" min="0" placeholder="0" class="fi">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-clock"></i> Horarios de Armado</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.8rem;">
                    <?php foreach([
                        ['hr_inicio_armado_ka','HR Inicio Armado KA','fa-play'],['hr_fin_armado_ka','HR Fin Armado KA','fa-stop'],
                        ['hr_inicio_armado','HR Inicio Armado','fa-play'],['hr_fin_armado','HR Fin Armado','fa-stop'],
                    ] as [$n,$l,$ic]): ?>
                    <div>
                        <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $l; ?></label>
                        <input type="time" name="<?php echo $n; ?>" class="fi">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-boxes"></i> Productividad y Cajas</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.8rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-chart-bar"></i> Productividad Cajas</label>
                        <input type="number" name="productividad_cajas" min="0" placeholder="0" class="fi">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-boxes"></i> Cajas Total</label>
                        <input type="number" name="cajas_total" id="add_cajas_total" min="0" placeholder="0" class="fi" oninput="calcPct('add')">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-hand-paper"></i> Cajas Picking</label>
                        <input type="number" name="cajas_picking" id="add_cajas_picking" min="0" placeholder="0" class="fi" oninput="calcPct('add')">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-percentage"></i> % Picking</label>
                        <input type="number" name="porcentaje_picking" id="add_porcentaje_picking" min="0" step="0.01" placeholder="0.00" class="fi">
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-users"></i> Auxiliares y Cajas</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.8rem;">
                    <?php foreach([
                        ['aux_rn','Aux RN','fa-user','number'],['cajas_rn','Cajas RN','fa-box','number'],
                        ['aux_nr','Aux NR','fa-user','number'],['cajas_nr','Cajas NR','fa-box','number'],
                        ['cajas_mkp','Cajas MKP','fa-box','number'],['productividad_mkp','Productividad MKP','fa-chart-line','number'],
                        ['errores_auxiliares','Errores Auxiliares','fa-exclamation-triangle','number'],['pi_reabastecimiento','PI Reabastecimiento','fa-sync','number'],
                    ] as [$n,$l,$ic,$t]): ?>
                    <div>
                        <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $l; ?></label>
                        <input type="<?php echo $t; ?>" name="<?php echo $n; ?>" min="0" placeholder="0" class="fi">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-tasks"></i> Actividades Adicionales</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.8rem;">
                    <?php foreach([1,2,3] as $n): ?>
                    <div>
                        <label class="lbl"><i class="fas fa-clipboard"></i> Actividad #<?php echo $n; ?></label>
                        <textarea name="actividad_adicional_<?php echo $n; ?>" class="fi" placeholder="Descripción…"></textarea>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-clock"></i> Hrs #<?php echo $n; ?></label>
                        <input type="text" name="hrs_<?php echo $n; ?>" class="fi" placeholder="Ej: 2.5">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-chart-bar"></i> Productividad #<?php echo $n; ?></label>
                        <input type="<?php echo $n<3?'number':'text'; ?>" name="productividad_<?php echo $n; ?>" <?php echo $n<3?'min="0"':''; ?> placeholder="0" class="fi">
                    </div>
                    <?php endforeach; ?>
                    <div>
                        <label class="lbl"><i class="fas fa-user-check"></i> Auxiliar Entrevistado</label>
                        <div class="sd" id="add_dd_aux">
                            <input type="text" id="add_search_aux" placeholder="Buscar usuario…" class="fi" autocomplete="off"
                                oninput="filterDD('add')" onfocus="openDD('add')" onblur="blurDD('add')">
                            <input type="hidden" name="auxiliar_entrevistado" id="add_aux_val">
                            <ul id="add_list_aux" class="sdl cscroll"></ul>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-images"></i> Imágenes de Respaldo</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.8rem;">
                    <?php foreach(['picking_img'=>['Imagen Picking','fa-hand-paper'],'pnc_img'=>['Imagen PNC','fa-clipboard-check'],'stayin_img'=>['Imagen Stayin','fa-home']] as $n=>[$l,$ic]): ?>
                    <div>
                        <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $l; ?></label>
                        <input type="file" name="<?php echo $n; ?>" accept="image/*" class="fi" onchange="prevImg(this,'add_prv_<?php echo $n; ?>')">
                        <div id="add_prv_<?php echo $n; ?>" style="margin-top:5px;"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.95rem 2rem;border-radius:var(--rad);font-weight:800;cursor:pointer;font-size:.9rem;font-family:'Poppins',sans-serif;box-shadow:var(--sh-md);transition:var(--tr);margin-top:.5rem;"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-lg)'"
                onmouseout="this.style.transform='';this.style.boxShadow='var(--sh-md)'">
                <i class="fas fa-save"></i> Guardar Registro
            </button>
        </form>
        </div>
    </div>
</div>


<div id="editModal" class="m-bg" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);overflow-y:auto;padding:.75rem;">
    <div class="m-box" style="background:#fff;margin:auto;width:100%;max-width:980px;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-lg);">
        <div style="position:sticky;top:0;z-index:20;background:var(--black-grad);border-radius:var(--rad) var(--rad) 0 0;display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;box-shadow:0 4px 6px rgba(0,0,0,.2);">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.83rem,3vw,1.1rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-edit"></i> Editar Registro
            </h2>
            <button onclick="closeEditModal()" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:transparent;border:none;color:#fff;font-size:1.5rem;cursor:pointer;font-weight:700;line-height:1;transition:var(--tr);"
                onmouseover="this.style.background='rgba(255,215,0,.18)';this.style.color='var(--gold)'"
                onmouseout="this.style.background='transparent';this.style.color='#fff'">&times;</button>
        </div>
        <div style="padding:1rem;">
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="picking_img_actual" id="picking_img_actual">
            <input type="hidden" name="pnc_img_actual"     id="pnc_img_actual">
            <input type="hidden" name="stayin_img_actual"  id="stayin_img_actual">

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-info-circle"></i> Información General</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.8rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-calendar-alt"></i> Marca Temporal</label>
                        <input type="datetime-local" name="marca_temporal" id="edit_marca_temporal" required class="fi">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-user-tie"></i> Supervisor</label>
                        <input type="text" id="edit_supervisor_visual" class="fi" disabled>
                        <input type="hidden" name="supervisor" id="edit_supervisor">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-bullseye"></i> Proyección de Turno</label>
                        <select name="proyeccion_turno" id="edit_proyeccion_turno" required class="fi">
                            <option value="">Seleccionar</option>
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-check-circle"></i> Cumplimiento Handling</label>
                        <select name="cumplimiento_handling" id="edit_cumplimiento_handling" required class="fi">
                            <option value="">Seleccionar</option>
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-industry"></i> Datos de Producción</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.8rem;">
                    <?php foreach([
                        ['vh_t1','VH T1','fa-truck'],['tiempos_t1','Tiempos T1','fa-clock'],
                        ['vh_t2_plan','VH T2 Plan','fa-clipboard-list'],['vh_t2_cargado','VH T2 Cargado','fa-truck-loading'],
                        ['vh_cargado_xhr','VH Cargado XHR','fa-shipping-fast'],['hr_cargado_xhr','HR Cargado XHR','fa-hourglass-half'],
                    ] as [$n,$l,$ic]): ?>
                    <div>
                        <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $l; ?></label>
                        <input type="number" name="<?php echo $n; ?>" id="edit_<?php echo $n; ?>" min="0" class="fi">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-clock"></i> Horarios de Armado</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.8rem;">
                    <?php foreach([
                        ['hr_inicio_armado_ka','HR Inicio KA','fa-play'],['hr_fin_armado_ka','HR Fin KA','fa-stop'],
                        ['hr_inicio_armado','HR Inicio Armado','fa-play'],['hr_fin_armado','HR Fin Armado','fa-stop'],
                    ] as [$n,$l,$ic]): ?>
                    <div>
                        <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $l; ?></label>
                        <input type="time" name="<?php echo $n; ?>" id="edit_<?php echo $n; ?>" class="fi">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-boxes"></i> Productividad y Cajas</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.8rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-chart-bar"></i> Productividad Cajas</label>
                        <input type="number" name="productividad_cajas" id="edit_productividad_cajas" min="0" class="fi">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-boxes"></i> Cajas Total</label>
                        <input type="number" name="cajas_total" id="edit_cajas_total" min="0" class="fi" oninput="calcPct('edit')">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-hand-paper"></i> Cajas Picking</label>
                        <input type="number" name="cajas_picking" id="edit_cajas_picking" min="0" class="fi" oninput="calcPct('edit')">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-percentage"></i> % Picking</label>
                        <input type="number" name="porcentaje_picking" id="edit_porcentaje_picking" min="0" step="0.01" class="fi">
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-users"></i> Auxiliares y Cajas</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.8rem;">
                    <?php foreach([
                        ['aux_rn','Aux RN','fa-user'],['cajas_rn','Cajas RN','fa-box'],
                        ['aux_nr','Aux NR','fa-user'],['cajas_nr','Cajas NR','fa-box'],
                        ['cajas_mkp','Cajas MKP','fa-box'],['productividad_mkp','Productividad MKP','fa-chart-line'],
                        ['errores_auxiliares','Errores Auxiliares','fa-exclamation-triangle'],['pi_reabastecimiento','PI Reabastecimiento','fa-sync'],
                    ] as [$n,$l,$ic]): ?>
                    <div>
                        <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $l; ?></label>
                        <input type="number" name="<?php echo $n; ?>" id="edit_<?php echo $n; ?>" min="0" class="fi">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-tasks"></i> Actividades Adicionales</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.8rem;">
                    <?php foreach([1,2,3] as $n): ?>
                    <div>
                        <label class="lbl"><i class="fas fa-clipboard"></i> Actividad #<?php echo $n; ?></label>
                        <textarea name="actividad_adicional_<?php echo $n; ?>" id="edit_actividad_adicional_<?php echo $n; ?>" class="fi"></textarea>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-clock"></i> Hrs #<?php echo $n; ?></label>
                        <input type="text" name="hrs_<?php echo $n; ?>" id="edit_hrs_<?php echo $n; ?>" class="fi">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-chart-bar"></i> Productividad #<?php echo $n; ?></label>
                        <input type="<?php echo $n<3?'number':'text'; ?>" name="productividad_<?php echo $n; ?>" id="edit_productividad_<?php echo $n; ?>" <?php echo $n<3?'min="0"':''; ?> class="fi">
                    </div>
                    <?php endforeach; ?>
                    <div>
                        <label class="lbl"><i class="fas fa-user-check"></i> Auxiliar Entrevistado</label>
                        <div class="sd" id="edit_dd_aux">
                            <input type="text" id="edit_search_aux" placeholder="Buscar usuario…" class="fi" autocomplete="off"
                                oninput="filterDD('edit')" onfocus="openDD('edit')" onblur="blurDD('edit')">
                            <input type="hidden" name="auxiliar_entrevistado" id="edit_aux_val">
                            <ul id="edit_list_aux" class="sdl cscroll"></ul>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-images"></i> Imágenes de Respaldo</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.8rem;">
                    <?php foreach(['picking_img_edit'=>['Imagen Picking','fa-hand-paper'],'pnc_img_edit'=>['Imagen PNC','fa-clipboard-check'],'stayin_img_edit'=>['Imagen Stayin','fa-home']] as $n=>[$l,$ic]): ?>
                    <div>
                        <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $l; ?></label>
                        <input type="file" name="<?php echo $n; ?>" accept="image/*" class="fi" onchange="prevImg(this,'edit_prv_<?php echo $n; ?>')">
                        <p style="font-size:.68rem;color:var(--text-g);margin-top:3px;">Dejar vacío para mantener la imagen actual</p>
                        <div id="edit_prv_<?php echo $n; ?>" style="margin-top:5px;"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.95rem 2rem;border-radius:var(--rad);font-weight:800;cursor:pointer;font-size:.9rem;font-family:'Poppins',sans-serif;box-shadow:var(--sh-md);transition:var(--tr);margin-top:.5rem;"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-lg)'"
                onmouseout="this.style.transform='';this.style.boxShadow='var(--sh-md)'">
                <i class="fas fa-save"></i> Actualizar Registro
            </button>
        </form>
        </div>
    </div>
</div>


<div id="viewModal" class="m-bg" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);overflow-y:auto;padding:.75rem;">
    <div class="m-box" style="background:#fff;margin:auto;width:100%;max-width:980px;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-lg);">
        <div style="position:sticky;top:0;z-index:20;background:var(--black-grad);border-radius:var(--rad) var(--rad) 0 0;display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;box-shadow:0 4px 6px rgba(0,0,0,.2);">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.83rem,3vw,1.1rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-eye"></i> Ver Registro Completo
            </h2>
            <button onclick="closeViewModal()" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:transparent;border:none;color:#fff;font-size:1.5rem;cursor:pointer;font-weight:700;line-height:1;transition:var(--tr);"
                onmouseover="this.style.background='rgba(255,215,0,.18)';this.style.color='var(--gold)'"
                onmouseout="this.style.background='transparent';this.style.color='#fff'">&times;</button>
        </div>
        <div id="viewContent" style="padding:1rem;"></div>
    </div>
</div>

<script>
const USUARIOS  = <?php echo $usuarios_json; ?>;
const SUPERVISOR = <?php echo json_encode($nombre_sesion_activa); ?>;


function openAddModal()  { document.getElementById('addModal').style.display='block'; clearAddPreviews(); }
function closeAddModal() { document.getElementById('addModal').style.display='none'; }
function closeEditModal(){ document.getElementById('editModal').style.display='none'; }
function closeViewModal(){ document.getElementById('viewModal').style.display='none'; }

window.onclick = e => {
    if(e.target===document.getElementById('addModal'))  closeAddModal();
    if(e.target===document.getElementById('editModal')) closeEditModal();
    if(e.target===document.getElementById('viewModal')) closeViewModal();
};


function prevImg(input, pid) {
    const p = document.getElementById(pid); p.innerHTML='';
    if(input.files&&input.files[0]){
        const r=new FileReader();
        r.onload=e=>{p.innerHTML=`<img src="${e.target.result}" style="width:100%;height:70px;object-fit:cover;border-radius:8px;margin-top:4px;border:2px solid var(--gold);">`};
        r.readAsDataURL(input.files[0]);
    }
}
function clearAddPreviews(){
    ['add_prv_picking_img','add_prv_pnc_img','add_prv_stayin_img'].forEach(id=>{
        const el=document.getElementById(id); if(el) el.innerHTML='';
    });
}


function calcPct(pfx){
    const t=parseFloat(document.getElementById(pfx+'_cajas_total').value)||0;
    const k=parseFloat(document.getElementById(pfx+'_cajas_picking').value)||0;
    const pct=document.getElementById(pfx+'_porcentaje_picking');
    pct.value = t>0 ? ((k/t)*100).toFixed(2) : '';
}


function initDD(pfx){
    const ul=document.getElementById(pfx+'_list_aux');
    ul.innerHTML='';
    USUARIOS.forEach(n=>{
        const li=document.createElement('li');
        li.textContent=n;
        li.onmousedown=e=>{e.preventDefault();selectDD(pfx,n)};
        ul.appendChild(li);
    });
}
function filterDD(pfx){
    const q=document.getElementById(pfx+'_search_aux').value.toLowerCase();
    const ul=document.getElementById(pfx+'_list_aux');
    let vis=0;
    ul.querySelectorAll('li:not(.nores)').forEach(li=>{
        const show=li.textContent.toLowerCase().includes(q);
        li.style.display=show?'':'none';
        if(show) vis++;
    });
    const nr=ul.querySelector('.nores');
    if(vis===0&&!nr){const li=document.createElement('li');li.className='nores';li.textContent='Sin resultados';ul.appendChild(li);}
    else if(vis>0&&nr) nr.remove();
    ul.classList.add('open');
}
function openDD(pfx){ initDD(pfx); filterDD(pfx); }
function blurDD(pfx){ setTimeout(()=>document.getElementById(pfx+'_list_aux').classList.remove('open'),160); }
function selectDD(pfx,val){
    document.getElementById(pfx+'_search_aux').value=val;
    document.getElementById(pfx+'_aux_val').value=val;
    document.getElementById(pfx+'_list_aux').classList.remove('open');
}


function confirmDelete(id){
    Swal.fire({title:'¿Estás seguro?',text:'Esta acción no se puede deshacer',icon:'warning',showCancelButton:true,
        confirmButtonColor:'#dc3545',cancelButtonColor:'#6c757d',confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar'})
    .then(r=>{if(r.isConfirmed){const f=document.createElement('form');f.method='POST';f.innerHTML=`<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;document.body.appendChild(f);f.submit();}});
}


function editRecord(rec){
    document.getElementById('edit_id').value=rec.id;
    document.getElementById('edit_marca_temporal').value=(rec.marca_temporal||'').replace(' ','T').substring(0,16);
    document.getElementById('edit_supervisor').value=rec.supervisor||'';
    document.getElementById('edit_supervisor_visual').value=rec.supervisor||'';
    document.getElementById('edit_proyeccion_turno').value=rec.proyeccion_turno||'';
    document.getElementById('edit_cumplimiento_handling').value=rec.cumplimiento_handling||'';
    const nums=['vh_t1','tiempos_t1','vh_t2_plan','vh_t2_cargado','vh_cargado_xhr','hr_cargado_xhr',
                'productividad_cajas','cajas_total','cajas_picking','porcentaje_picking',
                'aux_rn','cajas_rn','aux_nr','cajas_nr','cajas_mkp','productividad_mkp','errores_auxiliares','pi_reabastecimiento',
                'productividad_1','productividad_2'];
    nums.forEach(n=>{const el=document.getElementById('edit_'+n);if(el) el.value=rec[n]||'';});
    const times=['hr_inicio_armado_ka','hr_fin_armado_ka','hr_inicio_armado','hr_fin_armado'];
    times.forEach(n=>{const el=document.getElementById('edit_'+n);if(el) el.value=rec[n]||'';});
    const texts=['actividad_adicional_1','hrs_1','actividad_adicional_2','hrs_2','actividad_adicional_3','productividad_3','hrs_3'];
    texts.forEach(n=>{const el=document.getElementById('edit_'+n);if(el) el.value=rec[n]||'';});
    const av=rec.auxiliar_entrevistado||'';
    document.getElementById('edit_search_aux').value=av;
    document.getElementById('edit_aux_val').value=av;
    document.getElementById('picking_img_actual').value=rec.picking_img||'';
    document.getElementById('pnc_img_actual').value=rec.pnc_img||'';
    document.getElementById('stayin_img_actual').value=rec.stayin_img||'';
    ['edit_prv_picking_img_edit','edit_prv_pnc_img_edit','edit_prv_stayin_img_edit'].forEach(id=>{
        const el=document.getElementById(id);if(el) el.innerHTML='';
    });
    document.getElementById('editModal').style.display='block';
}


function viewRecord(rec){
    const dc=(lbl,val,ic)=>`
        <div class="dc">
            <div class="dc-lbl"><i class="fas ${ic}" style="color:var(--gold-d);margin-right:3px;"></i>${lbl}</div>
            <div class="dc-val">${val??'N/A'}</div>
        </div>`;
    const badge=(val)=>`<span class="${val=='Si'?'b-si':'b-no'}" style="font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:20px;"><i class="fas ${val=='Si'?'fa-check':'fa-times'}" style="margin-right:3px;"></i>${val}</span>`;
    const imgBox=(lbl,src)=>src
        ?`<div style="text-align:center;background:#fff;padding:10px;border-radius:12px;border:1px solid var(--border);">
            <img src="${src}" onclick="openImgModal('${src}','${lbl}')" style="width:100%;height:90px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid var(--border);transition:.2s;"
                onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="margin-top:5px;font-size:.7rem;font-weight:700;">${lbl}</div></div>`
        :`<div style="text-align:center;background:#fff;padding:10px;border-radius:12px;border:1px dashed var(--border);">
            <div style="height:90px;display:flex;align-items:center;justify-content:center;background:var(--bg);border-radius:8px;"><i class="fas fa-image" style="font-size:1.4rem;color:var(--border);"></i></div>
            <div style="margin-top:5px;font-size:.7rem;font-weight:700;color:#94a3b8;">${lbl}</div></div>`;
    const actCard=(n)=>rec['actividad_adicional_'+n]?`
        <div class="dc" style="grid-column:span 1;">
            <div class="dc-lbl"><i class="fas fa-clipboard" style="color:var(--gold-d);margin-right:3px;"></i>Actividad #${n}</div>
            <div class="dc-val" style="font-size:.78rem;">${rec['actividad_adicional_'+n]||'N/A'}</div>
            <div style="font-size:.7rem;color:var(--text-g);margin-top:4px;"><strong>Hrs:</strong> ${rec['hrs_'+n]||'N/A'} &nbsp;|&nbsp; <strong>Prod:</strong> ${rec['productividad_'+n]||'N/A'}</div>
        </div>`:'';

    document.getElementById('viewContent').innerHTML=`
        <div class="sec">
            <div class="sec-title"><i class="fas fa-info-circle"></i> Información General</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px;">
                ${dc('Fecha/Hora',new Date(rec.marca_temporal).toLocaleString('es-CO'),'fa-calendar-alt')}
                ${dc('Supervisor',rec.supervisor,'fa-user-tie')}
                <div class="dc"><div class="dc-lbl"><i class="fas fa-bullseye" style="color:var(--gold-d);margin-right:3px;"></i>Proyección</div><div class="dc-val">${badge(rec.proyeccion_turno)}</div></div>
                <div class="dc"><div class="dc-lbl"><i class="fas fa-check-circle" style="color:var(--gold-d);margin-right:3px;"></i>Handling</div><div class="dc-val">${badge(rec.cumplimiento_handling)}</div></div>
            </div>
        </div>
        <div class="sec">
            <div class="sec-title"><i class="fas fa-industry"></i> Datos de Producción &amp; Horarios</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;">
                ${dc('VH T1',rec.vh_t1,'fa-truck')}${dc('Tiempos T1',rec.tiempos_t1,'fa-clock')}
                ${dc('VH T2 Plan',rec.vh_t2_plan,'fa-clipboard-list')}${dc('VH T2 Cargado',rec.vh_t2_cargado,'fa-truck-loading')}
                ${dc('VH Cargado XHR',rec.vh_cargado_xhr,'fa-shipping-fast')}${dc('HR Cargado XHR',rec.hr_cargado_xhr,'fa-hourglass-half')}
                ${dc('HR Inicio KA',rec.hr_inicio_armado_ka,'fa-play')}${dc('HR Fin KA',rec.hr_fin_armado_ka,'fa-stop')}
                ${dc('HR Inicio Armado',rec.hr_inicio_armado,'fa-play')}${dc('HR Fin Armado',rec.hr_fin_armado,'fa-stop')}
            </div>
        </div>
        <div class="sec">
            <div class="sec-title"><i class="fas fa-boxes"></i> Productividad, Cajas y Auxiliares</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px;">
                ${dc('Prod. Cajas',rec.productividad_cajas,'fa-chart-bar')}${dc('Cajas Total',rec.cajas_total,'fa-boxes')}
                ${dc('Cajas Picking',rec.cajas_picking,'fa-hand-paper')}${dc('% Picking',(rec.porcentaje_picking||0)+'%','fa-percentage')}
                ${dc('Aux RN',rec.aux_rn,'fa-user')}${dc('Cajas RN',rec.cajas_rn,'fa-box')}
                ${dc('Aux NR',rec.aux_nr,'fa-user')}${dc('Cajas NR',rec.cajas_nr,'fa-box')}
                ${dc('Cajas MKP',rec.cajas_mkp,'fa-box')}${dc('Prod. MKP',rec.productividad_mkp,'fa-chart-line')}
                ${dc('Errores Aux.',rec.errores_auxiliares,'fa-exclamation-triangle')}${dc('PI Reabastecer',rec.pi_reabastecimiento,'fa-sync')}
                ${dc('Auxiliar Entrevistado',rec.auxiliar_entrevistado||'N/A','fa-user-check')}
            </div>
        </div>
        ${(rec.actividad_adicional_1||rec.actividad_adicional_2||rec.actividad_adicional_3)?`
        <div class="sec">
            <div class="sec-title"><i class="fas fa-tasks"></i> Actividades Adicionales</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">
                ${actCard(1)}${actCard(2)}${actCard(3)}
            </div>
        </div>`:''}
        <div class="sec">
            <div class="sec-title"><i class="fas fa-images"></i> Imágenes de Respaldo</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;">
                ${imgBox('Picking',rec.picking_img)}
                ${imgBox('PNC',rec.pnc_img)}
                ${imgBox('Stayin',rec.stayin_img)}
            </div>
        </div>`;
    document.getElementById('viewModal').style.display='block';
}


function openImgModal(src,title){
    Swal.fire({title,imageUrl:src,imageWidth:600,imageHeight:400,imageAlt:title,showCloseButton:true,showConfirmButton:false});
}


document.getElementById('searchInput').addEventListener('input',function(){
    const q=this.value.toLowerCase();
    document.querySelectorAll('#mainTable tbody tr').forEach(r=>{
        r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';
    });
});
</script>