<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';

date_default_timezone_set('America/Bogota');

$user_cargo          = $_SESSION['cargo']  ?? 'operador';
$nombre_sesion_activa = $_SESSION['nombre'] ?? 'Usuario';

$sql_usuarios = "SELECT nombre FROM usuarios WHERE activo = 1 AND operacion_id = ? ORDER BY nombre ASC";
$stmt_usuarios = $pdo->prepare($sql_usuarios);
$stmt_usuarios->execute([getOperacionActiva()]);
$lista_usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_COLUMN);

if ($_POST && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'agregar' || $_POST['accion'] == 'editar') {
        try {
            if (!file_exists('uploads')) mkdir('uploads', 0777, true);

            $imagenes = [];
            $campos_imagen = ['lavado_unidades','reempaque','staying','sendero','pns','vertimiento'];

            foreach ($campos_imagen as $campo) {
                if (isset($_FILES['imagen_'.$campo]) && $_FILES['imagen_'.$campo]['error'] == 0) {
                    $ext  = pathinfo($_FILES['imagen_'.$campo]['name'], PATHINFO_EXTENSION);
                    $file = 'turnoa_'.$campo.'_'.time().'_'.rand(1000,9999).'.'.$ext;
                    $dest = '../../uploads/'.$file;
                    $imagenes[$campo] = move_uploaded_file($_FILES['imagen_'.$campo]['tmp_name'], $dest) ? $file : null;
                } else {
                    $imagenes[$campo] = ($_POST['accion']=='editar' && isset($_POST['imagen_actual_'.$campo]))
                        ? $_POST['imagen_actual_'.$campo] : null;
                }
            }

            if ($_POST['accion'] == 'agregar') {
                $sql = "INSERT INTO turnoa_registros (
                    fecha_registro,supervisor,proyeccion_turno,manejo_handling,
                    vh_t1,tiempos_t1,vh_t2,tiempos_t2,vh_t4,tiempos_t4,vh_mkp,
                    horas_reempaque,cajas_reempacadas,horas_limpieza_clasificacion,cajas_clasificadas,
                    horas_lavado_unidades,cajas_lavadas,horas_vertimiento,cajas_vertidas,
                    horas_revision_rn,cajas_rn,horas_revision_nr,cajas_nr,
                    toma_temperatura,surtido_picking,estibas_sider_certificados,video_dpo,auxiliar_entrevistado,
                    imagen_lavado_unidades,imagen_reempaque,imagen_staying,imagen_sendero,imagen_pns,imagen_vertimiento,
                    operacion_id
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['fecha_registro'],$_POST['supervisor'],$_POST['proyeccion_turno'],$_POST['manejo_handling'],
                    floatval($_POST['vh_t1']),floatval($_POST['tiempos_t1']),floatval($_POST['vh_t2']),floatval($_POST['tiempos_t2']),
                    floatval($_POST['vh_t4']),floatval($_POST['tiempos_t4']),floatval($_POST['vh_mkp']),
                    floatval($_POST['horas_reempaque']),intval($_POST['cajas_reempacadas']),floatval($_POST['horas_limpieza_clasificacion']),intval($_POST['cajas_clasificadas']),
                    floatval($_POST['horas_lavado_unidades']),intval($_POST['cajas_lavadas']),floatval($_POST['horas_vertimiento']),intval($_POST['cajas_vertidas']),
                    floatval($_POST['horas_revision_rn']),intval($_POST['cajas_rn']),floatval($_POST['horas_revision_nr']),intval($_POST['cajas_nr']),
                    $_POST['toma_temperatura'],$_POST['surtido_picking'],$_POST['estibas_sider_certificados'],$_POST['video_dpo'],$_POST['auxiliar_entrevistado'],
                    $imagenes['lavado_unidades'],$imagenes['reempaque'],$imagenes['staying'],$imagenes['sendero'],$imagenes['pns'],$imagenes['vertimiento'],
                    getOperacionActiva()
                ]);
                $mensaje = 'Registro agregado correctamente';
            } else {
                $sql = "UPDATE turnoa_registros SET
                    fecha_registro=?,supervisor=?,proyeccion_turno=?,manejo_handling=?,
                    vh_t1=?,tiempos_t1=?,vh_t2=?,tiempos_t2=?,vh_t4=?,tiempos_t4=?,vh_mkp=?,
                    horas_reempaque=?,cajas_reempacadas=?,horas_limpieza_clasificacion=?,cajas_clasificadas=?,
                    horas_lavado_unidades=?,cajas_lavadas=?,horas_vertimiento=?,cajas_vertidas=?,
                    horas_revision_rn=?,cajas_rn=?,horas_revision_nr=?,cajas_nr=?,
                    toma_temperatura=?,surtido_picking=?,estibas_sider_certificados=?,video_dpo=?,auxiliar_entrevistado=?,
                    imagen_lavado_unidades=?,imagen_reempaque=?,imagen_staying=?,imagen_sendero=?,imagen_pns=?,imagen_vertimiento=?
                    WHERE id=? AND operacion_id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['fecha_registro'],$_POST['supervisor'],$_POST['proyeccion_turno'],$_POST['manejo_handling'],
                    floatval($_POST['vh_t1']),floatval($_POST['tiempos_t1']),floatval($_POST['vh_t2']),floatval($_POST['tiempos_t2']),
                    floatval($_POST['vh_t4']),floatval($_POST['tiempos_t4']),floatval($_POST['vh_mkp']),
                    floatval($_POST['horas_reempaque']),intval($_POST['cajas_reempacadas']),floatval($_POST['horas_limpieza_clasificacion']),intval($_POST['cajas_clasificadas']),
                    floatval($_POST['horas_lavado_unidades']),intval($_POST['cajas_lavadas']),floatval($_POST['horas_vertimiento']),intval($_POST['cajas_vertidas']),
                    floatval($_POST['horas_revision_rn']),intval($_POST['cajas_rn']),floatval($_POST['horas_revision_nr']),intval($_POST['cajas_nr']),
                    $_POST['toma_temperatura'],$_POST['surtido_picking'],$_POST['estibas_sider_certificados'],$_POST['video_dpo'],$_POST['auxiliar_entrevistado'],
                    $imagenes['lavado_unidades'],$imagenes['reempaque'],$imagenes['staying'],$imagenes['sendero'],$imagenes['pns'],$imagenes['vertimiento'],
                    intval($_POST['id']), getOperacionActiva()
                ]);
                $mensaje = 'Registro actualizado correctamente';
            }

            echo "<script>
                Swal.fire({title:'¡Éxito!',text:'$mensaje',icon:'success',confirmButtonColor:'#FFD700'})
                .then(()=>window.location.reload());
            </script>";

        } catch (Exception $e) {
            echo "<script>
                Swal.fire({title:'Error',text:'Error al procesar registro: ".$e->getMessage()."',icon:'error',confirmButtonColor:'#FFD700'});
            </script>";
        }
    }
}

