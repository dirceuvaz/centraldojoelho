<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pergunta_id = $_POST['perguntaId'] ?? null;

    if (!$pergunta_id) {
        $_SESSION['error'] = "ID da pergunta não fornecido.";
        header('Location: index.php?page=medico/perguntas');
        exit;
    }

    try {
        $conn = getConnection();
        
        // Excluir a pergunta
        $stmt = $conn->prepare("DELETE FROM perguntas WHERE id = ?");
        $stmt->execute([$pergunta_id]);

        $_SESSION['success'] = "Pergunta excluída com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao excluir pergunta: " . $e->getMessage();
    }
}

header('Location: index.php?page=medico/perguntas');
exit;
