<?php
require_once '../../core/config.php';


verificarLogin();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}


$uploadDir = '../../uploads/informativos/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createInformativo();
        break;
    case 'list':
        listInformativos();
        break;
    case 'get':
        getInformativo();
        break;
    case 'update':
        updateInformativo();
        break;
    case 'delete':
        deleteInformativo();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function createInformativo() {
    global $pdo;
    
    try {
        $texto = limpiarDatos($_POST['texto']);
        $usuario_id = $_SESSION['usuario_id'];
        $imagen = null;
        
        
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = uploadImage($_FILES['imagen']);
            if (!$imagen) {
                echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
                return;
            }
        }
        
        $sql = "INSERT INTO cargar_informativo (texto, imagen, usuario_id, operacion_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$texto, $imagen, $usuario_id, getOperacionActiva()]);
        
        echo json_encode(['success' => true, 'message' => 'Informativo creado exitosamente']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function listInformativos() {
    global $pdo;
    
    try {
        $sql = "SELECT ci.*, u.nombre as usuario_nombre
                FROM cargar_informativo ci
                LEFT JOIN usuarios u ON ci.usuario_id = u.id
                WHERE ci.activo = 1 AND ci.operacion_id = ?
                ORDER BY ci.fecha_creacion DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([getOperacionActiva()]);
        $informativos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $informativos]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getInformativo() {
    global $pdo;
    
    try {
        $id = $_GET['id'] ?? 0;
        
        $sql = "SELECT ci.*, u.nombre as usuario_nombre
                FROM cargar_informativo ci
                LEFT JOIN usuarios u ON ci.usuario_id = u.id
                WHERE ci.id = ? AND ci.activo = 1 AND ci.operacion_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, getOperacionActiva()]);
        $informativo = $stmt->fetch();
        
        if ($informativo) {
            echo json_encode(['success' => true, 'data' => $informativo]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Informativo no encontrado']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function updateInformativo() {
    global $pdo;
    
    
    if ($_SESSION['cargo'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
        return;
    }
    
    try {
        $id = $_POST['id'] ?? 0;
        $texto = limpiarDatos($_POST['texto']);
        $imagen_actual = null;
        
        
        $sql = "SELECT imagen FROM cargar_informativo WHERE id = ? AND operacion_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, getOperacionActiva()]);
        $result = $stmt->fetch();
        if ($result) {
            $imagen_actual = $result['imagen'];
        }
        
        
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nueva_imagen = uploadImage($_FILES['imagen']);
            if ($nueva_imagen) {
                
                if ($imagen_actual && file_exists('../../uploads/informativos/' . $imagen_actual)) {
                    unlink('../../uploads/informativos/' . $imagen_actual);
                }
                $imagen_actual = $nueva_imagen;
            }
        }
        
        $sql = "UPDATE cargar_informativo SET texto = ?, imagen = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ? AND operacion_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$texto, $imagen_actual, $id, getOperacionActiva()]);
        
        echo json_encode(['success' => true, 'message' => 'Informativo actualizado exitosamente']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteInformativo() {
    global $pdo;
    
    
    if ($_SESSION['cargo'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
        return;
    }
    
    try {
        $id = $_GET['id'] ?? 0;
        
        
        $sql = "SELECT imagen FROM cargar_informativo WHERE id = ? AND operacion_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, getOperacionActiva()]);
        $result = $stmt->fetch();


        $sql = "UPDATE cargar_informativo SET activo = 0 WHERE id = ? AND operacion_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, getOperacionActiva()]);
        
        
        if ($result && $result['imagen'] && file_exists('../../uploads/informativos/' . $result['imagen'])) {
            unlink('../../uploads/informativos/' . $result['imagen']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Informativo eliminado exitosamente']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function uploadImage($file) {
    $uploadDir = '../../uploads/informativos/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; 
    
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('info_') . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}


if (!function_exists('limpiarDatos')) {
    function limpiarDatos($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
}
?>