$sql  = "SELECT * FROM turnoa_registros WHERE operacion_id = ? ORDER BY fecha_creacion DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([getOperacionActiva()]);
$registros = $stmt->fetchAll();
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.tailwindcss.com"></script>

<script>
tailwind.config = {
    theme: { extend: { fontFamily: { poppins: ['Poppins','sans-serif'] } } }
}
</script>

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

    
    .cscroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .cscroll::-webkit-scrollbar-track { background: var(--bg); border-radius: 4px; }
    .cscroll::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 4px; }

    
    @keyframes fadeIn  { from{opacity:0}       to{opacity:1} }
    @keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
    @keyframes gPulse  {
        0%   { box-shadow:0 0 0 0 rgba(255,215,0,.7); }
        70%  { box-shadow:0 0 0 8px rgba(255,215,0,0); }
        100% { box-shadow:0 0 0 0 rgba(255,215,0,0); }
    }
    .m-bg  { animation: fadeIn  .25s ease-out; }
    .m-box { animation: slideUp .3s  ease-out; }
    .gpulse { animation: gPulse 2s infinite; }

    
    .fi {
        width: 100%;
        padding: 10px 13px;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        font-size: .85rem;
        font-family: 'Poppins', sans-serif;
        background: #fff;
        color: var(--black);
        transition: border-color .2s, box-shadow .2s;
        -webkit-appearance: none;
        appearance: none;
    }
    .fi:focus {
        outline: none;
        border-color: var(--gold);
        box-shadow: 0 0 0 3px rgba(255,215,0,.18);
    }
    .fi:disabled { background: var(--bg); color: var(--text-g); cursor: not-allowed; }
    .fi[type="file"] { padding: 7px 13px; cursor: pointer; }
    select.fi {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right .6rem center;
        background-repeat: no-repeat;
        background-size: 1.2em;
        padding-right: 2.2rem;
    }
    .fi.err { border-color: #dc3545 !important; }

    
    .sd { position: relative; z-index: 100; }
    .sdl {
        position: absolute;
        top: calc(100% + 3px); left: 0; right: 0;
        background: #fff;
        border: 1.5px solid var(--gold);
        border-radius: 10px;
        max-height: 190px;
        overflow-y: auto;
        z-index: 9999;
        box-shadow: 0 8px 24px rgba(255,215,0,.18);
        display: none;
    }
    .sdl.open { display: block; }
    .sdl li { padding: 9px 13px; cursor: pointer; font-size: .83rem; color: var(--black); transition: background .13s; list-style: none; }
    .sdl li:hover { background: #fffbeb; }
    .sdl li.nores { color: #94a3b8; cursor: default; }
    .sdl li.nores:hover { background: transparent; }

    
    .b-si { background:#dcfce7; color:#16a34a; border:1px solid #86efac; }
    .b-no { background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; }

    
    .sec {
        position: relative;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: var(--rad);
        padding: 1.2rem;
        height: 300px;
        margin-bottom: 1.2rem;
        overflow: hidden;
    }
    .sec::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: var(--gold-grad);
    }
    .sec-title {
        font-size: .88rem; font-weight: 700; color: var(--black);
        display: flex; align-items: center; gap: .5rem;
        margin-bottom: .9rem; padding-bottom: .6rem;
        border-bottom: 1.5px solid var(--border);
    }
    .sec-title i { color: var(--gold); }

    
    .lbl { display: block; font-size: .75rem; font-weight: 600; color: #2d2d2d; margin-bottom: 5px; }
    .lbl i { color: var(--gold-d); margin-right: 3px; }

    
    .dc { background: var(--bg); border: 1px solid var(--border); border-left: 4px solid var(--gold); border-radius: 10px; padding: .8rem 1rem; }
    .dc-lbl { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-g); margin-bottom: 3px; }
    .dc-val { font-size: .88rem; font-weight: 600; color: var(--black); }

    
    @media (max-width: 640px)  { .h-sm { display: none !important; } .sec { padding: .9rem; } }
    @media (max-width: 420px)  { .h-xs { display: none !important; } }

    
    .swal2-popup    { border-radius: var(--rad) !important; font-family: 'Poppins',sans-serif !important; border: 2px solid var(--gold) !important; }
    .swal2-title    { color: var(--black) !important; font-weight: 700 !important; }
    .swal2-confirm  { background: var(--gold-grad) !important; color: var(--black) !important; border-radius: 8px !important; font-weight: 700 !important; border: none !important; }
    .swal2-confirm:hover { transform: translateY(-2px) !important; box-shadow: var(--sh-md) !important; }
    .swal2-cancel   { background: var(--text-g) !important; border-radius: 8px !important; font-weight: 600 !important; border: none !important; }
</style>

<?php $usuarios_json = json_encode($lista_usuarios); ?>

<div style="max-width:1400px;margin:0 auto;padding:1rem 0.75rem;">

    
    <div style="position:relative;background:var(--black-grad);border-radius:var(--rad);padding:clamp(1.2rem,4vw,2.5rem);margin-bottom:1.25rem;overflow:hidden;box-shadow:var(--sh-lg);">
        <div style="position:absolute;inset:0;background:radial-gradient(circle at 80% 50%,rgba(255,215,0,.08) 0%,transparent 60%);pointer-events:none;"></div>
        <div style="position:relative;z-index:2;display:flex;align-items:center;gap:clamp(.75rem,3vw,1.5rem);">
            <div class="gpulse" style="flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--gold-grad);color:var(--black);border-radius:var(--rad);width:clamp(44px,8vw,64px);height:clamp(44px,8vw,64px);font-size:clamp(1.2rem,3.5vw,1.8rem);">
                <i class="fas fa-sun"></i>
            </div>
            <div style="min-width:0;">
                <h1 style="color:var(--gold);font-weight:800;margin:0;line-height:1.2;font-size:clamp(1.1rem,3.5vw,2rem);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    Turno A — Control Matutino
                </h1>
                <p style="color:#cbd5e1;font-size:clamp(.72rem,2vw,.95rem);margin-top:4px;">
                    Sistema de gestión · 6:00 AM – 2:00 PM
                </p>
            </div>
        </div>
    </div>

    
    <div style="display:flex;align-items:center;justify-content:space-between;background:#fff;border-radius:var(--rad);padding:.9rem 1.25rem;margin-bottom:1.25rem;box-shadow:var(--sh-md);border:1px solid var(--border);">
        <button onclick="openModal()"
            style="display:inline-flex;align-items:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:.65rem 1.1rem;border-radius:12px;font-weight:700;cursor:pointer;font-size:.85rem;font-family:'Poppins',sans-serif;box-shadow:0 4px 6px rgba(0,0,0,.08);transition:var(--tr);"
            onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-md)'"
            onmouseout="this.style.transform='';this.style.boxShadow='0 4px 6px rgba(0,0,0,.08)'">
            <i class="fas fa-plus-circle"></i> Nuevo Registro
        </button>
        <span style="display:inline-flex;align-items:center;gap:.5rem;font-size:.8rem;font-weight:700;color:var(--black);background:var(--bg);border:2px solid var(--gold);padding:.45rem 1rem;border-radius:20px;">
            <i class="fas fa-database" style="color:var(--gold);"></i>
            <?php echo count($registros); ?> registros
        </span>
    </div>

    
    <div style="background:#fff;border-radius:var(--rad);box-shadow:var(--sh-md);border:1px solid var(--border);overflow:hidden;">
        <div style="background:var(--black-grad);padding:1rem 1.5rem;">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.95rem,3vw,1.3rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-table"></i> Registros del Turno A
            </h2>
        </div>

        <?php if (empty($registros)): ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4rem 1rem;color:var(--text-g);">
                <i class="fas fa-clipboard-list" style="font-size:3.5rem;color:var(--border);margin-bottom:1rem;"></i>
                <h3 style="font-weight:700;color:var(--black);margin-bottom:.5rem;">No hay registros disponibles</h3>
                <p style="font-size:.9rem;">Comienza agregando el primer registro del Turno A</p>
            </div>
        <?php else: ?>
            <div class="cscroll" style="overflow-x:auto;max-height:70vh;">
                <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
                    <thead style="position:sticky;top:0;z-index:10;">
                        <tr style="background:var(--bg);border-bottom:2px solid var(--gold);">
                            <th style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;"><i class="fas fa-calendar" style="color:var(--gold-d);margin-right:4px;"></i>Fecha</th>
                            <th class="h-sm" style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;"><i class="fas fa-user-tie" style="color:var(--gold-d);margin-right:4px;"></i>Supervisor</th>
                            <th style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;"><i class="fas fa-clock" style="color:var(--gold-d);margin-right:4px;"></i>Turno</th>
                            <th class="h-sm" style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;">Handling</th>
                            <th class="h-xs" style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;">VH T1</th>
                            <th class="h-sm" style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;">Cjs Rmp.</th>
                            <th class="h-xs" style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;">Temp.</th>
                            <th class="h-sm" style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;">Picking</th>
                            <th class="h-sm" style="padding:.7rem .75rem;text-align:left;font-weight:700;color:var(--black);white-space:nowrap;">Video</th>
                            <th style="padding:.7rem .75rem;text-align:center;font-weight:700;color:var(--black);"><i class="fas fa-cogs" style="color:var(--gold-d);"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $reg): ?>
                            <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;"
                                onmouseover="this.style.background='rgba(255,215,0,.05)'"
                                onmouseout="this.style.background=''">
                                <td style="padding:.65rem .75rem;white-space:nowrap;font-weight:600;color:var(--black);"><?php echo date('d/m/Y H:i',strtotime($reg['fecha_registro'])); ?></td>
                                <td class="h-sm" style="padding:.65rem .75rem;white-space:nowrap;color:#444;"><?php echo htmlspecialchars($reg['supervisor']); ?></td>
                                <td style="padding:.65rem .75rem;white-space:nowrap;color:#444;"><?php echo $reg['proyeccion_turno']; ?></td>
                                <td class="h-sm" style="padding:.65rem .75rem;white-space:nowrap;">
                                    <span class="<?php echo $reg['manejo_handling']=='Sí'?'b-si':'b-no'; ?>" style="font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:20px;"><?php echo $reg['manejo_handling']; ?></span>
                                </td>
                                <td class="h-xs" style="padding:.65rem .75rem;white-space:nowrap;color:#444;"><?php echo $reg['vh_t1']; ?></td>
                                <td class="h-sm" style="padding:.65rem .75rem;white-space:nowrap;color:#444;"><?php echo $reg['cajas_reempacadas']; ?></td>
                                <td class="h-xs" style="padding:.65rem .75rem;white-space:nowrap;">
                                    <span class="<?php echo $reg['toma_temperatura']=='Sí'?'b-si':'b-no'; ?>" style="font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:20px;"><?php echo $reg['toma_temperatura']; ?></span>
                                </td>
                                <td class="h-sm" style="padding:.65rem .75rem;white-space:nowrap;">
                                    <span class="<?php echo $reg['surtido_picking']=='Sí'?'b-si':'b-no'; ?>" style="font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:20px;"><?php echo $reg['surtido_picking']; ?></span>
                                </td>
                                <td class="h-sm" style="padding:.65rem .75rem;white-space:nowrap;">
                                    <span class="<?php echo $reg['video_dpo']=='Sí'?'b-si':'b-no'; ?>" style="font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:20px;"><?php echo $reg['video_dpo']; ?></span>
                                </td>
                                <td style="padding:.65rem .75rem;white-space:nowrap;">
                                    <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                                        <button onclick="viewRecord(<?php echo $reg['id']; ?>)" title="Ver"
                                            style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:2px solid #17a2b8;color:#17a2b8;background:transparent;cursor:pointer;transition:var(--tr);"
                                            onmouseover="this.style.background='#17a2b8';this.style.color='#fff'"
                                            onmouseout="this.style.background='transparent';this.style.color='#17a2b8'">
                                            <i class="fas fa-eye" style="font-size:.7rem;"></i>
                                        </button>
                                        <?php if ($user_cargo==='admin'): ?>
                                            <button onclick="editRecord(<?php echo $reg['id']; ?>)" title="Editar"
                                                style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:2px solid #28a745;color:#28a745;background:transparent;cursor:pointer;transition:var(--tr);"
                                                onmouseover="this.style.background='#28a745';this.style.color='#fff'"
                                                onmouseout="this.style.background='transparent';this.style.color='#28a745'">
                                                <i class="fas fa-edit" style="font-size:.7rem;"></i>
                                            </button>
                                        <?php endif; ?>
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
    <div class="m-box" style="background:#fff;margin:auto;width:100%;max-width:950px;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-lg);">

        <div style="position:sticky;top:0;z-index:20;background:var(--black-grad);border-radius:var(--rad) var(--rad) 0 0;display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;box-shadow:0 4px 6px rgba(0,0,0,.2);">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.85rem,3vw,1.15rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-plus-circle"></i> <span id="modalTitle">Nuevo Registro Turno A</span>
            </h2>
            <button onclick="closeModal()"
                style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:transparent;border:none;color:#fff;font-size:1.6rem;cursor:pointer;font-weight:700;line-height:1;transition:var(--tr);"
                onmouseover="this.style.background='rgba(255,215,0,.18)';this.style.color='var(--gold)'"
                onmouseout="this.style.background='transparent';this.style.color='#fff'">
                &times;
            </button>
        </div>

        <div style="padding:1rem;padding-top:1.25rem;">
        <form method="POST" enctype="multipart/form-data" id="recordForm">
            <input type="hidden" name="accion" id="formAction" value="agregar">
            <input type="hidden" name="id"     id="recordId"   value="">

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-info-circle"></i> Información General</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.9rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-calendar-alt"></i> Fecha y Hora</label>
                        <input type="datetime-local" id="fecha_registro" name="fecha_registro" required class="fi">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-user-tie"></i> Supervisor (Usuario actual)</label>
                        <input type="text" id="supervisor_visual" class="fi" value="<?php echo htmlspecialchars($nombre_sesion_activa); ?>" disabled>
                        <input type="hidden" id="supervisor" name="supervisor" value="<?php echo htmlspecialchars($nombre_sesion_activa); ?>">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-clock"></i> Proyección de Turno</label>
                        <select id="proyeccion_turno" name="proyeccion_turno" required class="fi">
                            <option value="">Seleccionar...</option>
                            <option value="Turno A">Turno A</option>
                            <option value="Turno B">Turno B</option>
                            <option value="Turno C">Turno C</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-hand-holding-box"></i> Manejo (Handling)</label>
                        <select id="manejo_handling" name="manejo_handling" required class="fi">
                            <option value="">Seleccionar...</option>
                            <option value="Sí">Sí</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-truck"></i> Tiempos de Atención</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.9rem;">
                    <?php
                    $vt = [
                        ['vh_t1','Vh T1','fa-truck'],['tiempos_t1','Tiempos T1','fa-stopwatch'],
                        ['vh_t2','Vh T2','fa-truck'],['tiempos_t2','Tiempos T2','fa-stopwatch'],
                        ['vh_t4','Vh T4','fa-truck'],['tiempos_t4','Tiempos T4','fa-stopwatch'],
                        ['vh_mkp','Vh MKP','fa-truck'],
                    ];
                    foreach ($vt as [$id,$lbl,$ic]): ?>
                        <div>
                            <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $lbl; ?></label>
                            <input type="number" step="0.01" id="<?php echo $id; ?>" name="<?php echo $id; ?>" value="0" min="0" class="fi">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-boxes"></i> Productividades</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.9rem;">
                    <?php
                    $ops = [
                        ['horas_reempaque','Hrs Reempaque','fa-clock','0.01'],
                        ['cajas_reempacadas','Cajas Reempacadas','fa-box','1'],
                        ['horas_limpieza_clasificacion','Hrs Limpieza Clasif.','fa-clock','0.01'],
                        ['cajas_clasificadas','Cajas Clasificadas','fa-box','1'],
                        ['horas_lavado_unidades','Hrs Lavado Unid.','fa-clock','0.01'],
                        ['cajas_lavadas','Cajas Lavadas','fa-box','1'],
                        ['horas_vertimiento','Hrs Vertimiento','fa-clock','0.01'],
                        ['cajas_vertidas','Cajas Vertidas','fa-box','1'],
                        ['horas_revision_rn','Hrs Revisión RN','fa-clock','0.01'],
                        ['cajas_rn','Cajas RN','fa-box','1'],
                        ['horas_revision_nr','Hrs Revisión NR','fa-clock','0.01'],
                        ['cajas_nr','Cajas NR','fa-box','1'],
                    ];
                    foreach ($ops as [$id,$lbl,$ic,$step]): ?>
                        <div>
                            <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $lbl; ?></label>
                            <input type="number" step="<?php echo $step; ?>" id="<?php echo $id; ?>" name="<?php echo $id; ?>" value="0" min="0" class="fi">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-tasks"></i> Controles y Verificaciones</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.9rem;">
                    <div>
                        <label class="lbl"><i class="fas fa-thermometer-half"></i> Toma de Temperatura</label>
                        <select id="toma_temperatura" name="toma_temperatura" required class="fi">
                            <option value="">Seleccionar...</option>
                            <option value="Sí">Sí</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-hand-pointer"></i> Surtido Picking</label>
                        <select id="surtido_picking" name="surtido_picking" required class="fi">
                            <option value="">Seleccionar...</option>
                            <option value="Sí">Sí</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-certificate"></i> Estibas Sider Certificados</label>
                        <input type="text" id="estibas_sider_certificados" name="estibas_sider_certificados" placeholder="Información de certificados" class="fi">
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-video"></i> Video DPO</label>
                        <select id="video_dpo" name="video_dpo" required class="fi">
                            <option value="">Seleccionar...</option>
                            <option value="Sí">Sí</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl"><i class="fas fa-user"></i> Auxiliar Entrevistado</label>
                        <div class="sd" id="dd_aux">
                            <input type="text" id="search_auxiliar_entrevistado" placeholder="Buscar usuario…" class="fi"
                                autocomplete="off"
                                oninput="filterDD('auxiliar_entrevistado')"
                                onfocus="openDD('auxiliar_entrevistado')"
                                onblur="blurDD('auxiliar_entrevistado')">
                            <input type="hidden" id="auxiliar_entrevistado" name="auxiliar_entrevistado">
                            <ul id="list_auxiliar_entrevistado" class="sdl cscroll"></ul>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="sec">
                <div class="sec-title"><i class="fas fa-camera"></i> Entregas de Turno</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.9rem;">
                    <?php
                    $imgs = [
                        'lavado_unidades' => ['Lavado de Unidades','fa-tint'],
                        'reempaque'       => ['Reempaque','fa-box-open'],
                        'staying'         => ['Staying','fa-warehouse'],
                        'sendero'         => ['Sendero','fa-route'],
                        'pns'             => ['PNS','fa-shield-alt'],
                        'vertimiento'     => ['Vertimiento','fa-recycle'],
                    ];
                    foreach ($imgs as $campo=>[$lbl,$ic]): ?>
                        <div>
                            <label class="lbl"><i class="fas <?php echo $ic; ?>"></i> <?php echo $lbl; ?></label>
                            <input type="file" id="imagen_<?php echo $campo; ?>" name="imagen_<?php echo $campo; ?>" accept="image/*"
                                onchange="prevImg(this,'prv_<?php echo $campo; ?>')" class="fi">
                            <input type="hidden" name="imagen_actual_<?php echo $campo; ?>" id="imagen_actual_<?php echo $campo; ?>">
                            <div id="prv_<?php echo $campo; ?>" style="margin-top:6px;"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:var(--gold-grad);color:var(--black);border:none;padding:1rem 2rem;border-radius:var(--rad);font-weight:800;cursor:pointer;font-size:.95rem;font-family:'Poppins',sans-serif;box-shadow:var(--sh-md);transition:var(--tr);margin-top:.5rem;"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--sh-lg)'"
                onmouseout="this.style.transform='';this.style.boxShadow='var(--sh-md)'">
                <i class="fas fa-save"></i> Guardar Registro
            </button>
        </form>
        </div>
    </div>
</div>


<div id="viewModal" class="m-bg" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);overflow-y:auto;padding:.75rem;">
    <div class="m-box" style="background:#fff;margin:auto;width:100%;max-width:950px;border-radius:var(--rad);border:1px solid var(--border);box-shadow:var(--sh-lg);">
        <div style="position:sticky;top:0;z-index:20;background:var(--black-grad);border-radius:var(--rad) var(--rad) 0 0;display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;box-shadow:0 4px 6px rgba(0,0,0,.2);">
            <h2 style="color:var(--gold);font-weight:700;font-size:clamp(.85rem,3vw,1.15rem);display:flex;align-items:center;gap:.5rem;margin:0;">
                <i class="fas fa-eye"></i> Detalles del Registro
            </h2>
            <button onclick="closeViewModal()"
                style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:transparent;border:none;color:#fff;font-size:1.6rem;cursor:pointer;font-weight:700;line-height:1;transition:var(--tr);"
                onmouseover="this.style.background='rgba(255,215,0,.18)';this.style.color='var(--gold)'"
                onmouseout="this.style.background='transparent';this.style.color='#fff'">
                &times;
            </button>
        </div>
        <div id="viewModalContent" style="padding:1rem 1.25rem;"></div>
    </div>
