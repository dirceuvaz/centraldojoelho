<?php
require_once __DIR__ . '/config/database.php';

try {
    $conn = getConnection();
    
    // Lê o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/sql/criar_tabela_tipo_reabilitacao.sql');
    
    // Executa o SQL
    $conn->exec($sql);
    
    echo "Tabela tipo_reabilitacao criada e populada com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
