<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Buscar dados básicos do usuário
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.email, u.tipo_usuario,
               m.crm, m.especialidade
        FROM usuarios u
        LEFT JOIN medicos m ON u.id = m.id_usuario
        WHERE u.id = ?
    ");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit;
    }

    echo json_encode($usuario);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Erro ao buscar dados do usuário'
    ]);
}