</div>

<script>
const USUARIOS  = <?php echo $usuarios_json; ?>;
const SUPERVISOR = <?php echo json_encode($nombre_sesion_activa); ?>;
let currentEditId = null;


function openModal() {
    document.getElementById('addModal').style.display = 'block';
    document.getElementById('modalTitle').textContent = 'Nuevo Registro Turno A';
    document.getElementById('formAction').value = 'agregar';
    document.getElementById('recordForm').reset();
    clearPreviews();
    setNow();
    document.getElementById('supervisor').value = SUPERVISOR;
    document.getElementById('supervisor_visual').value = SUPERVISOR;
    document.getElementById('search_auxiliar_entrevistado').value = '';
    document.getElementById('auxiliar_entrevistado').value = '';
}
function closeModal() {
    document.getElementById('addModal').style.display = 'none';
    currentEditId = null;
}
function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function setNow() {
    const n = new Date();
    document.getElementById('fecha_registro').value =
        n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0')
        +'T'+String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0');
}

function prevImg(input, pid) {
    const p = document.getElementById(pid);
    p.innerHTML = '';
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => { p.innerHTML = `<img src="${e.target.result}" style="width:100%;height:80px;object-fit:cover;border-radius:8px;margin-top:4px;border:2px solid var(--gold);">`; };
        r.readAsDataURL(input.files[0]);
    }
}
function clearPreviews() {
    ['lavado_unidades','reempaque','staying','sendero','pns','vertimiento']
        .forEach(c => document.getElementById('prv_'+c).innerHTML = '');
}


