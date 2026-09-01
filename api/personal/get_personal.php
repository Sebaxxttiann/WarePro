<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $cargo = isset($_GET['cargo']) ? $_GET['cargo'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    
    $sql = "SELECT nombre FROM personal_activo WHERE estado = 'activo' AND operacion_id = ?";
    $params = [getOperacionActiva()];
    
    
    if ($cargo === 'VERIFICADOR') {
        $sql .= " AND cargo = 'VERIFICADOR'";
    } elseif ($cargo === 'FACTURADOR') {
        $sql .= " AND (cargo = 'AUXILIAR DE FACTURACIÓN' OR cargo = 'FACTURACIÓN')";
    }
    
    
    if (!empty($search)) {
        $sql .= " AND nombre LIKE ?";
        $searchParam = "%$search%";
        $params[] = $searchParam;
    }
    
    
    $countSql = str_replace("SELECT nombre", "SELECT COUNT(*)", $sql);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    
    $sql .= " ORDER BY nombre ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $personal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $results = [];
    foreach ($personal as $persona) {
        $results[] = [
            'id' => $persona['nombre'],
            'text' => $persona['nombre']
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
        'error' => 'Error al obtener personal: ' . $e->getMessage()
    ]);
}
?>