<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

$id_pergunta = filter_input(INPUT_POST, 'id_pergunta', FILTER_VALIDATE_INT);
$resposta = trim(filter_input(INPUT_POST, 'resposta', FILTER_SANITIZE_SPECIAL_CHARS));

if (!$id_pergunta || empty($resposta)) {
    $_SESSION['error'] = "Todos os campos são obrigatórios.";
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

try {
    $pdo = getConnection();

    // Verifica se a pergunta existe e pertence ao paciente
    $stmt = $pdo->prepare("
        SELECT id, id_paciente, status 
        FROM perguntas 
        WHERE id = ? AND id_paciente = ?
    ");
    $stmt->execute([$id_pergunta, $_SESSION['user_id']]);
    $pergunta = $stmt->fetch();

    if (!$pergunta) {
        $_SESSION['error'] = "Pergunta não encontrada ou você não tem permissão para respondê-la.";
        header('Location: index.php?page=paciente/perguntas');
        exit;
    }

    // Atualiza a pergunta com a resposta do paciente
    $stmt = $pdo->prepare("
        UPDATE perguntas 
        SET resposta_paciente = ?, 
            data_resposta_paciente = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$resposta, $id_pergunta]);

    $_SESSION['success'] = "Resposta enviada com sucesso!";
} catch (PDOException $e) {
    error_log("Erro ao responder pergunta: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao enviar resposta. Por favor, tente novamente.";
}

header('Location: index.php?page=paciente/perguntas');
exit;
