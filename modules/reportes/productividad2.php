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
    <title>Productividad Vertimiento - WARE PRO</title>
    
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
        .flatpickr-day.selected { background: #FFD700 !important; border-color: #FFD700 !important; color: #1a1a1a !important; font-weight: bold; }
        
        .loader {
            border: 4px solid #f3f3f3; border-top: 4px solid #FFD700; border-radius: 50%;
            width: 40px; height: 40px; animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>

<body class="text-gray-800 antialiased min-h-screen pb-10">
    <div class="max-w-[1600px] mx-auto p-4 md:p-8">
        
        
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 border-l-[5px] border-brand-400 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-1 flex items-center gap-3">
                    <i class="fas fa-chart-line text-brand-400"></i>
                    Productividad de Vertimiento
                </h1>
                <p class="text-gray-500 text-sm">Análisis integral de cumplimiento, horas y unidades</p>
            </div>
            
            <button onclick="fetchInitialData()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition-all flex items-center gap-2 shadow-md transform hover:-translate-y-0.5">
                <i class="fas fa-sync-alt"></i> Sincronizar Datos
            </button>
        </div>

        
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 border border-gray-100 flex flex-wrap gap-6 items-end relative z-20">
            <div class="flex-1 min-w-[300px]">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                    <i class="fas fa-calendar-alt text-brand-500"></i> Seleccionar Fecha o Rango
                </label>
                <input type="text" id="filterDate" placeholder="Elige un rango..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-400 outline-none font-medium bg-white cursor-pointer shadow-inner">
            </div>
        </div>

        
        <div id="loadingState" class="hidden flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm mb-6">
            <div class="loader mb-4"></div>
            <p class="text-gray-500 font-medium">Obteniendo datos de Google Sheets...</p>
        </div>

        
        <div id="chartsWrapper" class="hidden grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
            
            
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 xl:col-span-2">
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
                <h3 class="text-lg font-bold text-gray-700 text-center mb-2"><i class="fas fa-clock text-purple-500"></i> Por Turno</h3>
                <div id="chartTurno" class="w-full h-[400px]"></div>
            </div>

            
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 text-center mb-2"><i class="fas fa-tags text-green-500"></i> Por Descripción</h3>
                <div class="w-full overflow-x-auto custom-scrollbar pb-2">
                    <div id="chartDescripcion" class="h-[400px]"></div>
                </div>
            </div>

            
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 text-center mb-2"><i class="fas fa-layer-group text-orange-500"></i> Por Categoría</h3>
                <div class="w-full overflow-x-auto custom-scrollbar pb-2">
                    <div id="chartCategoria" class="h-[400px]"></div>
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
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200">Categoría(s)</th>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200 text-center">Horas Diarias (Promedio)</th>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200 text-center">Cumplimiento (Promedio)</th>
                            <th class="p-3 text-sm font-bold text-gray-600 border-b border-gray-200 text-center">Unidades (Total)</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        
        const GOOGLE_SHEETS_URL = 'https://sheets.googleapis.com/v4/spreadsheets/1vARa8PyXHJSKXRtzTRTfstSqbmD7hRD5nigPK1KiHjQ/values/DATOS?key=AIzaSyCm-y51bdjROuJgw1ZK5SmTvEU7NuIp-VA';
        
        let allData = [];
        let fpCalendar; 
        
        let chartInstances = {
            auxiliar: null,
            fecha: null,
            turno: null,
            descripcion: null,
            categoria: null
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
                    if (selectedDates.length > 0) renderDashboard();
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
                } else if(data.error) {
                    alert("Error en la API de Google: " + data.error.message);
                }
            } catch (error) {
                console.error("Error al obtener datos:", error);
                alert("Hubo un error conectando a Google Sheets. Verifica el nombre de la hoja en el código.");
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

        
        function calcularDiferenciaHoras(inicioStr, finStr) {
            if(!inicioStr || !finStr) return 0;
            
            const parseHora = (str) => {
                const partes = str.match(/(\d+):(\d+):(\d+)\s*(a\.?\s*m\.?|p\.?\s*m\.?|)/i);
                if(!partes) return 0;
                let h = parseInt(partes[1], 10);
                let m = parseInt(partes[2], 10);
                let ampm = partes[4] ? partes[4].toLowerCase().replace(/\s|\./g, '') : '';
                
                if(ampm === 'pm' && h < 12) h += 12;
                if(ampm === 'am' && h === 12) h = 0;
                return h + (m / 60);
            };

            let inicio = parseHora(inicioStr);
            let fin = parseHora(finStr);
            
            if (fin < inicio) fin += 24; 
            return fin - inicio;
        }

        function processSheetData(rows) {
            allData = [];

            
            
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                if (!row || row.length < 14) continue; 

                const fechaRaw = row[0] ? row[0].trim() : '';
                const fechaObj = parseSheetDate(fechaRaw);
                
                const categoria = row[1] ? row[1].trim() : 'Sin Categoría';
                const auxiliar = row[2] ? row[2].trim() : 'Desconocido';
                const turno = row[3] ? row[3].trim() : 'Sin Turno';
                const descripcion = row[5] ? row[5].trim() : 'N/A';
                
                const horaInicio = row[6] ? row[6].trim() : '';
                const horaFin = row[7] ? row[7].trim() : '';
                const horasCalculadas = calcularDiferenciaHoras(horaInicio, horaFin);
                
                const unidadesStr = row[8] ? row[8].toString().replace(',', '.') : '0';
                const cumpStr = row[13] ? row[13].toString().replace(',', '.').replace('%', '') : '0';

                allData.push({
                    fechaObj: fechaObj,
                    fechaStr: fechaRaw,
                    categoria: categoria,
                    auxiliar: auxiliar,
                    turno: turno,
                    descripcion: descripcion,
                    horas: Number(horasCalculadas.toFixed(2)) || 0,
                    cumplimiento: parseFloat(cumpStr) || 0, 
                    unidades: parseFloat(unidadesStr) || 0
                });
            }
            renderDashboard(); 
        }

        function renderDashboard() {
            const dates = fpCalendar.selectedDates;
            if (dates.length === 0) return; 

            const startDate = dates[0];
            const endDate = dates.length === 2 ? dates[1] : dates[0];

            startDate.setHours(0,0,0,0);
            endDate.setHours(23,59,59,999);
            
            
            const filteredData = allData.filter(d => {
                if(!d.fechaObj) return false;
                const dTime = d.fechaObj.getTime();
                return dTime >= startDate.getTime() && dTime <= endDate.getTime();
            });

            
            const grpAuxiliar = {};
            const grpFecha = {};
            const grpTurno = {};
            const grpDesc = {};
            const grpCat = {};
            
            filteredData.forEach(item => {
                
                const agrupar = (obj, key) => {
                    if(!obj[key]) obj[key] = { h:0, c:0, uni:0, count:0, turnos: new Set(), cats: new Set() };
                    obj[key].h += item.horas;
                    obj[key].c += item.cumplimiento;
                    obj[key].uni += item.unidades;
                    obj[key].count += 1;
                    obj[key].turnos.add(item.turno);
                    obj[key].cats.add(item.categoria);
                };

                agrupar(grpAuxiliar, item.auxiliar);
                agrupar(grpFecha, flatpickr.formatDate(item.fechaObj, "d/M"));
                agrupar(grpTurno, item.turno);
                agrupar(grpDesc, item.descripcion);
                agrupar(grpCat, item.categoria);
            });

            let tableHTML = '';
            
            const extractData = (grp) => {
                let cats = [], dataH = [], dataC = [], dataUni = [];
                Object.keys(grp).forEach(key => {
                    cats.push(key);
                    dataH.push(Number((grp[key].h / grp[key].count).toFixed(2))); 
                    dataC.push(Number((grp[key].c / grp[key].count).toFixed(2))); 
                    dataUni.push(Number((grp[key].uni).toFixed(2)));              
                });
                return { cats, series: [
                    { name: 'Horas Diarias (Prom)', type: 'column', data: dataH },
                    { name: 'Cumplimiento (Prom)', type: 'column', data: dataC },
                    { name: 'Unidades (Total)', type: 'column', data: dataUni }
                ]};
            };

            const dataAux = extractData(grpAuxiliar);
            const dataFec = extractData(grpFecha);
            const dataTur = extractData(grpTurno);
            const dataDes = extractData(grpDesc);
            const dataCat = extractData(grpCat);

            
            Object.keys(grpAuxiliar).forEach((aux) => {
                const st = grpAuxiliar[aux];
                const promedioHoras = (st.h / st.count).toFixed(2);
                const promedioCumplimiento = (st.c / st.count).toFixed(2);
                const totalUni = st.uni.toFixed(2);
                const turnosStr = Array.from(st.turnos).join(', ');
                const catsStr = Array.from(st.cats).join(', ');

                tableHTML += `
                    <tr class="hover:bg-gray-50 border-b border-gray-100">
                        <td class="p-3 text-sm font-semibold">${aux}</td>
                        <td class="p-3 text-sm text-gray-500">${turnosStr}</td>
                        <td class="p-3 text-sm text-gray-500">${catsStr}</td>
                        <td class="p-3 text-sm text-center font-mono text-blue-600">${promedioHoras} h</td>
                        <td class="p-3 text-sm text-center font-bold text-green-600">${promedioCumplimiento}</td>
                        <td class="p-3 text-sm text-center font-mono font-bold text-orange-500">${totalUni}</td>
                    </tr>
                `;
            });

            if(dataAux.cats.length === 0) {
                tableHTML = `<tr><td colspan="6" class="p-6 text-center text-gray-500">No hay datos para este rango de fechas.</td></tr>`;
            }

            document.getElementById('tableBody').innerHTML = tableHTML;
            document.getElementById('chartsWrapper').classList.remove('hidden');
            document.getElementById('chartsWrapper').classList.add('grid');
            document.getElementById('tableContainer').classList.remove('hidden');

            
            renderBarChart('#chartAuxiliar', 'auxiliar', dataAux.cats, dataAux.series);
            renderBarChart('#chartFecha', 'fecha', dataFec.cats, dataFec.series);
            renderRadarChart('#chartTurno', 'turno', dataTur.cats, dataTur.series);
            renderBarChart('#chartDescripcion', 'descripcion', dataDes.cats, dataDes.series);
            renderBarChart('#chartCategoria', 'categoria', dataCat.cats, dataCat.series);
        }

        
        function renderBarChart(selector, instanceKey, categories, series) {
            const chartElement = document.querySelector(selector);
            const minWidth = categories.length * 90; 
            chartElement.style.minWidth = minWidth > 0 ? minWidth + 'px' : '100%';
            chartElement.style.width = '100%';

            const options = {
                series: series,
                chart: { height: 380, type: 'bar', toolbar: { show: true } },
                plotOptions: { 
                    bar: { horizontal: false, columnWidth: '65%', borderRadius: 3 } 
                },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                colors: ['#3b82f6', '#10b981', '#f97316'], 
                dataLabels: { enabled: false },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: { formatter: function (val, opts) {
                        if (opts.seriesIndex === 0) return val + " h";       
                        if (opts.seriesIndex === 2) return val + " Unid";    
                        return val;                                          
                    }}
                },
                xaxis: { categories: categories, labels: { style: { fontSize: '11px', fontWeight: 600 }, rotate: -45, trim: true } },
                yaxis: [
                    { seriesName: 'Horas Diarias (Prom)', title: { text: 'Horas (h)', style: { color: '#3b82f6' } }, min: 0 },
                    { seriesName: 'Cumplimiento (Prom)', title: { text: 'Cumplimiento', style: { color: '#10b981' } }, min: 0 },
                    { seriesName: 'Unidades (Total)', opposite: true, title: { text: 'Unidades', style: { color: '#f97316' } }, min: 0 }
                ],
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
                colors: ['#3b82f6', '#10b981', '#f97316'],
                yaxis: { show: false },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: { formatter: function (val, opts) {
                        if (opts.seriesIndex === 0) return val + " h";
                        if (opts.seriesIndex === 2) return val + " Unid";
                        return val;
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
            
            if (show) {
                loader.classList.remove('hidden'); loader.classList.add('flex');
                chartsW.classList.add('hidden'); chartsW.classList.remove('grid');
                tableCont.classList.add('hidden');
            } else {
                loader.classList.add('hidden'); loader.classList.remove('flex');
            }
        }
    </script>
</body>
</html>