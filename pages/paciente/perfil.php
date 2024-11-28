<?php
// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$conn = getConnection();
$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    try {
        // Verifica se o email já está em uso por outro usuário
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = "Este e-mail já está em uso por outro usuário.";
        } else {
            // Inicia a transação
            $conn->beginTransaction();

            // Atualiza nome e email
            if (!empty($nome) && !empty($email)) {
                $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
                $stmt->execute([$nome, $email, $user_id]);
                $msg = "Informações atualizadas com sucesso!";
            }

            // Se forneceu senha atual, tenta alterar a senha
            if (!empty($senha_atual)) {
                // Verifica a senha atual
                $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id]);
                $usuario = $stmt->fetch();

                if (password_verify($senha_atual, $usuario['senha'])) {
                    if (!empty($nova_senha) && !empty($confirmar_senha)) {
                        if ($nova_senha === $confirmar_senha) {
                            if (strlen($nova_senha) >= 6) {
                                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                                $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                                $stmt->execute([$senha_hash, $user_id]);
                                $msg .= " Senha alterada com sucesso!";
                            } else {
                                $error = "A nova senha deve ter pelo menos 6 caracteres.";
                            }
                        } else {
                            $error = "A nova senha e a confirmação não coincidem.";
                        }
                    } else {
                        $error = "Por favor, preencha a nova senha e a confirmação.";
                    }
                } else {
                    $error = "Senha atual incorreta.";
                }
            }

            if (empty($error)) {
                $conn->commit();
            } else {
                $conn->rollBack();
            }
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Erro ao atualizar informações: " . $e->getMessage();
    }
}

// Busca os dados atuais do usuário
$stmt = $conn->prepare("
    SELECT u.nome, u.email, p.data_cirurgia, p.problema
    FROM usuarios u 
    LEFT JOIN pacientes p ON u.id = p.id_usuario 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .bg-primary {
            background-color: #231F5D !important;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
            border-radius: 15px;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #231F5D;">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=paciente/perfil">
                            <i class="bi bi-person-circle"></i> Meu Perfil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Meu Perfil</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Data da Cirurgia</label>
                                <input type="text" class="form-control" 
                                    value="<?php echo $usuario['data_cirurgia'] ? date('d/m/Y', strtotime($usuario['data_cirurgia'])) : 'Não informada'; ?>" 
                                    readonly>
                                <div class="form-text">Data informada no momento do cadastro.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Problema</label>
                                <input type="text" class="form-control" 
                                    value="<?php echo htmlspecialchars($usuario['problema'] ?? 'Não informado'); ?>" 
                                    readonly>
                            </div>

                            <hr class="my-4">

                            <h6 class="mb-3">Alterar Senha</h6>

                            <div class="mb-3">
                                <label for="senha_atual" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                                <div class="form-text">Preencha apenas se desejar alterar sua senha.</div>
                            </div>

                            <div class="mb-3">
                                <label for="nova_senha" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                            </div>

                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                                <a href="index.php?page=paciente/painel" class="btn btn-secondary">Voltar ao Painel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
