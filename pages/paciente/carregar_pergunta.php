<?php
header('Content-Type: application/json');

// Não precisa iniciar a sessão aqui pois já é iniciada no index.php
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    if (empty($_GET['id'])) {
        throw new Exception('ID da pergunta não fornecido');
    }

    $pdo = getConnection();
    
    // Busca a pergunta que pertence ao paciente e ainda não foi respondida
    $sql = "SELECT p.*, m.nome as nome_medico 
            FROM perguntas p 
            LEFT JOIN usuarios m ON p.id_medico = m.id 
            WHERE p.id = ? AND p.criado_por = ? AND p.resposta IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pergunta) {
        throw new Exception('Pergunta não encontrada ou não pode ser editada');
    }

    // Busca lista de médicos para o select
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'medico' AND status = 'ativo' ORDER BY nome");
    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'pergunta' => $pergunta,
        'medicos' => $medicos
    ]);

} catch (Exception $e) {
    error_log("Erro ao carregar pergunta: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
