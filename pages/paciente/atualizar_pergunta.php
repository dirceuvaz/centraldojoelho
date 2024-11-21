<?php
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    
    // Validação dos dados
    if (empty($_POST['id']) || empty($_POST['titulo']) || empty($_POST['descricao'])) {
        throw new Exception("Todos os campos são obrigatórios");
    }

    // Verifica se a pergunta pertence ao paciente
    $stmt = $pdo->prepare("SELECT id FROM perguntas WHERE id = ? AND criado_por = ? AND resposta IS NULL");
    $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("Pergunta não encontrada ou não pode ser editada");
    }

    // Prepara os dados
    $data = [
        'id' => $_POST['id'],
        'titulo' => $_POST['titulo'],
        'descricao' => $_POST['descricao'],
        'id_medico' => !empty($_POST['id_medico']) ? $_POST['id_medico'] : null
    ];

    // Atualiza a pergunta
    $sql = "UPDATE perguntas 
            SET titulo = :titulo, 
                descricao = :descricao, 
                id_medico = :id_medico 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);

    header('Location: index.php?page=paciente/perguntas&success=1');
    exit;

} catch (Exception $e) {
    error_log("Erro ao atualizar pergunta: " . $e->getMessage());
    header('Location: index.php?page=paciente/perguntas&error=' . urlencode($e->getMessage()));
    exit;
}
