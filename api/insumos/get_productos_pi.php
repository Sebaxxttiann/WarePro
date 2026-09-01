<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');

if (isset($_GET['action']) && $_GET['action'] == 'obtener_productos') {
    try {
        $busqueda = $_GET['busqueda'] ?? '';
        $limite = (int)($_GET['limite'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);
        
        
        $limite = max(1, min($limite, 500)); 
        $offset = max(0, $offset);
        
        if (empty($busqueda)) {
            
            $sql = "SELECT id_material, material FROM productos ORDER BY material ASC LIMIT $offset, $limite";
            $params = [];
            
            
            $sqlCount = "SELECT COUNT(*) as total FROM productos";
            $stmtCount = $pdo->prepare($sqlCount);
            $stmtCount->execute();
            $totalProductos = $stmtCount->fetch()['total'];
        } else {
            
            $sql = "SELECT id_material, material FROM productos 
                    WHERE id_material LIKE ? OR material LIKE ? 
                    ORDER BY 
                        CASE WHEN id_material = ? THEN 1
                             WHEN material = ? THEN 2
                             WHEN id_material LIKE ? THEN 3
                             WHEN material LIKE ? THEN 4
                             ELSE 5 
                        END, material ASC
                    LIMIT 200"; 
            $params = [
                "%$busqueda%", "%$busqueda%",
                $busqueda, $busqueda,
                "$busqueda%", "$busqueda%"
            ];
            
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
            'offset' => $offset,
            'limite' => $limite,
            'success' => true
        ];

        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Error al obtener productos: ' . $e->getMessage(),
            'success' => false,
            'productos' => [],
            'total' => 0
        ]);
    }
} else {
    echo json_encode([
        'error' => 'Acción no válida',
        'success' => false,
        'productos' => [],
        'total' => 0
    ]);
}
?>