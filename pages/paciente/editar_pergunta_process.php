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
$titulo = trim(filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS));
$descricao = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS));
$id_medico = filter_input(INPUT_POST, 'id_medico', FILTER_VALIDATE_INT) ?: null;

if (!$id_pergunta || empty($titulo) || empty($descricao)) {
    $_SESSION['error'] = "Todos os campos obrigatórios devem ser preenchidos.";
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

try {
    $pdo = getConnection();

    // Verifica se a pergunta existe e pertence ao paciente
    $stmt = $pdo->prepare("
        SELECT id, criado_por, status 
        FROM perguntas 
        WHERE id = ? AND criado_por = ? AND status = 'pendente'
    ");
    $stmt->execute([$id_pergunta, $_SESSION['user_id']]);
    $pergunta = $stmt->fetch();

    if (!$pergunta) {
        $_SESSION['error'] = "Pergunta não encontrada ou você não tem permissão para editá-la.";
        header('Location: index.php?page=paciente/perguntas');
        exit;
    }

    // Se um médico foi selecionado, verifica se ele existe e está ativo
    if ($id_medico) {
        $stmt = $pdo->prepare("
            SELECT u.id 
            FROM usuarios u
            JOIN medicos m ON m.id_usuario = u.id
            WHERE u.id = ? AND u.status = 'ativo' AND m.status = 'ativo'
        ");
        $stmt->execute([$id_medico]);
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "Médico selecionado não está disponível.";
            header('Location: index.php?page=paciente/perguntas');
            exit;
        }
    }

    // Atualiza a pergunta
    $stmt = $pdo->prepare("
        UPDATE perguntas 
        SET titulo = ?,
            descricao = ?,
            id_medico = ?,
            data_atualizacao = NOW()
        WHERE id = ? AND criado_por = ?
    ");
    $stmt->execute([$titulo, $descricao, $id_medico, $id_pergunta, $_SESSION['user_id']]);

    $_SESSION['success'] = "Pergunta atualizada com sucesso!";
} catch (PDOException $e) {
    error_log("Erro ao editar pergunta: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao editar pergunta. Por favor, tente novamente.";
}

header('Location: index.php?page=paciente/perguntas');
exit;
