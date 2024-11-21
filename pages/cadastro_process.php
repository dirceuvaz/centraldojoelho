<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

try {
    $pdo = getConnection();

    // Validar dados
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $data_cirurgia = filter_input(INPUT_POST, 'data_cirurgia', FILTER_SANITIZE_STRING);
    $medico = filter_input(INPUT_POST, 'medico', FILTER_SANITIZE_STRING);
    $fisioterapeuta = filter_input(INPUT_POST, 'fisioterapeuta', FILTER_SANITIZE_STRING);
    $problema = filter_input(INPUT_POST, 'problema', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    $aceito_termos = isset($_POST['aceito_termos']);

    // Validações
    if (!$nome || !$cpf || !$email || !$data_cirurgia || !$medico || !$fisioterapeuta || !$problema || !$senha || !$confirma_senha) {
        throw new Exception('Todos os campos são obrigatórios');
    }

    if ($senha !== $confirma_senha) {
        throw new Exception('As senhas não conferem');
    }

    if (!$aceito_termos) {
        throw new Exception('Você precisa aceitar os termos de uso');
    }

    // Remover formatação do CPF
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Este email já está cadastrado');
    }

    // Verificar se CPF já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        throw new Exception('Este CPF já está cadastrado');
    }

    // Iniciar transação
    $pdo->beginTransaction();
    $transaction_started = true;

    try {
        // Inserir usuário
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, cpf, senha, tipo_usuario, status)
            VALUES (?, ?, ?, ?, 'paciente', 'pendente')
        ");
        $stmt->execute([
            $nome,
            $email,
            $cpf,
            password_hash($senha, PASSWORD_DEFAULT)
        ]);

        $id_usuario = $pdo->lastInsertId();

        // Inserir dados do paciente
        $stmt = $pdo->prepare("
            INSERT INTO pacientes (
                id_usuario, data_cirurgia, medico, fisioterapeuta, 
                problema, status, data_cadastro
            )
            VALUES (?, ?, ?, ?, ?, 'pendente', NOW())
        ");
        $stmt->execute([
            $id_usuario,
            $data_cirurgia,
            $medico,
            $fisioterapeuta,
            $problema
        ]);

        // Confirmar transação
        $pdo->commit();
        $transaction_started = false;

        // Redirecionar com mensagem de sucesso
        header('Location: ../index.php?cadastro=sucesso');
        exit;

    } catch (Exception $e) {
        if ($transaction_started) {
            $pdo->rollBack();
        }
        throw $e;
    }

} catch (Exception $e) {
    $_SESSION['erro_cadastro'] = $e->getMessage();
    header('Location: cadastro.php?erro=' . urlencode($e->getMessage()));
    exit;
}
