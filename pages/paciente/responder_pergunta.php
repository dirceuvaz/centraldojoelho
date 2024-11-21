<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verifica se é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    $_SESSION['error'] = 'Acesso não autorizado';
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

// Verifica dados
$id = isset($_POST['id']) ? $_POST['id'] : '';
$resposta = isset($_POST['resposta']) ? $_POST['resposta'] : '';

if (empty($id) || empty($resposta)) {
    $_SESSION['error'] = 'Dados incompletos';
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

try {
    $pdo = getConnection();
    
    // Atualiza a pergunta
    $sql = "UPDATE perguntas SET resposta = ?, respondido_por = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([$resposta, $_SESSION['user_id'], $id]);
    
    if ($ok) {
        $_SESSION['success'] = 'Resposta salva com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao salvar resposta';
    }
} catch (PDOException $e) {
    error_log('Erro ao responder: ' . $e->getMessage());
    $_SESSION['error'] = 'Erro ao processar resposta';
}

header('Location: index.php?page=paciente/perguntas');
exit;
