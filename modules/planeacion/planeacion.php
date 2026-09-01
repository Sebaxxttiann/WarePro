<?php
require_once '../../core/header.php';
require_once '../../core/con_universal.php';
verificarLogin();


$semana_actual = date('Y-\WW'); 
$semana_seleccionada = $_GET['semana'] ?? $semana_actual;


$query_empleados = "
    SELECT e1.* 
    FROM empleados e1
    INNER JOIN (
        SELECT identificador, MAX(id) AS max_id 
        FROM empleados 
        GROUP BY identificador
    ) e2 ON e1.id = e2.max_id
    WHERE e1.cargo LIKE '%OL%' 
      AND LOWER(e1.cargo) != 'control'
";
$stmt = $pdo_cab->query($query_empleados);
$empleados_base = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmtWare = $pdo_warepro->prepare("SELECT * FROM planeacion_semanal WHERE semana = :semana AND estado != 'descartado' AND operacion_id = :operacion_id");
$stmtWare->execute(['semana' => $semana_seleccionada, 'operacion_id' => getOperacionActiva()]);
$planeacion_guardada = [];
while($row = $stmtWare->fetch(PDO::FETCH_ASSOC)) {
    $planeacion_guardada[$row['identificador']] = $row;
}


$turnos = [
    'C' => [], 
    'A' => [], 
    'B' => [], 
    'Sin Turno' => []
];

foreach ($empleados_base as $emp) {
    $id = $emp['identificador'];
    $turno_asignado = isset($planeacion_guardada[$id]) ? $planeacion_guardada[$id]['turno'] : 'Sin Turno';
    if (!isset($turnos[$turno_asignado])) $turno_asignado = 'Sin Turno';
    
    $emp['datos_planeacion'] = $planeacion_guardada[$id] ?? null;
    $turnos[$turno_asignado][] = $emp;
}


foreach ($turnos as $tipo => &$lista) {
    usort($lista, function($a, $b) {
        $ordenA = $a['datos_planeacion']['orden'] ?? 999999;
        $ordenB = $b['datos_planeacion']['orden'] ?? 999999;
        
        if ($ordenA == $ordenB) {
            return $a['id'] <=> $b['id']; 
        }
        return $ordenA <=> $ordenB;
    });
}
unset($lista);


