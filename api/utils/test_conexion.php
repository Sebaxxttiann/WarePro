<?php
require_once '../../core/config.php';

verificarLogin();
echo "<h2>Test de Conexión a Base de Datos</h2>";

try {
    
    echo "<h3>1. Test de Conexión:</h3>";
    $stmt = $pdo->query("SELECT 'Conexión exitosa' as resultado");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ " . $result['resultado'] . "</p>";
    
    
    echo "<h3>2. Test tabla productos:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos");
    $count = $stmt->fetch();
    echo "<p>Total productos: " . $count['total'] . "</p>";
    
    
    $stmt = $pdo->query("SELECT id_material, material FROM productos LIMIT 5");
    $productos = $stmt->fetchAll();
    echo "<p>Primeros 5 productos:</p><ul>";
    foreach ($productos as $producto) {
        echo "<li>" . $producto['id_material'] . " - " . $producto['material'] . "</li>";
    }
    echo "</ul>";
    
    
    echo "<h3>3. Test tabla personal_activo:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM personal_activo WHERE estado = 'activo'");
    $count = $stmt->fetch();
    echo "<p>Total personal activo: " . $count['total'] . "</p>";
    
    
    $stmt = $pdo->query("SELECT cargo, COUNT(*) as cantidad FROM personal_activo WHERE estado = 'activo' GROUP BY cargo");
    $cargos = $stmt->fetchAll();
    echo "<p>Personal por cargo:</p><ul>";
    foreach ($cargos as $cargo) {
        echo "<li>" . $cargo['cargo'] . ": " . $cargo['cantidad'] . "</li>";
    }
    echo "</ul>";
    
    
    echo "<h3>4. Test tabla devoluciones:</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'devoluciones'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "<p style='color: green;'>✓ Tabla devoluciones existe</p>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM devoluciones");
        $count = $stmt->fetch();
        echo "<p>Total devoluciones: " . $count['total'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla devoluciones NO existe</p>";
        echo "<p>Necesitas crear la tabla devoluciones. Aquí está el SQL:</p>";
        echo "<pre>
CREATE TABLE `devoluciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date DEFAULT NULL,
  `canal` varchar(10) DEFAULT NULL,
  `operador` varchar(100) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `dt` varchar(50) DEFAULT NULL,
  `unidades` int(11) DEFAULT NULL,
  `casual` varchar(100) DEFAULT NULL,
  `verificador` varchar(100) DEFAULT NULL,
  `facturador` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        </pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>