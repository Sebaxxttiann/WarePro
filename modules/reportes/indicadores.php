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
    <title>KPIs y PIs - WARE PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { brand: { 400: '#FFD700', 500: '#FFA500' }, dark: '#1a1a1a' }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .table-input { 
            width: 50px; text-align: center; border: 1px solid transparent; background: transparent; 
            font-weight: 600; font-size: 0.875rem; border-radius: 0.25rem; outline: none;
        }
        .table-input:focus { border-color: #FFD700; background: #fff; }
        .table-input:disabled { background-color: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
        
        .status-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-fail { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status-trigger { background-color: #fee2e2; color: #991b1b; border: 2px solid #3b82f6 !important; }
        
        
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }

        
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="text-gray-800 antialiased min-h-screen pb-20">
    <div class="max-w-[1800px] mx-auto p-4 md:p-8">
        
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 border-l-[5px] border-brand-400 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-1 flex items-center gap-3">
                    <i class="fas fa-bullseye text-brand-400"></i> Tablero de KPIs y PIs
                </h1>
                <p class="text-gray-500 text-sm">Medición, seguimiento y ordenamiento (arrastra para ordenar)</p>
            </div>
            <button onclick="document.getElementById('monthModal').classList.remove('hidden')" class="bg-white border-2 border-gray-200 hover:border-brand-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition-all flex items-center gap-2 shadow-sm">
                <i class="far fa-calendar-alt text-brand-500"></i> Mes Actual: <span id="currentMonthDisplay">Cargando...</span>
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden relative">
            <div id="tableLoader" class="absolute inset-0 bg-white bg-opacity-75 z-30 flex flex-col justify-center items-center hidden">
                <i class="fas fa-circle-notch fa-spin text-4xl text-brand-500 mb-2"></i>
                <p class="font-bold text-gray-600">Cargando datos...</p>
            </div>
            <div class="overflow-x-auto custom-scrollbar" id="tableContainer"></div>
        </div>
    </div>

    
    <button onclick="openIndicatorModal('create')" class="fixed bottom-8 right-8 bg-dark text-white w-14 h-14 rounded-full shadow-lg hover:bg-brand-500 hover:text-dark transition-all flex items-center justify-center text-2xl z-40 transform hover:scale-110">
        <i class="fas fa-plus"></i>
    </button>

    
    <div id="indicatorModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="bg-dark p-4 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg" id="modalTitle">Nuevo Indicador</h3>
                <button onclick="closeIndicatorModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form id="indicatorForm" class="space-y-4">
                    <input type="hidden" id="indId">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre</label>
                            <input type="text" id="indNombre" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-brand-400 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipo</label>
                            <select id="indTipo" class="w-full px-3 py-2 border rounded-lg outline-none">
                                <option value="KPI">KPI</option>
                                <option value="PI">PI</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Temporalidad</label>
                            <select id="indTemporalidad" class="w-full px-3 py-2 border rounded-lg outline-none">
                                <option value="Diario">Diario</option>
                                <option value="Semanal">Semanal</option>
                                <option value="Mensual">Mensual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Unidad de Medida</label>
                            <select id="indUnidad" onchange="toggleUnidadEspecifica()" class="w-full px-3 py-2 border rounded-lg outline-none">
                                <option value="Porcentaje">Porcentaje (%)</option>
                                <option value="Numero">Número (#)</option>
                                <option value="Cantidad">Cantidad de...</option>
                            </select>
                        </div>
                        <div id="divUnidadEspec" class="col-span-2 hidden">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Especificar Cantidad</label>
                            <input type="text" id="indUnidadEspec" placeholder="Ej: Cajas, Pallets, Litros" class="w-full px-3 py-2 border rounded-lg outline-none">
                        </div>
                    </div>

                    <div class="border-t pt-4 mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="bg-green-50 p-4 rounded-lg border border-green-100 flex flex-col">
                            <h4 class="font-bold text-green-800 mb-2 text-sm"><i class="fas fa-flag-checkered"></i> Meta</h4>
                            <div class="flex gap-2 mb-3">
                                <select id="metaOp" class="w-1/2 px-2 py-2 border rounded-lg text-sm outline-none" onchange="checkMetaChange()">
                                    <option value=">=">Mayor o igual (>=)</option>
                                    <option value=">">Mayor que (>)</option>
                                    <option value="<=">Menor o igual (<=)</option>
                                    <option value="<">Menor que (<)</option>
                                    <option value="==">Igual (==)</option>
                                </select>
                                <input type="number" step="0.01" id="metaVal" placeholder="Valor" class="w-1/2 px-3 py-2 border rounded-lg outline-none" required oninput="checkMetaChange()">
                            </div>
                            
                            
                            <label class="flex items-center text-xs text-green-700 font-semibold cursor-pointer mb-2">
                                <input type="checkbox" id="hasMeta2" class="mr-2 accent-green-600" onchange="toggleSecondRule('meta2Div', this.checked); checkMetaChange()">
                                Añadir 2do límite (Rango)
                            </label>
                            <div id="meta2Div" class="hidden flex gap-2 border-t border-green-200 pt-3">
                                <span class="text-xs font-bold text-green-800 self-center uppercase">Y</span>
                                <select id="metaOp2" class="w-1/2 px-2 py-2 border rounded-lg text-sm outline-none" onchange="checkMetaChange()">
                                    <option value="<=">Menor o igual (<=)</option>
                                    <option value="<">Menor que (<)</option>
                                    <option value=">=">Mayor o igual (>=)</option>
                                    <option value=">">Mayor que (>)</option>
                                </select>
                                <input type="number" step="0.01" id="metaVal2" placeholder="Valor 2" class="w-1/2 px-3 py-2 border rounded-lg outline-none" oninput="checkMetaChange()">
                            </div>
                        </div>

                        
                        <div class="bg-red-50 p-4 rounded-lg border border-red-100 flex flex-col">
                            <h4 class="font-bold text-red-800 mb-2 text-sm"><i class="fas fa-exclamation-triangle"></i> Disparador</h4>
                            <div class="flex gap-2 mb-3">
                                <select id="dispOp" class="w-1/2 px-2 py-2 border rounded-lg text-sm outline-none" onchange="checkMetaChange()">
                                    <option value="<=">Menor o igual (<=)</option>
                                    <option value="<">Menor que (<)</option>
                                    <option value=">=">Mayor o igual (>=)</option>
                                    <option value=">">Mayor que (>)</option>
                                    <option value="==">Igual (==)</option>
                                </select>
                                <input type="number" step="0.01" id="dispVal" placeholder="Valor" class="w-1/2 px-3 py-2 border rounded-lg outline-none" required oninput="checkMetaChange()">
                            </div>
                            
                            
                            <label class="flex items-center text-xs text-red-700 font-semibold cursor-pointer mb-2">
                                <input type="checkbox" id="hasDisp2" class="mr-2 accent-red-600" onchange="toggleSecondRule('disp2Div', this.checked); checkMetaChange()">
                                Añadir 2do límite (Rango)
                            </label>
                            <div id="disp2Div" class="hidden flex gap-2 border-t border-red-200 pt-3">
                                <span class="text-xs font-bold text-red-800 self-center uppercase">Y</span>
                                <select id="dispOp2" class="w-1/2 px-2 py-2 border rounded-lg text-sm outline-none" onchange="checkMetaChange()">
                                    <option value=">=">Mayor o igual (>=)</option>
                                    <option value=">">Mayor que (>)</option>
                                    <option value="<=">Menor o igual (<=)</option>
                                    <option value="<">Menor que (<)</option>
                                </select>
                                <input type="number" step="0.01" id="dispVal2" placeholder="Valor 2" class="w-1/2 px-3 py-2 border rounded-lg outline-none" oninput="checkMetaChange()">
                            </div>
                        </div>
                    </div>

                    <div id="divHistorial" class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 hidden mt-4">
                        <p class="text-sm font-semibold text-yellow-800 mb-2">Has modificado la Meta o Disparador. ¿Deseas modificar los resultados anteriores?</p>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                                <input type="radio" name="updateHist" value="yes" class="accent-brand-500"> Sí, actualizar.
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                                <input type="radio" name="updateHist" value="no" checked class="accent-brand-500"> No, solo futuros.
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="p-4 border-t bg-gray-50 flex justify-between items-center">
                <button type="button" id="btnDeleteInd" onclick="deleteIndicador()" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-lg hidden"><i class="fas fa-trash-alt"></i> Eliminar</button>
                <div class="flex gap-3 ml-auto">
                    <button onclick="closeIndicatorModal()" class="px-4 py-2 text-gray-600 font-semibold hover:bg-gray-200 rounded-lg">Cancelar</button>
                    <button type="button" onclick="saveIndicador()" class="px-6 py-2 bg-brand-500 hover:bg-brand-400 text-dark font-bold rounded-lg shadow-sm">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    
    <div id="monthModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-xl overflow-hidden">
            <div class="bg-dark p-4 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg"><i class="fas fa-calendar-alt"></i> Seleccionar Mes</h3>
                <button onclick="document.getElementById('monthModal').classList.add('hidden')" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <button id="prevYear" class="p-2 hover:bg-gray-100 rounded"><i class="fas fa-chevron-left"></i></button>
                    <span id="yearDisplay" class="font-bold text-xl"></span>
                    <button id="nextYear" class="p-2 hover:bg-gray-100 rounded"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="grid grid-cols-3 gap-3" id="monthGrid"></div>
            </div>
        </div>
    </div>

    <script>
        const monthNames = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
        const today = new Date();
        let currentYear = today.getFullYear();
        let currentMonth = today.getMonth();

        let dbIndicators = [];
        let dbValues = [];
        
        let origRules = {};

        document.addEventListener('DOMContentLoaded', () => {
            renderMonthCards();
            loadDashboardData();
        });

        
        async function loadDashboardData() {
            document.getElementById('tableLoader').classList.remove('hidden');
            try {
                const resInd = await fetch('../../api/personal/guardar_kpi.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'get_indicadores' })
                });
                const dataInd = await resInd.json();
                dbIndicators = dataInd.data || [];

                const resVal = await fetch('../../api/personal/guardar_kpi.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'get_valores', year: currentYear, month: currentMonth + 1 })
                });
                const dataVal = await resVal.json();
                dbValues = dataVal.data || [];

                generateDynamicTable(currentYear, currentMonth);
            } catch (error) {
                console.error("Error cargando datos:", error);
            } finally {
                document.getElementById('tableLoader').classList.add('hidden');
            }
        }

        async function saveIndicador() {
            if(!document.getElementById('indicatorForm').checkValidity()){
                alert("Completa todos los campos obligatorios."); return;
            }

            const data = {
                action: 'save_indicador',
                id: document.getElementById('indId').value,
                nombre: document.getElementById('indNombre').value,
                tipo: document.getElementById('indTipo').value,
                temporalidad: document.getElementById('indTemporalidad').value,
                unidad_medida: document.getElementById('indUnidad').value,
                unidad_especifica: document.getElementById('indUnidadEspec').value,
                
                meta_operador: document.getElementById('metaOp').value,
                meta_valor: document.getElementById('metaVal').value,
                meta_operador_2: document.getElementById('hasMeta2').checked ? document.getElementById('metaOp2').value : null,
                meta_valor_2: document.getElementById('hasMeta2').checked ? document.getElementById('metaVal2').value : null,
                
                disparador_operador: document.getElementById('dispOp').value,
                disparador_valor: document.getElementById('dispVal').value,
                disparador_operador_2: document.getElementById('hasDisp2').checked ? document.getElementById('dispOp2').value : null,
                disparador_valor_2: document.getElementById('hasDisp2').checked ? document.getElementById('dispVal2').value : null,
                
                updateHist: document.querySelector('input[name="updateHist"]:checked').value
            };

            await fetch('../../api/personal/guardar_kpi.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            closeIndicatorModal();
            loadDashboardData();
        }

        async function deleteIndicador() {
            const id = document.getElementById('indId').value;
            if(!id) return;
            if(confirm("¿Estás seguro de eliminar este indicador? Se borrará todo su historial.")) {
                await fetch('../../api/personal/guardar_kpi.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'delete_indicador', id: id })
                });
                closeIndicatorModal();
                loadDashboardData();
            }
        }

        async function saveKpiValue(inputElem, indId, dateStr, mOp, mVal, mOp2, mVal2, dOp, dVal, dOp2, dVal2) {
            const val = inputElem.value;
            
            
            let cssClass = 'table-input';
            if (val !== '') {
                if (evaluateFullLogic(val, dOp, dVal, dOp2, dVal2)) cssClass += ' status-trigger';
                else if (evaluateFullLogic(val, mOp, mVal, mOp2, mVal2)) cssClass += ' status-success';
                else cssClass += ' status-fail';
            }
            inputElem.className = cssClass;

            recalcAverages(inputElem);

            
            await fetch('../../api/personal/guardar_kpi.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'save_valor', indicador_id: indId, fecha: dateStr, valor: val,
                    meta_operador: mOp, meta_valor: mVal, meta_operador_2: mOp2, meta_valor_2: mVal2,
                    disparador_operador: dOp, disparador_valor: dVal, disparador_operador_2: dOp2, disparador_valor_2: dVal2
                })
            });
        }

        

        function toggleUnidadEspecifica() {
            const val = document.getElementById('indUnidad').value;
            const div = document.getElementById('divUnidadEspec');
            if (val === 'Cantidad') div.classList.remove('hidden'); else div.classList.add('hidden');
        }

        function toggleSecondRule(divId, isChecked) {
            const div = document.getElementById(divId);
            if (isChecked) div.classList.remove('hidden'); else div.classList.add('hidden');
        }

        function checkMetaChange() {
            const isEditing = document.getElementById('indId').value !== "";
            if (!isEditing) return;

            const current = {
                mVal: document.getElementById('metaVal').value,
                mVal2: document.getElementById('hasMeta2').checked ? document.getElementById('metaVal2').value : null,
                dVal: document.getElementById('dispVal').value,
                dVal2: document.getElementById('hasDisp2').checked ? document.getElementById('dispVal2').value : null
            };
            
            if (current.mVal != origRules.mVal || current.dVal != origRules.dVal || current.mVal2 != origRules.mVal2 || current.dVal2 != origRules.dVal2) {
                document.getElementById('divHistorial').classList.remove('hidden');
            } else {
                document.getElementById('divHistorial').classList.add('hidden');
            }
        }

        function openIndicatorModal(mode, id = null) {
            const form = document.getElementById('indicatorForm');
            form.reset();
            document.getElementById('divHistorial').classList.add('hidden');
            
            document.getElementById('hasMeta2').checked = false;
            document.getElementById('hasDisp2').checked = false;
            toggleSecondRule('meta2Div', false);
            toggleSecondRule('disp2Div', false);
            
            if (mode === 'create') {
                document.getElementById('modalTitle').innerText = 'Nuevo Indicador';
                document.getElementById('indId').value = "";
                document.getElementById('btnDeleteInd').classList.add('hidden');
                origRules = {};
            } else {
                document.getElementById('modalTitle').innerText = 'Editar Indicador';
                document.getElementById('btnDeleteInd').classList.remove('hidden');
                
                const ind = dbIndicators.find(i => i.id == id);
                if(ind) {
                    document.getElementById('indId').value = ind.id;
                    document.getElementById('indNombre').value = ind.nombre;
                    document.getElementById('indTipo').value = ind.tipo;
                    document.getElementById('indTemporalidad').value = ind.temporalidad;
                    document.getElementById('indUnidad').value = ind.unidad_medida;
                    document.getElementById('indUnidadEspec').value = ind.unidad_especifica || '';
                    
                    document.getElementById('metaOp').value = ind.meta_operador;
                    document.getElementById('metaVal').value = ind.meta_valor;
                    
                    if (ind.meta_operador_2 && ind.meta_valor_2 !== null) {
                        document.getElementById('hasMeta2').checked = true;
                        toggleSecondRule('meta2Div', true);
                        document.getElementById('metaOp2').value = ind.meta_operador_2;
                        document.getElementById('metaVal2').value = ind.meta_valor_2;
                    }
                    
                    document.getElementById('dispOp').value = ind.disparador_operador;
                    document.getElementById('dispVal').value = ind.disparador_valor;
                    
                    if (ind.disparador_operador_2 && ind.disparador_valor_2 !== null) {
                        document.getElementById('hasDisp2').checked = true;
                        toggleSecondRule('disp2Div', true);
                        document.getElementById('dispOp2').value = ind.disparador_operador_2;
                        document.getElementById('dispVal2').value = ind.disparador_valor_2;
                    }
                    
                    origRules = {
                        mVal: ind.meta_valor,
                        mVal2: document.getElementById('hasMeta2').checked ? ind.meta_valor_2 : null,
                        dVal: ind.disparador_valor,
                        dVal2: document.getElementById('hasDisp2').checked ? ind.disparador_valor_2 : null
                    };
                }
            }
            toggleUnidadEspecifica();
            document.getElementById('indicatorModal').classList.remove('hidden');
        }

        function closeIndicatorModal() {
            document.getElementById('indicatorModal').classList.add('hidden');
        }

        function renderMonthCards() {
            const grid = document.getElementById('monthGrid');
            grid.innerHTML = '';
            monthNames.forEach((m, idx) => {
                const btn = document.createElement('button');
                btn.className = `p-4 border rounded-xl font-bold transition-all ${idx === currentMonth ? 'bg-brand-400 text-dark border-brand-500 shadow-md' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'}`;
                btn.innerText = m;
                btn.onclick = () => {
                    currentMonth = idx;
                    document.getElementById('monthModal').classList.add('hidden');
                    loadDashboardData();
                };
                grid.appendChild(btn);
            });
            document.getElementById('yearDisplay').innerText = currentYear;
            document.getElementById('currentMonthDisplay').innerText = `${monthNames[currentMonth]} ${currentYear}`;
        }

        document.getElementById('prevYear').onclick = () => { currentYear--; renderMonthCards(); }
        document.getElementById('nextYear').onclick = () => { currentYear++; renderMonthCards(); }

        
        function evaluateSingleLogic(val, op, target) {
            val = parseFloat(val); target = parseFloat(target);
            if(isNaN(val) || isNaN(target) || !op) return false;
            if(op === '>=') return val >= target;
            if(op === '<=') return val <= target;
            if(op === '>') return val > target;
            if(op === '<') return val < target;
            if(op === '==') return val === target;
            return false;
        }

        
        function evaluateFullLogic(val, op1, val1, op2, val2) {
            let res1 = evaluateSingleLogic(val, op1, val1);
            if (op2 && val2 !== null && val2 !== '') {
                let res2 = evaluateSingleLogic(val, op2, val2);
                return res1 && res2;
            }
            return res1;
        }

        function getDbValue(indId, dayNum) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
            return dbValues.find(v => v.indicador_id == indId && v.fecha === dateStr);
        }

        function generateDynamicTable(year, month) {
            const container = document.getElementById('tableContainer');
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysOfWeek = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
            
            let weeks = [];
            let currentWeek = [];
            
            for (let d = 1; d <= daysInMonth; d++) {
                let dateObj = new Date(year, month, d);
                let jsDay = dateObj.getDay(); 
                let isoDay = jsDay === 0 ? 6 : jsDay - 1;

                currentWeek.push({ dayStr: daysOfWeek[isoDay], dateNum: d, isLastOfMonth: d === daysInMonth });
                if (isoDay === 6 || d === daysInMonth) {
                    weeks.push(currentWeek); currentWeek = [];
                }
            }

            let html = `<table class="w-full text-left border-collapse min-w-[1200px]">`;
            
            
            html += `<thead class="bg-dark text-white"><tr class="text-xs uppercase">
                <th class="p-0 sticky left-0 bg-dark z-20 min-w-[420px] max-w-[450px] border-r border-gray-600 shadow-[2px_0_5px_rgba(0,0,0,0.3)]" rowspan="2">
                    <div class="flex h-full items-stretch w-full leading-tight font-semibold text-[11px]">
                        <div class="flex-1 p-3 flex items-center border-r border-gray-600">KPI / PI</div>
                        <div class="w-16 p-3 flex items-center justify-center border-r border-gray-600">TIPO</div>
                        <div class="w-16 p-3 flex items-center justify-center border-r border-gray-600 text-center">U.M.</div>
                        <div class="w-24 p-3 flex items-center justify-center border-r border-gray-600 text-red-400">DISP.</div>
                        <div class="w-24 p-3 flex items-center justify-center text-green-400">META</div>
                    </div>
                </th>`;
            
            weeks.forEach((w, i) => { html += `<th colspan="${w.length + 1}" class="p-2 text-center border-r border-gray-600">Semana ${i + 1}</th>`; });
            html += `<th class="p-2 text-center bg-brand-500 text-dark w-20" rowspan="2">MTD</th></tr><tr>`;

            weeks.forEach((w, i) => {
                w.forEach(day => {
                    html += `<th class="p-2 text-center border-r border-gray-600 bg-gray-800 w-12">${day.dayStr}<br><span class="text-[10px] text-gray-400">${day.dateNum}</span></th>`;
                });
                html += `<th class="p-2 text-center border-r border-gray-600 bg-gray-700 w-16 text-[10px]">PROM<br>SEM</th>`;
            });
            html += `</tr></thead><tbody class="text-sm">`;

            if(dbIndicators.length === 0) {
                html += `<tr><td colspan="100%" class="p-6 text-center text-gray-500">No hay indicadores creados. Usa el botón '+' para añadir uno.</td></tr>`;
            }

            dbIndicators.forEach(ind => {
                let umShort = ind.unidad_medida === 'Porcentaje' ? '%' : (ind.unidad_medida === 'Numero' ? '#' : ind.unidad_especifica);
                
                html += `<tr class="border-b hover:bg-gray-50 group kpi-row" data-ind-id="${ind.id}">
                    <td class="p-0 sticky left-0 bg-white group-hover:bg-gray-50 z-10 border-r shadow-[2px_0_5px_rgba(0,0,0,0.05)] align-top">
                        <div class="flex h-full items-stretch w-full min-h-[50px]">
                           <div class="flex-1 p-3 flex items-center border-r border-gray-200 min-w-0">
    <i class="fas fa-grip-vertical text-gray-300 mr-3 flex-shrink-0 drag-handle hover:text-brand-500 text-lg transition-colors" title="Arrastrar para ordenar"></i>
    <span class="font-bold text-gray-800 cursor-pointer hover:text-blue-600 truncate block w-full" title="${ind.nombre}" onclick="openIndicatorModal('edit', ${ind.id})">${ind.nombre}</span>
</div>
                            <div class="w-16 p-2 flex items-center justify-center border-r border-gray-200 text-[10px] bg-gray-50 text-gray-600 font-medium">${ind.tipo}</div>
                            <div class="w-16 p-2 flex items-center justify-center border-r border-gray-200 text-[10px] text-gray-500 text-center truncate">${umShort}</div>
                            
                            <div class="w-24 p-2 flex flex-col items-center justify-center border-r border-gray-200 text-xs font-mono text-red-600 leading-tight">
                                <span>${ind.disparador_operador}${ind.disparador_valor}</span>
                                ${ind.disparador_operador_2 ? `<span class="text-[9px] mt-1 text-red-400 font-semibold">& ${ind.disparador_operador_2}${ind.disparador_valor_2}</span>` : ''}
                            </div>
                            <div class="w-24 p-2 flex flex-col items-center justify-center text-xs font-mono text-green-600 font-bold leading-tight">
                                <span>${ind.meta_operador}${ind.meta_valor}</span>
                                ${ind.meta_operador_2 ? `<span class="text-[9px] mt-1 text-green-500 font-semibold">& ${ind.meta_operador_2}${ind.meta_valor_2}</span>` : ''}
                            </div>
                        </div>
                    </td>`;

                weeks.forEach((w, wIndex) => {
                    w.forEach(day => {
                        let disabled = false;
                        if(ind.temporalidad === 'Semanal') disabled = !(day === w[w.length-1]);
                        else if(ind.temporalidad === 'Mensual') disabled = !day.isLastOfMonth;

                        let dbVal = getDbValue(ind.id, day.dateNum);
                        let val = dbVal ? dbVal.valor : '';
                        
                        
                        let mOp = dbVal && dbVal.meta_operador_hist ? dbVal.meta_operador_hist : ind.meta_operador;
                        let mVal = dbVal && dbVal.meta_valor_hist !== null ? dbVal.meta_valor_hist : ind.meta_valor;
                        let mOp2 = dbVal && dbVal.meta_operador_2_hist ? dbVal.meta_operador_2_hist : ind.meta_operador_2;
                        let mVal2 = dbVal && dbVal.meta_valor_2_hist !== null ? dbVal.meta_valor_2_hist : ind.meta_valor_2;

                        let dOp = dbVal && dbVal.disparador_operador_hist ? dbVal.disparador_operador_hist : ind.disparador_operador;
                        let dVal = dbVal && dbVal.disparador_valor_hist !== null ? dbVal.disparador_valor_hist : ind.disparador_valor;
                        let dOp2 = dbVal && dbVal.disparador_operador_2_hist ? dbVal.disparador_operador_2_hist : ind.disparador_operador_2;
                        let dVal2 = dbVal && dbVal.disparador_valor_2_hist !== null ? dbVal.disparador_valor_2_hist : ind.disparador_valor_2;

                        let dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day.dateNum).padStart(2,'0')}`;

                        let cssClass = '';
                        if(val !== '') {
                            if (evaluateFullLogic(val, dOp, dVal, dOp2, dVal2)) cssClass = 'status-trigger';
                            else if (evaluateFullLogic(val, mOp, mVal, mOp2, mVal2)) cssClass = 'status-success';
                            else cssClass = 'status-fail';
                        }

                        
                        const safeString = (s) => s ? `'${s}'` : 'null';
                        const safeNum = (n) => (n !== null && n !== undefined && n !== '') ? n : 'null';
                        
                        const args = `this, ${ind.id}, '${dateStr}', '${mOp}', ${mVal}, ${safeString(mOp2)}, ${safeNum(mVal2)}, '${dOp}', ${dVal}, ${safeString(dOp2)}, ${safeNum(dVal2)}`;

                        html += `<td class="p-1 border-r text-center">
                            <input type="number" step="0.01" class="table-input w-full kpi-input week-${wIndex} ${cssClass}" value="${val}" ${disabled ? 'disabled' : ''} onchange="saveKpiValue(${args})">
                        </td>`;
                    });
                    html += `<td class="p-1 border-r bg-gray-50 text-center font-bold text-gray-600 text-xs avg-sem-${wIndex}">--</td>`;
                });
                
                html += `<td class="p-2 border-r bg-brand-50 text-center font-bold text-dark text-sm avg-mtd">--</td>`;
                html += `</tr>`;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;

            
            const tbody = container.querySelector('tbody');
            if (tbody && dbIndicators.length > 0) {
                new Sortable(tbody, {
                    handle: '.drag-handle', 
                    animation: 150,
                    ghostClass: 'bg-brand-50', 
                    onEnd: async function (evt) {
                        const rows = tbody.querySelectorAll('tr.kpi-row');
                        const newOrder = Array.from(rows).map((row, index) => ({
                            id: row.getAttribute('data-ind-id'),
                            orden: index
                        }));
                        
                        try {
                            await fetch('../../api/personal/guardar_kpi.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ action: 'update_order', order: newOrder })
                            });
                        } catch(e) {
                            console.error('Error guardando orden', e);
                        }
                    }
                });
            }

            
            document.querySelectorAll('.kpi-row').forEach(row => {
                recalcAverages(row.querySelector('.kpi-input'));
            });
        }

        function recalcAverages(element) {
            if(!element) return;
            const row = element.closest('.kpi-row');
            if(!row) return;

            let sumMTD = 0, countMTD = 0;

            const allInputs = row.querySelectorAll('.kpi-input');
            const weeksCount = Array.from(allInputs).reduce((acc, input) => {
                const wClass = Array.from(input.classList).find(c => c.startsWith('week-'));
                if(wClass && !acc.includes(wClass)) acc.push(wClass);
                return acc;
            }, []);

            weeksCount.forEach(wClass => {
                const inputsInWeek = row.querySelectorAll(`.${wClass}`);
                const idx = wClass.split('-')[1];
                let sumSem = 0, countSem = 0;
                
                inputsInWeek.forEach(inp => {
                    if(inp.value !== '') {
                        let v = parseFloat(inp.value);
                        sumSem += v; countSem++;
                        sumMTD += v; countMTD++;
                    }
                });
                
                const avgCell = row.querySelector(`.avg-sem-${idx}`);
                if(avgCell) avgCell.innerText = countSem > 0 ? (sumSem / countSem).toFixed(1) : '--';
            });

            const mtdCell = row.querySelector('.avg-mtd');
            if(mtdCell) mtdCell.innerText = countMTD > 0 ? (sumMTD / countMTD).toFixed(1) : '--';
        }
    </script>
</body>
</html>