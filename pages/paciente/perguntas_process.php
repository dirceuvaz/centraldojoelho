<?php
session_start();

// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

// Verifica se todos os campos necessários foram enviados
if (!isset($_POST['pergunta']) || !isset($_POST['id_medico'])) {
    $_SESSION['error'] = "Todos os campos são obrigatórios.";
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

$pergunta = trim($_POST['pergunta']);
$id_medico = (int)$_POST['id_medico'];

// Validações básicas
if (empty($pergunta)) {
    $_SESSION['error'] = "A pergunta não pode estar vazia.";
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

if (strlen($pergunta) > 5000) {
    $_SESSION['error'] = "A pergunta não pode ter mais de 5000 caracteres.";
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    // Verifica se o médico existe e está ativo
    $stmt = $pdo->prepare("
        SELECT id 
        FROM usuarios 
        WHERE id = ? AND tipo_usuario = 'medico' AND status = 'ativo'
    ");
    $stmt->execute([$id_medico]);
    $medico = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medico) {
        throw new Exception("Médico não encontrado ou inativo.");
    }

    // Insere a nova pergunta
    $stmt = $pdo->prepare("
        INSERT INTO perguntas (id_paciente, id_medico, pergunta, status, data_criacao, criado_por)
        VALUES (?, ?, ?, 'pendente', NOW(), ?)
    ");
    
    $result = $stmt->execute([$_SESSION['user_id'], $id_medico, $pergunta, $_SESSION['user_id']]);
    if (!$result) {
        throw new Exception("Não foi possível salvar a pergunta.");
    }

    $pdo->commit();
    $_SESSION['success'] = "Pergunta enviada com sucesso!";
    header('Location: index.php?page=paciente/perguntas&status=pendentes');
    exit;

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Erro ao processar pergunta: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error'] = "Erro ao processar sua pergunta: " . $e->getMessage();
    header('Location: index.php?page=paciente/perguntas');
    exit;
}
