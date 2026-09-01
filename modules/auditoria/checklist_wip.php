<?php
require_once '../../core/config.php';
require_once '../../core/header.php';

verificarLogin();

$nombre_usuario = $_SESSION['nombre'] ?? '';
$fecha_hoy = date('d/m/Y');

$mensaje = '';
$tipo_mensaje = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $preguntas = [
        'golpes_fisuras', 'botones_funcionan', 'camara_limpia', 
        'conectividad', 'forro_completo', 'condiciones_seguras'
    ];

    $datos = [];
    foreach ($preguntas as $p) {
        $datos[$p] = isset($_POST[$p]) ? limpiarDatos($_POST[$p]) : null;
        $obs_key = 'obs_' . $p;
        $datos[$obs_key] = isset($_POST[$obs_key]) ? limpiarDatos($_POST[$obs_key]) : null;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO checklist_wip
            (nombre_usuario, fecha_registro, golpes_fisuras, obs_golpes_fisuras, botones_funcionan, obs_botones_funcionan, camara_limpia, obs_camara_limpia, conectividad, obs_conectividad, forro_completo, obs_forro_completo, condiciones_seguras, obs_condiciones_seguras, operacion_id)
            VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nombre_usuario,
            $datos['golpes_fisuras'], $datos['obs_golpes_fisuras'],
            $datos['botones_funcionan'], $datos['obs_botones_funcionan'],
            $datos['camara_limpia'], $datos['obs_camara_limpia'],
            $datos['conectividad'], $datos['obs_conectividad'],
            $datos['forro_completo'], $datos['obs_forro_completo'],
            $datos['condiciones_seguras'], $datos['obs_condiciones_seguras'],
            getOperacionActiva()
        ]);
        $mensaje = 'Checklist guardado exitosamente.';
        $tipo_mensaje = 'success';
    } catch (PDOException $e) {
        $mensaje = 'Error al guardar: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}


$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

$rango_fechas = $_GET['rango_fechas'] ?? '';
$where_clause = "operacion_id = ?";
$params = [getOperacionActiva()];

if ($rango_fechas) {
    if (strpos($rango_fechas, ' to ') !== false) {
        
        list($fecha_inicio, $fecha_fin) = explode(' to ', $rango_fechas);
        $where_clause .= " AND fecha_registro BETWEEN ? AND ?";
        $params[] = $fecha_inicio;
        $params[] = $fecha_fin;
    } else {
        
        $where_clause .= " AND fecha_registro = ?";
        $params[] = $rango_fechas;
    }
}


$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM checklist_wip WHERE $where_clause");
$total_stmt->execute($params);
$total_registros = $total_stmt->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);


$registros_query = "SELECT * FROM checklist_wip WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$registros_stmt = $pdo->prepare($registros_query);

$param_idx = 1;
foreach($params as $p) {
    $registros_stmt->bindValue($param_idx++, $p);
}
$registros_stmt->bindValue($param_idx++, $por_pagina, PDO::PARAM_INT);
$registros_stmt->bindValue($param_idx, $offset, PDO::PARAM_INT);
$registros_stmt->execute();
$registros = $registros_stmt->fetchAll();

$preguntas_labels = [
    'golpes_fisuras'     => '¿El equipo presenta golpes, fisuras o pantalla rota?',
    'botones_funcionan'  => '¿Los botones (encendido, volumen) funcionan correctamente?',
    'camara_limpia'      => '¿La cámara está limpia y sin daños?',
    'conectividad'       => '¿Tiene buena conectividad (datos móviles / WiFi)?',
    'forro_completo'     => '¿El forro está completo (sin partes rotas o desprendidas)?',
    'condiciones_seguras'=> '¿El equipo y el forro están en condiciones seguras para su uso en operación?',
];


$qs_params = [];
if ($rango_fechas) $qs_params['rango_fechas'] = $rango_fechas;
$query_string = http_build_query($qs_params);
$url_paginacion = '?' . ($query_string ? $query_string . '&' : '') . 'pagina=';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

