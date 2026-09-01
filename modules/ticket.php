<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../core/header.php';
require_once '../core/con_universal.php'; 

verificarLogin();

$nombre_usuario = $_SESSION['nombre'] ?? '';
$usuario_id = null;
$cedula = null;
$error_diagnostico = ''; 




function normalizarTexto($string) {
    $string = trim($string);
    
    $string = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'n'],
        $string
    );
    return strtolower($string);
}

function nombresCoinciden($nombre_db, $nombre_excel) {
    $n_db = normalizarTexto($nombre_db);
    $n_ex = normalizarTexto($nombre_excel);
    
    
    $partes_db = explode(' ', $n_db);
    
    
    foreach ($partes_db as $parte) {
        if (trim($parte) !== '' && strpos($n_ex, trim($parte)) === false) {
            return false; 
        }
    }
    return true; 
}

try {
    
    $stmtUser = $pdo_warepro->prepare("SELECT id, cedula FROM usuarios WHERE nombre = ? AND operacion_id = ? LIMIT 1");
    $stmtUser->execute([$nombre_usuario, getOperacionActiva()]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $usuario_id = $user['id'];
        $cedula = $user['cedula'];
    }

    
    $semana_actual = date('Y-\WW'); 
    
    $dias_semana_es = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
    $dia_hoy_str = $dias_semana_es[date('w')]; 
    
    $actividades_asignadas = '';
    $es_productiva = false;

    if ($cedula) {
        $stmtPlan = $pdo_warepro->prepare("SELECT actividad, lunes, martes, miercoles, jueves, viernes, sabado, domingo FROM planeacion_semanal WHERE identificador = ? AND semana = ? AND estado != 'descartado' AND operacion_id = ? LIMIT 1");
        $stmtPlan->execute([$cedula, $semana_actual, getOperacionActiva()]);
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            $horario_hoy = strtolower(trim($plan[$dia_hoy_str] ?? ''));
            
            if ($horario_hoy !== 'libre' && $horario_hoy !== '') {
                $actividad_a_evaluar = $plan['actividad'];
                
                if (!empty($actividad_a_evaluar)) {
                    $actividades_asignadas = $actividad_a_evaluar;
                    $lista_act = array_map('trim', explode(' y ', $actividades_asignadas));
                    
                    if (count($lista_act) > 0) {
                        $inQuery = implode(',', array_fill(0, count($lista_act), '?'));
                        $stmtAct = $pdo_warepro->prepare("SELECT COUNT(*) FROM actividades_ol WHERE nombre IN ($inQuery) AND es_productiva = 1");
                        $stmtAct->execute($lista_act);
                        
                        if ($stmtAct->fetchColumn() > 0) {
                            $es_productiva = true;
                        }
                    }
                }
            } else {
                $actividades_asignadas = 'Día Libre';
            }
        }
    }

    
    $tiene_registros_hoy = false;

    if ($es_productiva && $usuario_id) {
        $tablas_productivas = ['reempaque1', 'vertimiento', 'revision'];
        foreach ($tablas_productivas as $tabla) {
            try {
                $sql = "SELECT COUNT(*) FROM $tabla WHERE auxiliar_id = ? AND DATE(fecha_creacion) = CURDATE()";
                $stmtProd = $pdo_warepro->prepare($sql);
                $stmtProd->execute([$usuario_id]);
                
                if ($stmtProd->fetchColumn() > 0) {
                    $tiene_registros_hoy = true;
                    break;
                }
            } catch (PDOException $e) {
                
            }
        }
    }

    
    $dias_trabajados = 0;
    $dia_actual = (int)date('j');
    $mes_actual = (int)date('n');
    $anio_actual = (int)date('Y');
    
    for ($i = 1; $i <= $dia_actual; $i++) {
        $fecha_loop = mktime(0, 0, 0, $mes_actual, $i, $anio_actual);
        if (date('w', $fecha_loop) != 0) { 
            $dias_trabajados++;
        }
    }

    $sheet_gid = "1977388054";
    $sheet_url = "https://docs.google.com/spreadsheets/d/1ZheS95p0luzC2cSZYbG9ExOcOxYit1C0O4RNE27Ebjs/gviz/tq?tqx=out:json&gid=" . $sheet_gid;
    
    $json_string = @file_get_contents($sheet_url);
    $acis_reportes = 0;

    if (!empty($json_string)) {
        $start = strpos($json_string, '{');
        $end = strrpos($json_string, '}');
        
        if ($start !== false && $end !== false) {
            $json_puro = substr($json_string, $start, $end - $start + 1);
            $data_sheet = json_decode($json_puro, true);
            
            if (isset($data_sheet['table']['rows'])) {
                foreach ($data_sheet['table']['rows'] as $row) {
                    $es_su_reporte = false;
                    
                    if (isset($row['c'][11]) && $row['c'][11] !== null && isset($row['c'][11]['v'])) {
                        $celda_reportado_por = (string)$row['c'][11]['v'];
                        if (nombresCoinciden($nombre_usuario, $celda_reportado_por)) {
                            $es_su_reporte = true;
                        }
                    }
                    
                    if ($es_su_reporte) {
                        $es_del_mes = false;
                        $celda_fecha = $row['c'][0] ?? null; 
                        
                        if ($celda_fecha && isset($celda_fecha['v'])) {
                            $val_fecha = (string)$celda_fecha['v'];
                            if (preg_match('/Date\((\d+),\s*(\d+)/', $val_fecha, $matches)) {
                                $anio_excel = (int)$matches[1];
                                $mes_excel = (int)$matches[2] + 1;
                                
                                if ($anio_excel == $anio_actual && $mes_excel == $mes_actual) {
                                    $es_del_mes = true;
                                }
                            } else {
                                $es_del_mes = true;
                            }
                        } else {
                            $es_del_mes = true; 
                        }
                        
                        if ($es_del_mes) {
                            $acis_reportes++;
                        }
                    }
                }
            } else {
                $error_diagnostico .= "El formato JSON de Google cambió y no se pudo leer las filas.<br>";
            }
        } else {
             $error_diagnostico .= "No se encontró un JSON válido en la respuesta de Google.<br>";
        }
    } else {
        $error_diagnostico .= "No se pudo conectar a Google Sheets. Revisa que el Excel esté configurado como 'Cualquier usuario con el vínculo'.<br>";
    }

    
    $tiene_entrada = false;
    $tiene_salida = false;

    if ($cedula) {
        try {
            $stmtEnc = $pdo_safety->prepare("SELECT tipo_encuesta FROM encuesta_salud WHERE cedula = ? AND fecha_date = CURDATE()");
            $stmtEnc->execute([$cedula]);
            $encuestas_hoy = $stmtEnc->fetchAll(PDO::FETCH_COLUMN);

            if (in_array('Entrada', $encuestas_hoy)) $tiene_entrada = true;
            if (in_array('Salida', $encuestas_hoy)) $tiene_salida = true;

            $hora_actual = (int)date('G'); 
            if ($hora_actual >= 0 && $hora_actual <= 7) {
                if (!$tiene_entrada) {
                    $stmtEncAyer = $pdo_safety->prepare("SELECT COUNT(*) FROM encuesta_salud WHERE cedula = ? AND tipo_encuesta = 'Entrada' AND fecha_date = CURDATE() - INTERVAL 1 DAY");
                    $stmtEncAyer->execute([$cedula]);
                    if ($stmtEncAyer->fetchColumn() > 0) {
                        $tiene_entrada = true;
                    }
                }
            }
        } catch (PDOException $e) {
            $error_diagnostico .= "Error en verificación de Encuesta Salud (SAFETY-OL): " . $e->getMessage() . "<br>";
        }
    }

    
    $pausas_activas_reportadas = 0;
    try {
        
        $stmtPausas = $pdo_escuela->prepare("SELECT nombre FROM registros_actividad WHERE mes = ? AND ano = ?");
        $stmtPausas->execute([$mes_actual, $anio_actual]);
        $registros_pausas = $stmtPausas->fetchAll(PDO::FETCH_ASSOC);

        
        foreach ($registros_pausas as $rp) {
            if (nombresCoinciden($nombre_usuario, $rp['nombre'])) {
                $pausas_activas_reportadas++;
            }
        }
    } catch (PDOException $e) {
        $error_diagnostico .= "Error en verificación de Pausas Activas (ESCUELA): " . $e->getMessage() . "<br>";
    }

} catch (PDOException $eGeneral) {
    $error_diagnostico .= "Error crítico de Base de Datos: " . $eGeneral->getMessage();
}


