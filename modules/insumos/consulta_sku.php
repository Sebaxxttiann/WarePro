<?php
require_once '../../core/header.php'; 

$productos = [];
try {
    $stmt = $pdo->prepare("SELECT id_material, material FROM productos ORDER BY material ASC");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'ware-gold': '#FFD700',
                    'ware-gold-dark': '#C5A300',
                    'ware-black': '#000000',
                    'ware-gray': '#121212'
                },
                boxShadow: {
                    'premium': '0 20px 50px rgba(0, 0, 0, 0.05)',
                    'gold': '0 10px 30px -10px rgba(255, 215, 0, 0.3)',
                }
            }
        }
    }
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap');

    body {
        font-family: 'Inter', sans-serif;
        background-color: #fcfcfc;
    }

    
    .bg-pattern {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: -1;
        background-image: radial-gradient(#FFD700 0.5px, transparent 0.5px);
        background-size: 30px 30px;
        opacity: 0.1;
    }

    
    .product-card {
        transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        border: 1px solid rgba(0,0,0,0.03);
    }
    .product-card:hover {
        transform: translateY(-5px) scale(1.01);
        border-color: #FFD700;
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    }

    
    .search-wrapper input:focus {
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.15);
    }

    
    @keyframes slideUpIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .copy-toast {
        animation: slideUpIn 0.3s ease-out;
    }

    
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<div class="bg-pattern"></div>

<main class="max-w-6xl mx-auto px-4 pt-16 pb-24">
    
    <header class="text-center mb-16 animate__animated animate__fadeIn">
        <div class="inline-block relative">
            <div class="absolute -inset-1 bg-ware-gold rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
            <div class="relative bg-ware-black text-ware-gold w-20 h-20 rounded-2xl flex items-center justify-center shadow-2xl mb-6 mx-auto">
                <i class="fas fa-magnifying-glass text-3xl"></i>
            </div>
        </div>
        <h1 class="text-5xl font-extrabold text-ware-black tracking-tighter mb-4">
            MASTER <span class="text-ware-gold">SKU</span> SEARCH
        </h1>
        <p class="text-gray-500 max-w-lg mx-auto font-medium">
            Consulta rápida de inventario y códigos de material para optimizar la operación diaria.
        </p>
    </header>

    <section class="max-w-2xl mx-auto mb-16 animate__animated animate__fadeInUp">
        <div class="search-wrapper relative group">
            <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400 group-focus-within:text-ware-gold transition-colors text-xl"></i>
            </div>
            
            <input 
                type="text" 
                id="skuSearch" 
                placeholder="Escribe el código o nombre del producto..." 
                class="w-full pl-16 pr-20 py-6 bg-white border-2 border-gray-100 rounded-3xl text-xl font-semibold placeholder-gray-300 focus:outline-none focus:border-ware-gold transition-all shadow-premium"
                autocomplete="off"
            >

            <div class="absolute inset-y-0 right-0 pr-4 flex items-center gap-3">
                <button id="clearInput" class="hidden w-10 h-10 rounded-full bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="flex justify-center gap-8 mt-6">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-ware-gold animate-pulse"></span>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Base de datos lista</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total: <span id="totalItems" class="text-ware-black"><?php echo count($productos); ?></span></span>
            </div>
        </div>
    </section>

    <section id="resultsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 min-h-[400px]">
        <div id="emptyState" class="col-span-full text-center py-20">
            <div class="opacity-10 mb-6">
                <i class="fas fa-box-open text-9xl text-ware-black"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-300 italic">Esperando tu búsqueda...</h3>
            <p class="text-gray-300 text-sm mt-2 font-medium uppercase tracking-tighter">Ingresa SKU o Descripción</p>
        </div>
    </section>

</main>

