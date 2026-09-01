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
    <title>Rotación y Frescura - WARE PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Poppins', 'sans-serif'] }, colors: { brand: { 400: '#FFD700', 500: '#FFA500' }, dark: '#1a1a1a' } } } }
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #FFD700; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .custom-table th { background-color: #f9fafb; position: sticky; top: 0; z-index: 10; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; }
        .badge-red { background-color: #fee2e2; color: #dc2626; border: 1px solid #f87171; }
        .badge-yellow { background-color: #fef9c3; color: #ca8a04; border: 1px solid #facc15; }
        .badge-green { background-color: #dcfce3; color: #16a34a; border: 1px solid #4ade80; }
    </style>
</head>

<body class="text-gray-800 antialiased min-h-screen pb-10">
    <div class="max-w-[1800px] mx-auto p-4 md:p-8">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm mb-6 border-l-[5px] border-brand-400 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-20">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-1 flex items-center gap-3">
                    <i class="fas fa-file-excel text-brand-400 bg-brand-400/10 p-2 rounded-lg"></i> Dashboard Inteligente IF
                </h1>
                <p class="text-gray-500 text-sm pl-12">Análisis de Rotación y Próximos a Vencer</p>
            </div>
            
            <div class="flex flex-col md:flex-row gap-3 items-center w-full md:w-auto">
                <div class="relative w-full md:w-48">
                    <i class="fas fa-filter absolute left-3 top-3.5 text-gray-400 text-sm"></i>
                    <select id="filtroCanalGlobal" onchange="applyGlobalFilter()" class="pl-9 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm w-full outline-none focus:border-brand-400 bg-gray-50 shadow-inner cursor-pointer font-semibold text-gray-700">
                        <option value="ALL">Todos los Canales</option>
                        <option value="KA">Canal KA</option>
                        <option value="TAT">Canal TAT</option>
                    </select>
                </div>

                <div class="relative w-full md:w-64">
                    <i class="fas fa-calendar-alt absolute left-3 top-3.5 text-gray-400"></i>
                    <input type="text" id="rangoFechasGlobal" placeholder="Filtrar por Fechas..." class="pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm w-full outline-none focus:border-brand-400 bg-gray-50 shadow-inner cursor-pointer" readonly>
                </div>

                <input type="file" id="excelFile" accept=".xlsx, .xls, .csv" class="hidden" onchange="handleFileUpload(event)">
                <label for="excelFile" class="bg-dark hover:bg-gray-800 text-white px-6 py-2.5 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center gap-2 cursor-pointer w-full md:w-auto justify-center">
                    <i class="fas fa-upload text-brand-400"></i> Subir Excel
                </label>
            </div>
        </div>

        <div id="loadingState" class="hidden flex-col items-center justify-center py-20 bg-white rounded-2xl shadow-sm mb-6">
            <div class="loader mb-6"></div>
            <p class="text-gray-600 font-semibold text-lg animate-pulse" id="loadingText">Procesando...</p>
        </div>

        <div id="dashboardContent" class="hidden opacity-0 transition-opacity duration-500">
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 xl:col-span-2 flex flex-col h-[400px]">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-exclamation-triangle text-red-500"></i> Alertas Vencimiento Crítico</h3>
                    </div>
                    <div class="flex-1 overflow-y-auto border border-gray-200 rounded-lg custom-table relative">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead><tr><th class="p-2 border-b">SKU</th><th class="p-2 border-b">HL</th><th class="p-2 border-b">Cajas</th><th class="p-2 border-b">Estibas</th><th class="p-2 border-b text-center">Días Falt.</th></tr></thead>
                            <tbody id="tbodyVencimientos" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>
                
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                    <h3 class="font-bold text-gray-800 mb-2 flex items-center gap-2"><i class="fas fa-chart-pie text-brand-400"></i> Vencimientos por Familia</h3>
                    <div id="chartFamVenc" class="w-full flex-1 min-h-[250px]"></div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                    <h3 class="font-bold text-gray-800 mb-2 flex items-center gap-2"><i class="fas fa-chart-bar text-brand-400"></i> Top Productos Críticos</h3>
                    <div id="chartProdVenc" class="w-full flex-1 min-h-[250px]"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 lg:col-span-1 flex flex-col h-[400px]">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-layer-group text-brand-400"></i> HL por Estado</h3>
                    <div id="chartEstado" class="w-full flex-1 min-h-[250px]"></div>
                </div>

                
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 lg:col-span-2 flex flex-col h-[400px]">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-calendar-day text-brand-400"></i> Evolución Diaria (Comparación HL)</h3>
                    <div class="flex-1 overflow-x-auto border border-gray-200 rounded-lg custom-table relative">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead><tr><th class="p-3 border-b">Fecha Análisis</th><th class="p-3 border-b">SKU (Cod.)</th><th class="p-3 border-b">Descripción</th><th class="p-3 border-b">HL Actual</th><th class="p-3 border-b">HL Anterior</th><th class="p-3 border-b text-center">Estado</th></tr></thead>
                            <tbody id="tbodyDiario" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2 text-lg"><i class="fas fa-table text-brand-400"></i> Datos Maestros IF</h3>
                    <div class="flex w-full md:w-1/3">
                        <input type="text" id="filtroSKUMaestro" placeholder="Buscar por SKU o Descripción..." onkeyup="renderMaestro()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm w-full outline-none focus:border-brand-400 bg-gray-50">
                    </div>
                </div>
                <div class="overflow-x-auto max-h-[500px] border border-gray-200 rounded-lg custom-table">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead><tr>
                            <th class="p-3 border-b">SKU</th><th class="p-3 border-b">Descripción</th><th class="p-3 border-b">Unidades</th>
                            <th class="p-3 border-b">Cajas</th><th class="p-3 border-b">Estibas</th><th class="p-3 border-b">F. Venc</th>
                            <th class="p-3 border-b">Días Falt.</th><th class="p-3 border-b bg-brand-400/10">Ubicación</th>
                            <th class="p-3 border-b">Valor Total</th><th class="p-3 border-b">Canal</th><th class="p-3 border-b">Estado</th>
                        </tr></thead>
                        <tbody id="tbodyMaestro" class="divide-y divide-gray-100 hover:divide-gray-50"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let rawData = [];
        let filteredData = []; 
        let chartFamVenc, chartProdVenc, chartEstado;
        
        let globalStartDate = null;
        let globalEndDate = null;

        
        function fmt1(num) {
            return parseFloat(num).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 1 });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initFlatpickr();
            initCharts();
            loadDataFromDatabase(); 
        });

        function initFlatpickr() {
            flatpickr.localize(flatpickr.l10ns.es);
            flatpickr("#rangoFechasGlobal", {
                mode: "range",
                dateFormat: "Y-m-d",
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        globalStartDate = selectedDates[0];
                        globalStartDate.setHours(0,0,0,0);
                        globalEndDate = selectedDates[1];
                        globalEndDate.setHours(23,59,59,999);
                        applyGlobalFilter();
                    } else if (selectedDates.length === 0) {
                        globalStartDate = null;
                        globalEndDate = null;
                        applyGlobalFilter();
                    }
                }
            });
        }

        function loadDataFromDatabase() {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('loadingState').classList.add('flex');
            document.getElementById('dashboardContent').classList.add('hidden');
            document.getElementById('loadingText').innerText = "Cargando registros desde la base de datos...";

            fetch('../../api/reportes/get_if_data.php')
            .then(res => res.json())
            .then(result => {
                if(result.status === 'success') {
                    rawData = [];
                    result.data.forEach(row => {
                        let familia = (row.familia || '').toUpperCase();
                        
                        if(familia.includes('MKP') || familia.includes('MARKETPLACE')) return;

                        rawData.push({
                            fechaAnalisis: row.fecha_analisis,
                            familia: row.familia,
                            sku: row.cod_sku,
                            desc: row.descripcion_pt,
                            unidades: parseFloat(row.cantidad_unidades) || 0,
                            hl: parseFloat(row.hl) || 0,
                            estibas: parseFloat(row.cantidad_estibas) || 0,
                            cajas: parseFloat(row.cajas_totales) || 0,
                            fechaVenc: row.fecha_vencimiento,
                            diasFaltantes: parseInt(row.dias_faltantes) || 0,
                            valorTotal: parseFloat(row.valor_total) || 0,
                            canal: row.canal,
                            ubicacion: row.ubicacion,
                            estado: row.estado
                        });
                    });
                    applyGlobalFilter();
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Fallo de conexión al cargar la base de datos.', 'error');
            })
            .finally(() => {
                document.getElementById('loadingState').classList.add('hidden');
                document.getElementById('loadingState').classList.remove('flex');
                document.getElementById('dashboardContent').classList.remove('hidden');
                setTimeout(() => {
                    document.getElementById('dashboardContent').classList.add('opacity-100');
                    triggerResize();
                }, 150);
            });
        }

        
        function applyGlobalFilter() {
            const canalFilter = document.getElementById('filtroCanalGlobal').value;

            filteredData = rawData.filter(d => {
                
                if (globalStartDate && globalEndDate && d.fechaAnalisis) {
                    let itemDate = new Date(d.fechaAnalisis + 'T00:00:00'); 
                    if (itemDate < globalStartDate || itemDate > globalEndDate) {
                        return false;
                    }
                }
                
                
                if (canalFilter !== 'ALL' && !(d.canal && d.canal.includes(canalFilter))) {
                    return false;
                }

                return true;
            });

            renderMaestro();
            renderVencimientos();
            renderDiario();
            renderEstado();
        }

        function initCharts() {
            if(!chartFamVenc) chartFamVenc = echarts.init(document.getElementById('chartFamVenc'));
            if(!chartProdVenc) chartProdVenc = echarts.init(document.getElementById('chartProdVenc'));
            if(!chartEstado) chartEstado = echarts.init(document.getElementById('chartEstado'));
        }

        function triggerResize() {
            if(chartFamVenc) chartFamVenc.resize();
            if(chartProdVenc) chartProdVenc.resize();
            if(chartEstado) chartEstado.resize();
        }

        window.addEventListener('resize', () => { setTimeout(triggerResize, 100); });

        function excelDateToJSDate(serial) {
            if (!serial) return null;
            if (typeof serial === 'number') {
                let utc_days  = Math.floor(serial - 25569);
                let utc_value = utc_days * 86400;                                        
                let date_info = new Date(utc_value * 1000);
                let anio = date_info.getUTCFullYear();
                let mes = String(date_info.getUTCMonth() + 1).padStart(2, '0');
                let dia = String(date_info.getUTCDate()).padStart(2, '0');
                return `${anio}-${mes}-${dia}`;
            }
            return String(serial).trim();
        }

        function handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('loadingState').classList.add('flex');
            document.getElementById('dashboardContent').classList.add('hidden');
            document.getElementById('loadingText').innerText = "Extrayendo datos del Excel...";

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    let sheetName = workbook.SheetNames.includes("IF") ? "IF" : workbook.SheetNames[2];
                    const worksheet = workbook.Sheets[sheetName];
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, { range: 4, defval: "" });

                    let newDataToSave = [];

                    jsonData.forEach(row => {
                        const descripcion = row['Descripción PT'];
                        let familia = (row['Familia'] || '').toUpperCase();
                        
                        
                        if (familia.includes('MKP') || familia.includes('MARKETPLACE')) return;
                        
                        if (descripcion && descripcion !== "" && descripcion !== "#N/D") {
                            let cleanRow = {};
                            cleanRow.fechaAnalisis = excelDateToJSDate(row['Fecha Análisis']) || '';
                            cleanRow.fechaVenc = excelDateToJSDate(row['Fecha Venc.']) || '';
                            cleanRow.familia = row['Familia'] || '';
                            cleanRow.sku = row['Cod.'] || '';
                            cleanRow.desc = descripcion;
                            cleanRow.unidades = parseFloat(row['CANTIDAD EN UNIDADES']) || 0;
                            cleanRow.hl = parseFloat(row['HL']) || 0;
                            cleanRow.estibas = parseFloat(row['CANTIDAD EN ESTIBAS']) || 0;
                            cleanRow.cajas = parseFloat(row['CAJAS TOTALES']) || 0;
                            cleanRow.diasFaltantes = parseInt(row['Días Faltantes']) || 0;
                            
                            let strVal = row[' VALOR TOTAL '] || row['VALOR TOTAL'] || 0;
                            cleanRow.valorTotal = parseFloat(strVal) || 0;
                            
                            cleanRow.canal = row['CANAL'] ? row['CANAL'].trim().toUpperCase() : '';
                            cleanRow.estado = row['Estado'] || 'Sin Estado';
                            
                            let ubicacion = '';
                            const keys = Object.keys(row);
                            if(row['UBICACION']) ubicacion = row['UBICACION'];
                            else if(keys.length >= 27) { ubicacion = row[keys[keys.length - 1]]; }
                            cleanRow.ubicacion = ubicacion;

                            newDataToSave.push(cleanRow);
                        }
                    });

                    autoSaveToDatabase(newDataToSave);

                } catch (error) {
                    console.error("Error SheetJS:", error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error procesando el Excel.' });
                }
            };
            reader.readAsArrayBuffer(file);
            event.target.value = ''; 
        }

        async function autoSaveToDatabase(dataToSave) {
            Swal.fire({
                title: 'Guardando en BD...',
                html: 'Sincronizando <b>' + dataToSave.length + '</b> registros nuevos.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const response = await fetch('../../api/reportes/save_if.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataToSave)
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Sincronización Exitosa!',
                        text: `Se insertaron ${result.inserted} registros correctos en la base de datos.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    setTimeout(() => { loadDataFromDatabase(); }, 2100);
                } else {
                    Swal.fire('Fallo Base de Datos', result.message, 'error');
                }
            } catch(e) {
                console.error("Fetch Error:", e);
                Swal.fire('Error de Conexión', 'No se pudo contactar al archivo ../../api/reportes/save_if.php', 'error');
            }
        }

        
        
        
        function renderMaestro() {
            const filtroSKU = document.getElementById('filtroSKUMaestro').value.toLowerCase();
            const tbody = document.getElementById('tbodyMaestro');
            let html = '';
            let count = 0;
            for(let d of filteredData) { 
                if(filtroSKU && !d.sku.toLowerCase().includes(filtroSKU) && !d.desc.toLowerCase().includes(filtroSKU)) continue;
                if(count > 500) break;
                html += `<tr>
                    <td class="p-3 font-semibold text-gray-700">${d.sku}</td>
                    <td class="p-3">${d.desc}</td>
                    <td class="p-3">${fmt1(d.unidades)}</td>
                    <td class="p-3">${fmt1(d.cajas)}</td>
                    <td class="p-3">${fmt1(d.estibas)}</td>
                    <td class="p-3">${d.fechaVenc}</td>
                    <td class="p-3 font-bold ${d.diasFaltantes <= 60 ? 'text-red-500' : 'text-gray-700'}">${d.diasFaltantes}</td>
                    <td class="p-3 bg-brand-400/5 font-medium text-dark">${d.ubicacion}</td>
                    <td class="p-3 text-green-700 font-medium">$${fmt1(d.valorTotal)}</td>
                    <td class="p-3"><span class="badge bg-gray-200">${d.canal}</span></td>
                    <td class="p-3"><span class="badge bg-gray-100">${d.estado}</span></td>
                </tr>`;
                count++;
            }
            tbody.innerHTML = html || `<tr><td colspan="11" class="text-center p-4 text-gray-500">No hay datos en este rango de fechas o canal.</td></tr>`;
        }

        function renderVencimientos() {
            const tbody = document.getElementById('tbodyVencimientos');
            
            let dataFiltrada = filteredData.filter(d => { 
                if (d.canal && d.canal.includes('KA') && d.diasFaltantes <= 120) return true;
                if (d.canal && d.canal.includes('TAT') && d.diasFaltantes <= 60) return true;
                return false;
            });
            dataFiltrada.sort((a,b) => a.diasFaltantes - b.diasFaltantes);

            let html = '', objFam = {}, objProd = {};
            dataFiltrada.forEach(d => {
                html += `<tr>
                    <td class="p-2 font-semibold text-gray-700 text-xs">${d.sku}</td>
                    <td class="p-2 text-xs">${fmt1(d.hl)}</td>
                    <td class="p-2 text-xs">${fmt1(d.cajas)}</td>
                    <td class="p-2 text-xs">${fmt1(d.estibas)}</td>
                    <td class="p-2 text-center text-xs"><span class="badge badge-red">${d.diasFaltantes} D</span></td>
                </tr>`;
                if(!objFam[d.familia]) objFam[d.familia] = 0; objFam[d.familia] += d.hl;
                if(!objProd[d.desc]) objProd[d.desc] = 0; objProd[d.desc] += d.hl;
            });
            tbody.innerHTML = html || `<tr><td colspan="5" class="text-center p-4">No hay alertas críticas (KA <=120, TAT <=60)</td></tr>`;
            
            
            const pieData = Object.keys(objFam).map(k => ({name: k || 'Sin Familia', value: objFam[k]}));
            chartFamVenc.setOption({ 
                tooltip: { trigger: 'item', formatter: '{b}: {c} HL ({d}%)' }, 
                series: [{ 
                    type: 'pie', 
                    radius: ['40%', '70%'], 
                    itemStyle: {borderRadius: 5}, 
                    data: pieData,
                    label: { show: true, formatter: '{b}\n{d}%', fontWeight: 'bold', color: '#4b5563' }
                }] 
            }, true);
            
            const topProd = Object.keys(objProd).map(k => ({name: k, value: objProd[k]})).sort((a,b) => b.value - a.value).slice(0,10);
            chartProdVenc.setOption({ 
                tooltip: { trigger: 'axis' }, 
                grid: { top: 10, right: 40, bottom: 10, left: '35%' }, 
                xAxis: { type: 'value', show: false }, 
                yAxis: { type: 'category', data: topProd.map(p=>p.name).reverse(), axisLabel: { fontSize: 10, width: 100, overflow: 'truncate' } }, 
                series: [{ type: 'bar', data: topProd.map(p=>p.value).reverse(), itemStyle: {color: '#dc2626', borderRadius: [0,4,4,0]}, label: { show: true, position: 'right', formatter: (params) => fmt1(params.value) } }] 
            }, true);
        }

        function renderEstado() {
            let objEstado = {};
            filteredData.forEach(d => { let est = d.estado || 'No definido'; if(!objEstado[est]) objEstado[est] = 0; objEstado[est] += d.hl; });
            const estadosArr = Object.keys(objEstado).map(k => ({name: k, value: objEstado[k]})).sort((a,b) => b.value - a.value);
            
            chartEstado.setOption({ 
                tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } }, 
                grid: { top: 30, right: 20, bottom: 80, left: 50 }, 
                xAxis: { type: 'category', data: estadosArr.map(e => e.name), axisLabel: { interval: 0, rotate: 30, fontSize: 10, color: '#6b7280' } }, 
                yAxis: { type: 'value', name: 'HL' }, 
                series: [{ 
                    type: 'bar', 
                    data: estadosArr.map(e => e.value), 
                    itemStyle: { color: '#FFA500', borderRadius: [4,4,0,0] }, 
                    label: { show: true, position: 'top', formatter: (params) => fmt1(params.value) } 
                }] 
            }, true);
        }

        function renderDiario() {
            let matriz = {}, fechasSet = new Set();
            filteredData.forEach(d => { 
                if(!d.fechaAnalisis || !d.sku) return;
                fechasSet.add(d.fechaAnalisis);
                if(!matriz[d.sku]) matriz[d.sku] = { desc: d.desc, history: {} };
                if(!matriz[d.sku].history[d.fechaAnalisis]) matriz[d.sku].history[d.fechaAnalisis] = 0;
                matriz[d.sku].history[d.fechaAnalisis] += d.hl;
            });
            const fechasOrdenadas = Array.from(fechasSet).sort();
            const tbody = document.getElementById('tbodyDiario');
            let html = '';
            for(let sku in matriz) {
                const prod = matriz[sku];
                for(let i = 1; i < fechasOrdenadas.length; i++) {
                    const fechaAnt = fechasOrdenadas[i-1], fechaAct = fechasOrdenadas[i];
                    const hlAnt = prod.history[fechaAnt] || 0, hlAct = prod.history[fechaAct] || 0;
                    
                    if (hlAnt === 0 && hlAct === 0) continue;
                    
                    let badgeClase = '', badgeTexto = '';
                    if (hlAct > hlAnt) { badgeClase = 'badge-red'; badgeTexto = '<i class="fas fa-arrow-up"></i> Incrementó'; }
                    else if (hlAct === hlAnt) { badgeClase = 'badge-yellow'; badgeTexto = '<i class="fas fa-equals"></i> Sin Cambio'; }
                    else {
                        let dif = hlAnt - hlAct, diez = hlAnt * 0.10;
                        if (dif <= diez) { badgeClase = 'badge-yellow'; badgeTexto = '<i class="fas fa-arrow-down"></i> Baja Mínima (< 10%)'; }
                        else { badgeClase = 'badge-green'; badgeTexto = '<i class="fas fa-arrow-down"></i> Baja Óptima (> 10%)'; }
                    }
                    html += `<tr>
                        <td class="p-3 text-gray-500">${fechaAct}</td>
                        <td class="p-3 font-semibold text-gray-700">${sku}</td>
                        <td class="p-3">${prod.desc}</td>
                        <td class="p-3 font-bold">${fmt1(hlAct)}</td>
                        <td class="p-3 text-gray-400">${fmt1(hlAnt)}</td>
                        <td class="p-3 text-center"><span class="badge ${badgeClase}">${badgeTexto}</span></td>
                    </tr>`;
                }
            }
            tbody.innerHTML = html || `<tr><td colspan="6" class="text-center p-4">Se necesitan al menos 2 días de análisis dentro del filtro para comparar.</td></tr>`;
        }
    </script>
</body>
</html>