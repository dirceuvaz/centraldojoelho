<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Validações básicas
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $_SESSION['error'] = "Todos os campos são obrigatórios.";
        header('Location: index.php?page=medico/painel');
        exit;
    }
    
    if ($nova_senha !== $confirmar_senha) {
        $_SESSION['error'] = "A nova senha e a confirmação não coincidem.";
        header('Location: index.php?page=medico/painel');
        exit;
    }
    
    try {
        $pdo = getConnection();
        
        // Verifica a senha atual
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $usuario = $stmt->fetch();
        
        if (!$usuario || !password_verify($senha_atual, $usuario['senha'])) {
            $_SESSION['error'] = "Senha atual incorreta.";
            header('Location: index.php?page=medico/painel');
            exit;
        }
        
        // Atualiza a senha
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([$nova_senha_hash, $_SESSION['user_id']]);
        
        $_SESSION['success'] = "Senha alterada com sucesso!";
    } catch (PDOException $e) {
        error_log("Erro ao alterar senha: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao alterar a senha. Tente novamente mais tarde.";
    }
    
    header('Location: index.php?page=medico/painel');
    exit;
}
