<?php
require_once '../core/config.php';
verificarLogin();
date_default_timezone_set('America/Bogota');

$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario Descargue';
$mensaje = '';
$tipo_mensaje = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    
    if ($_POST['action'] === 'iniciar_descargue') {
        $id_sortiing = limpiarDatos($_POST['id_sortiing']);
        $placa = limpiarDatos($_POST['placa']);
        $fecha_hora_actual = date('Y-m-d H:i:s');

        try {
            $stmt = $pdo->prepare("INSERT INTO descargue (id_sortiing, placa, usuario_descargue, fecha_hora_inicio, estado, operacion_id) VALUES (?, ?, ?, ?, 'en_proceso', ?)");
            $stmt->execute([$id_sortiing, $placa, $nombre_usuario, $fecha_hora_actual, getOperacionActiva()]);
            $mensaje = 'Descargue iniciado correctamente.';
            $tipo_mensaje = 'success';
        } catch (PDOException $e) {
            $mensaje = 'Error al iniciar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }

    
    if ($_POST['action'] === 'finalizar_descargue') {
        $id_descargue = limpiarDatos($_POST['id_descargue']);
        $tiene_novedad = limpiarDatos($_POST['tiene_novedad']);
        $novedades = limpiarDatos($_POST['novedades'] ?? '');
        $fecha_hora_fin = date('Y-m-d H:i:s');

        try {
            $stmt = $pdo->prepare("UPDATE descargue SET fecha_hora_fin = ?, tiene_novedad = ?, novedades = ?, estado = 'finalizado' WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$fecha_hora_fin, $tiene_novedad, $novedades, $id_descargue, getOperacionActiva()]);
            $mensaje = 'Descargue finalizado exitosamente.';
            $tipo_mensaje = 'success';
        } catch (PDOException $e) {
            $mensaje = 'Error al finalizar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}


$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');


$stmt_vehiculos = $pdo->prepare("
    SELECT s.id AS id_sortiing, s.placa, s.estado AS estado_sorting, s.fecha_ingreso, s.hora_ingreso,
           d.id AS id_descargue, d.estado AS estado_descargue, d.fecha_hora_inicio, d.fecha_hora_fin, 
           d.usuario_descargue, d.tiene_novedad, d.novedades
    FROM sortiing s
    LEFT JOIN descargue d ON s.id = d.id_sortiing
    WHERE DATE(s.fecha_ingreso) BETWEEN :inicio AND :fin
    AND s.operacion_id = :operacion_id
    ORDER BY s.id DESC
");
$stmt_vehiculos->execute(['inicio' => $fecha_inicio, 'fin' => $fecha_fin, 'operacion_id' => getOperacionActiva()]);
$vehiculos = $stmt_vehiculos->fetchAll();

require_once '../core/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>

<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { poppins: ['Poppins', 'sans-serif'] },
        colors: { gold: '#FFD700', goldDark: '#E6C200', darkBg: '#121212' }
      }
    }
  }
</script>

<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
    
    
    .modal-enter { opacity: 0; transform: scale(0.95) translateY(10px); }
    .modal-enter-active { opacity: 1; transform: scale(1) translateY(0); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    
    
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #E6C200; border-radius: 10px; }
</style>

<div id="toast-container" class="fixed top-24 right-5 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 relative z-10">
    
    <div class="mb-10 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold/20 text-goldDark text-xs font-bold mb-3 border border-gold/30 uppercase tracking-wider">
                <i class="fas fa-boxes"></i> Logística Interna
            </div>
            <h1 class="text-4xl font-black text-gray-900 tracking-tight">
                Módulo de <span class="text-transparent bg-clip-text bg-gradient-to-r from-goldDark to-yellow-500">Descargue</span>
            </h1>
            <p class="text-sm text-gray-500 mt-2 font-medium">Control en tiempo real de operaciones de descargue.</p>
        </div>

        <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200 flex-shrink-0">
            <form method="GET" action="descargue.php" class="flex flex-col sm:flex-row items-center gap-2">
                <div class="flex items-center bg-gray-50 px-3 py-2 rounded-xl border border-gray-100">
                    <i class="fas fa-calendar-alt text-goldDark mr-2"></i>
                    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" class="bg-transparent text-sm font-semibold text-gray-700 outline-none">
                </div>
                <span class="text-gray-400 font-bold text-sm">a</span>
                <div class="flex items-center bg-gray-50 px-3 py-2 rounded-xl border border-gray-100">
                    <i class="fas fa-calendar-check text-goldDark mr-2"></i>
                    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" class="bg-transparent text-sm font-semibold text-gray-700 outline-none">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-darkBg hover:bg-gray-800 text-gold text-sm font-bold rounded-xl transition-all shadow-md">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-gold to-yellow-400"></div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-darkBg">
                    <tr>
                        <th class="px-8 py-5 text-left text-xs font-bold text-gold uppercase tracking-wider">Vehículo</th>
                        <th class="px-8 py-5 text-left text-xs font-bold text-gold uppercase tracking-wider">Estado Check-in</th>
                        <th class="px-8 py-5 text-left text-xs font-bold text-gold uppercase tracking-wider">Duración / Tiempo Real</th>
                        <th class="px-8 py-5 text-center text-xs font-bold text-gold uppercase tracking-wider">Operación</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    <?php if (empty($vehiculos)): ?>
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <i class="fas fa-inbox text-5xl text-gray-200 mb-4 block"></i>
                            <p class="text-lg font-bold text-gray-500">No hay registros para las fechas seleccionadas.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($vehiculos as $vh): ?>
                        <tr class="hover:bg-gray-50 transition-all duration-200">
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="inline-flex items-center justify-center px-4 py-1.5 bg-gray-900 border-2 border-gray-800 rounded-lg shadow-sm">
                                    <span class="text-gold font-bold tracking-widest text-sm uppercase">
                                        <?php echo htmlspecialchars($vh['placa']); ?>
                                    </span>
                                </div>
                                <div class="text-[10px] font-bold text-gray-400 mt-1 uppercase">
                                    Ingreso: <?php echo date('d/m/Y h:i A', strtotime($vh['fecha_ingreso'].' '.$vh['hora_ingreso'])); ?>
                                </div>
                            </td>

                            <td class="px-8 py-5 whitespace-nowrap">
                                <?php if($vh['estado_sorting'] === 'completado'): ?>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-lg text-xs font-bold">
                                        <i class="fas fa-check-double"></i> Listo
                                    </div>
                                <?php else: ?>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 text-red-600 border border-red-200 rounded-lg text-xs font-bold">
                                        <i class="fas fa-times-circle"></i> Pendiente
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="px-8 py-5 whitespace-nowrap">
                                <?php if ($vh['estado_descargue'] === 'en_proceso'): ?>
                                    <div class="flex items-center gap-3">
                                        <span class="relative flex h-3 w-3">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gold opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-goldDark"></span>
                                        </span>
                                        <span class="live-timer font-mono text-xl font-black text-darkBg tracking-widest" data-start="<?php echo strtotime($vh['fecha_hora_inicio']) * 1000; ?>">
                                            00:00:00
                                        </span>
                                    </div>
                                <?php elseif ($vh['estado_descargue'] === 'finalizado'): ?>
                                    <?php 
                                        $inicio = new DateTime($vh['fecha_hora_inicio']);
                                        $fin = new DateTime($vh['fecha_hora_fin']);
                                        $diff = $inicio->diff($fin);
                                    ?>
                                    <div class="flex items-center gap-2 text-gray-600 font-bold text-sm">
                                        <i class="fas fa-flag-checkered text-gray-400"></i> 
                                        <?php echo $diff->format('%Hh %Im %Ss'); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm font-medium italic">A la espera de inicio...</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-8 py-5 whitespace-nowrap text-center">
                                <?php if ($vh['estado_sorting'] === 'pendiente'): ?>
                                    <button disabled class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-400 text-xs font-bold rounded-xl cursor-not-allowed border border-gray-200">
                                        <i class="fas fa-lock"></i> Bloqueado
                                    </button>

                                <?php elseif ($vh['estado_descargue'] == null): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="iniciar_descargue">
                                        <input type="hidden" name="id_sortiing" value="<?php echo $vh['id_sortiing']; ?>">
                                        <input type="hidden" name="placa" value="<?php echo $vh['placa']; ?>">
                                        <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-darkBg hover:bg-gray-800 text-gold text-xs font-bold rounded-xl transition-all shadow-md group border border-gray-700">
                                            <i class="fas fa-play text-gold group-hover:scale-110 transition-transform"></i> Iniciar
                                        </button>
                                    </form>

                                <?php elseif ($vh['estado_descargue'] === 'en_proceso'): ?>
                                    <button onclick="abrirModalFin(<?php echo $vh['id_descargue']; ?>, '<?php echo $vh['placa']; ?>')" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-goldDark hover:bg-yellow-500 text-darkBg text-xs font-bold rounded-xl transition-all shadow-md hover:shadow-lg">
                                        <i class="fas fa-stop"></i> Finalizar
                                    </button>

                                <?php else: ?>
                                    <button onclick='abrirModalDetalles(<?php echo json_encode($vh); ?>)' class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-xl border border-gray-200 transition-all shadow-sm">
                                        <i class="fas fa-eye text-goldDark"></i> Ver Detalles
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="finModal" class="fixed inset-0 z-[1002] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity duration-300 opacity-0" id="modalBackdropFin"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden modal-enter border border-gray-100" id="modalPanelFin">
                
                <div class="bg-darkBg px-8 py-6 flex justify-between items-center border-b-2 border-gold/30">
                    <h3 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-stop-circle text-gold"></i> Finalizar Proceso
                    </h3>
                    <button type="button" onclick="cerrarModalFin()" class="text-gray-400 hover:text-white transition-colors"><i class="fas fa-times text-lg"></i></button>
                </div>

                <form action="descargue.php" method="POST" class="p-8">
                    <input type="hidden" name="action" value="finalizar_descargue">
                    <input type="hidden" name="id_descargue" id="modal_id_descargue">
                    
                    <div class="mb-8 flex justify-center">
                        <div class="bg-gray-900 border-2 border-gray-800 rounded-xl px-6 py-2 shadow-inner">
                            <p class="text-[10px] text-gray-400 uppercase font-bold text-center mb-1">Placa Vehículo</p>
                            <p id="modal_placa_display" class="text-3xl font-black text-gold tracking-widest text-center"></p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">¿Se presentaron novedades?</label>
                            <div class="flex gap-4">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="tiene_novedad" value="no" class="peer hidden" checked onchange="toggleNovedades(false)">
                                    <div class="p-4 text-center border-2 border-gray-100 rounded-2xl peer-checked:border-darkBg peer-checked:bg-gray-50 peer-checked:text-darkBg text-gray-400 font-bold transition-all shadow-sm">
                                        <i class="fas fa-check-circle text-xl mb-1 block"></i> Sin Novedad
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="tiene_novedad" value="si" class="peer hidden" onchange="toggleNovedades(true)">
                                    <div class="p-4 text-center border-2 border-gray-100 rounded-2xl peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 text-gray-400 font-bold transition-all shadow-sm">
                                        <i class="fas fa-exclamation-triangle text-xl mb-1 block"></i> Reportar
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div id="novedades_container" class="hidden">
                            <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Detalle el reporte</label>
                            <textarea name="novedades" rows="4" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-medium outline-none focus:border-gold focus:ring-4 focus:ring-gold/10 transition-all resize-none" placeholder="Escriba aquí los detalles..."></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-4">
                        <button type="button" onclick="cerrarModalFin()" class="w-1/3 py-3.5 border-2 border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="w-2/3 py-3.5 bg-darkBg text-gold font-bold text-lg rounded-xl flex items-center justify-center gap-2 hover:bg-gray-800 shadow-md">
                            <i class="fas fa-save"></i> Guardar y Finalizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="detallesModal" class="fixed inset-0 z-[1003] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity duration-300 opacity-0" id="modalBackdropDet"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden modal-enter border border-gray-100" id="modalPanelDet">
                
                <div class="bg-darkBg px-8 py-6 flex justify-between items-center border-b-2 border-gold/30 relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 text-white/5 text-9xl"><i class="fas fa-clipboard-list"></i></div>
                    <div class="relative z-10">
                        <h3 class="text-2xl font-black text-white flex items-center gap-3">
                            Resumen de Operación
                        </h3>
                        <p class="text-gold/80 text-xs font-bold uppercase tracking-widest mt-1" id="det_placa_title"></p>
                    </div>
                    <button type="button" onclick="cerrarModalDetalles()" class="text-gray-400 hover:text-white transition-colors relative z-10"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Encargado Descargue</p>
                            <p class="text-sm font-black text-gray-800 flex items-center gap-2">
                                <i class="fas fa-user-hard-hat text-goldDark"></i> <span id="det_usuario"></span>
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Tiempo de Ejecución</p>
                            <p class="text-sm font-black text-gray-800 flex items-center gap-2">
                                <i class="fas fa-stopwatch text-goldDark"></i> <span id="det_duracion"></span>
                            </p>
                        </div>
                    </div>

                    <div class="border border-gray-100 rounded-2xl overflow-hidden mb-6">
                        <div class="flex justify-between items-center p-4 bg-white border-b border-gray-50">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Inicio de Descargue</p>
                                <p class="text-sm font-bold text-gray-700" id="det_inicio"></p>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center"><i class="fas fa-play text-xs"></i></div>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-gray-50">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Fin de Descargue</p>
                                <p class="text-sm font-bold text-gray-700" id="det_fin"></p>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-green-50 text-green-500 flex items-center justify-center"><i class="fas fa-flag-checkered text-xs"></i></div>
                        </div>
                    </div>

                    <div id="det_novedad_wrapper" class="rounded-2xl p-5 border-l-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div id="det_novedad_icon"></div>
                            <h4 class="text-sm font-bold uppercase tracking-wider" id="det_novedad_status"></h4>
                        </div>
                        <p class="text-sm text-gray-600 font-medium ml-8" id="det_novedad_texto"></p>
                    </div>

                    <div class="mt-8 text-center">
                        <button type="button" onclick="cerrarModalDetalles()" class="px-8 py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition-colors shadow-lg">
                            Cerrar Detalles
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    
    function updateTimers() {
        document.querySelectorAll('.live-timer').forEach(el => {
            let startMs = parseInt(el.getAttribute('data-start'));
            let nowMs = new Date().getTime();
            let diff = nowMs - startMs;
            
            if (diff < 0) diff = 0;

            let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            let seconds = Math.floor((diff % (1000 * 60)) / 1000);

            el.innerHTML = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        });
    }
    
    if(document.querySelectorAll('.live-timer').length > 0) {
        setInterval(updateTimers, 1000);
        updateTimers(); 
    }

    
    function abrirModalFin(id, placa) {
        document.getElementById('modal_id_descargue').value = id;
        document.getElementById('modal_placa_display').textContent = placa;
        
        document.querySelector('input[name="tiene_novedad"][value="no"]').checked = true;
        toggleNovedades(false);
        document.querySelector('textarea[name="novedades"]').value = '';

        document.getElementById('finModal').classList.remove('hidden');
        requestAnimationFrame(() => {
            document.getElementById('modalBackdropFin').classList.remove('opacity-0');
            document.getElementById('modalPanelFin').classList.remove('modal-enter');
            document.getElementById('modalPanelFin').classList.add('modal-enter-active');
        });
    }

    function cerrarModalFin() {
        document.getElementById('modalBackdropFin').classList.add('opacity-0');
        document.getElementById('modalPanelFin').classList.remove('modal-enter-active');
        document.getElementById('modalPanelFin').classList.add('modal-enter');
        setTimeout(() => { document.getElementById('finModal').classList.add('hidden'); }, 300);
    }

    function toggleNovedades(mostrar) {
        const container = document.getElementById('novedades_container');
        if(mostrar) {
            container.classList.remove('hidden');
            container.querySelector('textarea').setAttribute('required', 'true');
        } else {
            container.classList.add('hidden');
            container.querySelector('textarea').removeAttribute('required');
        }
    }

    
    function abrirModalDetalles(vh) {
        document.getElementById('det_placa_title').textContent = 'PLACA: ' + vh.placa;
        document.getElementById('det_usuario').textContent = vh.usuario_descargue;
        
        
        const opt = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit', hour12: true };
        const dInicio = new Date(vh.fecha_hora_inicio);
        const dFin = new Date(vh.fecha_hora_fin);
        
        document.getElementById('det_inicio').textContent = dInicio.toLocaleString('es-CO', opt);
        document.getElementById('det_fin').textContent = dFin.toLocaleString('es-CO', opt);

        
        let diffMs = dFin - dInicio;
        let hrs = Math.floor((diffMs % 86400000) / 3600000);
        let mins = Math.round(((diffMs % 86400000) % 3600000) / 60000);
        document.getElementById('det_duracion').textContent = `${hrs}h ${mins}m`;

        
        const wrapper = document.getElementById('det_novedad_wrapper');
        const icon = document.getElementById('det_novedad_icon');
        const status = document.getElementById('det_novedad_status');
        const texto = document.getElementById('det_novedad_texto');

        if (vh.tiene_novedad === 'si') {
            wrapper.className = "rounded-2xl p-5 border-l-4 bg-red-50 border-red-500";
            icon.innerHTML = '<i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>';
            status.className = "text-sm font-bold uppercase tracking-wider text-red-700";
            status.textContent = "Reporte de Novedad";
            texto.textContent = vh.novedades || 'Novedad reportada sin descripción.';
        } else {
            wrapper.className = "rounded-2xl p-5 border-l-4 bg-green-50 border-green-500";
            icon.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xl"></i>';
            status.className = "text-sm font-bold uppercase tracking-wider text-green-700";
            status.textContent = "Proceso sin Novedad";
            texto.textContent = "El vehículo fue descargado con éxito y sin contratiempos.";
        }

        
        document.getElementById('detallesModal').classList.remove('hidden');
        requestAnimationFrame(() => {
            document.getElementById('modalBackdropDet').classList.remove('opacity-0');
            document.getElementById('modalPanelDet').classList.remove('modal-enter');
            document.getElementById('modalPanelDet').classList.add('modal-enter-active');
        });
    }

    function cerrarModalDetalles() {
        document.getElementById('modalBackdropDet').classList.add('opacity-0');
        document.getElementById('modalPanelDet').classList.remove('modal-enter-active');
        document.getElementById('modalPanelDet').classList.add('modal-enter');
        setTimeout(() => { document.getElementById('detallesModal').classList.add('hidden'); }, 300);
    }

    
    function showToast(message, type) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `flex items-center p-4 bg-white border-l-4 ${type === 'success' ? 'border-green-500' : 'border-red-500'} rounded-xl shadow-2xl transition-all duration-500 opacity-0 pointer-events-auto`;
        toast.innerHTML = `<div class="ml-3"><p class="text-sm font-bold text-gray-900">${message}</p></div>`;
        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.remove('opacity-0'));
        setTimeout(() => { toast.remove(); }, 4000);
    }

    <?php if ($mensaje !== ''): ?>
        showToast("<?php echo addslashes($mensaje); ?>", "<?php echo $tipo_mensaje; ?>");
    <?php endif; ?>
</script>

</body>
</html>