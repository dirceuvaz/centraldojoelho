<?php
// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Verifica se foi fornecido um ID de pergunta
if (!isset($_GET['id'])) {
    header('Location: index.php?page=medico/perguntas');
    exit;
}

$id_pergunta = (int)$_GET['id'];
$pdo = getConnection();

// Busca os detalhes da pergunta
try {
    $stmt = $pdo->prepare("
        SELECT p.*, COALESCE(u.nome, 'Pergunta do Admin') as nome_paciente
        FROM perguntas p
        LEFT JOIN usuarios u ON p.id_paciente = u.id
        WHERE p.id = ? AND p.status = 'pendente'
    ");
    $stmt->execute([$id_pergunta]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pergunta) {
        $_SESSION['error'] = "Pergunta não encontrada ou já foi respondida.";
        header('Location: index.php?page=medico/perguntas');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar pergunta: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao carregar a pergunta.";
    header('Location: index.php?page=medico/perguntas');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Pergunta - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=medico/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=medico/painel">Painel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=medico/perguntas">Perguntas</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3 text-white">
                        <i class="bi bi-person"></i> 
                        Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>
                    </span>
                    <a class="btn btn-light btn-sm" href="index.php?page=login_process&logout=1">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?page=medico/painel">Painel</a></li>
                        <li class="breadcrumb-item"><a href="index.php?page=medico/perguntas">Perguntas</a></li>
                        <li class="breadcrumb-item active">Responder Pergunta</li>
                    </ol>
                </nav>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-chat-dots"></i> 
                            Responder Pergunta
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Paciente:</strong> <?php echo htmlspecialchars($pergunta['nome_paciente']); ?></p>
                                    <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="fw-bold mb-0">Pergunta</h6>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo htmlspecialchars($pergunta['titulo']); ?>
                                </h6>
                                <p class="card-text">
                                    <?php echo nl2br(htmlspecialchars($pergunta['descricao'])); ?>
                                </p>
                            </div>
                        </div>

                        <form action="index.php?page=medico/responder_pergunta_process" method="POST">
                            <input type="hidden" name="id_pergunta" value="<?php echo $pergunta['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="resposta" class="form-label">Sua Resposta</label>
                                <textarea class="form-control" id="resposta" name="resposta" rows="5" required></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php?page=medico/perguntas" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Voltar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Enviar Resposta
                                </button>
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
