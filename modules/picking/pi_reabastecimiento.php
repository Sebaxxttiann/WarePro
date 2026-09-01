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
        $productos = json_decode($_POST['productos'], true);
        
        if (empty($productos)) {
            throw new Exception('No se recibieron productos');
        }

        $stmt = $pdo->prepare("INSERT INTO pi_reabastecimiento (marca_temporal, nombre, descripcion_material, cantidad_estibas, tipo_picking, operacion_id) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($productos as $producto) {
            $stmt->execute([
                $_POST['marca_temporal'],
                strtoupper(limpiarDatos($_POST['nombre'])),
                limpiarDatos($producto['descripcion']),
                (int)$producto['cantidad'],
                $producto['tipo_picking'],
                getOperacionActiva()
            ]);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Registros guardados correctamente']);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'obtener_productos') {
    try {
        $busqueda = $_GET['busqueda'] ?? '';
        $limite = (int)($_GET['limite'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);
        
        if (empty($busqueda)) {
            $sql = "SELECT id_material, material FROM productos ORDER BY material ASC LIMIT $offset, $limite";
            $params = [];
            
            $sqlCount = "SELECT COUNT(*) as total FROM productos";
            $stmtCount = $pdo->prepare($sqlCount);
            $stmtCount->execute();
            $totalProductos = $stmtCount->fetch()['total'];
        } else {
            $limiteConsulta = 200;
            $sql = "SELECT id_material, material FROM productos 
                    WHERE id_material LIKE ? OR material LIKE ? 
                    ORDER BY material ASC LIMIT $limiteConsulta";
            $params = ["%$busqueda%", "%$busqueda%"];
            
            $sqlCount = "SELECT COUNT(*) as total FROM productos 
                        WHERE id_material LIKE ? OR material LIKE ?";
            $stmtCount = $pdo->prepare($sqlCount);
            $stmtCount->execute(["%$busqueda%", "%$busqueda%"]);
            $totalProductos = $stmtCount->fetch()['total'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'productos' => $productos,
            'total' => count($productos),
            'total_disponible' => (int)$totalProductos,
            'busqueda' => $busqueda,
            'tiene_mas' => (empty($busqueda)) ? ($offset + $limite < $totalProductos) : false,
            'success' => true
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Error al obtener productos: ' . $e->getMessage(),
            'success' => false,
            'productos' => []
        ]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'grafica_datos') {
    try {
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-01-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-12-31');
        $material_filtro = $_GET['material'] ?? '';

        $sql = "
            SELECT 
                descripcion_material,
                SUM(cantidad_estibas) as total_estibas,
                COUNT(*) as total_registros
            FROM pi_reabastecimiento
            WHERE marca_temporal BETWEEN ? AND ? AND operacion_id = ?
        ";

        $params = [$fecha_inicio, $fecha_fin, getOperacionActiva()];

        if (!empty($material_filtro)) {
            $sql .= " AND descripcion_material LIKE ?";
            $params[] = "%$material_filtro%";
        }

        $sql .= " GROUP BY descripcion_material ORDER BY total_estibas DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($datos);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al obtener datos: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'registros_material') {
    try {
        $material = $_GET['material'];
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-01-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-12-31');

        $stmt = $pdo->prepare("
            SELECT * FROM pi_reabastecimiento
            WHERE descripcion_material = ?
            AND marca_temporal BETWEEN ? AND ?
            AND operacion_id = ?
            ORDER BY marca_temporal DESC
        ");
        $stmt->execute([$material, $fecha_inicio, $fecha_fin, getOperacionActiva()]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($registros);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al obtener registros: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'todos_registros') {
    try {
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-01-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-12-31');
        $material_filtro = $_GET['material'] ?? '';

        $sql = "SELECT * FROM pi_reabastecimiento WHERE marca_temporal BETWEEN ? AND ? AND operacion_id = ?";
        $params = [$fecha_inicio, $fecha_fin, getOperacionActiva()];

        if (!empty($material_filtro)) {
            $sql .= " AND descripcion_material LIKE ?";
            $params[] = "%$material_filtro%";
        }

        $sql .= " ORDER BY marca_temporal DESC, id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($registros);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al obtener registros: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'obtener_materiales') {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT descripcion_material
            FROM pi_reabastecimiento
            WHERE operacion_id = ?
            ORDER BY descripcion_material ASC
        ");
        $stmt->execute([getOperacionActiva()]);
        $materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($materiales);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al obtener materiales: ' . $e->getMessage()]);
        exit;
    }
}

require_once '../../core/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PI Reabastecimiento - WARE PRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #333;
            min-height: 100vh;
        }

        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }

        .page-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border-left: 6px solid #FFD700;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .page-subtitle { color: #666; font-size: 1.1rem; font-weight: 500; }

        .controls-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 2rem;
            align-items: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4); }

        .filters-container {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            background: rgba(255, 215, 0, 0.05);
            padding: 1.5rem;
            border-radius: 15px;
            flex-wrap: wrap;
        }

        .filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .filter-group label { font-weight: 600; color: #1a1a1a; font-size: 0.9rem; }

        .date-input, .material-select {
            padding: 12px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            background: white;
            min-width: 150px;
        }

        .date-input:focus, .material-select:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.15);
        }

        .btn-clear {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-container {
            position: relative;
            height: 400px;
        }

        .records-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .records-table { width: 100%; border-collapse: collapse; }

        .records-table thead {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }

        .records-table th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: #FFD700 !important;
            font-size: 0.9rem;
        }

        .records-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
        }

        .records-table tbody tr:hover { background: rgba(255, 215, 0, 0.05); }

        .material-badge {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-reabastecimiento {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .picking-badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .picking-retornable { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .picking-no-retornable { background: rgba(255, 193, 7, 0.1); color: #ffc107; }

        .estibas-count {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
            padding: 3px 6px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            border-bottom: 3px solid #FFD700;
            position: relative;
        }

        .modal-title {
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            color: #FFD700;
        }

        .close {
            position: absolute;
            right: 2rem;
            top: 2rem;
            color: #FFD700;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-body { padding: 2rem; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-group { margin-bottom: 1rem; }
        .form-group.full-width { grid-column: 1 / -1; }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.15);
        }

        .product-selector-container { position: relative; }

        .product-search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #FFD700;
            pointer-events: none;
        }

        .product-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #FFD700;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .product-option {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }

        .product-option:hover { background: rgba(255, 215, 0, 0.1); }

        .product-code {
            font-weight: 700;
            color: #FFD700;
            font-size: 0.85rem;
        }

        .product-name {
            color: #666;
            font-size: 0.9rem;
            margin-top: 2px;
        }

        .loading-results, .no-results {
            padding: 15px;
            text-align: center;
            color: #666;
        }

        .modal-footer {
            padding: 2rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .productos-lista {
            margin-bottom: 2rem;
        }

        .producto-item {
            background: rgba(255, 215, 0, 0.05);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 2px solid #e0e0e0;
        }

        .producto-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .producto-numero {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .producto-campos {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
        }

        .btn-add-producto {
            background: #28a745;
            color: white;
            width: 100%;
            margin-bottom: 2rem;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-add-producto:hover {
            background: #218838;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px;
        }

        @media (max-width: 1200px) {
            .container { padding: 1.5rem; }
            .chart-container { height: 350px; }
        }

        @media (max-width: 992px) {
            .controls-grid { 
                grid-template-columns: 1fr; 
                gap: 1.5rem; 
            }
            
            .page-title { font-size: 2rem; }
            .page-subtitle { font-size: 1rem; }
            
            .chart-container { height: 320px; }
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            
            .page-header { 
                padding: 1.5rem; 
                border-left-width: 4px;
            }
            
            .page-title { 
                font-size: 1.75rem; 
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .page-subtitle { font-size: 0.9rem; }
            
            .controls-section { padding: 1.5rem; }
            
            .controls-grid { 
                grid-template-columns: 1fr; 
                gap: 1rem; 
            }
            
            .btn-primary, .btn-clear {
                width: 100%;
                justify-content: center;
            }
            
            .filters-container { 
                flex-direction: column; 
                gap: 1rem;
                padding: 1rem;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .date-input, .material-select {
                width: 100%;
            }
            
            .chart-section, .records-section {
                padding: 1.5rem;
            }
            
            .section-title {
                font-size: 1.2rem;
                flex-wrap: wrap;
            }
            
            .chart-container { height: 300px; }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .records-table {
                min-width: 600px;
            }
            
            .records-table th,
            .records-table td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
                max-height: 85vh;
            }
            
            .modal-header {
                padding: 1.5rem;
            }
            
            .modal-title {
                font-size: 1.3rem;
                padding-right: 2rem;
            }
            
            .close {
                right: 1rem;
                top: 1.5rem;
                font-size: 1.75rem;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .form-grid { 
                grid-template-columns: 1fr; 
                gap: 1rem;
            }
            
            .producto-campos { 
                grid-template-columns: 1fr; 
            }
            
            .producto-item {
                padding: 1rem;
            }
            
            .producto-item-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .btn-remove {
                width: 100%;
                justify-content: center;
            }
            
            .modal-footer {
                padding: 1.5rem;
                flex-direction: column-reverse;
            }
            
            .modal-footer button {
                width: 100%;
            }
            
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                text-align: center;
                margin-bottom: 1rem;
            }
            
            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin-left: 0;
                margin-top: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .container { padding: 0.75rem; }
            
            .page-header { 
                padding: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .page-title { 
                font-size: 1.5rem; 
            }
            
            .page-title i {
                font-size: 1.25rem;
            }
            
            .controls-section { 
                padding: 1rem; 
                margin-bottom: 1rem;
            }
            
            .btn-primary, .btn-clear {
                padding: 12px 20px;
                font-size: 0.9rem;
            }
            
            .filters-container {
                padding: 0.75rem;
            }
            
            .filter-group label {
                font-size: 0.85rem;
            }
            
            .date-input, .material-select {
                padding: 10px 14px;
                font-size: 0.9rem;
            }
            
            .chart-section, .records-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .section-title i {
                font-size: 1rem;
            }
            
            .chart-container { 
                height: 280px; 
            }
            
            .records-table {
                min-width: 550px;
            }
            
            .records-table th,
            .records-table td {
                padding: 8px 6px;
                font-size: 0.8rem;
            }
            
            .material-badge,
            .picking-badge,
            .estibas-count {
                font-size: 0.7rem;
                padding: 3px 6px;
            }
            
            .modal-content {
                width: 98%;
                margin: 2% auto;
            }
            
            .modal-header {
                padding: 1.25rem;
            }
            
            .modal-title {
                font-size: 1.15rem;
            }
            
            .close {
                font-size: 1.5rem;
            }
            
            .modal-body {
                padding: 1.25rem;
            }
            
            .form-label {
                font-size: 0.9rem;
            }
            
            .form-input, .form-select, .product-search-input {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .producto-numero {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
            
            .btn-remove {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
            
            .btn-add-producto {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .product-option {
                padding: 8px 12px;
            }
            
            .product-code {
                font-size: 0.8rem;
            }
            
            .product-name {
                font-size: 0.85rem;
            }
            
            .modal-footer {
                padding: 1.25rem;
            }
            
            .modal-footer button {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 360px) {
            .page-title { 
                font-size: 1.35rem; 
            }
            
            .section-title {
                font-size: 1rem;
            }
            
            .chart-container { 
                height: 250px; 
            }
            
            .modal-title {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-boxes" style="color: #FFD700; margin-right: 15px;"></i>
                PI Reabastecimiento
            </h1>
            <p class="page-subtitle">Sistema de registro y seguimiento de reabastecimiento de materiales</p>
        </div>

        <div class="controls-section">
            <div class="controls-grid">
                <button class="btn-primary" onclick="openModal()">
                    <i class="fas fa-plus-circle"></i>
                    Nuevo Registro
                </button>

                <div class="filters-container">
                    <div class="filter-group">
                        <label>Fecha inicio:</label>
                        <input type="date" id="fechaInicio" class="date-input" value="<?php echo date('Y-01-01'); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Fecha fin:</label>
                        <input type="date" id="fechaFin" class="date-input" value="<?php echo date('Y-12-31'); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Material:</label>
                        <select id="materialFiltro" class="material-select">
                            <option value="">Todos los materiales</option>
                        </select>
                    </div>
                </div>

                <button class="btn-clear" onclick="limpiarFiltros()">
                    <i class="fas fa-eraser"></i>
                    Limpiar
                </button>
            </div>
        </div>

        <div class="chart-section">
            <h3 class="section-title">
                <i class="fas fa-chart-bar" style="color: #FFD700;"></i>
                Estibas por Material
            </h3>
            <div class="chart-container">
                <div id="chartReabastecimiento"></div>
            </div>
        </div>

        <div class="records-section" id="recordsSection">
            <h3 id="recordsTitle" class="section-title">
                <i class="fas fa-table" style="color: #FFD700;"></i>
                Todos los Registros
            </h3>
            <div class="table-container">
                <table class="records-table" id="registrosTable">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Material</th>
                            <th>Estibas</th>
                            <th>Tipo Picking</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="reabastecimientoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Nuevo Registro de PI Reabastecimiento</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="reabastecimientoForm">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Marca Temporal</label>
                            <input type="date" name="marca_temporal" class="form-input" value="<?php echo $fecha_hoy; ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-input" style="text-transform: uppercase;" required>
                        </div>
                    </div>

                    <button type="button" class="btn-add-producto" onclick="agregarProducto()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>

                    <div id="productosLista" class="productos-lista"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedProduct = null;
        let productSearchTimeout = null;
        let productoCounter = 0;
        let chart = null;
        let dataTable = null;

        function openModal() {
            document.getElementById('reabastecimientoModal').style.display = 'block';
            productoCounter = 0;
            document.getElementById('productosLista').innerHTML = '';
            agregarProducto();
        }

        function closeModal() {
            document.getElementById('reabastecimientoModal').style.display = 'none';
            document.getElementById('reabastecimientoForm').reset();
            document.getElementById('productosLista').innerHTML = '';
            productoCounter = 0;
        }

        function limpiarFiltros() {
            document.getElementById('fechaInicio').value = '<?php echo date('Y-01-01'); ?>';
            document.getElementById('fechaFin').value = '<?php echo date('Y-12-31'); ?>';
            document.getElementById('materialFiltro').value = '';
            actualizarGrafica();
        }

        function agregarProducto() {
            productoCounter++;
            const productosLista = document.getElementById('productosLista');
            
            const productoDiv = document.createElement('div');
            productoDiv.className = 'producto-item';
            productoDiv.id = `producto-${productoCounter}`;
            
            productoDiv.innerHTML = `
                <div class="producto-item-header">
                    <span class="producto-numero">Producto #${productoCounter}</span>
                    <button type="button" class="btn-remove" onclick="removerProducto(${productoCounter})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
                <div class="producto-campos">
                    <div class="form-group">
                        <label class="form-label">Seleccionar Producto</label>
                        <div class="product-selector-container">
                            <input type="text" 
                                   id="productSearchInput-${productoCounter}"
                                   class="product-search-input"
                                   placeholder="Buscar producto..."
                                   autocomplete="off">
                            <input type="hidden" id="selectedProductName-${productoCounter}" name="productos[${productoCounter}][descripcion]">
                            <i class="fas fa-search search-icon"></i>
                            <div id="productDropdown-${productoCounter}" class="product-dropdown"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cantidad de Estibas</label>
                        <input type="number" name="productos[${productoCounter}][cantidad]" class="form-input" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de Picking</label>
                        <select name="productos[${productoCounter}][tipo_picking]" class="form-select" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="no retornable">No Retornable</option>
                            <option value="retornable">Retornable</option>
                        </select>
                    </div>
                </div>
            `;
            
            productosLista.appendChild(productoDiv);
            initProductSearch(productoCounter);
        }

        function removerProducto(id) {
            const elemento = document.getElementById(`producto-${id}`);
            if (elemento) {
                elemento.remove();
            }
            
            if (document.querySelectorAll('.producto-item').length === 0) {
                agregarProducto();
            }
        }

        function initProductSearch(id) {
            const searchInput = document.getElementById(`productSearchInput-${id}`);
            const dropdown = document.getElementById(`productDropdown-${id}`);
            
            searchInput.addEventListener('input', function(e) {
                const busqueda = e.target.value.trim();
                
                if (productSearchTimeout) clearTimeout(productSearchTimeout);
                
                productSearchTimeout = setTimeout(() => {
                    cargarProductos(busqueda, id);
                }, 300);
            });

            searchInput.addEventListener('focus', function() {
                cargarProductos('', id);
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest(`#producto-${id}`)) {
                    dropdown.style.display = 'none';
                }
            });
        }

        function cargarProductos(busqueda, productoId) {
            const url = `pi_reabastecimiento.php?action=obtener_productos&busqueda=${encodeURIComponent(busqueda)}&limite=50`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) return;

                    const dropdown = document.getElementById(`productDropdown-${productoId}`);
                    dropdown.innerHTML = '';

                    if (data.productos.length === 0) {
                        dropdown.innerHTML = '<div class="no-results">No se encontraron productos</div>';
                    } else {
                        data.productos.forEach(producto => {
                            const option = document.createElement('div');
                            option.className = 'product-option';
                            option.innerHTML = `
                                <div class="product-code">${producto.id_material}</div>
                                <div class="product-name">${producto.material}</div>
                            `;
                            option.onclick = () => seleccionarProducto(producto, productoId);
                            dropdown.appendChild(option);
                        });
                    }

                    dropdown.style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        }

        function seleccionarProducto(producto, productoId) {
            const searchInput = document.getElementById(`productSearchInput-${productoId}`);
            const hiddenInput = document.getElementById(`selectedProductName-${productoId}`);
            const dropdown = document.getElementById(`productDropdown-${productoId}`);

            searchInput.value = `${producto.id_material} - ${producto.material}`;
            hiddenInput.value = producto.material;
            dropdown.style.display = 'none';
        }

        function actualizarGrafica() {
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;
            const materialFiltro = document.getElementById('materialFiltro').value;

            const url = `pi_reabastecimiento.php?action=grafica_datos&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&material=${encodeURIComponent(materialFiltro)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }

                    const materiales = data.map(item => item.descripcion_material);
                    const estibas = data.map(item => parseInt(item.total_estibas));

                    renderChart(materiales, estibas);
                    cargarTodosRegistros();
                })
                .catch(error => console.error('Error:', error));
        }

        function renderChart(materiales, estibas) {
            const options = {
                series: [{
                    name: 'Estibas',
                    data: estibas
                }],
                chart: {
                    type: 'bar',
                    height: 400,
                    toolbar: {
                        show: true
                    },
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            const index = config.dataPointIndex;
                            mostrarRegistrosMaterial(materiales[index]);
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        horizontal: false,
                        columnWidth: '70%',
                        distributed: true
                    }
                },
                colors: ['#FFD700', '#FFA500', '#FF8C00', '#FF7F50', '#FF6347', '#DC143C', '#C71585', '#8B008B', '#4B0082', '#191970'],
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '12px',
                        fontWeight: 'bold',
                        colors: ['#1a1a1a']
                    }
                },
                xaxis: {
                    categories: materiales,
                    labels: {
                        style: {
                            fontSize: '11px',
                            fontWeight: 600
                        },
                        rotate: -45
                    }
                },
                yaxis: {
                    title: {
                        text: 'Total Estibas',
                        style: {
                            fontSize: '14px',
                            fontWeight: 600
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(val) {
                            return val + ' estibas';
                        }
                    }
                },
                legend: {
                    show: false
                }
            };

            if (chart) {
                chart.destroy();
            }

            chart = new ApexCharts(document.querySelector("#chartReabastecimiento"), options);
            chart.render();
        }

        function cargarTodosRegistros() {
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;
            const materialFiltro = document.getElementById('materialFiltro').value;

            const url = `pi_reabastecimiento.php?action=todos_registros&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&material=${encodeURIComponent(materialFiltro)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }

                    const tableTitle = document.getElementById('recordsTitle');
                    if (materialFiltro) {
                        tableTitle.innerHTML = `<i class="fas fa-table" style="color: #FFD700;"></i> Registros: ${materialFiltro}`;
                    } else {
                        tableTitle.innerHTML = `<i class="fas fa-table" style="color: #FFD700;"></i> Todos los Registros`;
                    }

                    if (dataTable) {
                        dataTable.destroy();
                    }

                    const tbody = document.querySelector('#registrosTable tbody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No hay registros para mostrar</td></tr>';
                    } else {
                        data.forEach(registro => {
                            const row = tbody.insertRow();
                            row.innerHTML = `
                                <td>${new Date(registro.marca_temporal).toLocaleDateString('es-CO')}</td>
                                <td><strong>${registro.nombre}</strong></td>
                                <td><span class="material-badge">${registro.descripcion_material}</span></td>
                                <td><span class="estibas-count">${registro.cantidad_estibas}</span></td>
                                <td><span class="picking-badge ${getPickingClass(registro.tipo_picking)}">${registro.tipo_picking.toUpperCase()}</span></td>
                            `;
                        });

                        dataTable = $('#registrosTable').DataTable({
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                            },
                            order: [[0, 'desc']],
                            pageLength: 25
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function mostrarRegistrosMaterial(material) {
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;

            const url = `pi_reabastecimiento.php?action=registros_material&material=${encodeURIComponent(material)}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }

                    const tableTitle = document.getElementById('recordsTitle');
                    tableTitle.innerHTML = `<i class="fas fa-table" style="color: #FFD700;"></i> Registros: ${material}`;

                    if (dataTable) {
                        dataTable.destroy();
                    }

                    const tbody = document.querySelector('#registrosTable tbody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No hay registros para este material</td></tr>';
                    } else {
                        data.forEach(registro => {
                            const row = tbody.insertRow();
                            row.innerHTML = `
                                <td>${new Date(registro.marca_temporal).toLocaleDateString('es-CO')}</td>
                                <td><strong>${registro.nombre}</strong></td>
                                <td><span class="material-badge">${registro.descripcion_material}</span></td>
                                <td><span class="estibas-count">${registro.cantidad_estibas}</span></td>
                                <td><span class="picking-badge ${getPickingClass(registro.tipo_picking)}">${registro.tipo_picking.toUpperCase()}</span></td>
                            `;
                        });

                        dataTable = $('#registrosTable').DataTable({
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                            },
                            order: [[0, 'desc']],
                            pageLength: 25
                        });
                    }

                    document.querySelector('.records-section').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => console.error('Error:', error));
        }

        function getPickingClass(tipoPicking) {
            return tipoPicking === 'retornable' ? 'picking-retornable' : 'picking-no-retornable';
        }

        function cargarMateriales() {
            fetch('pi_reabastecimiento.php?action=obtener_materiales')
                .then(response => response.json())
                .then(data => {
                    if (data.error) return;

                    const select = document.getElementById('materialFiltro');
                    if (!select) return;

                    while (select.children.length > 1) {
                        select.removeChild(select.lastChild);
                    }

                    data.forEach(material => {
                        const option = document.createElement('option');
                        option.value = material.descripcion_material;
                        option.textContent = material.descripcion_material;
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error('Error al cargar materiales:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fechaInicio = document.getElementById('fechaInicio');
            const fechaFin = document.getElementById('fechaFin');
            const materialFiltro = document.getElementById('materialFiltro');

            if (fechaInicio) fechaInicio.addEventListener('change', actualizarGrafica);
            if (fechaFin) fechaFin.addEventListener('change', actualizarGrafica);
            if (materialFiltro) materialFiltro.addEventListener('change', actualizarGrafica);

            const nombreInput = document.querySelector('input[name="nombre"]');
            if (nombreInput) {
                nombreInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.toUpperCase();
                });
            }

            actualizarGrafica();
            cargarMateriales();
        });

        const form = document.getElementById('reabastecimientoForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const productosItems = document.querySelectorAll('.producto-item');
                const productos = [];
                let valid = true;

                productosItems.forEach((item, index) => {
                    const id = item.id.split('-')[1];
                    const descripcion = document.getElementById(`selectedProductName-${id}`).value;
                    const cantidad = item.querySelector('input[type="number"]').value;
                    const tipo_picking = item.querySelector('select').value;

                    if (!descripcion) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Producto faltante',
                            text: `Debes seleccionar un producto para el Producto #${parseInt(id)}`,
                            confirmButtonColor: '#FFD700'
                        });
                        valid = false;
                        return;
                    }

                    productos.push({
                        descripcion: descripcion,
                        cantidad: cantidad,
                        tipo_picking: tipo_picking
                    });
                });

                if (!valid || productos.length === 0) {
                    return;
                }

                const formData = new FormData(this);
                formData.append('action', 'agregar');
                formData.append('productos', JSON.stringify(productos));

                fetch('pi_reabastecimiento.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: `Se guardaron ${productos.length} producto(s) correctamente`,
                            confirmButtonColor: '#FFD700'
                        });
                        closeModal();
                        setTimeout(() => {
                            actualizarGrafica();
                            cargarMateriales();
                        }, 500);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#FFD700'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión',
                        confirmButtonColor: '#FFD700'
                    });
                });
            });
        }

        window.onclick = function(event) {
            const modal = document.getElementById('reabastecimientoModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>