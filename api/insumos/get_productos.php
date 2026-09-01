<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    
    $sql = "SELECT id_material, material FROM productos WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (id_material LIKE ? OR material LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    
    $countSql = str_replace("SELECT id_material, material", "SELECT COUNT(*)", $sql);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    
    $sql .= " ORDER BY material ASC LIMIT ? OFFSET ?";
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
            'more' => ($page * $limit) < $totalCount
        ],
        'total_count' => $totalCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al obtener productos: ' . $e->getMessage()
    ]);
}
?>