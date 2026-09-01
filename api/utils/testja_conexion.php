<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Inicio del script...<br>";


if (file_exists('../../core/config.php')) {
    echo "../../core/config.php existe<br>";
    require_once '../../core/config.php';
verificarLogin();
    echo "../../core/config.php cargado correctamente<br>";
} else {
    die("ERROR: ../core/config.php NO existe");
}


if (isset($conn)) {
    echo "Variable \$conn existe<br>";
    if ($conn->connect_error) {
        die("ERROR de conexión: " . $conn->connect_error);
    }
    echo "Conexión a la base de datos OK<br>";
} else {
    die("ERROR: Variable \$conn no está definida");
}


$tables = ['picking', 'mkp', 'general'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Tabla '$table' existe<br>";
        
        
        $count = $conn->query("SELECT COUNT(*) as total FROM $table");
        $row = $count->fetch_assoc();
        echo "&nbsp;&nbsp;- Registros: " . $row['total'] . "<br>";
    } else {
        echo "✗ Tabla '$table' NO existe<br>";
    }
}

echo "<br>Todo OK!";
?>