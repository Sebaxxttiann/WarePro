<?php

date_default_timezone_set('America/Bogota');

require_once '../../core/config.php';

verificarLogin();
require_once '../../vendor/autoload.php';

$hoy = date('Y-m-d');




if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'upload_excel') {
    header('Content-Type: application/json');
    
    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Error al subir el archivo al servidor.']);
        exit;
    }

    $file = $_FILES['file_excel']['tmp_name'];
    $skus_data = [];

    try {
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $header_skipped = false;
        foreach ($rows as $index => $row) {
            if (!$header_skipped) {
                $header_skipped = true;
                continue; 
            }

            
            if (isset($row[1]) && isset($row[3])) {
                $sku = trim((string)$row[1]);
                $fecha_raw = $row[3];
                
                if ($sku === '' || $fecha_raw === null || $fecha_raw === '') continue;

                $fecha_formateada = null;

                
                if (is_numeric($fecha_raw)) {
                    $date_obj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha_raw);
                    $fecha_formateada = $date_obj->format('Y-m-d');
                } else {
                    
                    $fecha_raw_str = trim((string)$fecha_raw);
                    $date_obj = DateTime::createFromFormat('d/m/Y', $fecha_raw_str);
                    if (!$date_obj) {
                        $date_obj = DateTime::createFromFormat('d/m/y', $fecha_raw_str);
                    }
                    if (!$date_obj) {
                        $date_obj = DateTime::createFromFormat('Y-m-d', $fecha_raw_str);
                    }
                    
                    if ($date_obj) {
                        $fecha_formateada = $date_obj->format('Y-m-d');
                    }
                }

                
                if ($fecha_formateada) {
                    if (!isset($skus_data[$sku])) {
                        $skus_data[$sku] = $fecha_formateada;
                    } else {
                        if (strtotime($fecha_formateada) < strtotime($skus_data[$sku])) {
                            $skus_data[$sku] = $fecha_formateada;
                        }
                    }
                }
            }
        }

        
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO picking_skus_archivo (sku, fecha_vencimiento) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE fecha_vencimiento = VALUES(fecha_vencimiento)
        ");
        
        $registros_procesados = 0;
        foreach ($skus_data as $sku => $fecha) {
            $stmt->execute([$sku, $fecha]);
            $registros_procesados++;
        }
        $pdo->commit();
        
        echo json_encode(['success' => true, 'mensaje' => "Base de datos actualizada. Se procesaron $registros_procesados SKUs únicos."]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Error al leer el Excel: ' . $e->getMessage()]);
    }
    exit;
}


