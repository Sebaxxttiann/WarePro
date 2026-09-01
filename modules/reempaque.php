<?php
date_default_timezone_set('America/Bogota');

require_once '../core/config.php';
verificarLogin();
require_once '../core/header.php';

$alert_message = '';
$alert_type = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'guardar_reempaque') {
            try {
                $pdo->beginTransaction();
                
                $total_unidades = 0;
                $total_horas_trabajadas = 0;
                
                foreach ($_POST['productos'] as $producto) {
                    $total_unidades += intval($producto['unidades']);
                    
                    $fecha_base = $_POST['fecha'];
                    $inicio = new DateTime($fecha_base . ' ' . $producto['hora_inicio'], new DateTimeZone('America/Bogota'));
                    $fin = new DateTime($fecha_base . ' ' . $producto['hora_fin'], new DateTimeZone('America/Bogota'));
                    
                    if ($fin < $inicio) {
                        $fin->modify('+1 day');
                    }
                    
                    $diferencia = $inicio->diff($fin);
                    $horas = $diferencia->h + ($diferencia->i / 60);
                    $total_horas_trabajadas += $horas;
                }
                
                $cumplimiento_general = $total_horas_trabajadas > 0 ? $total_unidades / $total_horas_trabajadas : 0;
                
                $stmt = $pdo->prepare("SELECT meta_minima, disparador FROM metas WHERE actividad = ?");
                $stmt->execute([$_POST['actividad']]);
                $meta = $stmt->fetch();
                
                $cumple_meta = $cumplimiento_general >= $meta['meta_minima'] ? 1 : 0;
                $estado_ciclo = $cumple_meta ? 'completo' : 'pendiente';
                
                $grupo_registro = uniqid('reg_');
                $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
                
                foreach ($_POST['productos'] as $producto) {
                    $fecha_base = $_POST['fecha'];
                    $hora_inicio = new DateTime($fecha_base . ' ' . $producto['hora_inicio'], new DateTimeZone('America/Bogota'));
                    $hora_fin = new DateTime($fecha_base . ' ' . $producto['hora_fin'], new DateTimeZone('America/Bogota'));
                    
                    if ($hora_fin < $hora_inicio) {
                        $hora_fin->modify('+1 day');
                    }
                    
                    $diferencia = $hora_inicio->diff($hora_fin);
                    $horas = $diferencia->h + ($diferencia->i / 60);
                    $cumplimiento_individual = $horas > 0 ? intval($producto['unidades']) / $horas : 0;
                    
                    $stmt = $pdo->prepare("SELECT id_material, material FROM productos WHERE id = ?");
                    $stmt->execute([$producto['sku']]);
                    $prod_data = $stmt->fetch();
                    
                    $stmt = $pdo->prepare("INSERT INTO reempaque1 (fecha, auxiliar_id, nombre, actividad, turno, producto_id, sku, producto_nombre, unidades, hora_inicio, hora_fin, horas_trabajadas, cumplimiento_individual, cumplimiento_general, cumple_meta, estado_ciclo, observaciones, grupo_registro, operacion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['fecha'],
                        $_SESSION['usuario_id'],
                        $_SESSION['nombre'],
                        $_POST['actividad'],
                        $_POST['turno'],
                        $producto['sku'],
                        $prod_data['id_material'],
                        $prod_data['material'],
                        $producto['unidades'],
                        $producto['hora_inicio'],
                        $producto['hora_fin'],
                        $horas,
                        $cumplimiento_individual,
                        $cumplimiento_general,
                        $cumple_meta,
                        $estado_ciclo,
                        $observaciones,
                        $grupo_registro,
                        getOperacionActiva()
                    ]);
                }
                
                $pdo->commit();
                
                if (!$cumple_meta) {
                    $alert_type = 'warning';
                    $alert_message = json_encode([
                        'title' => 'Atención ' . $_SESSION['nombre'],
                        'html' => '<div style="text-align: left;"><strong>No cumpliste con la meta de ' . $_POST['actividad'] . '</strong><br><br>' .
                                  'Tu cumplimiento: <strong>' . number_format($cumplimiento_general, 2) . ' unidades/hora</strong><br>' .
                                  'Meta requerida: <strong>' . $meta['meta_minima'] . ' unidades/hora</strong><br><br>' .
                                  '<strong style="color: #e74c3c;">IMPORTANTE:</strong> Debes cargar la evidencia del <strong>5 Por Qué</strong> para que tu ciclo esté completo.<br>' .
                                  'Si no lo haces, tu ciclo quedará <strong>INCOMPLETO</strong>.</div>',
                        'icon' => 'warning',
                        'showCancelButton' => true,
                        'confirmButtonText' => 'Ir a 5 Por Qué',
                        'cancelButtonText' => 'Cargaré después',
                        'confirmButtonColor' => '#FFD700',
                        'cancelButtonColor' => '#666'
                    ]);
                } else {
                    $alert_type = 'success';
                    $alert_message = json_encode([
                        'title' => 'Éxito',
                        'text' => 'Productividad registrada correctamente. Felicidades por cumplir la meta! Ciclo completado.',
                        'icon' => 'success',
                        'confirmButtonColor' => '#FFD700'
                    ]);
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $alert_type = 'error';
                $alert_message = json_encode([
                    'title' => 'Error',
                    'text' => 'Error al guardar los datos: ' . $e->getMessage(),
                    'icon' => 'error',
                    'confirmButtonColor' => '#FFD700'
                ]);
            }
        }
        
        if ($_POST['action'] == 'subir_evidencia') {
            try {
                if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    $max_size = 5 * 1024 * 1024;
                    
                    $file_type = mime_content_type($_FILES['evidencia']['tmp_name']);
                    $file_size = $_FILES['evidencia']['size'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception('Solo se permiten imágenes (JPG, PNG, GIF)');
                    }
                    
                    if ($file_size > $max_size) {
                        throw new Exception('El archivo es demasiado grande (máximo 5MB)');
                    }
                    
                    $upload_dir = '../uploads/evidencias/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['evidencia']['name'], PATHINFO_EXTENSION);
                    $file_name = 'evidencia_' . $_POST['grupo_registro'] . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $file_path)) {
                        $stmt = $pdo->prepare("UPDATE reempaque1 SET evidencia_5_porque = ?, estado_ciclo = 'completo' WHERE grupo_registro = ? AND operacion_id = ?");
                        $stmt->execute([$file_path, $_POST['grupo_registro'], getOperacionActiva()]);
                        
                        $alert_type = 'success';
                        $alert_message = json_encode([
                            'title' => 'Ciclo Completo',
                            'text' => 'Evidencia subida correctamente. El ciclo ha sido cerrado.',
                            'icon' => 'success',
                            'confirmButtonColor' => '#FFD700'
                        ]);
                    }
                }
            } catch (Exception $e) {
                $alert_type = 'error';
                $alert_message = json_encode([
                    'title' => 'Error',
                    'text' => 'Error al subir la evidencia: ' . $e->getMessage(),
                    'icon' => 'error',
                    'confirmButtonColor' => '#FFD700'
                ]);
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM productos ORDER BY material");
$productos = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        grupo_registro,
        fecha,
        actividad,
        turno,
        cumplimiento_general,
        cumple_meta,
        estado_ciclo,
        nombre,
        GROUP_CONCAT(CONCAT(sku, ' - ', producto_nombre) SEPARATOR ', ') as productos,
        MIN(fecha_creacion) as fecha_creacion
    FROM reempaque1
    WHERE operacion_id = ?
    GROUP BY grupo_registro
    ORDER BY fecha_creacion DESC
");
$stmt->execute([getOperacionActiva()]);
$registros = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT actividad, meta_minima, disparador FROM metas WHERE actividad IN ('Clasificación', 'Lavado', 'Reempaque') AND operacion_id = ?");
$stmt->execute([getOperacionActiva()]);
$metas = [];
while ($row = $stmt->fetch()) {
    $metas[$row['actividad']] = ['meta' => $row['meta_minima'], 'disparador' => $row['disparador']];
}
$user_cargo = $_SESSION['cargo'] ?? 'operador';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productividad - Ware-Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border-left: 5px solid #FFD700;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 180px;
            height: 180px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.08), rgba(255, 165, 0, 0.04));
            border-radius: 50%;
            transform: translate(40%, -40%);
        }

        .page-header h1 {
            color: #1a1a1a;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 2;
        }

        .page-header h1 i {
            color: #FFD700;
            font-size: 2rem;
        }

        .page-header p {
            color: #666;
            font-size: 1rem;
            font-weight: 400;
            position: relative;
            z-index: 2;
        }

        .main-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
            border: none;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.35);
        }

        .records-table {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
        }

        .dataTables_wrapper {
            padding: 0;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .dataTables_wrapper .dataTables_length select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        table.dataTable {
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
        }

        table.dataTable thead th {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            color: white;
            padding: 1.2rem 1rem;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: left;
            border: none;
        }

        table.dataTable thead th:first-child {
            border-radius: 10px 0 0 0;
        }

        table.dataTable thead th:last-child {
            border-radius: 0 10px 0 0;
        }

        table.dataTable tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        table.dataTable tbody tr:hover {
            background: #f8f9fa;
        }

        table.dataTable tbody td {
            padding: 1.2rem 1rem;
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .cumplimiento-badge {
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 600;
            text-align: center;
            font-size: 0.85rem;
            white-space: nowrap;
            display: inline-block;
        }

        .cumple-meta {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #b8dabd;
        }

        .no-cumple-meta {
            background: linear-gradient(135deg, #f8d7da, #f1aeb5);
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .disparador {
            background: linear-gradient(135deg, #f8d7da, #f1aeb5);
            color: #721c24;
            border: 2px solid #007bff;
        }

        .estado-badge {
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 600;
            text-align: center;
            font-size: 0.85rem;
            white-space: nowrap;
            display: inline-block;
        }

        .estado-completo {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .estado-pendiente {
            background: linear-gradient(135deg, #fff3cd, #fdeaa7);
            color: #856404;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-view {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
        }

        .btn-view:hover {
            transform: scale(1.1);
        }

        .btn-ciclo {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn-ciclo:hover {
            transform: scale(1.1);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 1% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 1200px;
            max-height: 95vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            color: white;
            padding: 1.8rem 2rem;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 130px;
            height: 130px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 165, 0, 0.08));
            border-radius: 50%;
            transform: translate(25%, -25%);
        }

        .modal-header h2 {
            font-size: 1.6rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            position: relative;
            z-index: 2;
        }

        .modal-header h2 i {
            color: #FFD700;
        }

        .close {
            color: #FFD700;
            font-size: 1.8rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .close:hover {
            transform: rotate(90deg) scale(1.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-section {
            background: #f8f9fa;
            padding: 1.8rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .section-title i {
            color: #FFD700;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.3rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 0.9rem;
        }

        .form-input, .form-select {
            padding: 0.9rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Poppins', sans-serif;
            width: 100%;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #FFD700;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-input:disabled {
            background: #f8f9fa;
            cursor: not-allowed;
            color: #6c757d;
        }

        .productos-section {
            background: linear-gradient(135deg, #fff8e1, #fff3c4);
            padding: 1.8rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            border: 2px solid #FFD700;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .btn-add {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            border: none;
            padding: 0.7rem 1.3rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(39, 174, 96, 0.25);
        }

        .producto-card {
            background: white;
            padding: 1.3rem;
            border-radius: 12px;
            margin-bottom: 1.3rem;
            border: 2px solid #e9ecef;
            position: relative;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .btn-remove {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #e74c3c, #c82333);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-remove:hover {
            transform: scale(1.1);
        }

        .producto-fields {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
        }

        .custom-select-wrapper {
            position: relative;
        }

        .custom-select {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .custom-select:focus, .custom-select.active {
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #FFD700;
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }

        .select-search {
            padding: 0.7rem;
            border: none;
            border-bottom: 1px solid #eee;
            width: 100%;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }

        .select-option {
            padding: 0.7rem;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }

        .select-option:hover {
            background: #f8f9fa;
        }

        .select-option:last-child {
            border-bottom: none;
        }

        .cumplimiento-display {
            background: linear-gradient(135deg, white, #f8f9fa);
            padding: 1.8rem;
            border-radius: 15px;
            border: 3px solid #FFD700;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .cumplimiento-value {
            font-size: 2.3rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .cumplimiento-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }

        .meta-info {
            margin-top: 1rem;
            font-size: 0.95rem;
            color: #666;
        }

        .modal-footer {
            padding: 1.8rem 2rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            background: #f8f9fa;
            border-radius: 0 0 20px 20px;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 0.9rem 1.8rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .btn-save {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
            border: none;
            padding: 0.9rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.25);
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #666;
        }

        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 1.3rem;
            display: block;
            color: #FFD700;
        }

        .empty-state p {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.8rem;
            margin: 0 0.2rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            background: white;
            color: #1a1a1a !important;
            transition: all 0.2s ease;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #FFD700 !important;
            border-color: #FFD700 !important;
            color: #1a1a1a !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #FFD700, #FFA500) !important;
            border-color: #FFD700 !important;
            color: #1a1a1a !important;
        }

        @media (max-width: 968px) {
            .producto-fields {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .modal-content {
                width: 95%;
                margin: 2% auto;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .main-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .modal-body, .modal-header, .modal-footer {
                padding: 1.3rem;
            }

            .form-section, .productos-section {
                padding: 1.3rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0 0.5rem;
            }

            .page-header {
                padding: 1rem;
            }

            .btn-primary {
                padding: 0.9rem 1.3rem;
                font-size: 0.95rem;
            }

            .modal-content {
                width: 98%;
                margin: 1% auto;
            }

            .modal-body {
                padding: 1rem;
            }

            .modal-header, .modal-footer {
                padding: 1.3rem 1rem;
            }

            .form-section, .productos-section {
                padding: 1rem;
            }

            .form-input, .form-select, .custom-select {
                padding: 0.8rem;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Gestión de Productividad
            </h1>
            <p>Registra y gestiona la productividad de las actividades de reempaque</p>
        </div>

        <div class="main-actions">
            <button class="btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i>
                Nueva Productividad
            </button>
        </div>

        <div class="records-table">
            <table id="tablaProductividad" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Actividad</th>
                        <th>Turno</th>
                        <th>Productos</th>
                        <th>Cumplimiento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                        <tr>
                            <td data-order="<?php echo strtotime($registro['fecha']); ?>"><?php echo date('d/m/Y', strtotime($registro['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($registro['actividad']); ?></td>
                            <td><?php echo htmlspecialchars($registro['turno']); ?></td>
                            <td><?php echo substr($registro['productos'], 0, 50) . (strlen($registro['productos']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <?php 
                                $cumplimiento = $registro['cumplimiento_general'];
                                $meta = $metas[$registro['actividad']]['meta'];
                                $disparador = $metas[$registro['actividad']]['disparador'];
                                
                                if ($cumplimiento >= $meta) {
                                    $class = 'cumple-meta';
                                } elseif ($cumplimiento <= $disparador) {
                                    $class = 'disparador';
                                } else {
                                    $class = 'no-cumple-meta';
                                }
                                ?>
                                <span class="cumplimiento-badge <?php echo $class; ?>">
                                    <?php echo number_format($cumplimiento, 2); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($registro['estado_ciclo'] == 'completo'): ?>
                                    <span class="estado-badge estado-completo">Completo</span>
                                <?php else: ?>
                                    <span class="estado-badge estado-pendiente">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-sm btn-view" onclick="verDetalle('<?php echo $registro['grupo_registro']; ?>')" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (!$registro['cumple_meta'] && $registro['estado_ciclo'] == 'pendiente'): ?>
                                        <button class="btn-sm btn-ciclo" onclick="cerrarCiclo('<?php echo $registro['grupo_registro']; ?>')" title="Cerrar ciclo">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div id="modalNuevo" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Nueva Productividad</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="formProductividad" method="POST">
                <input type="hidden" name="action" value="guardar_reempaque">
                <div class="modal-body">
                    
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Información General
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="fecha" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Auxiliar</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Actividad</label>
                                <select name="actividad" class="form-select" required onchange="updateMeta()">
                                    <option value="">Seleccionar...</option>
                                    <option value="Clasificación">Clasificación</option>
                                    <option value="Lavado">Lavado</option>
                                    <option value="Reempaque">Reempaque</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Turno</label>
                                <select name="turno" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="A">Turno A</option>
                                    <option value="B">Turno B</option>
                                    <option value="C">Turno C</option>
                                </select>
                            </div>
                        </div>
                        <br/>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label"><i class="fas fa-comment"></i> Indique en este campo las tareas adicionales fuera de jornada productiva reportada</label>
                            <textarea name="observaciones" class="form-input" style="min-height: 100px; resize: vertical;" placeholder="Ingrese cualquier actividad adicional realizada en el turno..."></textarea>
                        </div>
                    </div>

                    <div class="productos-section">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-boxes"></i>
                                Productos y Productividad
                            </div>
                            <button type="button" class="btn-add" onclick="addProducto()">
                                <i class="fas fa-plus"></i>
                                Agregar Producto
                            </button>
                        </div>
                        <div id="productosContainer">
                        </div>
                    </div>

                    <div class="cumplimiento-display">
                        <div class="cumplimiento-value" id="cumplimientoValue">0.00</div>
                        <div class="cumplimiento-label">Cumplimiento (Unidades por Hora)</div>
                        <div class="meta-info" id="metaInfo"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Productividad</button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="modalDetalle" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> Detalle de Productividad</h2>
                <span class="close" onclick="closeModalDetalle()">&times;</span>
            </div>
            <div class="modal-body" id="detalleContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModalDetalle()">Cerrar</button>
            </div>
        </div>
    </div>

    
    <div id="modalCiclo" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-upload"></i> Cerrar Ciclo - Evidencia 5 Por Qué</h2>
                <span class="close" onclick="closeModalCiclo()">&times;</span>
            </div>
            <form id="formEvidencia" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="subir_evidencia">
                <input type="hidden" name="grupo_registro" id="grupoRegistro">
                <div class="modal-body">
                    <p style="margin-bottom: 2rem; color: #666; font-size: 1.1rem; line-height: 1.6;">
                        Para cerrar el ciclo de productividad, debes subir una evidencia de que completaste el formulario de <strong>5 Por Qué</strong>.
                    </p>
                    <div class="form-group">
                        <label class="form-label">Evidencia (Imagen)</label>
                        <input type="file" name="evidencia" class="form-input" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModalCiclo()">Cancelar</button>
                    <button type="submit" class="btn-save">Subir Evidencia</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const metas = <?php echo json_encode($metas); ?>;
        const productos = <?php echo json_encode($productos); ?>;
        let productoCount = 0;

        $(document).ready(function() {
            $('#tablaProductividad').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
                pageLength: 10,
                dom: '<"top"lf>rt<"bottom"ip><"clear">',
                columnDefs: [
                    {
                        targets: 0,
                        type: 'num',
                        orderSequence: ['desc', 'asc']
                    }
                ]
            });
        });

        <?php if ($alert_message && $alert_type): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const alertData = <?php echo $alert_message; ?>;
            
            if (alertData.showCancelButton) {
                Swal.fire(alertData).then((result) => {
                    if (result.isConfirmed) {
                        window.open('https://cdcucuta.logisticos.com.co/admin_formulario.php', '_blank');
                    }
                });
            } else {
                Swal.fire(alertData);
            }
        });
        <?php endif; ?>

        function openModal() {
            document.getElementById('modalNuevo').style.display = 'block';
            if (document.querySelectorAll('.producto-card').length === 0) {
                addProducto();
            }
        }

        function closeModal() {
            document.getElementById('modalNuevo').style.display = 'none';
            document.getElementById('formProductividad').reset();
            document.getElementById('productosContainer').innerHTML = '';
            productoCount = 0;
            document.getElementById('cumplimientoValue').textContent = '0.00';
            document.getElementById('metaInfo').innerHTML = '';
        }

        function cerrarCiclo(grupoRegistro) {
            document.getElementById('grupoRegistro').value = grupoRegistro;
            document.getElementById('modalCiclo').style.display = 'block';
        }

        function closeModalCiclo() {
            document.getElementById('modalCiclo').style.display = 'none';
        }

        function closeModalDetalle() {
            document.getElementById('modalDetalle').style.display = 'none';
        }

        function addProducto() {
            if (productoCount >= 5) {
                Swal.fire({
                    title: 'Límite alcanzado',
                    text: 'Máximo 5 productos por registro',
                    icon: 'warning',
                    confirmButtonColor: '#FFD700'
                });
                return;
            }

            let ultimaHoraInicio = '';
            let ultimaHoraFin = '';
            
            const ultimoProducto = document.querySelector('.producto-card:last-child');
            if (ultimoProducto) {
                const horaInicioInput = ultimoProducto.querySelector('input[name*="[hora_inicio]"]');
                const horaFinInput = ultimoProducto.querySelector('input[name*="[hora_fin]"]');
                
                if (horaInicioInput && horaFinInput) {
                    ultimaHoraInicio = horaInicioInput.value;
                    ultimaHoraFin = horaFinInput.value;
                }
            }

            productoCount++;
            const container = document.getElementById('productosContainer');
            const productoDiv = document.createElement('div');
            productoDiv.className = 'producto-card';
            productoDiv.innerHTML = `
                <button type="button" class="btn-remove" onclick="removeProducto(this)" title="Eliminar">
                    <i class="fas fa-times"></i>
                </button>
                <div class="producto-fields">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-barcode"></i> SKU / Producto</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleSelect(this)">
                                <span class="select-text">Seleccionar producto...</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="select-dropdown">
                                <input type="text" class="select-search" placeholder="Buscar producto..." onkeyup="filterOptions(this)">
                                <div class="select-options">
                                    ${productos.map(p => `<div class="select-option" data-value="${p.id}">${p.id_material} - ${p.material}</div>`).join('')}
                                </div>
                            </div>
                            <select name="productos[${productoCount}][sku]" style="display: none;" required onchange="calculateCumplimiento()">
                                <option value="">Seleccionar producto...</option>
                                ${productos.map(p => `<option value="${p.id}">${p.id_material} - ${p.material}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calculator"></i> Unidades</label>
                        <input type="number" name="productos[${productoCount}][unidades]" class="form-input" min="1" required onchange="calculateCumplimiento()" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-clock"></i> Hora Inicio</label>
                        <input type="time" name="productos[${productoCount}][hora_inicio]" class="form-input" required onchange="calculateCumplimiento()" value="${ultimaHoraInicio}">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-clock"></i> Hora Fin</label>
                        <input type="time" name="productos[${productoCount}][hora_fin]" class="form-input" required onchange="calculateCumplimiento()" value="${ultimaHoraFin}">
                    </div>
                </div>
            `;
            container.appendChild(productoDiv);
        }

        function removeProducto(button) {
            button.parentElement.remove();
            productoCount--;
            calculateCumplimiento();
        }

        function toggleSelect(selectElement) {
            const dropdown = selectElement.nextElementSibling;
            const isOpen = dropdown.style.display === 'block';
            
            document.querySelectorAll('.select-dropdown').forEach(dd => dd.style.display = 'none');
            document.querySelectorAll('.custom-select').forEach(cs => cs.classList.remove('active'));
            
            if (!isOpen) {
                dropdown.style.display = 'block';
                selectElement.classList.add('active');
                dropdown.querySelector('.select-search').focus();
            }
        }

        function filterOptions(searchInput) {
            const filter = searchInput.value.toLowerCase();
            const options = searchInput.nextElementSibling.querySelectorAll('.select-option');
            
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(filter) ? 'block' : 'none';
            });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-select-wrapper')) {
                document.querySelectorAll('.select-dropdown').forEach(dd => dd.style.display = 'none');
                document.querySelectorAll('.custom-select').forEach(cs => cs.classList.remove('active'));
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('select-option')) {
                const option = e.target;
                const wrapper = option.closest('.custom-select-wrapper');
                const customSelect = wrapper.querySelector('.custom-select');
                const hiddenSelect = wrapper.querySelector('select');
                const dropdown = wrapper.querySelector('.select-dropdown');
                
                customSelect.querySelector('.select-text').textContent = option.textContent;
                hiddenSelect.value = option.dataset.value;
                dropdown.style.display = 'none';
                customSelect.classList.remove('active');
                
                calculateCumplimiento();
            }
        });

        function calculateCumplimiento() {
            const productos = document.querySelectorAll('.producto-card');
            let totalUnidades = 0;
            let totalHorasTrabajadas = 0;

            productos.forEach(producto => {
                const unidades = parseInt(producto.querySelector('input[name*="[unidades]"]').value) || 0;
                const horaInicio = producto.querySelector('input[name*="[hora_inicio]"]').value;
                const horaFin = producto.querySelector('input[name*="[hora_fin]"]').value;

                if (horaInicio && horaFin && unidades > 0) {
                    totalUnidades += unidades;
                    
                    const inicio = new Date(`2000-01-01 ${horaInicio}`);
                    let fin = new Date(`2000-01-01 ${horaFin}`);
                    
                    if (fin < inicio) {
                        fin.setDate(fin.getDate() + 1);
                    }
                    
                    const horasProducto = (fin - inicio) / (1000 * 60 * 60);
                    totalHorasTrabajadas += horasProducto;
                }
            });

            let cumplimiento = 0;
            if (totalHorasTrabajadas > 0 && totalUnidades > 0) {
                cumplimiento = totalUnidades / totalHorasTrabajadas;
            }

            document.getElementById('cumplimientoValue').textContent = cumplimiento.toFixed(2);
        }

        function updateMeta() {
            const actividad = document.querySelector('select[name="actividad"]').value;
            const metaInfo = document.getElementById('metaInfo');
            
            if (actividad && metas[actividad]) {
                const meta = metas[actividad];
                metaInfo.innerHTML = `Meta: <strong>${meta.meta}</strong> | Disparador: <strong>${meta.disparador}</strong>`;
            } else {
                metaInfo.innerHTML = '';
            }
        }

        function verDetalle(grupoRegistro) {
            fetch('../api/reempaque/get_detalle.php?grupo=' + grupoRegistro)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    
                    html += '<div style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; border: 2px solid #FFD700;">';
                    html += '<h3 style="color: #1a1a1a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-info-circle" style="color: #FFD700;"></i> Información General</h3>';
                    html += `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">`;
                    html += `<div><strong>Auxiliar:</strong> ${data[0].nombre}</div>`;
                    html += `<div><strong>Fecha:</strong> ${data[0].fecha_formateada}</div>`;
                    html += `<div><strong>Actividad:</strong> ${data[0].actividad}</div>`;
                    html += `<div><strong>Estado:</strong> <span style="color: ${data[0].estado_ciclo === 'completo' ? '#155724' : '#856404'}">${data[0].estado_ciclo === 'completo' ? 'Completo' : 'Pendiente'}</span></div>`;
                    html += `<div><strong>Cumplimiento:</strong> <span style="color: ${data[0].cumple_meta ? '#155724' : '#721c24'}; font-weight: 600;">${parseFloat(data[0].cumplimiento_general).toFixed(2)} unidades/hora</span></div>`;
                    html += `</div>`;
                    
                    if (data[0].observaciones) {
                        html += `<div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 10px;">`;
                        html += `<strong><i class="fas fa-comment"></i> Observaciones:</strong>`;
                        html += `<p style="margin-top: 0.5rem; color: #666;">${data[0].observaciones}</p>`;
                        html += `</div>`;
                    }
                    
                    html += `</div>`;
                    
                    html += '<div style="overflow-x: auto; margin-bottom: 2rem;">';
                    html += '<h3 style="color: #1a1a1a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-list" style="color: #FFD700;"></i> Detalle de Productos</h3>';
                    html += '<table style="width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">';
                    html += '<thead><tr style="background: linear-gradient(135deg, #1a1a1a, #2d2d2d); color: white;">';
                    html += '<th style="padding: 1rem; text-align: left; font-weight: 600;">SKU</th>';
                    html += '<th style="padding: 1rem; text-align: left; font-weight: 600;">Producto</th>';
                    html += '<th style="padding: 1rem; text-align: left; font-weight: 600;">Unidades</th>';
                    html += '<th style="padding: 1rem; text-align: left; font-weight: 600;">Hora Inicio</th>';
                    html += '<th style="padding: 1rem; text-align: left; font-weight: 600;">Hora Fin</th>';
                    html += '<th style="padding: 1rem; text-align: left; font-weight: 600;">Horas</th>';
                    html += '<th style="padding: 1rem; text-align: left; font-weight: 600;">Productividad</th>';
                    html += '</tr></thead><tbody>';
                    
                    data.forEach((item, index) => {
                        html += `<tr style="border-bottom: 1px solid #f0f0f0; ${index % 2 === 0 ? 'background: #f8f9fa;' : 'background: white;'}">`;
                        html += `<td style="padding: 1rem; font-weight: 500;">${item.sku}</td>`;
                        html += `<td style="padding: 1rem;">${item.producto_nombre}</td>`;
                        html += `<td style="padding: 1rem; text-align: center; font-weight: 600; color: #2c3e50;">${item.unidades}</td>`;
                        html += `<td style="padding: 1rem; text-align: center;">${item.hora_inicio}</td>`;
                        html += `<td style="padding: 1rem; text-align: center;">${item.hora_fin}</td>`;
                        html += `<td style="padding: 1rem; text-align: center;">${parseFloat(item.horas_trabajadas).toFixed(2)}h</td>`;
                        html += `<td style="padding: 1rem; text-align: center; font-weight: 600; color: #27ae60;">${parseFloat(item.cumplimiento_individual).toFixed(2)}</td>`;
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    
                    if (!data[0].cumple_meta) {
                        html += '<div style="background: linear-gradient(135deg, #fff3cd, #fdeaa7); padding: 2rem; border-radius: 15px; border: 2px solid #ffc107;">';
                        html += '<h3 style="color: #856404; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Estado del Ciclo</h3>';
                        
                        if (data[0].estado_ciclo === 'completo' && data[0].evidencia_5_porque) {
                            html += '<p style="color: #155724; font-weight: 600; margin-bottom: 1rem;">Ciclo completo - Evidencia del 5 Por Qué subida correctamente</p>';
                            html += '<div style="text-align: center;">';
                            html += `<img src="${data[0].evidencia_5_porque}" style="max-width: 100%; max-height: 400px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); cursor: pointer;" onclick="window.open('${data[0].evidencia_5_porque}', '_blank')" title="Click para ver en tamaño completo">`;
                            html += '</div>';
                        } else {
                            html += '<p style="color: #721c24; font-weight: 600;">Ciclo pendiente - Falta subir evidencia del 5 Por Qué</p>';
                            html += '<p style="color: #856404; font-style: italic;">Debe cargar la evidencia para completar el ciclo.</p>';
                        }
                        html += '</div>';
                    }
                    
                    html += `<div style="background: ${data[0].cumple_meta ? 'linear-gradient(135deg, #d4edda, #c3e6cb)' : 'linear-gradient(135deg, #f8d7da, #f1aeb5)'}; padding: 2rem; border-radius: 15px; text-align: center; margin-top: 2rem;">`;
                    html += `<h3 style="color: ${data[0].cumple_meta ? '#155724' : '#721c24'}; margin-bottom: 0.5rem;">`;
                    html += `${data[0].cumple_meta ? 'Meta Cumplida!' : 'Meta No Cumplida'}`;
                    html += `</h3>`;
                    html += `<p style="font-size: 1.2rem; font-weight: 600; color: ${data[0].cumple_meta ? '#155724' : '#721c24'};">`;
                    html += `Cumplimiento Final: ${parseFloat(data[0].cumplimiento_general).toFixed(2)} unidades por hora`;
                    html += `</p></div>`;
                    
                    document.getElementById('detalleContent').innerHTML = html;
                    document.getElementById('modalDetalle').style.display = 'block';
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al cargar el detalle',
                        icon: 'error',
                        confirmButtonColor: '#FFD700'
                    });
                });
        }

        window.onclick = function(event) {
            const modalNuevo = document.getElementById('modalNuevo');
            const modalCiclo = document.getElementById('modalCiclo');
            const modalDetalle = document.getElementById('modalDetalle');
            
            if (event.target == modalNuevo) {
                closeModal();
            }
            if (event.target == modalCiclo) {
                closeModalCiclo();
            }
            if (event.target == modalDetalle) {
                closeModalDetalle();
            }
        }
    </script>
</body>
</html>