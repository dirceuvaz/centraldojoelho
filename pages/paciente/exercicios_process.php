<?php
// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=paciente/exercicios');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    
    // Validação dos dados recebidos
    if (empty($_POST['exercicio_id'])) {
        throw new Exception('ID do exercício não fornecido.');
    }

    // Verifica se o exercício pertence ao paciente
    $stmt = $pdo->prepare("
        SELECT id, status 
        FROM exercicios 
        WHERE id = ? AND id_paciente = ?
    ");
    $stmt->execute([$_POST['exercicio_id'], $_SESSION['user_id']]);
    $exercicio = $stmt->fetch();

    if (!$exercicio) {
        throw new Exception('Exercício não encontrado ou não pertence ao paciente.');
    }

    if ($exercicio['status'] === 'completo') {
        throw new Exception('Este exercício já foi marcado como concluído.');
    }

    // Atualiza o status do exercício
    $stmt = $pdo->prepare("
        UPDATE exercicios 
        SET status = 'completo',
            data_conclusao = CURRENT_TIMESTAMP
        WHERE id = ? AND id_paciente = ?
    ");
    $stmt->execute([$_POST['exercicio_id'], $_SESSION['user_id']]);

    $_SESSION['success'] = 'Exercício marcado como concluído com sucesso!';

} catch (Exception $e) {
    error_log('Erro ao processar exercício: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

// Redireciona de volta para a página de exercícios
header('Location: index.php?page=paciente/exercicios');
exit;