function initDD(id) {
    const ul = document.getElementById('list_'+id);
    ul.innerHTML = '';
    USUARIOS.forEach(n => {
        const li = document.createElement('li');
        li.textContent = n;
        li.onmousedown = e => { e.preventDefault(); selectDD(id, n); };
        ul.appendChild(li);
    });
}
function filterDD(id) {
    const q = document.getElementById('search_'+id).value.toLowerCase();
    const ul = document.getElementById('list_'+id);
    let vis = 0;
    ul.querySelectorAll('li:not(.nores)').forEach(li => {
        const show = li.textContent.toLowerCase().includes(q);
        li.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    const nr = ul.querySelector('.nores');
    if (vis === 0 && !nr) {
        const li = document.createElement('li');
        li.className = 'nores';
        li.textContent = 'Sin resultados';
        ul.appendChild(li);
    } else if (vis > 0 && nr) nr.remove();
    ul.classList.add('open');
}
function openDD(id) { initDD(id); filterDD(id); }
function blurDD(id) { setTimeout(() => document.getElementById('list_'+id).classList.remove('open'), 160); }
function selectDD(id, val) {
    document.getElementById('search_'+id).value = val;
    document.getElementById(id).value = val;
    document.getElementById('list_'+id).classList.remove('open');
}


function viewRecord(id) {
    fetch(`../../api/turnos/get_record.php?id=${id}`)
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                buildView(d.record);
                document.getElementById('viewModal').style.display = 'block';
            } else swalErr('No se pudo cargar el registro');
        }).catch(() => swalErr('Error al cargar el registro'));
}


