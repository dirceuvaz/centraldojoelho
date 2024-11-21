<?php
session_start();

// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Debug
error_log("POST data: " . print_r($_POST, true));

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

// Verifica se todos os campos necessários foram enviados
if (!isset($_POST['id_pergunta']) || !isset($_POST['mensagem'])) {
    $_SESSION['error'] = "Todos os campos são obrigatórios.";
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

$id_pergunta = (int)$_POST['id_pergunta'];
$mensagem = trim($_POST['mensagem']);

// Validações básicas
if (empty($mensagem)) {
    $_SESSION['error'] = "A mensagem não pode estar vazia.";
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

if (strlen($mensagem) > 5000) {
    $_SESSION['error'] = "A mensagem não pode ter mais de 5000 caracteres.";
    header('Location: index.php?page=paciente/perguntas');
    exit;
}

try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    // Verifica se a pergunta existe e pertence ao paciente
    $stmt = $pdo->prepare("
        SELECT id, id_medico 
        FROM perguntas 
        WHERE id = ? AND (id_paciente = ? OR criado_por = ?) AND status = 'respondida'
    ");
    $stmt->execute([$id_pergunta, $_SESSION['user_id'], $_SESSION['user_id']]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pergunta) {
        throw new Exception("Pergunta não encontrada ou você não tem permissão para respondê-la.");
    }

    // Primeiro vamos verificar a estrutura da tabela
    $stmt = $pdo->query("DESCRIBE perguntas");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Estrutura da tabela perguntas: " . print_r($colunas, true));

    // Cria uma nova pergunta vinculada à conversa
    $stmt = $pdo->prepare("
        INSERT INTO perguntas (
            id_paciente,
            id_medico,
            descricao,
            status,
            data_criacao,
            criado_por
        ) VALUES (?, ?, ?, 'pendente', NOW(), ?)
    ");
    
    $result = $stmt->execute([
        $_SESSION['user_id'],
        $pergunta['id_medico'],
        $mensagem,
        $_SESSION['user_id']
    ]);

    if (!$result) {
        throw new Exception("Não foi possível enviar a mensagem.");
    }

    // Pega o ID da nova pergunta
    $nova_pergunta_id = $pdo->lastInsertId();
    error_log("Nova pergunta criada com ID: " . $nova_pergunta_id);

    // Agora vamos tentar atualizar o relacionamento
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM perguntas LIKE '%origem%'");
        $stmt->execute();
        $coluna_origem = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Coluna de origem encontrada: " . print_r($coluna_origem, true));

        if ($coluna_origem) {
            $nome_coluna = $coluna_origem['Field'];
            $stmt = $pdo->prepare("UPDATE perguntas SET $nome_coluna = ? WHERE id = ?");
            $stmt->execute([$id_pergunta, $nova_pergunta_id]);
        }
    } catch (Exception $e) {
        error_log("Erro ao tentar atualizar relacionamento: " . $e->getMessage());
        // Não vamos lançar exceção aqui, pois a pergunta já foi criada
    }

    $pdo->commit();
    $_SESSION['success'] = "Mensagem enviada com sucesso!";
    header('Location: index.php?page=paciente/perguntas&status=pendentes');
    exit;

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Erro ao processar resposta ao médico: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error'] = "Erro ao enviar mensagem: " . $e->getMessage();
    header('Location: index.php?page=paciente/perguntas');
    exit;
}
