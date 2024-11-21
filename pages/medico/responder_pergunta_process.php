<?php
session_start();

// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=medico/perguntas');
    exit;
}

// Verifica se todos os campos necessários foram enviados
if (!isset($_POST['id_pergunta']) || !isset($_POST['resposta'])) {
    $_SESSION['error'] = "Todos os campos são obrigatórios.";
    header('Location: index.php?page=medico/perguntas');
    exit;
}

$id_pergunta = (int)$_POST['id_pergunta'];
$resposta = trim($_POST['resposta']);

// Validações básicas
if (empty($resposta)) {
    $_SESSION['error'] = "A resposta não pode estar vazia.";
    header("Location: index.php?page=medico/responder_pergunta&id=$id_pergunta");
    exit;
}

if (strlen($resposta) > 5000) {
    $_SESSION['error'] = "A resposta não pode ter mais de 5000 caracteres.";
    header("Location: index.php?page=medico/responder_pergunta&id=$id_pergunta");
    exit;
}

try {
    $pdo = getConnection();
    
    // Log para debug
    error_log("Iniciando processamento de resposta - ID Pergunta: " . $id_pergunta);
    error_log("ID Médico: " . $_SESSION['user_id']);
    
    $pdo->beginTransaction();

    // Verifica se a pergunta existe e pode ser respondida
    $stmt = $pdo->prepare("
        SELECT id, status, id_medico 
        FROM perguntas 
        WHERE id = ?
    ");
    $stmt->execute([$id_pergunta]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pergunta) {
        throw new Exception("Pergunta não encontrada.");
    }

    if ($pergunta['status'] === 'respondida') {
        throw new Exception("Esta pergunta já foi respondida.");
    }

    // Primeiro atualiza o status da pergunta
    $stmt = $pdo->prepare("
        UPDATE perguntas 
        SET status = 'respondida', 
            data_resposta = NOW()
        WHERE id = ? AND status = 'pendente'
    ");
    
    $result = $stmt->execute([$id_pergunta]);
    if (!$result || $stmt->rowCount() === 0) {
        throw new Exception("Não foi possível atualizar o status da pergunta.");
    }

    // Depois insere a resposta
    $stmt = $pdo->prepare("
        INSERT INTO respostas (id_pergunta, id_usuario, resposta, data_resposta)
        VALUES (?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([$id_pergunta, $_SESSION['user_id'], $resposta]);
    if (!$result) {
        throw new Exception("Não foi possível salvar a resposta.");
    }

    $pdo->commit();
    error_log("Resposta processada com sucesso!");
    $_SESSION['success'] = "Resposta enviada com sucesso!";
    header('Location: index.php?page=medico/perguntas&status=respondidas');
    exit;

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Erro ao processar resposta: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error'] = "Erro ao processar sua resposta: " . $e->getMessage();
    header("Location: index.php?page=medico/responder_pergunta&id=$id_pergunta");
    exit;
}
exit;