$cumple_prod = (!$es_productiva) || ($es_productiva && $tiene_registros_hoy);
$cumple_acis = ($acis_reportes >= $dias_trabajados);
$cumple_encuesta = ($tiene_entrada && $tiene_salida);
$cumple_pausa = ($pausas_activas_reportadas >= $dias_trabajados);

$estado_ok = false;
$mensaje_principal = '';
$color_bg = '';
$color_shadow = '';
$icono = '';
$causales = [];

if ($cumple_prod && $cumple_acis && $cumple_encuesta && $cumple_pausa) {
    $estado_ok = true;
    $mensaje_principal = 'TRABAJADOR AL DÍA';
    $color_bg = 'bg-green-500';
    $color_shadow = 'shadow-green-500/40';
    $icono = 'fa-check-circle';
    
    if (!$es_productiva) {
        $causales[] = "Sin asignación productiva para hoy (o día libre).";
    } else {
        $causales[] = "Productividades registradas exitosamente.";
    }
    $causales[] = "Reportes ACIS al día ($acis_reportes de $dias_trabajados).";
    $causales[] = "Encuestas de salud (Entrada y Salida) registradas.";
    $causales[] = "Pausas activas al día ($pausas_activas_reportadas de $dias_trabajados).";
    
} else {
    $estado_ok = false;
    $mensaje_principal = 'USUARIO NO AL DÍA';
    $color_bg = 'bg-red-500';
    $color_shadow = 'shadow-red-500/40';
    $icono = 'fa-times-circle';
    
    if (!$cumple_prod) {
        $causales[] = "No ha registrado sus productividades de hoy en el sistema.";
    }
    if (!$cumple_acis) {
        $causales[] = "Usuario tiene $acis_reportes reportes ACIS y debería tener mínimo $dias_trabajados.";
    }
    if (!$cumple_encuesta) {
        if (!$tiene_entrada && !$tiene_salida) {
            $causales[] = "Falta registrar encuesta de Entrada y Salida en SAFETY-OL.";
        } elseif (!$tiene_entrada) {
            $causales[] = "Falta registrar encuesta de Entrada en SAFETY-OL.";
        } elseif (!$tiene_salida) {
            $causales[] = "Falta registrar encuesta de Salida en SAFETY-OL.";
        }
    }
    if (!$cumple_pausa) {
        $causales[] = "Usuario tiene $pausas_activas_reportadas pausas activas y debería tener mínimo $dias_trabajados.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Estado - WARE PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
        .ticket-bg {
            background-image: radial-gradient(circle at top right, rgba(255, 215, 0, 0.1), transparent 40%),
                              radial-gradient(circle at bottom left, rgba(255, 215, 0, 0.1), transparent 40%);
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col">

    <div class="flex-1 flex flex-col items-center justify-center p-4">
        
        <?php if (!empty($error_diagnostico)): ?>
        <div class="w-full max-w-lg mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm">
            <h3 class="font-bold flex items-center gap-2"><i class="fas fa-bug"></i> Diagnóstico del Sistema</h3>
            <p class="text-sm mt-1"><?php echo $error_diagnostico; ?></p>
        </div>
        <?php endif; ?>

        
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-xl overflow-hidden border border-gray-100 ticket-bg relative">
            
            <div class="h-3 <?php echo $estado_ok ? 'bg-green-400' : 'bg-red-400'; ?> w-full transition-colors duration-300"></div>

            <div class="p-8 text-center">
                
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-50 rounded-full mb-4 border-2 <?php echo $estado_ok ? 'border-green-400' : 'border-red-400'; ?> shadow-sm transition-colors">
                        <i class="fas fa-id-badge text-2xl text-gray-800"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($nombre_usuario); ?></h1>
                    <p class="text-gray-500 text-sm font-mono mt-1"><i class="fas fa-fingerprint mr-1"></i> ID: <?php echo htmlspecialchars($cedula ?? 'Sin asignar'); ?></p>
                    <p class="text-gray-400 text-xs mt-2 font-medium uppercase tracking-widest"><?php echo date('d M Y'); ?></p>
                </div>

                <hr class="border-dashed border-gray-200 mb-6">

                <div class="mb-6 bg-gray-50 rounded-2xl p-4 text-left border border-gray-100">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Información de Operación</h3>
                    
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 font-medium">Actividad Hoy:</span>
                            <span class="text-gray-900 font-bold text-right ml-2"><?php echo $actividades_asignadas ? htmlspecialchars($actividades_asignadas) : 'Ninguna / Libre'; ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 font-medium">Req. Producción:</span>
                            <span class="font-bold <?php echo $es_productiva ? 'text-blue-600' : 'text-gray-500'; ?>">
                                <?php echo $es_productiva ? 'SÍ' : 'NO'; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 font-medium">Días hábiles del mes:</span>
                            <span class="font-bold text-gray-900"><?php echo $dias_trabajados; ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-2">
                    <div class="w-full <?php echo $color_bg; ?> text-white rounded-2xl p-5 shadow-lg <?php echo $color_shadow; ?> transform transition-all hover:scale-[1.02] cursor-default flex flex-col items-center justify-center min-h-[120px]">
                        <i class="fas <?php echo $icono; ?> text-4xl mb-2"></i>
                        <h2 class="text-2xl sm:text-3xl font-black uppercase tracking-wide text-center">
                            <?php echo $mensaje_principal; ?>
                        </h2>
                    </div>
                    
                    <div class="mt-5 text-sm font-medium text-gray-700 bg-gray-50 py-4 px-5 rounded-xl border border-gray-200 text-left w-full shadow-sm">
                        <p class="font-bold mb-2 uppercase text-xs tracking-wider <?php echo $estado_ok ? 'text-green-600' : 'text-red-600'; ?>">
                            <i class="fas <?php echo $estado_ok ? 'fa-info-circle' : 'fa-exclamation-triangle'; ?> mr-1"></i>
                            <?php echo $estado_ok ? 'Detalles de Cumplimiento' : 'Causales de Bloqueo'; ?>
                        </p>
                        <ul class="space-y-1.5 ml-1">
                            <?php foreach ($causales as $causa): ?>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-caret-right text-gray-400 mt-1"></i>
                                    <span><?php echo $causa; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

            </div>
            
            <div class="absolute -left-4 top-1/2 w-8 h-8 bg-gray-50 rounded-full border-r border-gray-200"></div>
            <div class="absolute -right-4 top-1/2 w-8 h-8 bg-gray-50 rounded-full border-l border-gray-200"></div>
        </div>
    </div>

</body>
</html>