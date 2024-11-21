<?php
session_start();

// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: ../../index.php?page=login');
    exit;
}

// Verifica se o ID foi fornecido
if (!isset($_POST['id'])) {
    $_SESSION['error'] = "ID da pergunta não fornecido";
    header('Location: ../../index.php?page=paciente/perguntas');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    
    // Verifica se a pergunta pertence ao paciente e não está respondida
    $stmt = $pdo->prepare("
        SELECT id, status 
        FROM perguntas 
        WHERE id = ? AND id_paciente = ? AND status = 'pendente'
    ");
    $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
    $pergunta = $stmt->fetch();

    if (!$pergunta) {
        $_SESSION['error'] = "Pergunta não encontrada ou não pode ser excluída";
        header('Location: ../../index.php?page=paciente/perguntas');
        exit;
    }

    // Exclui a pergunta
    $stmt = $pdo->prepare("DELETE FROM perguntas WHERE id = ?");
    $stmt->execute([$_POST['id']]);

    $_SESSION['success'] = "Pergunta excluída com sucesso!";

} catch (PDOException $e) {
    error_log("Erro ao excluir pergunta: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao excluir a pergunta. Por favor, tente novamente.";
}

header('Location: ../../index.php?page=paciente/perguntas');
exit;