<style>
* { font-family: 'Poppins', sans-serif; }
.gold-border { border-color: #FFD700; }
.gold-bg { background-color: #FFD700; }
.gold-text { color: #FFD700; }


.flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
    background: #FFD700 !important;
    border-color: #FFD700 !important;
    color: #000 !important;
    font-weight: bold;
}
.flatpickr-day.inRange {
    background: #fff8c4 !important;
    border-color: #fff8c4 !important;
    box-shadow: -5px 0 0 #fff8c4, 5px 0 0 #fff8c4 !important;
}

.radio-option input[type="radio"]:checked + label {
    background-color: #FFD700;
    color: #000;
    border-color: #FFD700;
    font-weight: 600;
}
.radio-option input[type="radio"] { display: none; }
.obs-field { display: none; }
.obs-field.visible { display: block; }
</style>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col md:flex-row justify-between items-center mb-8 bg-white p-6 rounded-2xl shadow-sm border border-yellow-200">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-2 h-8 gold-bg rounded-full"></div>
                    <h1 class="text-3xl font-bold text-gray-900">Checklist WIP</h1>
                </div>
                <p class="text-gray-500 text-sm ml-5">Revisión de condiciones del teléfono de trabajo</p>
            </div>
            
            <button onclick="abrirModal()" class="mt-4 md:mt-0 inline-flex items-center gap-2 bg-yellow-400 text-black px-6 py-3 rounded-xl font-bold text-sm hover:bg-yellow-500 transition-all shadow-lg hover:shadow-yellow-400/50">
                <i class="fas fa-plus-circle text-lg"></i>
                Nuevo Registro
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="bg-yellow-400 px-6 py-3 flex items-center gap-3">
                <i class="fas fa-filter text-black text-lg"></i>
                <h2 class="text-black font-bold text-base">Filtros de Búsqueda</h2>
            </div>
            <div class="p-5">
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i> Rango de Fechas
                        </label>
                        <input type="text" name="rango_fechas" id="rango_fechas" value="<?php echo htmlspecialchars($rango_fechas); ?>" placeholder="Selecciona el rango de fechas..." class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-xl text-sm font-medium focus:ring-2 focus:ring-yellow-400 focus:outline-none">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-black text-yellow-400 px-5 py-2 rounded-xl font-semibold text-sm hover:bg-gray-800 transition-colors">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <?php if ($rango_fechas): ?>
                        <a href="?" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-semibold text-sm hover:bg-gray-300 transition-colors">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white border border-yellow-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="bg-black px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-list-check text-yellow-400 text-lg"></i>
                    <h2 class="text-white font-semibold text-base">Historial de Registros</h2>
                </div>
                <span class="text-black text-xs font-bold bg-yellow-400 px-3 py-1 rounded-full shadow-sm">
                    <?php echo $total_registros; ?> registros
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-yellow-50 border-b border-yellow-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">#</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Responsable</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Fecha</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Golpes/Fisuras</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Botones</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Cámara</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Conectividad</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Forro</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Condiciones</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-gray-500 bg-gray-50">
                                <i class="fas fa-search-minus text-4xl mb-3 block text-yellow-400"></i>
                                <span class="text-base font-medium">No se encontraron registros</span>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php $num = $offset + 1; foreach ($registros as $r): ?>
                        <tr class="hover:bg-yellow-50/50 transition-colors">
                            <td class="px-4 py-3 text-gray-500 font-medium"><?php echo $num++; ?></td>
                            <td class="px-4 py-3 font-semibold text-gray-900"><?php echo htmlspecialchars($r['nombre_usuario']); ?></td>
                            <td class="px-4 py-3 text-gray-700 font-medium"><?php echo date('d/m/Y', strtotime($r['fecha_registro'])); ?></td>
                            <?php
                            $campos = ['golpes_fisuras', 'botones_funcionan', 'camara_limpia', 'conectividad', 'forro_completo', 'condiciones_seguras'];
                            foreach ($campos as $c):
                                $val = $r[$c];
                            ?>
                            <td class="px-4 py-3 text-center">
                                <?php if ($val === 'si'): ?>
                                    <span class="inline-flex items-center justify-center w-7 h-7 bg-green-100 text-green-600 rounded-full border border-green-200 shadow-sm">
                                        <i class="fas fa-check text-xs"></i>
                                    </span>
                                <?php elseif ($val === 'no'): ?>
                                    <span class="inline-flex items-center justify-center w-7 h-7 bg-red-100 text-red-600 rounded-full border border-red-200 shadow-sm">
                                        <i class="fas fa-times text-xs"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-300">—</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                            <td class="px-4 py-3 text-center">
                                <button onclick="verDetalle(<?php echo $r['id']; ?>)"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-black hover:text-white bg-yellow-400 hover:bg-black px-3 py-1.5 rounded-lg transition-colors border border-yellow-500 shadow-sm">
                                    <i class="fas fa-eye text-xs"></i> Ver
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between flex-wrap gap-3">
                <span class="text-sm text-gray-600">
                    Página <strong class="text-black"><?php echo $pagina; ?></strong> de <strong class="text-black"><?php echo $total_paginas; ?></strong>
                </span>
                <div class="flex gap-2">
                    <?php if ($pagina > 1): ?>
                    <a href="<?php echo $url_paginacion . ($pagina - 1); ?>"
                        class="inline-flex items-center gap-1 px-4 py-2 text-xs font-bold bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-yellow-400 hover:text-black hover:border-yellow-400 transition-colors shadow-sm">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $pagina - 2);
                    $end = min($total_paginas, $pagina + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="<?php echo $url_paginacion . $i; ?>"
                        class="inline-flex items-center justify-center w-9 h-9 text-xs font-bold rounded-lg shadow-sm transition-colors border <?php echo $i === $pagina ? 'bg-yellow-400 border-yellow-500 text-black' : 'bg-white border-gray-300 text-gray-700 hover:bg-yellow-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                    <a href="<?php echo $url_paginacion . ($pagina + 1); ?>"
                        class="inline-flex items-center gap-1 px-4 py-2 text-xs font-bold bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-yellow-400 hover:text-black hover:border-yellow-400 transition-colors shadow-sm">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<div id="modalChecklist" class="fixed inset-0 z-50 hidden bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center overflow-y-auto px-4 py-6">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl border border-yellow-400 transform transition-all relative">
        
        <div class="bg-yellow-400 px-6 py-4 rounded-t-2xl flex items-center justify-between border-b border-yellow-500">
            <div class="flex items-center gap-3">
                <i class="fas fa-clipboard-check text-black text-2xl"></i>
                <h2 class="text-black font-bold text-xl">Diligenciar Nuevo Checklist</h2>
            </div>
            <button type="button" onclick="cerrarModal()" class="text-black hover:text-white bg-transparent hover:bg-black/20 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="p-6 md:p-8 max-h-[80vh] overflow-y-auto">
            <form method="POST" id="formChecklist">
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">
                                <i class="fas fa-user text-yellow-500 mr-1"></i> Responsable
                            </label>
                            <input type="text" value="<?php echo htmlspecialchars($nombre_usuario); ?>" disabled
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-700 font-bold text-sm cursor-not-allowed shadow-inner">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">
                                <i class="fas fa-calendar text-yellow-500 mr-1"></i> Fecha de Diligenciamiento
                            </label>
                            <input type="text" value="<?php echo $fecha_hoy; ?>" disabled
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-700 font-bold text-sm cursor-not-allowed shadow-inner">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($preguntas_labels as $key => $label): ?>
                    <div class="border border-gray-200 bg-white rounded-xl p-5 hover:border-yellow-400 hover:shadow-md transition-all">
                        <p class="text-sm font-semibold text-gray-800 mb-4 h-10"><?php echo $label; ?></p>
                        <div class="flex gap-3 flex-wrap">
                            <div class="radio-option w-full sm:w-auto">
                                <input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>_si" value="si" required>
                                <label for="<?php echo $key; ?>_si"
                                    class="flex items-center justify-center w-full gap-2 px-6 py-2.5 border-2 border-gray-200 rounded-xl text-sm font-medium cursor-pointer transition-all duration-200 hover:border-yellow-400 hover:bg-yellow-50">
                                    <i class="fas fa-check text-green-500"></i> Sí
                                </label>
                            </div>
                            <div class="radio-option w-full sm:w-auto">
                                <input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>_no" value="no" required>
                                <label for="<?php echo $key; ?>_no"
                                    class="flex items-center justify-center w-full gap-2 px-6 py-2.5 border-2 border-gray-200 rounded-xl text-sm font-medium cursor-pointer transition-all duration-200 hover:border-red-400 hover:bg-red-50">
                                    <i class="fas fa-times text-red-500"></i> No
                                </label>
                            </div>
                        </div>
                        <div class="obs-field mt-4" id="obs_container_<?php echo $key; ?>">
                            <label class="block text-xs font-bold text-red-500 uppercase tracking-wide mb-2">
                                <i class="fas fa-exclamation-circle mr-1"></i> Justificación requerida
                            </label>
                            <textarea name="obs_<?php echo $key; ?>" id="obs_<?php echo $key; ?>" rows="2"
                                placeholder="Describe el motivo por el cual la respuesta es No..."
                                class="w-full px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-gray-700 font-medium focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200 resize-none"></textarea>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <button type="button" onclick="cerrarModal()" class="px-6 py-3 rounded-xl font-bold text-sm bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" name="guardar"
                        class="inline-flex items-center gap-2 bg-black text-yellow-400 px-8 py-3 rounded-xl font-bold text-sm hover:bg-gray-900 transition-all shadow-lg hover:shadow-xl border border-black">
                        <i class="fas fa-save"></i> Guardar Checklist
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$registros_json = json_encode($registros);
$preguntas_json = json_encode($preguntas_labels);
?>

<script>
const registrosData = <?php echo $registros_json; ?>;
const preguntasLabels = <?php echo $preguntas_json; ?>;


flatpickr("#rango_fechas", {
    mode: "range",
    locale: "es", 
    dateFormat: "Y-m-d", 
    altInput: true,
    altFormat: "d M, Y", 
    placeholder: "Arrastra para seleccionar fechas..."
});


function abrirModal() {
    document.getElementById('modalChecklist').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; 
}

function cerrarModal() {
    document.getElementById('modalChecklist').classList.add('hidden');
    document.body.style.overflow = 'auto'; 
}


document.getElementById('modalChecklist').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

<?php if ($mensaje): ?>
Swal.fire({
    icon: '<?php echo $tipo_mensaje; ?>',
    title: '<?php echo $tipo_mensaje === 'success' ? '¡Listo!' : 'Error'; ?>',
    text: '<?php echo $mensaje; ?>',
    confirmButtonColor: '#FFD700',
    confirmButtonText: '<span style="color:black; font-weight:bold;">Aceptar</span>',
    background: '#fff',
    color: '#000',
    customClass: { popup: 'custom-swal' }
});
<?php endif; ?>

document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const key = this.name;
        const obsContainer = document.getElementById('obs_container_' + key);
        const obsTextarea = document.getElementById('obs_' + key);

        if (this.value === 'no') {
            obsContainer.classList.add('visible');
            obsTextarea.required = true;
        } else {
            obsContainer.classList.remove('visible');
            obsTextarea.required = false;
            obsTextarea.value = '';
        }
    });
});

