<?php
include '../../core/config.php';
include '../../core/header.php';

verificarLogin();

$current_user = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '';

try {
    $stmt = $pdo->prepare("SELECT id, nombre, cargo FROM usuarios WHERE activo = 1 AND operacion_id = ? ORDER BY nombre ASC");
    $stmt->execute([getOperacionActiva()]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM insumos WHERE operacion_id = ? ORDER BY fecha DESC, id DESC");
    $stmt->execute([getOperacionActiva()]);
    $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $insumos = [];
    $error_message = "Error al cargar insumos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Insumos - WARE PRO</title>
    
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            900: '#111827',
                            950: '#030712',
                        },
                        accent: {
                            DEFAULT: '#EAB308', 
                            hover: '#CA8A04', 
                        }
                    }
                }
            }
        }
    </script>

    <style>
        
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        
        .ts-control {
            border-radius: 0.5rem !important;
            border-color: #D1D5DB !important;
            padding: 0.5rem 0.75rem !important;
        }
        .ts-control:focus {
            border-color: #EAB308 !important;
            box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.1) !important;
        }
        .ts-dropdown {
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        }
        .ts-dropdown .active {
            background-color: #FEF9C3 !important;
            color: #854D0E !important;
        }
        
        
        @keyframes pulse-red {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        }
        .alert-pulse {
            animation: pulse-red 2s infinite;
        }
        
        
        .modal-enter {
            animation: modalSlideIn 0.3s ease-out;
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900 font-sans antialiased">
    <div class="min-h-screen p-4 md:p-6 lg:p-8">
        <div class="max-w-[1600px] mx-auto space-y-6">
            
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-brand-900 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-boxes text-2xl text-accent"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-brand-900">Gestión de Insumos</h1>
                        <p class="text-gray-500 mt-1">Control y seguimiento de inventario de insumos</p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-6 flex flex-col md:flex-row gap-4 justify-between items-center">
                <button onclick="abrirModal()" class="w-full md:w-auto bg-brand-900 hover:bg-brand-950 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                    <i class="fas fa-plus"></i>
                    Agregar Nuevo Insumo
                </button>

                <div class="relative w-full md:w-96">
                    <input type="text" id="searchInput" placeholder="Buscar por supervisor o fecha..." 
                           onkeyup="filtrarInsumos()"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent/20 transition-all outline-none">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            
            <?php if (empty($insumos)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                    <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-boxes text-3xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-brand-900 mb-2">No hay insumos registrados</h3>
                    <p class="text-gray-500">Comienza agregando el primer registro de insumos</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-brand-900 to-gray-800 text-white px-6 py-4 flex items-center justify-between border-b-4 border-accent">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-clipboard-list text-accent"></i>
                            <h3 class="font-semibold text-lg">Registro de Insumos</h3>
                        </div>
                        <span class="bg-white/10 px-3 py-1 rounded-full text-sm font-medium backdrop-blur-sm">
                            <?php echo count($insumos); ?> registros
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="insumosTable">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Supervisor</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">Vini</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">Termo</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">Cartón</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">ISO Total</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">ISO Llenos</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">ISO Vacíos</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">ISO Malo</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Estibas</th>
                                    <th class="px-4 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($insumos as $insumo): ?>
                                    <tr data-search="<?php echo strtolower($insumo['supervisor'] . ' ' . $insumo['fecha']); ?>" 
                                        class="hover:bg-gray-50 transition-colors group">
                                        
                                        <td class="px-4 py-4 whitespace-nowrap font-semibold text-brand-900">
                                            <?php echo date('d/m/Y', strtotime($insumo['fecha'])); ?>
                                        </td>
                                        
                                        <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-700">
                                            <?php echo htmlspecialchars($insumo['supervisor']); ?>
                                        </td>
                                        
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-sm font-bold min-w-[40px] justify-center">
                                                <?php echo $insumo['vinipel_rollos']; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-4 text-center">
                                            <?php 
                                            $termoAlert = intval($insumo['termoencogido_rollos']) <= 200;
                                            $termoClass = $termoAlert ? 'bg-red-100 text-red-700 border-2 border-red-400 alert-pulse' : 'bg-green-100 text-green-700';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold min-w-[40px] justify-center <?php echo $termoClass; ?>">
                                                <?php echo $insumo['termoencogido_rollos']; ?>
                                                <?php if ($termoAlert): ?>⚠️<?php endif; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-sm font-bold min-w-[40px] justify-center">
                                                <?php echo $insumo['carton_laminas']; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-sm font-bold min-w-[40px] justify-center">
                                                <?php echo $insumo['isotanques']; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-sm font-bold min-w-[40px] justify-center">
                                                <?php echo $insumo['iso_llenos']; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-4 text-center">
                                            <?php 
                                            $isoVacioAlert = intval($insumo['iso_bueno']) < 5;
                                            $isoClass = $isoVacioAlert ? 'bg-red-100 text-red-700 border-2 border-red-400 alert-pulse' : 'bg-blue-100 text-blue-700';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold min-w-[40px] justify-center <?php echo $isoClass; ?>">
                                                <?php echo $insumo['iso_bueno']; ?>
                                                <?php if ($isoVacioAlert): ?>⚠️<?php endif; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-sm font-bold min-w-[40px] justify-center">
                                                <?php echo $insumo['iso_malo']; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-4">
                                            <div class="flex flex-wrap gap-1 max-w-[200px]">
                                                <?php if (!empty($insumo['estibas_tipo_a'])): ?>
                                                    <?php 
                                                    $estibaAAlert = intval($insumo['estibas_tipo_a']) > 1100;
                                                    $estibaAClass = $estibaAAlert ? 'bg-red-100 text-red-700 border-red-400 alert-pulse' : 'bg-orange-100 text-orange-700';
                                                    ?>
                                                    <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $estibaAClass; ?>" title="Tipo A">
                                                        A:<?php echo $insumo['estibas_tipo_a']; ?><?php if ($estibaAAlert): ?>⚠️<?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($insumo['estibas_tipo_b'])): ?>
                                                    <?php 
                                                    $estibaBAlert = intval($insumo['estibas_tipo_b']) > 600;
                                                    $estibaBClass = $estibaBAlert ? 'bg-red-100 text-red-700 border-red-400 alert-pulse' : 'bg-orange-100 text-orange-700';
                                                    ?>
                                                    <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $estibaBClass; ?>" title="Tipo B">
                                                        B:<?php echo $insumo['estibas_tipo_b']; ?><?php if ($estibaBAlert): ?>⚠️<?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($insumo['estibas_tipo_c'])): ?>
                                                    <?php 
                                                    $estibaCAlert = intval($insumo['estibas_tipo_c']) > 15;
                                                    $estibaCClass = $estibaCAlert ? 'bg-red-100 text-red-700 border-red-400 alert-pulse' : 'bg-orange-100 text-orange-700';
                                                    ?>
                                                    <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $estibaCClass; ?>" title="Tipo C">
                                                        C:<?php echo $insumo['estibas_tipo_c']; ?><?php if ($estibaCAlert): ?>⚠️<?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($insumo['estibas_ara'])): ?>
                                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-orange-100 text-orange-700" title="ARA">
                                                        ARA:<?php echo $insumo['estibas_ara']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($insumo['estibas_d1'])): ?>
                                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-orange-100 text-orange-700" title="D1">
                                                        D1:<?php echo $insumo['estibas_d1']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-4 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="verInsumo(<?php echo $insumo['id']; ?>)" 
                                                        class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-200" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editarInsumo(<?php echo $insumo['id']; ?>)" 
                                                        class="p-2 rounded-lg bg-yellow-50 text-yellow-600 hover:bg-yellow-500 hover:text-white transition-all duration-200" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div id="insumoModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="cerrarModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full modal-enter">
                <div class="bg-gradient-to-r from-brand-900 to-gray-800 px-6 py-4 border-b-4 border-accent flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white flex items-center gap-3" id="modalTitle">
                        <i class="fas fa-boxes text-accent"></i>
                        <span>Agregar Nuevo Insumo</span>
                    </h3>
                    <button onclick="cerrarModal()" class="text-white hover:text-accent transition-colors text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="insumoForm" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                            <h4 class="font-semibold text-brand-900 mb-4 flex items-center gap-2 pb-2 border-b-2 border-accent">
                                <i class="fas fa-info-circle text-accent"></i>
                                Información General
                            </h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                                    <input type="date" id="fecha" name="fecha" required
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Supervisor</label>
                                    <select id="supervisor" name="supervisor" required disabled
                                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 bg-gray-100 text-gray-600 cursor-not-allowed">
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <option value="<?php echo htmlspecialchars($usuario['nombre']); ?>" 
                                                    <?php echo ($usuario['nombre'] === $current_user) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($usuario['nombre']); ?> (<?php echo htmlspecialchars($usuario['cargo']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="supervisor" id="supervisor_hidden" value="<?php echo htmlspecialchars($current_user); ?>">
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-lock mr-1"></i>Usuario actual (no editable)
                                    </p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                            <h4 class="font-semibold text-brand-900 mb-4 flex items-center gap-2 pb-2 border-b-2 border-accent">
                                <i class="fas fa-industry text-accent"></i>
                                Materiales
                            </h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vinipel (Rollos)</label>
                                    <input type="number" id="vinipel_rollos" name="vinipel_rollos" min="0" value="0"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Termoencogido (Rollos)</label>
                                    <input type="number" id="termoencogido_rollos" name="termoencogido_rollos" min="0" value="0"
                                           onchange="validarCampos()" onkeyup="validarCampos()"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                    <div id="termoAlert" class="hidden mt-2 text-xs text-red-600 flex items-center gap-1 font-medium">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Stock crítico: 200 rollos o menos
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cartón (Láminas)</label>
                                    <input type="number" id="carton_laminas" name="carton_laminas" min="0" value="0"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                </div>
                            </div>
                        </div>

                        
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                            <h4 class="font-semibold text-brand-900 mb-4 flex items-center gap-2 pb-2 border-b-2 border-accent">
                                <i class="fas fa-oil-can text-accent"></i>
                                Isotanques
                            </h4>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                                    <input type="number" id="isotanques" name="isotanques" min="0" value="0"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Llenos</label>
                                    <input type="number" id="iso_llenos" name="iso_llenos" min="0" value="0"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vacíos</label>
                                    <input type="number" id="iso_bueno" name="iso_bueno" min="0" value="0"
                                           onchange="validarCampos()" onkeyup="validarCampos()"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                    <div id="isoVacioAlert" class="hidden mt-2 text-xs text-red-600 flex items-center gap-1 font-medium">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Stock crítico: Menos de 5 unidades
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Malos</label>
                                    <input type="number" id="iso_malo" name="iso_malo" min="0" value="0"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                </div>
                            </div>
                        </div>

                        
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                            <h4 class="font-semibold text-brand-900 mb-4 flex items-center gap-2 pb-2 border-b-2 border-accent">
                                <i class="fas fa-pallet text-accent"></i>
                                Estibas
                            </h4>
                            
                            <div class="space-y-3">
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo A</label>
                                    <input type="text" id="estibas_tipo_a" name="estibas_tipo_a" placeholder="Cantidad"
                                           onchange="validarCampos()" onkeyup="validarCampos()"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                    <div id="estibaAAlert" class="hidden mt-1 text-xs text-red-600 flex items-center gap-1 font-medium">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Exceso: Más de 1100 unidades
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo B</label>
                                    <input type="text" id="estibas_tipo_b" name="estibas_tipo_b" placeholder="Cantidad"
                                           onchange="validarCampos()" onkeyup="validarCampos()"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                    <div id="estibaBAlert" class="hidden mt-1 text-xs text-red-600 flex items-center gap-1 font-medium">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Exceso: Más de 600 unidades
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo C</label>
                                    <input type="text" id="estibas_tipo_c" name="estibas_tipo_c" placeholder="Cantidad"
                                           onchange="validarCampos()" onkeyup="validarCampos()"
                                           class="w-full rounded-lg border-gray-300 border px-4 py-2 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                    <div id="estibaCAlert" class="hidden mt-1 text-xs text-red-600 flex items-center gap-1 font-medium">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Exceso: Más de 15 unidades
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ARA</label>
                                        <input type="text" id="estibas_ara" name="estibas_ara" placeholder="Cantidad"
                                               class="w-full rounded-lg border-gray-300 border px-4 py-2 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">D1</label>
                                        <input type="text" id="estibas_d1" name="estibas_d1" placeholder="Cantidad"
                                               class="w-full rounded-lg border-gray-300 border px-4 py-2 focus:border-accent focus:ring-2 focus:ring-accent/20 outline-none transition-all">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end gap-3">
                        <button type="button" onclick="cerrarModal()" 
                                class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-6 py-2.5 rounded-lg bg-brand-900 text-white font-medium hover:bg-brand-950 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            <span id="btnSubmitText">Guardar Insumo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div id="verModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="cerrarVerModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full modal-enter">
                <div class="bg-gradient-to-r from-brand-900 to-gray-800 px-6 py-4 border-b-4 border-accent flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-eye text-accent"></i>
                        Detalles del Insumo
                    </h3>
                    <button onclick="cerrarVerModal()" class="text-white hover:text-accent transition-colors text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6" id="verModalBody">
                    
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button type="button" onclick="cerrarVerModal()" 
                            class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-white transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let modoEdicion = false;
        let insumoEditando = null;
        let supervisorSelect;

        
        document.addEventListener('DOMContentLoaded', function() {
            
            
            
            
            
            
        });

        function validarCampos() {
            
            const termoInput = document.getElementById('termoencogido_rollos');
            const termoAlert = document.getElementById('termoAlert');
            const termoValue = parseInt(termoInput.value) || 0;
            
            if (termoValue <= 200) {
                termoInput.classList.add('border-red-500', 'bg-red-50', 'alert-pulse');
                termoInput.classList.remove('border-gray-300');
                termoAlert.classList.remove('hidden');
            } else {
                termoInput.classList.remove('border-red-500', 'bg-red-50', 'alert-pulse');
                termoInput.classList.add('border-gray-300');
                termoAlert.classList.add('hidden');
            }

            
            const isoVacioInput = document.getElementById('iso_bueno');
            const isoVacioAlert = document.getElementById('isoVacioAlert');
            const isoVacioValue = parseInt(isoVacioInput.value) || 0;
            
            if (isoVacioValue < 5) {
                isoVacioInput.classList.add('border-red-500', 'bg-red-50', 'alert-pulse');
                isoVacioInput.classList.remove('border-gray-300');
                isoVacioAlert.classList.remove('hidden');
            } else {
                isoVacioInput.classList.remove('border-red-500', 'bg-red-50', 'alert-pulse');
                isoVacioInput.classList.add('border-gray-300');
                isoVacioAlert.classList.add('hidden');
            }

            
            const validarEstiba = (inputId, alertId, limite) => {
                const input = document.getElementById(inputId);
                const alert = document.getElementById(alertId);
                const value = parseInt(input.value) || 0;
                
                if (value > limite) {
                    input.classList.add('border-red-500', 'bg-red-50', 'alert-pulse');
                    input.classList.remove('border-gray-300');
                    alert.classList.remove('hidden');
                } else {
                    input.classList.remove('border-red-500', 'bg-red-50', 'alert-pulse');
                    input.classList.add('border-gray-300');
                    alert.classList.add('hidden');
                }
            };

            validarEstiba('estibas_tipo_a', 'estibaAAlert', 1100);
            validarEstiba('estibas_tipo_b', 'estibaBAlert', 600);
            validarEstiba('estibas_tipo_c', 'estibaCAlert', 15);
        }

        function abrirModal() {
            modoEdicion = false;
            insumoEditando = null;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-boxes text-accent"></i><span>Agregar Nuevo Insumo</span>';
            document.getElementById('btnSubmitText').textContent = 'Guardar Insumo';
            document.getElementById('insumoForm').reset();

            
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('fecha').value = today;

            
            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('border-red-500', 'bg-red-50', 'alert-pulse');
                input.classList.add('border-gray-300');
            });
            document.querySelectorAll('[id$="Alert"]').forEach(alert => {
                alert.classList.add('hidden');
            });

            document.getElementById('insumoModal').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('insumoModal').classList.add('hidden');
        }

        function cerrarVerModal() {
            document.getElementById('verModal').classList.add('hidden');
        }

        function editarInsumo(id) {
            modoEdicion = true;
            insumoEditando = id;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-boxes text-accent"></i><span>Editar Insumo</span>';
            document.getElementById('btnSubmitText').textContent = 'Actualizar Insumo';

            Swal.fire({
                title: 'Cargando...',
                text: 'Obteniendo datos del insumo',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('../../api/insumos/get_insumos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=obtener&id=' + id
            })
            .then(handleResponse)
            .then(data => {
                Swal.close();
                if (data && !data.error) {
                    document.getElementById('fecha').value = data.fecha;
                    
                    document.getElementById('vinipel_rollos').value = data.vinipel_rollos;
                    document.getElementById('termoencogido_rollos').value = data.termoencogido_rollos;
                    document.getElementById('carton_laminas').value = data.carton_laminas;
                    document.getElementById('isotanques').value = data.isotanques;
                    document.getElementById('iso_llenos').value = data.iso_llenos;
                    document.getElementById('iso_bueno').value = data.iso_bueno;
                    document.getElementById('iso_malo').value = data.iso_malo;
                    document.getElementById('estibas_tipo_a').value = data.estibas_tipo_a || '';
                    document.getElementById('estibas_tipo_b').value = data.estibas_tipo_b || '';
                    document.getElementById('estibas_tipo_c').value = data.estibas_tipo_c || '';
                    document.getElementById('estibas_ara').value = data.estibas_ara || '';
                    document.getElementById('estibas_d1').value = data.estibas_d1 || '';

                    setTimeout(() => validarCampos(), 100);
                    document.getElementById('insumoModal').classList.remove('hidden');
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.error || 'Error al cargar los datos',
                        icon: 'error',
                        confirmButtonColor: '#111827'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error de conexión',
                    text: 'No se pudieron cargar los datos del insumo',
                    icon: 'error',
                    confirmButtonColor: '#111827'
                });
            });
        }

        function verInsumo(id) {
            Swal.fire({
                title: 'Cargando...',
                text: 'Obteniendo detalles',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('../../api/insumos/get_insumos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=obtener&id=' + id
            })
            .then(handleResponse)
            .then(data => {
                Swal.close();
                if (data && !data.error) {
                    const fecha = new Date(data.fecha).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
                    
                    const getAlertClass = (condition) => condition ? 'bg-red-50 border-red-400 text-red-700 alert-pulse' : 'bg-gray-50 border-gray-200';
                    const getValueClass = (condition) => condition ? 'text-red-700' : 'text-brand-900';

                    const termoAlert = parseInt(data.termoencogido_rollos) <= 200;
                    const isoVacioAlert = parseInt(data.iso_bueno) < 5;
                    const estibaAAlert = parseInt(data.estibas_tipo_a) > 1100;
                    const estibaBAlert = parseInt(data.estibas_tipo_b) > 600;
                    const estibaCAlert = parseInt(data.estibas_tipo_c) > 15;

                    document.getElementById('verModalBody').innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                                <h4 class="font-semibold text-brand-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-info-circle text-accent"></i>
                                    Información General
                                </h4>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between border-b border-gray-200 pb-2">
                                        <span class="text-gray-600">Fecha:</span>
                                        <span class="font-semibold">${fecha}</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-200 pb-2">
                                        <span class="text-gray-600">Supervisor:</span>
                                        <span class="font-semibold">${data.supervisor}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                                <h4 class="font-semibold text-brand-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-industry text-accent"></i>
                                    Materiales
                                </h4>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
                                        <div class="text-2xl font-bold text-brand-900">${data.vinipel_rollos}</div>
                                        <div class="text-xs text-gray-500 mt-1">Vinipel</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border-2 ${termoAlert ? 'border-red-400 bg-red-50 alert-pulse' : 'border-gray-200'} text-center">
                                        <div class="text-2xl font-bold ${termoAlert ? 'text-red-700' : 'text-brand-900'}">${data.termoencogido_rollos}</div>
                                        <div class="text-xs ${termoAlert ? 'text-red-600 font-semibold' : 'text-gray-500'} mt-1">Termo ${termoAlert ? '⚠️' : ''}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
                                        <div class="text-2xl font-bold text-brand-900">${data.carton_laminas}</div>
                                        <div class="text-xs text-gray-500 mt-1">Cartón</div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                                <h4 class="font-semibold text-brand-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-oil-can text-accent"></i>
                                    Isotanques
                                </h4>
                                <div class="grid grid-cols-4 gap-3">
                                    <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
                                        <div class="text-xl font-bold text-brand-900">${data.isotanques}</div>
                                        <div class="text-xs text-gray-500 mt-1">Total</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
                                        <div class="text-xl font-bold text-brand-900">${data.iso_llenos}</div>
                                        <div class="text-xs text-gray-500 mt-1">Llenos</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border-2 ${isoVacioAlert ? 'border-red-400 bg-red-50 alert-pulse' : 'border-gray-200'} text-center">
                                        <div class="text-xl font-bold ${isoVacioAlert ? 'text-red-700' : 'text-brand-900'}">${data.iso_bueno}</div>
                                        <div class="text-xs ${isoVacioAlert ? 'text-red-600 font-semibold' : 'text-gray-500'} mt-1">Vacíos ${isoVacioAlert ? '⚠️' : ''}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
                                        <div class="text-xl font-bold text-brand-900">${data.iso_malo}</div>
                                        <div class="text-xs text-gray-500 mt-1">Malos</div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                                <h4 class="font-semibold text-brand-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-pallet text-accent"></i>
                                    Estibas
                                </h4>
                                <div class="grid grid-cols-5 gap-2">
                                    <div class="bg-white p-3 rounded-lg border-2 ${estibaAAlert ? 'border-red-400 bg-red-50 alert-pulse' : 'border-gray-200'} text-center">
                                        <div class="text-lg font-bold ${estibaAAlert ? 'text-red-700' : 'text-orange-600'}">${data.estibas_tipo_a || 'N/A'}</div>
                                        <div class="text-xs ${estibaAAlert ? 'text-red-600' : 'text-gray-500'} mt-1">A ${estibaAAlert ? '⚠️' : ''}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border-2 ${estibaBAlert ? 'border-red-400 bg-red-50 alert-pulse' : 'border-gray-200'} text-center">
                                        <div class="text-lg font-bold ${estibaBAlert ? 'text-red-700' : 'text-orange-600'}">${data.estibas_tipo_b || 'N/A'}</div>
                                        <div class="text-xs ${estibaBAlert ? 'text-red-600' : 'text-gray-500'} mt-1">B ${estibaBAlert ? '⚠️' : ''}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border-2 ${estibaCAlert ? 'border-red-400 bg-red-50 alert-pulse' : 'border-gray-200'} text-center">
                                        <div class="text-lg font-bold ${estibaCAlert ? 'text-red-700' : 'text-orange-600'}">${data.estibas_tipo_c || 'N/A'}</div>
                                        <div class="text-xs ${estibaCAlert ? 'text-red-600' : 'text-gray-500'} mt-1">C ${estibaCAlert ? '⚠️' : ''}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
                                        <div class="text-lg font-bold text-orange-600">${data.estibas_ara || 'N/A'}</div>
                                        <div class="text-xs text-gray-500 mt-1">ARA</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
                                        <div class="text-lg font-bold text-orange-600">${data.estibas_d1 || 'N/A'}</div>
                                        <div class="text-xs text-gray-500 mt-1">D1</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('verModal').classList.remove('hidden');
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudieron cargar los detalles',
                    icon: 'error',
                    confirmButtonColor: '#111827'
                });
            });
        }

        function filtrarInsumos() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#insumosTable tbody tr');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                row.style.display = searchData && searchData.includes(searchTerm) ? '' : 'none';
            });

            const visibleRows = document.querySelectorAll('#insumosTable tbody tr:not([style*="display: none"])').length;
            const countElement = document.querySelector('.records-count');
            if (countElement) {
                countElement.textContent = `${visibleRows} registro${visibleRows !== 1 ? 's' : ''}`;
            }
        }

        function handleResponse(response) {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta del servidor:', text);
                    throw new Error('Respuesta inválida del servidor');
                }
            });
        }

        document.getElementById('insumoForm').addEventListener('submit', function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'Procesando...',
                text: 'Guardando información',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData(this);
            const action = modoEdicion ? 'editar' : 'agregar';
            formData.append('action', action);
            if (modoEdicion) formData.append('id', insumoEditando);

            fetch('../../api/insumos/get_insumos.php', {
                method: 'POST',
                body: formData
            })
            .then(handleResponse)
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#111827',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        cerrarModal();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || data.error,
                        icon: 'error',
                        confirmButtonColor: '#111827'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo procesar la solicitud',
                    icon: 'error',
                    confirmButtonColor: '#111827'
                });
            });
        });

        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
                cerrarVerModal();
            }
        });

        <?php if (isset($error_message)): ?>
            Swal.fire({
                title: 'Error',
                text: '<?php echo $error_message; ?>',
                icon: 'error',
                confirmButtonColor: '#111827'
            });
        <?php endif; ?>
    </script>
</body>
</html>