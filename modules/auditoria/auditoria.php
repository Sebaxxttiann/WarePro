<?php

require_once __DIR__ . '/../../core/config.php';
require_once '../../core/header.php';

$errores = [];
$registros = [];
$modulosJS = [];
$zonasJS = []; 


$iconosAleatorios = ['fa-shield-halved', 'fa-chart-pie', 'fa-magnifying-glass-chart', 'fa-clipboard-check', 'fa-list-check', 'fa-file-signature', 'fa-box-archive', 'fa-scale-balanced'];
$coloresFondo = ['bg-blue-100', 'bg-green-100', 'bg-purple-100', 'bg-pink-100', 'bg-indigo-100', 'bg-teal-100', 'bg-orange-100', 'bg-rose-100'];
$coloresTexto = ['text-blue-600', 'text-green-600', 'text-purple-600', 'text-pink-600', 'text-indigo-600', 'text-teal-600', 'text-orange-600', 'text-rose-600'];


try {
    
    $zonasStmt = $pdo->prepare("SELECT id, nombre FROM auditoria_zonas WHERE operacion_id = ? ORDER BY nombre ASC");
    $zonasStmt->execute([getOperacionActiva()]);
    $zonas = $zonasStmt->fetchAll(PDO::FETCH_ASSOC);
    $zonasJS = $zonas;

    
    $modulos = $pdo->query("SELECT * FROM auditorias ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($modulos as $mod) {
        $id = $mod['id'];
        
        
        $media = $pdo->prepare("SELECT * FROM auditoria_media WHERE auditoria_id = ? ORDER BY created_at DESC");
        $media->execute([$id]);
        $mod['archivos'] = $media->fetchAll(PDO::FETCH_ASSOC);

        
        $chk = $pdo->prepare("SELECT id, titulo FROM auditoria_checklists WHERE auditoria_id = ? ORDER BY created_at ASC");
        $chk->execute([$id]);
        $mod['checklists'] = $chk->fetchAll(PDO::FETCH_ASSOC);

        $registros[] = $mod;
        $modulosJS[$id] = $mod; 
    }
} catch (Exception $e) {
    $errores[] = "Error al cargar los datos de auditoría: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - WARE PRO</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        ware: {
                            black: '#0F0F10',
                            dark: '#1a1a1a',
                            yellow: '#FFC107',
                            yellowDark: '#E0A800',
                            gray: '#f8f9fa'
                        }
                    }
                }
            }
        }
    </script>

    <style>
        
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        
        .modal-enter { opacity: 1; pointer-events: auto; }
        .modal-enter .modal-scale { transform: scale(1); }
        .modal-leave { opacity: 0; pointer-events: none; }
        .modal-leave .modal-scale { transform: scale(0.95); }

        
        #modalCriterios table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.875rem; }
        #modalCriterios th, #modalCriterios td { border: 1px solid #e5e7eb; padding: 0.75rem; text-align: left; }
        #modalCriterios th { background-color: #f9fafb; font-weight: bold; color: #111827; }
        #modalCriterios ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
        #modalCriterios ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; }
        #modalCriterios p { margin-bottom: 0.75rem; }
        #modalCriterios a { color: #3b82f6; text-decoration: underline; }
        #modalCriterios strong, #modalCriterios b { font-weight: 700; color: var(--ware-black); }
        
        
        .bg-dots {
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-gray-50 bg-dots text-gray-800 antialiased min-h-screen flex flex-col">

    <script>
        const modulosData = <?php echo json_encode($modulosJS, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const zonasData = <?php echo json_encode($zonasJS, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    </script>

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex-grow relative z-10">
        
        <div class="mb-10">
            <a href="../reportes/dashboard.php" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-ware-black transition-colors mb-6 bg-white/80 backdrop-blur-md px-4 py-2 rounded-full shadow-sm border border-gray-200 hover:shadow-md">
                <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
            </a>
            
            <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-ware-yellow/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10">
                    <h1 class="text-4xl font-black text-ware-black tracking-tight mb-2">Módulos de Auditoría</h1>
                    <p class="text-gray-500 font-medium">Selecciona un módulo para visualizar sus anexos y ejecutar checklists.</p>
                </div>
                <div class="bg-gray-50 border border-gray-100 px-6 py-4 rounded-[1.5rem] flex items-center gap-4 relative z-10 shadow-inner">
                    <div class="w-12 h-12 rounded-2xl bg-ware-yellow flex items-center justify-center text-ware-black shadow-md transform rotate-3">
                        <i class="fa-solid fa-layer-group text-xl"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Total Activos</span>
                        <span class="block text-2xl font-black text-ware-black"><?php echo count($registros); ?> Módulos</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($errores): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl mb-8 shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0"><i class="fa-solid fa-triangle-exclamation text-red-500"></i></div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-red-800">Hubo un problema</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <?php foreach ($errores as $e): ?>
                                    <li><?php echo htmlspecialchars($e); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$registros): ?>
            <div class="bg-white rounded-[2rem] p-16 text-center shadow-sm border border-gray-200 border-dashed border-2">
                <div class="w-24 h-24 mx-auto bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-6 shadow-inner">
                    <i class="fa-solid fa-folder-open text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-ware-black mb-2">No hay módulos disponibles</h3>
                <p class="text-gray-500 max-w-md mx-auto">Actualmente no existen módulos de auditoría creados. Comunícate con un administrador para que asigne las nuevas tareas.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach ($registros as $r): ?>
                    <?php 
                        $cantArchivos = count($r['archivos']);
                        $cantChecklists = count($r['checklists']);
                        
                        
                        $randIndex = array_rand($iconosAleatorios);
                        $randColor = array_rand($coloresFondo);
                        $iconoCard = $iconosAleatorios[$randIndex];
                        $bgCard = $coloresFondo[$randColor];
                        $textCard = $coloresTexto[$randColor];
                    ?>
                    <article class="relative bg-white rounded-[2rem] shadow-sm hover:shadow-2xl border border-gray-100 overflow-hidden group cursor-pointer h-[18rem] flex flex-col transition-all duration-500 hover:-translate-y-2" onclick="abrirModal(<?php echo $r['id']; ?>)">
                        
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-ware-yellow to-amber-500 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500 ease-out z-20"></div>
                        
                        <div class="absolute top-5 right-5 w-12 h-12 rounded-2xl <?php echo $bgCard; ?> flex items-center justify-center transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 z-20 shadow-sm">
                            <i class="fa-solid <?php echo $iconoCard; ?> text-xl <?php echo $textCard; ?>"></i>
                        </div>

                        <i class="fa-solid <?php echo $iconoCard; ?> absolute -bottom-6 -right-6 text-9xl text-gray-50 opacity-40 group-hover:opacity-100 group-hover:scale-110 transition-all duration-500 pointer-events-none z-0 transform -rotate-12"></i>

                        <div class="p-6 flex flex-col h-full relative z-10">
                            <h3 class="text-xl font-black text-ware-black mb-3 line-clamp-2 pr-12 group-hover:text-amber-600 transition-colors duration-300">
                                <?php echo htmlspecialchars($r['nombre']); ?>
                            </h3>
                            
                            <p class="text-gray-500 text-sm line-clamp-3 mb-4 leading-relaxed font-medium">
                                <?php echo htmlspecialchars(strip_tags($r['criterios'])); ?>
                            </p>
                            
                            <div class="w-full h-px bg-gray-100 my-auto"></div>
                            
                            <div class="mt-4 flex justify-between items-center text-[0.65rem] sm:text-xs font-bold uppercase tracking-wider">
                                <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl transition-colors <?php echo $cantArchivos > 0 ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-gray-50 text-gray-400 border border-transparent'; ?>">
                                    <i class="fa-solid fa-paperclip text-sm"></i> <?php echo $cantArchivos; ?> Anexos
                                </span>
                                <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl transition-colors <?php echo $cantChecklists > 0 ? 'bg-green-50 text-green-600 border border-green-100' : 'bg-gray-50 text-gray-400 border border-transparent'; ?>">
                                    <i class="fa-solid fa-list-check text-sm"></i> <?php echo $cantChecklists; ?> Listas
                                </span>
                            </div>
                        </div>

                        <div class="absolute inset-0 bg-ware-black/60 backdrop-blur-[2px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-30">
                            <span class="bg-ware-yellow text-ware-black font-black px-6 py-3 rounded-full shadow-2xl transform translate-y-8 group-hover:translate-y-0 transition-all duration-300 ease-out flex items-center gap-2">
                                <i class="fa-solid fa-eye"></i> Visualizar Módulo
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="modalVista" class="fixed inset-0 z-40 bg-ware-black/80 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300 modal-leave no-scrollbar overflow-y-auto">
        <div id="modalContent" class="bg-white w-full max-w-5xl rounded-[2rem] shadow-2xl relative flex flex-col max-h-[90vh] transition-transform duration-300 modal-scale overflow-hidden">
            
            <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 id="modalTitulo" class="text-2xl font-black text-ware-black truncate pr-4">Título del Módulo</h2>
                <button onclick="cerrarModal()" class="w-10 h-10 rounded-full bg-white border border-gray-200 text-gray-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors flex-shrink-0 shadow-sm">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8 overflow-y-auto no-scrollbar flex-grow">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    
                    <div class="lg:col-span-7 space-y-8">
                        <div>
                            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3"><i class="fa-solid fa-align-left mr-2"></i>Criterios / Descripción</h4>
                            <div id="modalCriterios" class="text-gray-700 text-base leading-relaxed bg-gray-50 p-6 rounded-3xl border border-gray-100 overflow-x-auto shadow-inner"></div>
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4"><i class="fa-solid fa-photo-film mr-2 text-ware-yellowDark"></i>Anexos Adjuntos</h4>
                            <div id="modalMedia" class="flex flex-wrap gap-4"></div>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="bg-ware-black rounded-[2rem] p-8 shadow-xl h-full border border-gray-800 relative overflow-hidden">
                            <div class="absolute -top-10 -right-10 w-48 h-48 bg-ware-yellow/10 rounded-full blur-3xl pointer-events-none"></div>
                            
                            <h4 class="text-white text-xl font-black mb-8 relative z-10 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-ware-yellow flex items-center justify-center text-ware-black shadow-inner">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                Tareas Asignadas
                            </h4>
                            
                            <div id="modalChecklists" class="space-y-4 relative z-10"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="modalZona" class="fixed inset-0 z-50 bg-ware-black/90 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300 opacity-0 pointer-events-none">
        <div class="bg-white w-full max-w-md rounded-[2rem] shadow-2xl relative p-8 transform scale-95 transition-transform duration-300" id="modalZonaContent">
            
            <button onclick="cerrarModalZona()" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-gray-50 text-gray-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="text-center mb-8 mt-2">
                <div class="w-20 h-20 mx-auto bg-blue-50 rounded-[1.5rem] flex items-center justify-center text-blue-500 mb-5 shadow-inner transform rotate-3">
                    <i class="fa-solid fa-map-location-dot text-3xl transform -rotate-3"></i>
                </div>
                <h3 class="text-2xl font-black text-ware-black">Selecciona la Zona</h3>
                <p class="text-gray-500 mt-2 text-sm">¿En qué área vas a realizar esta auditoría?</p>
            </div>

            <form id="formSeleccionZona" onsubmit="iniciarChecklist(event)">
                <input type="hidden" id="zona_modulo_id">
                <input type="hidden" id="zona_checklist_id">
                
                <div class="mb-6">
                    <select id="select_zona" class="w-full bg-gray-50 border-2 border-gray-200 text-gray-800 rounded-2xl p-4 outline-none focus:border-ware-yellow focus:ring-4 focus:ring-ware-yellow/20 font-bold transition-all" required>
                        <option value="" disabled selected>-- Elige una zona --</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-ware-yellow hover:bg-ware-yellowDark text-ware-black font-black py-4 px-6 rounded-2xl shadow-lg transition-transform hover:-translate-y-1 flex items-center justify-center gap-3 text-lg">
                    Continuar <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <div id="modalImagen" class="fixed inset-0 z-[60] bg-black/95 flex items-center justify-center p-4 transition-all duration-300 opacity-0 pointer-events-none backdrop-blur-sm">
        <button onclick="cerrarImagen()" class="absolute top-6 right-6 w-12 h-12 rounded-full bg-white/10 text-white hover:bg-red-500 flex items-center justify-center transition-colors">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
        <img id="imagenFullscreen" src="" alt="Visor" class="max-w-full max-h-[90vh] object-contain rounded-2xl shadow-2xl transform scale-95 transition-transform duration-300 border border-white/10">
    </div>

    <script>
        const modal = document.getElementById('modalVista');
        const modalImg = document.getElementById('modalImagen');
        const modalZona = document.getElementById('modalZona');
        const modalZonaContent = document.getElementById('modalZonaContent');
        const imgFull = document.getElementById('imagenFullscreen');

        
        function abrirModal(id) {
            const data = modulosData[id];
            if(!data) return;

            document.getElementById('modalTitulo').textContent = data.nombre;
            document.getElementById('modalCriterios').innerHTML = data.criterios || "Sin descripción proporcionada.";

            
            const mediaContainer = document.getElementById('modalMedia');
            mediaContainer.innerHTML = '';
            
            if (data.archivos && data.archivos.length > 0) {
                data.archivos.forEach(file => {
                    const el = document.createElement('div');
                    
                    if(file.tipo === 'imagen') {
                        el.className = 'w-24 h-24 rounded-[1.25rem] overflow-hidden cursor-pointer border-2 border-transparent hover:border-ware-yellow transition-all shadow-md relative group';
                        el.innerHTML = `
                            <img src="${file.ruta}" class="w-full h-full object-cover" alt="Img">
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-[1px]">
                                <i class="fa-solid fa-expand text-white text-2xl transform scale-75 group-hover:scale-100 transition-transform"></i>
                            </div>
                        `;
                        el.onclick = () => abrirImagen(file.ruta);
                    
                    } else if (file.tipo === 'link') {
                        el.className = 'w-24 h-24 rounded-[1.25rem] bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 flex flex-col items-center justify-center cursor-pointer hover:shadow-lg hover:-translate-y-1 transition-all text-blue-600 relative group';
                        el.title = file.nombre_archivo || 'Enlace Externo';
                        el.innerHTML = `
                            <i class="fa-solid fa-link text-3xl mb-2 group-hover:rotate-12 transition-transform"></i>
                            <span class="text-[10px] font-black px-2 truncate w-full text-center tracking-wider">ENLACE</span>
                        `;
                        el.onclick = () => window.open(file.ruta, '_blank');
                    
                    } else {
                        
                        el.className = 'w-24 h-24 rounded-[1.25rem] bg-gradient-to-br from-red-50 to-red-100 border border-red-200 flex flex-col items-center justify-center cursor-pointer hover:shadow-lg hover:-translate-y-1 transition-all text-red-600 relative group';
                        el.title = file.nombre_archivo || 'Documento PDF';
                        el.innerHTML = `
                            <i class="fa-solid fa-file-pdf text-3xl mb-2 group-hover:-rotate-12 transition-transform"></i>
                            <span class="text-[10px] font-black px-2 truncate w-full text-center tracking-wider">PDF</span>
                        `;
                        el.onclick = () => window.open(file.ruta, '_blank');
                    }
                    mediaContainer.appendChild(el);
                });
            } else {
                mediaContainer.innerHTML = '<p class="text-gray-400 text-sm italic py-2 font-medium">No hay archivos ni enlaces adjuntos en este módulo.</p>';
            }

            
            const chkContainer = document.getElementById('modalChecklists');
            chkContainer.innerHTML = '';

            if (data.checklists && data.checklists.length > 0) {
                data.checklists.forEach(chk => {
                    const btn = document.createElement('button');
                    btn.onclick = () => abrirModalZona(id, chk.id);
                    btn.className = 'w-full text-left block bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-[1.25rem] p-5 transition-all duration-300 group hover:shadow-lg';
                    btn.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div class="pr-4">
                                <h5 class="text-white font-black text-sm mb-1.5 group-hover:text-ware-yellow transition-colors leading-tight">${chk.titulo}</h5>
                                <span class="text-gray-400 text-[10px] uppercase font-bold tracking-wider"><i class="fa-solid fa-clipboard-list mr-1"></i>Requerido</span>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-ware-yellow text-ware-black flex items-center justify-center transform group-hover:translate-x-1 transition-transform shadow-md flex-shrink-0">
                                <i class="fa-solid fa-play text-sm ml-0.5"></i>
                            </div>
                        </div>
                    `;
                    chkContainer.appendChild(btn);
                });
            } else {
                chkContainer.innerHTML = `
                    <div class="text-center py-10 bg-gray-900 rounded-[1.25rem] border border-gray-800">
                        <div class="w-16 h-16 mx-auto bg-gray-800 rounded-full flex items-center justify-center mb-4">
                            <i class="fa-solid fa-clipboard-check text-gray-600 text-2xl"></i>
                        </div>
                        <p class="text-gray-400 text-sm font-medium px-4">No hay tareas configuradas para este módulo.</p>
                    </div>
                `;
            }

            modal.classList.remove('modal-leave');
            modal.classList.add('modal-enter');
            document.body.style.overflow = 'hidden'; 
        }

        function cerrarModal() {
            modal.classList.remove('modal-enter');
            modal.classList.add('modal-leave');
            document.body.style.overflow = ''; 
        }

        
        
        
        function abrirModalZona(modulo_id, checklist_id) {
            if (zonasData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No hay Zonas',
                    text: 'Debe existir al menos una zona creada en el sistema para iniciar. Pide a un administrador que la configure.',
                    confirmButtonColor: '#0F0F10',
                    customClass: { confirmButton: 'rounded-xl' }
                });
                return;
            }

            const select = document.getElementById('select_zona');
            select.innerHTML = '<option value="" disabled selected>-- Elige una zona --</option>';
            zonasData.forEach(z => {
                select.innerHTML += `<option value="${z.id}">${z.nombre}</option>`;
            });

            document.getElementById('zona_modulo_id').value = modulo_id;
            document.getElementById('zona_checklist_id').value = checklist_id;

            modalZona.classList.remove('opacity-0', 'pointer-events-none');
            modalZonaContent.classList.remove('scale-95');
            modalZonaContent.classList.add('scale-100');
        }

        function cerrarModalZona() {
            modalZona.classList.add('opacity-0', 'pointer-events-none');
            modalZonaContent.classList.remove('scale-100');
            modalZonaContent.classList.add('scale-95');
        }

        function iniciarChecklist(e) {
            e.preventDefault();
            const mod_id = document.getElementById('zona_modulo_id').value;
            const chk_id = document.getElementById('zona_checklist_id').value;
            const zona_id = document.getElementById('select_zona').value;

            if(mod_id && chk_id && zona_id) {
                window.location.href = `realizar_checklist.php?modulo_id=${mod_id}&checklist_id=${chk_id}&zona_id=${zona_id}`;
            }
        }

        
        
        
        function abrirImagen(ruta) {
            imgFull.src = ruta;
            modalImg.classList.remove('opacity-0', 'pointer-events-none');
            imgFull.classList.remove('scale-95');
            imgFull.classList.add('scale-100');
        }

        function cerrarImagen() {
            modalImg.classList.add('opacity-0', 'pointer-events-none');
            imgFull.classList.remove('scale-100');
            imgFull.classList.add('scale-95');
            setTimeout(() => { imgFull.src = ''; }, 300);
        }

        
        
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) cerrarModal();
        });
        
        modalImg.addEventListener('click', (e) => {
            if (e.target === modalImg) cerrarImagen();
        });

        modalZona.addEventListener('click', (e) => {
            if (e.target === modalZona) cerrarModalZona();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (!modalImg.classList.contains('opacity-0')) {
                    cerrarImagen();
                } else if (!modalZona.classList.contains('opacity-0')) {
                    cerrarModalZona();
                } else if (!modal.classList.contains('modal-leave')) {
                    cerrarModal();
                }
            }
        });
    </script>
</body>
</html>