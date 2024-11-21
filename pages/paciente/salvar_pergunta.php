<?php
// Desativa a exibição de erros para o navegador
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Garante que a saída será sempre JSON
header('Content-Type: application/json');

session_start();

// Debug
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// Função para retornar erro em JSON
function returnError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    returnError('Acesso não autorizado', 403);
}

try {
    require_once __DIR__ . '/../../config/database.php';

    // Validação dos dados
    if (empty($_POST['titulo']) || empty($_POST['descricao'])) {
        returnError("Título e descrição são obrigatórios");
    }

    $pdo = getConnection();
    
    // Prepara os dados
    $data = [
        'titulo' => trim($_POST['titulo']),
        'descricao' => trim($_POST['descricao']),
        'id_medico' => !empty($_POST['id_medico']) ? $_POST['id_medico'] : null,
        'id_paciente' => $_SESSION['user_id'], // Adiciona o id_paciente
        'criado_por' => $_SESSION['user_id'],
        'status' => 'pendente'
    ];

    error_log("Dados para inserção: " . print_r($data, true));

    // Insere a pergunta
    $sql = "INSERT INTO perguntas (titulo, descricao, id_medico, id_paciente, criado_por, status, data_criacao) 
            VALUES (:titulo, :descricao, :id_medico, :id_paciente, :criado_por, :status, NOW())";
    
    error_log("SQL: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($data);

    if (!$result) {
        $error = $stmt->errorInfo();
        error_log("Erro SQL: " . print_r($error, true));
        returnError("Erro ao salvar a pergunta: " . $error[2]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Pergunta salva com sucesso!',
        'id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    error_log("Erro PDO: " . $e->getMessage());
    returnError("Erro no banco de dados: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    returnError($e->getMessage());
}
