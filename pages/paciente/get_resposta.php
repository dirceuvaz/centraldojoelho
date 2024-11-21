<?php
// Desativa a exibição de erros para o navegador
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Garante que a saída será sempre JSON
header('Content-Type: application/json');

session_start();

// Debug
error_log("GET data: " . print_r($_GET, true));
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

    // Validação do ID
    if (empty($_GET['id'])) {
        returnError("ID da pergunta não fornecido");
    }

    $pdo = getConnection();
    
    // Debug - Verifica a estrutura da tabela
    $stmt = $pdo->query("SHOW COLUMNS FROM perguntas");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Estrutura da tabela perguntas: " . print_r($colunas, true));
    
    // Busca a pergunta e sua resposta
    $sql = "
        SELECT p.*, 
               u.nome as nome_medico,
               r.resposta as texto_resposta,
               r.data_resposta
        FROM perguntas p
        LEFT JOIN usuarios u ON u.id = p.id_medico
        LEFT JOIN respostas r ON r.id_pergunta = p.id
        WHERE p.id = ? AND p.id_paciente = ?
    ";
    error_log("SQL Query: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Pergunta encontrada: " . print_r($pergunta, true));

    if (!$pergunta) {
        returnError("Pergunta não encontrada");
    }

    // Verifica apenas o status da pergunta
    if ($pergunta['status'] !== 'respondida') {
        returnError("Esta pergunta ainda não foi respondida pelo médico");
    }

    // Formata a data da resposta
    $data_resposta = !empty($pergunta['data_resposta']) 
        ? date('d/m/Y H:i', strtotime($pergunta['data_resposta']))
        : '';

    // Verifica se a resposta está vazia
    if (empty($pergunta['texto_resposta'])) {
        error_log("AVISO: Pergunta marcada como respondida mas sem resposta: " . print_r($pergunta, true));
        returnError("A resposta está vazia, mesmo com status respondida. Por favor, contate o suporte.");
    }

    $response = [
        'success' => true,
        'data' => [
            'titulo' => $pergunta['titulo'],
            'descricao' => $pergunta['descricao'],
            'resposta' => $pergunta['texto_resposta'],
            'data_resposta' => $data_resposta,
            'nome_medico' => $pergunta['nome_medico'] ?? 'Não informado'
        ]
    ];

    error_log("Resposta JSON: " . json_encode($response));
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Erro PDO: " . $e->getMessage());
    returnError("Erro no banco de dados: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    returnError($e->getMessage());
}
