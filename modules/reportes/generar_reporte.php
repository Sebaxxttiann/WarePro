<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


try {
    $stmt = $pdo->prepare("SELECT cedula, nombre, cargo FROM usuarios WHERE activo = 1 AND operacion_id = ? ORDER BY nombre ASC");
    $stmt->execute([getOperacionActiva()]);
    $todos_los_usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error cargando usuarios: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['descargar'])) {
    
    
    $meses = $_POST['mes'] ?? [];
    $cargos = $_POST['cargo'] ?? [];
    $indicadores = $_POST['indicador'] ?? [];
    $top1_cedulas = $_POST['top1'] ?? [];
    $top2_cedulas = $_POST['top2'] ?? [];
    $top3_cedulas = $_POST['top3'] ?? [];

    if (empty($meses)) {
        die("Debes agregar al menos una premiación.");
    }

    
    $año = '2026';
    $proceso = 'ALMACEN';
    $contratista = 'LIS';
    $operador = 'OL';

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    
    $encabezados = ['Año', 'Mes', 'Proceso', 'Contratista', 'Operador', 'Indicador', 'Nombres y apellidos', 'Cedula', 'Cargo', 'Resultado', 'Puesto'];
    $columna = 'A';
    foreach ($encabezados as $titulo) {
        $sheet->setCellValue($columna . '1', $titulo);
        $columna++;
    }

    $fila = 2; 

    
    for ($i = 0; $i < count($meses); $i++) {
        $mes_actual = limpiarDatos($meses[$i]);
        $cargo_actual = limpiarDatos($cargos[$i]);
        $indicador_actual = strtoupper(limpiarDatos($indicadores[$i]));
        
        $c1 = limpiarDatos($top1_cedulas[$i]);
        $c2 = limpiarDatos($top2_cedulas[$i]);
        $c3 = limpiarDatos($top3_cedulas[$i]);

        $ganadores = [];
        $resto = [];

        
        foreach ($todos_los_usuarios as $u) {
            
            if ($u['cedula'] === $c1) {
                $u['resultado'] = rand(98, 100); 
                $u['puesto'] = 1;
                $ganadores[1] = $u;
            } elseif ($u['cedula'] === $c2) {
                $u['resultado'] = rand(94, 97);  
                $u['puesto'] = 2;
                $ganadores[2] = $u;
            } elseif ($u['cedula'] === $c3) {
                $u['resultado'] = rand(90, 93);  
                $u['puesto'] = 3;
                $ganadores[3] = $u;
            } 
            
            elseif (strtolower($u['cargo']) === strtolower($cargo_actual)) {
                $u['resultado'] = rand(40, 89); 
                $resto[] = $u;
            }
        }

        
        usort($resto, function($a, $b) {
            return $b['resultado'] <=> $a['resultado'];
        });

        
        $puesto_actual = 4;
        foreach ($resto as &$r) {
            $r['puesto'] = $puesto_actual;
            $puesto_actual++;
        }
        unset($r);

        
        $usuarios_finales = [];
        if (isset($ganadores[1])) $usuarios_finales[] = $ganadores[1];
        if (isset($ganadores[2])) $usuarios_finales[] = $ganadores[2];
        if (isset($ganadores[3])) $usuarios_finales[] = $ganadores[3];
        $usuarios_finales = array_merge($usuarios_finales, $resto);

        
        foreach ($usuarios_finales as $u) {
            $sheet->setCellValue('A' . $fila, $año);
            $sheet->setCellValue('B' . $fila, $mes_actual);
            $sheet->setCellValue('C' . $fila, $proceso);
            $sheet->setCellValue('D' . $fila, $contratista);
            $sheet->setCellValue('E' . $fila, $operador);
            $sheet->setCellValue('F' . $fila, $indicador_actual);
            $sheet->setCellValue('G' . $fila, $u['nombre']);
            $sheet->setCellValue('H' . $fila, $u['cedula']);
            
            $sheet->setCellValue('I' . $fila, strtoupper($u['cargo']));
            $sheet->setCellValue('J' . $fila, $u['resultado'] . '%');
            $sheet->setCellValue('K' . $fila, $u['puesto']);
            $fila++;
        }
    }

    
    $nombre_archivo = "Mega_Reporte_Premiaciones_" . date('Ymd_His') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mega Generador de Premiaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header-title { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 30px 0; border-radius: 0 0 20px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card-premiacion { background: white; border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 5px solid #28a745; transition: transform 0.2s; }
        .card-premiacion:hover { transform: translateY(-3px); }
        .btn-add { background-color: #e9ecef; color: #495057; border: 2px dashed #ced4da; border-radius: 15px; width: 100%; padding: 15px; font-weight: bold; transition: all 0.3s; }
        .btn-add:hover { background-color: #dee2e6; border-color: #adb5bd; }
        .btn-mega { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 15px 30px; font-size: 1.2rem; font-weight: bold; border-radius: 10px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); width: 100%; }
        .btn-mega:hover { background: linear-gradient(135deg, #218838 0%, #1e7e34 100%); color: white; }
        .btn-remove { color: #dc3545; font-weight: bold; text-decoration: none; font-size: 0.9rem; }
        .btn-remove:hover { color: #bd2130; }
        .top-badge { padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.8rem; }
        .badge-1 { background-color: #ffd700; color: #856404; }
        .badge-2 { background-color: #e0e0e0; color: #383d41; }
        .badge-3 { background-color: #cd7f32; color: #fff; }
    </style>
</head>
<body>

<div class="header-title text-center">
    <h1 class="fw-bold"> Generador de Premiaciones</h1>
    <p class="mb-0">Configura tus indicadores, asigna los podios y genera la data masiva.</p>
</div>

<div class="container mb-5">
    <form method="POST" action="" id="megaForm">
        
        <div id="contenedor-tarjetas">
            </div>

        <div class="row mb-4">
            <div class="col-12">
                <button type="button" class="btn btn-add" id="btnAñadir">
                    ➕ Añadir otra premiación (Nuevo Mes, Cargo o Indicador)
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <button type="submit" name="descargar" class="btn btn-mega">
                    📥 Generar Mega Excel
                </button>
            </div>
        </div>

    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    
    const usuarios_db = <?php echo json_encode($todos_los_usuarios); ?>;
    let indexTarjeta = 0;

    
    let opcionesUsuariosHTML = '<option value="" disabled selected>Buscar empleado...</option>';
    usuarios_db.forEach(u => {
        
        opcionesUsuariosHTML += `<option value="${u.cedula}">${u.nombre} - ${u.cargo.toUpperCase()} (${u.cedula})</option>`;
    });

    
    function agregarTarjeta() {
        indexTarjeta++;
        
        const html = `
        <div class="card card-premiacion p-4" id="tarjeta_${indexTarjeta}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-primary mb-0">📌 Configuración #${indexTarjeta}</h5>
                ${indexTarjeta > 1 ? `<a href="#" class="btn-remove" onclick="eliminarTarjeta(${indexTarjeta}); return false;">❌ Eliminar</a>` : ''}
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Mes</label>
                    <select name="mes[]" class="form-select" required>
                        <option value="ENERO">Enero</option>
                        <option value="FEBRERO">Febrero</option>
                        <option value="MARZO">Marzo</option>
                        <option value="ABRIL">Abril</option>
                        <option value="MAYO">Mayo</option>
                        <option value="JUNIO">Junio</option>
                        <option value="JULIO">Julio</option>
                        <option value="AGOSTO">Agosto</option>
                        <option value="SEPTIEMBRE">Septiembre</option>
                        <option value="OCTUBRE">Octubre</option>
                        <option value="NOVIEMBRE">Noviembre</option>
                        <option value="DICIEMBRE">Diciembre</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Rellenar Excel con Cargo:</label>
                    <select name="cargo[]" class="form-select" required>
                        <option value="" disabled selected>Seleccione...</option>
                        <option value="auxiliar">Auxiliares</option>
                        <option value="verificador">Verificadores</option>
                        <option value="operador">Operadores</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Indicador a premiar</label>
                    <input type="text" name="indicador[]" class="form-control" placeholder="Ej: REEMPAQUE" required>
                </div>
            </div>

            <div class="row g-3 p-3 bg-light rounded border">
                <div class="col-md-4">
                    <label class="form-label fw-bold"><span class="top-badge badge-1">🥇 1er Puesto</span></label>
                    <select name="top1[]" class="form-control select2-busqueda" required>
                        ${opcionesUsuariosHTML}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><span class="top-badge badge-2">🥈 2do Puesto</span></label>
                    <select name="top2[]" class="form-control select2-busqueda" required>
                        ${opcionesUsuariosHTML}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><span class="top-badge badge-3">🥉 3er Puesto</span></label>
                    <select name="top3[]" class="form-control select2-busqueda" required>
                        ${opcionesUsuariosHTML}
                    </select>
                </div>
            </div>
        </div>
        `;

        $('#contenedor-tarjetas').append(html);
        
        
        $(`#tarjeta_${indexTarjeta} .select2-busqueda`).select2({
            width: '100%',
            placeholder: "Buscar empleado en toda la BD..."
        });
    }

    
    function eliminarTarjeta(id) {
        $(`#tarjeta_${id}`).slideUp(300, function() { $(this).remove(); });
    }

    
    $(document).ready(function() {
        agregarTarjeta();

        $('#btnAñadir').click(function() {
            agregarTarjeta();
        });
    });
</script>
</body>
</html>