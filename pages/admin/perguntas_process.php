<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn = getConnection();

    try {
        // Primeiro, verifica se a pergunta existe
        $stmt = $conn->prepare("SELECT id FROM perguntas WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            // Exclui a pergunta
            $stmt = $conn->prepare("DELETE FROM perguntas WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: index.php?page=admin/perguntas&msg=Pergunta_deletada');
            exit;
        } else {
            // Pergunta não encontrada
            header('Location: index.php?page=admin/perguntas&error=Pergunta_nao_encontrada');
            exit;
        }
    } catch (PDOException $e) {
        // Erro ao excluir
        header('Location: index.php?page=admin/perguntas&error=Erro_ao_excluir');
        exit;
    }
} else {
    // Ação inválida
    header('Location: index.php?page=admin/perguntas');
    exit;
}
?>
