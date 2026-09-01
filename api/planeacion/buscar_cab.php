<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/con_universal.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = json_decode(file_get_contents("php://input"), true);
    $identificador = limpiarDatos($datos['identificador'] ?? '');

    try {
        if (!empty($identificador)) {
            
            $sql = "SELECT e1.* 
                    FROM empleados e1
                    INNER JOIN (SELECT identificador, MAX(id) AS max_id FROM empleados GROUP BY identificador) e2 
                    ON e1.id = e2.max_id 
                    WHERE e1.identificador = :identificador LIMIT 1";
            $stmt = $pdo_cab->prepare($sql);
            $stmt->execute(['identificador' => $identificador]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($empleado) {
                echo json_encode(['exito' => true, 'datos' => [$empleado]]);
            } else {
                echo json_encode(['exito' => false, 'mensaje' => 'No se encontró el empleado.']);
            }
        } else {
            
            
            $sql = "SELECT e1.identificador, e1.nombres, e1.apellidos, e1.cargo 
                    FROM empleados e1
                    INNER JOIN (SELECT identificador, MAX(id) AS max_id FROM empleados GROUP BY identificador) e2 
                    ON e1.id = e2.max_id 
                    WHERE e1.cargo LIKE '%OL%' AND LOWER(e1.cargo) != 'control'
                    ORDER BY e1.nombres ASC";
            $stmt = $pdo_cab->query($sql);
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['exito' => true, 'datos' => $empleados]);
        }
    } catch(PDOException $e) {
        echo json_encode(['exito' => false, 'mensaje' => 'Error de BD: ' . $e->getMessage()]);
    }
}
?>