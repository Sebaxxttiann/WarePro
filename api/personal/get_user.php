<?php
require_once '../../core/config.php';
verificarLogin();

if ($_SESSION['cargo'] != 'admin') {
    http_response_code(403);
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        header('Content-Type: application/json');
        echo json_encode($usuario);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID de usuario requerido']);
}
?>
