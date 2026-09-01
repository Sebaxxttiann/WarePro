<?php
require_once '../../core/config.php';
verificarLogin();
date_default_timezone_set('America/Bogota');

$fecha_actual = date('Y-m-d');
$hora_actual = date('H:i'); 
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario Desconocido';

$mensaje = '';
$tipo_mensaje = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ingreso_vehiculo') {
    $placa = limpiarDatos($_POST['placa']);
    $fecha_ingreso = !empty($_POST['fecha']) ? limpiarDatos($_POST['fecha']) : date('Y-m-d');
    
    $hora_post = limpiarDatos($_POST['hora']);
    $hora_ingreso = !empty($hora_post) ? (strlen($hora_post) == 5 ? $hora_post . ':00' : $hora_post) : date('H:i:s');
    
    if (!empty($placa)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sortiing (placa, usuario_porteria, fecha_ingreso, hora_ingreso, estado, operacion_id) VALUES (?, ?, ?, ?, 'pendiente', ?)");
            $stmt->execute([strtoupper($placa), $nombre_usuario, $fecha_ingreso, $hora_ingreso, getOperacionActiva()]);
            $mensaje = 'Vehículo con placa ' . strtoupper($placa) . ' ingresado correctamente.';
            $tipo_mensaje = 'success';
        } catch (PDOException $e) {
            $mensaje = 'Error al registrar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = 'La placa es un campo obligatorio.';
        $tipo_mensaje = 'warning';
    }
}






$rango_fechas = $_GET['rango_fechas'] ?? '';
$fecha_inicio = $fecha_actual;
$fecha_fin = $fecha_actual;

if (!empty($rango_fechas)) {
    
    $fechas = explode(' to ', $rango_fechas);
    $fecha_inicio = $fechas[0];
    $fecha_fin = isset($fechas[1]) ? $fechas[1] : $fechas[0]; 
}


$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;


$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM sortiing WHERE fecha_ingreso BETWEEN ? AND ? AND operacion_id = ?");
$stmt_total->execute([$fecha_inicio, $fecha_fin, getOperacionActiva()]);
$total_registros = $stmt_total->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);


$sql = "SELECT * FROM sortiing WHERE fecha_ingreso BETWEEN :inicio AND :fin AND operacion_id = :operacion_id ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':inicio', $fecha_inicio, PDO::PARAM_STR);
$stmt->bindValue(':fin', $fecha_fin, PDO::PARAM_STR);
$stmt->bindValue(':operacion_id', getOperacionActiva(), PDO::PARAM_INT);
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$registros = $stmt->fetchAll();


$url_filtros = "";
if (!empty($rango_fechas)) {
    $url_filtros = "&rango_fechas=" . urlencode($rango_fechas);
}


require_once '../../core/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { poppins: ['Poppins', 'sans-serif'], },
        colors: { gold: '#FFD700', goldDark: '#E6C200', darkBg: '#121212', }
      }
    }
  }
</script>

