<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/con_universal.php';
header('Content-Type: application/json');





if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents("php://input");
    $datos = json_decode($raw_input, true);
    
    
    if (isset($datos['accion']) && $datos['accion'] === 'masivo') {
        $semana = trim($datos['semana'] ?? '');
        $turnoDestino = trim($datos['turnoDestino'] ?? '');
        $identificadores = $datos['identificadores'] ?? [];
        $horarios = $datos['horariosPorDefecto'] ?? [];
        
        if (empty($semana) || empty($turnoDestino) || empty($identificadores)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan datos para la asignación masiva.']);
            exit;
        }

        try {
            $pdo_warepro->beginTransaction();
            
            foreach ($identificadores as $id) {
                $check = $pdo_warepro->prepare("SELECT id FROM planeacion_semanal WHERE identificador = :id AND semana = :semana AND operacion_id = :operacion_id");
                $check->execute(['id' => $id, 'semana' => $semana, 'operacion_id' => getOperacionActiva()]);
                $existe = $check->fetch();

                if ($existe) {
                    $sql = "UPDATE planeacion_semanal
                            SET turno = :turno, estado = 'activo',
                                lunes = :lunes, martes = :martes, miercoles = :miercoles,
                                jueves = :jueves, viernes = :viernes, sabado = :sabado, domingo = :domingo
                            WHERE identificador = :id AND semana = :semana AND operacion_id = :operacion_id";
                } else {
                    $sql = "INSERT INTO planeacion_semanal
                            (identificador, semana, turno, estado, lunes, martes, miercoles, jueves, viernes, sabado, domingo, operacion_id)
                            VALUES (:id, :semana, :turno, 'activo', :lunes, :martes, :miercoles, :jueves, :viernes, :sabado, :domingo, :operacion_id)";
                }

                $stmt = $pdo_warepro->prepare($sql);
                $stmt->execute([
                    'id' => $id,
                    'semana' => $semana,
                    'turno' => $turnoDestino,
                    'lunes' => $horarios['lunes'] ?? '',
                    'martes' => $horarios['martes'] ?? '',
                    'miercoles' => $horarios['miercoles'] ?? '',
                    'jueves' => $horarios['jueves'] ?? '',
                    'viernes' => $horarios['viernes'] ?? '',
                    'sabado' => $horarios['sabado'] ?? '',
                    'domingo' => $horarios['domingo'] ?? '',
                    'operacion_id' => getOperacionActiva()
                ]);
            }
            
            $pdo_warepro->commit();
            echo json_encode(['exito' => true, 'mensaje' => 'Asignación masiva exitosa.']);
        } catch(PDOException $e) {
            $pdo_warepro->rollBack();
            echo json_encode(['exito' => false, 'mensaje' => 'Error BD: ' . $e->getMessage()]);
        }
        exit;
    }

    
    if (isset($datos['accion']) && $datos['accion'] === 'heredar_actividades') {
        $semana_actual = trim($datos['semana'] ?? '');

        if (empty($semana_actual)) {
            echo json_encode(['exito' => false, 'mensaje' => 'No se especificó la semana actual.']);
            exit;
        }

        try {
            
            $partes = explode('-W', $semana_actual);
            if (count($partes) !== 2) {
                echo json_encode(['exito' => false, 'mensaje' => 'Formato de semana inválido.']);
                exit;
            }

            $year = (int)$partes[0];
            $week = (int)$partes[1];
            
            $dto = new DateTime();
            $dto->setISODate($year, $week);
            $dto->modify('-7 days');
            $semana_anterior = $dto->format('Y-\WW');

            
            $stmt_ant = $pdo_warepro->prepare("
                SELECT identificador, actividad
                FROM planeacion_semanal
                WHERE semana = :semana_ant
                  AND estado != 'descartado'
                  AND actividad IS NOT NULL
                  AND actividad != ''
                  AND operacion_id = :operacion_id
            ");
            $stmt_ant->execute(['semana_ant' => $semana_anterior, 'operacion_id' => getOperacionActiva()]);
            $actividades_anteriores = $stmt_ant->fetchAll(PDO::FETCH_ASSOC);

            if (empty($actividades_anteriores)) {
                echo json_encode(['exito' => false, 'mensaje' => "No se encontraron actividades programadas en la semana $semana_anterior."]);
                exit;
            }

            $pdo_warepro->beginTransaction();
            
            $stmt_update = $pdo_warepro->prepare("
                UPDATE planeacion_semanal
                SET actividad = :actividad
                WHERE identificador = :id
                  AND semana = :semana_actual
                  AND estado != 'descartado'
                  AND operacion_id = :operacion_id
            ");

            $actualizados = 0;
            foreach ($actividades_anteriores as $row) {
                $stmt_update->execute([
                    'actividad' => $row['actividad'],
                    'id' => $row['identificador'],
                    'semana_actual' => $semana_actual,
                    'operacion_id' => getOperacionActiva()
                ]);
                
                if ($stmt_update->rowCount() > 0) {
                    $actualizados++;
                }
            }

            $pdo_warepro->commit();
            
            echo json_encode([
                'exito' => true, 
                'mensaje' => "Éxito. Se heredaron actividades a $actualizados empleados.",
                'actualizados' => $actualizados
            ]);

        
        } catch (Throwable $e) {
            if ($pdo_warepro->inTransaction()) {
                $pdo_warepro->rollBack();
            }
            echo json_encode(['exito' => false, 'mensaje' => 'Error Interno PHP: ' . $e->getMessage()]);
        }
        exit;
    }
   
    if (isset($datos['accion']) && $datos['accion'] === 'cargar_pdf') {
        $semana_actual = trim($datos['semana'] ?? '');
        $asignaciones = $datos['datos'] ?? [];
        $horarios = $datos['horariosDefault'] ?? [];

        if (empty($semana_actual) || empty($asignaciones)) {
            echo json_encode(['exito' => false, 'mensaje' => 'No hay datos válidos para procesar.']);
            exit;
        }

        try {
            $pdo_warepro->beginTransaction();
            
            $check = $pdo_warepro->prepare("SELECT id FROM planeacion_semanal WHERE identificador = :id AND semana = :semana AND operacion_id = :operacion_id");

            $stmt_update = $pdo_warepro->prepare("
                UPDATE planeacion_semanal
                SET turno = :turno, estado = 'activo',
                    lunes = :lunes, martes = :martes, miercoles = :miercoles,
                    jueves = :jueves, viernes = :viernes, sabado = :sabado, domingo = :domingo
                WHERE identificador = :id AND semana = :semana AND operacion_id = :operacion_id
            ");

            $stmt_insert = $pdo_warepro->prepare("
                INSERT INTO planeacion_semanal
                (identificador, semana, turno, estado, lunes, martes, miercoles, jueves, viernes, sabado, domingo, operacion_id)
                VALUES (:id, :semana, :turno, 'activo', :lunes, :martes, :miercoles, :jueves, :viernes, :sabado, :domingo, :operacion_id)
            ");

            $actualizados = 0;

            foreach ($asignaciones as $item) {
                $id = trim($item['cedula']);
                $turno = trim($item['turno']);

                if (!in_array($turno, ['A', 'B', 'C'])) {
                    $turno = 'Sin Turno';
                }

                
                $h_lunes = $horarios[$turno]['lunes'] ?? '';
                $h_martes = $horarios[$turno]['martes'] ?? '';
                $h_miercoles = $horarios[$turno]['miercoles'] ?? '';
                $h_jueves = $horarios[$turno]['jueves'] ?? '';
                $h_viernes = $horarios[$turno]['viernes'] ?? '';
                $h_sabado = $horarios[$turno]['sabado'] ?? '';
                $h_domingo = $horarios[$turno]['domingo'] ?? '';

                $params = [
                    'turno' => $turno,
                    'id' => $id,
                    'semana' => $semana_actual,
                    'lunes' => $h_lunes,
                    'martes' => $h_martes,
                    'miercoles' => $h_miercoles,
                    'jueves' => $h_jueves,
                    'viernes' => $h_viernes,
                    'sabado' => $h_sabado,
                    'domingo' => $h_domingo,
                    'operacion_id' => getOperacionActiva()
                ];

                $check->execute(['id' => $id, 'semana' => $semana_actual, 'operacion_id' => getOperacionActiva()]);
                if ($check->fetch()) {
                    $stmt_update->execute($params);
                } else {
                    $stmt_insert->execute($params);
                }
                $actualizados++;
            }

            $pdo_warepro->commit();
            
            echo json_encode(['exito' => true, 'actualizados' => $actualizados]);

        } catch (Throwable $e) {
            if ($pdo_warepro->inTransaction()) {
                $pdo_warepro->rollBack();
            }
            echo json_encode(['exito' => false, 'mensaje' => 'Error Interno PHP: ' . $e->getMessage()]);
        }
        exit;
    }

    
    $identificador = trim($datos['identificador'] ?? '');
    $semana = trim($datos['semana'] ?? '');
    $campo = trim($datos['campo'] ?? ''); 
    $valor = trim($datos['valor'] ?? '');

    if (!isset($datos['accion'])) {
        if (empty($identificador) || empty($semana) || empty($campo)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan datos obligatorios para el guardado individual.']);
            exit;
        }

        try {
            $check = $pdo_warepro->prepare("SELECT id FROM planeacion_semanal WHERE identificador = :id AND semana = :semana AND operacion_id = :operacion_id");
            $check->execute(['id' => $identificador, 'semana' => $semana, 'operacion_id' => getOperacionActiva()]);
            $existe = $check->fetch();

            $campos_validos = ['turno', 'actividad', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo', 'estado'];

            if ($existe) {
                if(in_array($campo, $campos_validos)) {
                    $sql = "UPDATE planeacion_semanal SET $campo = :valor WHERE identificador = :id AND semana = :semana AND operacion_id = :operacion_id";
                    $stmt = $pdo_warepro->prepare($sql);
                    $stmt->execute(['valor' => $valor, 'id' => $identificador, 'semana' => $semana, 'operacion_id' => getOperacionActiva()]);
                }
            } else {
                if(in_array($campo, $campos_validos)) {
                    $sql = "INSERT INTO planeacion_semanal (identificador, semana, $campo, operacion_id) VALUES (:id, :semana, :valor, :operacion_id)";
                    $stmt = $pdo_warepro->prepare($sql);
                    $stmt->execute(['id' => $identificador, 'semana' => $semana, 'valor' => $valor, 'operacion_id' => getOperacionActiva()]);
                }
            }
            echo json_encode(['exito' => true]);
        } catch(Throwable $e) {
            echo json_encode(['exito' => false, 'mensaje' => 'Error individual: ' . $e->getMessage()]);
        }
        exit;
    }
}