function editRecord(id) {
    currentEditId = id;
    fetch(`../../api/turnos/get_record.php?id=${id}`)
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                fillEdit(d.record);
                document.getElementById('addModal').style.display = 'block';
                document.getElementById('modalTitle').textContent = 'Editar Registro Turno A';
                document.getElementById('formAction').value = 'editar';
                document.getElementById('recordId').value = id;
            } else swalErr('No se pudo cargar el registro para editar');
        }).catch(() => swalErr('Error al cargar el registro'));
}

function fillEdit(rec) {
    clearPreviews();
    ['fecha_registro','proyeccion_turno','manejo_handling',
     'vh_t1','tiempos_t1','vh_t2','tiempos_t2','vh_t4','tiempos_t4','vh_mkp',
     'horas_reempaque','cajas_reempacadas','horas_limpieza_clasificacion','cajas_clasificadas',
     'horas_lavado_unidades','cajas_lavadas','horas_vertimiento','cajas_vertidas',
     'horas_revision_rn','cajas_rn','horas_revision_nr','cajas_nr',
     'toma_temperatura','surtido_picking','estibas_sider_certificados','video_dpo'
    ].forEach(f => {
        const el = document.getElementById(f);
        if (el) el.value = f === 'fecha_registro' ? fmtDT(rec[f]) : (rec[f] ?? '');
    });
    document.getElementById('supervisor').value = rec['supervisor'] || '';
    document.getElementById('supervisor_visual').value = rec['supervisor'] || '';
    const av = rec['auxiliar_entrevistado'] || '';
    document.getElementById('search_auxiliar_entrevistado').value = av;
    document.getElementById('auxiliar_entrevistado').value = av;
    ['lavado_unidades','reempaque','staying','sendero','pns','vertimiento'].forEach(c => {
        const img = rec['imagen_'+c];
        if (img) {
            document.getElementById('imagen_actual_'+c).value = img;
            document.getElementById('prv_'+c).innerHTML = `<img src="../../uploads/${img}" style="width:100%;height:80px;object-fit:cover;border-radius:8px;margin-top:4px;border:2px solid var(--gold);">`;
        }
    });
}

