<?php
require_once __DIR__ . '/config/database.php';

try {
    $conn = getConnection();
    
    // Lê o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/sql/atualizar_reabilitacao_tipo_campo.sql');
    
    // Executa o SQL
    $conn->exec($sql);
    
    echo "Tabela 'reabilitacao' atualizada com sucesso!";
    
} catch(PDOException $e) {
    echo "Erro ao atualizar tabela: " . $e->getMessage();
}
