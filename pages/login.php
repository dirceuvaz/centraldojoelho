<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: url('assets/images/clinic-background.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
        }
        .login-logo {
            width: 150px;
            height: auto;
            margin-bottom: 1.5rem;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="login-container">
        <div class="text-center mb-4">
            <img src="assets/images/logo-cj.png" alt="Logo Central do Joelho" class="login-logo" style="width: 120px; border-radius: 25px;">
            <h2 class="mb-3">Faça o seu Login</h2>
        </div>

        <?php if (isset($_SESSION['cadastro']) && $_SESSION['cadastro'] === 'pendente'): ?>
            <div class="alert alert-warning" role="alert">
                <h5 class="alert-heading"><i class="bi bi-clock-history"></i> Cadastro Realizado!</h5>
                <p class="mb-0"><?php echo $_SESSION['mensagem']; ?></p>
            </div>
            <?php 
            // Limpa as mensagens da sessão após exibir
            unset($_SESSION['cadastro']);
            unset($_SESSION['mensagem']);
            unset($_SESSION['tipo_usuario']);
            ?>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logout'): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill"></i> Você saiu do sistema com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php 
                $mensagens = [
                    'invalid' => 'Email ou senha incorretos',
                    'empty' => 'Por favor, preencha todos os campos',
                    'unauthorized' => 'Seu acesso ainda não foi autorizado. Por favor, aguarde a liberação.',
                    'inactive' => 'Sua conta está inativa. Entre em contato com o suporte.'
                ];
                echo $mensagens[$_GET['error']] ?? 'Erro ao fazer login';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso'): ?>
            <div class="alert alert-success">
                Cadastro realizado com sucesso! Aguarde a aprovação do administrador.
            </div>
        <?php endif; ?>

        <form action="index.php?page=login_process" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" required 
                           value="<?php echo isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : ''; ?>">
                </div>
            </div>
            <div class="mb-4">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="lembrar" name="lembrar" 
                       <?php echo isset($_COOKIE['remember_email']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="lembrar">Lembrar-me</label>
            </div>
            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
        </form>
        <a href="index.php?page=cadastro" class="btn btn-warning w-100 mt-3">
            Novo Paciente? Cadastre-se
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
