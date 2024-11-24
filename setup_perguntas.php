<?php
require_once __DIR__ . '/config/database.php';

try {
    $conn = getConnection();
    
    // Lê o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/sql/criar_tabela_perguntas.sql');
    
    // Executa o SQL
    $conn->exec($sql);
    
    echo "Tabela perguntas criada com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
