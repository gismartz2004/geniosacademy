<?php
require 'includes/config.php';

$stmt = $pdo->query("SELECT NOW() AS fecha");
$row = $stmt->fetch();
echo "✅ Conexión correcta. Fecha desde MySQL: " . $row['fecha'];
