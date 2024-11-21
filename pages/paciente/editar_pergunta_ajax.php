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
    if (empty($_POST['id']) || empty($_POST['titulo']) || empty($_POST['descricao'])) {
        returnError("Todos os campos são obrigatórios");
    }

    $pdo = getConnection();
    
    // Verifica se a pergunta pertence ao paciente e não foi respondida
    $stmt = $pdo->prepare("
        SELECT id, titulo, descricao, id_medico 
        FROM perguntas 
        WHERE id = ? AND criado_por = ? AND resposta IS NULL
    ");
    $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pergunta) {
        returnError("Pergunta não encontrada ou não pode ser editada");
    }

    // Prepara os dados
    $data = [
        'id' => $_POST['id'],
        'titulo' => trim($_POST['titulo']),
        'descricao' => trim($_POST['descricao']),
        'id_medico' => !empty($_POST['id_medico']) ? $_POST['id_medico'] : null,
        'criado_por' => $_SESSION['user_id']
    ];

    error_log("Dados para atualização: " . print_r($data, true));

    // Atualiza a pergunta
    $sql = "UPDATE perguntas 
            SET titulo = :titulo, 
                descricao = :descricao, 
                id_medico = :id_medico,
                data_atualizacao = NOW()
            WHERE id = :id AND criado_por = :criado_por";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($data);

    if (!$result) {
        $error = $stmt->errorInfo();
        error_log("Erro SQL: " . print_r($error, true));
        returnError("Erro ao atualizar a pergunta: " . $error[2]);
    }

    if ($stmt->rowCount() === 0) {
        returnError("Nenhuma alteração foi realizada");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Pergunta atualizada com sucesso!'
    ]);

} catch (PDOException $e) {
    error_log("Erro PDO: " . $e->getMessage());
    returnError("Erro no banco de dados: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    returnError($e->getMessage());
}
