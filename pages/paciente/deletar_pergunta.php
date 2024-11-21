<?php
// Não precisa do session_start pois já é iniciado no index.php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Você precisa estar logado.';
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

// Verifica se recebeu o ID
if (!isset($_POST['id'])) {
    $_SESSION['error'] = 'ID da pergunta não fornecido.';
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

try {
    $pdo = getConnection();
    
    // Deleta a pergunta
    $stmt = $pdo->prepare("DELETE FROM perguntas WHERE id = ? AND criado_por = ?");
    $stmt->execute([
        $_POST['id'],
        $_SESSION['user_id']
    ]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Pergunta excluída com sucesso!';
    } else {
        $_SESSION['error'] = 'Pergunta não encontrada ou você não tem permissão para excluí-la.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao excluir a pergunta.';
}

// Redireciona de volta
header('Location: index.php?page=paciente/perguntas');
exit;
