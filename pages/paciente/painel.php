<?php
// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getConnection();

// Busca os dados do paciente
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome, u.email
        FROM usuarios u 
        LEFT JOIN pacientes p ON p.id_usuario = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $paciente = $stmt->fetch();

    // Se não existir registro na tabela pacientes, cria um
    if (!$paciente['id']) {
        $stmt = $pdo->prepare("
            INSERT INTO pacientes (id_usuario, data_cirurgia, medico, fisioterapeuta, problema, status)
            VALUES (?, CURRENT_DATE, '', '', '', 'pendente')
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Busca os dados novamente
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome, u.email
            FROM usuarios u 
            LEFT JOIN pacientes p ON p.id_usuario = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $paciente = $stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar/criar dados do paciente: " . $e->getMessage());
    $paciente = [
        'nome' => $_SESSION['user_nome'],
        'email' => '',
        'data_cirurgia' => date('Y-m-d'),
        'medico' => '',
        'fisioterapeuta' => '',
        'problema' => '',
        'status' => 'pendente'
    ];
}

// Busca os exercícios do paciente
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_exercicios
    FROM exercicios
    WHERE id_paciente = ?
");
$stmt->execute([$paciente['id']]);
$exercicios = $stmt->fetch();

// Busca as cirurgias do paciente
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_cirurgias
    FROM cirurgias
    WHERE id_paciente = ?
");
$stmt->execute([$paciente['id']]);
$cirurgias = $stmt->fetch();

// Busca os exames do paciente
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_exames
    FROM exames
    WHERE id_paciente = ?
");
$stmt->execute([$paciente['id']]);
$exames = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Paciente - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .card-dashboard {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .icon-large {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=paciente/painel">Painel</a>
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
        <div class="row mb-4">
            <div class="col">
                <h2>Meu Painel</h2>
                <p class="text-muted">Bem-vindo ao seu painel de controle</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Exercícios -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/exercicios'">
                    <div class="card-body text-center">
                        <i class="bi bi-activity icon-large text-primary"></i>
                        <h5 class="card-title">Exercícios</h5>
                        <p class="card-text">Visualize e acompanhe seus exercícios prescritos</p>
                        <span class="badge bg-primary"><?php echo $exercicios['total_exercicios']; ?> exercícios</span>
                    </div>
                </div>
            </div>

            <!-- Cirurgias -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/cirurgias'">
                    <div class="card-body text-center">
                        <i class="bi bi-bandaid icon-large text-danger"></i>
                        <h5 class="card-title">Cirurgias</h5>
                        <p class="card-text">Informações sobre suas cirurgias</p>
                    </div>
                </div>
            </div>

            <!-- Perguntas -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/perguntas'">
                    <div class="card-body text-center">
                        <i class="bi bi-chat-dots icon-large text-info"></i>
                        <h5 class="card-title">Perguntas</h5>
                        <p class="card-text">Tire suas dúvidas com a equipe médica</p>
                    </div>
                </div>
            </div>

            <!-- Exames -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/exames'">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text icon-large text-warning"></i>
                        <h5 class="card-title">Exames</h5>
                        <p class="card-text">Acesse seus exames e resultados</p>
                    </div>
                </div>
            </div>

            <!-- Depoimentos -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/depoimentos'">
                    <div class="card-body text-center">
                        <i class="bi bi-chat-quote icon-large text-secondary"></i>
                        <h5 class="card-title">Depoimentos</h5>
                        <p class="card-text">Compartilhe sua experiência</p>
                    </div>
                </div>
            </div>

            <!-- Documentos -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/documentos'">
                    <div class="card-body text-center">
                        <i class="bi bi-folder icon-large text-success"></i>
                        <h5 class="card-title">Documentos</h5>
                        <p class="card-text">Acesse seus documentos médicos</p>
                    </div>
                </div>
            </div>

            <!-- Vídeos -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/videos'">
                    <div class="card-body text-center">
                        <i class="bi bi-play-circle icon-large text-danger"></i>
                        <h5 class="card-title">Vídeos</h5>
                        <p class="card-text">Assista vídeos educativos</p>
                    </div>
                </div>
            </div>

            <!-- Fisioterapeuta -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/fisioterapeuta'">
                    <div class="card-body text-center">
                        <i class="bi bi-person icon-large text-primary"></i>
                        <h5 class="card-title">Fisioterapeuta</h5>
                        <p class="card-text">Informações do seu fisioterapeuta</p>
                    </div>
                </div>
            </div>

            <!-- Médicos -->
            <div class="col-md-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=paciente/medicos'">
                    <div class="card-body text-center">
                        <i class="bi bi-people icon-large text-success"></i>
                        <h5 class="card-title">Médicos</h5>
                        <p class="card-text">Informações da sua equipe médica</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Perfil -->
    <div class="modal fade" id="perfilModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Meu Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($paciente['nome']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($paciente['email']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data da Cirurgia</label>
                        <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($paciente['data_cirurgia'])); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Médico</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($paciente['medico']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fisioterapeuta</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($paciente['fisioterapeuta']); ?>" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="window.location='alterar_senha.php'">Alterar Senha</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