document.getElementById('formChecklist').addEventListener('submit', function (e) {
    const radios = ['golpes_fisuras', 'botones_funcionan', 'camara_limpia', 'conectividad', 'forro_completo', 'condiciones_seguras'];
    let todas_respondidas = true;

    radios.forEach(name => {
        const checked = document.querySelector(`input[name="${name}"]:checked`);
        if (!checked) todas_respondidas = false;
    });

    if (!todas_respondidas) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Formulario incompleto',
            text: 'Por favor responde todas las preguntas antes de guardar.',
            confirmButtonColor: '#FFD700',
            confirmButtonText: '<span style="color:black; font-weight:bold;">Entendido</span>',
            background: '#fff',
            color: '#000',
            customClass: { popup: 'custom-swal' }
        });
    }
});

function verDetalle(id) {
    const reg = registrosData.find(r => parseInt(r.id) === id);
    if (!reg) return;

    const campos = ['golpes_fisuras', 'botones_funcionan', 'camara_limpia', 'conectividad', 'forro_completo', 'condiciones_seguras'];

    let html = `<div style="font-family:'Poppins',sans-serif;text-align:left">`;
    html += `<div style="display:flex;justify-content:space-between;margin-bottom:20px;font-size:14px;color:#333; background:#FFF9C4; padding:10px; border-radius:10px; border:1px solid #FFD700;">
        <span style="font-weight:600;"><i class="fas fa-user" style="color:#000;margin-right:6px"></i>${reg.nombre_usuario}</span>
        <span style="font-weight:600;"><i class="fas fa-calendar" style="color:#000;margin-right:6px"></i>${new Date(reg.fecha_registro).toLocaleDateString('es-CO')}</span>
    </div>`;

    campos.forEach(c => {
        const label = preguntasLabels[c];
        const val = reg[c];
        const obs = reg['obs_' + c];
        const isNo = val === 'no';
        const icon = isNo ? '❌' : '✅';
        const color = isNo ? '#dc2626' : '#16a34a';
        const bg = isNo ? '#fef2f2' : '#f0fdf4';
        const borderColor = isNo ? '#fecaca' : '#bbf7d0';

        html += `<div style="border:1px solid ${borderColor}; background-color:${bg}; border-radius:12px; padding:14px; margin-bottom:12px;">
            <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 8px">${label}</p>
            <span style="font-size:14px;font-weight:700;color:${color}; background:#fff; padding:4px 10px; border-radius:8px; display:inline-block; border:1px solid ${borderColor};">${icon} ${val ? val.toUpperCase() : '—'}</span>
            ${isNo && obs ? `<p style="font-size:13px;color:#7f1d1d;margin:10px 0 0;padding-top:10px;border-top:1px solid ${borderColor}"><strong style="color:#991b1b;"><i class="fas fa-comment-dots" style="margin-right:5px"></i>Nota:</strong> ${obs}</p>` : ''}
        </div>`;
    });

    html += '</div>';

    Swal.fire({
        title: '<span style="font-weight:700; font-size:24px;">Detalle del Checklist</span>',
        html: html,
        width: 650,
        confirmButtonColor: '#000',
        confirmButtonText: '<span style="color:#FFD700; font-weight:bold;">Cerrar Resumen</span>',
        background: '#fff',
        color: '#000',
        customClass: { popup: 'custom-swal' }
    });
}
</script>

<style>
.custom-swal {
    border-radius: 24px !important;
    border: 2px solid #FFD700;
    box-shadow: 0 25px 50px -12px rgba(255, 215, 0, 0.25) !important;
}
</style>

</body>
</html>