$horarios_default = [
    'A' => ['lunes' => '06:00 - 14:00', 'martes' => '06:00 - 14:00', 'miercoles' => '06:00 - 14:00', 'jueves' => '06:00 - 14:00', 'viernes' => '06:00 - 14:00', 'sabado' => '06:00 - 14:00', 'domingo' => 'Libre'],
    'B' => ['lunes' => '14:00 - 22:00', 'martes' => '14:00 - 22:00', 'miercoles' => '14:00 - 22:00', 'jueves' => '14:00 - 22:00', 'viernes' => '14:00 - 22:00', 'sabado' => '14:00 - 22:00', 'domingo' => 'Libre'],
    'C' => ['lunes' => '22:00 - 06:00', 'martes' => '22:00 - 06:00', 'miercoles' => '22:00 - 06:00', 'jueves' => '22:00 - 06:00', 'viernes' => '22:00 - 06:00', 'sabado' => 'Libre', 'domingo' => '22:00 - 06:00'],
    'Sin Turno' => ['lunes' => '', 'martes' => '', 'miercoles' => '', 'jueves' => '', 'viernes' => '', 'sabado' => '', 'domingo' => '']
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planeación Semanal OL - WARE PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>
        
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    </script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { brand: { 400: '#FFD700', 500: '#FFA500' }, dark: '#1a1a1a' }
                }
            }
        }
        const horariosDefault = <?= json_encode($horarios_default) ?>;
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .horario-input { 
            width: 100%; min-width: 90px; text-align: center; border: 1px solid transparent; background: transparent; 
            font-weight: 500; font-size: 0.85rem; border-radius: 0.25rem; outline: none; padding: 4px; transition: all 0.2s;
        }
        .horario-input:focus { border-color: #FFD700; background: #fff; box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2); }
        .horario-input:hover:not(:focus) { background: rgba(255,255,255,0.5); border-color: #e2e8f0; }
        
        .celda-libre { background-color: #fee2e2 !important; color: #991b1b !important; font-weight: bold; border-color: #fecaca !important; }
        .fila-seleccionable.selected { border-color: #FFA500; background-color: #fffbeb; }
        
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
        .sortable-ghost { opacity: 0.4; background-color: #fef3c7; }
    </style>
</head>
<body class="text-gray-800 antialiased min-h-screen pb-20">

    <div class="max-w-[1800px] mx-auto p-4 md:p-8">
        
        
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 border-l-[5px] border-brand-400 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-1 flex items-center gap-3">
                    <i class="fas fa-calendar-alt text-brand-400"></i> Planeación Semanal
                </h1>
                <p class="text-gray-500 text-sm">Operaciones Logísticas - Asignación de turnos, horarios y actividades</p>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                <form method="GET" class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200">
                    <label for="semana" class="font-bold text-gray-600 text-sm uppercase tracking-wider"><i class="fas fa-calendar-week mr-2 text-brand-500"></i>Semana:</label>
                    <input type="week" id="semana" name="semana" class="bg-transparent outline-none font-bold text-gray-800 cursor-pointer" value="<?= htmlspecialchars($semana_seleccionada) ?>" onchange="this.form.submit()">
                </form>
                
                
                <button onclick="heredarActividadesAnteriores(event)" class="bg-white border-2 border-blue-200 hover:border-blue-400 text-blue-700 px-5 py-2 rounded-lg font-bold transition-all flex items-center gap-2 shadow-sm">
                    <i class="fas fa-history text-blue-500"></i> Heredar Actividades Ant.
                </button>

                
                <button onclick="abrirModalProductivas()" class="bg-white border-2 border-green-200 hover:border-green-400 text-green-700 px-5 py-2 rounded-lg font-bold transition-all flex items-center gap-2 shadow-sm">
                    <i class="fas fa-chart-line text-green-500"></i> Act. Productivas
                </button>

                <button onclick="openModal('modalActividades')" class="bg-white border-2 border-gray-200 hover:border-brand-400 text-gray-700 px-5 py-2 rounded-lg font-bold transition-all flex items-center gap-2 shadow-sm">
                    <i class="fas fa-tasks text-brand-500"></i> Gestor de Actividades
                </button>
                
                
                <input type="file" id="inputCargarPDF" accept=".pdf" class="hidden" onchange="procesarPDF(event)">
                
                
                <button onclick="document.getElementById('inputCargarPDF').click()" class="bg-white border-2 border-purple-200 hover:border-purple-400 text-purple-700 px-5 py-2 rounded-lg font-bold transition-all flex items-center gap-2 shadow-sm">
                    <i class="fas fa-file-pdf text-purple-500"></i> Cargar PDF
                </button>
            </div>
        </div>

        
        <?php 
        $orden_impresion = ['C', 'A', 'B', 'Sin Turno']; 
        
        foreach ($orden_impresion as $tipo_turno): 
            $bgColor = match($tipo_turno) {
                'A' => 'bg-blue-600',
                'B' => 'bg-red-600',
                'C' => 'bg-green-600',
                default => 'bg-gray-600'
            };
            $icon = $tipo_turno === 'Sin Turno' ? 'fa-user-clock' : 'fa-clock';
            $id_tbody = 'tbody-' . str_replace(' ', '-', $tipo_turno);
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="<?= $bgColor ?> text-white font-bold p-3 uppercase tracking-wider flex items-center justify-between text-sm shadow-sm z-10 relative">
                <div class="flex items-center gap-2">
                    <i class="fas <?= $icon ?>"></i> TURNO <?= $tipo_turno ?> 
                </div>
                <button onclick="abrirBuscadorTurno('<?= $tipo_turno ?>')" class="bg-white/20 hover:bg-white/30 transition-colors px-3 py-1 rounded-lg text-xs flex items-center gap-2 shadow-sm border border-white/20">
                    <i class="fas fa-users"></i> Asignar Personal
                </button>
            </div>
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[1500px]">
                    <thead class="bg-gray-100 text-gray-600 text-xs uppercase border-b border-gray-200">
                        <tr>
                            <th class="p-3 border-r font-bold sticky left-0 bg-gray-100 z-10 w-32">ID</th>
                            <th class="p-3 border-r font-bold w-48">Nombre Completo</th>
                            <th class="p-3 border-r font-bold w-32">Cargo</th>
                            <th class="p-3 border-r font-bold w-64">Actividad(es)</th>
                            <th class="p-3 border-r font-bold text-center">Lunes</th>
                            <th class="p-3 border-r font-bold text-center">Martes</th>
                            <th class="p-3 border-r font-bold text-center">Miércoles</th>
                            <th class="p-3 border-r font-bold text-center">Jueves</th>
                            <th class="p-3 border-r font-bold text-center">Viernes</th>
                            <th class="p-3 border-r font-bold text-center">Sábado</th>
                            <th class="p-3 border-r font-bold text-center">Domingo</th>
                            <th class="p-3 font-bold text-center w-36">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm transition-all sortable-tbody" id="<?= $id_tbody ?>">
                        
                        <tr class="fila-vacia <?= !empty($turnos[$tipo_turno]) ? 'hidden' : '' ?>">
                            <td colspan="12" class="p-6 text-center text-gray-400 font-medium">
                                <i class="fas fa-inbox text-2xl mb-2 block"></i> No hay personal asignado a este turno
                            </td>
                        </tr>

                        <?php foreach ($turnos[$tipo_turno] as $emp): 
                            $datos_plan = $emp['datos_planeacion'];
                            $actividad_guardada = $datos_plan['actividad'] ?? '';
                        ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors fila-empleado bg-white" id="tr-<?= htmlspecialchars($emp['identificador']) ?>">
                            <td class="p-2 border-r sticky left-0 bg-white group-hover:bg-gray-50 font-mono text-xs text-gray-500 shadow-[2px_0_5px_rgba(0,0,0,0.02)] flex items-center h-full min-h-[45px]">
                                <i class="fas fa-grip-vertical text-gray-300 hover:text-brand-500 mr-2 drag-handle px-1"></i>
                                <?= htmlspecialchars($emp['identificador']) ?>
                            </td>
                            <td class="p-2 border-r font-semibold text-gray-800">
                                <?= htmlspecialchars($emp['nombres'] . ' ' . $emp['apellidos']) ?>
                            </td>
                            <td class="p-2 border-r text-xs text-gray-600">
                                <span class="bg-gray-200 px-2 py-1 rounded-md"><?= htmlspecialchars($emp['cargo']) ?></span>
                            </td>
                            <td class="p-2 border-r">
                                <button class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-[11px] font-medium outline-none hover:border-brand-400 bg-gray-50 hover:bg-white text-left flex justify-between items-center transition-colors btn-asignar-actividad" 
                                        data-seleccionado="<?= htmlspecialchars($actividad_guardada) ?>" 
                                        onclick="abrirModalAsignarActividad('<?= htmlspecialchars($emp['identificador']) ?>')">
                                    <span class="truncate texto-actividad text-gray-700"><?= !empty($actividad_guardada) ? htmlspecialchars($actividad_guardada) : '-- Sin asignar --' ?></span>
                                    <i class="fas fa-list-check text-brand-500 ml-2"></i>
                                </button>
                            </td>
                            <?php 
                            $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                            foreach ($dias as $dia):
                                $horario_celda = (!empty($datos_plan[$dia])) ? $datos_plan[$dia] : $horarios_default[$tipo_turno][$dia];
                            ?>
                                <td class="p-1 border-r">
                                    <input type="text" class="horario-input" 
                                           value="<?= htmlspecialchars($horario_celda) ?>" 
                                           data-dia="<?= $dia ?>" 
                                           placeholder="00:00-00:00">
                                </td>
                            <?php endforeach; ?>
                            <td class="p-2 text-center flex flex-col gap-1">
                                <select class="w-full px-2 py-1 border border-gray-200 rounded-md text-[11px] outline-none font-medium text-gray-600 cursor-pointer hover:border-gray-300 select-turno">
                                    <option value="">Mover a...</option>
                                    <option value="A">Turno A (Azul)</option>
                                    <option value="B">Turno B (Rojo)</option>
                                    <option value="C">Turno C (Verde)</option>
                                    <option value="Sin Turno">Sin Turno</option>
                                </select>
                                <button class="w-full px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-100 rounded-md text-[11px] font-bold transition-colors btn-descartar">
                                    Descartar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    
    <div id="modalActividades" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
            <div class="bg-dark p-4 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg"><i class="fas fa-tasks text-brand-400 mr-2"></i> Crear/Ver Actividades</h3>
                <button onclick="closeModal('modalActividades')" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6">
                <div class="flex gap-2 mb-4">
                    <input type="text" id="inputNuevaActividad" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg outline-none" placeholder="Ej: Picking manual...">
                    <button id="btnCrearActividad" class="bg-brand-500 text-dark px-4 py-2 rounded-lg font-bold">Crear</button>
                </div>
                <div id="contenedorActividadesList"></div>
            </div>
        </div>
    </div>

    
    <div id="modalProductivas" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
            <div class="bg-dark p-4 flex justify-between items-center shrink-0">
                <h3 class="text-white font-bold text-lg"><i class="fas fa-chart-line text-green-400 mr-2"></i> Actividades Productivas</h3>
                <button onclick="closeModal('modalProductivas')" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 bg-gray-50 border-b border-gray-200 shrink-0">
                <p class="text-gray-600 text-sm">Selecciona cuáles de las actividades existentes cuentan como <strong>productivas</strong>. Estas se guardarán en la base de datos.</p>
            </div>
            
            <div class="p-4 overflow-y-auto custom-scrollbar flex-1 bg-white" id="contenedorListaProductivas">
                
            </div>

            <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 shrink-0">
                <button onclick="closeModal('modalProductivas')" class="px-5 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-100 transition-colors shadow-sm">Cancelar</button>
                <button id="btnGuardarProductivas" class="px-5 py-2 bg-green-500 text-white rounded-lg font-bold hover:bg-green-600 transition-colors shadow-sm">Guardar Productivas</button>
            </div>
        </div>
    </div>

    
    <div id="modalAsignarActividad" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
            
            <div class="bg-dark p-4 flex justify-between items-center shrink-0">
                <h3 class="text-white font-bold text-lg"><i class="fas fa-clipboard-check text-brand-400 mr-2"></i> Asignar Actividades</h3>
                <button onclick="closeModal('modalAsignarActividad')" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 bg-gray-50 border-b border-gray-200 shrink-0">
                <p class="text-gray-600 text-sm">Selecciona <strong>hasta 2 actividades</strong> para el empleado.</p>
                <div class="mt-2 text-xs font-mono text-gray-500 flex items-center gap-2">
                    <i class="fas fa-user text-brand-500"></i> Empleado ID: <span id="lblIdEmpleadoAsignacion" class="font-bold text-gray-800"></span>
                </div>
            </div>
            
            <div class="p-4 overflow-y-auto custom-scrollbar flex-1 bg-white">
                <div id="contenedorTarjetasActividades" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 shrink-0">
                <button onclick="closeModal('modalAsignarActividad')" class="px-5 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-100 transition-colors shadow-sm">Cancelar</button>
                <button id="btnGuardarAsignacionActividades" class="px-5 py-2 bg-brand-500 text-dark rounded-lg font-bold hover:bg-brand-400 transition-colors shadow-sm">Guardar Asignación</button>
            </div>
        </div>
    </div>

    
    <div id="modalAgregarCAB" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="bg-dark p-4 flex justify-between items-center shrink-0">
                <h3 class="text-white font-bold text-lg"><i class="fas fa-users text-brand-400 mr-2"></i> Asignar Personal</h3>
                <button onclick="closeModal('modalAgregarCAB')" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-4 bg-gray-50 border-b border-gray-200 shrink-0">
                <p class="text-gray-600 text-sm mb-3">Se asignarán al <strong id="lblTurnoDestino" class="text-brand-500 font-bold px-2 py-0.5 bg-dark rounded">Turno</strong> con sus horarios por defecto.</p>
                <input type="hidden" id="turnoDestinoSeleccionado" value="">
                
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="inputFiltroCAB" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all shadow-sm" placeholder="Buscar por nombre o cédula...">
                </div>
            </div>
            
            <div class="p-4 overflow-y-auto custom-scrollbar flex-1 bg-white" id="listaEmpleadosCAB"></div>

            <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center shrink-0">
                <span class="text-sm font-semibold text-gray-600"><span id="lblSeleccionados" class="text-brand-500 font-bold text-lg">0</span> seleccionados</span>
                <button id="btnGuardarSeleccion" class="bg-brand-500 hover:bg-brand-400 text-dark px-6 py-2 rounded-lg font-bold shadow-sm transition-colors opacity-50 cursor-not-allowed" disabled>
                    Agregar Seleccionados
                </button>
            </div>
        </div>
    </div>

    
    <div id="toastNotificacion" class="fixed bottom-6 right-6 bg-gray-800 text-white px-5 py-3 rounded-lg shadow-xl transform translate-y-20 opacity-0 transition-all duration-300 z-[80] flex items-center gap-3 border-l-4 border-green-400 pointer-events-none">
        <i id="toastIcon" class="fas fa-check-circle text-green-400 text-xl"></i>
        <span id="toastMsg" class="text-sm font-medium">Guardado correctamente</span>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
        
        let toastTimeout;
        function mostrarNotificacion(mensaje, tipo = 'success') {
            const toast = document.getElementById('toastNotificacion');
            const icon = document.getElementById('toastIcon');
            document.getElementById('toastMsg').innerText = mensaje;
            
            if (tipo === 'success') {
                icon.className = 'fas fa-check-circle text-green-400 text-xl';
                toast.className = 'fixed bottom-6 right-6 bg-gray-800 text-white px-5 py-3 rounded-lg shadow-xl transition-all duration-300 z-[80] flex items-center gap-3 border-l-4 border-green-400 transform translate-y-0 opacity-100';
            } else if (tipo === 'info') {
                icon.className = 'fas fa-info-circle text-blue-400 text-xl';
                toast.className = 'fixed bottom-6 right-6 bg-gray-800 text-white px-5 py-3 rounded-lg shadow-xl transition-all duration-300 z-[80] flex items-center gap-3 border-l-4 border-blue-400 transform translate-y-0 opacity-100';
            } else if (tipo === 'error') {
                icon.className = 'fas fa-exclamation-circle text-red-400 text-xl';
                toast.className = 'fixed bottom-6 right-6 bg-gray-800 text-white px-5 py-3 rounded-lg shadow-xl transition-all duration-300 z-[80] flex items-center gap-3 border-l-4 border-red-400 transform translate-y-0 opacity-100';
            }

            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => { toast.classList.add('translate-y-20', 'opacity-0'); }, 3000);
        }

        function moverFilaDOM(idEmpleado, turnoDestino) {
            let tr = document.getElementById('tr-' + idEmpleado);
            if (!tr) return; 
            
            let tbodyDestino = document.getElementById('tbody-' + turnoDestino.replace(/ /g, '-'));
            let tbodyOrigen = tr.parentElement;
            if (tbodyOrigen === tbodyDestino) return;
            
            tbodyDestino.appendChild(tr);
            
            let filaVaciaDestino = tbodyDestino.querySelector('.fila-vacia');
            if (filaVaciaDestino) filaVaciaDestino.classList.add('hidden');
            
            let filasRestantes = tbodyOrigen.querySelectorAll('.fila-empleado');
            if (filasRestantes.length === 0) {
                let filaVaciaOrigen = tbodyOrigen.querySelector('.fila-vacia');
                if (filaVaciaOrigen) filaVaciaOrigen.classList.remove('hidden');
            }
            let selectTurno = tr.querySelector('.select-turno');
            if(selectTurno) selectTurno.value = "";
        }

        let todasLasActividades = [];
        let actividadesSeleccionadasTemporal = [];
        let empleadoEditandoActividad = null;
        const maxActividadesPermitidas = 2;

        document.addEventListener('DOMContentLoaded', function() {
            const semanaActual = document.getElementById('semana').value;

            
            document.querySelectorAll('.sortable-tbody').forEach(tbody => {
                new Sortable(tbody, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function (evt) {
                        let ordenIDs = Array.from(evt.to.querySelectorAll('tr.fila-empleado')).map(tr => tr.id.replace('tr-', ''));
                        fetch('../../api/planeacion/guardar_orden.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ semana: semanaActual, ordenIDs: ordenIDs })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if(data.exito) mostrarNotificacion('Orden actualizado', 'success');
                        });
                    }
                });
            });

            function verificarLibre(input) {
                if (input.value.trim().toLowerCase() === 'libre') input.classList.add('celda-libre');
                else input.classList.remove('celda-libre');
            }

            function guardarCambio(identificador, campo, valor) {
                return fetch('../../api/planeacion/guardar_planeacion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identificador, semana: semanaActual, campo, valor })
                })
                .then(r => r.json())
                .then(data => {
                    if(!data.exito) console.error('Error:', data.mensaje);
                    return data.exito;
                });
            }

            
            document.querySelectorAll('.horario-input').forEach(input => {
                verificarLibre(input);
                input.addEventListener('change', function() {
                    verificarLibre(this);
                    let id = this.closest('tr').id.replace('tr-', '');
                    guardarCambio(id, this.getAttribute('data-dia'), this.value).then(exito => {
                        if (exito) mostrarNotificacion('Horario actualizado', 'success');
                    });
                });
            });
            
            
            document.querySelectorAll('.select-turno').forEach(select => {
                select.addEventListener('change', async function() {
                    if(this.value === '') return;
                    let tr = this.closest('tr');
                    let id = tr.id.replace('tr-', '');
                    let nuevoTurno = this.value;
                    
                    this.disabled = true; 
                    let exito = await guardarCambio(id, 'turno', nuevoTurno);
                    this.disabled = false;
                    
                    if(exito) {
                        moverFilaDOM(id, nuevoTurno);
                        if(horariosDefault[nuevoTurno]) {
                            let promesasH = [];
                            for (let input of tr.querySelectorAll('.horario-input')) {
                                let dia = input.getAttribute('data-dia');
                                let defaultVal = horariosDefault[nuevoTurno][dia] || '';
                                input.value = defaultVal;
                                verificarLibre(input);
                                promesasH.push(guardarCambio(id, dia, defaultVal));
                            }
                            await Promise.all(promesasH);
                        }
                        mostrarNotificacion(`Movido al Turno ${nuevoTurno}`, 'success');
                    }
                });
            });

            
            document.querySelectorAll('.btn-descartar').forEach(btn => {
                btn.addEventListener('click', function() {
                    if(!confirm('¿Estás seguro de descartar a este empleado?')) return;
                    let tr = this.closest('tr');
                    let id = tr.id.replace('tr-', '');
                    this.disabled = true;
                    guardarCambio(id, 'estado', 'descartado').then(exito => {
                        this.disabled = false;
                        if(exito) {
                            moverFilaDOM(id, 'Sin Turno');
                            for (let input of tr.querySelectorAll('.horario-input')) {
                                input.value = '';
                                verificarLibre(input);
                                guardarCambio(id, input.getAttribute('data-dia'), '');
                            }
                            mostrarNotificacion('Empleado descartado', 'info');
                        }
                    });
                });
            });

            
            
            
            const inputFiltroCAB = document.getElementById('inputFiltroCAB');
            const listaEmpleadosCAB = document.getElementById('listaEmpleadosCAB');
            const lblSeleccionados = document.getElementById('lblSeleccionados');
            const btnGuardarSeleccion = document.getElementById('btnGuardarSeleccion');
            let todosLosEmpleadosCAB = []; 
            let empleadosSeleccionados = new Set(); 

            window.abrirBuscadorTurno = function(turno) {
                document.getElementById('turnoDestinoSeleccionado').value = turno;
                document.getElementById('lblTurnoDestino').innerText = 'TURNO ' + turno;
                document.getElementById('inputFiltroCAB').value = '';
                empleadosSeleccionados.clear();
                actualizarBotonSeleccion(); 
                openModal('modalAgregarCAB');
                
                if (todosLosEmpleadosCAB.length === 0) {
                    listaEmpleadosCAB.innerHTML = '<div class="text-center py-8 text-brand-500"><i class="fas fa-circle-notch fa-spin text-2xl mb-2 block"></i>Cargando...</div>';
                    fetch('../../api/planeacion/buscar_cab.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({}) 
                    }).then(r => r.json()).then(data => {
                        if(data.exito) {
                            todosLosEmpleadosCAB = data.datos;
                            filtrarYRenderizar(todosLosEmpleadosCAB, turno);
                        }
                    });
                } else {
                    filtrarYRenderizar(todosLosEmpleadosCAB, turno);
                }
            };

            function filtrarYRenderizar(lista, turnoDestino) {
                let tbodyDestino = document.getElementById('tbody-' + turnoDestino.replace(/ /g, '-'));
                let idsEnTurno = Array.from(tbodyDestino.querySelectorAll('.fila-empleado')).map(tr => tr.id.replace('tr-', ''));
                let disponibles = lista.filter(emp => !idsEnTurno.includes(emp.identificador));
                renderizarListaEmpleados(disponibles);
            }

            function renderizarListaEmpleados(lista) {
                if (lista.length === 0) {
                    listaEmpleadosCAB.innerHTML = '<div class="text-center py-8 text-gray-500">Todos asignados o sin coincidencias.</div>';
                    return;
                }
                let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
                lista.forEach(emp => {
                    let nombreCompleto = `${emp.nombres} ${emp.apellidos}`;
                    let isChecked = empleadosSeleccionados.has(emp.identificador) ? 'checked' : '';
                    let isSelectedClass = empleadosSeleccionados.has(emp.identificador) ? 'selected border-brand-400 bg-amber-50' : 'bg-white';
                    html += `
                        <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:border-brand-400 transition-all cursor-pointer fila-seleccionable group ${isSelectedClass}">
                            <input type="checkbox" class="chk-empleado w-5 h-5 accent-brand-500 rounded border-gray-300 text-brand-600 mr-3" value="${emp.identificador}" ${isChecked}>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-gray-800 text-sm truncate">${nombreCompleto}</div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] text-gray-500 font-mono bg-gray-100 px-1.5 py-0.5 rounded"><i class="far fa-id-card mr-1"></i>${emp.identificador}</span>
                                    <span class="text-[10px] text-gray-500 truncate"><i class="fas fa-briefcase mr-1"></i>${emp.cargo}</span>
                                </div>
                            </div>
                        </label>
                    `;
                });
                html += '</div>';
                listaEmpleadosCAB.innerHTML = html;

                document.querySelectorAll('.chk-empleado').forEach(chk => {
                    chk.addEventListener('change', function() {
                        let label = this.closest('label');
                        if(this.checked) {
                            label.classList.add('selected', 'border-brand-400', 'bg-amber-50');
                            label.classList.remove('bg-white');
                            empleadosSeleccionados.add(this.value); 
                        } else {
                            label.classList.remove('selected', 'border-brand-400', 'bg-amber-50');
                            label.classList.add('bg-white');
                            empleadosSeleccionados.delete(this.value); 
                        }
                        actualizarBotonSeleccion();
                    });
                });
            }

            function actualizarBotonSeleccion() {
                let count = empleadosSeleccionados.size;
                lblSeleccionados.innerText = count;
                if(count > 0) {
                    btnGuardarSeleccion.disabled = false;
                    btnGuardarSeleccion.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    btnGuardarSeleccion.disabled = true;
                    btnGuardarSeleccion.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }

            if(inputFiltroCAB) {
                inputFiltroCAB.addEventListener('keyup', function() {
                    let texto = this.value.toLowerCase().trim();
                    let turnoDestino = document.getElementById('turnoDestinoSeleccionado').value;
                    let tbodyDestino = document.getElementById('tbody-' + turnoDestino.replace(/ /g, '-'));
                    let idsEnTurno = Array.from(tbodyDestino.querySelectorAll('.fila-empleado')).map(tr => tr.id.replace('tr-', ''));
                    let filtrados = todosLosEmpleadosCAB.filter(emp => {
                        if(idsEnTurno.includes(emp.identificador)) return false;
                        let nombreCompleto = `${emp.nombres} ${emp.apellidos}`.toLowerCase();
                        let cedula = emp.identificador.toLowerCase();
                        return nombreCompleto.includes(texto) || cedula.includes(texto);
                    });
                    renderizarListaEmpleados(filtrados);
                });
            }

            btnGuardarSeleccion.addEventListener('click', function() {
                let seleccionados = Array.from(empleadosSeleccionados);
                if(seleccionados.length === 0) return;
                let turnoDestino = document.getElementById('turnoDestinoSeleccionado').value;
                let horarios = horariosDefault[turnoDestino] || {lunes:'', martes:'', miercoles:'', jueves:'', viernes:'', sabado:'', domingo:''};
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Asignando...';

                fetch('../../api/planeacion/guardar_planeacion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        accion: 'masivo', 
                        semana: semanaActual, 
                        turnoDestino: turnoDestino, 
                        identificadores: seleccionados,
                        horariosPorDefecto: horarios
                    })
                }).then(r => r.json()).then(data => {
                    if(data.exito) window.location.reload(); 
                    else {
                        this.disabled = false;
                        this.innerHTML = 'Agregar Seleccionados';
                        alert('Hubo un error: ' + data.mensaje);
                    }
                });
            });
            
            
            
            
            
            function cargarActividades() {
                fetch('../auditoria/gestionar_actividades.php?accion=listar')
                .then(r => r.json())
                .then(data => {
                    if(data.exito) {
                        todasLasActividades = data.datos; 
                        let listaHTML = '<div class="bg-gray-50 border rounded-lg max-h-48 overflow-y-auto custom-scrollbar">';
                        data.datos.forEach(act => {
                            
                            let badge = (act.es_productiva == 1) ? '<span class="ml-auto bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded font-bold">Productiva</span>' : '';
                            listaHTML += `<div class="p-2 border-b text-sm text-gray-700 flex items-center"><i class="fas fa-check text-gray-400 mr-2"></i>${act.nombre} ${badge}</div>`;
                        });
                        listaHTML += '</div>';
                        document.getElementById('contenedorActividadesList').innerHTML = data.datos.length > 0 ? listaHTML : '<p class="text-sm text-gray-400">No hay actividades creadas</p>';
                    }
                });
            }

            
            window.abrirModalProductivas = function() {
                let contenedor = document.getElementById('contenedorListaProductivas');
                if (todasLasActividades.length === 0) {
                    contenedor.innerHTML = '<p class="text-gray-500 text-center text-sm py-8 border border-dashed rounded-lg bg-gray-50">No hay actividades creadas.</p>';
                } else {
                    let html = '<div class="space-y-2">';
                    todasLasActividades.forEach(act => {
                        
                        let isChecked = (act.es_productiva == 1 || act.es_productiva === true) ? 'checked' : '';
                        html += `
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-green-50 hover:border-green-300 cursor-pointer transition-colors group">
                                <input type="checkbox" class="chk-productiva w-5 h-5 accent-green-500 rounded border-gray-300 mr-3" value="${act.nombre.replace(/"/g, '&quot;')}" ${isChecked}>
                                <span class="text-sm font-medium text-gray-800">${act.nombre}</span>
                            </label>
                        `;
                    });
                    html += '</div>';
                    contenedor.innerHTML = html;
                }
                openModal('modalProductivas');
            };

            
            document.getElementById('btnGuardarProductivas').addEventListener('click', function() {
                let seleccionadas = Array.from(document.querySelectorAll('.chk-productiva:checked')).map(chk => chk.value);
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

                fetch('../auditoria/gestionar_actividades.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'guardar_productivas', productivas: seleccionadas })
                })
                .then(r => r.json())
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = 'Guardar Productivas';
                    if(data.exito) {
                        mostrarNotificacion('Actividades productivas guardadas', 'success');
                        closeModal('modalProductivas');
                        cargarActividades(); 
                    } else {
                        alert(data.mensaje || 'Hubo un error al guardar');
                    }
                }).catch(e => {
                    this.disabled = false;
                    this.innerHTML = 'Guardar Productivas';
                    console.error('Error:', e);
                });
            });

            
            window.abrirModalAsignarActividad = function(idEmpleado) {
                empleadoEditandoActividad = idEmpleado;
                document.getElementById('lblIdEmpleadoAsignacion').textContent = idEmpleado;
                
                let btn = document.querySelector(`#tr-${idEmpleado} .btn-asignar-actividad`);
                let asignadasActuales = btn.getAttribute('data-seleccionado');
                
                if (asignadasActuales && asignadasActuales !== '-- Sin asignar --' && asignadasActuales !== '') {
                    actividadesSeleccionadasTemporal = asignadasActuales.split(' y ');
                } else {
                    actividadesSeleccionadasTemporal = [];
                }
                renderizarTarjetasActividades();
                openModal('modalAsignarActividad');
            };

            window.renderizarTarjetasActividades = function() {
                let contenedor = document.getElementById('contenedorTarjetasActividades');
                let html = '';
                
                if (todasLasActividades.length === 0) {
                    contenedor.innerHTML = '<div class="col-span-full p-6 text-center text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">No hay actividades. Créalas en "Gestor de Actividades".</div>';
                    return;
                }

                todasLasActividades.forEach(act => {
                    let isSelected = actividadesSeleccionadasTemporal.includes(act.nombre);
                    let classSelected = isSelected ? 'border-brand-500 bg-amber-50 shadow-md ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-brand-300 hover:shadow-sm';
                    let iconColor = isSelected ? 'text-brand-500 opacity-100' : 'text-gray-200 opacity-0 group-hover:opacity-100 transition-opacity';
                    
                    let badge = (act.es_productiva == 1) ? '<span class="text-[9px] bg-green-500 text-white px-1.5 py-0.5 rounded ml-2">Prod</span>' : '';

                    html += `
                        <div onclick="toggleActividad('${act.nombre.replace(/'/g, "\\'")}')" class="cursor-pointer p-3 border rounded-xl transition-all flex items-center justify-between group select-none ${classSelected}">
                            <div class="flex items-center min-w-0">
                                <span class="font-medium text-sm text-gray-700 truncate" title="${act.nombre}">${act.nombre}</span>
                                ${badge}
                            </div>
                            <i class="fas fa-check-circle text-xl shrink-0 ml-2 transition-colors ${iconColor}"></i>
                        </div>
                    `;
                });
                
                contenedor.innerHTML = html;
            };

            window.toggleActividad = function(nombre) {
                let index = actividadesSeleccionadasTemporal.indexOf(nombre);
                if (index > -1) {
                    actividadesSeleccionadasTemporal.splice(index, 1);
                } else {
                    if (actividadesSeleccionadasTemporal.length >= maxActividadesPermitidas) {
                        mostrarNotificacion('Solo puedes seleccionar un máximo de 2 actividades.', 'error');
                        return;
                    }
                    actividadesSeleccionadasTemporal.push(nombre);
                }
                renderizarTarjetasActividades();
            };

            document.getElementById('btnGuardarAsignacionActividades').addEventListener('click', function() {
                if (!empleadoEditandoActividad) return;
                let stringGuardar = actividadesSeleccionadasTemporal.join(' y ');
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

                guardarCambio(empleadoEditandoActividad, 'actividad', stringGuardar).then(exito => {
                    this.disabled = false;
                    this.innerHTML = 'Guardar Asignación';
                    if (exito) {
                        let btn = document.querySelector(`#tr-${empleadoEditandoActividad} .btn-asignar-actividad`);
                        btn.setAttribute('data-seleccionado', stringGuardar);
                        let spanTexto = btn.querySelector('.texto-actividad');
                        spanTexto.textContent = stringGuardar !== '' ? stringGuardar : '-- Sin asignar --';
                        
                        if (stringGuardar !== '') {
                            spanTexto.classList.add('font-bold', 'text-brand-600');
                            spanTexto.classList.remove('text-gray-700');
                        } else {
                            spanTexto.classList.remove('font-bold', 'text-brand-600');
                            spanTexto.classList.add('text-gray-700');
                        }
                        
                        mostrarNotificacion('Actividades asignadas', 'success');
                        closeModal('modalAsignarActividad');
                    }
                });
            });
            
            
            window.heredarActividadesAnteriores = function(event) {
                if (!confirm('¿Estás seguro de heredar las actividades de la semana anterior? Esto sobrescribirá las actividades asignadas actualmente en esta semana.')) {
                    return;
                }

                let btn = event.currentTarget;
                let textoOriginal = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                fetch('../../api/planeacion/guardar_planeacion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        accion: 'heredar_actividades', 
                        semana: semanaActual 
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.exito) {
                        mostrarNotificacion('Actividades heredadas con éxito', 'success');
                        setTimeout(() => window.location.reload(), 1500); 
                    } else {
                        alert('Error: ' + (data.mensaje || 'No se pudieron heredar las actividades'));
                        btn.disabled = false;
                        btn.innerHTML = textoOriginal;
                    }
                })
                .catch(e => {
                    console.error('Error:', e);
                    alert('Hubo un error de conexión.');
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                });
            };
            
          
            window.procesarPDF = async function(event) {
                const file = event.target.files[0];
                if (!file) return;

                mostrarNotificacion('Leyendo y ordenando PDF...', 'info');

                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;
                    let textoCompleto = "";

                    
                    for (let i = 1; i <= pdf.numPages; i++) {
                        const page = await pdf.getPage(i);
                        const textContent = await page.getTextContent();
                        
                        
                        let itemsOrdenados = textContent.items.sort((a, b) => {
                            let yA = a.transform[5]; 
                            let yB = b.transform[5];
                            
                            
                            if (Math.abs(yA - yB) < 5) {
                                return a.transform[4] - b.transform[4]; 
                            }
                            
                            return yB - yA;
                        });

                        const pageText = itemsOrdenados.map(item => item.str).join(" ");
                        textoCompleto += pageText + " | ";
                    }

                    console.log("📄 TEXTO ORDENADO DEL PDF:", textoCompleto);

                    let asignaciones = [];
                    let turnoActual = 'Sin Turno';
                    
                    
                    const regex = /TURNO\s*([A-C])|\b([0-9]{6,15})\b/gi;
                    let match;

                    while ((match = regex.exec(textoCompleto)) !== null) {
                        if (match[1]) {
                            
                            turnoActual = match[1].toUpperCase();
                            console.log("➡️ Leyendo a partir de aquí: TURNO " + turnoActual);
                        } else if (match[2]) {
                            let cedula = match[2];
                            
                            
                            if (turnoActual !== 'Sin Turno') {
                                asignaciones.push({
                                    cedula: cedula,
                                    turno: turnoActual
                                });
                            }
                        }
                    }

                    console.log("✅ RESULTADO:", asignaciones);

                    if (asignaciones.length === 0) {
                        alert('No pude asignar ninguna cédula. Revisa la consola (F12) para ver cómo se extrajo el texto.');
                        event.target.value = '';
                        return;
                    }

                    if (!confirm(`¡Listo! Detecté ${asignaciones.length} empleados en sus respectivos turnos.\n\n¿Deseas guardarlos para esta semana?`)) {
                        event.target.value = '';
                        return;
                    }

                    
                    enviarAsignacionesPDF(asignaciones);

                } catch (error) {
                    console.error("❌ Error:", error);
                    alert("Hubo un problema al procesar el documento PDF.");
                }
                event.target.value = ''; 
            };

            
            function enviarAsignacionesPDF(asignaciones) {
                fetch('../../api/planeacion/guardar_planeacion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        accion: 'cargar_pdf',
                        semana: semanaActual,
                        datos: asignaciones,
                        horariosDefault: horariosDefault
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.exito) {
                        mostrarNotificacion(`¡${data.actualizados} empleados asignados a sus turnos!`, 'success');
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        alert('Error del servidor: ' + data.mensaje);
                    }
                })
                .catch(e => {
                    console.error('Error de red al guardar:', e);
                    alert('Hubo un error de conexión al guardar los datos.');
                });
            }

            
            const btnCrearActividad = document.getElementById('btnCrearActividad');
            if(btnCrearActividad) {
                btnCrearActividad.addEventListener('click', function() {
                    let nombreAct = document.getElementById('inputNuevaActividad').value.trim();
                    if(nombreAct === '') return;
                    btnCrearActividad.disabled = true;
                    fetch('../auditoria/gestionar_actividades.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ accion: 'crear', nombre: nombreAct })
                    }).then(r => r.json()).then(data => {
                        btnCrearActividad.disabled = false;
                        if(data.exito) {
                            document.getElementById('inputNuevaActividad').value = ''; 
                            cargarActividades(); 
                            mostrarNotificacion('Actividad creada exitosamente', 'success');
                        } else alert(data.mensaje);
                    });
                });
            }

            cargarActividades();
        });
    </script>
</body>
</html>