function buildView(r) {
    const dc = (lbl, val, ic) => `
        <div class="dc">
            <div class="dc-lbl"><i class="fas ${ic}" style="color:var(--gold-d);margin-right:3px;"></i>${lbl}</div>
            <div class="dc-val">${val ?? 'N/A'}</div>
        </div>`;
    const imgBox = (lbl, img) => img
        ? `<div style="text-align:center;background:#fff;padding:10px;border-radius:12px;border:1px solid var(--border);">
            <img src="../../uploads/${img}" alt="${lbl}" onclick="openImgModal('../../uploads/${img}','${lbl}')"
                style="width:100%;height:100px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid var(--border);transition:.2s;"
                onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="margin-top:6px;font-size:.72rem;font-weight:700;color:var(--black);">${lbl}</div>
           </div>`
        : `<div style="text-align:center;background:#fff;padding:10px;border-radius:12px;border:1px dashed var(--border);">
            <div style="height:100px;display:flex;align-items:center;justify-content:center;background:var(--bg);border-radius:8px;">
                <i class="fas fa-image" style="font-size:1.6rem;color:var(--border);"></i>
            </div>
            <div style="margin-top:6px;font-size:.72rem;font-weight:700;color:#94a3b8;">${lbl}</div>
           </div>`;

    document.getElementById('viewModalContent').innerHTML = `
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:1rem;">
            ${dc('Fecha y Hora', formatDateTime(r.fecha_registro), 'fa-calendar-alt')}
            ${dc('Supervisor', r.supervisor, 'fa-user-tie')}
            ${dc('Proyección Turno', r.proyeccion_turno, 'fa-clock')}
            ${dc('Manejo (Handling)', r.manejo_handling, 'fa-hand-holding-box')}
            ${dc('Vh T1', r.vh_t1, 'fa-truck')} ${dc('Tiempos T1', r.tiempos_t1, 'fa-stopwatch')}
            ${dc('Vh T2', r.vh_t2, 'fa-truck')} ${dc('Tiempos T2', r.tiempos_t2, 'fa-stopwatch')}
            ${dc('Vh T4', r.vh_t4, 'fa-truck')} ${dc('Tiempos T4', r.tiempos_t4, 'fa-stopwatch')}
            ${dc('Vh MKP', r.vh_mkp, 'fa-truck')}
            ${dc('Hrs Reempaque', r.horas_reempaque, 'fa-clock')} ${dc('Cajas Reem.', r.cajas_reempacadas, 'fa-box')}
            ${dc('Hrs Limpieza', r.horas_limpieza_clasificacion, 'fa-clock')} ${dc('Cajas Clasif.', r.cajas_clasificadas, 'fa-box')}
            ${dc('Hrs Lavado', r.horas_lavado_unidades, 'fa-clock')} ${dc('Cajas Lavadas', r.cajas_lavadas, 'fa-box')}
            ${dc('Hrs Vertimiento', r.horas_vertimiento, 'fa-clock')} ${dc('Cajas Vertidas', r.cajas_vertidas, 'fa-box')}
            ${dc('Hrs Rev. RN', r.horas_revision_rn, 'fa-clock')} ${dc('Cajas RN', r.cajas_rn, 'fa-box')}
            ${dc('Hrs Rev. NR', r.horas_revision_nr, 'fa-clock')} ${dc('Cajas NR', r.cajas_nr, 'fa-box')}
            ${dc('Toma Temperatura', r.toma_temperatura, 'fa-thermometer-half')}
            ${dc('Surtido Picking', r.surtido_picking, 'fa-hand-pointer')}
            ${dc('Estibas Sider', r.estibas_sider_certificados || 'N/A', 'fa-certificate')}
            ${dc('Video DPO', r.video_dpo, 'fa-video')}
            ${dc('Auxiliar Entrevistado', r.auxiliar_entrevistado || 'N/A', 'fa-user')}
        </div>
        <div class="sec">
            <div class="sec-title"><i class="fas fa-camera"></i> Evidencias Fotográficas</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;">
                ${imgBox('Lavado Unidades', r.imagen_lavado_unidades)}
                ${imgBox('Reempaque', r.imagen_reempaque)}
                ${imgBox('Staying', r.imagen_staying)}
                ${imgBox('Sendero', r.imagen_sendero)}
                ${imgBox('PNS', r.imagen_pns)}
                ${imgBox('Vertimiento', r.imagen_vertimiento)}
            </div>
        </div>`;
}

