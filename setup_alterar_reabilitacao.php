<?php
require_once __DIR__ . '/config/database.php';

try {
    $conn = getConnection();
    
    // Lê o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/sql/alterar_tabela_reabilitacao.sql');
    
    // Executa o SQL
    $conn->exec($sql);
    
    echo "Coluna 'sequencia' adicionada com sucesso na tabela 'reabilitacao'!";
    
} catch(PDOException $e) {
    echo "Erro ao alterar tabela: " . $e->getMessage();
}