<div id="copyToast" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 pointer-events-none hidden">
    <div class="bg-ware-black text-white px-8 py-4 rounded-2xl shadow-2xl border border-ware-gold/30 flex items-center gap-4 copy-toast">
        <div class="w-8 h-8 bg-ware-gold rounded-lg flex items-center justify-center">
            <i class="fas fa-check text-ware-black text-sm"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-ware-gold">¡Copiado con éxito!</p>
            <p id="copiedSkeText" class="text-sm font-medium text-gray-300">SKU: 0000</p>
        </div>
    </div>
</div>

<script>
    const database = <?php echo json_encode($productos); ?>;
    const searchInput = document.getElementById('skuSearch');
    const resultsGrid = document.getElementById('resultsGrid');
    const clearBtn = document.getElementById('clearInput');
    const totalSpan = document.getElementById('totalItems');
    const copyToast = document.getElementById('copyToast');

    
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        
        if (query.length > 0) {
            clearBtn.classList.remove('hidden');
            performSearch(query);
        } else {
            clearBtn.classList.add('hidden');
            showInitialState();
        }
    });

    
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.classList.add('hidden');
        showInitialState();
        searchInput.focus();
    });

    function performSearch(query) {
        const matches = database.filter(item => 
            item.id_material.toLowerCase().includes(query) || 
            item.material.toLowerCase().includes(query)
        ).slice(0, 20); 

        if (matches.length === 0) {
            resultsGrid.innerHTML = `
                <div class="col-span-full text-center py-20 animate__animated animate__fadeIn">
                    <i class="fas fa-search-minus text-6xl text-gray-200 mb-4"></i>
                    <p class="text-gray-400 text-xl font-bold">No hay coincidencias para tu búsqueda</p>
                    <button onclick="clearSearch()" class="mt-4 text-ware-gold font-bold underline">Intentar de nuevo</button>
                </div>`;
            return;
        }

        renderCards(matches);
    }

    function renderCards(items) {
        resultsGrid.innerHTML = items.map((item, index) => `
            <div class="product-card bg-white p-6 rounded-[2rem] flex items-center justify-between group animate__animated animate__fadeInUp" style="animation-delay: ${index * 0.05}s">
                <div class="flex items-center gap-6">
                    <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center group-hover:bg-ware-gold transition-colors duration-500">
                        <i class="fas fa-box text-gray-300 group-hover:text-ware-black text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-ware-gold uppercase tracking-[0.2em] mb-1">Material</p>
                        <h3 class="text-gray-900 font-extrabold text-lg leading-tight">${item.material}</h3>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs font-mono font-bold tracking-tighter">
                                <i class="fas fa-barcode mr-1 text-[10px]"></i> ${item.id_material}
                            </span>
                        </div>
                    </div>
                </div>
                
                <button 
                    onclick="copySKU('${item.id_material}')" 
                    class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-400 hover:bg-ware-black hover:text-ware-gold transition-all duration-300 flex items-center justify-center shadow-sm"
                    title="Copiar Código"
                >
                    <i class="far fa-copy text-lg"></i>
                </button>
            </div>
        `).join('');
    }

    function showInitialState() {
        resultsGrid.innerHTML = `
            <div id="emptyState" class="col-span-full text-center py-20">
                <div class="opacity-10 mb-6">
                    <i class="fas fa-box-open text-9xl text-ware-black"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-300 italic">Esperando tu búsqueda...</h3>
                <p class="text-gray-300 text-sm mt-2 font-medium uppercase tracking-tighter">Ingresa SKU o Descripción</p>
            </div>`;
    }

    
    window.copySKU = (sku) => {
        navigator.clipboard.writeText(sku).then(() => {
            
            document.getElementById('copiedSkeText').innerText = `SKU: ${sku}`;
            
            
            copyToast.classList.remove('hidden');
            
            
            

            
            setTimeout(() => {
                copyToast.classList.add('animate__fadeOutDown');
                setTimeout(() => {
                    copyToast.classList.add('hidden');
                    copyToast.classList.remove('animate__fadeOutDown');
                }, 500);
            }, 2500);
        });
    };
</script>

</body>
</html>