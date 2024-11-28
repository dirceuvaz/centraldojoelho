<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pergunta_id = $_POST['perguntaId'] ?? null;
    $pergunta_texto = $_POST['pergunta'] ?? null;
    $resposta = $_POST['resposta'] ?? null;
    $momento = $_POST['momento'] ?? null;

    if (!$pergunta_texto) {
        $_SESSION['error'] = "A pergunta é obrigatória.";
        header('Location: index.php?page=medico/perguntas');
        exit;
    }

    try {
        $conn = getConnection();
        
        if ($pergunta_id) {
            // Atualizar pergunta existente
            $stmt = $conn->prepare("
                UPDATE perguntas 
                SET pergunta = ?, 
                    resposta = ?, 
                    id_momento = ?,
                    data_resposta = CASE 
                        WHEN resposta IS NULL AND ? IS NOT NULL THEN NOW()
                        ELSE data_resposta 
                    END
                WHERE id = ?
            ");
            $stmt->execute([$pergunta_texto, $resposta ?: null, $momento ?: null, $resposta, $pergunta_id]);
            $_SESSION['success'] = "Pergunta atualizada com sucesso!";
        } else {
            // Inserir nova pergunta
            $stmt = $conn->prepare("
                INSERT INTO perguntas (pergunta, resposta, id_momento, data_criacao, data_resposta) 
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([$pergunta_texto, $resposta ?: null, $momento ?: null, $resposta ? date('Y-m-d H:i:s') : null]);
            $_SESSION['success'] = "Pergunta criada com sucesso!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao salvar pergunta: " . $e->getMessage();
    }
}

header('Location: index.php?page=medico/perguntas');
exit;