function openImgModal(src, title) {
    Swal.fire({ title, imageUrl: src, imageWidth: 600, imageHeight: 400, imageAlt: title, showCloseButton: true, showConfirmButton: false });
}

function formatDateTime(ds) {
    const d = new Date(ds);
    return d.toLocaleString('es-CO', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', hour12: false });
}

function fmtDT(ds) {
    const d = new Date(ds);
    return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')
        +'T'+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');
}

function swalErr(msg) { Swal.fire({ title: 'Error', text: msg, icon: 'error', confirmButtonColor: '#FFD700' }); }

window.onclick = e => {
    if (e.target === document.getElementById('addModal'))  closeModal();
    if (e.target === document.getElementById('viewModal')) closeViewModal();
};

document.getElementById('recordForm').addEventListener('submit', function(e) {
    const req = ['fecha_registro','proyeccion_turno','manejo_handling','toma_temperatura','surtido_picking','video_dpo'];
    let ok = true;
    req.forEach(id => {
        const el = document.getElementById(id);
        if (!el || !el.value.trim()) { el.classList.add('err'); ok = false; }
    });
    if (!ok) {
        e.preventDefault();
        swalErr('Por favor complete todos los campos obligatorios');
    }
});

document.querySelectorAll('.fi').forEach(el => el.addEventListener('input', function() { this.classList.remove('err'); }));
</script>

</body>
</html>