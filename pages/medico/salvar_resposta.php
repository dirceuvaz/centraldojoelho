<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pergunta_id = $_POST['perguntaId'] ?? null;
    $resposta = $_POST['resposta'] ?? null;
    $medico_id = $_SESSION['user_id'];

    if (!$pergunta_id || !$resposta) {
        $_SESSION['error'] = "Todos os campos são obrigatórios.";
        header('Location: index.php?page=medico/perguntas');
        exit;
    }

    try {
        $conn = getConnection();
        
        // Verifica se a pergunta pertence a um paciente deste médico
        $stmt = $conn->prepare("
            SELECT p.* 
            FROM perguntas p
            JOIN pacientes pac ON p.id_paciente = pac.id_usuario
            WHERE p.id = ? AND pac.medico = ?
        ");
        $stmt->execute([$pergunta_id, $medico_id]);
        $pergunta = $stmt->fetch();

        if (!$pergunta) {
            $_SESSION['error'] = "Pergunta não encontrada.";
            header('Location: index.php?page=medico/perguntas');
            exit;
        }

        // Salva a resposta
        $stmt = $conn->prepare("
            UPDATE perguntas 
            SET resposta = ?, data_resposta = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$resposta, $pergunta_id]);

        $_SESSION['success'] = "Resposta salva com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao salvar resposta: " . $e->getMessage();
    }
}

header('Location: index.php?page=medico/perguntas');
exit;
