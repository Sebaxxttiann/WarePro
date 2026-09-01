<?php

ob_start();

header('Content-Type: application/json');
include '../../core/config.php';


verificarLogin();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'agregar':
                $fecha = limpiarDatos($_POST['fecha']);
                $supervisor = limpiarDatos($_POST['supervisor']);
                $vinipel_rollos = (int)($_POST['vinipel_rollos'] ?? 0);
                $termoencogido_rollos = (int)($_POST['termoencogido_rollos'] ?? 0);
                $carton_laminas = (int)($_POST['carton_laminas'] ?? 0);
                $isotanques = (int)($_POST['isotanques'] ?? 0);
                $iso_llenos = (int)($_POST['iso_llenos'] ?? 0);
                $iso_bueno = (int)($_POST['iso_bueno'] ?? 0);
                $iso_malo = (int)($_POST['iso_malo'] ?? 0);
                $estibas_tipo_a = limpiarDatos($_POST['estibas_tipo_a'] ?? '');
                $estibas_tipo_b = limpiarDatos($_POST['estibas_tipo_b'] ?? '');
                $estibas_tipo_c = limpiarDatos($_POST['estibas_tipo_c'] ?? '');
                $estibas_ara = limpiarDatos($_POST['estibas_ara'] ?? '');
                $estibas_d1 = limpiarDatos($_POST['estibas_d1'] ?? '');
                
                $stmt = $pdo->prepare("INSERT INTO insumos (fecha, supervisor, vinipel_rollos, termoencogido_rollos, carton_laminas, isotanques, iso_llenos, iso_bueno, iso_malo, estibas_tipo_a, estibas_tipo_b, estibas_tipo_c, estibas_ara, estibas_d1, operacion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if ($stmt->execute([$fecha, $supervisor, $vinipel_rollos, $termoencogido_rollos, $carton_laminas, $isotanques, $iso_llenos, $iso_bueno, $iso_malo, $estibas_tipo_a, $estibas_tipo_b, $estibas_tipo_c, $estibas_ara, $estibas_d1, getOperacionActiva()])) {
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Insumo agregado exitosamente']);
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Error al agregar insumo']);
                }
                break;
                
            case 'editar':
                $id = (int)($_POST['id'] ?? 0);
                $fecha = limpiarDatos($_POST['fecha']);
                $supervisor = limpiarDatos($_POST['supervisor']);
                $vinipel_rollos = (int)($_POST['vinipel_rollos'] ?? 0);
                $termoencogido_rollos = (int)($_POST['termoencogido_rollos'] ?? 0);
                $carton_laminas = (int)($_POST['carton_laminas'] ?? 0);
                $isotanques = (int)($_POST['isotanques'] ?? 0);
                $iso_llenos = (int)($_POST['iso_llenos'] ?? 0);
                $iso_bueno = (int)($_POST['iso_bueno'] ?? 0);
                $iso_malo = (int)($_POST['iso_malo'] ?? 0);
                $estibas_tipo_a = limpiarDatos($_POST['estibas_tipo_a'] ?? '');
                $estibas_tipo_b = limpiarDatos($_POST['estibas_tipo_b'] ?? '');
                $estibas_tipo_c = limpiarDatos($_POST['estibas_tipo_c'] ?? '');
                $estibas_ara = limpiarDatos($_POST['estibas_ara'] ?? '');
                $estibas_d1 = limpiarDatos($_POST['estibas_d1'] ?? '');
                
                $stmt = $pdo->prepare("UPDATE insumos SET fecha=?, supervisor=?, vinipel_rollos=?, termoencogido_rollos=?, carton_laminas=?, isotanques=?, iso_llenos=?, iso_bueno=?, iso_malo=?, estibas_tipo_a=?, estibas_tipo_b=?, estibas_tipo_c=?, estibas_ara=?, estibas_d1=? WHERE id=? AND operacion_id=?");

                if ($stmt->execute([$fecha, $supervisor, $vinipel_rollos, $termoencogido_rollos, $carton_laminas, $isotanques, $iso_llenos, $iso_bueno, $iso_malo, $estibas_tipo_a, $estibas_tipo_b, $estibas_tipo_c, $estibas_ara, $estibas_d1, $id, getOperacionActiva()])) {
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Insumo actualizado exitosamente']);
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar insumo']);
                }
                break;
                
            case 'obtener':
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("SELECT * FROM insumos WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$id, getOperacionActiva()]);
                $insumo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                ob_clean();
                if ($insumo) {
                    echo json_encode($insumo);
                } else {
                    echo json_encode(['error' => 'Insumo no encontrado']);
                }
                break;
                
            case 'eliminar':
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("DELETE FROM insumos WHERE id = ? AND operacion_id = ?");

                if ($stmt->execute([$id, getOperacionActiva()])) {
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Insumo eliminado exitosamente']);
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar insumo']);
                }
                break;
                
            default:
                ob_clean();
                echo json_encode(['error' => 'Acción no válida']);
                break;
        }
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
    }
} else {
    ob_clean();
    echo json_encode(['error' => 'Método no permitido']);
}


exit();
?>
