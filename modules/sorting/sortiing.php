<?php
require_once '../../core/config.php';
verificarLogin();
date_default_timezone_set('America/Bogota');

$fecha_actual = date('Y-m-d');
$hora_actual = date('H:i:s');
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario Desconocido';

$mensaje = '';
$tipo_mensaje = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'completar_sorting') {
    $id_registro = limpiarDatos($_POST['id_registro']);
    $envase = limpiarDatos($_POST['envase']); 
    $cajas = limpiarDatos($_POST['cajas_sorting']);
    

    if (!empty($id_registro) && !empty($envase) && !empty($cajas)) {
        try {
            
            $stmt = $pdo->prepare("UPDATE sortiing SET 
                usuario_sorting = ?, 
                fecha_sorting = ?, 
                hora_sorting = ?, 
                envase = ?, 
                cajas_sorting = ?, 
                estado = 'completado'
                WHERE id = ? AND operacion_id = ?");

            $stmt->execute([
                $nombre_usuario,
                $fecha_actual,
                $hora_actual,
                $envase,
                $cajas,
                $id_registro,
                getOperacionActiva()
            ]);
            
            $mensaje = 'Registro actualizado correctamente. El ciclo de sorting ha finalizado.';
            $tipo_mensaje = 'success';
        } catch (PDOException $e) {
            $mensaje = 'Error al actualizar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo_mensaje = 'warning';
    }
}


$stmt_pendientes = $pdo->prepare("SELECT * FROM sortiing WHERE estado = 'pendiente' AND DATE(fecha_ingreso) = :fecha_actual AND operacion_id = :operacion_id ORDER BY id ASC");
$stmt_pendientes->execute(['fecha_actual' => $fecha_actual, 'operacion_id' => getOperacionActiva()]);
$pendientes = $stmt_pendientes->fetchAll();



require_once '../../core/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    
    
    .row-hidden { display: none; }
    .row-fade { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    
    .modal-enter { opacity: 0; transform: scale(0.95) translateY(10px); }
    .modal-enter-active { opacity: 1; transform: scale(1) translateY(0); transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
</style>

<div id="toast-container" class="fixed top-24 right-5 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 relative z-10">
    
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold/20 text-goldDark text-xs font-bold mb-3 border border-gold/30 uppercase tracking-wider">
                <i class="fas fa-boxes"></i> Logística
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">
                Operación de <span class="text-transparent bg-clip-text bg-gradient-to-r from-goldDark to-yellow-500">Sorting</span>
            </h1>
            <p class="text-sm text-gray-500 mt-2 font-medium">Gestión y procesamiento de la carga entrante de hoy.</p>
        </div>

        <div class="w-full md:w-96">
            <label for="placaSearch" class="block text-xs font-bold text-gray-400 uppercase mb-2 ml-1 tracking-widest">Buscar por Placa</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 group-focus-within:text-goldDark transition-colors"></i>
                </div>
                <input type="text" id="placaSearch" placeholder="Ej: ABC-123..." 
                    class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-2xl shadow-sm outline-none focus:border-gold focus:ring-4 focus:ring-gold/10 transition-all text-sm font-semibold text-gray-700 uppercase">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                    <span id="counterDisplay" class="text-[10px] font-bold bg-gray-100 text-gray-400 px-2 py-1 rounded-lg border border-gray-200 uppercase">
                        <?php echo count($pendientes); ?> Vehículos
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-gold to-yellow-400"></div>
        
        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white/50 backdrop-blur-sm">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-truck-loading text-goldDark text-2xl"></i>
                Vehículos en Espera (Hoy)
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100" id="sortingTable">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">ID</th>
                        <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Placa</th>
                        <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Registrado por</th>
                        <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Hora Llegada</th>
                        <th class="px-8 py-5 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Acción</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    <?php if (empty($pendientes)): ?>
                    <tr id="noResultsRow">
                        <td colspan="5" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                                    <i class="fas fa-check-double text-3xl text-green-400"></i>
                                </div>
                                <p class="text-xl font-bold text-gray-700">¡Todo al día!</p>
                                <p class="text-sm text-gray-500 mt-2">No hay vehículos pendientes ingresados el día de hoy.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($pendientes as $vh): ?>
                        <tr class="table-row-item hover:bg-blue-50/30 transition-all duration-200 group" data-placa="<?php echo strtoupper($vh['placa']); ?>">
                            <td class="px-8 py-5 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-400">#<?php echo str_pad($vh['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="inline-flex items-center justify-center px-4 py-1.5 bg-gray-900 border-2 border-gray-800 rounded-lg shadow-sm">
                                    <span class="text-gold font-bold tracking-widest text-sm uppercase placa-text">
                                        <?php echo htmlspecialchars($vh['placa']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 border border-gray-300 flex items-center justify-center text-gray-600 text-xs font-bold">
                                        <?php echo substr($vh['usuario_porteria'], 0, 1); ?>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($vh['usuario_porteria']); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-800"><?php echo date('h:i A', strtotime($vh['hora_ingreso'])); ?></span>
                                    <span class="text-xs text-gray-400"><?php echo date('d M, Y', strtotime($vh['fecha_ingreso'])); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-center">
                                <button 
                                    onclick="openSortingModal(<?php echo $vh['id']; ?>, '<?php echo $vh['placa']; ?>')"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-black hover:bg-gray-800 text-gold text-sm font-bold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 group-hover:ring-2 ring-gold/30">
                                    <i class="fas fa-sign-in-alt"></i> INGRESAR
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="searchEmptyRow" class="hidden">
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-search text-4xl text-gray-200 mb-4"></i>
                                    <p class="text-lg font-bold text-gray-500">No se encontraron placas con ese nombre</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="sortingModal" class="fixed inset-0 z-[1002] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity duration-300 opacity-0" id="modalBackdrop"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-[2rem] shadow-2xl overflow-hidden modal-enter" id="modalPanel">
                <div class="bg-darkBg px-8 py-6">
                    <div class="flex justify-between items-center relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-gold to-yellow-600 flex items-center justify-center">
                                <i class="fas fa-clipboard-check text-darkBg text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Completar Ciclo</h3>
                                <p class="text-gold/70 text-xs font-medium uppercase">Módulo de Sorting</p>
                            </div>
                        </div>
                        <button onclick="closeSortingModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times text-lg"></i></button>
                    </div>
                </div>

                <form action="sortiing.php" method="POST" class="p-8">
                    <input type="hidden" name="action" value="completar_sorting">
                    <input type="hidden" name="id_registro" id="modal_id_registro">
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 p-5 bg-gray-50 rounded-2xl border border-gray-100">
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase">Placa</span>
                            <span id="modal_placa_display" class="text-sm font-black text-darkBg bg-gold/20 px-2 py-0.5 rounded border border-gold/30"></span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase">Fecha</span>
                            <span class="text-sm font-medium text-gray-600"><?php echo date('d/m/Y'); ?></span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Envase *</label>
                            <select name="envase" required class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl font-bold text-gray-700 outline-none focus:border-gold">
                                <option value="">Seleccione un envase...</option>
                                <option value="Estibas">Estibas</option>
                                <option value="Envase">Envase</option>
                                
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cajas Sorting *</label>
                            <input type="number" name="cajas_sorting" required min="1" placeholder="Ej. 150"
                                class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl font-bold text-lg outline-none focus:border-gold">
                        </div>
                    </div>

                    <div class="mt-10 flex gap-4">
                        <button type="button" onclick="closeSortingModal()" class="w-1/3 py-3.5 border-2 border-gray-200 text-gray-600 font-bold rounded-xl">Cancelar</button>
                        <button type="submit" class="w-2/3 py-3.5 bg-darkBg text-gold font-bold text-lg rounded-xl flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i> Guardar Registro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    
    document.getElementById('placaSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toUpperCase();
        const rows = document.querySelectorAll('.table-row-item');
        const emptyRow = document.getElementById('searchEmptyRow');
        const counter = document.getElementById('counterDisplay');
        let visibleCount = 0;

        rows.forEach(row => {
            const placa = row.getAttribute('data-placa');
            if (placa.includes(searchTerm)) {
                row.classList.remove('row-hidden');
                row.classList.add('row-fade');
                visibleCount++;
            } else {
                row.classList.add('row-hidden');
                row.classList.remove('row-fade');
            }
        });

        
        if (visibleCount === 0 && searchTerm !== "") {
            emptyRow.classList.remove('hidden');
        } else {
            emptyRow.classList.add('hidden');
        }

        
        counter.textContent = `${visibleCount} Vehículos`;
    });

    

    function openSortingModal(id, placa) {
        document.getElementById('modal_id_registro').value = id;
        document.getElementById('modal_placa_display').textContent = placa;
        
        
        document.querySelector('select[name="envase"]').value = "";
        document.querySelector('input[name="cajas_sorting"]').value = "";

        document.getElementById('sortingModal').classList.remove('hidden');
        requestAnimationFrame(() => {
            document.getElementById('modalBackdrop').classList.remove('opacity-0');
            document.getElementById('modalPanel').classList.remove('modal-enter');
            document.getElementById('modalPanel').classList.add('modal-enter-active');
        });
    }

    function closeSortingModal() {
        document.getElementById('modalBackdrop').classList.add('opacity-0');
        document.getElementById('modalPanel').classList.add('modal-leave-active');
        setTimeout(() => {
            document.getElementById('sortingModal').classList.add('hidden');
        }, 300);
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