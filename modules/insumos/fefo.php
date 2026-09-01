<?php

date_default_timezone_set('America/Bogota');

require_once '../../core/config.php';


verificarLogin();
$hoy = date('Y-m-d');

function calcularCumplimientoModulo($rotulo, $inicio, $fin) {
    if (empty($rotulo) || empty($inicio) || empty($fin)) {
        return 0; 
    }

    try {
        $date_rotulo = new DateTime($rotulo);
        $date_inicio = new DateTime($inicio);
        $date_fin = new DateTime($fin);

        $diff_inicio = abs($date_rotulo->diff($date_inicio)->days);
        $diff_fin = abs($date_rotulo->diff($date_fin)->days);

        
        if ($diff_inicio >= 15 || $diff_fin >= 15) {
            return 0;
        }

        return 100;
    } catch (Exception $e) {
        return 0;
    }
}

function obtenerPromedioAreaFefo($pdo, $area_id, $fecha_dia, $operacion_id) {
    $stmt = $pdo->prepare("
        SELECT r.fecha_rotulo, r.fecha_inicio, r.fecha_fin
        FROM fefo_subareas s
        LEFT JOIN fefo_registros r ON s.id = r.subarea_id AND r.fecha_dia = ? AND r.operacion_id = ?
        WHERE s.area_id = ? AND s.operacion_id = ?
    ");
    $stmt->execute([$fecha_dia, $operacion_id, $area_id, $operacion_id]);
    $subs = $stmt->fetchAll();

    if (count($subs) === 0) return 0;

    $suma = 0;
    foreach ($subs as $sub) {
        $suma += calcularCumplimientoModulo($sub['fecha_rotulo'] ?? '', $sub['fecha_inicio'] ?? '', $sub['fecha_fin'] ?? '');
    }

    return $suma / count($subs);
}

function obtenerCumplimientoGeneralFefo($pdo, $fecha_dia, $operacion_id) {
    $stmt = $pdo->prepare("SELECT id FROM fefo_areas WHERE operacion_id = ?");
    $stmt->execute([$operacion_id]);
    $areas_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($areas_ids)) return 0;

    $suma_total = 0;
    foreach ($areas_ids as $id) {
        $suma_total += obtenerPromedioAreaFefo($pdo, $id, $fecha_dia, $operacion_id);
    }

    return $suma_total / count($areas_ids);
}


