<?php
// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    
    // Busca o total de perguntas pendentes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM perguntas
        WHERE status = 'pendente'
        AND (id_medico = ? OR id_medico IS NULL)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    header('Content-Type: application/json');
    echo json_encode(['total' => (int)$result['total']]);
} catch (PDOException $e) {
    error_log("Erro ao buscar total de perguntas pendentes: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar perguntas pendentes']);
}