if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'evaluar_posicion') {
    header('Content-Type: application/json');
    $posicion_id = $_POST['posicion_id'];
    $sku_ingresado = trim($_POST['sku']);
    $fecha_producto = $_POST['fecha_producto'];
    $fecha_actual = date('Y-m-d H:i:s');

    try {
        
        $stmt_buscar = $pdo->prepare("SELECT fecha_vencimiento FROM picking_skus_archivo WHERE sku = ?");
        $stmt_buscar->execute([$sku_ingresado]);
        $fecha_archivo = $stmt_buscar->fetchColumn();

        if (!$fecha_archivo) {
            echo json_encode(['success' => false, 'error' => 'El SKU ingresado no se encuentra en el archivo base.']);
            exit;
        }

        
        $date1 = new DateTime($fecha_producto);
        $date2 = new DateTime($fecha_archivo);
        $diferencia_dias = abs($date1->diff($date2)->days);
        
        $cumple = ($diferencia_dias >= 15) ? 0 : 1;

        
        $stmt = $pdo->prepare("
            INSERT INTO picking_registros (posicion_id, fecha_dia, sku, fecha_producto, fecha_archivo, cumple, evaluado_en, operacion_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            sku = VALUES(sku),
            fecha_producto = VALUES(fecha_producto),
            fecha_archivo = VALUES(fecha_archivo),
            cumple = VALUES(cumple),
            evaluado_en = VALUES(evaluado_en)
        ");
        $stmt->execute([$posicion_id, $hoy, $sku_ingresado, $fecha_producto, $fecha_archivo, $cumple, $fecha_actual, getOperacionActiva()]);


        $stmt_total = $pdo->prepare("SELECT COUNT(*) as total, SUM(cumple) as exitos FROM picking_registros WHERE fecha_dia = ? AND operacion_id = ?");
        $stmt_total->execute([$hoy, getOperacionActiva()]);
        $stats = $stmt_total->fetch();
        
        $porcentaje_general = ($stats['total'] > 0) ? ($stats['exitos'] / $stats['total']) * 100 : 0;

        echo json_encode([
            'success' => true,
            'cumple' => $cumple,
            'fecha_archivo' => $fecha_archivo,
            'hora_evaluacion' => date('H:i', strtotime($fecha_actual)),
            'porcentaje_general' => round($porcentaje_general, 2)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
    exit;
}


if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'eliminar_posicion') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("DELETE FROM picking_posiciones WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$_POST['id'], getOperacionActiva()]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar la posición.']);
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar_posicion') {
    $nombre = htmlspecialchars(strip_tags(trim($_POST['nombre_posicion'])));
    $id = isset($_POST['posicion_id_edit']) ? intval($_POST['posicion_id_edit']) : 0;
    
    if (!empty($nombre)) {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE picking_posiciones SET nombre = ? WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$nombre, $id, getOperacionActiva()]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO picking_posiciones (nombre, operacion_id) VALUES (?, ?)");
            $stmt->execute([$nombre, getOperacionActiva()]);
        }
    }
    header("Location: picking.php");
    exit;
}


$stmt_posiciones = $pdo->prepare("
    SELECT p.id, p.nombre,
           r.sku, r.fecha_producto, r.fecha_archivo, r.cumple, r.evaluado_en
    FROM picking_posiciones p
    LEFT JOIN picking_registros r ON p.id = r.posicion_id AND r.fecha_dia = ? AND r.operacion_id = ?
    WHERE p.operacion_id = ?
    ORDER BY CAST(REGEXP_SUBSTR(p.nombre, '[0-9]+') AS UNSIGNED), p.nombre ASC
");
$stmt_posiciones->execute([$hoy, getOperacionActiva(), getOperacionActiva()]);
$posiciones = $stmt_posiciones->fetchAll();

$stmt_total = $pdo->prepare("SELECT COUNT(*) as total, SUM(cumple) as exitos FROM picking_registros WHERE fecha_dia = ? AND operacion_id = ?");
$stmt_total->execute([$hoy, getOperacionActiva()]);
$stats = $stmt_total->fetch();
$cumplimiento_general = ($stats['total'] > 0) ? ($stats['exitos'] / $stats['total']) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Picking - Evaluación de SKUs</title>
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
        
        .actions-group { display: flex; gap: 15px; }
        .btn { border: none; padding: 10px 20px; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; }
        .btn-primary { background: #FFD700; color: #1a1a1a; }
        .btn-primary:hover { box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4); }
        .btn-dark { background: #1a1a1a; color: #FFD700; }
        .btn-dark:hover { background: #2d2d2d; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        
        .table-container { width: 100%; overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        thead { background: #1a1a1a; color: white; }
        th { padding: 15px; text-align: center; font-weight: 500; font-size: 14px; }
        th:first-child { text-align: left; }
        th i { color: #FFD700; margin-right: 5px; }
        td { padding: 15px; vertical-align: middle; text-align: center; border-bottom: 1px solid #f1f1f1; }
        td:first-child { text-align: left; font-weight: 500; }
        
        .posicion-row { background: #ffffff; transition: background 0.2s; }
        .posicion-row:hover { background: #f8f9fa; }
        .posicion-name-cell { display: flex; flex-direction: column; gap: 5px; }
        .posicion-header { display: flex; align-items: center; justify-content: space-between; }
        .eval-date { font-size: 11px; color: #6c757d; display: flex; align-items: center; gap: 4px; }
        
        .badge { display: inline-block; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-dark { background: #e9ecef; color: #1a1a1a; }
        .badge-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .btn-crud { background: none; border: none; cursor: pointer; padding: 5px; font-size: 14px; margin-left: 5px; }
        .btn-edit { color: #007bff; }
        .btn-delete { color: #dc3545; }

        .form-control { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-family: inherit; font-size: 13px; text-align: center; }
        .form-control:focus { border-color: #FFD700; outline: none; box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25); }
        .input-disabled { background-color: #e9ecef; color: #6c757d; cursor: not-allowed; }
        
        .input-wrapper { display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .mobile-label { display: none; font-size: 11px; color: #6c757d; font-weight: 500; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 30px; width: 90%; max-width: 450px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; }
        .btn-close { background: none; border: none; font-size: 24px; color: #6c757d; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; text-align: left; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #6c757d; }

        
        @media (max-width: 900px) {
            .header-section { flex-direction: column; align-items: stretch; text-align: center; }
            .actions-group { flex-direction: column; }
            .btn { width: 100%; }
            
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            
            .posicion-row { margin-bottom: 20px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            .posicion-row td { padding: 10px 0; text-align: left; border: none; border-bottom: 1px solid #f8f9fa; }
            .posicion-row td:last-child { border-bottom: none; }
            
            .input-wrapper { flex-direction: row; justify-content: space-between; align-items: center; width: 100%; }
            .mobile-label { display: block; width: 40%; font-size: 13px; color: #495057; }
            .form-control { max-width: 55%; text-align: right; }
            
            .badge-container { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; }
        }
    </style>
</head>
<body>
<?php require_once '../../core/header.php'; ?>

<div class="container">
    <div class="header-section">
        <div class="title-box">
            <h1><i class="fas fa-barcode"></i> Control de Picking</h1>
            <p>Evaluación diaria de SKUs vs Base de Datos Maestra</p>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> Evaluando: <?php echo date('d/m/Y'); ?></div>
        </div>
        
        <div class="general-score">
            <div class="score-label">Cumplimiento Global (Día)</div>
            <div class="score-value" id="generalScoreText"><?php echo round($cumplimiento_general, 2); ?>%</div>
        </div>

        <div class="actions-group">
            <button class="btn btn-success" onclick="abrirModalArchivo()">
                <i class="fas fa-file-excel"></i> Cargar Archivo Base
            </button>
            <button class="btn btn-dark" onclick="abrirModalPosicion()">
                <i class="fas fa-plus-circle"></i> Nueva Posición
            </button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Posición</th>
                    <th style="width: 15%;">SKU (Buscador)</th>
                    <th style="width: 18%;">Fecha del Producto</th>
                    <th style="width: 18%;">Fecha del Archivo</th>
                    <th style="width: 12%;">Acción</th>
                    <th style="width: 17%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($posiciones)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-map-marker-alt fa-3x" style="color: #FFD700; opacity: 0.5; margin-bottom:15px;"></i>
                                <h3>Sin posiciones registradas</h3>
                                <p>Crea tu primera posición o inserta la consulta SQL sugerida para comenzar.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($posiciones as $pos): 
                        $evalDate = $pos['evaluado_en'] ? date('H:i', strtotime($pos['evaluado_en'])) : 'Pendiente';
                        $estadoTexto = "Sin Evaluar";
                        $badgeClass = "badge-dark";
                        
                        if ($pos['evaluado_en']) {
                            $estadoTexto = ($pos['cumple']) ? "Cumple" : "No Cumple";
                            $badgeClass = ($pos['cumple']) ? "badge-success" : "badge-danger";
                        }
                    ?>
                        <tr class="posicion-row">
                            <td>
                                <div class="posicion-name-cell">
                                    <div class="posicion-header">
                                        <div><i class="fas fa-thumbtack" style="color: #ccc; margin-right:5px;"></i> <?php echo htmlspecialchars($pos['nombre']); ?></div>
                                        <div>
                                            <button class="btn-crud btn-edit" onclick="editarPosicion(<?php echo $pos['id']; ?>, '<?php echo htmlspecialchars(addslashes($pos['nombre'])); ?>')"><i class="fas fa-edit"></i></button>
                                            <button class="btn-crud btn-delete" onclick="eliminarPosicion(<?php echo $pos['id']; ?>)"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <div class="eval-date"><i class="far fa-clock"></i> Eval: <span id="eval-date-<?php echo $pos['id']; ?>"><?php echo $evalDate; ?></span></div>
                                </div>
                            </td>
                            <td>
                                <div class="input-wrapper">
                                    <span class="mobile-label">SKU:</span>
                                    <input type="text" class="form-control" id="sku-<?php echo $pos['id']; ?>" value="<?php echo htmlspecialchars($pos['sku'] ?? ''); ?>" placeholder="Ej: 17667">
                                </div>
                            </td>
                            <td>
                                <div class="input-wrapper">
                                    <span class="mobile-label">F. Producto:</span>
                                    <input type="text" class="form-control flatpickr-input" id="fecha-prod-<?php echo $pos['id']; ?>" value="<?php echo htmlspecialchars($pos['fecha_producto'] ?? ''); ?>" placeholder="Seleccionar...">
                                </div>
                            </td>
                            <td>
                                <div class="input-wrapper">
                                    <span class="mobile-label">F. Archivo:</span>
                                    <input type="text" class="form-control input-disabled" id="fecha-arch-<?php echo $pos['id']; ?>" value="<?php echo htmlspecialchars($pos['fecha_archivo'] ?? '***'); ?>" readonly disabled>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-dark" style="width:100%; padding: 8px;" onclick="guardarEvaluacion(<?php echo $pos['id']; ?>)">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                            </td>
                            <td class="badge-container">
                                <span class="mobile-label" style="display:none;">Estado:</span>
                                <span class="badge <?php echo $badgeClass; ?>" id="badge-estado-<?php echo $pos['id']; ?>"><?php echo $estadoTexto; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="modalPosicion">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalPosTitle">Nueva Posición</h2>
            <button class="btn-close" type="button" onclick="cerrarModal('modalPosicion')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="guardar_posicion">
            <input type="hidden" name="posicion_id_edit" id="posicion_id_edit" value="0">
            <div class="form-group">
                <label>Nombre de la Posición</label>
                <input type="text" name="nombre_posicion" id="nombre_posicion" required class="form-control" placeholder="Ej: Posición 1...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" onclick="cerrarModal('modalPosicion')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modalArchivo">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Cargar Base de Datos</h2>
            <button class="btn-close" type="button" onclick="cerrarModal('modalArchivo')">&times;</button>
        </div>
        <div class="form-group">
            <label>Selecciona tu archivo de Excel (.xlsx, .xls o .csv)</label>
            <input type="file" id="file_excel" accept=".xlsx, .xls, .csv" class="form-control" style="padding: 6px; text-align:left;">
            <small style="color:#6c757d; font-size:12px; margin-top:5px; display:block; text-align:left;">El sistema leerá directamente la Columna B (SKU) y la Columna D (Fecha de Vencimiento).</small>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-dark" onclick="cerrarModal('modalArchivo')">Cancelar</button>
            <button type="button" class="btn btn-success" onclick="procesarArchivo()">Subir y Procesar</button>
        </div>
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
});

function abrirModalPosicion() {
    document.getElementById('modalPosTitle').innerText = 'Nueva Posición';
    document.getElementById('posicion_id_edit').value = '0';
    document.getElementById('nombre_posicion').value = '';
    document.getElementById('modalPosicion').classList.add('active');
}

function editarPosicion(id, nombre) {
    document.getElementById('modalPosTitle').innerText = 'Editar Posición';
    document.getElementById('posicion_id_edit').value = id;
    document.getElementById('nombre_posicion').value = nombre;
    document.getElementById('modalPosicion').classList.add('active');
}

function abrirModalArchivo() {
    document.getElementById('file_excel').value = '';
    document.getElementById('modalArchivo').classList.add('active');
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('active');
}

function eliminarPosicion(id) {
    Swal.fire({
        title: '¿Eliminar posición?',
        text: 'Se borrará el historial diario vinculado a esta posición.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('ajax_action', 'eliminar_posicion');
            fd.append('id', id);

            fetch('picking.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else Swal.fire('Error', data.error, 'error');
            });
        }
    });
}

function procesarArchivo() {
    const fileInput = document.getElementById('file_excel');
    if (fileInput.files.length === 0) {
        Swal.fire('Atención', 'Debes seleccionar un archivo.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Procesando...',
        text: 'Leyendo el archivo Excel y actualizando datos. Por favor, espera.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    const fd = new FormData();
    fd.append('ajax_action', 'upload_excel');
    fd.append('file_excel', fileInput.files[0]);

    fetch('picking.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire('¡Éxito!', data.mensaje, 'success').then(() => cerrarModal('modalArchivo'));
        } else {
            Swal.fire('Error en el archivo', data.error, 'error');
        }
    }).catch(() => {
        Swal.fire('Error', 'Fallo en la conexión al subir el archivo.', 'error');
    });
}

function guardarEvaluacion(posicion_id) {
    const sku = document.getElementById('sku-' + posicion_id).value.trim();
    const fecha_producto = document.getElementById('fecha-prod-' + posicion_id).value;

    if (!sku || !fecha_producto) {
        Swal.fire('Atención', 'Debes ingresar el SKU y la Fecha del Producto.', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append('ajax_action', 'evaluar_posicion');
    fd.append('posicion_id', posicion_id);
    fd.append('sku', sku);
    fd.append('fecha_producto', fecha_producto);

    fetch('picking.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById('fecha-arch-' + posicion_id).value = data.fecha_archivo;
            document.getElementById('eval-date-' + posicion_id).innerText = data.hora_evaluacion;
            
            const badge = document.getElementById('badge-estado-' + posicion_id);
            const esCumple = data.cumple == 1;
            
            badge.innerText = esCumple ? 'Cumple' : 'No Cumple';
            badge.className = 'badge ' + (esCumple ? 'badge-success' : 'badge-danger');
            
            document.getElementById('generalScoreText').innerText = data.porcentaje_general + '%';

            Swal.fire({
                title: esCumple ? '¡Cumple!' : 'No Cumple',
                text: esCumple ? 'Fechas dentro del rango permitido.' : 'Desviación igual o superior a 15 días.',
                icon: esCumple ? 'success' : 'error',
                confirmButtonColor: '#1a1a1a'
            });
        } else {
            Swal.fire('Atención', data.error, 'warning');
            document.getElementById('fecha-arch-' + posicion_id).value = '***';
        }
    }).catch(() => Swal.fire('Error', 'Fallo de conexión.', 'error'));
}
</script>
</body>
</html>