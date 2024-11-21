<?php
// Verifica se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();

    // Exclusão de pergunta
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM perguntas WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $_SESSION['success'] = 'Pergunta excluída com sucesso!';
        header('Location: index.php?page=admin/perguntas');
        exit;
    }

    // Verifica se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=admin/perguntas');
        exit;
    }

    // Validação dos dados
    if (empty($_POST['titulo']) || empty($_POST['id_paciente'])) {
        throw new Exception('Por favor, preencha todos os campos obrigatórios.');
    }

    // Limita o tamanho do título
    if (strlen($_POST['titulo']) > 255) {
        throw new Exception('O título não pode ter mais que 255 caracteres.');
    }

    // Verifica se o paciente existe e é válido
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo_usuario = 'paciente' AND status = 'ativo'");
    $stmt->execute([$_POST['id_paciente']]);
    if (!$stmt->fetch()) {
        throw new Exception('Paciente inválido ou não encontrado.');
    }

    // Verifica o tipo de ação (nova ou edição)
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        // Edição de pergunta existente
        $stmt = $pdo->prepare("
            UPDATE perguntas 
            SET titulo = ?,
                descricao = ?,
                id_paciente = ?,
                data_atualizacao = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            trim($_POST['titulo']),
            trim($_POST['descricao']),
            $_POST['id_paciente'],
            $_POST['id']
        ]);

        $_SESSION['success'] = 'Pergunta atualizada com sucesso!';
    } else {
        // Nova pergunta
        $stmt = $pdo->prepare("
            INSERT INTO perguntas (titulo, descricao, id_paciente, criado_por)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            trim($_POST['titulo']),
            trim($_POST['descricao']),
            $_POST['id_paciente'],
            $_SESSION['user_id']
        ]);

        $_SESSION['success'] = 'Pergunta criada com sucesso!';
    }

} catch (Exception $e) {
    error_log('Erro ao processar pergunta: ' . $e->getMessage());
    $_SESSION['error'] = 'Erro ao processar pergunta: ' . $e->getMessage();
}

// Redireciona de volta para a página de perguntas
header('Location: index.php?page=admin/perguntas');
exit;
