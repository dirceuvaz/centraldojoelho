<?php
require_once __DIR__ . '/config/database.php';

$conn = getConnection();
$stmt = $conn->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Tabelas no banco de dados:\n\n";
foreach ($tables as $table) {
    echo "- $table\n";
    
    // Mostrar estrutura da tabela
    $stmt = $conn->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Colunas:\n";
    foreach ($columns as $column) {
        echo "    {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
}
