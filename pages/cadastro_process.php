<?php
session_start();
require_once __DIR__ . '/../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido');
    }

    $pdo = getConnection();
    
    // Coletar dados do formulário
    $tipo_usuario = $_POST['tipo_usuario'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $senha = $_POST['senha'];
    
    // Validações básicas
    if (empty($tipo_usuario) || empty($nome) || empty($email) || empty($cpf) || empty($senha)) {
        throw new Exception('Todos os campos são obrigatórios');
    }
    
    // Verificar email existente
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Email já cadastrado');
    }
    
    // Verificar CPF existente
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        throw new Exception('CPF já cadastrado');
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Inserir usuário
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, email, cpf, senha, tipo_usuario, status)
        VALUES (?, ?, ?, ?, ?, 'pendente')
    ");
    
    $stmt->execute([
        $nome,
        $email,
        $cpf,
        password_hash($senha, PASSWORD_DEFAULT),
        $tipo_usuario
    ]);
    
    $id_usuario = $pdo->lastInsertId();
    
    // Dados específicos por tipo de usuário
    if ($tipo_usuario === 'medico') {
        $stmt = $pdo->prepare("
            INSERT INTO medicos (id_usuario, crm, especialidade, status, data_cadastro)
            VALUES (?, ?, ?, 'pendente', NOW())
        ");
        $stmt->execute([
            $id_usuario,
            $_POST['crm'],
            $_POST['especialidade']
        ]);
    } else if ($tipo_usuario === 'paciente') {
        $stmt = $pdo->prepare("
            INSERT INTO pacientes (id_usuario, medico, data_cirurgia, fisioterapeuta, problema, status, data_cadastro)
            VALUES (?, ?, ?, ?, ?, 'ativo', NOW())
        ");
        
        // Buscar ID do médico
        $medico_stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome = ? AND tipo_usuario = 'medico'");
        $medico_stmt->execute([$_POST['medico']]);
        $id_medico = $medico_stmt->fetchColumn();
        
        $stmt->execute([
            $id_usuario,
            $id_medico,
            $_POST['data_cirurgia'],
            $_POST['fisioterapeuta'],
            $_POST['problema']
        ]);
    }
    
    // Commit da transação
    $pdo->commit();
    
    // Definir mensagens de sessão
    $_SESSION['cadastro'] = 'pendente';
    $_SESSION['mensagem'] = 'Cadastro realizado com sucesso! ' . 
        ($tipo_usuario === 'medico' ? 
            'Aguarde a liberação pela administração do sistema.' : 
            'Aguarde a liberação pelo seu médico responsável.');
    
    // Redirecionar para login
    header('Location: /centraldojoelho/index.php?page=login');
    exit();
    
} catch (Exception $e) {
    // Rollback em caso de erro
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Mensagem de erro
    $_SESSION['error'] = $e->getMessage();
    
    // Redirecionar de volta para o cadastro
    header('Location: /centraldojoelho/index.php?page=cadastro');
    exit();
}
