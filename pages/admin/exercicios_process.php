<?php
// Verifica se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();

    // Exclusão de exercício
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM exercicios WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $_SESSION['success'] = 'Exercício excluído com sucesso!';
        header('Location: index.php?page=admin/exercicios');
        exit;
    }

    // Verifica se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=admin/exercicios');
        exit;
    }

    // Validação dos dados
    if (empty($_POST['id_paciente']) || empty($_POST['titulo']) || empty($_POST['descricao'])) {
        throw new Exception('Por favor, preencha todos os campos obrigatórios.');
    }

    // Limita o tamanho dos campos
    if (strlen($_POST['titulo']) > 255) {
        throw new Exception('O título não pode ter mais que 255 caracteres.');
    }

    if (strlen($_POST['descricao']) > 2000) {
        throw new Exception('A descrição não pode ter mais que 2000 caracteres.');
    }

    // Validação da URL do vídeo (se fornecida)
    if (!empty($_POST['video_url']) && !filter_var($_POST['video_url'], FILTER_VALIDATE_URL)) {
        throw new Exception('URL do vídeo inválida.');
    }

    // Edição de exercício existente
    if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("
            UPDATE exercicios 
            SET id_paciente = ?,
                titulo = ?,
                descricao = ?,
                video_url = ?,
                status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['id_paciente'],
            trim($_POST['titulo']),
            trim($_POST['descricao']),
            !empty($_POST['video_url']) ? trim($_POST['video_url']) : null,
            $_POST['status'],
            $_POST['id']
        ]);

        $_SESSION['success'] = 'Exercício atualizado com sucesso!';
    }
    // Criação de novo exercício
    else {
        $stmt = $pdo->prepare("
            INSERT INTO exercicios (
                id_paciente,
                titulo,
                descricao,
                video_url,
                status,
                data_criacao
            ) VALUES (
                ?, ?, ?, ?, 'pendente', CURRENT_TIMESTAMP
            )
        ");
        
        $stmt->execute([
            $_POST['id_paciente'],
            trim($_POST['titulo']),
            trim($_POST['descricao']),
            !empty($_POST['video_url']) ? trim($_POST['video_url']) : null
        ]);

        $_SESSION['success'] = 'Exercício criado com sucesso!';
    }

} catch (Exception $e) {
    error_log('Erro ao processar exercício: ' . $e->getMessage());
    $_SESSION['error'] = 'Erro ao processar exercício: ' . $e->getMessage();
}

// Redireciona de volta para a página de exercícios
header('Location: index.php?page=admin/exercicios');
exit;