if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_fefo') {
    header('Content-Type: application/json');
    $subarea_id = $_POST['id'];
    
    $rotulo = !empty($_POST['rotulo']) ? $_POST['rotulo'] : null;
    $inicio = !empty($_POST['inicio']) ? $_POST['inicio'] : null;
    $fin = !empty($_POST['fin']) ? $_POST['fin'] : null;
    $fecha_actual = date('Y-m-d H:i:s');

    try {
        
        $stmt = $pdo->prepare("
            INSERT INTO fefo_registros (subarea_id, fecha_dia, fecha_rotulo, fecha_inicio, fecha_fin, fecha_evaluacion, operacion_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            fecha_rotulo = VALUES(fecha_rotulo),
            fecha_inicio = VALUES(fecha_inicio),
            fecha_fin = VALUES(fecha_fin),
            fecha_evaluacion = VALUES(fecha_evaluacion)
        ");
        $stmt->execute([$subarea_id, $hoy, $rotulo, $inicio, $fin, $fecha_actual, getOperacionActiva()]);

        $stmt = $pdo->prepare("SELECT area_id FROM fefo_subareas WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$subarea_id, getOperacionActiva()]);
        $area_id = $stmt->fetchColumn();

        $sub_pct = calcularCumplimientoModulo($rotulo, $inicio, $fin);
        $area_pct = obtenerPromedioAreaFefo($pdo, $area_id, $hoy, getOperacionActiva());
        $general_pct = obtenerCumplimientoGeneralFefo($pdo, $hoy, getOperacionActiva());
        
        $fecha_eval_format = date('d/m/Y H:i', strtotime($fecha_actual));
        $cumple_texto = ($sub_pct == 100) ? 'Cumple' : 'No Cumple';

        echo json_encode([
            'success' => true,
            'area_id' => $area_id,
            'sub_pct' => round($sub_pct, 0),
            'area_pct' => round($area_pct, 2),
            'general_pct' => round($general_pct, 2),
            'ultima_evaluacion' => $fecha_eval_format,
            'estado_texto' => $cumple_texto
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'eliminar') {
    header('Content-Type: application/json');
    $id = $_POST['id'];
    $tipo = $_POST['tipo'];

    try {
        if ($tipo === 'area') {
            $stmt = $pdo->prepare("DELETE FROM fefo_areas WHERE id = ? AND operacion_id = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM fefo_subareas WHERE id = ? AND operacion_id = ?");
        }
        $stmt->execute([$id, getOperacionActiva()]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    function cleanInputData($data) { return htmlspecialchars(strip_tags(trim($data))); }

    if ($action === 'guardar_area') {
        $nombre = cleanInputData($_POST['nombre_area']);
        $id = isset($_POST['area_id_edit']) ? intval($_POST['area_id_edit']) : 0;
        
        if (!empty($nombre)) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE fefo_areas SET nombre = ? WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$nombre, $id, getOperacionActiva()]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO fefo_areas (nombre, operacion_id) VALUES (?, ?)");
                $stmt->execute([$nombre, getOperacionActiva()]);
            }
        }
        header("Location: fefo.php");
        exit;
    }

    if ($action === 'guardar_subarea') {
        $area_id = $_POST['area_id'];
        $nombre = cleanInputData($_POST['nombre_subarea']);
        $id = isset($_POST['subarea_id_edit']) ? intval($_POST['subarea_id_edit']) : 0;
        
        if (!empty($nombre) && !empty($area_id)) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE fefo_subareas SET area_id = ?, nombre = ? WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$area_id, $nombre, $id, getOperacionActiva()]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO fefo_subareas (area_id, nombre, operacion_id) VALUES (?, ?, ?)");
                $stmt->execute([$area_id, $nombre, getOperacionActiva()]);
            }
        }
        header("Location: fefo.php");
        exit;
    }
}


$stmt = $pdo->prepare("SELECT * FROM fefo_areas WHERE operacion_id = ? ORDER BY nombre ASC");
$stmt->execute([getOperacionActiva()]);
$areas = $stmt->fetchAll();
$subareas_por_area = [];
$totales_area = [];

foreach ($areas as $area) {

    $stmt = $pdo->prepare("
        SELECT s.id, s.nombre, s.area_id,
               r.fecha_rotulo, r.fecha_inicio, r.fecha_fin, r.fecha_evaluacion
        FROM fefo_subareas s
        LEFT JOIN fefo_registros r ON s.id = r.subarea_id AND r.fecha_dia = ? AND r.operacion_id = ?
        WHERE s.area_id = ? AND s.operacion_id = ?
        ORDER BY s.nombre ASC
    ");
    $stmt->execute([$hoy, getOperacionActiva(), $area['id'], getOperacionActiva()]);
    $subareas_por_area[$area['id']] = $stmt->fetchAll();

    $totales_area[$area['id']] = obtenerPromedioAreaFefo($pdo, $area['id'], $hoy, getOperacionActiva());
}

$cumplimiento_general = obtenerCumplimientoGeneralFefo($pdo, $hoy, getOperacionActiva());
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEFO - Control Diario de Fechas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; color: #333; }
        .container { max-width: 1400px; margin: 20px auto; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 30px; }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #FFD700; flex-wrap: wrap; gap: 20px; }
        .title-box h1 { color: #1a1a1a; font-size: 26px; font-weight: 600; display: flex; align-items: center; gap: 12px; margin-bottom: 5px; }
        .title-box h1 i { color: #FFD700; }
        .title-box p { color: #6c757d; font-size: 14px; margin-top: 5px; }
        .date-badge { display: inline-block; background: #e9ecef; padding: 4px 12px; border-radius: 20px; font-size: 12px; color: #495057; font-weight: 600; margin-top: 8px; }
        
        .general-score { text-align: center; background: #1a1a1a; padding: 15px 30px; border-radius: 12px; color: #ffffff; min-width: 250px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
        .score-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #FFD700; font-weight: 500; margin-bottom: 5px; }
        .score-value { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .progress-bar-bg { width: 100%; height: 6px; background: rgba(255,255,255,0.2); border-radius: 4px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #FFD700, #FFA500); transition: width 0.5s ease; }
        
        .actions-group { display: flex; gap: 15px; }
        .btn { border: none; padding: 10px 20px; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
        .btn-primary { background: #FFD700; color: #1a1a1a; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4); }
        .btn-dark { background: #1a1a1a; color: #FFD700; }
        .btn-dark:hover { background: #2d2d2d; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
        
        .table-container { width: 100%; overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        thead { background: #1a1a1a; color: white; }
        th { padding: 15px; text-align: center; font-weight: 500; font-size: 14px; }
        th:first-child { text-align: left; }
        th i { color: #FFD700; margin-right: 5px; }
        td { padding: 15px; vertical-align: middle; text-align: center; border-bottom: 1px solid #f1f1f1; }
        td:first-child { text-align: left; }
        
        .area-row { background: #f8f9fa; cursor: pointer; border-bottom: 2px solid #e9ecef; }
        .area-name-cell { display: flex; align-items: center; justify-content: space-between; }
        .area-name { font-weight: 600; font-size: 15px; color: #1a1a1a; display: flex; align-items: center; }
        
        .toggle-icon { margin-right: 12px; color: #FFD700; transition: transform 0.3s ease; }
        .toggle-icon.open { transform: rotate(90deg); }
        
        .subarea-row { background: #ffffff; }
        .subarea-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; }
        .subarea-name { color: #495057; font-weight: 500; font-size: 14px; }
        .eval-date { font-size: 11px; color: #6c757d; }
        
        .badge { display: inline-block; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-yellow { background: #FFD700; color: #1a1a1a; }
        .badge-dark { background: #e9ecef; color: #1a1a1a; }
        .badge-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .btn-crud { background: none; border: none; cursor: pointer; padding: 5px; font-size: 14px; margin-left: 5px; }
        .btn-edit { color: #007bff; }
        .btn-delete { color: #dc3545; }

        
        .date-input { 
            border: 1px solid #ced4da; 
            padding: 8px 12px; 
            border-radius: 6px; 
            font-family: 'Poppins', sans-serif; 
            font-size: 13px; 
            width: 100%;
            max-width: 140px; 
            text-align: center;
            background-color: #fff;
            color: #333;
            cursor: pointer;
        }
        .date-input:focus { border-color: #FFD700; box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25); outline: none; }
        
        .input-wrapper { display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .mobile-label { display: none; font-size: 11px; color: #6c757d; font-weight: 500; }

        .btn-save-row { background: #1a1a1a; color: #FFD700; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; cursor: pointer; transition: 0.3s; font-weight: 500; width: 100%; }
        .btn-save-row:hover { background: #FFD700; color: #1a1a1a; }

        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 30px; width: 90%; max-width: 450px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; }
        .modal-header h2 { font-size: 20px; color: #1a1a1a; }
        .btn-close { background: none; border: none; font-size: 24px; color: #6c757d; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-family: inherit; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }

        .empty-state { text-align: center; padding: 40px 20px; color: #6c757d; }

        
        @media (max-width: 900px) {
            .header-section { flex-direction: column; align-items: stretch; text-align: center; }
            .title-box h1 { justify-content: center; }
            .actions-group { flex-direction: column; }
            .btn { justify-content: center; width: 100%; }
            .general-score { margin-top: 10px; }

            
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none;  }
            
            .area-row { padding: 15px; border-radius: 8px; margin-bottom: 10px; background: #e9ecef; }
            .area-row td { padding: 5px 0; border: none; text-align: left; }
            .area-row td:nth-child(2), .area-row td:nth-child(3) { display: none;  }
            
            .subarea-row { margin: 10px 0 20px 0; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            .subarea-row td { padding: 10px 0; text-align: left; border: none; border-bottom: 1px solid #f8f9fa; }
            .subarea-row td:last-child { border-bottom: none; }
            
            .input-wrapper { flex-direction: row; justify-content: space-between; width: 100%; align-items: center; }
            .mobile-label { display: block; font-size: 13px; width: 45%; color: #495057; }
            .date-input { max-width: 50%; }
            
            .subarea-name-cell { padding-left: 0; }
            .badge-container { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; }
        }
    </style>
</head>
<body>
    
<?php  require_once '../../core/header.php';  ?>
<div class="container">
    <div class="header-section">
        <div class="title-box">
            <h1><i class="fas fa-calendar-check"></i> FEFO - Almacén</h1>
            <p>Control y Evaluación Diaria de Vencimientos</p>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> Fecha de Evaluación: <?php echo date('d/m/Y'); ?></div>
        </div>
        
        <div class="general-score">
            <div class="score-label">Cumplimiento del Día</div>
            <div class="score-value" id="generalScoreText"><?php echo round($cumplimiento_general, 2); ?>%</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="generalScoreBar" style="width: <?php echo round($cumplimiento_general, 2); ?>%;"></div>
            </div>
        </div>

        <div class="actions-group">
            <button class="btn btn-dark" onclick="abrirModalArea()">
                <i class="fas fa-folder-plus"></i> Nueva Área
            </button>
            <button class="btn btn-primary" onclick="abrirModalSubarea()">
                <i class="fas fa-layer-group"></i> Nuevo Módulo
            </button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Área / Módulo</th>
                    <th>Rótulo (Base)</th>
                    <th>Vencimiento Inicio</th>
                    <th>Vencimiento Fin</th>
                    <th style="width: 12%;">Acción</th>
                    <th style="width: 15%;">Estado del Día</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($areas)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <h3>Sin áreas configuradas</h3>
                                <p>Crea tu primera área para comenzar el registro diario.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($areas as $area): ?>
                        <tr class="area-row" onclick="toggleSubareas(<?php echo $area['id']; ?>)">
                            <td>
                                <div class="area-name-cell">
                                    <div class="area-name">
                                        <i class="fas fa-chevron-right toggle-icon" id="icon-<?php echo $area['id']; ?>"></i> 
                                        <?php echo htmlspecialchars($area['nombre']); ?>
                                    </div>
                                    <div>
                                        <button class="btn-crud btn-edit" onclick="editarArea(event, <?php echo $area['id']; ?>, '<?php echo htmlspecialchars(addslashes($area['nombre'])); ?>')"><i class="fas fa-edit"></i></button>
                                        <button class="btn-crud btn-delete" onclick="eliminarRegistro(event, <?php echo $area['id']; ?>, 'area')"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </td>
                            <td colspan="4" style="text-align: right; color: #6c757d; font-size: 13px;" class="hide-mobile">
                                Promedio Área (Hoy):
                            </td>
                            <td class="badge-container">
                                <span class="mobile-label hide-desktop" style="display:none;">Promedio:</span>
                                <span class="badge badge-yellow" id="badge-area-<?php echo $area['id']; ?>">
                                    <?php echo round($totales_area[$area['id']], 2); ?>%
                                </span>
                            </td>
                        </tr>

                        <?php if(!empty($subareas_por_area[$area['id']])): ?>
                            <?php foreach($subareas_por_area[$area['id']] as $sub): 
                                $sub_pct = calcularCumplimientoModulo($sub['fecha_rotulo'] ?? '', $sub['fecha_inicio'] ?? '', $sub['fecha_fin'] ?? '');
                                $evalDate = $sub['fecha_evaluacion'] ? date('H:i', strtotime($sub['fecha_evaluacion'])) : 'Pendiente hoy';
                                
                                $cumple_texto = "Sin Evaluar";
                                $badge_class = "badge-dark";
                                if ($sub['fecha_rotulo']) {
                                    $cumple_texto = ($sub_pct == 100) ? "Cumple" : "No Cumple";
                                    $badge_class = ($sub_pct == 100) ? "badge-success" : "badge-danger";
                                }
                            ?>
                                <tr class="subarea-row sub-row-<?php echo $area['id']; ?>" style="display: none;">
                                    <td>
                                        <div class="subarea-name-cell">
                                            <div class="subarea-header">
                                                <div class="subarea-name">
                                                    <i class="fas fa-cube" style="margin-right: 5px; color:#ccc;"></i> 
                                                    <?php echo htmlspecialchars($sub['nombre']); ?>
                                                </div>
                                                <div>
                                                    <button class="btn-crud btn-edit" onclick="editarSubarea(event, <?php echo $sub['id']; ?>, <?php echo $area['id']; ?>, '<?php echo htmlspecialchars(addslashes($sub['nombre'])); ?>')"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-crud btn-delete" onclick="eliminarRegistro(event, <?php echo $sub['id']; ?>, 'subarea')"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                            <div class="eval-date">
                                                <i class="far fa-clock"></i> Registro: <span id="eval-date-<?php echo $sub['id']; ?>"><?php echo $evalDate; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="input-wrapper">
                                            <span class="mobile-label">Rótulo (Base):</span>
                                            <input type="text" class="date-input flatpickr-input" id="rotulo-<?php echo $sub['id']; ?>" value="<?php echo $sub['fecha_rotulo'] ?? ''; ?>" placeholder="Seleccionar...">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-wrapper">
                                            <span class="mobile-label">Fecha Inicio:</span>
                                            <input type="text" class="date-input flatpickr-input" id="inicio-<?php echo $sub['id']; ?>" value="<?php echo $sub['fecha_inicio'] ?? ''; ?>" placeholder="Seleccionar...">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-wrapper">
                                            <span class="mobile-label">Fecha Fin:</span>
                                            <input type="text" class="date-input flatpickr-input" id="fin-<?php echo $sub['id']; ?>" value="<?php echo $sub['fecha_fin'] ?? ''; ?>" placeholder="Seleccionar...">
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn-save-row" onclick="guardarFechas(<?php echo $sub['id']; ?>)">
                                            <i class="fas fa-save"></i> Guardar
                                        </button>
                                    </td>
                                    
                                    <td class="badge-container">
                                        <span class="mobile-label" style="display:none;">Estado:</span>
                                        <span class="badge <?php echo $badge_class; ?>" id="badge-sub-<?php echo $sub['id']; ?>">
                                            <?php echo $cumple_texto; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="modalArea">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalAreaTitle">Nueva Área</h2>
            <button class="btn-close" type="button" onclick="cerrarModal('modalArea')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="guardar_area">
            <input type="hidden" name="area_id_edit" id="area_id_edit" value="0">
            <div class="form-group">
                <label>Nombre del Área</label>
                <input type="text" name="nombre_area" id="nombre_area" required class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" onclick="cerrarModal('modalArea')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modalSubarea">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalSubareaTitle">Nuevo Módulo</h2>
            <button class="btn-close" type="button" onclick="cerrarModal('modalSubarea')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="guardar_subarea">
            <input type="hidden" name="subarea_id_edit" id="subarea_id_edit" value="0">
            <div class="form-group">
                <label>Área Padre</label>
                <select name="area_id" id="select_area_id" required class="form-control">
                    <option value="">Seleccione un área</option>
                    <?php foreach($areas as $area): ?>
                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nombre del Módulo</label>
                <input type="text" name="nombre_subarea" id="nombre_subarea" required class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" onclick="cerrarModal('modalSubarea')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>

document.addEventListener("DOMContentLoaded", function() {
    flatpickr(".flatpickr-input", {
        dateFormat: "Y-m-d",
        locale: "es",
        allowInput: true,
        disableMobile: "true" 
    });

    
    if (window.innerWidth > 900) {
        const firstAreaIcon = document.querySelector('.toggle-icon');
        if(firstAreaIcon) {
            const areaId = firstAreaIcon.id.split('-')[1];
            toggleSubareas(areaId);
        }
    }
});

function toggleSubareas(areaId) {
    const rows = document.querySelectorAll('.sub-row-' + areaId);
    const icon = document.getElementById('icon-' + areaId);
    if(rows.length === 0) return;

    let isHidden = rows[0].style.display === 'none';
    
    
    let displayType = window.innerWidth > 900 ? 'table-row' : 'block';
    
    rows.forEach(row => row.style.display = isHidden ? displayType : 'none');
    
    if(isHidden) icon.classList.add('open');
    else icon.classList.remove('open');
}

function guardarFechas(subarea_id) {
    const rotulo = document.getElementById('rotulo-' + subarea_id).value;
    const inicio = document.getElementById('inicio-' + subarea_id).value;
    const fin = document.getElementById('fin-' + subarea_id).value;

    if (!rotulo || !inicio || !fin) {
        Swal.fire({
            title: 'Atención', 
            text: 'Debes completar las tres fechas del módulo para evaluar.', 
            icon: 'warning',
            confirmButtonColor: '#1a1a1a'
        });
        return;
    }

    const formData = new FormData();
    formData.append('ajax_action', 'update_fefo');
    formData.append('id', subarea_id);
    formData.append('rotulo', rotulo);
    formData.append('inicio', inicio);
    formData.append('fin', fin);

    fetch('fefo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const badgeSub = document.getElementById('badge-sub-' + subarea_id);
            
            
            badgeSub.innerText = data.estado_texto;
            badgeSub.className = 'badge ' + (data.sub_pct === 100 ? 'badge-success' : 'badge-danger');
            
            
            const timeOnly = data.ultima_evaluacion.split(' ')[1];
            document.getElementById('eval-date-' + subarea_id).innerText = timeOnly;

            
            document.getElementById('badge-area-' + data.area_id).innerText = data.area_pct + '%';
            document.getElementById('generalScoreText').innerText = data.general_pct + '%';
            document.getElementById('generalScoreBar').style.width = data.general_pct + '%';
            
            Swal.fire({
                title: data.estado_texto === 'Cumple' ? '¡Cumple!' : 'No Cumple',
                text: data.estado_texto === 'Cumple' ? 'Las fechas están dentro del rango permitido.' : 'Las fechas exceden los 15 días de diferencia.',
                icon: data.estado_texto === 'Cumple' ? 'success' : 'error',
                confirmButtonColor: '#1a1a1a'
            });
        } else {
            Swal.fire('Error', 'No se pudo guardar la información del día.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Problema de conexión.', 'error');
    });
}

function abrirModalArea() {
    document.getElementById('modalAreaTitle').innerText = 'Nueva Área';
    document.getElementById('area_id_edit').value = '0';
    document.getElementById('nombre_area').value = '';
    document.getElementById('modalArea').classList.add('active');
}

function editarArea(event, id, nombre) {
    event.stopPropagation(); 
    document.getElementById('modalAreaTitle').innerText = 'Editar Área';
    document.getElementById('area_id_edit').value = id;
    document.getElementById('nombre_area').value = nombre;
    document.getElementById('modalArea').classList.add('active');
}

function abrirModalSubarea() {
    document.getElementById('modalSubareaTitle').innerText = 'Nuevo Módulo';
    document.getElementById('subarea_id_edit').value = '0';
    document.getElementById('select_area_id').value = '';
    document.getElementById('nombre_subarea').value = '';
    document.getElementById('modalSubarea').classList.add('active');
}

function editarSubarea(event, id, area_id, nombre) {
    event.stopPropagation();
    document.getElementById('modalSubareaTitle').innerText = 'Editar Módulo';
    document.getElementById('subarea_id_edit').value = id;
    document.getElementById('select_area_id').value = area_id;
    document.getElementById('nombre_subarea').value = nombre;
    document.getElementById('modalSubarea').classList.add('active');
}

function eliminarRegistro(event, id, tipo) {
    event.stopPropagation();
    const texto = tipo === 'area' ? 'Se eliminarán también todos los módulos y registros de esta área.' : 'Esta acción no se puede deshacer.';
    
    Swal.fire({
        title: '¿Confirmar eliminación?',
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_action', 'eliminar');
            formData.append('id', id);
            formData.append('tipo', tipo);

            fetch('fefo.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.success) location.reload();
                else Swal.fire('Error', 'No se pudo procesar la solicitud', 'error');
            });
        }
    });
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('active');
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}


window.addEventListener('resize', function() {
    const isMobile = window.innerWidth <= 900;
    document.querySelectorAll('.subarea-row').forEach(row => {
        if (row.style.display !== 'none') {
            row.style.display = isMobile ? 'block' : 'table-row';
        }
    });
});
</script>
</body>
</html>