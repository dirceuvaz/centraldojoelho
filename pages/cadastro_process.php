<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/reabilitacao_helper.php';

// Função para sanitizar strings
function sanitize_string($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    $pdo = getConnection();
    $transaction_started = false;

    // Validar dados comuns
    $tipo_usuario = sanitize_string($_POST['tipo_usuario'] ?? '');
    $nome = sanitize_string($_POST['nome'] ?? '');
    $cpf = sanitize_string($_POST['cpf'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    $aceito_termos = isset($_POST['aceito_termos']);

    // Validações básicas
    if (!$tipo_usuario || !$nome || !$cpf || !$email || !$senha || !$confirma_senha) {
        throw new Exception('Todos os campos são obrigatórios');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
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
        $status = 'pendente'; // Todos os usuários começam como pendentes

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
            $crm = sanitize_string($_POST['crm'] ?? '');
            $especialidade = sanitize_string($_POST['especialidade'] ?? '');

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

        } elseif ($tipo_usuario === 'paciente') {
            // Dados específicos do paciente
            $data_cirurgia = sanitize_string($_POST['data_cirurgia'] ?? '');
            $medico = sanitize_string($_POST['medico'] ?? '');
            $fisioterapeuta = sanitize_string($_POST['fisioterapeuta'] ?? '');
            $problema = sanitize_string($_POST['problema'] ?? '');

            // Validar dados do paciente
            if (!$data_cirurgia || !$medico || !$problema) {
                throw new Exception('Data da cirurgia, médico e problema são obrigatórios');
            }

            // Buscar ID do médico
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome = ? AND tipo_usuario = 'medico'");
            $stmt->execute([$medico]);
            $id_medico = $stmt->fetchColumn();

            if (!$id_medico) {
                throw new Exception('Médico não encontrado');
            }

            // Determinar etapa inicial de reabilitação
            $reabHelper = new ReabilitacaoHelper($pdo);
            $etapa = $reabHelper->determinarEtapaReabilitacao($data_cirurgia);

            // Inserir paciente com a etapa inicial
            $stmt = $pdo->prepare("
                INSERT INTO pacientes (id_usuario, medico, data_cirurgia, fisioterapeuta, problema, status, data_cadastro)
                VALUES (?, ?, ?, ?, ?, 'ativo', NOW())
            ");
            $stmt->execute([
                $id_usuario,
                $id_medico,
                $data_cirurgia,
                $fisioterapeuta,
                $problema
            ]);

            // Obter o ID do paciente recém-inserido
            $id_paciente = $pdo->lastInsertId();

            // Inserir a cirurgia
            $stmt = $pdo->prepare("
                INSERT INTO cirurgias (id_paciente, id_medico, tipo, data_cirurgia)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id_paciente,
                $id_medico,
                $problema, // Usando o problema como tipo de cirurgia
                $data_cirurgia
            ]);
        }

        $pdo->commit();
        $transaction_started = false;
        
        $_SESSION['cadastro'] = 'pendente';
        $_SESSION['tipo_usuario'] = $tipo_usuario;
        $_SESSION['mensagem'] = 'Cadastro realizado com sucesso! ' . 
            ($tipo_usuario === 'medico' ? 
             'Aguarde a liberação pela administração do sistema.' : 
             'Aguarde a liberação pelo seu médico responsável.');
        header('Location: index.php?page=login');
        exit;

    } catch (Exception $e) {
        if ($transaction_started) {
            $pdo->rollBack();
        }
        throw $e;
    }

} catch (Exception $e) {
    if ($transaction_started) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php?page=cadastro');
    exit;
}
