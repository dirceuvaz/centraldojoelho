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

// Busca as cirurgias do paciente
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_cirurgias
    FROM cirurgias
    WHERE id_paciente = ?
");
$stmt->execute([$paciente['id']]);
$cirurgias = $stmt->fetch();

// Busca os exercícios do paciente
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_exercicios
    FROM exercicios
    WHERE id_paciente = ?
");
$stmt->execute([$paciente['id']]);
$exercicios = $stmt->fetch();

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
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .icon-large {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .bg-primary {
            background-color: #0d6efd !important;
        }
        .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .card-text {
            color: #6c757d;
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
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#perfilModal">
                            <i class="bi bi-person-circle"></i> Perfil
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
        <div class="row mb-4">
            <div class="col">
                <h2>Painel do Paciente</h2>
                <p class="text-muted">Bem-vindo(a), <?php echo htmlspecialchars($paciente['nome']); ?></p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Exercícios -->
            <div class="col-md-3">
                <a href="index.php?page=paciente/exercicios" class="text-decoration-none">
                    <div class="card card-dashboard h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-activity icon-large text-primary"></i>
                            <h3 class="card-title"><?php echo $exercicios['total_exercicios']; ?></h3>
                            <p class="card-text">Exercícios</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="index.php?page=paciente/perguntas" class="text-decoration-none">
                    <div class="card card-dashboard h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-question-circle icon-large text-primary"></i>
                            <h3 class="card-title">Perguntas</h3>
                            <p class="card-text">Tire suas dúvidas</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="index.php?page=paciente/semanas" class="text-decoration-none">
                    <div class="card card-dashboard h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-week icon-large text-primary"></i>
                            <h3 class="card-title">Semanas</h3>
                            <p class="card-text">Acompanhe sua evolução semanal</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="index.php?page=paciente/reabilitacao" class="text-decoration-none">
                    <div class="card card-dashboard h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-clipboard2-pulse icon-large text-primary"></i>
                            <h3 class="card-title">Reabilitação</h3>
                            <p class="card-text">Minhas Orientações</p>
                        </div>
                    </div>
                </a>
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
