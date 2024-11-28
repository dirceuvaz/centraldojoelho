<?php
session_start();
require_once 'config/database.php';

// Processa o logout
if (isset($_GET['logout'])) {
    // Limpa todas as variáveis de sessão
    $_SESSION = array();
    
    // Destrói o cookie da sessão se existir
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }
    
    // Remover cookie de lembrar-me ao fazer logout
    setcookie('remember_email', '', time() - 3600, '/');
    
    // Destrói a sessão
    session_destroy();
    
    // Redireciona para a página de login
    header('Location: index.php?page=login&msg=logout');
    exit;
}

// Verifica se recebeu os dados do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $lembrar = isset($_POST['lembrar']);
    
    // Verifica se os campos estão preenchidos
    if (empty($email) || empty($senha)) {
        header('Location: index.php?page=login&error=empty');
        exit;
    }
    
    try {
        // Conecta ao banco de dados
        $pdo = getConnection();
        
        // Busca o usuário
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? AND status = "ativo"');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        // Verifica se encontrou o usuário e a senha está correta
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Cria a sessão
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_nome'] = $usuario['nome'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
            
            // Se marcou "Lembrar-me", salva o email em um cookie por 30 dias
            if ($lembrar) {
                setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
            } else {
                // Se não marcou, remove o cookie se existir
                setcookie('remember_email', '', time() - 3600, '/');
            }
            
            // Redireciona baseado no tipo de usuário
            switch($usuario['tipo_usuario']) {
                case 'admin':
                    $pagina = 'admin/painel';
                    break;
                case 'paciente':
                    $pagina = 'paciente/painel';
                    break;
                case 'medico':
                    $pagina = 'medico/painel';
                    break;
                case 'fisioterapeuta':
                    $pagina = 'fisioterapeuta/painel';
                    break;
                default:
                    $pagina = 'login';
            }
            
            header("Location: index.php?page=$pagina");
            exit;
        }
        
        // Se chegou aqui, o login falhou
        header('Location: index.php?page=login&error=invalid');
        exit;
        
    } catch (PDOException $e) {
        // Em caso de erro no banco
        header('Location: index.php?page=login&error=database');
        exit;
    }
}
