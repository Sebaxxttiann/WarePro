<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/con_universal.php';
header('Content-Type: application/json');


$datos = json_decode(file_get_contents("php://input"), true);
$accion = $datos['accion'] ?? ($_GET['accion'] ?? '');

try {
    if ($accion === 'crear') {
        
        $nombre = limpiarDatos($datos['nombre'] ?? '');
        
        if (empty($nombre)) {
            echo json_encode(['exito' => false, 'mensaje' => 'El nombre no puede estar vacío.']);
            exit;
        }
        
        $sql = "INSERT INTO actividades_ol (nombre) VALUES (:nombre)";
        $stmt = $pdo_warepro->prepare($sql);
        $stmt->execute(['nombre' => $nombre]);
        
        echo json_encode(['exito' => true, 'mensaje' => 'Actividad creada correctamente.']);
        
    } elseif ($accion === 'listar') {
        
        $sql = "SELECT id, nombre, es_productiva FROM actividades_ol WHERE estado = 1 ORDER BY nombre ASC";
        $stmt = $pdo_warepro->query($sql);
        $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['exito' => true, 'datos' => $actividades]);
        
    } elseif ($accion === 'guardar_productivas') {
        
        $productivas = $datos['productivas'] ?? [];
        
        
        $pdo_warepro->query("UPDATE actividades_ol SET es_productiva = 0");
        
        
        if (!empty($productivas)) {
            $inQuery = implode(',', array_fill(0, count($productivas), '?'));
            $sql = "UPDATE actividades_ol SET es_productiva = 1 WHERE nombre IN ($inQuery)";
            $stmt = $pdo_warepro->prepare($sql);
            $stmt->execute($productivas);
        }
        
        echo json_encode(['exito' => true, 'mensaje' => 'Actividades productivas guardadas correctamente.']);
        
    } else {
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
    }
} catch(PDOException $e) {
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>