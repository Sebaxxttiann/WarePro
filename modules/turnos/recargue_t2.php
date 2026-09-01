<?php
include '../../core/config.php';
verificarLogin();
include '../../core/header.php';
$user_cargo = $_SESSION['cargo'] ?? 'operador';
$user_nombre = $_SESSION['nombre'] ?? 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recargue T2 - WARE PRO</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: {
                        brand: { 400: '#FFD700', 500: '#FFA500' },
                        dark: '#1a1a1a'
                    }
                }
            }
        }
    </script>

<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
    
    table.dataTable thead th {
        background: #f8f9fa !important;
        color: #374151 !important;
        font-weight: 600 !important;
        border-bottom: 2px solid #FFD700 !important;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    table.dataTable tbody td {
        font-size: 0.85rem;
        vertical-align: middle !important;
        border-bottom: 1px solid #f3f4f6 !important;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }
    .modal-content-scroll { max-height: 85vh; overflow-y: auto; }

    .form-control.error {
        border-color: #ef4444;
        animation: shake 0.3s;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    .novedad-field { display: none; }
    .novedad-field.show { display: block; }
    #placa { text-transform: uppercase; font-weight: 600; }
    .swal2-container { z-index: 99999 !important; }
    
    
    .apexcharts-tooltip {
        background: #1a1a1a !important;
        border: 1px solid #333 !important;
        color: #fff !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    }
    .apexcharts-tooltip-title {
        background: #2d2d2d !important;
        border-bottom: 1px solid #444 !important;
        font-weight: 600 !important;
    }

g.apexcharts-datalabel rect.apexcharts-datalabel-bg {
    fill: #dcfce7 !important;  
    stroke: #22c55e !important; 
    fill-opacity: 1 !important;
}


g.apexcharts-datalabel text {
    fill: #FFFFFF !important; 
    font-weight: 800 !important;
}
</style></head>

<body class="text-gray-800 antialiased min-h-screen pb-10">
    <div class="max-w-[1600px] mx-auto p-4 md:p-8">
        
        
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm mb-6 border-l-[5px] border-brand-400 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-1 flex items-center gap-3">
                    <i class="fas fa-truck-loading text-brand-400 text-4xl"></i>
                    Recargue T2
                </h1>
                <p class="text-gray-500 text-sm">Sistema de gestión y control de recargues T2</p>
            </div>

            
            <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg flex flex-wrap sm:flex-nowrap items-center gap-4 shadow-inner">
                <div class="flex flex-col">
                    <label for="avgDateFilter" class="text-xs font-bold text-gray-500 uppercase mb-1">
                        <i class="fas fa-calendar-day text-brand-500"></i> Promedio Día
                    </label>
                    <input type="date" id="avgDateFilter" class="px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-brand-400 outline-none transition-all cursor-pointer">
                </div>
                <div class="hidden sm:block h-10 w-px bg-gray-300"></div>
                <div class="flex flex-col items-center justify-center min-w-[100px]">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Tiempo Medio</span>
                    <span id="avgTimeDisplay" class="text-xl font-bold text-blue-600">00:00:00</span>
                </div>
                <div class="hidden sm:block h-10 w-px bg-gray-300"></div>
                <button onclick="openPerformanceModal()" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-4 py-2 rounded-lg shadow font-semibold text-sm transition-all transform hover:scale-105 flex items-center gap-2 whitespace-nowrap">
                    <i class="fas fa-chart-line"></i> Ver Performance
                </button>
            </div>
        </div>

        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="bg-dark text-white p-5 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-xl font-semibold flex items-center gap-2">
                    <i class="fas fa-list-alt text-brand-400"></i>
                    Lista de Recargues T2
                </h2>
                <button class="bg-brand-400 hover:bg-brand-500 text-dark font-semibold py-2 px-5 rounded-lg flex items-center gap-2 transition-all transform hover:-translate-y-0.5 shadow-md" onclick="openModal('create')">
                    <i class="fas fa-plus-circle"></i>
                    Nuevo Recargue
                </button>
            </div>

            <div class="p-6 overflow-x-auto">
                <table id="recargueTable" class="w-full text-left border-collapse display">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Verificador</th>
                            <th>Turno</th>
                            <th>Placa</th>
                            <th>Inicio</th>
                            <th>Final</th>
                            <th>Tiempo</th>
                            <th>OPM1</th>
                            <th>Nov.</th>
                            <th>Estatus</th>
                            <th>Canal</th>
                            <th>Conteo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div id="recargueModal" class="modal">
        <div class="bg-white mx-auto mt-10 md:mt-16 p-0 rounded-xl w-[95%] max-w-4xl shadow-2xl relative z-[10000] modal-content-scroll flex flex-col">
            <div class="bg-dark text-white p-6 rounded-t-xl flex justify-between items-center sticky top-0 z-10">
                <h3 class="text-xl font-semibold flex items-center gap-2" id="modalTitle">
                    <i class="fas fa-plus-circle text-brand-400"></i>
                    Nuevo Recargue T2
                </h3>
                <button class="bg-white/10 hover:bg-brand-400/20 border border-white/30 text-white w-10 h-10 rounded-lg flex items-center justify-center transition-all hover:rotate-90 hover:text-brand-400" onclick="closeModal()">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <div class="p-6 md:p-8 flex-grow">
                <form id="recargueForm">
                    <input type="hidden" id="recordId" name="id">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        
                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="fecha">
                                <i class="fas fa-calendar-alt text-brand-400"></i> Fecha del Recargue <span class="text-red-500">*</span>
                            </label>
                            <input type="date" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100" id="fecha" name="fecha" required>
                        </div>

                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="verificador">
                                <i class="fas fa-user-check text-brand-400"></i> Verificador (Auto)
                            </label>
                            <input type="text" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 outline-none" id="verificador" name="verificador" value="<?php echo htmlspecialchars($user_nombre); ?>" readonly>
                        </div>

                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="turno">
                                <i class="fas fa-clock text-brand-400"></i> Turno (Auto)
                            </label>
                            <input type="text" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 outline-none font-bold text-center" id="turno" name="turno" readonly placeholder="Cálculo auto">
                        </div>

                         <div class="space-y-1"> <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="hora_entrada_bahia"> <i class="fas fa-sign-in-alt text-brand-400"></i> Inicio de Cargue <span class="text-red-500">*</span> </label> <input type="time" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100" id="hora_entrada_bahia" name="hora_entrada_bahia" step="1" required> 
                        </div>
                        
                        <div class="space-y-1"> <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="hora_salida_bahia"> <i class="fas fa-sign-out-alt text-brand-400"></i> Fin de Cargue <span class="text-red-500">*</span> </label> <input type="time" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100" id="hora_salida_bahia" name="hora_salida_bahia" step="1" required> </div>
                        
                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="tiempo">
                                <i class="fas fa-stopwatch text-brand-400"></i> Tiempo Total
                            </label>
                            <input type="text" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-blue-600 font-bold text-center outline-none" id="tiempo" name="tiempo" readonly placeholder="00:00:00">
                        </div>

                        
                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="placa">
                                <i class="fas fa-car text-brand-400"></i> Placa Vehículo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100 font-mono text-lg" id="placa" name="placa" required placeholder="ABC123" maxlength="10">
                        </div>

                        
                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="opm1">
                                <i class="fas fa-clipboard-list text-brand-400"></i> OPM1 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100" id="opm1" name="opm1" required placeholder="Código OPM1">
                        </div>

                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700"  for="canal">
                                <i class="fas fa-route text-brand-400"></i> Canal <span class="text-red-500">*</span>
                            </label>
                            <input type="text" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-blue-600 font-bold text-center outline-none" id="canal" name="canal" required placeholder="Canal" value="Tradicional">
                        </div>

                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="conteo_vehiculo">
                                <i class="fas fa-calculator text-brand-400"></i> Conteo (Opcional) 
                            </label>
                            <input type="number" class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100" id="conteo_vehiculo" name="conteo_vehiculo" required min="0" value="0">
                        </div>

                        <div class="space-y-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="estatus">
                                <i class="fas fa-info-circle text-brand-400"></i> Estatus <span class="text-red-500">*</span>
                            </label>
                            <select class="form-control w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100" id="estatus" name="estatus" required>
                                <option value="Completado">Completado</option>
                                <option value="En Proceso">En Proceso</option>
                                <option value="Pendiente">Pendiente</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>

                        
                        <div class="col-span-1 md:col-span-2 lg:col-span-3 bg-gray-50 p-4 rounded-xl border border-gray-200 mt-2">
                            <div class="space-y-1 mb-3">
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700" for="novedades_salidas_bahia">
                                    <i class="fas fa-exclamation-triangle text-brand-400"></i> ¿Hay Novedades? <span class="text-red-500">*</span>
                                </label>
                                <select class="form-control w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100" id="novedades_salidas_bahia" name="novedades_salidas_bahia" required onchange="toggleNovedadField()">
                                    <option value="NO">No hay novedades</option>
                                    <option value="SI">Sí hay novedades</option>
                                </select>
                            </div>

                            <div id="novedadField" class="novedad-field bg-yellow-50 p-4 border border-yellow-200 rounded-lg">
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2" for="descripcion_novedad">
                                    <i class="fas fa-edit text-brand-400"></i> Descripción de la Novedad <span class="text-red-500">*</span>
                                </label>
                                <textarea class="form-control w-full px-3 py-2 border border-yellow-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none transition-all disabled:bg-gray-100" id="descripcion_novedad" name="descripcion_novedad" rows="3" placeholder="Describe detalladamente la novedad..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 p-5 rounded-b-xl border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 z-10">
                <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-5 rounded-lg flex items-center gap-2 transition-all" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="bg-brand-400 hover:bg-brand-500 text-dark font-semibold py-2 px-5 rounded-lg flex items-center gap-2 transition-all shadow-md btn-primary-save" onclick="saveRecord()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    
    <div id="performanceModal" class="modal">
        <div class="bg-white mx-auto mt-10 md:mt-12 p-0 rounded-xl w-[95%] max-w-6xl shadow-2xl relative z-[10000] flex flex-col h-[85vh]">
            
            <div class="bg-dark text-white p-6 rounded-t-xl flex justify-between items-center shrink-0">
                <h3 class="text-xl font-semibold flex items-center gap-3">
                    <i class="fas fa-chart-pie text-brand-400"></i>
                    Performance: Tiempo en Bahía
                </h3>
                <button class="bg-white/10 hover:bg-brand-400/20 border border-white/30 text-white w-10 h-10 rounded-lg flex items-center justify-center transition-all hover:rotate-90 hover:text-brand-400" onclick="closePerformanceModal()">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            
            <div class="p-6 bg-gray-50 overflow-y-auto flex-grow flex flex-col gap-6">
                
                
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <div class="p-3 bg-blue-100 rounded-lg text-blue-600">
                            <i class="fas fa-filter text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase">Filtrar por Rango</p>
                            <input type="text" id="dateRangeFilter" class="w-[250px] px-3 py-1.5 border border-gray-300 rounded bg-gray-50 focus:ring-2 focus:ring-brand-400 outline-none text-sm font-medium" placeholder="Selecciona fechas...">
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-2 text-sm font-semibold text-gray-600">
                            <span class="w-3 h-3 rounded-full bg-red-500 block"></span> Meta (≤ 35 min)
                        </span>
                        <span class="flex items-center gap-2 text-sm font-semibold text-gray-600">
                            <span class="w-3 h-3 rounded-full bg-blue-500 block"></span> Promedio Real
                        </span>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                        <h4 class="text-md font-bold text-gray-700 mb-4 flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-brand-500"></i> Promedio por Meses
                        </h4>
                        <div id="chartMonthly" class="w-full h-[300px]"></div>
                    </div>
                    
                    
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                        <h4 class="text-md font-bold text-gray-700 mb-4 flex items-center gap-2">
                            <i class="fas fa-calendar-week text-brand-500"></i> Promedio por Semanas
                        </h4>
                        <div id="chartWeekly" class="w-full h-[300px]"></div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <script>
        let currentAction = 'create';
        let currentRecordId = null;
        let dataTable;

        
        let chartMonthlyInstance = null;
        let chartWeeklyInstance = null;
        let fpInstance = null; 

        $(document).ready(function() {
            const todayStr = new Date().toISOString().split('T')[0];
            $('#avgDateFilter').val(todayStr);
            $('#avgDateFilter').on('change', calculateAverageTime);

            setDefaultDateTime();
            initDataTable();
            initFlatpickr();
        });

        

        function initFlatpickr() {
            const currentYear = new Date().getFullYear();
            const firstDay = new Date(currentYear, 0, 1);
            const today = new Date();

            fpInstance = flatpickr("#dateRangeFilter", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "es",
                defaultDate: [firstDay, today], 
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        updateCharts(selectedDates[0], selectedDates[1]);
                    } else if (selectedDates.length === 0) {
                        updateCharts(null, null); 
                    }
                }
            });
        }

        function openPerformanceModal() {
            $('#performanceModal').show();
            $('body').css('overflow', 'hidden');
            
            const currentYear = new Date().getFullYear();
            const firstDay = new Date(currentYear, 0, 1);
            const today = new Date();

            
            if(!chartMonthlyInstance || !chartWeeklyInstance) {
                initCharts(firstDay, today);
            } else {
                
                
                fpInstance.setDate([firstDay, today], true); 
            }
        }

        function closePerformanceModal() {
            $('#performanceModal').hide();
            $('body').css('overflow', 'auto');
        }

        
        function getMinutesDiff(horaEntrada, horaSalida) {
            if (!horaEntrada || !horaSalida) return 0;
            const [eH, eM, eS] = horaEntrada.split(':').map(Number);
            const [sH, sM, sS] = horaSalida.split(':').map(Number);
            let e = new Date(2000, 0, 1, eH, eM, eS || 0);
            let s = new Date(2000, 0, 1, sH, sM, sS || 0);
            if (s < e) s.setDate(s.getDate() + 1);
            return (s - e) / 60000; 
        }

        
        function getWeekNumber(d) {
            d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay()||7));
            var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
            var weekNo = Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7);
            return weekNo;
        }

        function initCharts(defaultStart, defaultEnd) {
            const chartOptions = {
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: { show: false },
                    fontFamily: 'Poppins, sans-serif'
                },
                colors: ['#3b82f6'],
                dataLabels: { 
                    enabled: true, 
                    formatter: function (val) {
                        return val > 0 ? val.toFixed(1) + 'm' : '';
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: 'bold',
                        colors: ['#1a1a1a']
                    },
                    background: {
                        enabled: true,
                        foreColor: '#000',
                        padding: 4,
                        borderRadius: 4,
                        borderWidth: 1,
                        borderColor: '#fff',
                        opacity: 0.9,
                    }
                },
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [50, 100] }
                },
                yaxis: {
                    title: { text: 'Minutos', style: { fontWeight: 600 } },
                    labels: { formatter: (val) => val.toFixed(1) + 'm' }
                },
                annotations: {
                    yaxis: [{
                        y: 35, 
                        borderColor: '#ef4444',
                        strokeDashArray: 5,
                        borderWidth: 2,
                        label: {
                            borderColor: '#ef4444',
                            style: { color: '#fff', background: '#ef4444', fontWeight: 'bold' },
                            text: 'Meta (35 min)'
                        }
                    }]
                }
            };

            
            const optMonthly = {
                ...chartOptions,
                series: [{ name: 'Promedio', data: [] }],
                xaxis: { categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'] }
            };
            chartMonthlyInstance = new ApexCharts(document.querySelector("#chartMonthly"), optMonthly);
            chartMonthlyInstance.render();

            
            const optWeekly = {
                ...chartOptions,
                series: [{ name: 'Promedio', data: [] }],
                xaxis: { categories: [] }
            };
            chartWeeklyInstance = new ApexCharts(document.querySelector("#chartWeekly"), optWeekly);
            chartWeeklyInstance.render();

            updateCharts(defaultStart, defaultEnd);
        }

        function updateCharts(startDate, endDate) {
            if (!dataTable) return;
            const data = dataTable.rows().data().toArray();
            
            
            let monthsData = Array.from({length: 12}, () => ({ total: 0, count: 0 }));
            let weeksMap = {}; 

            data.forEach(r => {
                if (!r.fecha || !r.hora_entrada_bahia || !r.hora_salida_bahia) return;

                let rowDate = new Date(r.fecha + 'T00:00:00'); 

                
                if (startDate && endDate) {
                    let d = new Date(r.fecha + 'T00:00:00');
                    
                    d.setHours(0,0,0,0);
                    let s = new Date(startDate); s.setHours(0,0,0,0);
                    let e = new Date(endDate); e.setHours(0,0,0,0);
                    
                    if (d < s || d > e) return;
                }

                let mins = getMinutesDiff(r.hora_entrada_bahia, r.hora_salida_bahia);
                
                
                let mIndex = rowDate.getMonth(); 
                monthsData[mIndex].total += mins;
                monthsData[mIndex].count++;

                
                let wNum = getWeekNumber(rowDate);
                let wKey = `Sem ${wNum}`;
                if (!weeksMap[wKey]) weeksMap[wKey] = { total: 0, count: 0, weekNo: wNum };
                weeksMap[wKey].total += mins;
                weeksMap[wKey].count++;
            });

            
            let monthlyAverages = monthsData.map(m => m.count > 0 ? (m.total / m.count) : 0);
            
            
            let weeksKeys = Object.keys(weeksMap).sort((a,b) => weeksMap[a].weekNo - weeksMap[b].weekNo);
            let weeklyCategories = [];
            let weeklyAverages = [];
            
            weeksKeys.forEach(k => {
                weeklyCategories.push(k);
                weeklyAverages.push(weeksMap[k].total / weeksMap[k].count);
            });

            
            if (weeklyCategories.length === 0) {
                weeklyCategories = ['Sin datos'];
                weeklyAverages = [0];
            }

            
            chartMonthlyInstance.updateSeries([{ name: 'Promedio (min)', data: monthlyAverages }]);
            
            chartWeeklyInstance.updateOptions({
                xaxis: { categories: weeklyCategories }
            });
            chartWeeklyInstance.updateSeries([{ name: 'Promedio (min)', data: weeklyAverages }]);
        }

        

        function calculateAverageTime() {
            const selectedDate = $('#avgDateFilter').val();
            if (!dataTable) return;

            const data = dataTable.rows().data().toArray();
            const dailyRecords = data.filter(r => r.fecha === selectedDate);
            
            if (dailyRecords.length === 0) {
                $('#avgTimeDisplay').text('00:00:00').removeClass('text-blue-600').addClass('text-gray-400');
                return;
            }

            let totalMs = 0;
            let validRecords = 0;

            dailyRecords.forEach(r => {
                if (r.hora_entrada_bahia && r.hora_salida_bahia) {
                    const [eH, eM, eS] = r.hora_entrada_bahia.split(':').map(Number);
                    const [sH, sM, sS] = r.hora_salida_bahia.split(':').map(Number);
                    let entradaTime = new Date(2000, 0, 1, eH, eM, eS || 0);
                    let salidaTime = new Date(2000, 0, 1, sH, sM, sS || 0);
                    if (salidaTime < entradaTime) salidaTime.setDate(salidaTime.getDate() + 1);
                    totalMs += (salidaTime - entradaTime);
                    validRecords++;
                }
            });

            if (validRecords === 0) {
                $('#avgTimeDisplay').text('00:00:00').removeClass('text-blue-600').addClass('text-gray-400');
                return;
            }

            const avgMs = totalMs / validRecords;
            const hours = Math.floor(avgMs / 3600000);
            const minutes = Math.floor((avgMs % 3600000) / 60000);
            const seconds = Math.floor((avgMs % 60000) / 1000);

            const displayStr = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            $('#avgTimeDisplay').text(displayStr).removeClass('text-gray-400').addClass('text-blue-600');
        }

        function initDataTable() {
            dataTable = $('#recargueTable').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'desc']],
                processing: true,
                deferRender: true,
                ajax: {
                    url: '../../api/turnos/get_recargue.php',
                    type: 'POST',
                    data: { action: 'read' },
                    dataSrc: function(json) {
                        return json.success ? json.data : [];
                    }
                },
                drawCallback: function() {
                    calculateAverageTime();
                    
                    if ($('#performanceModal').is(':visible') && fpInstance) {
                        const dates = fpInstance.selectedDates;
                        if (dates.length === 2) updateCharts(dates[0], dates[1]);
                        else updateCharts(null, null);
                    }
                },
                columns: [
                    { data: 'fecha' },
                    { data: 'verificador' },
                    { data: 'turno', render: (d) => `<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-bold">${d}</span>` },
                    { data: 'placa', render: (d) => `<strong class="font-mono text-gray-800">${d}</strong>` },
                    { data: 'hora_entrada_bahia' },
                    { data: 'hora_salida_bahia' },
                    { data: 'tiempo', render: (d) => `<span class="font-semibold text-gray-600">${d}</span>` },
                    { data: 'opm1' },
                    { data: 'novedades_salidas_bahia', render: (d) => d === 'SI' ? '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-bold">SÍ</span>' : '<span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-bold">NO</span>' },
                    { data: 'estatus', render: (d) => `<span class="${getStatusClass(d)} px-2 py-1 rounded-full text-xs font-bold uppercase">${d}</span>` },
                    { data: 'canal' },
                    { data: 'conteo_vehiculo' },
                    { 
                        data: 'id',
                        render: function(data) {
                            return `<div class="flex gap-1 justify-center">
                                <button class="bg-blue-500 hover:bg-blue-600 text-white p-1.5 rounded transition-colors" onclick="openModal('view',${data})" title="Ver"><i class="fas fa-eye text-sm"></i></button>
                                <?php if ($user_cargo === 'admin'): ?>
                                <button class="bg-emerald-500 hover:bg-emerald-600 text-white p-1.5 rounded transition-colors" onclick="openModal('edit',${data})" title="Editar"><i class="fas fa-edit text-sm"></i></button>
                                <button class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded transition-colors" onclick="deleteRecord(${data})" title="Eliminar"><i class="fas fa-trash text-sm"></i></button>
                                <?php endif; ?>
                            </div>`;
                        }
                    }
                ]
            });
        }

        function setDefaultDateTime() {
            const now = new Date();
            document.getElementById('fecha').value = now.toISOString().split('T')[0];
            const time = now.toTimeString().split(' ')[0];
            document.getElementById('hora_entrada_bahia').value = '0';
             document.getElementById('hora_salida_bahia').value = time;
            document.getElementById('conteo_vehiculo').value = '0';
            calculateTurno();
        }

        function calculateTurno() {
            const hora = document.getElementById('hora_entrada_bahia').value;
            if (!hora) return;
            const [hours] = hora.split(':').map(Number);
            const totalMinutes = hours * 60;
            let turno = '';
            if (totalMinutes >= 360 && totalMinutes < 840) turno = 'A';
            else if (totalMinutes >= 840 && totalMinutes < 1320) turno = 'B';
            else turno = 'C';
            document.getElementById('turno').value = turno;
        }

        function calculateTotalTime() {
            const entrada = document.getElementById('hora_entrada_bahia').value;
            const salida = document.getElementById('hora_salida_bahia').value;
            if (!entrada || !salida) return;
            const [eH, eM, eS] = entrada.split(':').map(Number);
            const [sH, sM, sS] = salida.split(':').map(Number);
            let entradaTime = new Date(); entradaTime.setHours(eH, eM, eS || 0);
            let salidaTime = new Date(); salidaTime.setHours(sH, sM, sS || 0);
            if (salidaTime < entradaTime) salidaTime.setDate(salidaTime.getDate() + 1);
            const diffMs = salidaTime - entradaTime;
            const hours = Math.floor(diffMs / 3600000);
            const minutes = Math.floor((diffMs % 3600000) / 60000);
            const seconds = Math.floor((diffMs % 60000) / 1000);
            document.getElementById('tiempo').value = 
                `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        $('#hora_entrada_bahia').on('change', function() { calculateTurno(); calculateTotalTime(); });
        $('#hora_salida_bahia').on('change', calculateTotalTime);
        $('#placa').on('input', function() { this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); });

        function openModal(action, id = null) {
            currentAction = action;
            currentRecordId = id;
            const modal = $('#recargueModal');
            const form = $('#recargueForm')[0];
            form.reset();
            
            $('#verificador').val('<?php echo htmlspecialchars($user_nombre); ?>');
            $('#novedadField').removeClass('show');
            $('#conteo_vehiculo').val('0');

            $('.form-control').prop('disabled', false);
            $('#verificador, #turno, #tiempo').prop('readonly', true);

            const saveBtn = $('.btn-primary-save');
            
            if (action === 'create') {
                $('#modalTitle').html('<i class="fas fa-plus-circle text-brand-400"></i> Nuevo Recargue T2');
                saveBtn.html('<i class="fas fa-save"></i> Guardar').show();
                setDefaultDateTime();
            } else if (action === 'edit') {
                $('#modalTitle').html('<i class="fas fa-edit text-brand-400"></i> Editar Recargue T2');
                saveBtn.html('<i class="fas fa-save"></i> Actualizar').show();
                loadRecordData(id);
            } else {
                $('#modalTitle').html('<i class="fas fa-eye text-brand-400"></i> Ver Detalles');
                saveBtn.hide();
                loadRecordData(id, true);
            }
            modal.show();
            $('body').css('overflow', 'hidden');
        }

        function closeModal() {
            $('#recargueModal').hide();
            $('body').css('overflow', 'auto');
            $('#recargueForm')[0].reset();
            $('#novedadField').removeClass('show');
        }

        function toggleNovedadField() {
            const val = $('#novedades_salidas_bahia').val();
            const field = $('#novedadField');
            const desc = $('#descripcion_novedad');
            if (val === 'SI') {
                field.addClass('show'); desc.prop('required', true);
            } else {
                field.removeClass('show'); desc.prop('required', false).val('');
            }
        }

        function loadRecordData(id, viewOnly = false) {
            $.post('../../api/turnos/get_recargue.php', { action: 'get_by_id', id }, function(data) {
                if (data.success) {
                    const r = data.data;
                    $('#recordId').val(r.id);
                    $('#fecha').val(r.fecha);
                    $('#verificador').val(r.verificador);
                    $('#turno').val(r.turno);
                    $('#placa').val(r.placa);
                    $('#hora_entrada_bahia').val(r.hora_entrada_bahia);
                    $('#hora_inicio_cargue').val(r.hora_inicio_cargue);
                    $('#hora_final_cargue').val(r.hora_final_cargue);
                    $('#hora_salida_bahia').val(r.hora_salida_bahia);
                    $('#opm1').val(r.opm1);
                    $('#novedades_salidas_bahia').val(r.novedades_salidas_bahia);
                    $('#tiempo').val(r.tiempo);
                    $('#estatus').val(r.estatus);
                    $('#canal').val(r.canal);
                    $('#conteo_vehiculo').val(r.conteo_vehiculo || '0');

                    if (r.novedades_salidas_bahia === 'SI') {
                        $('#novedadField').addClass('show');
                        $('#descripcion_novedad').val(r.descripcion_novedad || '');
                    }
                    if (viewOnly) $('.form-control').prop('disabled', true);
                }
            }, 'json');
        }

        function saveRecord() {
            const form = $('#recargueForm')[0];
            let isValid = true;
            
            $('.form-control[required]').each(function() {
                if ($(this).prop('readonly') || $(this).prop('disabled')) return;
                if (!$(this).val() || $(this).val().trim() === '') {
                    $(this).addClass('error');
                    isValid = false;
                    setTimeout(() => $(this).removeClass('error'), 1000);
                } else {
                    $(this).removeClass('error');
                }
            });

            if (!isValid) {
                Swal.fire({ icon: 'warning', title: 'Campos Requeridos', text: 'Por favor completa todos los campos marcados con (*)', confirmButtonText: 'Entendido', confirmButtonColor: '#FFD700' });
                return;
            }

            const novedades = $('#novedades_salidas_bahia').val();
            const desc = $('#descripcion_novedad').val().trim();
            if (novedades === 'SI' && !desc) {
                Swal.fire({ icon: 'warning', title: 'Campo Requerido', text: 'Debes describir la novedad cuando seleccionas "Sí hay novedades"', confirmButtonText: 'Entendido', confirmButtonColor: '#FFD700' });
                $('#descripcion_novedad').addClass('error');
                setTimeout(() => $('#descripcion_novedad').removeClass('error'), 1000);
                return;
            }

            let conteoValue = $('#conteo_vehiculo').val();
            if (!conteoValue || conteoValue === '' || isNaN(conteoValue)) {
                $('#conteo_vehiculo').val('0');
            }

            const formData = new FormData(form);
            formData.append('action', currentAction === 'edit' ? 'update' : 'create');

            Swal.fire({ title: 'Guardando...', html: 'Por favor espera un momento', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });

            $.ajax({
                url: '../../api/turnos/get_recargue.php', method: 'POST', data: formData, processData: false, contentType: false,
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, confirmButtonText: 'Continuar', confirmButtonColor: '#FFD700' })
                        .then(() => { closeModal(); dataTable.ajax.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error al Guardar', text: data.message, confirmButtonText: 'Entendido', confirmButtonColor: '#FFD700' });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo guardar el registro. Verifica tu conexión.', confirmButtonText: 'Entendido', confirmButtonColor: '#FFD700' });
                },
                dataType: 'json'
            });
        }

        function deleteRecord(id) {
            Swal.fire({
                title: '¿Confirmar Eliminación?', text: 'Esta acción no se puede deshacer', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../../api/turnos/get_recargue.php', { action: 'delete', id }, function(data) {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: '¡Eliminado!', text: data.message, confirmButtonText: 'Continuar', confirmButtonColor: '#FFD700' })
                            .then(() => { dataTable.ajax.reload(); });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonText: 'Entendido', confirmButtonColor: '#FFD700' });
                        }
                    }, 'json');
                }
            });
        }

        function getStatusClass(status) {
            const map = { 'Completado': 'bg-green-100 text-green-800', 'En Proceso': 'bg-yellow-100 text-yellow-800', 'Pendiente': 'bg-orange-100 text-orange-800', 'Cancelado': 'bg-red-100 text-red-800' };
            return map[status] || 'bg-gray-100 text-gray-800';
        }

        $(window).on('click', function(e) {
            if ($(e.target).is('#recargueModal')) closeModal();
            if ($(e.target).is('#performanceModal')) closePerformanceModal();
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') { closeModal(); closePerformanceModal(); }
        });
    </script>
</body>
</html>