<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    .flatpickr-calendar { font-family: 'Poppins', sans-serif !important; border-radius: 1rem !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; border: none !important; }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay { background: #121212 !important; border-color: #121212 !important; color: #FFD700 !important; font-weight: bold; }
    .flatpickr-day.inRange { box-shadow: -5px 0 0 #fef9c3, 5px 0 0 #fef9c3 !important; background: #fef9c3 !important; border-color: #fef9c3 !important; }
    .flatpickr-day:hover { background: #fef9c3 !important; }
    
    .modal-enter { opacity: 0; transform: scale(0.95) translateY(10px); }
    .modal-enter-active { opacity: 1; transform: scale(1) translateY(0); transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
    .modal-leave-active { opacity: 0; transform: scale(0.95) translateY(10px); transition: all 0.3s ease-in; }
</style>

<div id="toast-container" class="fixed top-24 right-5 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 font-poppins relative z-10">
    
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold/20 text-goldDark text-xs font-bold mb-3 border border-gold/30 uppercase tracking-wider">
                <i class="fas fa-shield-alt"></i> Seguridad
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">
                Control de <span class="text-transparent bg-clip-text bg-gradient-to-r from-goldDark to-yellow-500">Portería</span>
            </h1>
            <p class="text-sm text-gray-500 mt-2 font-medium">Gestión de ingreso y trazabilidad de vehículos.</p>
        </div>
        
        <button onclick="openModal()" class="bg-darkBg hover:bg-black text-gold font-bold text-sm py-3 px-8 rounded-xl shadow-[0_8px_20px_rgba(0,0,0,0.15)] hover:shadow-[0_8px_25px_rgba(255,215,0,0.25)] transition-all duration-300 transform hover:-translate-y-1 flex items-center gap-3">
            <i class="fas fa-truck-loading text-lg"></i>
            Ingresar Vehículo
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <form method="GET" action="sorting_porteria.php" class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
            <label class="text-sm font-bold text-gray-700 whitespace-nowrap">Rango de Fechas:</label>
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="far fa-calendar-alt text-gray-400"></i>
                </div>
                <input type="text" name="rango_fechas" id="filtro_fechas" value="<?php echo htmlspecialchars($rango_fechas); ?>" placeholder="Seleccionar fechas..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 focus:ring-2 focus:ring-gold/50 focus:border-gold outline-none cursor-pointer hover:bg-gray-100 transition-all">
            </div>
            <button type="submit" class="px-5 py-2 bg-gray-900 hover:bg-black text-white text-sm font-bold rounded-lg transition-all shadow-md">
                Filtrar
            </button>
            <?php if(!empty($rango_fechas)): ?>
                <a href="sorting_porteria.php" class="px-4 py-2 text-gray-500 hover:text-red-500 text-sm font-bold transition-all" title="Limpiar Filtros">
                    <i class="fas fa-times-circle text-lg"></i>
                </a>
            <?php endif; ?>
        </form>

        <div class="text-sm font-bold text-gray-500 bg-gray-50 px-4 py-2 rounded-lg border border-gray-100">
            Total Registros: <span class="text-darkBg"><?php echo $total_registros; ?></span>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden relative flex flex-col">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-gold to-yellow-400"></div>

        <div class="overflow-x-auto flex-grow">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th scope="col" class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Placa</th>
                        <th scope="col" class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Vigilante</th>
                        <th scope="col" class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Fecha / Hora</th>
                        <th scope="col" class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-8 py-5 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100 shadow-inner">
                                    <i class="fas fa-search text-3xl text-gray-300"></i>
                                </div>
                                <p class="text-xl font-bold text-gray-700">Sin registros encontrados</p>
                                <p class="text-gray-400 mt-1 text-sm">No hay vehículos para la fecha o rango seleccionado.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $row): ?>
                        <tr class="hover:bg-blue-50/30 transition-all duration-200">
                            <td class="px-8 py-5 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-400">#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="inline-flex items-center justify-center px-4 py-1.5 bg-gray-900 border-2 border-gray-800 rounded-lg shadow-sm">
                                    <span class="text-gold font-bold tracking-widest text-sm uppercase">
                                        <?php echo htmlspecialchars($row['placa']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 border border-gray-300 flex items-center justify-center text-gray-600 text-xs font-bold shadow-inner">
                                        <?php echo substr($row['usuario_porteria'], 0, 1); ?>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($row['usuario_porteria']); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-800"><?php echo date('h:i A', strtotime($row['hora_ingreso'])); ?></span>
                                    <span class="text-xs text-gray-400"><?php echo date('d/m/Y', strtotime($row['fecha_ingreso'])); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <?php if ($row['estado'] === 'pendiente'): ?>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-yellow-50 text-yellow-700 text-xs font-bold border border-yellow-200 uppercase tracking-wide">
                                        <i class="fas fa-hourglass-half"></i> Pendiente
                                    </div>
                                <?php else: ?>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-green-50 text-green-700 text-xs font-bold border border-green-200 uppercase tracking-wide">
                                        <i class="fas fa-check-circle"></i> Completado
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-center">
                                <button 
                                    onclick='openDetailsModal(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 hover:text-darkBg text-xs font-bold rounded-lg transition-all duration-300 border border-gray-200">
                                    <i class="fas fa-eye"></i> Detalles
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="bg-white border-t border-gray-100 px-8 py-4 flex items-center justify-between">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">
                        Mostrando <span class="font-bold text-gray-900"><?php echo $offset + 1; ?></span> a 
                        <span class="font-bold text-gray-900"><?php echo min($offset + $registros_por_pagina, $total_registros); ?></span> de 
                        <span class="font-bold text-gray-900"><?php echo $total_registros; ?></span> resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo $url_filtros; ?>" class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </a>
                        <?php else: ?>
                            <span class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-200 bg-gray-50 text-sm font-medium text-gray-300 cursor-not-allowed">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </span>
                        <?php endif; ?>

                        <?php 
                        $inicio_pag = max(1, $pagina_actual - 2);
                        $fin_pag = min($total_paginas, $pagina_actual + 2);
                        
                        if ($inicio_pag > 1) {
                            echo '<a href="?pagina=1'.$url_filtros.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                            if ($inicio_pag > 2) echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }

                        for ($i = $inicio_pag; $i <= $fin_pag; $i++): 
                        ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-900 bg-gray-900 text-sm font-bold text-gold z-10 shadow-sm">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="?pagina=<?php echo $i; ?><?php echo $url_filtros; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php 
                        if ($fin_pag < $total_paginas) {
                            if ($fin_pag < $total_paginas - 1) echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                            echo '<a href="?pagina='.$total_paginas.$url_filtros.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$total_paginas.'</a>';
                        }
                        ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo $url_filtros; ?>" class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </a>
                        <?php else: ?>
                            <span class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-200 bg-gray-50 text-sm font-medium text-gray-300 cursor-not-allowed">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </span>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
            <div class="flex items-center justify-between sm:hidden w-full">
                <a href="<?php echo ($pagina_actual > 1) ? '?pagina='.($pagina_actual - 1).$url_filtros : '#'; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?php echo ($pagina_actual <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                    Anterior
                </a>
                <span class="text-sm text-gray-500">Pág <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
                <a href="<?php echo ($pagina_actual < $total_paginas) ? '?pagina='.($pagina_actual + 1).$url_filtros : '#'; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?php echo ($pagina_actual >= $total_paginas) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                    Siguiente
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

</main>

<div id="ingresoModal" class="fixed inset-0 z-[1002] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity duration-300 opacity-0" id="modalBackdrop"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-lg bg-white rounded-[2rem] shadow-2xl overflow-hidden modal-enter" id="modalPanel">
                <div class="bg-darkBg px-8 py-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gold/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                    <div class="flex justify-between items-center relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-gold to-yellow-600 flex items-center justify-center shadow-lg shadow-gold/20">
                                <i class="fas fa-car-side text-darkBg text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white tracking-wide">Nuevo Ingreso</h3>
                                <p class="text-gold/70 text-xs font-medium uppercase tracking-wider">Punto de Control</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeModal()" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                </div>

                <form action="sorting_porteria.php" method="POST" class="p-8" id="formIngreso">
                    <input type="hidden" name="action" value="ingreso_vehiculo">
                    
                    <div class="mb-6 bg-gray-50 rounded-xl p-4 border border-gray-100 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-500 border border-gray-200">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Guardia en Turno</span>
                            <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nombre_usuario); ?></span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label for="placa" class="block text-sm font-bold text-gray-700 mb-2">Placa del Vehículo <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-keyboard text-gray-400"></i>
                                </div>
                                <input type="text" name="placa" id="placa" required placeholder="EJ: ABC-123" maxlength="10"
                                    class="w-full pl-11 pr-4 py-3.5 bg-white border border-gray-200 rounded-xl text-gray-900 font-bold text-xl uppercase tracking-widest shadow-sm focus:border-gold focus:ring-4 focus:ring-gold/20 transition-all outline-none placeholder:normal-case placeholder:font-medium placeholder:text-gray-300">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha de Ingreso</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-calendar-day text-goldDark"></i></div>
                                    <input type="text" name="fecha" id="fecha_input" required class="w-full pl-10 pr-3 py-3 bg-white border border-gray-200 rounded-xl text-gray-800 font-semibold text-sm shadow-sm focus:border-gold focus:ring-4 focus:ring-gold/20 transition-all cursor-pointer hover:bg-gray-50">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Hora de Ingreso</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-clock text-goldDark"></i></div>
                                    <input type="text" name="hora" id="hora_input" required class="w-full pl-10 pr-3 py-3 bg-white border border-gray-200 rounded-xl text-gray-800 font-semibold text-sm shadow-sm focus:border-gold focus:ring-4 focus:ring-gold/20 transition-all cursor-pointer hover:bg-gray-50">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <button type="button" onclick="closeModal()" class="w-full sm:w-1/3 px-6 py-3.5 border-2 border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all">Cancelar</button>
                        <button type="submit" class="w-full sm:w-2/3 px-6 py-3.5 bg-darkBg text-gold font-bold text-lg rounded-xl hover:bg-black shadow-[0_8px_20px_rgba(0,0,0,0.15)] hover:shadow-[0_8px_25px_rgba(255,215,0,0.25)] transition-all transform hover:-translate-y-1 flex items-center justify-center gap-2"><i class="fas fa-check-circle"></i> Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="detailsModal" class="fixed inset-0 z-[1003] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity duration-300 opacity-0" id="detailsBackdrop"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-2xl bg-white rounded-[2rem] shadow-2xl overflow-hidden modal-enter" id="detailsPanel">
                
                <div class="bg-gray-50 px-8 py-6 border-b border-gray-100 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gray-900 flex items-center justify-center shadow-lg">
                            <i class="fas fa-clipboard-list text-gold text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Detalles del Vehículo</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Placa:</span>
                                <span id="det_placa" class="text-xs font-black text-darkBg bg-gold/20 px-2 py-0.5 rounded border border-gold/30"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="closeDetailsModal()" class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-500 hover:text-red-500 transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-8 space-y-6">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
                            <i class="fas fa-shield-alt text-gray-400"></i> Registro de Ingreso
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Vigilante</span>
                                <span id="det_usuario_porteria" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Fecha Ingreso</span>
                                <span id="det_fecha_ingreso" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Hora Llegada</span>
                                <span id="det_hora_ingreso" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
                            <i class="fas fa-boxes text-goldDark"></i> Proceso de Sorting
                        </h4>
                        
                        <div id="det_sorting_pendiente" class="hidden bg-yellow-50 rounded-xl p-6 border border-yellow-200 text-center">
                            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-hourglass-half text-xl"></i>
                            </div>
                            <h5 class="text-yellow-800 font-bold text-sm">Vehículo en espera</h5>
                            <p class="text-yellow-600 text-xs mt-1">El equipo de Sorting aún no ha procesado esta carga.</p>
                        </div>

                        <div id="det_sorting_completado" class="hidden space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Operador Sorting</span>
                                    <span id="det_usuario_sorting" class="text-sm font-semibold text-gray-800"></span>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Fin del Proceso</span>
                                    <span id="det_fecha_hora_sorting" class="text-sm font-semibold text-gray-800"></span>
                                </div>
                            </div>
                            
                            <div class="bg-darkBg rounded-xl p-5 border border-gray-800 text-white flex flex-col sm:flex-row items-center justify-between gap-4 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-gold/5 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2"></div>
                                <div class="relative z-10 w-full">
                                    <span class="block text-[10px] font-bold text-gold/70 uppercase tracking-wider mb-1">Producto / SKU Procesado</span>
                                    <div class="flex items-center gap-2">
                                        <span id="det_sku" class="px-2 py-0.5 bg-white/10 rounded text-xs font-mono font-bold"></span>
                                        <span id="det_material" class="text-sm font-bold truncate"></span>
                                    </div>
                                </div>
                                <div class="relative z-10 text-center sm:text-right w-full sm:w-auto min-w-[120px] border-t sm:border-t-0 sm:border-l border-white/10 pt-3 sm:pt-0 sm:pl-5">
                                    <span class="block text-[10px] font-bold text-gold/70 uppercase tracking-wider mb-1">Total Cajas</span>
                                    <span id="det_cajas" class="text-3xl font-black text-gold"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
                    <button onclick="closeDetailsModal()" class="w-full sm:w-auto px-8 py-2.5 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-100 transition-all">
                        Cerrar Detalles
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    
    document.addEventListener('DOMContentLoaded', function() {
        
        flatpickr("#filtro_fechas", {
            mode: "range",
            locale: "es",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M, Y",
        });

        
        flatpickr("#fecha_input", { locale: "es", dateFormat: "Y-m-d", altInput: true, altFormat: "d F, Y", defaultDate: "<?php echo $fecha_actual; ?>", disableMobile: "true" });
        flatpickr("#hora_input", { enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: false, defaultDate: "<?php echo $hora_actual; ?>", disableMobile: "true" });
    });

    
    const modal = document.getElementById('ingresoModal');
    const backdrop = document.getElementById('modalBackdrop');
    const panel = document.getElementById('modalPanel');

    function openModal() {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('modal-enter');
            panel.classList.add('modal-enter-active');
            setTimeout(() => document.getElementById('placa').focus(), 100);
        });
    }

    function closeModal() {
        backdrop.classList.add('opacity-0');
        panel.classList.remove('modal-enter-active');
        panel.classList.add('modal-leave-active');
        setTimeout(() => {
            modal.classList.add('hidden');
            panel.classList.remove('modal-leave-active');
            panel.classList.add('modal-enter'); 
            document.getElementById('formIngreso').reset();
            document.querySelector("#fecha_input")._flatpickr.setDate("<?php echo $fecha_actual; ?>");
            document.querySelector("#hora_input")._flatpickr.setDate("<?php echo $hora_actual; ?>");
        }, 300);
    }

    
    const detailsModal = document.getElementById('detailsModal');
    const detailsBackdrop = document.getElementById('detailsBackdrop');
    const detailsPanel = document.getElementById('detailsPanel');

    function formatearFecha(fechaStr) {
        if(!fechaStr) return '';
        const opciones = { year: 'numeric', month: 'short', day: 'numeric' };
        
        return new Date(fechaStr + 'T00:00:00').toLocaleDateString('es-ES', opciones);
    }

    function formatearHora(horaStr) {
        if(!horaStr) return '';
        return new Date('1970-01-01T' + horaStr).toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit', hour12: true});
    }

    function openDetailsModal(data) {
        document.getElementById('det_placa').textContent = data.placa;
        document.getElementById('det_usuario_porteria').textContent = data.usuario_porteria;
        document.getElementById('det_fecha_ingreso').textContent = formatearFecha(data.fecha_ingreso);
        document.getElementById('det_hora_ingreso').textContent = formatearHora(data.hora_ingreso);

        const divPendiente = document.getElementById('det_sorting_pendiente');
        const divCompletado = document.getElementById('det_sorting_completado');

        if(data.estado === 'pendiente') {
            divPendiente.classList.remove('hidden');
            divCompletado.classList.add('hidden');
        } else {
            divPendiente.classList.add('hidden');
            divCompletado.classList.remove('hidden');
            document.getElementById('det_usuario_sorting').innerHTML = `<i class="fas fa-user-check text-gold mr-1"></i> ${data.usuario_sorting}`;
            document.getElementById('det_fecha_hora_sorting').textContent = `${formatearFecha(data.fecha_sorting)} - ${formatearHora(data.hora_sorting)}`;
            document.getElementById('det_sku').textContent = `SKU: ${data.sku}`;
            document.getElementById('det_material').textContent = data.material;
            document.getElementById('det_cajas').textContent = data.cajas_sorting;
        }

        detailsModal.classList.remove('hidden');
        requestAnimationFrame(() => {
            detailsBackdrop.classList.remove('opacity-0');
            detailsPanel.classList.remove('modal-enter');
            detailsPanel.classList.add('modal-enter-active');
        });
    }

    function closeDetailsModal() {
        detailsBackdrop.classList.add('opacity-0');
        detailsPanel.classList.remove('modal-enter-active');
        detailsPanel.classList.add('modal-leave-active');
        setTimeout(() => {
            detailsModal.classList.add('hidden');
            detailsPanel.classList.remove('modal-leave-active');
            detailsPanel.classList.add('modal-enter'); 
        }, 300);
    }

    
    function showToast(message, type) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        let bgColor = 'bg-white', icon, borderColor;
        if(type === 'success') {
            borderColor = 'border-l-4 border-green-500';
            icon = '<div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center"><i class="fas fa-check text-green-500"></i></div>';
        } else if(type === 'error') {
            borderColor = 'border-l-4 border-red-500';
            icon = '<div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center"><i class="fas fa-times text-red-500"></i></div>';
        } else {
            borderColor = 'border-l-4 border-yellow-500';
            icon = '<div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center"><i class="fas fa-exclamation text-yellow-500"></i></div>';
        }
        
        toast.className = `flex items-center w-full max-w-sm p-4 text-gray-800 ${bgColor} ${borderColor} rounded-xl shadow-2xl transform transition-all duration-500 translate-x-full opacity-0 pointer-events-auto`;
        toast.innerHTML = `${icon}<div class="ml-3"><p class="text-sm font-bold text-gray-900">${type === 'success' ? '¡Éxito!' : (type === 'error' ? 'Error' : 'Aviso')}</p><p class="text-xs font-medium text-gray-500 mt-0.5">${message}</p></div>`;

        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.remove('translate-x-full', 'opacity-0'));
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    <?php if ($mensaje !== ''): ?>
        document.addEventListener('DOMContentLoaded', () => showToast("<?php echo addslashes($mensaje); ?>", "<?php echo $tipo_mensaje; ?>"));
    <?php endif; ?>
</script>

</body>
</html>