<?php
require_once '../../core/config.php';
verificarLogin();
header('Content-Type: application/json');

try {
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    
    $sql = "SELECT id_material, material FROM productos WHERE 1=1";
    $params = [];
    
    
    if (!empty($search)) {
        $sql .= " AND (id_material LIKE ? OR material LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    
    $total_sql = str_replace('SELECT id_material, material', 'SELECT COUNT(*)', $sql);
    $stmt_total = $pdo->prepare($total_sql);
    $stmt_total->execute($params);
    $total_count = $stmt_total->fetchColumn();
    
    
    $sql .= " ORDER BY id_material ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $results = [];
    foreach ($productos as $producto) {
        $results[] = [
            'id' => $producto['id_material'],
            'text' => $producto['id_material'] . ' - ' . $producto['material'],
            'id_material' => $producto['id_material'],
            'material' => $producto['material']
        ];
    }
    
    
    echo json_encode([
        'results' => $results,
        'pagination' => [
            'more' => ($page * $limit) < $total_count
        ]
    ]);
    
} catch (Exception $e) {
    
    error_log("Error en get_productos1.php: " . $e->getMessage());
    
    echo json_encode([
        'results' => [],
        'pagination' => ['more' => false],
        'error' => 'Error al obtener los productos: ' . $e->getMessage()
    ]);
}
?>