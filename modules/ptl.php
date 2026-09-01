<?php
require_once '../core/config.php';
verificarLogin();


function calcularPorcentajeFila($fila) {
    return (($fila['rotulacion'] + $fila['staking'] + $fila['nivel_almacenamiento'] + $fila['adherencia_abc']) / 4) * 100;
}

function obtenerPromedioArea($pdo, $area_id, $operacion_id) {
    $stmt = $pdo->prepare("SELECT * FROM ptl_areas WHERE id = ? AND operacion_id = ?");
    $stmt->execute([$area_id, $operacion_id]);
    $area = $stmt->fetch();

    if (!$area) return 0;

    $suma = calcularPorcentajeFila($area);
    $cantidad = 1;

    $stmt = $pdo->prepare("SELECT * FROM ptl_subareas WHERE area_id = ? AND operacion_id = ?");
    $stmt->execute([$area_id, $operacion_id]);
    $subs = $stmt->fetchAll();

    foreach ($subs as $sub) {
        $suma += calcularPorcentajeFila($sub);
        $cantidad++;
    }

    return $suma / $cantidad;
}

function obtenerCumplimientoGeneral($pdo, $operacion_id) {
    $stmt = $pdo->prepare("SELECT id FROM ptl_areas WHERE operacion_id = ?");
    $stmt->execute([$operacion_id]);
    $areas_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($areas_ids)) return 0;

    $suma_total = 0;
    foreach ($areas_ids as $id) {
        $suma_total += obtenerPromedioArea($pdo, $id, $operacion_id);
    }

    return $suma_total / count($areas_ids);
}


