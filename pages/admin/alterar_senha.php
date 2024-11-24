<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['nova_senha'])) {
    $conn = getConnection();
    
    try {
        // Hash da nova senha
        $senha_hash = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
        
        // Atualiza a senha do usuário
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([$senha_hash, $_POST['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao alterar a senha.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
}
