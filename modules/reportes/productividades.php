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
    <title>Productividades - WARE PRO</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
        .apexcharts-datalabel { font-weight: 700 !important; }
        .apexcharts-legend-text { font-weight: 600 !important; }
        
        .flatpickr-calendar { font-family: 'Poppins', sans-serif !important; box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; border: none !important; width: 320px !important; }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
            background: #FFD700 !important; border-color: #FFD700 !important; color: #1a1a1a !important; font-weight: bold;
        }
        
        .loader {
            border: 4px solid #f3f3f3; border-top: 4px solid #FFD700; border-radius: 50%;
            width: 40px; height: 40px; animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="text-gray-800 antialiased min-h-screen pb-10">
    <div class="max-w-[1600px] mx-auto p-4 md:p-8">
        
        
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 border-l-[5px] border-brand-400 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-1 flex items-center gap-3">
                    <i class="fas fa-chart-line text-brand-400"></i>
                    Productividades Diarias
                </h1>
                <p class="text-gray-500 text-sm">Análisis integral de cumplimiento, horas y hectolitros</p>
            </div>
            
            <button onclick="fetchInitialData()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition-all flex items-center gap-2 shadow-md transform hover:-translate-y-0.5">
                <i class="fas fa-sync-alt"></i> Sincronizar Datos
            </button>
        </div>

        
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 border border-gray-100 flex flex-wrap gap-6 items-end relative z-20">
            
            <div class="flex-1 min-w-[300px]">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                    <i class="fas fa-calendar-alt text-brand-500"></i> Seleccionar Fecha o Rango (Mes Actual)
                </label>
                
                <input type="text" id="filterDate" placeholder="Elige un rango..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none font-medium bg-white cursor-pointer shadow-inner">
            </div>
            
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                    <i class="fas fa-tasks text-brand-500"></i> Actividad
                </label>
                <select id="filterActividad" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none font-medium bg-gray-50 cursor-pointer" onchange="renderDashboard()">
                    <option value="Reempaque">Reempaque</option>
                    <option value="Clasificacion">Clasificación</option>
                    <option value="Lavado">Lavado</option>
                </select>
            </div>
        </div>

        
        <div id="loadingState" class="hidden flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm mb-6">
            <div class="loader mb-4"></div>
            <p class="text-gray-500 font-medium">Obteniendo datos de Google Sheets...</p>
        </div>

        
        <div id="metaContainer" class="mb-4 text-center hidden">
            <h2 id="chartTitle" class="text-xl font-bold text-gray-800">Cumplimiento</h2>
            <p id="chartSubtitle" class="text-sm text-gray-500">Meta actual: <span id="metaDisplay" class="font-bold text-green-600">0</span></p>
        </div>

        
        <div id="chartsWrapper" class="hidden grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
            
            
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 text-center mb-2"><i class="fas fa-users text-blue-500"></i> Por Auxiliar</h3>
                <div class="w-full overflow-x-auto custom-scrollbar pb-2">
                    <div id="chartAuxiliar" class="h-[400px]"></div>
                </div>
            </div>

            
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 text-center mb-2"><i class="fas fa-calendar-day text-brand-500"></i> Por Fecha</h3>
                <div class="w-full overflow-x-auto custom-scrollbar pb-2">
                    <div id="chartFecha" class="h-[400px]"></div>
                </div>
            </div>

            
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 text-center mb-2"><i class="fas fa-clock text-purple-500"></i> Rendimiento por Turno</h3>
                <p class="text-xs text-center text-gray-400 mb-2">Gráfico circular que integra las 3 variables</p>
                <div id="chartTurno" class="w-full h-[380px]"></div>
            </div>

            
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 text-center mb-2"><i class="fas fa-tags text-green-500"></i> Por Descripción 1</h3>
                <div class="w-full overflow-x-auto custom-scrollbar pb-2">
                    <div id="chartDescripcion" class="h-[400px]"></div>
                </div>
            </div>
            
        </div>

        
        <div id="tableContainer" class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 hidden relative z-10">
            <div class="bg-dark text-white p-4 flex justify-between items-center">
                <h3 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-table text-brand-400"></i> Resumen de Datos por Auxiliar
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200">Auxiliar</th>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200">Turno(s)</th>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200 text-center">Horas Diarias (Promedio)</th>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200 text-center">Cumplimiento (Promedio)</th>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200 text-center">Hectolitros (Total)</th>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200 text-center">Estatus</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        
        const METAS_ACTIVIDAD = {
            'Reempaque': 20,
            'Clasificacion': 18, 
            'Lavado': 17         
        };

        const GOOGLE_SHEETS_URL = 'https://sheets.googleapis.com/v4/spreadsheets/14nmey9lC9GEe3zf9ncPDdA0-1UXyVB3r92X5rxemugU/values/CIRCUITO?key=AIzaSyCm-y51bdjROuJgw1ZK5SmTvEU7NuIp-VA';
        
        let allData = [];
        let fpCalendar; 
        
        
        let chartInstances = {
            auxiliar: null,
            fecha: null,
            turno: null,
            descripcion: null
        };

        document.addEventListener('DOMContentLoaded', () => {
            initCalendar();
            fetchInitialData();
        });

        function initCalendar() {
            flatpickr.localize(flatpickr.l10ns.es);
            
            
            const today = new Date();
            const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            
            fpCalendar = flatpickr("#filterDate", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [firstDayOfMonth, lastDayOfMonth],
                disableMobile: "true",
                showMonths: 1, 
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        renderDashboard();
                    }
                }
            });
        }

        async function fetchInitialData() {
            showLoader(true);
            try {
                const response = await fetch(GOOGLE_SHEETS_URL);
                const data = await response.json();
                
                if (data.values && data.values.length > 1) {
                    processSheetData(data.values);
                }
            } catch (error) {
                console.error("Error al obtener datos:", error);
                alert("Hubo un error conectando a Google Sheets.");
            }
            showLoader(false);
        }

        function parseSheetDate(dateStr) {
            if(!dateStr) return null;
            const parts = dateStr.split('/');
            if(parts.length === 3) {
                let d = parseInt(parts[0]);
                let m = parseInt(parts[1]) - 1; 
                let y = parseInt(parts[2]);
                if(y < 100) y += 2000; 
                return new Date(y, m, d);
            }
            return new Date(dateStr);
        }

        function processSheetData(rows) {
            allData = [];

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                if (!row || row.length < 17) continue; 

                const fechaRaw = row[1] ? row[1].trim() : '';
                const fechaObj = parseSheetDate(fechaRaw);
                
                const actividad = row[2] ? row[2].trim() : '';
                const actividadNormalizada = actividad.toLowerCase().includes('clasificaci') ? 'Clasificacion' : actividad;
                const auxiliar = row[3] ? row[3].trim() : 'Desconocido';
                const turno = row[4] ? row[4].trim() : 'Sin Turno';
                const descripcion = row[6] ? row[6].trim() : 'N/A';
                
                const hrsStr = row[14] ? row[14].replace(',', '.') : '0';
                const cumpStr = row[16] ? row[16].replace(',', '.') : '0';
                
                const hectolitrosStr = (row.length > 18 && row[18]) ? row[18].replace(',', '.') : '0';

                allData.push({
                    fechaObj: fechaObj,
                    fechaStr: fechaRaw,
                    actividad: actividadNormalizada,
                    auxiliar: auxiliar,
                    turno: turno,
                    descripcion: descripcion,
                    horas: Math.round(parseFloat(hrsStr) || 0),
                    cumplimiento: Math.round(parseFloat(cumpStr) || 0),
                    hectolitros: parseFloat(hectolitrosStr) || 0
                });
            }

            renderDashboard(); 
        }

        function renderDashboard() {
            const selectedActividad = document.getElementById('filterActividad').value;
            const metaActual = Math.round(METAS_ACTIVIDAD[selectedActividad] || 0);

            const dates = fpCalendar.selectedDates;
            if (dates.length === 0) return; 

            const startDate = dates[0];
            const endDate = dates.length === 2 ? dates[1] : dates[0];

            startDate.setHours(0,0,0,0);
            endDate.setHours(23,59,59,999);
            
            let displayDateText = dates.length === 2 
                ? `${flatpickr.formatDate(startDate, "d/m/Y")} al ${flatpickr.formatDate(endDate, "d/m/Y")}`
                : flatpickr.formatDate(startDate, "d/m/Y");

            
            const filteredData = allData.filter(d => {
                if(!d.fechaObj) return false;
                const dTime = d.fechaObj.getTime();
                return dTime >= startDate.getTime() && dTime <= endDate.getTime() && d.actividad === selectedActividad;
            });

            document.getElementById('metaDisplay').textContent = metaActual;
            document.getElementById('chartTitle').textContent = `Cumplimiento: ${selectedActividad} (${displayDateText})`;

            
            const grpAuxiliar = {};
            const grpFecha = {};
            const grpTurno = {};
            const grpDesc = {};
            
            filteredData.forEach(item => {
                
                if(!grpAuxiliar[item.auxiliar]) grpAuxiliar[item.auxiliar] = { h:0, c:0, hec:0, count:0, turnos: new Set() };
                grpAuxiliar[item.auxiliar].h += item.horas;
                grpAuxiliar[item.auxiliar].c += item.cumplimiento;
                grpAuxiliar[item.auxiliar].hec += item.hectolitros;
                grpAuxiliar[item.auxiliar].count += 1;
                grpAuxiliar[item.auxiliar].turnos.add(item.turno);

                
                const fStr = flatpickr.formatDate(item.fechaObj, "d/M");
                if(!grpFecha[fStr]) grpFecha[fStr] = { h:0, c:0, hec:0, count:0 };
                grpFecha[fStr].h += item.horas;
                grpFecha[fStr].c += item.cumplimiento;
                grpFecha[fStr].hec += item.hectolitros;
                grpFecha[fStr].count += 1;

                
                if(!grpTurno[item.turno]) grpTurno[item.turno] = { h:0, c:0, hec:0, count:0 };
                grpTurno[item.turno].h += item.horas;
                grpTurno[item.turno].c += item.cumplimiento;
                grpTurno[item.turno].hec += item.hectolitros;
                grpTurno[item.turno].count += 1;

                
                if(!grpDesc[item.descripcion]) grpDesc[item.descripcion] = { h:0, c:0, hec:0, count:0 };
                grpDesc[item.descripcion].h += item.horas;
                grpDesc[item.descripcion].c += item.cumplimiento;
                grpDesc[item.descripcion].hec += item.hectolitros;
                grpDesc[item.descripcion].count += 1;
            });

            
            let tableHTML = '';
            
            
            const extractData = (grp) => {
                let cats = [], dataH = [], dataC = [], dataHec = [];
                Object.keys(grp).forEach(key => {
                    cats.push(key);
                    dataH.push(Math.round(grp[key].h / grp[key].count)); 
                    dataC.push(Math.round(grp[key].c / grp[key].count)); 
                    dataHec.push(Number((grp[key].hec).toFixed(4)));     
                });
                return { cats, series: [
                    { name: 'Horas Diarias', type: 'column', data: dataH },
                    { name: 'Cumplimiento', type: 'column', data: dataC },
                    { name: 'Hectolitros', type: 'column', data: dataHec }
                ] };
            };

            const dataAux = extractData(grpAuxiliar);
            const dataFec = extractData(grpFecha);
            const dataTur = extractData(grpTurno);
            const dataDes = extractData(grpDesc);

            
            Object.keys(grpAuxiliar).forEach((aux, index) => {
                const st = grpAuxiliar[aux];
                const promedioHoras = Math.round(st.h / st.count);
                const promedioCumplimiento = Math.round(st.c / st.count);
                const totalHec = st.hec.toFixed(4);
                const turnosStr = Array.from(st.turnos).join(', ');

                const isMet = promedioCumplimiento >= metaActual;
                const statusBadge = isMet 
                    ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">CUMPLE</span>`
                    : `<span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">NO CUMPLE</span>`;

                tableHTML += `
                    <tr class="hover:bg-gray-50 border-b border-gray-100">
                        <td class="p-3 text-sm font-semibold">${aux}</td>
                        <td class="p-3 text-sm text-gray-500">${turnosStr}</td>
                        <td class="p-3 text-sm text-center font-mono">${promedioHoras}</td>
                        <td class="p-3 text-sm text-center font-bold ${isMet ? 'text-green-600' : 'text-red-500'}">${promedioCumplimiento}</td>
                        <td class="p-3 text-sm text-center font-mono text-blue-600">${totalHec}</td>
                        <td class="p-3 text-sm text-center">${statusBadge}</td>
                    </tr>
                `;
            });

            if(dataAux.cats.length === 0) {
                tableHTML = `<tr><td colspan="6" class="p-6 text-center text-gray-500">No hay datos registrados para los filtros seleccionados.</td></tr>`;
            }

            document.getElementById('tableBody').innerHTML = tableHTML;
            document.getElementById('metaContainer').classList.remove('hidden');
            document.getElementById('chartsWrapper').classList.remove('hidden');
            document.getElementById('chartsWrapper').classList.add('grid');
            document.getElementById('tableContainer').classList.remove('hidden');

            
            renderBarChart('#chartAuxiliar', 'auxiliar', dataAux.cats, dataAux.series, metaActual);
            renderBarChart('#chartFecha', 'fecha', dataFec.cats, dataFec.series, metaActual);
            renderRadarChart('#chartTurno', 'turno', dataTur.cats, dataTur.series);
            renderBarChart('#chartDescripcion', 'descripcion', dataDes.cats, dataDes.series, metaActual);
        }

        
        function renderBarChart(selector, instanceKey, categories, series, meta) {
            const chartElement = document.querySelector(selector);
            
            
            
            const minWidth = categories.length * 90; 
            chartElement.style.minWidth = minWidth > 0 ? minWidth + 'px' : '100%';
            chartElement.style.width = '100%';

            const options = {
                series: series,
                chart: { height: 380, type: 'bar', toolbar: { show: true }, animations: { enabled: true } },
                plotOptions: { 
                    bar: { horizontal: false, columnWidth: '60%', borderRadius: 3, dataLabels: { position: 'top' } } 
                },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                colors: ['#cbd5e1', '#3b82f6', '#10b981'], 
                dataLabels: { enabled: false }, 
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: { formatter: function (val, opts) {
                        if (opts.seriesIndex === 2) return val.toFixed(4) + " Hec"; 
                        if (opts.seriesIndex === 0) return Math.round(val) + "h";  
                        return Math.round(val); 
                    }}
                },
                xaxis: { categories: categories, labels: { style: { fontSize: '11px', fontWeight: 600 }, rotate: -45, trim: true } },
                yaxis: [
                    { seriesName: 'Horas Diarias', title: { text: 'Horas', style: { color: '#64748b' } }, min: 0 },
                    { seriesName: 'Cumplimiento', title: { text: 'Cumplimiento', style: { color: '#3b82f6' } }, min: 0 },
                    { seriesName: 'Hectolitros', opposite: true, title: { text: 'Hectolitros (x1000)', style: { color: '#10b981' } }, min: 0, labels: { formatter: val => val.toFixed(4) } }
                ],
                annotations: {
                    yaxis: [
                        { y: meta, yAxisIndex: 1, borderColor: '#f59e0b', strokeDashArray: 4, 
                          label: { borderColor: '#f59e0b', style: { color: '#fff', background: '#f59e0b', fontSize: '11px' }, text: 'META' } }
                    ]
                },
                legend: { position: 'top', horizontalAlign: 'center', fontSize: '12px', fontWeight: 600 },
                grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
            };

            if (chartInstances[instanceKey]) chartInstances[instanceKey].destroy();
            chartInstances[instanceKey] = new ApexCharts(document.querySelector(selector), options);
            chartInstances[instanceKey].render();
        }

        
        function renderRadarChart(selector, instanceKey, categories, series) {
            const options = {
                series: series,
                chart: { height: 380, type: 'radar', toolbar: { show: true } },
                labels: categories,
                stroke: { width: 2 },
                fill: { opacity: 0.2 },
                markers: { size: 4 },
                colors: ['#cbd5e1', '#3b82f6', '#10b981'],
                yaxis: { show: false },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: { formatter: function (val, opts) {
                        if (opts.seriesIndex === 2) return val.toFixed(4) + " Hec";
                        if (opts.seriesIndex === 0) return Math.round(val) + "h";
                        return Math.round(val);
                    }}
                },
                legend: { position: 'bottom', fontSize: '12px', fontWeight: 600 }
            };

            if (chartInstances[instanceKey]) chartInstances[instanceKey].destroy();
            chartInstances[instanceKey] = new ApexCharts(document.querySelector(selector), options);
            chartInstances[instanceKey].render();
        }

        function showLoader(show) {
            const loader = document.getElementById('loadingState');
            const chartsW = document.getElementById('chartsWrapper');
            const tableCont = document.getElementById('tableContainer');
            const metaCont = document.getElementById('metaContainer');
            
            if (show) {
                loader.classList.remove('hidden'); loader.classList.add('flex');
                chartsW.classList.add('hidden'); chartsW.classList.remove('grid');
                tableCont.classList.add('hidden');
                metaCont.classList.add('hidden');
            } else {
                loader.classList.add('hidden'); loader.classList.remove('flex');
            }
        }
    </script>
</body>
</html>