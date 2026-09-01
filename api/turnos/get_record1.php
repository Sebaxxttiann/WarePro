<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');


error_reporting(E_ALL);
ini_set('display_errors', 0); 

try {
    
    require_once '../../core/config.php';
    
    
verificarLogin();
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }
    
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID no proporcionado');
    }
    
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        throw new Exception('ID inválido');
    }
    
    
    $stmt = $pdo->prepare("SELECT * FROM turnob_registros WHERE id = ? AND operacion_id = ? LIMIT 1");
    $stmt->execute([$id, getOperacionActiva()]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Registro no encontrado');
    }
    
    
    echo json_encode([
        'success' => true,
        'record' => $record,
        'message' => 'Registro cargado correctamente'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_type' => 'database'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'general'
    ], JSON_UNESCAPED_UNICODE);
}


exit;
?>
