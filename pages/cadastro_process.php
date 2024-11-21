<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

try {
    $pdo = getConnection();

    // Validar dados comuns
    $tipo_usuario = filter_input(INPUT_POST, 'tipo_usuario', FILTER_SANITIZE_STRING);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    $aceito_termos = isset($_POST['aceito_termos']);

    // Validações básicas
    if (!$tipo_usuario || !$nome || !$cpf || !$email || !$senha || !$confirma_senha) {
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
        // Status inicial baseado no tipo de usuário
        $status = $tipo_usuario === 'medico' ? 'pendente' : 'ativo';

        // Inserir usuário
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, cpf, senha, tipo_usuario, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nome,
            $email,
            $cpf,
            password_hash($senha, PASSWORD_DEFAULT),
            $tipo_usuario,
            $status
        ]);

        $id_usuario = $pdo->lastInsertId();

        if ($tipo_usuario === 'medico') {
            // Dados específicos do médico
            $crm = filter_input(INPUT_POST, 'crm', FILTER_SANITIZE_STRING);
            $especialidade = filter_input(INPUT_POST, 'especialidade', FILTER_SANITIZE_STRING);

            if (!$crm || !$especialidade) {
                throw new Exception('CRM e especialidade são obrigatórios para médicos');
            }

            $stmt = $pdo->prepare("
                INSERT INTO medicos (id_usuario, crm, especialidade, status, data_cadastro)
                VALUES (?, ?, ?, 'pendente', NOW())
            ");
            $stmt->execute([
                $id_usuario,
                $crm,
                $especialidade
            ]);

        } else {
            // Dados específicos do paciente
            $data_cirurgia = filter_input(INPUT_POST, 'data_cirurgia', FILTER_SANITIZE_STRING);
            $medico = filter_input(INPUT_POST, 'medico', FILTER_SANITIZE_STRING);
            $fisioterapeuta = filter_input(INPUT_POST, 'fisioterapeuta', FILTER_SANITIZE_STRING);
            $problema = filter_input(INPUT_POST, 'problema', FILTER_SANITIZE_STRING);

            if (!$data_cirurgia || !$medico || !$fisioterapeuta || !$problema) {
                throw new Exception('Todos os campos do paciente são obrigatórios');
            }

            $stmt = $pdo->prepare("
                INSERT INTO pacientes (
                    id_usuario, data_cirurgia, medico, fisioterapeuta, 
                    problema, status, data_cadastro
                )
                VALUES (?, ?, ?, ?, ?, 'ativo', NOW())
            ");
            $stmt->execute([
                $id_usuario,
                $data_cirurgia,
                $medico,
                $fisioterapeuta,
                $problema
            ]);
        }

        // Confirmar transação
        $pdo->commit();
        $transaction_started = false;

        // Redirecionar com mensagem apropriada
        if ($tipo_usuario === 'medico') {
            header('Location: ../index.php?cadastro=pendente');
        } else {
            header('Location: ../index.php?cadastro=sucesso');
        }
        exit;

    } catch (Exception $e) {
        if ($transaction_started) {
            $pdo->rollBack();
        }
        throw $e;
    }

} catch (Exception $e) {
    header('Location: ../index.php?page=cadastro&erro=' . urlencode($e->getMessage()));
    exit;
}