if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_ptl') {
    header('Content-Type: application/json');
    $id = $_POST['id'];
    $tipo = $_POST['tipo']; 
    $campo = $_POST['campo'];
    $valor = $_POST['valor'] == 'true' ? 1 : 0;

    $campos_validos = ['rotulacion', 'staking', 'nivel_almacenamiento', 'adherencia_abc'];
    
    if (in_array($campo, $campos_validos)) {
        try {
            if ($tipo === 'area') {
                $stmt = $pdo->prepare("UPDATE ptl_areas SET $campo = ? WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$valor, $id, getOperacionActiva()]);

                $stmt = $pdo->prepare("UPDATE ptl_subareas SET $campo = ? WHERE area_id = ? AND operacion_id = ?");
                $stmt->execute([$valor, $id, getOperacionActiva()]);

                $area_id = $id;
            } else {
                $stmt = $pdo->prepare("UPDATE ptl_subareas SET $campo = ? WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$valor, $id, getOperacionActiva()]);

                $stmt = $pdo->prepare("SELECT area_id FROM ptl_subareas WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$id, getOperacionActiva()]);
                $area_id = $stmt->fetchColumn();
            }

            $area_pct = obtenerPromedioArea($pdo, $area_id, getOperacionActiva());
            $general_pct = obtenerCumplimientoGeneral($pdo, getOperacionActiva());

            $sub_pct = 0;
            if ($tipo === 'subarea') {
                $stmt = $pdo->prepare("SELECT * FROM ptl_subareas WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$id, getOperacionActiva()]);
                $sub_pct = calcularPorcentajeFila($stmt->fetch());
            }

            echo json_encode([
                'success' => true,
                'tipo' => $tipo,
                'area_id' => $area_id,
                'area_pct' => round($area_pct, 2),
                'sub_pct' => round($sub_pct, 2),
                'general_pct' => round($general_pct, 2)
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    exit;
}


if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'eliminar') {
    header('Content-Type: application/json');
    $id = $_POST['id'];
    $tipo = $_POST['tipo'];

    try {
        if ($tipo === 'area') {
            $stmt = $pdo->prepare("DELETE FROM ptl_areas WHERE id = ? AND operacion_id = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM ptl_subareas WHERE id = ? AND operacion_id = ?");
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

    if ($action === 'guardar_area') {
        $nombre = limpiarDatos($_POST['nombre_area']);
        $id = isset($_POST['area_id_edit']) ? intval($_POST['area_id_edit']) : 0;
        
        if (!empty($nombre)) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE ptl_areas SET nombre = ? WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$nombre, $id, getOperacionActiva()]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO ptl_areas (nombre, operacion_id) VALUES (?, ?)");
                $stmt->execute([$nombre, getOperacionActiva()]);
            }
        }
        header("Location: ptl.php");
        exit;
    }

    if ($action === 'guardar_subarea') {
        $area_id = $_POST['area_id'];
        $nombre = limpiarDatos($_POST['nombre_subarea']);
        $id = isset($_POST['subarea_id_edit']) ? intval($_POST['subarea_id_edit']) : 0;
        
        if (!empty($nombre) && !empty($area_id)) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE ptl_subareas SET area_id = ?, nombre = ? WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$area_id, $nombre, $id, getOperacionActiva()]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO ptl_subareas (area_id, nombre, operacion_id) VALUES (?, ?, ?)");
                $stmt->execute([$area_id, $nombre, getOperacionActiva()]);
            }
        }
        header("Location: ptl.php");
        exit;
    }
}


$stmtAreas = $pdo->prepare("SELECT * FROM ptl_areas WHERE operacion_id = ? ORDER BY nombre ASC");
$stmtAreas->execute([getOperacionActiva()]);
$areas = $stmtAreas->fetchAll();
$subareas_por_area = [];
$totales_area = [];

foreach ($areas as $area) {
    $stmt = $pdo->prepare("SELECT * FROM ptl_subareas WHERE area_id = ? AND operacion_id = ? ORDER BY nombre ASC");
    $stmt->execute([$area['id'], getOperacionActiva()]);
    $subareas_por_area[$area['id']] = $stmt->fetchAll();

    $totales_area[$area['id']] = obtenerPromedioArea($pdo, $area['id'], getOperacionActiva());
}

$cumplimiento_general = obtenerCumplimientoGeneral($pdo, getOperacionActiva());

require_once '../core/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patrón Técnico Logístico (PTL)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        .container { max-width: 1400px; margin: 20px auto; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #FFD700; }
        .title-box h1 { color: #1a1a1a; font-size: 28px; font-weight: 600; display: flex; align-items: center; gap: 12px; margin-bottom: 5px; }
        .title-box h1 i { color: #FFD700; }
        .title-box p { color: #6c757d; font-size: 14px; }
        
        .general-score { text-align: center; background: #1a1a1a; padding: 15px 30px; border-radius: 12px; color: #ffffff; min-width: 250px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
        .score-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #FFD700; font-weight: 500; margin-bottom: 5px; }
        .score-value { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .progress-bar-bg { width: 100%; height: 6px; background: rgba(255,255,255,0.2); border-radius: 4px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #FFD700, #FFA500); transition: width 0.5s ease; }
        
        .actions-group { display: flex; gap: 15px; }
        .btn { border: none; padding: 12px 24px; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
        .btn-primary { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #1a1a1a; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4); }
        .btn-dark { background: #1a1a1a; color: #FFD700; }
        .btn-dark:hover { background: #2d2d2d; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
        
        .table-container { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); color: white; }
        th { padding: 15px; text-align: center; font-weight: 500; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.1); }
        th:first-child { text-align: left; }
        th:last-child { border-right: none; }
        th i { color: #FFD700; margin-right: 5px; }
        td { padding: 15px; vertical-align: middle; text-align: center; }
        td:first-child { text-align: left; }
        
        .area-row { background: #f8f9fa; cursor: pointer; border-bottom: 2px solid #e9ecef; transition: background 0.2s; }
        .area-row:hover { background: #e9ecef; }
        .area-name-cell { display: flex; align-items: center; justify-content: space-between; }
        .area-name { font-weight: 600; font-size: 15px; color: #1a1a1a; display: flex; align-items: center; }
        
        .toggle-icon { margin-right: 12px; color: #FFD700; transition: transform 0.3s ease; }
        .toggle-icon.open { transform: rotate(90deg); }
        
        .subarea-row { background: #ffffff; border-bottom: 1px solid #f1f1f1; }
        .subarea-name-cell { display: flex; align-items: center; justify-content: space-between; padding-left: 30px; }
        .subarea-name { color: #495057; font-weight: 500; font-size: 14px; display: flex; align-items: center; }
        
        .badge { display: inline-block; padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .badge-yellow { background: #FFD700; color: #1a1a1a; }
        .badge-dark { background: #e9ecef; color: #1a1a1a; }

        .btn-crud { background: none; border: none; cursor: pointer; padding: 5px; font-size: 14px; transition: color 0.2s; margin-left: 5px; }
        .btn-edit { color: #007bff; }
        .btn-edit:hover { color: #0056b3; }
        .btn-delete { color: #dc3545; }
        .btn-delete:hover { color: #c82333; }
        .crud-group { display: flex; align-items: center; }

        .check-cell { text-align: center; }
        .custom-checkbox { display: inline-block; position: relative; cursor: pointer; width: 24px; height: 24px; margin: 0 auto; }
        .custom-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
        .checkmark { position: absolute; top: 0; left: 0; height: 24px; width: 24px; background-color: #ffffff; border: 2px solid #ced4da; border-radius: 6px; transition: all 0.2s ease; }
        .custom-checkbox:hover input ~ .checkmark { border-color: #FFD700; }
        .custom-checkbox input:checked ~ .checkmark { background-color: #FFD700; border-color: #FFD700; }
        .checkmark:after { content: ""; position: absolute; display: none; }
        .custom-checkbox input:checked ~ .checkmark:after { display: block; }
        .custom-checkbox .checkmark:after { left: 7px; top: 3px; width: 6px; height: 12px; border: solid #1a1a1a; border-width: 0 3px 3px 0; transform: rotate(45deg); }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 30px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); animation: modalSlide 0.3s ease; }
        @keyframes modalSlide { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #FFD700; }
        .modal-header h2 { color: #1a1a1a; font-size: 22px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .modal-header h2 i { color: #FFD700; }
        .btn-close { background: none; border: none; font-size: 24px; color: #6c757d; cursor: pointer; transition: color 0.2s ease; }
        .btn-close:hover { color: #dc3545; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #1a1a1a; font-weight: 500; font-size: 14px; }
        .form-group label i { color: #FFD700; margin-right: 5px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px; transition: border-color 0.3s ease; }
        .form-control:focus { outline: none; border-color: #FFD700; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
        .empty-state i { font-size: 48px; margin-bottom: 20px; color: #FFD700; opacity: 0.5; }

        @media (max-width: 768px) {
            .header-section { flex-direction: column; gap: 20px; align-items: stretch; }
            .actions-group { justify-content: space-between; }
            .btn { flex: 1; justify-content: center; }
            table { font-size: 12px; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <div class="title-box">
            <h1><i class="fas fa-clipboard-check"></i> PTL - Almacén</h1>
            <p>Evaluación de Patrón Técnico Logístico</p>
        </div>
        
        <div class="general-score">
            <div class="score-label">Cumplimiento General</div>
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
                <i class="fas fa-layer-group"></i> Nueva Sub-Área
            </button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;"><i class="fas fa-sitemap"></i> Área / Sub-área</th>
                    <th><i class="fas fa-tags"></i> Rotulación</th>
                    <th><i class="fas fa-cubes"></i> Staking</th>
                    <th><i class="fas fa-boxes"></i> Nivel Alm.</th>
                    <th><i class="fas fa-check-double"></i> Adherencia ABC</th>
                    <th style="width: 15%;"><i class="fas fa-chart-line"></i> Cumplimiento</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($areas)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-warehouse"></i>
                                <p>No hay áreas creadas.</p>
                                <small>Comienza creando tu primera área (Ej: Bodega B o Carpas).</small>
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
                                        <i class="fas fa-warehouse" style="margin-right: 5px;"></i> <?php echo htmlspecialchars($area['nombre']); ?>
                                    </div>
                                    <div class="crud-group">
                                        <button class="btn-crud btn-edit" onclick="editarArea(event, <?php echo $area['id']; ?>, '<?php echo htmlspecialchars(addslashes($area['nombre'])); ?>')" title="Editar Área">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-crud btn-delete" onclick="eliminarRegistro(event, <?php echo $area['id']; ?>, 'area')" title="Eliminar Área">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="check-cell">
                                <label class="custom-checkbox" onclick="event.stopPropagation();">
                                    <input type="checkbox" id="area-rotulacion-<?php echo $area['id']; ?>" <?php echo $area['rotulacion'] ? 'checked' : ''; ?> 
                                           onchange="updatePTL(<?php echo $area['id']; ?>, 'area', 'rotulacion', this.checked)">
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            <td class="check-cell">
                                <label class="custom-checkbox" onclick="event.stopPropagation();">
                                    <input type="checkbox" id="area-staking-<?php echo $area['id']; ?>" <?php echo $area['staking'] ? 'checked' : ''; ?>
                                           onchange="updatePTL(<?php echo $area['id']; ?>, 'area', 'staking', this.checked)">
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            <td class="check-cell">
                                <label class="custom-checkbox" onclick="event.stopPropagation();">
                                    <input type="checkbox" id="area-nivel_almacenamiento-<?php echo $area['id']; ?>" <?php echo $area['nivel_almacenamiento'] ? 'checked' : ''; ?>
                                           onchange="updatePTL(<?php echo $area['id']; ?>, 'area', 'nivel_almacenamiento', this.checked)">
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            <td class="check-cell">
                                <label class="custom-checkbox" onclick="event.stopPropagation();">
                                    <input type="checkbox" id="area-adherencia_abc-<?php echo $area['id']; ?>" <?php echo $area['adherencia_abc'] ? 'checked' : ''; ?>
                                           onchange="updatePTL(<?php echo $area['id']; ?>, 'area', 'adherencia_abc', this.checked)">
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            
                            <td>
                                <span class="badge badge-yellow" id="badge-area-<?php echo $area['id']; ?>">
                                    <?php echo round($totales_area[$area['id']], 2); ?>%
                                </span>
                            </td>
                        </tr>

                        <?php if(!empty($subareas_por_area[$area['id']])): ?>
                            <?php foreach($subareas_por_area[$area['id']] as $sub): 
                                $sub_pct = calcularPorcentajeFila($sub);
                            ?>
                                <tr class="subarea-row sub-row-<?php echo $area['id']; ?>" style="display: none;">
                                    <td>
                                        <div class="subarea-name-cell">
                                            <div class="subarea-name">
                                                <i class="fas fa-level-up-alt fa-rotate-90" style="margin-right: 5px; color:#ccc;"></i> 
                                                <?php echo htmlspecialchars($sub['nombre']); ?>
                                            </div>
                                            <div class="crud-group">
                                                <button class="btn-crud btn-edit" onclick="editarSubarea(event, <?php echo $sub['id']; ?>, <?php echo $area['id']; ?>, '<?php echo htmlspecialchars(addslashes($sub['nombre'])); ?>')" title="Editar Sub-área">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-crud btn-delete" onclick="eliminarRegistro(event, <?php echo $sub['id']; ?>, 'subarea')" title="Eliminar Sub-área">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="check-cell">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" class="sub-rotulacion-<?php echo $area['id']; ?>" id="sub-rotulacion-<?php echo $sub['id']; ?>" <?php echo $sub['rotulacion'] ? 'checked' : ''; ?> 
                                                   onchange="updatePTL(<?php echo $sub['id']; ?>, 'subarea', 'rotulacion', this.checked)">
                                            <span class="checkmark"></span>
                                        </label>
                                    </td>
                                    <td class="check-cell">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" class="sub-staking-<?php echo $area['id']; ?>" id="sub-staking-<?php echo $sub['id']; ?>" <?php echo $sub['staking'] ? 'checked' : ''; ?>
                                                   onchange="updatePTL(<?php echo $sub['id']; ?>, 'subarea', 'staking', this.checked)">
                                            <span class="checkmark"></span>
                                </label>
                                    </td>
                                    <td class="check-cell">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" class="sub-nivel_almacenamiento-<?php echo $area['id']; ?>" id="sub-nivel_almacenamiento-<?php echo $sub['id']; ?>" <?php echo $sub['nivel_almacenamiento'] ? 'checked' : ''; ?>
                                                   onchange="updatePTL(<?php echo $sub['id']; ?>, 'subarea', 'nivel_almacenamiento', this.checked)">
                                            <span class="checkmark"></span>
                                        </label>
                                    </td>
                                    <td class="check-cell">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" class="sub-adherencia_abc-<?php echo $area['id']; ?>" id="sub-adherencia_abc-<?php echo $sub['id']; ?>" <?php echo $sub['adherencia_abc'] ? 'checked' : ''; ?>
                                                   onchange="updatePTL(<?php echo $sub['id']; ?>, 'subarea', 'adherencia_abc', this.checked)">
                                            <span class="checkmark"></span>
                                        </label>
                                    </td>
                                    
                                    <td>
                                        <span class="badge badge-dark" id="badge-sub-<?php echo $sub['id']; ?>">
                                            <?php echo round($sub_pct, 2); ?>%
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
            <h2 id="modalAreaTitle"><i class="fas fa-folder-plus"></i> Nueva Área</h2>
            <button class="btn-close" type="button" onclick="cerrarModal('modalArea')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="guardar_area">
            <input type="hidden" name="area_id_edit" id="area_id_edit" value="0">
            <div class="form-group">
                <label><i class="fas fa-font"></i> Nombre del Área (Ej: Bodega B o Carpa)</label>
                <input type="text" name="nombre_area" id="nombre_area" required placeholder="Ingresa el nombre del área..." class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" onclick="cerrarModal('modalArea')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modalSubarea">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalSubareaTitle"><i class="fas fa-layer-group"></i> Nueva Sub-Área</h2>
            <button class="btn-close" type="button" onclick="cerrarModal('modalSubarea')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="guardar_subarea">
            <input type="hidden" name="subarea_id_edit" id="subarea_id_edit" value="0">
            <div class="form-group">
                <label><i class="fas fa-warehouse"></i> Selecciona el Área Padre</label>
                <select name="area_id" id="select_area_id" required class="form-control">
                    <option value="">-- Seleccione un área --</option>
                    <?php foreach($areas as $area): ?>
                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Nombre de la Sub-área (Ej: Pasillo B1)</label>
                <input type="text" name="nombre_subarea" id="nombre_subarea" required placeholder="Ingresa el nombre..." class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" onclick="cerrarModal('modalSubarea')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSubareas(areaId) {
    const rows = document.querySelectorAll('.sub-row-' + areaId);
    const icon = document.getElementById('icon-' + areaId);
    if(rows.length === 0) return;

    let isHidden = rows[0].style.display === 'none';
    rows.forEach(row => row.style.display = isHidden ? 'table-row' : 'none');
    
    if(isHidden) icon.classList.add('open');
    else icon.classList.remove('open');
}

function updatePTL(id, tipo, campo, valorChecked) {
    const formData = new FormData();
    formData.append('ajax_action', 'update_ptl');
    formData.append('id', id);
    formData.append('tipo', tipo);
    formData.append('campo', campo);
    formData.append('valor', valorChecked);

    if(tipo === 'area') {
        const subCheckboxes = document.querySelectorAll('.sub-' + campo + '-' + id);
        subCheckboxes.forEach(chk => {
            chk.checked = valorChecked;
        });
    }

    fetch('ptl.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            
            if(tipo === 'area') {
                setTimeout(() => location.reload(), 300);
            } else {
                document.getElementById('badge-sub-' + id).innerText = data.sub_pct + '%';
                document.getElementById('badge-area-' + data.area_id).innerText = data.area_pct + '%';
                document.getElementById('generalScoreText').innerText = data.general_pct + '%';
                document.getElementById('generalScoreBar').style.width = data.general_pct + '%';
            }
        } else {
            Swal.fire('Error', 'No se pudo guardar la información.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Problema de conexión.', 'error');
    });
}

function abrirModalArea() {
    document.getElementById('modalAreaTitle').innerHTML = '<i class="fas fa-folder-plus"></i> Nueva Área';
    document.getElementById('area_id_edit').value = '0';
    document.getElementById('nombre_area').value = '';
    document.getElementById('modalArea').classList.add('active');
}

function editarArea(event, id, nombre) {
    event.stopPropagation(); 
    document.getElementById('modalAreaTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Área';
    document.getElementById('area_id_edit').value = id;
    document.getElementById('nombre_area').value = nombre;
    document.getElementById('modalArea').classList.add('active');
}

function abrirModalSubarea() {
    document.getElementById('modalSubareaTitle').innerHTML = '<i class="fas fa-layer-group"></i> Nueva Sub-Área';
    document.getElementById('subarea_id_edit').value = '0';
    document.getElementById('select_area_id').value = '';
    document.getElementById('nombre_subarea').value = '';
    document.getElementById('modalSubarea').classList.add('active');
}

function editarSubarea(event, id, area_id, nombre) {
    event.stopPropagation();
    document.getElementById('modalSubareaTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Sub-Área';
    document.getElementById('subarea_id_edit').value = id;
    document.getElementById('select_area_id').value = area_id;
    document.getElementById('nombre_subarea').value = nombre;
    document.getElementById('modalSubarea').classList.add('active');
}

function eliminarRegistro(event, id, tipo) {
    event.stopPropagation();
    const texto = tipo === 'area' ? '¡Esto eliminará también todas sus sub-áreas!' : '¡Esta acción no se puede deshacer!';
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_action', 'eliminar');
            formData.append('id', id);
            formData.append('tipo', tipo);

            fetch('ptl.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', 'No se pudo eliminar', 'error');
                }
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
</script>
</body>
</html>